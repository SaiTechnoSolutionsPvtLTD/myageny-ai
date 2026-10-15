@extends('layouts.app')

@section('title', 'Ad Budget Ledger Statement')

@push('styles')
<style>
.adb-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px 28px;
    background: #f4f5f7;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    min-height: 100%;
}

/* Topbar */
.adb-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
    padding: 18px 24px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.adb-title {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin-bottom: 3px;
}
.adb-sub {
    font-size: 13px;
    color: #64748b;
}

/* Stat Cards */
.adb-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
}
.adb-stat-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.adb-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}
.adb-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.adb-stat-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.adb-stat-val {
    font-size: 22px;
    font-weight: 800;
    margin-top: 3px;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}

/* Card Container */
.adb-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

/* Nav Tabs */
.adb-nav-tabs {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 12px 20px 0 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    overflow-x: auto;
}
.adb-tab-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px 10px 0 0;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    border: 1px solid transparent;
    border-bottom: none;
    background: transparent;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.adb-tab-item:hover {
    color: #fe5f04;
    background: #ffffff;
}
.adb-tab-item.active {
    color: #fe5f04;
    background: #ffffff;
    border-color: #e2e8f0;
    border-bottom: 2px solid #fe5f04;
    margin-bottom: -1px;
}

/* Filter Bar */
.adb-filter-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
    flex-wrap: wrap;
}
.adb-search-wrap {
    position: relative;
    flex: 1;
    min-width: 220px;
    max-width: 320px;
}
.adb-search-input {
    width: 100%;
    padding: 9px 12px 9px 38px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 13px;
    outline: none;
    font-family: inherit;
    background: #ffffff;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.adb-search-input:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.adb-search-ico {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}
.adb-select {
    padding: 9px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    background: #ffffff;
    color: #1e293b;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.adb-select:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}

/* Buttons */
.adb-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    white-space: nowrap;
}
.adb-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25);
}
.adb-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(254, 95, 4, 0.35);
    color: #ffffff;
}
.adb-btn-secondary {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
}
.adb-btn-secondary:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* Table Design */
.adb-tbl-wrap {
    width: 100%;
    overflow-x: auto;
    background: #ffffff;
}
.adb-tbl {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}
.adb-tbl th {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    padding: 13px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    white-space: nowrap;
}
.adb-tbl td {
    padding: 14px 16px;
    font-size: 13px;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.adb-tbl tbody tr {
    transition: background-color 0.15s ease;
}
.adb-tbl tbody tr:hover {
    background-color: #f8fafc;
}

/* Badges */
.txn-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 9px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.txn-badge-alloc {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #ffedd5;
}
.txn-badge-spend {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
}

.platform-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* Balance Pill */
.balance-pill {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 8px;
    font-weight: 800;
    font-size: 13px;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.balance-positive {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.balance-negative {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
</style>
@endpush

@section('content')
<div class="adb-page">

    {{-- Topbar --}}
    <div class="adb-topbar">
        <div>
            <div class="adb-title">Ad Budget Ledger Statement</div>
            <div class="adb-sub">Passbook view of approved budget allocations (Credit +) and ad spend entries (Debit -) with running balance</div>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" class="adb-btn adb-btn-primary" onclick="openPassbookPdf(false)">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Passbook
            </button>
            <button type="button" class="adb-btn adb-btn-secondary" onclick="openPassbookPdf(true)" title="Download PDF Statement">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download PDF
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="adb-stats-grid">
        @if($dateFrom && $openingBalance != 0)
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#f1f5f9; color:#475569;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="adb-stat-label">Opening Balance B/F</div>
                <div class="adb-stat-val" style="color:#0f172a;">₹{{ number_format($openingBalance, 2) }}</div>
                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">Prior to {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</div>
            </div>
        </div>
        @endif

        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#fff7ed; color:#ea580c;">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Allocated (Credit +)</div>
                <div class="adb-stat-val" style="color:#ea580c;">+₹{{ number_format($periodAllocated, 2) }}</div>
                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">{{ $selectedAccount ? $selectedAccount->account_name : 'All Accounts Pool' }}</div>
            </div>
        </div>

        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-credit-card-2-front"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Ad Spent (Debit -)</div>
                <div class="adb-stat-val" style="color:#2563eb;">-₹{{ number_format($periodSpent, 2) }}</div>
                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">{{ $selectedAccount ? $selectedAccount->account_name : 'All Accounts Pool' }}</div>
            </div>
        </div>

        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:{{ $periodClosingBalance >= 0 ? '#f0fdf4' : '#fef2f2' }}; color:{{ $periodClosingBalance >= 0 ? '#16a34a' : '#dc2626' }};">
                <i class="bi bi-bank"></i>
            </div>
            <div>
                <div class="adb-stat-label">
                    {{ $selectedAccount ? $selectedAccount->account_name . ' Balance' : 'Consolidated Closing Balance' }}
                </div>
                <div class="adb-stat-val" style="color:{{ $periodClosingBalance >= 0 ? '#16a34a' : '#dc2626' }};">
                    ₹{{ number_format($periodClosingBalance, 2) }}
                </div>
                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">{{ $periodClosingBalance >= 0 ? 'Net Available Balance' : 'Overspent' }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="adb-card">
        {{-- Navigation Tabs --}}
        <div class="adb-nav-tabs">
            <a href="{{ route('accounts.ad-budget.clients') }}" class="adb-tab-item">
                <span>For Clients</span>
            </a>
            <a href="{{ route('accounts.ad-budget.partners') }}" class="adb-tab-item">
                <span>For Partners</span>
            </a>
            <a href="{{ route('accounts.ad-budget.spend') }}" class="adb-tab-item">
                <span>Ad Spent</span>
            </a>
            <a href="{{ route('accounts.ad-budget.allocation-vs-spent') }}" class="adb-tab-item active">
                <span>Allocation Vs Spent (Ledger)</span>
            </a>
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('accounts.ad-budget.allocation-vs-spent') }}" id="adbFilterForm">
            <div class="adb-filter-bar">
                <div class="adb-search-wrap">
                    <i class="bi bi-search adb-search-ico"></i>
                    <input type="text" name="search" class="adb-search-input" placeholder="Search particulars, account, user…" value="{{ request('search') }}">
                </div>

                <select name="ad_account_id" class="adb-select" style="min-width:190px;" onchange="this.form.submit()">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('ad_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->account_name }} ({{ $acc->platform }})
                        </option>
                    @endforeach
                </select>

                <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-size:12px; color:#64748b; font-weight:700;">From:</span>
                    <input type="date" name="date_from" class="adb-select" value="{{ $dateFrom }}" onchange="this.form.submit()">
                </div>

                <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-size:12px; color:#64748b; font-weight:700;">To:</span>
                    <input type="date" name="date_to" class="adb-select" value="{{ $dateTo }}" onchange="this.form.submit()">
                </div>

                <button type="submit" class="adb-btn adb-btn-secondary" style="padding:9px 15px; font-size:12.5px;">Apply Filter</button>
                @if(request()->hasAny(['search','ad_account_id','date_from','date_to']))
                    <a href="{{ route('accounts.ad-budget.allocation-vs-spent') }}" class="adb-btn adb-btn-secondary" style="padding:9px 15px; font-size:12.5px; background:#fff;">Reset</a>
                @endif
            </div>
        </form>

        {{-- Ledger Statement Table --}}
        @if($rows->isEmpty())
            <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <i class="bi bi-journal-text" style="font-size:48px; display:block; margin-bottom:12px; color:#cbd5e1;"></i>
                <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Ledger Transactions Found</div>
                <div style="font-size:13px; color:#94a3b8;">No budget allocation or ad spend entries match your selected criteria.</div>
            </div>
        @else
            <div class="adb-tbl-wrap">
                <table class="adb-tbl">
                    <thead>
                        <tr>
                            <th style="width:50px; text-align:center;">#</th>
                            <th style="width:115px;">Date</th>
                            <th style="width:200px;">Ad Account</th>
                            <th style="width:105px; text-align:center;">Type</th>
                            <th>Particulars / Description</th>
                            <th style="width:145px; text-align:right;">Allocated (Credit +)</th>
                            <th style="width:135px; text-align:right;">Spent (Debit -)</th>
                            <th style="width:150px; text-align:right;">Running Balance</th>
                            <th style="width:140px;">User / Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $index => $row)
                        @php
                            $dateObj        = \Carbon\Carbon::parse($row['date']);
                            $allocated      = (float) $row['allocated_amount'];
                            $spent          = (float) $row['spent_amount'];
                            $runningBalance = (float) $row['running_balance'];
                            $isAllocation   = $row['type'] === 'allocation';
                        @endphp
                        <tr>
                            <td style="text-align:center; color:#94a3b8; font-weight:700; font-size:12px;">
                                #{{ $rows->count() - $index }}
                            </td>
                            <td>
                                <strong style="color:#0f172a; font-size:13px;">{{ $dateObj->format('d M Y') }}</strong>
                                <div style="font-size:11px; color:#94a3b8; font-weight:500;">{{ $dateObj->format('l') }}</div>
                            </td>
                            <td>
                                <div style="font-weight:700; color:#0f172a; font-size:13px;">
                                    {{ $row['ad_account']?->account_name ?? '—' }}
                                </div>
                                <div style="display:flex; align-items:center; gap:5px; margin-top:3px;">
                                    <span class="platform-badge">{{ $row['ad_account']?->platform ?? 'Meta' }}</span>
                                    @if($row['ad_account']?->account_id)
                                        <span style="font-size:10.5px; color:#64748b;">({{ $row['ad_account']->account_id }})</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align:center;">
                                @if($isAllocation)
                                    <span class="txn-badge txn-badge-alloc">+ ALLOC</span>
                                @else
                                    <span class="txn-badge txn-badge-spend">- SPEND</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:13px; color:#1e293b; font-weight:600; line-height:1.4;">{{ $row['description'] }}</div>
                                @if(!empty($row['ref_id']))
                                    <div style="font-size:11px; color:#94a3b8; margin-top:2px;">Ref #{{ $row['ref_id'] }}</div>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:800; font-size:13.5px; font-variant-numeric:tabular-nums; color:{{ $allocated > 0 ? '#ea580c' : '#cbd5e1' }};">
                                {{ $allocated > 0 ? '+₹' . number_format($allocated, 2) : '—' }}
                            </td>
                            <td style="text-align:right; font-weight:800; font-size:13.5px; font-variant-numeric:tabular-nums; color:{{ $spent > 0 ? '#2563eb' : '#cbd5e1' }};">
                                {{ $spent > 0 ? '-₹' . number_format($spent, 2) : '—' }}
                            </td>
                            <td style="text-align:right;">
                                <span class="balance-pill {{ $runningBalance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                    ₹{{ number_format($runningBalance, 2) }}
                                </span>
                                @if(!$selectedAccount && isset($row['account_balance']))
                                    <div style="font-size:10.5px; color:#64748b; margin-top:3px; font-weight:600;" title="Individual Account Balance">
                                        Acc: ₹{{ number_format($row['account_balance'], 2) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:12.5px; font-weight:700; color:#334155;">{{ $row['user_name'] }}</div>
                                <div style="font-size:10.5px; color:#94a3b8;">Ref #{{ $row['ref_id'] }}</div>
                            </td>
                        </tr>
                        @endforeach

                        @if($dateFrom && $openingBalance != 0)
                        <tr style="background:#f8fafc; font-weight:700; border-top:1px dashed #cbd5e1;">
                            <td style="text-align:center; color:#64748b; font-size:11px;">OB</td>
                            <td>
                                <strong style="color:#475569;">{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</strong>
                            </td>
                            <td colspan="3">
                                <span style="color:#475569; font-style:italic;">[Opening Balance Brought Forward prior to {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}]</span>
                            </td>
                            <td style="text-align:right; color:#94a3b8;">—</td>
                            <td style="text-align:right; color:#94a3b8;">—</td>
                            <td style="text-align:right;">
                                <span class="balance-pill {{ $openingBalance >= 0 ? 'balance-positive' : 'balance-negative' }}" style="opacity:0.9;">
                                    ₹{{ number_format($openingBalance, 2) }}
                                </span>
                            </td>
                            <td style="color:#64748b; font-size:11.5px;">System</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:800; border-top:2px solid #cbd5e1; border-bottom:2px solid #cbd5e1;">
                            <td colspan="5" style="text-align:right; text-transform:uppercase; color:#0f172a; padding:15px 16px; font-size:12px; letter-spacing:0.04em;">
                                Total for Statement Period:
                            </td>
                            <td style="text-align:right; color:#ea580c; padding:15px 16px; font-size:14px; font-weight:800; font-variant-numeric:tabular-nums;">
                                +₹{{ number_format($periodAllocated, 2) }}
                            </td>
                            <td style="text-align:right; color:#2563eb; padding:15px 16px; font-size:14px; font-weight:800; font-variant-numeric:tabular-nums;">
                                -₹{{ number_format($periodSpent, 2) }}
                            </td>
                            <td style="text-align:right; padding:15px 16px;">
                                <span class="balance-pill {{ $periodClosingBalance >= 0 ? 'balance-positive' : 'balance-negative' }}" style="font-size:13.5px; padding:5px 14px;">
                                    ₹{{ number_format($periodClosingBalance, 2) }}
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function openPassbookPdf(download = false) {
    const form = document.getElementById('adbFilterForm') || document.querySelector('form[action="{{ route('accounts.ad-budget.allocation-vs-spent') }}"]');
    const params = new URLSearchParams(new FormData(form));
    if (download) {
        params.set('download', '1');
    }
    const url = "{{ route('accounts.ad-budget.allocation-vs-spent.pdf') }}?" + params.toString();
    if (download) {
        window.location.href = url;
    } else {
        window.open(url, '_blank');
    }
}
</script>
@endpush
