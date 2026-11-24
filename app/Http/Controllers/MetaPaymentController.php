<?php

namespace App\Http\Controllers;

use App\Models\AdAccountMaster;
use App\Models\CampaignMaster;
use App\Models\MetaAdSetting;
use App\Services\MetaAdInsightsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MetaPaymentController extends Controller
{
    public function __construct(private readonly MetaAdInsightsService $insightsService)
    {
    }

    public function index(Request $request): View|StreamedResponse
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $savedAccounts = $this->loadSavedAccounts($companyId);
        $adAccountMasters = AdAccountMaster::active()
            ->orderBy('account_name')
            ->get();

        $fallbackToken = $this->resolveFallbackToken($savedAccounts);
        $connectedAccounts = $this->mergeConnectedAccounts($savedAccounts, $adAccountMasters, $fallbackToken);

        $requestedAccount = trim((string) $request->query('ad_account_id', ''));
        if ($requestedAccount === '') {
            $selectedAccountFilter = $connectedAccounts->count() > 1
                ? 'all'
                : (string) ($connectedAccounts->first()['ad_account_id'] ?? '');
        } elseif ($requestedAccount === 'all') {
            $selectedAccountFilter = 'all';
        } else {
            $selectedAccountFilter = MetaAdInsightsService::normalizeAdAccountId($requestedAccount);
        }

        $datePreset = (string) $request->query('date_preset', 'this_month');
        if (! array_key_exists($datePreset, MetaAdInsightsService::DATE_PRESETS)) {
            $datePreset = 'this_month';
        }
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $search = trim((string) $request->query('search', ''));
        $statusFilter = trim((string) $request->query('status', 'all'));
        $viewType = trim((string) $request->query('view_type', 'payments_only'));

        $targetAccounts = collect();
        if ($selectedAccountFilter === 'all') {
            $targetAccounts = $connectedAccounts;
        } elseif ($selectedAccountFilter !== '') {
            $matched = $connectedAccounts->firstWhere('ad_account_id', $selectedAccountFilter);
            if ($matched) {
                $targetAccounts = collect([$matched]);
            } elseif ($fallbackToken !== '') {
                $targetAccounts = collect([[
                    'id' => null,
                    'ad_account_id' => $selectedAccountFilter,
                    'account_label' => $selectedAccountFilter,
                    'graph_version' => 'v20.0',
                    'access_token' => $fallbackToken,
                    'masked_token' => $this->maskToken($fallbackToken),
                    'source' => 'adhoc',
                ]]);
            }
        }

        $isConfigured = $targetAccounts->contains(fn (array $acc) => ! empty($acc['access_token']) && ! empty($acc['ad_account_id']));

        $accountSummaries = [];
        $accountErrors = [];
        $rows = [];
        $paymentErrors = [];

        if ($isConfigured) {
            foreach ($targetAccounts as $account) {
                $actId = $account['ad_account_id'];
                $token = (string) ($account['access_token'] ?? '');
                $version = (string) ($account['graph_version'] ?? 'v20.0');
                $label = $account['account_label'] ?: $actId;

                if ($token === '') {
                    $paymentErrors[] = "{$label} ({$actId}): Missing access token.";
                    continue;
                }

                $paymentResult = $this->insightsService->fetchPaymentHistory($actId, $token, [
                    'graph_version' => $version,
                    'date_preset' => $datePreset,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]);

                if ($paymentResult['success']) {
                    $summaryData = $paymentResult['account_summary'] ?? [];
                    if (! empty($summaryData)) {
                        $summaryData['label'] = $account['account_label'] ?: ($summaryData['name'] ?? $actId);
                        $accountSummaries[] = $summaryData;
                    }

                    $accountDisplayName = $summaryData['name'] ?? $label;
                    $accCurrency = $summaryData['currency'] ?? 'INR';
                    $accCurrencySymbol = $summaryData['currency_symbol'] ?? MetaAdInsightsService::currencySymbol($accCurrency);

                    foreach ($paymentResult['rows'] as $row) {
                        $row['ad_account_id'] = $actId;
                        $row['ad_account_name'] = $accountDisplayName;
                        $row['currency_symbol'] = $row['currency_symbol'] ?: $accCurrencySymbol;
                        $rows[] = $row;
                    }
                } else {
                    $paymentErrors[] = "{$label} ({$actId}): {$paymentResult['error']}";
                }
            }

            // Calculate per-account payment stats for account-wise cards
            foreach ($accountSummaries as &$accSum) {
                $accId = $accSum['id'];
                $accRows = array_filter($rows, fn (array $r) => ($r['ad_account_id'] ?? '') === $accId);

                $accFunds = collect($accRows)->where('event_type', 'funding_event_successful');
                $accCharges = collect($accRows)->where('event_type', 'ad_account_billing_charge');

                $accSum['total_funds_added'] = (float) $accFunds->sum('amount');
                $accSum['total_charges'] = (float) $accCharges->sum('amount');
                $accSum['total_paid'] = $accSum['total_funds_added'] > 0 ? $accSum['total_funds_added'] : $accSum['total_charges'];
                $accSum['funds_count'] = $accFunds->count();
                $accSum['charges_count'] = $accCharges->count();
                $accSum['payments_count'] = $accSum['funds_count'] > 0 ? $accSum['funds_count'] : $accSum['charges_count'];

                // Methods breakdown (Net Banking, UPI, Cards)
                $methodSource = $accSum['funds_count'] > 0 ? $accFunds : $accCharges;
                $accSum['payment_methods'] = $methodSource
                    ->groupBy('payment_method')
                    ->map(fn ($grp) => [
                        'method' => $grp->first()['payment_method'] ?: 'Payment Method',
                        'count' => $grp->count(),
                        'total' => (float) $grp->sum('amount'),
                    ])
                    ->values()
                    ->all();
            }
            unset($accSum);

            // Filter view mode: only payment history (funds added & payments to Meta) or all activity
            if ($viewType === 'payments_only') {
                $hasFundsAdded = collect($rows)->contains('event_type', 'funding_event_successful');
                if ($hasFundsAdded) {
                    $rows = array_values(array_filter($rows, fn (array $r) => $r['event_type'] === 'funding_event_successful'));
                }
            }

            // Filter by search keyword
            if ($search !== '') {
                $needle = Str::lower($search);
                $rows = array_values(array_filter($rows, function (array $row) use ($needle) {
                    return Str::contains(Str::lower((string) ($row['status_label'] ?? '')), $needle)
                        || Str::contains(Str::lower((string) ($row['actor_name'] ?? '')), $needle)
                        || Str::contains(Str::lower((string) ($row['payment_method'] ?? '')), $needle)
                        || Str::contains(Str::lower((string) ($row['transaction_id'] ?? '')), $needle)
                        || Str::contains(Str::lower((string) ($row['ad_account_name'] ?? '')), $needle)
                        || Str::contains(Str::lower((string) ($row['ad_account_id'] ?? '')), $needle);
                }));
            }

            // Filter by status (charges, declines, refunds, funds_added)
            if ($statusFilter !== 'all' && $statusFilter !== '') {
                $rows = array_values(array_filter($rows, function (array $row) use ($statusFilter) {
                    return match ($statusFilter) {
                        'funds_added' => $row['event_type'] === 'funding_event_successful',
                        'billed' => $row['event_type'] === 'ad_account_billing_charge',
                        'charges' => in_array($row['event_type'], ['ad_account_billing_charge', 'funding_event_successful'], true),
                        'declines' => in_array($row['event_type'], ['ad_account_billing_decline', 'funding_event_failed'], true),
                        'refunds' => $row['event_type'] === 'ad_account_billing_refund',
                        default => true,
                    };
                }));
            }

            usort($rows, fn (array $a, array $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));
        }

        $totals = $this->insightsService->computePaymentTotals($rows);

        $singleSummary = count($accountSummaries) === 1 ? $accountSummaries[0] : null;
        $currencies = collect($accountSummaries)->pluck('currency')->filter()->unique()->values();
        $currency = $currencies->count() === 1 ? (string) $currencies->first() : 'INR';
        $currencySymbol = MetaAdInsightsService::currencySymbol($currency);

        if ($request->query('export') === 'csv' && $isConfigured && ! empty($rows)) {
            return $this->exportCsv($rows, $selectedAccountFilter ?: 'meta-payments', $datePreset, $currency);
        }

        return view('pages.accounts.ad_budget.meta_payments', [
            'savedAccounts' => $savedAccounts,
            'connectedAccounts' => $connectedAccounts,
            'hasAnyToken' => $fallbackToken !== '',
            'primaryMaskedToken' => $this->maskToken($fallbackToken),
            'selectedAccountFilter' => $selectedAccountFilter,
            'isMultiView' => $selectedAccountFilter === 'all' && $targetAccounts->count() > 1,
            'adAccountId' => $selectedAccountFilter === 'all' ? '' : $selectedAccountFilter,
            'datePresets' => MetaAdInsightsService::DATE_PRESETS,
            'datePreset' => $datePreset,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'viewType' => $viewType,
            'isConfigured' => $isConfigured,
            'accountSummary' => $singleSummary,
            'accountSummaries' => $accountSummaries,
            'rows' => $rows,
            'totals' => $totals,
            'paymentErrors' => $paymentErrors,
            'currency' => $currency,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user?->company_id;
        $savedAccounts = $this->loadSavedAccounts($companyId);
        $fallbackToken = $this->resolveFallbackToken($savedAccounts);

        $settingId = trim((string) $request->input('setting_id', ''));
        $existing = $settingId !== ''
            ? $savedAccounts->first(fn (array $acc) => (string) $acc['id'] === $settingId)
            : null;

        $tokenRequired = ! $existing && $fallbackToken === '';

        $validated = $request->validate([
            'setting_id' => ['nullable', 'string', 'max:64'],
            'ad_account_id' => ['required', 'string', 'max:100'],
            'account_label' => ['nullable', 'string', 'max:150'],
            'access_token' => [$tokenRequired ? 'required' : 'nullable', 'string', 'max:2000'],
            'graph_version' => ['nullable', 'string', 'max:20'],
        ]);

        $normalizedAccountId = MetaAdInsightsService::normalizeAdAccountId($validated['ad_account_id']);
        $token = trim((string) ($validated['access_token'] ?? ''));

        if ($token === '') {
            $token = $existing ? (string) $existing['access_token'] : $fallbackToken;
        }

        if ($token === '') {
            return redirect()
                ->route('accounts.ad-budget.meta-payments')
                ->withInput()
                ->with('error', 'Meta Marketing Access Token is required for this account.');
        }

        $graphVersion = trim((string) ($validated['graph_version'] ?? 'v20.0')) ?: 'v20.0';
        $accountLabel = trim((string) ($validated['account_label'] ?? '')) ?: null;

        if (Schema::hasTable('meta_ad_settings')) {
            $dbModel = null;
            if ($existing && $existing['source'] === 'db') {
                $dbModel = MetaAdSetting::find($existing['id']);
            } else {
                $dbModel = MetaAdSetting::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('ad_account_id', $normalizedAccountId)
                    ->first();
            }

            $payload = [
                'company_id' => $companyId,
                'ad_account_id' => $normalizedAccountId,
                'account_label' => $accountLabel,
                'access_token' => $token,
                'graph_version' => $graphVersion,
                'updated_by' => auth()->id(),
            ];

            if ($dbModel) {
                $dbModel->update($payload);
            } else {
                MetaAdSetting::create($payload);
            }
        } else {
            $records = $this->readLocalFallbackAccounts($companyId);
            $foundIndex = null;
            foreach ($records as $idx => $r) {
                if ($existing && (string) ($r['id'] ?? '') === (string) $existing['id']) {
                    $foundIndex = $idx;
                    break;
                }
                if (($r['ad_account_id'] ?? '') === $normalizedAccountId) {
                    $foundIndex = $idx;
                    break;
                }
            }

            $entry = [
                'id' => $existing['id'] ?? (string) Str::uuid(),
                'ad_account_id' => $normalizedAccountId,
                'account_label' => $accountLabel,
                'access_token' => Crypt::encryptString($token),
                'graph_version' => $graphVersion,
                'updated_at' => now()->toIso8601String(),
            ];

            if ($foundIndex !== null) {
                $records[$foundIndex] = $entry;
            } else {
                $records[] = $entry;
            }

            $this->writeLocalFallbackAccounts($companyId, $records);
        }

        return redirect()
            ->route('accounts.ad-budget.meta-payments', ['ad_account_id' => $normalizedAccountId])
            ->with('success', 'Meta Ad Account credentials saved successfully.');
    }

    public function deleteAccount(string $id): RedirectResponse
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        if (Schema::hasTable('meta_ad_settings') && is_numeric($id)) {
            $deleted = MetaAdSetting::query()
                ->where('id', (int) $id)
                ->when($companyId, fn ($q) => $q->where(function ($sub) use ($companyId) {
                    $sub->where('company_id', $companyId)->orWhereNull('company_id');
                }))
                ->delete();

            if ($deleted > 0) {
                return redirect()
                    ->route('accounts.ad-budget.meta-payments')
                    ->with('success', 'Meta Ad Account removed successfully.');
            }
        }

        $records = $this->readLocalFallbackAccounts($companyId);
        $filtered = array_values(array_filter($records, fn (array $r) => (string) ($r['id'] ?? '') !== $id));
        $this->writeLocalFallbackAccounts($companyId, $filtered);

        return redirect()
            ->route('accounts.ad-budget.meta-payments')
            ->with('success', 'Meta Ad Account removed successfully.');
    }

    private function loadSavedAccounts(?int $companyId): Collection
    {
        $accounts = collect();

        if (Schema::hasTable('meta_ad_settings')) {
            $dbRows = MetaAdSetting::query()
                ->when($companyId, fn ($q) => $q->where(function ($sub) use ($companyId) {
                    $sub->where('company_id', $companyId)->orWhereNull('company_id');
                }))
                ->orderBy('id')
                ->get();

            foreach ($dbRows as $row) {
                $normId = MetaAdInsightsService::normalizeAdAccountId($row->ad_account_id);
                if ($normId === '' || empty($row->access_token)) {
                    continue;
                }
                $accounts->push([
                    'id' => (string) $row->id,
                    'ad_account_id' => $normId,
                    'account_label' => $row->account_label,
                    'graph_version' => $row->graph_version ?: 'v20.0',
                    'access_token' => (string) $row->access_token,
                    'masked_token' => $this->maskToken((string) $row->access_token),
                    'source' => 'db',
                ]);
            }
        }

        if ($accounts->isEmpty()) {
            foreach ($this->readLocalFallbackAccounts($companyId) as $row) {
                try {
                    $token = ! empty($row['access_token']) ? Crypt::decryptString($row['access_token']) : '';
                } catch (\Throwable) {
                    $token = '';
                }

                $normId = MetaAdInsightsService::normalizeAdAccountId($row['ad_account_id'] ?? '');
                if ($normId === '' || $token === '') {
                    continue;
                }

                $accounts->push([
                    'id' => (string) ($row['id'] ?? $normId),
                    'ad_account_id' => $normId,
                    'account_label' => $row['account_label'] ?? null,
                    'graph_version' => $row['graph_version'] ?? 'v20.0',
                    'access_token' => $token,
                    'masked_token' => $this->maskToken($token),
                    'source' => 'local',
                ]);
            }
        }

        return $accounts->unique('ad_account_id')->values();
    }

    private function mergeConnectedAccounts(Collection $savedAccounts, Collection $adAccountMasters, string $fallbackToken): Collection
    {
        $merged = $savedAccounts->keyBy('ad_account_id');

        foreach ($adAccountMasters as $master) {
            if (empty($master->account_id)) {
                continue;
            }
            $normId = MetaAdInsightsService::normalizeAdAccountId($master->account_id);
            if ($normId === '' || $merged->has($normId)) {
                continue;
            }

            $platform = Str::lower((string) ($master->platform ?? ''));
            $isMetaPlatform = $platform === '' || Str::contains($platform, ['meta', 'facebook', 'fb', 'instagram']);
            if (! $isMetaPlatform) {
                continue;
            }

            $merged->put($normId, [
                'id' => null,
                'ad_account_id' => $normId,
                'account_label' => $master->account_name,
                'graph_version' => 'v20.0',
                'access_token' => $fallbackToken,
                'masked_token' => $this->maskToken($fallbackToken),
                'source' => 'master',
            ]);
        }

        if ($merged->isEmpty()) {
            $envAccount = MetaAdInsightsService::normalizeAdAccountId((string) config('services.facebook.ad_account_id', ''));
            if ($envAccount !== '' && $fallbackToken !== '') {
                $merged->put($envAccount, [
                    'id' => null,
                    'ad_account_id' => $envAccount,
                    'account_label' => $envAccount,
                    'graph_version' => 'v20.0',
                    'access_token' => $fallbackToken,
                    'masked_token' => $this->maskToken($fallbackToken),
                    'source' => 'env',
                ]);
            }
        }

        return $merged->values();
    }

    private function resolveFallbackToken(Collection $savedAccounts): string
    {
        $firstSaved = $savedAccounts->first(fn (array $acc) => ! empty($acc['access_token']));
        if ($firstSaved) {
            return (string) $firstSaved['access_token'];
        }

        $envToken = trim((string) config('services.facebook.marketing_access_token', ''));
        if ($envToken !== '') {
            return $envToken;
        }

        if (Schema::hasTable('campaign_masters')) {
            $campaignToken = CampaignMaster::query()
                ->whereNotNull('access_token')
                ->where('access_token', '!=', '')
                ->latest('id')
                ->value('access_token');

            if ($campaignToken) {
                return trim((string) $campaignToken);
            }
        }

        return '';
    }

    private function readLocalFallbackAccounts(?int $companyId): array
    {
        $multiPath = $this->multiFallbackPath($companyId);
        if (Storage::disk('local')->exists($multiPath)) {
            $decoded = json_decode((string) Storage::disk('local')->get($multiPath), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $legacyPath = 'meta_ad_settings_' . ($companyId ?: 'default') . '.json';
        if (! Storage::disk('local')->exists($legacyPath)) {
            $legacyPath = 'meta_ad_settings_default.json';
        }
        if (Storage::disk('local')->exists($legacyPath)) {
            $legacy = json_decode((string) Storage::disk('local')->get($legacyPath), true);
            if (is_array($legacy) && ! empty($legacy['ad_account_id']) && ! empty($legacy['access_token'])) {
                $legacy['id'] = $legacy['id'] ?? 'local_legacy_1';
                return [$legacy];
            }
        }

        return [];
    }

    private function writeLocalFallbackAccounts(?int $companyId, array $records): void
    {
        Storage::disk('local')->put($this->multiFallbackPath($companyId), json_encode(array_values($records)));
    }

    private function multiFallbackPath(?int $companyId): string
    {
        return 'meta_ad_accounts_' . ($companyId ?: 'default') . '.json';
    }

    private function maskToken(string $token): string
    {
        if ($token === '') {
            return '';
        }

        $len = strlen($token);
        if ($len <= 12) {
            return str_repeat('*', $len);
        }

        return substr($token, 0, 6) . '••••••' . substr($token, -5);
    }

    private function exportCsv(array $rows, string $adAccountFilter, string $datePreset, string $currency): StreamedResponse
    {
        $filename = 'meta-payments-' . Str::slug($adAccountFilter) . '-' . $datePreset . '-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date & Time',
                'Ad Account ID',
                'Ad Account Name',
                'Event / Status',
                "Amount Paid ({$currency})",
                'Currency',
                'Payment Method / Card',
                'Transaction / Invoice ID',
                'Actor / Performed By',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['event_time'] ?? '',
                    $row['ad_account_id'] ?? '',
                    $row['ad_account_name'] ?? '',
                    $row['status_label'] ?? '',
                    number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                    $row['currency'] ?? $currency,
                    $row['payment_method'] ?? '',
                    $row['transaction_id'] ?? '',
                    $row['actor_name'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
