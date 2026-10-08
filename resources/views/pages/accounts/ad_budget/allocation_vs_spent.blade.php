@extends('layouts.app')

@section('title', 'Ad Budget Ledger Statement')

@push('styles')
<style>
.adb-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.adb-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; }
.adb-sub { font-size:13px; color:#64748b; }

.adb-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:14px; }
.adb-stat-card { background:#fff; border-radius:14px; padding:18px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.adb-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.adb-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.adb-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.adb-nav-tabs { display:flex; align-items:center; gap:4px; padding:12px 20px 0 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; overflow-x:auto; }
.adb-tab-item { display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px 10px 0 0; font-size:13px; font-weight:700; color:#64748b; text-decoration:none; border:1px solid transparent; border-bottom:none; background:transparent; transition:all .15s ease; white-space:nowrap; }
.adb-tab-item:hover { color:#fe5f04; background:#ffffff; }
.adb-tab-item.active { color:#fe5f04; background:#ffffff; border-color:#e2e8f0; border-bottom:2px solid #fe5f04; margin-bottom:-1px; }

.adb-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:nowrap; overflow-x:auto; white-space:nowrap; }
.adb-search-wrap { position:relative; flex:1; min-width:180px; max-width:280px; flex-shrink:0; }
.adb-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.adb-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.adb-select { padding:8px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.adb-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.adb-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.adb-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.adb-btn-secondary { background:#f1f5f9; color:#334155; }
.adb-btn-secondary:hover { background:#e2e8f0; }

.adb-tbl { width:100%; border-collapse:collapse; }
.adb-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; }
.adb-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

.txn-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; }
.txn-badge-alloc { background:#fff7ed; color:#c2410c; border:1px solid #ffedd5; }
.txn-badge-spend { background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; }

.balance-pill { display:inline-block; padding:4px 10px; border-radius:8px; font-weight:800; font-size:13px; }
.balance-positive { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.balance-negative { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
</style>
@endpush

@section('content')
<div class="adb-page">

    {{-- Topbar --}}
    <div class="adb-topbar">
        <div>
            <div class="adb-title">Ad Budget Ledger Statement</div>
            <div class="adb-sub">Passbook view of approved budget allocations (Credit) and ad spend entries (Debit) with running balance</div>
        </div>
        <button type="button" class="adb-btn adb-btn-secondary" onclick="window.print()">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print Passbook / Statement
        </button>
    </div>

    {{-- Stats Cards --}}
    <div class="adb-stats-grid">
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#fff7ed; color:#ea580c;">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Allocated (Credit +)</div>
                <div class="adb-stat-val">₹{{ number_format($totalAllocatedOverall, 2) }}</div>
            </div>
        </div>
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-credit-card-2-front"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Ad Spent (Debit -)</div>
                <div class="adb-stat-val">₹{{ number_format($totalSpentOverall, 2) }}</div>
            </div>
        </div>
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:{{ $accountCurrentBalance >= 0 ? '#f0fdf4' : '#fef2f2' }}; color:{{ $accountCurrentBalance >= 0 ? '#16a34a' : '#dc2626' }};">
                <i class="bi bi-bank"></i>
            </div>
            <div>
                <div class="adb-stat-label">
                    {{ $selectedAccount ? $selectedAccount->account_name . ' Balance' : 'Current Available Balance' }}
                </div>
                <div class="adb-stat-val" style="color:{{ $accountCurrentBalance >= 0 ? '#16a34a' : '#dc2626' }};">
                    ₹{{ number_format($accountCurrentBalance, 2) }}
                </div>
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
        <form method="GET" action="{{ route('accounts.ad-budget.allocation-vs-spent') }}">
            <div class="adb-filter-bar">
                <div class="adb-search-wrap">
                    <i class="bi bi-search adb-search-ico"></i>
                    <input type="text" name="search" class="adb-search-input" placeholder="Search particulars, account, user…" value="{{ request('search') }}">
                </div>

                <select name="ad_account_id" class="adb-select" style="min-width:180px;" onchange="this.form.submit()">
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

                <button type="submit" class="adb-btn adb-btn-secondary" style="padding:8px 14px; font-size:12px;">Apply Filter</button>
                @if(request()->hasAny(['search','ad_account_id','date_from','date_to']))
                    <a href="{{ route('accounts.ad-budget.allocation-vs-spent') }}" class="adb-btn adb-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1;">Reset</a>
                @endif
            </div>
        </form>

        {{-- Ledger Statement Table --}}
        @if($rows->isEmpty())
            <div style="text-align:center; padding:50px; color:#94a3b8;">
                <i class="bi bi-journal-text" style="font-size:40px; display:block; margin-bottom:10px;"></i>
                No Allocation or Spend ledger records found for the selected criteria.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="adb-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Ad Account Name</th>
                            <th>Particulars / Description</th>
                            <th style="text-align:right;">Allocated</th>
                            <th style="text-align:right;">Spent</th>
                            <th style="text-align:right;">Running Balance</th>
                            <th>User / Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $index => $row)
                        @php
                            $dateObj        = \Carbon\Carbon::parse($row['date']);
                            $allocated      = $row['allocated_amount'];
                            $spent          = $row['spent_amount'];
                            $runningBalance = $row['running_balance'];
                            $isAllocation   = $row['type'] === 'allocation';
                        @endphp
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">#{{ $rows->count() - $index }}</td>
                            <td>
                                <strong>{{ $dateObj->format('d M Y') }}</strong>
                                <div style="font-size:11px; color:#94a3b8;">{{ $dateObj->format('l') }}</div>
                            </td>
                            <td>
                                <strong>{{ $row['ad_account']?->account_name ?? '—' }}</strong>
                                <div style="font-size:11px; color:#64748b;">
                                    {{ $row['ad_account']?->platform }} {{ $row['ad_account']?->account_id ? '('.$row['ad_account']->account_id.')' : '' }}
                                </div>
                            </td>
                            <td>
                                <div style="margin-bottom:3px;">
                                    @if($isAllocation)
                                        <span class="txn-badge txn-badge-alloc">+ Allocation</span>
                                    @else
                                        <span class="txn-badge txn-badge-spend">- Spend</span>
                                    @endif
                                </div>
                                <span style="font-size:13px; color:#334155; font-weight:600;">{{ $row['description'] }}</span>
                            </td>
                            <td style="text-align:right; font-weight:800; color:{{ $allocated > 0 ? '#ea580c' : '#cbd5e1' }};">
                                {{ $allocated > 0 ? '+₹' . number_format($allocated, 2) : '—' }}
                            </td>
                            <td style="text-align:right; font-weight:800; color:{{ $spent > 0 ? '#2563eb' : '#cbd5e1' }};">
                                {{ $spent > 0 ? '-₹' . number_format($spent, 2) : '—' }}
                            </td>
                            <td style="text-align:right;">
                                <span class="balance-pill {{ $runningBalance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                    ₹{{ number_format($runningBalance, 2) }}
                                </span>
                            </td>
                            <td>
                                <div style="font-size:12px; font-weight:700; color:#334155;">{{ $row['user_name'] }}</div>
                                <div style="font-size:11px; color:#94a3b8;">Ref #{{ $row['ref_id'] }}</div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection

