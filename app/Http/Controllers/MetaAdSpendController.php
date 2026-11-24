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

class MetaAdSpendController extends Controller
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
        $level = $request->query('level') === 'account' ? 'account' : 'campaign';
        $search = trim((string) $request->query('search', ''));

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
        $insightsErrors = [];

        if ($isConfigured) {
            foreach ($targetAccounts as $account) {
                $actId = $account['ad_account_id'];
                $token = (string) ($account['access_token'] ?? '');
                $version = (string) ($account['graph_version'] ?? 'v20.0');
                $label = $account['account_label'] ?: $actId;

                if ($token === '') {
                    $insightsErrors[] = "{$label} ({$actId}): Missing access token.";
                    continue;
                }

                $summaryResult = $this->insightsService->fetchAccountSummary($actId, $token, $version);
                $summaryData = null;
                if ($summaryResult['success'] && is_array($summaryResult['data'])) {
                    $summaryData = $summaryResult['data'];
                    $summaryData['label'] = $account['account_label'] ?: ($summaryData['name'] ?? $actId);
                    $accountSummaries[] = $summaryData;
                } elseif (! empty($summaryResult['error'])) {
                    $accountErrors[] = "{$label}: {$summaryResult['error']}";
                }

                $insightsResult = $this->insightsService->fetchInsights($actId, $token, [
                    'graph_version' => $version,
                    'level' => $level,
                    'date_preset' => $datePreset,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]);

                if ($insightsResult['success']) {
                    $accountDisplayName = $summaryData['name'] ?? $label;
                    $accCurrencySymbol = $summaryData['currency_symbol'] ?? '₹';
                    $accPeriodSpend = 0.0;

                    foreach ($insightsResult['rows'] as $row) {
                        $row['ad_account_id'] = $actId;
                        $row['ad_account_name'] = $accountDisplayName;
                        $row['currency_symbol'] = $accCurrencySymbol;
                        $accPeriodSpend += (float) ($row['spend'] ?? 0);
                        $rows[] = $row;
                    }

                    if ($summaryData !== null && count($accountSummaries) > 0) {
                        $lastIdx = count($accountSummaries) - 1;
                        $accountSummaries[$lastIdx]['period_spend'] = $accPeriodSpend;
                    }
                } else {
                    $insightsErrors[] = "{$label} ({$actId}): {$insightsResult['error']}";
                }
            }

            if ($search !== '') {
                $needle = Str::lower($search);
                $rows = array_values(array_filter($rows, function (array $row) use ($needle) {
                    return Str::contains(Str::lower((string) ($row['name'] ?? '')), $needle)
                        || Str::contains(Str::lower((string) ($row['id'] ?? '')), $needle)
                        || Str::contains(Str::lower((string) ($row['ad_account_name'] ?? '')), $needle)
                        || Str::contains(Str::lower((string) ($row['ad_account_id'] ?? '')), $needle);
                }));
            }

            usort($rows, fn (array $a, array $b) => ($b['spend'] ?? 0) <=> ($a['spend'] ?? 0));
        }

        $totals = $this->insightsService->computeTotals($rows);
        $singleSummary = count($accountSummaries) === 1 ? $accountSummaries[0] : null;
        $currencies = collect($accountSummaries)->pluck('currency')->filter()->unique()->values();
        $currency = $currencies->count() === 1 ? (string) $currencies->first() : 'INR';
        $currencySymbol = MetaAdInsightsService::currencySymbol($currency);

        if ($request->query('export') === 'csv' && $isConfigured && ! empty($rows)) {
            return $this->exportCsv($rows, $selectedAccountFilter ?: 'meta', $datePreset, $currency);
        }

        return view('pages.accounts.ad_budget.spending_meta', [
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
            'level' => $level,
            'search' => $search,
            'isConfigured' => $isConfigured,
            'accountSummary' => $singleSummary,
            'accountSummaries' => $accountSummaries,
            'accountError' => ! empty($accountErrors) ? implode(' | ', $accountErrors) : null,
            'rows' => $rows,
            'totals' => $totals,
            'insightsError' => ! empty($insightsErrors) && empty($rows) ? implode(' | ', $insightsErrors) : null,
            'insightsWarnings' => ! empty($insightsErrors) && ! empty($rows) ? $insightsErrors : [],
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
                ->route('accounts.ad-budget.spending-meta')
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
            $updated = false;

            foreach ($records as &$record) {
                $matchesId = $existing && (string) ($record['id'] ?? '') === $settingId;
                $matchesAccount = MetaAdInsightsService::normalizeAdAccountId($record['ad_account_id'] ?? '') === $normalizedAccountId;
                if ($matchesId || $matchesAccount) {
                    $record['ad_account_id'] = $normalizedAccountId;
                    $record['account_label'] = $accountLabel;
                    $record['access_token'] = Crypt::encryptString($token);
                    $record['graph_version'] = $graphVersion;
                    $record['is_active'] = true;
                    $record['updated_by'] = auth()->id();
                    $updated = true;
                    break;
                }
            }
            unset($record);

            if (! $updated) {
                $records[] = [
                    'id' => 'local_' . Str::random(10),
                    'company_id' => $companyId,
                    'ad_account_id' => $normalizedAccountId,
                    'account_label' => $accountLabel,
                    'access_token' => Crypt::encryptString($token),
                    'graph_version' => $graphVersion,
                    'is_active' => true,
                    'updated_by' => auth()->id(),
                ];
            }

            $this->writeLocalFallbackAccounts($companyId, $records);
        }

        $actionWord = $existing ? 'updated' : 'added';

        return redirect()
            ->route('accounts.ad-budget.spending-meta', ['ad_account_id' => $normalizedAccountId])
            ->with('success', "Meta Ad Account ({$normalizedAccountId}) {$actionWord} successfully.");
    }

    public function deleteAccount(string $id): RedirectResponse
    {
        $companyId = auth()->user()?->company_id;

        if (Schema::hasTable('meta_ad_settings') && is_numeric($id)) {
            $deleted = MetaAdSetting::query()
                ->where('id', (int) $id)
                ->when($companyId, fn ($q) => $q->where(function ($sub) use ($companyId) {
                    $sub->where('company_id', $companyId)->orWhereNull('company_id');
                }))
                ->delete();

            if ($deleted > 0) {
                return redirect()
                    ->route('accounts.ad-budget.spending-meta')
                    ->with('success', 'Meta Ad Account removed successfully.');
            }
        }

        $records = $this->readLocalFallbackAccounts($companyId);
        $filtered = array_values(array_filter($records, fn (array $r) => (string) ($r['id'] ?? '') !== $id));
        $this->writeLocalFallbackAccounts($companyId, $filtered);

        return redirect()
            ->route('accounts.ad-budget.spending-meta')
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

            // Only include AdAccountMaster accounts automatically when they look like Meta/Facebook or a fallback token exists
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

        // Backward compatibility: migrate old single-account fallback file if present
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
        $filename = 'meta-ad-spend-' . Str::slug($adAccountFilter) . '-' . $datePreset . '-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $currency) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Ad Account ID',
                'Ad Account Name',
                'Campaign / Account ID',
                'Campaign / Account Name',
                'Date Start',
                'Date Stop',
                "Spend ({$currency})",
                'Impressions',
                'Reach',
                'Clicks',
                'CTR (%)',
                "Avg CPC ({$currency})",
                'Top Actions',
            ]);

            foreach ($rows as $row) {
                $actionsText = collect($row['actions'] ?? [])
                    ->map(fn ($a) => "{$a['label']}: {$a['value']}")
                    ->implode(' | ');

                fputcsv($handle, [
                    $row['ad_account_id'] ?? '',
                    $row['ad_account_name'] ?? '',
                    $row['id'] ?? '',
                    $row['name'] ?? '',
                    $row['date_start'] ?? '',
                    $row['date_stop'] ?? '',
                    number_format((float) ($row['spend'] ?? 0), 2, '.', ''),
                    $row['impressions'] ?? 0,
                    $row['reach'] ?? 0,
                    $row['clicks'] ?? 0,
                    number_format((float) ($row['ctr'] ?? 0), 2, '.', ''),
                    number_format((float) ($row['cpc'] ?? 0), 2, '.', ''),
                    $actionsText,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
