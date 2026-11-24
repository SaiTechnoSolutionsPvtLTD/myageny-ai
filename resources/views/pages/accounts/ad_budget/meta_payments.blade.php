@extends('layouts.app')

@section('title', 'Meta Payments — Ad Budget')

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

.meta-acc-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:14px; }
.meta-acc-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:16px 18px; display:flex; flex-direction:column; gap:8px; box-shadow:0 2px 6px rgba(0,0,0,.02); transition:all .15s ease; }
.meta-acc-card:hover { border-color:#fdba74; box-shadow:0 6px 16px rgba(254,95,4,.08); }
.meta-acc-card.is-selected { border-color:#fe5f04; background:linear-gradient(180deg, #fffaf5 0%, #ffffff 100%); }
.meta-acc-pill { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; background:#fff; border:1px solid #fed7aa; font-size:11px; font-weight:700; color:#c2410c; }

.meta-type-tag { display:inline-flex; align-items:center; gap:5px; padding:3px 8px; border-radius:7px; font-size:11.5px; font-weight:700; }
.tag-upi { background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; }
.tag-netbanking { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.tag-card { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.tag-balance { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }

.meta-filter-accordion { border-bottom:1px solid #e2e8f0; background:#fff; }
.meta-filter-toggle { width:100%; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 20px; background:#f8fafc; border:none; cursor:pointer; font-family:inherit; text-align:left; transition:background .15s ease; }
.meta-filter-toggle:hover { background:#fff7ed; }
.meta-filter-toggle-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.meta-filter-title { font-size:13px; font-weight:800; color:#0f172a; display:inline-flex; align-items:center; gap:7px; }
.meta-filter-pill { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; background:#fff; border:1px solid #e2e8f0; font-size:11.5px; font-weight:700; color:#475569; }
.meta-filter-pill strong { color:#fe5f04; }
.meta-filter-panel { display:block; padding:16px 20px; border-top:1px solid #e2e8f0; background:#fff; }
.meta-filter-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; align-items:end; }
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
.adb-btn-meta { background:#1877f2; color:#fff; }
.adb-btn-meta:hover { background:#166fe5; color:#fff; }
.adb-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.adb-btn-danger:hover { background:#fecaca; }
.adb-btn-sm { padding:5px 12px; font-size:12px; border-radius:8px; }

.adb-tbl { width:100%; border-collapse:collapse; }
.adb-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.adb-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.adb-tbl tbody tr:hover td { background:#fdf9f6; }
.adb-tbl tfoot td { background:#f8fafc; border-top:2px solid #e2e8f0; font-weight:800; color:#0f172a; }

.badge-status { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11.5px; font-weight:700; }
.badge-status-success { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.badge-status-failed { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.badge-status-refund { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.badge-status-default { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

.meta-tabs { display:flex; align-items:center; gap:8px; border-bottom:1px solid #e2e8f0; padding:10px 20px; background:#fff; }
.meta-tab-btn { padding:7px 14px; border-radius:8px; font-size:12.5px; font-weight:700; border:1px solid transparent; text-decoration:none; color:#64748b; display:inline-flex; align-items:center; gap:6px; transition:all .15s ease; }
.meta-tab-btn:hover { background:#f8fafc; color:#0f172a; }
.meta-tab-btn.is-active { background:#fff7ed; color:#fe5f04; border-color:#fed7aa; }

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
                <i class="bi bi-credit-card-2-front-fill" style="color:#fe5f04;"></i>
                <span>Meta Payments</span>
            </div>
            <div class="adb-breadcrumb">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <span>Ad Budget</span>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Meta Payments</span>
            </div>
        </div>
        <div class="adb-actions">
            @php
                $currentActId = $adAccountId ?: ($connectedAccounts->first()['ad_account_id'] ?? '');
                $cleanActNum = preg_replace('/^act_/', '', $currentActId);
                $metaBillingUrl = $cleanActNum
                    ? "https://adsmanager.facebook.com/adsmanager/manage/billing?act={$cleanActNum}"
                    : "https://adsmanager.facebook.com/adsmanager/manage/billing";
            @endphp
            <a href="{{ $metaBillingUrl }}" target="_blank" rel="noopener noreferrer" class="adb-btn adb-btn-meta" title="Download Official GST Tax Invoices from Meta Ads Manager">
                <i class="bi bi-box-arrow-up-right"></i> Official Tax Invoices
            </a>
            @if($isConfigured && ! empty($rows))
                <a href="{{ route('accounts.ad-budget.meta-payments', array_merge(request()->query(), ['export' => 'csv'])) }}" class="adb-btn adb-btn-secondary">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            @endif
            @if($isConfigured)
                <a href="{{ route('accounts.ad-budget.meta-payments', request()->query()) }}" class="adb-btn adb-btn-secondary">
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
        @if(isset($errors) && $errors->any())
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        @if(! empty($paymentErrors))
            <div style="background:#fef3c7; border:1px solid #fde68a; color:#b45309; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
                <div style="font-weight:800; margin-bottom:4px;">⚠️ Meta Graph API Notice:</div>
                <ul style="margin:0; padding-left:20px; font-size:12.5px;">
                    @foreach($paymentErrors as $pErr)
                        <li>{{ $pErr }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(! $isConfigured)
            <div class="adb-card">
                <div style="text-align:center; padding:52px 24px; color:#64748b;">
                    <i class="bi bi-credit-card-2-front" style="font-size:40px; color:#fe5f04; display:block; margin-bottom:10px;"></i>
                    <div style="font-size:16px; font-weight:800; color:#0f172a; margin-bottom:6px;">
                        Connect Your Meta Ad Accounts
                    </div>
                    <div style="max-width:560px; margin:0 auto 18px; font-size:13px; line-height:1.6;">
                        Connect your Meta Ad Accounts with a valid Marketing Access Token to track payment history, payment types (Net Banking, UPI, Cards), and top-ups made to Meta.
                    </div>
                    @can('accounts_ad_budget.edit')
                        <button type="button" class="adb-btn adb-btn-primary" onclick="openMetaSettingsModal()">
                            <i class="bi bi-plus-lg"></i> Add Meta Ad Account
                        </button>
                    @endcan
                </div>
            </div>
        @else

            {{-- Summary Stats Grid (Pure Payment Metrics) --}}
            <div class="adb-stats-grid">
                {{-- Total Paid to Meta --}}
                <div class="adb-stat-card">
                    <div class="adb-stat-icon" style="background:#ecfdf5; color:#059669;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="adb-stat-label">Total Paid to Meta</div>
                        <div class="adb-stat-val" style="color:#059669;">
                            {{ $currencySymbol }}{{ number_format($totals['total_paid'] ?? 0, 2) }}
                        </div>
                        <div class="adb-stat-sub">{{ $datePresets[$datePreset] ?? 'Selected Period' }}</div>
                    </div>
                </div>

                {{-- Payments Count --}}
                <div class="adb-stat-card">
                    <div class="adb-stat-icon" style="background:#eff6ff; color:#2563eb;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="adb-stat-label">Payments Count</div>
                        <div class="adb-stat-val">
                            {{ number_format(($totals['funds_added_count'] ?? 0) > 0 ? $totals['funds_added_count'] : ($totals['charges_count'] ?? 0)) }}
                        </div>
                        <div class="adb-stat-sub">Payment transactions</div>
                    </div>
                </div>

                {{-- Available Balance --}}
                <div class="adb-stat-card">
                    <div class="adb-stat-icon" style="background:#f0fdfa; color:#0d9488;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="adb-stat-label">Available Balance</div>
                        <div class="adb-stat-val" style="color:#0d9488; font-size:16px;">
                            {{ $accountSummary['funding_source'] ?? 'Active' }}
                        </div>
                        <div class="adb-stat-sub">Current prepaid balance in Meta</div>
                    </div>
                </div>

                {{-- Payment Methods Used --}}
                <div class="adb-stat-card">
                    <div class="adb-stat-icon" style="background:#faf5ff; color:#7c3aed;">
                        <i class="bi bi-credit-card-2-front-fill"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="adb-stat-label">Payment Types</div>
                        <div class="adb-stat-val" style="font-size:15px; font-weight:800; display:flex; gap:5px; flex-wrap:wrap; margin-top:4px;">
                            @php
                                $usedMethods = collect($rows)->pluck('payment_method')->filter()->unique()->values();
                            @endphp
                            @forelse($usedMethods as $mName)
                                @php
                                    $mLower = strtolower($mName);
                                    $isUpi = str_contains($mLower, 'upi');
                                    $isNet = str_contains($mLower, 'net banking') || str_contains($mLower, 'bank');
                                @endphp
                                <span class="meta-type-tag {{ $isUpi ? 'tag-upi' : ($isNet ? 'tag-netbanking' : 'tag-card') }}" style="font-size:11px;">
                                    @if($isUpi) <i class="bi bi-phone"></i>
                                    @elseif($isNet) <i class="bi bi-bank"></i>
                                    @else <i class="bi bi-credit-card"></i>
                                    @endif
                                    {{ $mName }}
                                </span>
                            @empty
                                <span style="font-size:13px; color:#64748b;">Net Banking / UPI</span>
                            @endforelse
                        </div>
                        <div class="adb-stat-sub">Channels used for payments</div>
                    </div>
                </div>
            </div>

            {{-- Each Account Wise Cards Section --}}
            @if(count($accountSummaries) > 0)
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                        <div style="font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#475569; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-person-badge-fill" style="color:#fe5f04;"></i>
                            <span>Account Wise Payment Breakdown</span>
                        </div>
                        @if($selectedAccountFilter !== 'all' && count($accountSummaries) > 1)
                            <a href="{{ route('accounts.ad-budget.meta-payments', array_merge(request()->query(), ['ad_account_id' => 'all'])) }}"
                               class="adb-btn adb-btn-secondary adb-btn-sm">
                                <i class="bi bi-grid-fill"></i> Show All Accounts
                            </a>
                        @endif
                    </div>

                    <div class="meta-acc-grid">
                        @foreach($accountSummaries as $acc)
                            @php
                                $accSym = $acc['currency_symbol'] ?? $currencySymbol;
                                $isSelected = ($selectedAccountFilter === $acc['id']);
                            @endphp
                            <div class="meta-acc-card {{ $isSelected ? 'is-selected' : '' }}">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                                    <div>
                                        <div style="font-size:15px; font-weight:800; color:#0f172a;">
                                            {{ $acc['label'] ?? $acc['name'] }}
                                        </div>
                                        <div style="font-size:11.5px; color:#64748b; font-family:monospace; margin-top:2px;">
                                            {{ $acc['id'] }}
                                        </div>
                                    </div>
                                    <span class="meta-acc-pill">{{ $acc['currency'] }}</span>
                                </div>

                                {{-- Account Metrics --}}
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:10px; padding:10px 12px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
                                    <div>
                                        <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase;">Total Payments</div>
                                        <div style="font-size:18px; font-weight:800; color:#059669; margin-top:2px;">
                                            {{ $accSym }}{{ number_format($acc['total_paid'] ?? 0, 2) }}
                                        </div>
                                        <div style="font-size:11px; color:#64748b; font-weight:600;">
                                            {{ $acc['payments_count'] ?? 0 }} transactions
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase;">Available Balance</div>
                                        <div style="font-size:14px; font-weight:800; color:#0284c7; margin-top:4px; line-height:1.2;">
                                            {{ $acc['funding_source'] ?? 'Active' }}
                                        </div>
                                        @if(($acc['balance'] ?? 0) > 0)
                                            <div style="font-size:10.5px; color:#c2410c; margin-top:2px;">Unbilled: {{ $accSym }}{{ number_format($acc['balance'], 2) }}</div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Payment Types used for this account --}}
                                @if(! empty($acc['payment_methods']))
                                    <div style="margin-top:10px; display:flex; flex-direction:column; gap:5px;">
                                        <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em;">Payment Types Used:</div>
                                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                            @foreach($acc['payment_methods'] as $m)
                                                @php
                                                    $mLower = strtolower($m['method']);
                                                    $isUpi = str_contains($mLower, 'upi');
                                                    $isNet = str_contains($mLower, 'net banking') || str_contains($mLower, 'bank');
                                                @endphp
                                                <span class="meta-type-tag {{ $isUpi ? 'tag-upi' : ($isNet ? 'tag-netbanking' : 'tag-card') }}">
                                                    @if($isUpi) <i class="bi bi-phone"></i>
                                                    @elseif($isNet) <i class="bi bi-bank"></i>
                                                    @else <i class="bi bi-credit-card"></i>
                                                    @endif
                                                    <strong>{{ $m['method'] }}</strong>: {{ $accSym }}{{ number_format($m['total'], 2) }} ({{ $m['count'] }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Switch / Filter Action --}}
                                <div style="margin-top:10px; padding-top:8px; border-top:1px dashed #e2e8f0;">
                                    @if($isSelected)
                                        <div style="font-size:12px; font-weight:800; color:#fe5f04; display:flex; align-items:center; gap:6px;">
                                            <i class="bi bi-check-circle-fill"></i> Currently Viewing This Account
                                        </div>
                                    @else
                                        <a href="{{ route('accounts.ad-budget.meta-payments', array_merge(request()->query(), ['ad_account_id' => $acc['id']])) }}"
                                           class="adb-btn adb-btn-secondary adb-btn-sm" style="width:100%; justify-content:center;">
                                            <i class="bi bi-filter"></i> View Payment History For This Account
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Main Payment History Card --}}
            <div class="adb-card">

                {{-- Mode Switcher Tabs (Only Payments vs All Activity) --}}
                <div class="meta-tabs">
                    <a href="{{ route('accounts.ad-budget.meta-payments', array_merge(request()->query(), ['view_type' => 'payments_only'])) }}"
                       class="meta-tab-btn {{ $viewType === 'payments_only' ? 'is-active' : '' }}">
                        <i class="bi bi-cash-coin"></i>
                        <span>Payments to Meta Only (Top-ups &amp; Charges)</span>
                    </a>
                    <a href="{{ route('accounts.ad-budget.meta-payments', array_merge(request()->query(), ['view_type' => 'all'])) }}"
                       class="meta-tab-btn {{ $viewType === 'all' ? 'is-active' : '' }}">
                        <i class="bi bi-list-check"></i>
                        <span>All Activity (Including Daily Deductions)</span>
                    </a>
                </div>

                {{-- Filter Bar --}}
                <div class="meta-filter-accordion">
                    <div class="meta-filter-panel">
                        <form method="GET" action="{{ route('accounts.ad-budget.meta-payments') }}">
                            <input type="hidden" name="view_type" value="{{ $viewType }}">

                            <div class="meta-filter-grid">
                                {{-- Account Select --}}
                                <div class="meta-filter-field">
                                    <label class="meta-filter-label" for="mp_account">Filter Account</label>
                                    <select name="ad_account_id" id="mp_account" class="adb-select">
                                        @if($connectedAccounts->count() > 1)
                                            <option value="all" {{ $selectedAccountFilter === 'all' ? 'selected' : '' }}>
                                                All Connected Accounts ({{ $connectedAccounts->count() }})
                                            </option>
                                        @endif
                                        @foreach($connectedAccounts as $acc)
                                            <option value="{{ $acc['ad_account_id'] }}" {{ $selectedAccountFilter === $acc['ad_account_id'] ? 'selected' : '' }}>
                                                {{ $acc['account_label'] ? "{$acc['account_label']} ({$acc['ad_account_id']})" : $acc['ad_account_id'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Date Preset --}}
                                <div class="meta-filter-field">
                                    <label class="meta-filter-label" for="mp_date_preset">Date Range</label>
                                    <select name="date_preset" id="mp_date_preset" class="adb-select" onchange="toggleCustomDates(this.value)">
                                        @foreach($datePresets as $key => $label)
                                            <option value="{{ $key }}" {{ $datePreset === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Custom Date From --}}
                                <div class="meta-filter-field custom-date-col" style="{{ $datePreset === 'custom' ? '' : 'display:none;' }}">
                                    <label class="meta-filter-label" for="mp_date_from">From</label>
                                    <input type="date" name="date_from" id="mp_date_from" value="{{ $dateFrom }}" class="adb-input">
                                </div>

                                {{-- Custom Date To --}}
                                <div class="meta-filter-field custom-date-col" style="{{ $datePreset === 'custom' ? '' : 'display:none;' }}">
                                    <label class="meta-filter-label" for="mp_date_to">To</label>
                                    <input type="date" name="date_to" id="mp_date_to" value="{{ $dateTo }}" class="adb-input">
                                </div>

                                {{-- Status Filter --}}
                                <div class="meta-filter-field">
                                    <label class="meta-filter-label" for="mp_status">Payment Type / Filter</label>
                                    <select name="status" id="mp_status" class="adb-select">
                                        <option value="all" {{ $statusFilter === 'all' || $statusFilter === '' ? 'selected' : '' }}>All Payment Types</option>
                                        <option value="funds_added" {{ $statusFilter === 'funds_added' ? 'selected' : '' }}>Money Added (Net Banking / UPI)</option>
                                        <option value="billed" {{ $statusFilter === 'billed' ? 'selected' : '' }}>Account Charges / Deductions</option>
                                        <option value="declines" {{ $statusFilter === 'declines' ? 'selected' : '' }}>Declined / Failed Only</option>
                                    </select>
                                </div>

                                {{-- Search Input --}}
                                <div class="meta-filter-field">
                                    <label class="meta-filter-label" for="mp_search">Search Keyword</label>
                                    <div class="adb-search-wrap">
                                        <i class="bi bi-search adb-search-ico"></i>
                                        <input type="text" name="search" id="mp_search" value="{{ $search }}" class="adb-search-input" placeholder="UPI, Net Banking, ID...">
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div style="display:flex; gap:8px;">
                                    <button type="submit" class="adb-btn adb-btn-primary" style="flex:1;">
                                        <i class="bi bi-funnel"></i> Apply
                                    </button>
                                    <a href="{{ route('accounts.ad-budget.meta-payments') }}" class="adb-btn adb-btn-secondary" title="Reset Filters">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Payment History Table --}}
                @if(empty($rows))
                    <div style="text-align:center; padding:48px 20px; color:#64748b;">
                        <i class="bi bi-receipt" style="font-size:36px; color:#cbd5e1; display:block; margin-bottom:10px;"></i>
                        <div style="font-size:15px; font-weight:700; color:#0f172a; margin-bottom:4px;">No Payment Records Found</div>
                        <div style="font-size:13px; max-width:480px; margin:0 auto;">
                            No payment transactions were found for the selected account and period. Try changing the date range or filters.
                        </div>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="adb-tbl">
                            <thead>
                                <tr>
                                    <th>Payment Date &amp; Time</th>
                                    <th>Ad Account</th>
                                    <th>Payment Type / Method</th>
                                    <th>Amount Paid</th>
                                    <th>Transaction Event</th>
                                    <th>Status</th>
                                    <th>Reference / Charge ID</th>
                                    <th>Paid By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    @php
                                        $mName = $row['payment_method'] ?? 'Payment Method';
                                        $mLower = strtolower($mName);
                                        $isUpi = str_contains($mLower, 'upi');
                                        $isNet = str_contains($mLower, 'net banking') || str_contains($mLower, 'bank');
                                    @endphp
                                    <tr>
                                        {{-- Date & Time --}}
                                        <td>
                                            <div style="font-weight:700; color:#0f172a;">
                                                {{ $row['event_time'] ? \Carbon\Carbon::parse($row['event_time'])->format('d M Y, h:i A') : '—' }}
                                            </div>
                                            <div style="font-size:11px; color:#64748b;">
                                                {{ $row['event_time'] ? \Carbon\Carbon::parse($row['event_time'])->diffForHumans() : '' }}
                                            </div>
                                        </td>

                                        {{-- Ad Account --}}
                                        <td>
                                            <div style="font-weight:700; color:#0f172a;">{{ $row['ad_account_name'] }}</div>
                                            <div style="font-size:11px; color:#64748b; font-family:monospace;">{{ $row['ad_account_id'] }}</div>
                                        </td>

                                        {{-- Payment Type / Method --}}
                                        <td>
                                            <span class="meta-type-tag {{ $isUpi ? 'tag-upi' : ($isNet ? 'tag-netbanking' : 'tag-card') }}" style="font-size:12px;">
                                                @if($isUpi)
                                                    <i class="bi bi-phone-fill"></i>
                                                @elseif($isNet)
                                                    <i class="bi bi-bank2"></i>
                                                @else
                                                    <i class="bi bi-credit-card-2-front-fill"></i>
                                                @endif
                                                <span>{{ $mName }}</span>
                                            </span>
                                        </td>

                                        {{-- Amount Paid --}}
                                        <td>
                                            @if($row['amount'] > 0)
                                                <div style="font-size:15px; font-weight:800; color:#059669;">
                                                    {{ $row['currency_symbol'] }}{{ number_format((float) $row['amount'], 2) }}
                                                </div>
                                                <div style="font-size:10.5px; color:#64748b; font-weight:600;">{{ $row['currency'] }}</div>
                                            @else
                                                <span style="color:#94a3b8; font-weight:600;">—</span>
                                            @endif
                                        </td>

                                        {{-- Event / Purpose --}}
                                        <td>
                                            <div style="font-weight:700; color:#1e293b; font-size:12.5px;">
                                                {{ $row['status_label'] }}
                                            </div>
                                            @if(! empty($row['billing_reason']))
                                                <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                                    {{ $row['billing_reason'] }}
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            <span class="badge-status badge-status-{{ $row['status_badge'] }}">
                                                @if($row['status_badge'] === 'success')
                                                    <i class="bi bi-check-circle-fill"></i> Paid
                                                @elseif($row['status_badge'] === 'failed')
                                                    <i class="bi bi-x-circle-fill"></i> Failed
                                                @elseif($row['status_badge'] === 'refund')
                                                    <i class="bi bi-arrow-counterclockwise"></i> Refunded
                                                @else
                                                    <i class="bi bi-info-circle-fill"></i> {{ $row['status_label'] }}
                                                @endif
                                            </span>
                                        </td>

                                        {{-- Transaction ID --}}
                                        <td>
                                            @if($row['transaction_id'])
                                                <span style="font-family:monospace; font-size:11.5px; background:#f1f5f9; padding:2px 7px; border-radius:6px; color:#334155; word-break:break-all;">
                                                    {{ $row['transaction_id'] }}
                                                </span>
                                            @else
                                                <span style="color:#94a3b8;">—</span>
                                            @endif
                                        </td>

                                        {{-- Performed By --}}
                                        <td>
                                            <span style="font-size:12.5px; color:#475569;">{{ $row['actor_name'] ?: 'Meta System' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align:right; font-weight:800;">Total Payments in Period:</td>
                                    <td style="color:#059669; font-size:16px; font-weight:800;">
                                        {{ $currencySymbol }}{{ number_format($totals['total_paid'] ?? 0, 2) }}
                                    </td>
                                    <td colspan="4" style="font-size:12px; color:#64748b; font-weight:600;">
                                        @if(($totals['funds_added_count'] ?? 0) > 0)
                                            {{ $totals['funds_added_count'] }} Top-ups via Net Banking / UPI ({{ $currencySymbol }}{{ number_format($totals['total_funds_added'] ?? 0, 2) }})
                                        @endif
                                        @if(($totals['total_charges'] ?? 0) > 0 && $viewType === 'all')
                                            • Ad Billing Deductions: {{ $currencySymbol }}{{ number_format($totals['total_charges'] ?? 0, 2) }}
                                        @endif
                                        @if(($totals['declined_count'] ?? 0) > 0)
                                            • <span style="color:#b91c1c;">{{ $totals['declined_count'] }} declined</span>
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

        @endif

    </div>

</div>

{{-- Manage Meta Accounts Modal --}}
<div id="metaSettingsModal" class="modal-overlay" onclick="handleModalOverlayClick(event)">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-meta" style="color:#fe5f04;"></i>
                <span>Manage Meta Ad Accounts</span>
            </div>
            <button type="button" class="modal-close" onclick="closeMetaSettingsModal()">&times;</button>
        </div>
        <div class="modal-body">
            @if($savedAccounts->isNotEmpty())
                <div>
                    <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:8px;">
                        Connected Accounts ({{ $savedAccounts->count() }})
                    </div>
                    <div class="meta-saved-list">
                        @foreach($savedAccounts as $acc)
                            <div class="meta-saved-item">
                                <div>
                                    <div style="font-weight:700; color:#0f172a;">
                                        {{ $acc['account_label'] ?: $acc['ad_account_id'] }}
                                    </div>
                                    <div style="font-size:11.5px; color:#64748b; font-family:monospace;">
                                        {{ $acc['ad_account_id'] }} &bull; Token: {{ $acc['masked_token'] ?: 'None' }}
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <button type="button" class="adb-btn adb-btn-secondary adb-btn-sm"
                                            onclick='editMetaAccount(@json($acc))'>
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </button>
                                    <form method="POST" action="{{ route('accounts.ad-budget.meta-payments.delete', $acc['id']) }}"
                                          onsubmit="return confirm('Remove this Meta Ad Account?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="adb-btn adb-btn-danger adb-btn-sm">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('accounts.ad-budget.meta-payments.settings') }}" id="metaSettingsForm">
                @csrf
                <input type="hidden" name="setting_id" id="modal_setting_id" value="">

                <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:8px;" id="modalFormTitle">
                    Add / Configure Meta Account
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_ad_account_id">Meta Ad Account ID *</label>
                    <input type="text" name="ad_account_id" id="modal_ad_account_id" class="form-input"
                           placeholder="act_1150358500674602 or 1150358500674602" required>
                    <small style="font-size:11px; color:#64748b;">From Facebook Ads Manager URL (e.g. act_1234567890).</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_account_label">Account Display Label</label>
                    <input type="text" name="account_label" id="modal_account_label" class="form-input"
                           placeholder="e.g. STS Brand Campaigns">
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_access_token">Marketing API Access Token</label>
                    <input type="password" name="access_token" id="modal_access_token" class="form-input"
                           placeholder="{{ $primaryMaskedToken ? "Leave blank to reuse: {$primaryMaskedToken}" : 'EAABw...' }}">
                    <small style="font-size:11px; color:#64748b;">
                        Needs <code>ads_read</code> / <code>ads_management</code> with Admin or Finance Editor role to read payments.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="modal_graph_version">Graph API Version</label>
                    <input type="text" name="graph_version" id="modal_graph_version" class="form-input"
                           value="v20.0" placeholder="v20.0">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:8px;">
                    <button type="button" class="adb-btn adb-btn-secondary" onclick="resetMetaModalForm()">
                        Clear / Add New
                    </button>
                    <button type="submit" class="adb-btn adb-btn-primary">
                        <i class="bi bi-check-lg"></i> Save Credentials
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleCustomDates(val) {
    document.querySelectorAll('.custom-date-col').forEach(el => {
        el.style.display = (val === 'custom') ? 'flex' : 'none';
    });
}

function openMetaSettingsModal() {
    document.getElementById('metaSettingsModal').classList.add('active');
}

function closeMetaSettingsModal() {
    document.getElementById('metaSettingsModal').classList.remove('active');
}

function handleModalOverlayClick(e) {
    if (e.target.id === 'metaSettingsModal') {
        closeMetaSettingsModal();
    }
}

function editMetaAccount(acc) {
    document.getElementById('modal_setting_id').value = acc.id || '';
    document.getElementById('modal_ad_account_id').value = acc.ad_account_id || '';
    document.getElementById('modal_account_label').value = acc.account_label || '';
    document.getElementById('modal_graph_version').value = acc.graph_version || 'v20.0';
    document.getElementById('modal_access_token').value = '';
    document.getElementById('modal_access_token').placeholder = acc.masked_token ? ('Leave blank to keep (' + acc.masked_token + ')') : '';
    document.getElementById('modalFormTitle').innerText = 'Edit Meta Account: ' + (acc.account_label || acc.ad_account_id);
    document.getElementById('modal_ad_account_id').focus();
}

function resetMetaModalForm() {
    document.getElementById('modal_setting_id').value = '';
    document.getElementById('modal_ad_account_id').value = '';
    document.getElementById('modal_account_label').value = '';
    document.getElementById('modal_graph_version').value = 'v20.0';
    document.getElementById('modal_access_token').value = '';
    document.getElementById('modal_access_token').placeholder = '{{ $primaryMaskedToken ? "Leave blank to reuse: {$primaryMaskedToken}" : "EAABw..." }}';
    document.getElementById('modalFormTitle').innerText = 'Add / Configure Meta Account';
}
</script>
@endpush
@endsection
