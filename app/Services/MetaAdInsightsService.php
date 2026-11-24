<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaAdInsightsService
{
    public const DATE_PRESETS = [
        'this_month' => 'This Month',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7d' => 'Last 7 Days',
        'last_30d' => 'Last 30 Days',
        'last_month' => 'Last Month',
        'maximum' => 'Lifetime (Maximum)',
        'custom' => 'Custom Date Range',
    ];

    private const ZERO_DECIMAL_CURRENCIES = ['JPY', 'KRW', 'VND', 'CLP', 'ISK', 'UGX', 'XAF', 'XOF'];

    public static function normalizeAdAccountId(?string $adAccountId): string
    {
        $clean = trim((string) $adAccountId);
        if ($clean === '') {
            return '';
        }

        if (Str::startsWith(strtolower($clean), 'act_')) {
            return 'act_' . substr($clean, 4);
        }

        return 'act_' . $clean;
    }

    public static function currencySymbol(?string $currency): string
    {
        return match (strtoupper((string) $currency)) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED ',
            'SGD' => 'S$',
            default => strtoupper((string) $currency) ? strtoupper((string) $currency) . ' ' : '₹',
        };
    }

    public function fetchAccountSummary(string $adAccountId, string $accessToken, string $graphVersion = 'v20.0'): array
    {
        $normalizedId = self::normalizeAdAccountId($adAccountId);
        if ($normalizedId === '' || trim($accessToken) === '') {
            return ['success' => false, 'data' => null, 'error' => 'Ad Account ID and Access Token are required.'];
        }

        try {
            $response = Http::timeout(20)->get("https://graph.facebook.com/{$graphVersion}/{$normalizedId}", [
                'fields' => 'id,account_id,name,currency,account_status,amount_spent,balance,spend_cap,funding_source_details',
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => $this->extractMetaError($response->json(), $response->body()),
                ];
            }

            $payload = $response->json() ?? [];
            $currency = strtoupper((string) ($payload['currency'] ?? 'INR'));
            $divisor = in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100;

            return [
                'success' => true,
                'data' => [
                    'id' => $payload['id'] ?? $normalizedId,
                    'account_id' => $payload['account_id'] ?? Str::after($normalizedId, 'act_'),
                    'name' => $payload['name'] ?? $normalizedId,
                    'currency' => $currency,
                    'currency_symbol' => self::currencySymbol($currency),
                    'account_status' => $payload['account_status'] ?? null,
                    'amount_spent' => isset($payload['amount_spent']) ? ((float) $payload['amount_spent']) / $divisor : null,
                    'balance' => isset($payload['balance']) ? ((float) $payload['balance']) / $divisor : null,
                    'spend_cap' => isset($payload['spend_cap']) && (float) $payload['spend_cap'] > 0
                        ? ((float) $payload['spend_cap']) / $divisor
                        : null,
                    'funding_source' => $payload['funding_source_details']['display_string'] ?? null,
                ],
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $this->sanitizeMessage($e->getMessage()),
            ];
        }
    }

    public function fetchInsights(string $adAccountId, string $accessToken, array $options = []): array
    {
        $normalizedId = self::normalizeAdAccountId($adAccountId);
        $graphVersion = trim((string) ($options['graph_version'] ?? 'v20.0')) ?: 'v20.0';
        $level = ($options['level'] ?? 'campaign') === 'account' ? 'account' : 'campaign';
        $datePreset = (string) ($options['date_preset'] ?? 'this_month');
        $dateFrom = trim((string) ($options['date_from'] ?? ''));
        $dateTo = trim((string) ($options['date_to'] ?? ''));

        if ($normalizedId === '' || trim($accessToken) === '') {
            return [
                'success' => false,
                'rows' => [],
                'totals' => $this->emptyTotals(),
                'error' => 'Ad Account ID and Access Token are required.',
            ];
        }

        $fields = $level === 'account'
            ? 'account_id,account_name,spend,impressions,clicks,reach,actions,cost_per_action_type'
            : 'campaign_id,campaign_name,spend,impressions,clicks,reach,actions,cost_per_action_type';

        $queryParams = [
            'level' => $level,
            'fields' => $fields,
            'limit' => 200,
            'access_token' => $accessToken,
        ];

        if ($datePreset === 'custom' && $dateFrom !== '' && $dateTo !== '') {
            $queryParams['time_range'] = json_encode([
                'since' => $dateFrom,
                'until' => $dateTo,
            ]);
        } else {
            $validPreset = array_key_exists($datePreset, self::DATE_PRESETS) && $datePreset !== 'custom'
                ? $datePreset
                : 'this_month';
            $queryParams['date_preset'] = $validPreset;
        }

        try {
            $rawRows = [];
            $nextUrl = "https://graph.facebook.com/{$graphVersion}/{$normalizedId}/insights";
            $pageCount = 0;

            while ($nextUrl && $pageCount < 5) {
                $response = $pageCount === 0
                    ? Http::timeout(25)->get($nextUrl, $queryParams)
                    : Http::timeout(25)->get($nextUrl);

                if ($response->failed()) {
                    return [
                        'success' => false,
                        'rows' => [],
                        'totals' => $this->emptyTotals(),
                        'error' => $this->extractMetaError($response->json(), $response->body()),
                    ];
                }

                $json = $response->json() ?? [];
                $pageData = $json['data'] ?? [];
                if (is_array($pageData)) {
                    $rawRows = array_merge($rawRows, $pageData);
                }

                $nextUrl = $json['paging']['next'] ?? null;
                $pageCount++;
            }

            $rows = collect($rawRows)->map(function (array $row) use ($level) {
                $spend = (float) ($row['spend'] ?? 0);
                $impressions = (int) ($row['impressions'] ?? 0);
                $clicks = (int) ($row['clicks'] ?? 0);
                $reach = (int) ($row['reach'] ?? 0);

                $cpc = $clicks > 0 ? $spend / $clicks : 0;
                $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;

                return [
                    'id' => $level === 'account' ? ($row['account_id'] ?? '') : ($row['campaign_id'] ?? ''),
                    'name' => $level === 'account' ? ($row['account_name'] ?? 'Ad Account') : ($row['campaign_name'] ?? 'Unnamed Campaign'),
                    'date_start' => $row['date_start'] ?? null,
                    'date_stop' => $row['date_stop'] ?? null,
                    'spend' => $spend,
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'reach' => $reach,
                    'cpc' => $cpc,
                    'ctr' => $ctr,
                    'actions' => $this->formatActions($row['actions'] ?? [], $row['cost_per_action_type'] ?? []),
                ];
            })
                ->sortByDesc('spend')
                ->values()
                ->all();

            $totals = $this->computeTotals($rows);

            return [
                'success' => true,
                'rows' => $rows,
                'totals' => $totals,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'rows' => [],
                'totals' => $this->emptyTotals(),
                'error' => $this->sanitizeMessage($e->getMessage()),
            ];
        }
    }

    public function computeTotals(array $rows): array
    {
        $totalSpend = 0.0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalReach = 0;

        foreach ($rows as $row) {
            $totalSpend += (float) ($row['spend'] ?? 0);
            $totalImpressions += (int) ($row['impressions'] ?? 0);
            $totalClicks += (int) ($row['clicks'] ?? 0);
            $totalReach += (int) ($row['reach'] ?? 0);
        }

        return [
            'campaigns_count' => count($rows),
            'total_spend' => $totalSpend,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_reach' => $totalReach,
            'avg_cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0.0,
            'avg_ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0.0,
        ];
    }

    private function emptyTotals(): array
    {
        return [
            'campaigns_count' => 0,
            'total_spend' => 0.0,
            'total_impressions' => 0,
            'total_clicks' => 0,
            'total_reach' => 0,
            'avg_cpc' => 0.0,
            'avg_ctr' => 0.0,
        ];
    }

    public function fetchPaymentHistory(string $adAccountId, string $accessToken, array $options = []): array
    {
        $normalizedId = self::normalizeAdAccountId($adAccountId);
        $graphVersion = trim((string) ($options['graph_version'] ?? 'v20.0')) ?: 'v20.0';
        $datePreset = (string) ($options['date_preset'] ?? 'this_month');
        $dateFrom = trim((string) ($options['date_from'] ?? ''));
        $dateTo = trim((string) ($options['date_to'] ?? ''));

        if ($normalizedId === '' || trim($accessToken) === '') {
            return [
                'success' => false,
                'rows' => [],
                'totals' => $this->emptyPaymentTotals(),
                'error' => 'Ad Account ID and Access Token are required.',
            ];
        }

        $accountSummary = $this->fetchAccountSummary($normalizedId, $accessToken, $graphVersion);
        $currency = $accountSummary['data']['currency'] ?? 'INR';
        $divisor = in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100;

        [$startTimestamp, $endTimestamp] = $this->resolveDateTimestamps($datePreset, $dateFrom, $dateTo);

        $rawEvents = [];
        $errorMsg = null;

        try {
            // Directly query activities without category filter
            $response = Http::timeout(25)->get("https://graph.facebook.com/{$graphVersion}/{$normalizedId}/activities", [
                'fields' => 'actor_name,date_time_in_timezone,event_time,event_type,extra_data,object_name,object_type,translated_event_type',
                'limit' => 200,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $rawEvents = $response->json()['data'] ?? [];
            } else {
                $errorMsg = $this->extractMetaError($response->json(), $response->body());
            }
        } catch (\Throwable $e) {
            $errorMsg = $this->sanitizeMessage($e->getMessage());
        }

        if ($errorMsg !== null && empty($rawEvents)) {
            return [
                'success' => false,
                'rows' => [],
                'totals' => $this->emptyPaymentTotals(),
                'error' => $errorMsg,
            ];
        }

        $billingEventTypes = [
            'funding_event_successful',
            'funding_event_failed',
            'funding_event_initiated',
            'ad_account_billing_charge',
            'ad_account_billing_decline',
            'ad_account_billing_refund',
            'ad_account_billing_charge_back',
            'ad_account_billing_charge_back_reversal',
            'add_funding_source',
            'remove_funding_source',
            'billing_threshold_change',
        ];

        $rows = [];
        foreach ($rawEvents as $event) {
            $type = (string) ($event['event_type'] ?? '');

            $isBilling = in_array($type, $billingEventTypes, true)
                || Str::contains(strtolower($type), ['billing', 'charge', 'payment', 'refund', 'funding']);

            if (! $isBilling) {
                continue;
            }

            $eventTime = null;
            if (! empty($event['event_time'])) {
                $eventTime = is_numeric($event['event_time']) ? (int) $event['event_time'] : strtotime((string) $event['event_time']);
            } elseif (! empty($event['date_time_in_timezone'])) {
                $eventTime = strtotime((string) $event['date_time_in_timezone']);
            }

            if ($eventTime) {
                if ($startTimestamp !== null && $eventTime < $startTimestamp) {
                    continue;
                }
                if ($endTimestamp !== null && $eventTime > $endTimestamp) {
                    continue;
                }
            }

            $rawExtra = $event['extra_data'] ?? null;
            if (is_string($rawExtra)) {
                $extra = json_decode($rawExtra, true) ?? [];
            } elseif (is_array($rawExtra)) {
                $extra = $rawExtra;
            } else {
                $extra = [];
            }

            $rawAmount = $extra['amount'] ?? $extra['new_value'] ?? $extra['charge_amount'] ?? $extra['provider_amount'] ?? $extra['value'] ?? null;
            $amount = 0.0;
            if ($rawAmount !== null && is_numeric($rawAmount)) {
                $numericVal = (float) $rawAmount;
                // Meta typically returns amount in smallest currency unit (cents / paise)
                $amount = ($numericVal > 100 && ! Str::contains((string) $rawAmount, '.'))
                    ? ($numericVal / $divisor)
                    : $numericVal;
            }

            $statusBadge = match ($type) {
                'funding_event_successful' => 'success',
                'ad_account_billing_charge' => 'success',
                'funding_event_failed', 'ad_account_billing_decline' => 'failed',
                'ad_account_billing_refund' => 'refund',
                'ad_account_billing_charge_back' => 'chargeback',
                'add_funding_source' => 'info',
                'remove_funding_source' => 'warning',
                default => 'default',
            };

            $statusLabel = match ($type) {
                'funding_event_successful' => 'Money Added (Funds Added)',
                'ad_account_billing_charge' => 'Account Billed / Charged',
                'funding_event_failed' => 'Fund Addition Failed',
                'ad_account_billing_decline' => 'Payment Declined',
                'ad_account_billing_refund' => 'Refunded',
                'ad_account_billing_charge_back' => 'Chargeback',
                'add_funding_source' => 'Funding Source Added',
                'remove_funding_source' => 'Funding Source Removed',
                default => $event['translated_event_type'] ?? Str::title(str_replace('_', ' ', $type)),
            };

            $eventCurrency = strtoupper((string) ($extra['currency'] ?? $currency));

            // Extract payment method / type
            $paymentMethod = $extra['network_id']
                ?? $extra['payment_method']
                ?? $extra['funding_source']
                ?? $extra['payment_type']
                ?? $extra['card_type']
                ?? (isset($extra['brand'], $extra['last4']) ? "{$extra['brand']} •••• {$extra['last4']}" : null)
                ?? ($accountSummary['data']['funding_source'] ?? 'Credit/Debit Card / Balance');

            $billingReason = match ($type) {
                'funding_event_successful' => 'Prepaid Balance Top-up',
                'ad_account_billing_charge' => 'Ad Spend Billing Deduction',
                default => $extra['billing_reason'] ?? $extra['charge_type'] ?? null,
            };

            $rows[] = [
                'event_time' => $eventTime ? date('Y-m-d H:i:s', $eventTime) : ($event['date_time_in_timezone'] ?? null),
                'timestamp' => $eventTime ?: 0,
                'event_type' => $type,
                'status_badge' => $statusBadge,
                'status_label' => $statusLabel,
                'amount' => $amount,
                'currency' => $eventCurrency,
                'currency_symbol' => self::currencySymbol($eventCurrency),
                'actor_name' => $event['actor_name'] ?? 'Meta System',
                'payment_method' => $paymentMethod,
                'billing_reason' => $billingReason ? Str::title(str_replace('_', ' ', $billingReason)) : null,
                'transaction_id' => $extra['transaction_id'] ?? $extra['charge_id'] ?? $extra['invoice_id'] ?? null,
                'extra_data' => $extra,
                'raw_event' => $event,
            ];
        }

        usort($rows, fn ($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        $totals = $this->computePaymentTotals($rows);

        return [
            'success' => true,
            'rows' => $rows,
            'totals' => $totals,
            'account_summary' => $accountSummary['data'] ?? null,
            'error' => null,
        ];
    }

    public function resolveDateTimestamps(string $preset, string $dateFrom = '', string $dateTo = ''): array
    {
        $now = \Illuminate\Support\Carbon::now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay()->timestamp, $now->copy()->endOfDay()->timestamp],
            'yesterday' => [$now->copy()->subDay()->startOfDay()->timestamp, $now->copy()->subDay()->endOfDay()->timestamp],
            'last_7d' => [$now->copy()->subDays(7)->startOfDay()->timestamp, $now->copy()->endOfDay()->timestamp],
            'last_30d' => [$now->copy()->subDays(30)->startOfDay()->timestamp, $now->copy()->endOfDay()->timestamp],
            'this_month' => [$now->copy()->startOfMonth()->startOfDay()->timestamp, $now->copy()->endOfDay()->timestamp],
            'last_month' => [$now->copy()->subMonth()->startOfMonth()->startOfDay()->timestamp, $now->copy()->subMonth()->endOfMonth()->timestamp],
            'custom' => [
                $dateFrom !== '' ? \Illuminate\Support\Carbon::parse($dateFrom)->startOfDay()->timestamp : null,
                $dateTo !== '' ? \Illuminate\Support\Carbon::parse($dateTo)->endOfDay()->timestamp : null,
            ],
            default => [null, null],
        };
    }

    public function computePaymentTotals(array $rows): array
    {
        $totalFundsAdded = 0.0;
        $fundsAddedCount = 0;
        $totalCharges = 0.0;
        $chargesCount = 0;
        $declinedCount = 0;
        $refundsCount = 0;
        $refundsTotal = 0.0;

        foreach ($rows as $row) {
            $amt = (float) ($row['amount'] ?? 0);
            if ($row['event_type'] === 'funding_event_successful') {
                $totalFundsAdded += $amt;
                $fundsAddedCount++;
            } elseif ($row['event_type'] === 'ad_account_billing_charge') {
                $totalCharges += $amt;
                $chargesCount++;
            } elseif (in_array($row['event_type'], ['ad_account_billing_decline', 'funding_event_failed'], true)) {
                $declinedCount++;
            } elseif ($row['event_type'] === 'ad_account_billing_refund') {
                $refundsCount++;
                $refundsTotal += $amt;
            }
        }

        // Total paid to Meta: In prepaid accounts, actual payment to Meta is the funds loaded (Net Banking, UPI).
        // In postpaid accounts, it's the direct billing charges.
        $totalPaid = $totalFundsAdded > 0 ? $totalFundsAdded : $totalCharges;

        return [
            'total_paid' => $totalPaid,
            'total_funds_added' => $totalFundsAdded,
            'funds_added_count' => $fundsAddedCount,
            'total_charges' => $totalCharges,
            'charges_count' => $chargesCount + $fundsAddedCount,
            'declined_count' => $declinedCount,
            'refunds_count' => $refundsCount,
            'refunds_total' => $refundsTotal,
            'events_count' => count($rows),
        ];
    }

    public function emptyPaymentTotals(): array
    {
        return [
            'total_paid' => 0.0,
            'charges_count' => 0,
            'declined_count' => 0,
            'refunds_count' => 0,
            'refunds_total' => 0.0,
            'events_count' => 0,
        ];
    }

    private function formatActions(array $actions, array $costPerActions): array
    {
        $costMap = [];
        foreach ($costPerActions as $item) {
            if (! empty($item['action_type'])) {
                $costMap[$item['action_type']] = (float) ($item['value'] ?? 0);
            }
        }

        $priorityTypes = [
            'lead',
            'onsite_conversion.lead_grouped',
            'onsite_conversion.messaging_conversation_started_7d',
            'omni_purchase',
            'purchase',
            'link_click',
            'landing_page_view',
            'post_engagement',
        ];

        $formatted = [];
        foreach ($actions as $action) {
            $type = (string) ($action['action_type'] ?? '');
            $val = (int) round((float) ($action['value'] ?? 0));
            if ($type === '' || $val <= 0) {
                continue;
            }

            $priorityIndex = array_search($type, $priorityTypes, true);
            $formatted[] = [
                'type' => $type,
                'label' => $this->humanizeActionType($type),
                'value' => $val,
                'cost_per_action' => $costMap[$type] ?? null,
                'priority' => $priorityIndex !== false ? $priorityIndex : 99,
            ];
        }

        usort($formatted, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return array_slice($formatted, 0, 4);
    }

    private function humanizeActionType(string $type): string
    {
        $map = [
            'lead' => 'Leads',
            'onsite_conversion.lead_grouped' => 'Meta Leads',
            'onsite_conversion.messaging_conversation_started_7d' => 'Messages',
            'omni_purchase' => 'Purchases',
            'purchase' => 'Purchases',
            'link_click' => 'Link Clicks',
            'landing_page_view' => 'Landing Page Views',
            'post_engagement' => 'Post Engagement',
            'page_engagement' => 'Page Engagement',
            'video_view' => 'Video Views',
        ];

        if (isset($map[$type])) {
            return $map[$type];
        }

        return Str::title(str_replace(['.', '_'], ' ', Str::afterLast($type, '.')));
    }

    private function extractMetaError(?array $json, string $rawBody): string
    {
        $message = data_get($json, 'error.message')
            ?: ($rawBody !== '' ? Str::limit($rawBody, 220) : 'Meta Graph API request failed.');

        return $this->sanitizeMessage((string) $message);
    }

    private function sanitizeMessage(string $message): string
    {
        return (string) preg_replace('/access_token=([^&\s]+)/i', 'access_token=***', $message);
    }
}
