@extends('layouts.app')

@section('title', 'Ad Budget Ledger Statement')

@push('styles')
<style>
.adb-page {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    background: #f4f5f7;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* Topbar */
.adb-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 0 28px;
    min-height: 60px;
    background: #ffffff;
    border-bottom: 1px solid #e1dee3;
    position: sticky;
    top: 0;
    z-index: 30;
}
.adb-title {
    font-size: 18px;
    font-weight: 800;
    color: #121212;
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.2;
}
.adb-breadcrumb {
    font-size: 12px;
    color: #9e9e9e;
    margin-top: 2px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.adb-breadcrumb a { color: #9e9e9e; text-decoration: none; }
.adb-breadcrumb a:hover { color: #fe5f04; }
.adb-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.adb-body { padding: 22px 28px 34px; display: flex; flex-direction: column; gap: 20px; flex: 1; }
@media (max-width: 900px) {
    .adb-topbar { padding: 14px 20px; flex-direction: column; align-items: flex-start; gap: 12px; min-height: auto; }
    .adb-body { padding: 16px 20px 24px; }
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

/* Filter Section & Unified Controls */
.adb-filter-container {
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px 14px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.adb-filter-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.adb-search-wrap {
    position: relative;
    flex: 1 1 240px;
    min-width: 200px;
    max-width: 320px;
}
.adb-search-input {
    width: 100%;
    height: 38px;
    padding: 0 32px 0 36px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 13px;
    outline: none;
    font-family: inherit;
    background: #ffffff;
    color: #0f172a;
    transition: all 0.15s ease;
    box-sizing: border-box;
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
    pointer-events: none;
}
.adb-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 16px;
    cursor: pointer;
    padding: 2px 6px;
    line-height: 1;
    border-radius: 4px;
}
.adb-search-clear:hover {
    color: #ef4444;
}

.adb-select-wrap {
    position: relative;
    flex: 0 1 220px;
    min-width: 170px;
}
.adb-control-ico {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
    pointer-events: none;
}
.adb-select {
    width: 100%;
    height: 38px;
    padding: 0 28px 0 34px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    background: #ffffff;
    color: #1e293b;
    transition: all 0.15s ease;
    box-sizing: border-box;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}
.adb-select:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}

/* Date Range Combined Capsule */
.adb-date-range-box {
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    height: 38px;
    padding: 0 8px;
    gap: 6px;
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.adb-date-range-box:focus-within {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.adb-date-field {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.adb-date-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    user-select: none;
}
.adb-date-input {
    border: none;
    outline: none;
    background: transparent;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    color: #0f172a;
    padding: 0 2px;
    cursor: pointer;
    height: 34px;
}
.adb-date-arrow {
    color: #94a3b8;
    font-size: 11px;
    font-weight: 700;
    user-select: none;
    padding: 0 2px;
}

/* Filter Actions */
.adb-filter-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}
@media (max-width: 900px) {
    .adb-filter-actions {
        margin-left: 0;
        width: 100%;
    }
}

/* Presets Bar */
.adb-presets-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding-top: 10px;
    border-top: 1px dashed #e2e8f0;
}
.adb-presets-left {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.adb-presets-title {
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 4px;
    margin-right: 4px;
}
.adb-preset-pill {
    padding: 4px 10px;
    border-radius: 7px;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.15s ease;
    line-height: 1.2;
}
.adb-preset-pill:hover {
    background: #fff7ed;
    border-color: #fdba74;
    color: #ea580c;
}
.adb-preset-pill.active {
    background: #fff7ed;
    border-color: #fe5f04;
    color: #fe5f04;
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(254, 95, 4, 0.1);
}
.adb-filter-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    color: #0369a1;
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    padding: 3px 9px;
    border-radius: 999px;
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
            <div class="adb-title">
                <i class="bi bi-journal-bookmark-fill" style="color:#fe5f04;"></i>
                <span>Ad Budget Ledger Statement</span>
            </div>
            <div class="adb-breadcrumb">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <span>Ad Budget</span>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Ledger Statement</span>
            </div>
        </div>
        <div class="adb-actions">
            <button type="button" class="adb-btn adb-btn-primary" onclick="openPassbookPdf(false)">
                <i class="bi bi-printer"></i> Print Passbook
            </button>
            <button type="button" class="adb-btn adb-btn-secondary" onclick="openPassbookPdf(true)" title="Download PDF Statement">
                <i class="bi bi-download"></i> Download PDF
            </button>
        </div>
    </div>

    <div class="adb-body">

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
                <div class="adb-stat-label">Total Ad Spend (Debit -)</div>
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
                <span>Ad Spend</span>
            </a>
            <a href="{{ route('accounts.ad-budget.allocation-vs-spent') }}" class="adb-tab-item active">
                <span>Allocation Vs Spend (Ledger)</span>
            </a>
        </div>

        {{-- Unified Filter Section --}}
        @php
            $activeFilterCount = 0;
            if (request('search')) $activeFilterCount++;
            if (request('ad_account_id')) $activeFilterCount++;
            if ($dateFrom) $activeFilterCount++;
            if ($dateTo) $activeFilterCount++;
        @endphp
        <form method="GET" action="{{ route('accounts.ad-budget.allocation-vs-spent') }}" id="adbFilterForm">
            <div class="adb-filter-container">
                {{-- Row 1: Unified Filter Controls --}}
                <div class="adb-filter-row">
                    {{-- Search Input --}}
                    <div class="adb-search-wrap">
                        <i class="bi bi-search adb-search-ico"></i>
                        <input type="text" name="search" id="filterSearchInput" class="adb-search-input" placeholder="Search particulars, account, ref…" value="{{ request('search') }}">
                        @if(request('search'))
                            <button type="button" class="adb-search-clear" onclick="clearFilterSearch()" title="Clear search">✕</button>
                        @endif
                    </div>

                    {{-- Ad Account Dropdown --}}
                    <div class="adb-select-wrap">
                        <i class="bi bi-megaphone adb-control-ico"></i>
                        <select name="ad_account_id" class="adb-select" onchange="this.form.submit()">
                            <option value="">All Ad Accounts</option>
                            @foreach($adAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ request('ad_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->account_name }} ({{ $acc->platform }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date Range Capsule --}}
                    <div class="adb-date-range-box">
                        <div class="adb-date-field">
                            <span class="adb-date-label">
                                <i class="bi bi-calendar3" style="color:#fe5f04;"></i> From
                            </span>
                            <input type="date" name="date_from" id="filterDateFrom" class="adb-date-input" value="{{ $dateFrom }}" onchange="this.form.submit()" title="Start Date">
                        </div>
                        <span class="adb-date-arrow">➔</span>
                        <div class="adb-date-field">
                            <span class="adb-date-label">
                                <i class="bi bi-calendar3" style="color:#fe5f04;"></i> To
                            </span>
                            <input type="date" name="date_to" id="filterDateTo" class="adb-date-input" value="{{ $dateTo }}" onchange="this.form.submit()" title="End Date">
                        </div>
                    </div>

                    {{-- Filter Actions --}}
                    <div class="adb-filter-actions">
                        <button type="submit" class="adb-btn adb-btn-primary" style="height:38px; padding:0 16px; font-size:12.5px;">
                            <i class="bi bi-funnel-fill"></i>
                            <span>Filter</span>
                        </button>
                        @if(request()->hasAny(['search','ad_account_id','date_from','date_to']))
                            <a href="{{ route('accounts.ad-budget.allocation-vs-spent') }}" class="adb-btn adb-btn-secondary" style="height:38px; padding:0 14px; font-size:12.5px; background:#fff;" title="Reset All Filters">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reset</span>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Row 2: Quick Date Presets Bar --}}
                <div class="adb-presets-bar">
                    <div class="adb-presets-left">
                        <span class="adb-presets-title">
                            <i class="bi bi-lightning-charge-fill" style="color:#fe5f04;"></i>
                            <span>Quick Ranges:</span>
                        </span>
                        <button type="button" class="adb-preset-pill" data-preset="today" onclick="applyDatePreset('today')">Today</button>
                        <button type="button" class="adb-preset-pill" data-preset="this_week" onclick="applyDatePreset('this_week')">This Week</button>
                        <button type="button" class="adb-preset-pill" data-preset="this_month" onclick="applyDatePreset('this_month')">This Month</button>
                        <button type="button" class="adb-preset-pill" data-preset="last_month" onclick="applyDatePreset('last_month')">Last Month</button>
                        <button type="button" class="adb-preset-pill" data-preset="this_fy" onclick="applyDatePreset('this_fy')">Financial Year</button>
                        <button type="button" class="adb-preset-pill" data-preset="all_time" onclick="applyDatePreset('all_time')">All Time</button>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="adb-active-badge-wrap">
                            <span class="adb-filter-count-badge">
                                <i class="bi bi-check2-circle"></i>
                                <span>{{ $activeFilterCount }} {{ \Illuminate\Support\Str::plural('filter', $activeFilterCount) }} active</span>
                            </span>
                        </div>
                    @endif
                </div>
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
                            <th style="width:135px; text-align:right;">Spend (Debit -)</th>
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

    </div>{{-- /adb-body --}}
</div>{{-- /adb-page --}}
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

function clearFilterSearch() {
    const input = document.getElementById('filterSearchInput');
    if (input) {
        input.value = '';
        document.getElementById('adbFilterForm').submit();
    }
}

function applyDatePreset(type) {
    const today = new Date();
    function fmt(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    let from = '';
    let to = fmt(today);

    if (type === 'today') {
        from = fmt(today);
        to = fmt(today);
    } else if (type === 'this_week') {
        const currentDay = today.getDay(); // 0 is Sun, 1 is Mon...
        const diff = (currentDay === 0 ? -6 : 1) - currentDay;
        const monday = new Date(today);
        monday.setDate(today.getDate() + diff);
        from = fmt(monday);
        to = fmt(today);
    } else if (type === 'this_month') {
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        from = fmt(start);
        to = fmt(end);
    } else if (type === 'last_month') {
        const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const end = new Date(today.getFullYear(), today.getMonth(), 0);
        from = fmt(start);
        to = fmt(end);
    } else if (type === 'this_fy') {
        const m = today.getMonth() + 1; // 1-12
        const fyStart = m >= 4 ? today.getFullYear() : today.getFullYear() - 1;
        from = `${fyStart}-04-01`;
        to = `${fyStart + 1}-03-31`;
    } else if (type === 'all_time') {
        from = '';
        to = '';
    }

    const fromInput = document.getElementById('filterDateFrom');
    const toInput = document.getElementById('filterDateTo');
    if (fromInput) fromInput.value = from;
    if (toInput) toInput.value = to;

    document.getElementById('adbFilterForm').submit();
}

document.addEventListener("DOMContentLoaded", function() {
    const fromVal = (document.getElementById('filterDateFrom') || {}).value || '';
    const toVal = (document.getElementById('filterDateTo') || {}).value || '';
    const today = new Date();
    function fmt(d) {
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    const todayStr = fmt(today);
    const thisMonthStart = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
    const thisMonthEnd = fmt(new Date(today.getFullYear(), today.getMonth() + 1, 0));
    const lastMonthStart = fmt(new Date(today.getFullYear(), today.getMonth() - 1, 1));
    const lastMonthEnd = fmt(new Date(today.getFullYear(), today.getMonth(), 0));

    const m = today.getMonth() + 1;
    const fyStart = m >= 4 ? today.getFullYear() : today.getFullYear() - 1;
    const fyStartStr = `${fyStart}-04-01`;
    const fyEndStr = `${fyStart + 1}-03-31`;

    let activePreset = null;
    if (!fromVal && !toVal) {
        activePreset = 'all_time';
    } else if (fromVal === todayStr && toVal === todayStr) {
        activePreset = 'today';
    } else if (fromVal === thisMonthStart && (toVal === thisMonthEnd || toVal === todayStr)) {
        activePreset = 'this_month';
    } else if (fromVal === lastMonthStart && toVal === lastMonthEnd) {
        activePreset = 'last_month';
    } else if (fromVal === fyStartStr && (toVal === fyEndStr || toVal === todayStr)) {
        activePreset = 'this_fy';
    }

    if (activePreset) {
        const pill = document.querySelector(`.adb-preset-pill[data-preset="${activePreset}"]`);
        if (pill) pill.classList.add('active');
    }
});
</script>
@endpush
