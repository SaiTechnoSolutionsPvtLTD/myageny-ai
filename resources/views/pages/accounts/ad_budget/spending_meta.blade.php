@extends('layouts.app')

@section('title', 'Spending Meta — Ad Budget')

@push('styles')
<style>
.adb-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.adb-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.adb-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.adb-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.adb-breadcrumb a { color:#9e9e9e; text-decoration:none; }
.adb-breadcrumb a:hover { color:#fe5f04; }
.adb-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.adb-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .adb-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .adb-body { padding:16px 20px 24px; }
}

.adb-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(215px, 1fr)); gap:14px; }
.adb-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.adb-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.adb-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.1; }
.adb-stat-sub { font-size:11.5px; color:#64748b; margin-top:3px; }

.adb-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }

.meta-filter-accordion { border-bottom:1px solid #e2e8f0; background:#fff; }
.meta-filter-toggle { width:100%; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 20px; background:#f8fafc; border:none; cursor:pointer; font-family:inherit; text-align:left; transition:background .15s ease; }
.meta-filter-toggle:hover { background:#fff7ed; }
.meta-filter-toggle-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.meta-filter-title { font-size:13px; font-weight:800; color:#0f172a; display:inline-flex; align-items:center; gap:7px; }
.meta-filter-pill { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; background:#fff; border:1px solid #e2e8f0; font-size:11.5px; font-weight:700; color:#475569; }
.meta-filter-pill strong { color:#fe5f04; }
.meta-filter-chevron { width:28px; height:28px; border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#475569; display:inline-flex; align-items:center; justify-content:center; transition:transform .2s ease; flex-shrink:0; }
.meta-filter-accordion.is-open .meta-filter-chevron { transform:rotate(180deg); color:#fe5f04; border-color:#fed7aa; background:#fff7ed; }
.meta-filter-panel { display:none; padding:16px 20px; border-top:1px solid #e2e8f0; background:#fff; }
.meta-filter-accordion.is-open .meta-filter-panel { display:block; }
.meta-filter-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:12px; align-items:end; }
.meta-filter-field { display:flex; flex-direction:column; gap:5px; }
.meta-filter-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#64748b; }
.adb-search-wrap { position:relative; width:100%; }
.adb-search-input { width:100%; padding:8px 12px 8px 34px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.adb-search-ico { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
.adb-select, .adb-input { width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }
.adb-select:focus, .adb-input:focus, .adb-search-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

.adb-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.adb-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.adb-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.adb-btn-secondary { background:#f1f5f9; color:#334155; }
.adb-btn-secondary:hover { background:#e2e8f0; }
.adb-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.adb-btn-danger:hover { background:#fecaca; }
.adb-btn-sm { padding:5px 10px; font-size:11.5px; border-radius:8px; }

.adb-tbl { width:100%; border-collapse:collapse; }
.adb-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.adb-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.adb-tbl tbody tr:hover td { background:#fdf9f6; }
.adb-tbl tfoot td { background:#f8fafc; border-top:2px solid #e2e8f0; font-weight:800; color:#0f172a; }

.meta-acc-banner { background:linear-gradient(135deg, #ffffff 0%, #fffaf5 100%); border:1px solid #fed7aa; border-radius:14px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.meta-acc-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px; }
.meta-acc-mini { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:14px 16px; text-decoration:none; color:inherit; display:flex; flex-direction:column; gap:8px; transition:all .16s ease; }
.meta-acc-mini:hover { border-color:#fdba74; box-shadow:0 8px 20px rgba(254,95,4,.08); transform:translateY(-1px); }
.meta-acc-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:11.5px; font-weight:700; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.meta-share-bar { width:100%; max-width:130px; height:6px; background:#f1f5f9; border-radius:999px; overflow:hidden; margin-top:5px; }
.meta-share-fill { height:100%; background:linear-gradient(90deg, #fe5f04, #fb923c); border-radius:999px; }
.meta-action-chip { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:8px; background:#f8fafc; border:1px solid #e2e8f0; font-size:11.5px; font-weight:600; color:#334155; margin:2px; }

.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; padding:16px; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:100%; max-width:680px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:2; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:18px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 14px; border:1px solid #cbd5e1; border-radius:9px; font-size:13px; font-family:inherit; outline:none; width:100%; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.meta-saved-list { display:flex; flex-direction:column; gap:8px; }
.meta-saved-item { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; flex-wrap:wrap; }
</style>
@endpush

@section('content')
<div class="adb-page">

    {{-- Topbar --}}
    <div class="adb-topbar">
        <div>
            <div class="adb-title">
                <i class="bi bi-meta" style="color:#fe5f04;"></i>
                <span>Spending Meta</span>
            </div>
            <div class="adb-breadcrumb">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <span>Ad Budget</span>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Spending Meta</span>
            </div>
        </div>
        <div class="adb-actions">
            @if($isConfigured && ! empty($rows))
                <a href="{{ route('accounts.ad-budget.spending-meta', array_merge(request()->query(), ['export' => 'csv'])) }}" class="adb-btn adb-btn-secondary">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            @endif
            @if($isConfigured)
                <a href="{{ route('accounts.ad-budget.spending-meta', request()->query()) }}" class="adb-btn adb-btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </a>
            @endif
            @can('accounts_ad_budget.edit')
                <button type="button" class="adb-btn adb-btn-primary" onclick="openMetaSettingsModal()">
                    <i class="bi bi-gear-fill"></i>
                    <span>Manage Meta Accounts</span>
                    @if($connectedAccounts->count() > 0)
                        <span style="background:rgba(255,255,255,.25); padding:1px 7px; border-radius:999px; font-size:11px;">{{ $connectedAccounts->count() }}</span>
                    @endif
                </button>
            @endcan
        </div>
    </div>

    <div class="adb-body">

        @if(session('success'))
            <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
                ✓ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
                ⚠️ {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        @if(! $isConfigured)
            <div class="adb-card">
                <div style="text-align:center; padding:52px 24px; color:#64748b;">
                    <i class="bi bi-meta" style="font-size:40px; color:#fe5f04; display:block; margin-bottom:10px;"></i>
                    <div style="font-size:16px; font-weight:800; color:#0f172a; margin-bottom:6px;">
                        Connect Your Meta Ad Accounts
                    </div>
                    <div style="max-width:560px; margin:0 auto 18px; font-size:13px; line-height:1.6;">
                        Add one or multiple Meta Ad Accounts (e.g. <code>act_1150358500674602</code>) with their Marketing Access Token to view real-time campaign spend, impressions, clicks, reach, and actions.
                    </div>
                    @can('accounts_ad_budget.edit')
                        <button type="button" class="adb-btn adb-btn-primary" onclick="openMetaSettingsModal()">
                            <i class="bi bi-plus-lg"></i> Add Meta Ad Account
                        </button>
                    @endcan
                </div>
            </div>
        @else
            {{-- Account Summary Banner (Single Account OR Multi-Account Cards) --}}
            @if($isMultiView && count($accountSummaries) > 1)
                <div class="meta-acc-grid">
                    @foreach($accountSummaries as $accSum)
                        @php $sym = $accSum['currency_symbol'] ?? $currencySymbol; @endphp
                        <a href="{{ route('accounts.ad-budget.spending-meta', array_merge(request()->except('page'), ['ad_account_id' => $accSum['id']])) }}"
                           class="meta-acc-mini"
                           title="Click to view only {{ $accSum['name'] }}">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                                <div>
                                    <div style="font-size:14px; font-weight:800; color:#0f172a;">{{ $accSum['label'] ?? $accSum['name'] }}</div>
                                    <div style="font-size:11px; color:#64748b; font-family:monospace;">{{ $accSum['id'] }}</div>
                                </div>
                                <span class="meta-acc-pill">{{ $accSum['currency'] }}</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:baseline; gap:10px; padding-top:6px; border-top:1px dashed #e2e8f0;">
                                <div>
                                    <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase;">{{ $datePresets[$datePreset] ?? 'Period' }} Spend</div>
                                    <div style="font-size:17px; font-weight:800; color:#fe5f04;">{{ $sym }}{{ number_format((float) ($accSum['period_spend'] ?? 0), 2) }}</div>
                                </div>
                                @if($accSum['amount_spent'] !== null)
                                    <div style="text-align:right;">
                                        <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase;">Lifetime</div>
                                        <div style="font-size:13px; font-weight:700; color:#0f172a;">{{ $sym }}{{ number_format($accSum['amount_spent'], 2) }}</div>
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @elseif($accountSummary)
                <div class="meta-acc-banner">
                    <div>
                        <div style="font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span>{{ $accountSummary['name'] }}</span>
                            <span class="meta-acc-pill">{{ $accountSummary['id'] }}</span>
                            <span class="meta-acc-pill" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">{{ $accountSummary['currency'] }}</span>
                        </div>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">
                            Live insights from Meta Marketing Graph API
                        </div>
                    </div>
                    <div style="display:flex; gap:18px; flex-wrap:wrap;">
                        @if($accountSummary['amount_spent'] !== null)
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Lifetime Spent</div>
                                <div style="font-size:16px; font-weight:800; color:#0f172a;">{{ $currencySymbol }}{{ number_format($accountSummary['amount_spent'], 2) }}</div>
                            </div>
                        @endif
                        @if($accountSummary['balance'] !== null)
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Current Bill Balance</div>
                                <div style="font-size:16px; font-weight:800; color:#ea580c;">{{ $currencySymbol }}{{ number_format($accountSummary['balance'], 2) }}</div>
                            </div>
                        @endif
                        @if($accountSummary['spend_cap'] !== null)
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Spend Cap</div>
                                <div style="font-size:16px; font-weight:800; color:#15803d;">{{ $currencySymbol }}{{ number_format($accountSummary['spend_cap'], 2) }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($accountError)
                <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:12px 16px; border-radius:10px; font-size:13px;">
                    <strong>Account Summary Notice:</strong> {{ $accountError }}
                </div>
            @endif

            @if(! empty($insightsWarnings))
                <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:12px 16px; border-radius:10px; font-size:13px;">
                    <strong>Some Accounts Returned Errors:</strong> {{ implode(' | ', $insightsWarnings) }}
                </div>
            @endif

            {{-- Stats Cards --}}
            <div class="adb-stats-grid">
                <div class="adb-stat-card">
                    <div class="adb-stat-icon" style="background:#fff7ed; color:#fe5f04;">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                    <div>
                        <div class="adb-stat-label">Total Ad Spend</div>
                        <div class="adb-stat-val" style="color:#fe5f04;">{{ $currencySymbol }}{{ number_format($totals['total_spend'], 2) }}</div>
                        <div class="adb-stat-sub">{{ $datePresets[$datePreset] ?? 'Selected Period' }}</div>
                    </div>
                </div>
                <div class="adb-stat-card">
                    <div class="adb-stat-icon" style="background:#eff6ff; color:#2563eb;">
                        <i class="bi bi-bullseye"></i>
                    </div>
                    <div>
                        <div class="adb-stat-label">{{ $level === 'account' ? 'Ad Accounts' : 'Campaigns' }}</div>
                        <div class="adb-stat-val">{{ $totals['campaigns_count'] }}</div>
                        <div class="adb-stat-sub">With insights in period</div>
                    </div>
                </div>
                <div class="adb-stat-card">
                    <div class="adb-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                        <i class="bi bi-eye-fill"></i>
                    </div>
                    <div>
                        <div class="adb-stat-label">Impressions / Reach</div>
                        <div class="adb-stat-val">{{ number_format($totals['total_impressions']) }}</div>
                        <div class="adb-stat-sub">Reach: {{ number_format($totals['total_reach']) }}</div>
                    </div>
                </div>
                <div class="adb-stat-card">
                    <div class="adb-stat-icon" style="background:#faf5ff; color:#7c3aed;">
                        <i class="bi bi-cursor-fill"></i>
                    </div>
                    <div>
                        <div class="adb-stat-label">Total Clicks</div>
                        <div class="adb-stat-val">{{ number_format($totals['total_clicks']) }}</div>
                        <div class="adb-stat-sub">CTR: {{ number_format($totals['avg_ctr'], 2) }}% • CPC: {{ $currencySymbol }}{{ number_format($totals['avg_cpc'], 2) }}</div>
                    </div>
                </div>
            </div>

            {{-- Main Table Card --}}
            <div class="adb-card">
                {{-- Collapsible Filter Accordion --}}
                @php
                    $isFilterOpen = $datePreset === 'custom' || $search !== '';
                    $activeAccountLabel = $selectedAccountFilter === 'all'
                        ? 'All Accounts (' . $connectedAccounts->count() . ')'
                        : ($adAccountId ?: 'Select Account');
                @endphp
                <div class="meta-filter-accordion {{ $isFilterOpen ? 'is-open' : '' }}" id="metaFilterAccordion">
                    <button type="button"
                            class="meta-filter-toggle"
                            onclick="toggleMetaFilterAccordion()"
                            aria-expanded="{{ $isFilterOpen ? 'true' : 'false' }}"
                            aria-controls="metaFilterPanel">
                        <div class="meta-filter-toggle-left">
                            <span class="meta-filter-title">
                                <i class="bi bi-funnel-fill" style="color:#fe5f04;"></i>
                                <span>Filters</span>
                            </span>
                            <span class="meta-filter-pill">Account: <strong>{{ $activeAccountLabel }}</strong></span>
                            <span class="meta-filter-pill">Period: <strong>{{ $datePresets[$datePreset] ?? $datePreset }}</strong></span>
                            <span class="meta-filter-pill">Level: <strong>{{ $level === 'account' ? 'Account' : 'Campaign' }}</strong></span>
                            @if($search !== '')
                                <span class="meta-filter-pill">Search: <strong>{{ $search }}</strong></span>
                            @endif
                        </div>
                        <span class="meta-filter-chevron">
                            <i class="bi bi-chevron-down"></i>
                        </span>
                    </button>

                    <div class="meta-filter-panel" id="metaFilterPanel">
                        <form method="GET" action="{{ route('accounts.ad-budget.spending-meta') }}" id="metaFilterForm">
                            <div class="meta-filter-grid">
                                <div class="meta-filter-field">
                                    <label class="meta-filter-label">Search</label>
                                    <div class="adb-search-wrap">
                                        <i class="bi bi-search adb-search-ico"></i>
                                        <input type="text" name="search" class="adb-search-input" placeholder="Campaign, account, or ID…" value="{{ $search }}">
                                    </div>
                                </div>

                                <div class="meta-filter-field">
                                    <label class="meta-filter-label">Ad Account</label>
                                    <select name="ad_account_id" class="adb-select" onchange="this.form.submit()">
                                        @if($connectedAccounts->count() > 1)
                                            <option value="all" @selected($selectedAccountFilter === 'all')>
                                                All Ad Accounts ({{ $connectedAccounts->count() }} Combined)
                                            </option>
                                        @endif
                                        @foreach($connectedAccounts as $acc)
                                            <option value="{{ $acc['ad_account_id'] }}" @selected($selectedAccountFilter === $acc['ad_account_id'])>
                                                {{ $acc['account_label'] ? $acc['account_label'] . ' (' . $acc['ad_account_id'] . ')' : $acc['ad_account_id'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="meta-filter-field">
                                    <label class="meta-filter-label">Date Range</label>
                                    <select name="date_preset" id="metaDatePresetSelect" class="adb-select" onchange="toggleMetaCustomDates()">
                                        @foreach($datePresets as $presetKey => $presetLabel)
                                            <option value="{{ $presetKey }}" @selected($datePreset === $presetKey)>{{ $presetLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="meta-filter-field" id="metaCustomDateInputs" style="display:{{ $datePreset === 'custom' ? 'flex' : 'none' }};">
                                    <label class="meta-filter-label">Custom Dates</label>
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <input type="date" name="date_from" class="adb-input" value="{{ $dateFrom }}">
                                        <span style="color:#64748b; font-size:12px;">to</span>
                                        <input type="date" name="date_to" class="adb-input" value="{{ $dateTo }}">
                                    </div>
                                </div>

                                <div class="meta-filter-field">
                                    <label class="meta-filter-label">Breakdown Level</label>
                                    <select name="level" class="adb-select" onchange="this.form.submit()">
                                        <option value="campaign" @selected($level === 'campaign')>Campaign Level</option>
                                        <option value="account" @selected($level === 'account')>Account Level</option>
                                    </select>
                                </div>

                                <div class="meta-filter-field" style="flex-direction:row; align-items:center; gap:8px;">
                                    <button type="submit" class="adb-btn adb-btn-primary" style="padding:8px 16px;">Apply</button>
                                    @if(request()->hasAny(['search', 'date_preset', 'date_from', 'date_to', 'level', 'ad_account_id']))
                                        <a href="{{ route('accounts.ad-budget.spending-meta') }}" class="adb-btn adb-btn-secondary" style="padding:8px 14px;">Reset</a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if($insightsError)
                    <div style="margin:20px; background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:16px 20px; border-radius:12px; font-size:13px;">
                        <div style="font-weight:800; margin-bottom:4px;">Unable to fetch Meta Insights</div>
                        <div>{{ $insightsError }}</div>
                    </div>
                @elseif(empty($rows))
                    <div style="text-align:center; padding:44px 20px; color:#94a3b8;">
                        <i class="bi bi-bar-chart" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                        No Meta spending data found for the selected period ({{ $datePresets[$datePreset] ?? $datePreset }}).
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="adb-tbl">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    @if($isMultiView)
                                        <th>Ad Account</th>
                                    @endif
                                    <th>{{ $level === 'account' ? 'Ad Account' : 'Campaign' }}</th>
                                    <th>Period</th>
                                    <th>Spend</th>
                                    <th>Impressions</th>
                                    <th>Reach</th>
                                    <th>Clicks</th>
                                    <th>CTR / Avg CPC</th>
                                    <th>Actions &amp; Cost / Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $index => $row)
                                    @php
                                        $rowSym = $row['currency_symbol'] ?? $currencySymbol;
                                        $sharePct = $totals['total_spend'] > 0
                                            ? min(100, round(($row['spend'] / $totals['total_spend']) * 100, 1))
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td style="color:#94a3b8; font-weight:700;">{{ $index + 1 }}</td>
                                        @if($isMultiView)
                                            <td>
                                                <div style="font-weight:700; color:#334155;">{{ $row['ad_account_name'] ?? $row['ad_account_id'] }}</div>
                                                <div style="font-size:11px; color:#94a3b8; font-family:monospace;">{{ $row['ad_account_id'] ?? '' }}</div>
                                            </td>
                                        @endif
                                        <td>
                                            <div style="font-weight:700; color:#0f172a;">{{ $row['name'] }}</div>
                                            <div style="font-size:11px; color:#64748b; font-family:monospace;">ID: {{ $row['id'] }}</div>
                                        </td>
                                        <td style="white-space:nowrap; font-size:12px; color:#475569;">
                                            @if($row['date_start'] && $row['date_stop'])
                                                {{ \Carbon\Carbon::parse($row['date_start'])->format('d M') }} – {{ \Carbon\Carbon::parse($row['date_stop'])->format('d M Y') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <div style="font-weight:800; font-size:14px; color:#fe5f04;">
                                                {{ $rowSym }}{{ number_format($row['spend'], 2) }}
                                            </div>
                                            <div class="meta-share-bar" title="{{ $sharePct }}% of total spend">
                                                <div class="meta-share-fill" style="width:{{ $sharePct }}%;"></div>
                                            </div>
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">{{ $sharePct }}% share</div>
                                        </td>
                                        <td>{{ number_format($row['impressions']) }}</td>
                                        <td>{{ number_format($row['reach']) }}</td>
                                        <td>{{ number_format($row['clicks']) }}</td>
                                        <td style="white-space:nowrap;">
                                            <div style="font-weight:700; color:#0f172a;">{{ number_format($row['ctr'], 2) }}% CTR</div>
                                            <div style="font-size:11.5px; color:#64748b;">CPC: {{ $rowSym }}{{ number_format($row['cpc'], 2) }}</div>
                                        </td>
                                        <td>
                                            @forelse($row['actions'] as $act)
                                                <span class="meta-action-chip">
                                                    <span>{{ $act['label'] }}: <strong>{{ number_format($act['value']) }}</strong></span>
                                                    @if($act['cost_per_action'] !== null)
                                                        <span style="color:#ea580c;">({{ $rowSym }}{{ number_format($act['cost_per_action'], 2) }})</span>
                                                    @endif
                                                </span>
                                            @empty
                                                <span style="color:#94a3b8;">—</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="{{ $isMultiView ? 4 : 3 }}">Grand Total ({{ $totals['campaigns_count'] }} {{ $level === 'account' ? 'Account(s)' : 'Campaign(s)' }})</td>
                                    <td style="color:#fe5f04; font-size:15px;">{{ $currencySymbol }}{{ number_format($totals['total_spend'], 2) }}</td>
                                    <td>{{ number_format($totals['total_impressions']) }}</td>
                                    <td>{{ number_format($totals['total_reach']) }}</td>
                                    <td>{{ number_format($totals['total_clicks']) }}</td>
                                    <td>{{ number_format($totals['avg_ctr'], 2) }}% / {{ $currencySymbol }}{{ number_format($totals['avg_cpc'], 2) }}</td>
                                    <td>—</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- Meta Accounts Manager Modal --}}
@can('accounts_ad_budget.edit')
<div class="modal-overlay" id="metaSettingsModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Manage Meta Ad Accounts</div>
            <button type="button" class="modal-close" onclick="closeMetaSettingsModal()">×</button>
        </div>

        <div class="modal-body">
            @if($savedAccounts->isNotEmpty())
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <div style="font-size:13px; font-weight:800; color:#0f172a;">
                            Connected Accounts ({{ $savedAccounts->count() }})
                        </div>
                        <button type="button" class="adb-btn adb-btn-secondary adb-btn-sm" onclick="resetMetaAccountForm()">
                            + Add New Account
                        </button>
                    </div>
                    <div class="meta-saved-list">
                        @foreach($savedAccounts as $saved)
                            <div class="meta-saved-item">
                                <div>
                                    <div style="font-size:13.5px; font-weight:800; color:#0f172a;">
                                        {{ $saved['account_label'] ?: $saved['ad_account_id'] }}
                                    </div>
                                    <div style="font-size:11.5px; color:#64748b; font-family:monospace;">
                                        {{ $saved['ad_account_id'] }} • {{ $saved['graph_version'] }} • Token: {{ $saved['masked_token'] }}
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <a href="{{ route('accounts.ad-budget.spending-meta', ['ad_account_id' => $saved['ad_account_id']]) }}"
                                       class="adb-btn adb-btn-secondary adb-btn-sm" title="View Spend">
                                        View
                                    </a>
                                    <button type="button"
                                            class="adb-btn adb-btn-secondary adb-btn-sm"
                                            data-account="{{ json_encode([
                                                'id' => $saved['id'],
                                                'ad_account_id' => $saved['ad_account_id'],
                                                'account_label' => $saved['account_label'],
                                                'graph_version' => $saved['graph_version'],
                                                'masked_token' => $saved['masked_token'],
                                            ]) }}"
                                            onclick="editMetaAccount(this)">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('accounts.ad-budget.spending-meta.delete', $saved['id']) }}" onsubmit="return confirm('Remove Meta Ad Account {{ $saved['ad_account_id'] }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="adb-btn adb-btn-danger adb-btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('accounts.ad-budget.spending-meta.settings') }}" id="metaAccountForm" style="display:flex; flex-direction:column; gap:14px; border-top:{{ $savedAccounts->isNotEmpty() ? '1px dashed #cbd5e1' : 'none' }}; padding-top:{{ $savedAccounts->isNotEmpty() ? '16px' : '0' }};">
                @csrf
                <input type="hidden" name="setting_id" id="meta_setting_id" value="">

                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-size:14px; font-weight:800; color:#0f172a;" id="metaFormHeading">
                        Add Meta Ad Account
                    </div>
                    <button type="button" id="metaCancelEditBtn" class="adb-btn adb-btn-secondary adb-btn-sm" style="display:none;" onclick="resetMetaAccountForm()">
                        Cancel Edit
                    </button>
                </div>

                <div class="form-group">
                    <label class="form-label">Meta Ad Account ID <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="ad_account_id" id="meta_ad_account_id" class="form-input"
                           value="{{ old('ad_account_id') }}"
                           placeholder="e.g. act_1150358500674602" required>
                    <span style="font-size:11px; color:#64748b;">Paste either <code>act_1150358500674602</code> or numeric ID.</span>
                </div>

                <div style="display:grid; grid-template-columns:1fr 140px; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Account Label (Optional)</label>
                        <input type="text" name="account_label" id="meta_account_label" class="form-input"
                               value="{{ old('account_label') }}"
                               placeholder="e.g. Client A Meta Ads">
                    </div>

                    <div class="form-group">
                        <label class="form-label">API Version</label>
                        <input type="text" name="graph_version" id="meta_graph_version" class="form-input"
                               value="{{ old('graph_version', 'v20.0') }}"
                               placeholder="v20.0">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Meta Marketing Access Token
                        <span id="metaTokenRequiredStar" style="color:#dc2626; display:{{ $hasAnyToken ? 'none' : 'inline' }};">*</span>
                    </label>
                    <textarea name="access_token" id="meta_access_token" class="form-input" rows="3"
                              placeholder="{{ $hasAnyToken ? 'Leave blank to reuse saved token (' . $primaryMaskedToken . ') or paste a different token' : 'Paste your Meta Graph API Access Token here' }}"
                              @if(! $hasAnyToken) required @endif></textarea>
                    <span id="metaTokenHint" style="font-size:11px; color:{{ $hasAnyToken ? '#15803d' : '#64748b' }}; font-weight:600;">
                        @if($hasAnyToken)
                            ✓ Leave blank to reuse existing saved token ({{ $primaryMaskedToken }}), or paste a new token for a separate account.
                        @else
                            Token is encrypted before saving.
                        @endif
                    </span>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:4px;">
                    <button type="button" class="adb-btn adb-btn-secondary" onclick="closeMetaSettingsModal()">Close</button>
                    <button type="submit" class="adb-btn adb-btn-primary" id="metaFormSubmitBtn">Save &amp; Fetch Spend</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script>
const metaHasPrimaryToken = @json($hasAnyToken);
const metaPrimaryMasked = @json($primaryMaskedToken);

function openMetaSettingsModal() {
    document.getElementById('metaSettingsModal')?.classList.add('active');
}
function closeMetaSettingsModal() {
    document.getElementById('metaSettingsModal')?.classList.remove('active');
}
function editMetaAccount(btn) {
    let acc = btn;
    if (btn instanceof HTMLElement) {
        try {
            acc = JSON.parse(btn.getAttribute('data-account') || '{}');
        } catch (e) {
            acc = {};
        }
    }
    document.getElementById('meta_setting_id').value = acc.id || '';
    document.getElementById('meta_ad_account_id').value = acc.ad_account_id || '';
    document.getElementById('meta_account_label').value = acc.account_label || '';
    document.getElementById('meta_graph_version').value = acc.graph_version || 'v20.0';
    document.getElementById('meta_access_token').value = '';
    document.getElementById('meta_access_token').required = false;
    document.getElementById('metaTokenRequiredStar').style.display = 'none';
    document.getElementById('metaFormHeading').innerText = 'Edit Meta Ad Account';
    document.getElementById('metaFormSubmitBtn').innerText = 'Update Account';
    document.getElementById('metaCancelEditBtn').style.display = 'inline-flex';
    document.getElementById('metaTokenHint').innerText = 'Leave blank to keep existing token (' + (acc.masked_token || metaPrimaryMasked) + ').';
}
function resetMetaAccountForm() {
    document.getElementById('meta_setting_id').value = '';
    document.getElementById('meta_ad_account_id').value = '';
    document.getElementById('meta_account_label').value = '';
    document.getElementById('meta_graph_version').value = 'v20.0';
    document.getElementById('meta_access_token').value = '';
    document.getElementById('meta_access_token').required = !metaHasPrimaryToken;
    document.getElementById('metaTokenRequiredStar').style.display = metaHasPrimaryToken ? 'none' : 'inline';
    document.getElementById('metaFormHeading').innerText = 'Add Meta Ad Account';
    document.getElementById('metaFormSubmitBtn').innerText = 'Save & Fetch Spend';
    document.getElementById('metaCancelEditBtn').style.display = 'none';
    document.getElementById('metaTokenHint').innerText = metaHasPrimaryToken
        ? '✓ Leave blank to reuse existing saved token (' + metaPrimaryMasked + '), or paste a new token for a separate account.'
        : 'Token is encrypted before saving.';
}
function toggleMetaFilterAccordion() {
    const accordion = document.getElementById('metaFilterAccordion');
    if (!accordion) return;
    const isOpen = accordion.classList.toggle('is-open');
    const toggleBtn = accordion.querySelector('.meta-filter-toggle');
    if (toggleBtn) {
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
}
function toggleMetaCustomDates() {
    const sel = document.getElementById('metaDatePresetSelect');
    const box = document.getElementById('metaCustomDateInputs');
    if (!sel || !box) return;
    const isCustom = sel.value === 'custom';
    box.style.display = isCustom ? 'flex' : 'none';
    if (!isCustom) {
        document.getElementById('metaFilterForm')?.submit();
    }
}
</script>
@endpush
@endsection
