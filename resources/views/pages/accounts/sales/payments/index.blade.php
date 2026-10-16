@extends('layouts.app')

@section('title', 'Payments Received - Accounts Sales')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.inv-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.inv-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.inv-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.inv-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .inv-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .inv-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
.acm-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 16px 20px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 6px rgba(0,0,0,.02);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.acm-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,.04);
    border-color: #cbd5e1;
}
.acm-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:20px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.1; }
.acm-stat-sub { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }

/* Filter Accordion */
.acc-filter-accordion { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); transition:box-shadow .2s; }
.acc-filter-accordion:hover { box-shadow:0 4px 12px rgba(0,0,0,.04); }
.acc-filter-toggle { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 20px; cursor:pointer; user-select:none; background:#fff; transition:background .15s ease; }
.acc-filter-toggle::-webkit-details-marker, .acc-filter-toggle::marker { display:none; content:""; }
.acc-filter-toggle:hover { background:#f8fafc; }
.acc-filter-accordion[open] .acc-filter-toggle { border-bottom:1px solid #e2e8f0; background:#fafbfc; }
.acc-filter-toggle-left { display:flex; align-items:center; gap:12px; min-width:0; }
.acc-filter-icon { width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:#fff7ed; color:#fe5f04; font-size:15px; flex-shrink:0; border:1px solid #ffedd5; }
.acc-filter-title { font-size:14px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.acc-filter-sub { font-size:12px; color:#64748b; margin-top:2px; }
.acc-filter-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; background:#fe5f04; color:#fff; font-size:11px; font-weight:800; letter-spacing:0.02em; }
.acc-filter-toggle-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.acc-filter-toggle-hint { font-size:12px; font-weight:700; color:#64748b; }
.acc-filter-toggle-hint::after { content:"Show Filters"; }
.acc-filter-accordion[open] .acc-filter-toggle-hint::after { content:"Hide Filters"; }
.acc-filter-chevron { width:32px; height:32px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e2e8f0; color:#64748b; background:#fff; transition:all .2s ease; font-size:13px; }
.acc-filter-accordion[open] .acc-filter-chevron { transform:rotate(180deg); color:#fe5f04; border-color:#fed7aa; background:#fff7ed; }
.acc-filter-body { padding:16px 20px; background:#fff; }

/* Single Row Filter Bar */
.inv-filter-single-row { display:flex; align-items:flex-end; gap:12px; width:100%; flex-wrap:wrap; }
.filter-col { display:flex; flex-direction:column; gap:6px; }
.filter-col-search { flex:2; min-width:200px; }
.filter-col-mode { flex:1.2; min-width:140px; }
.filter-col-tds { flex:1.1; min-width:130px; }
.filter-col-date { flex:1; min-width:125px; }
.filter-col-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; margin-bottom:0; }
.filter-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; line-height:1; }
.filter-input-wrap { position:relative; width:100%; }
.filter-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
.filter-control { width:100%; height:38px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; color:#0f172a; box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
.filter-control:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.filter-input-wrap .filter-control { padding-left:36px; }
.filter-select { cursor:pointer; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 14px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap !important; }
.acm-tbl td { padding:12px 14px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.acm-tbl tbody tr { transition:background .12s ease; }
.acm-tbl tbody tr:hover td { background:#f8fafc; }

.pay-num-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 2.5px 7px;
    border-radius: 5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
    text-decoration: none;
    white-space: nowrap !important;
    word-break: keep-all !important;
    letter-spacing: 0.2px;
    line-height: 1.35;
}
.pay-num-pill i {
    font-size: 10.5px;
    color: #64748b;
}

.pay-inv-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    padding: 2.5px 7px;
    border-radius: 5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 700;
    color: #c2410c;
    text-decoration: none;
    white-space: nowrap !important;
    word-break: keep-all !important;
    letter-spacing: 0.2px;
    line-height: 1.35;
    transition: all .15s ease;
}
.pay-inv-pill:hover {
    background: #ffedd5;
    border-color: #fdba74;
    color: #9a3412;
    box-shadow: 0 2px 6px rgba(194, 65, 12, .14);
}
.pay-inv-pill i {
    font-size: 10px;
}

.pay-date-cell {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    white-space: nowrap !important;
    word-break: keep-all !important;
}
.pay-date-cell i {
    font-size: 11px;
    color: #94a3b8;
}

.pay-cust-avatar {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    background: #f0fdf4;
    color: #16a34a;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #bbf7d0;
}

.mode-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2.5px 8px;
    border-radius: 5px;
    font-size: 11px;
    font-weight: 700;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    white-space: nowrap !important;
    word-break: keep-all !important;
}
.mode-badge.bank_transfer { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.mode-badge.upi { background:#faf5ff; color:#7e22ce; border-color:#e9d5ff; }
.mode-badge.cheque { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
.mode-badge.cash { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
.mode-badge.card { background:#ecfeff; color:#0e7490; border-color:#a5f3fc; }

.tds-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10.5px;
    font-weight: 700;
    color: #c2410c;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    padding: 1.5px 6.5px;
    border-radius: 4px;
    white-space: nowrap !important;
    word-break: keep-all !important;
    font-variant-numeric: tabular-nums;
}

.acm-btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 29px;
    height: 29px;
    border-radius: 7px;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    font-size: 12px;
    text-decoration: none;
    cursor: pointer;
    transition: all .15s ease;
}
.acm-btn-icon:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #94a3b8;
    transform: translateY(-1px);
}
.acm-btn-icon.btn-icon-danger {
    color: #dc2626;
    border-color: #fecaca;
    background: #fff;
}
.acm-btn-icon.btn-icon-danger:hover {
    background: #fee2e2;
    color: #b91c1c;
    border-color: #fca5a5;
}

.attach-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #2563eb;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    text-decoration: none;
    transition: all .15s ease;
}
.attach-pill:hover {
    background: #dbeafe;
    color: #1d4ed8;
}
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-wallet2" style="color:#16a34a;"></i>
                <span>Sales Payments Received</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.invoices.index') }}">Sales</a>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Payments</span>
            </div>
        </div>
        <div class="inv-actions">
            <a href="{{ route('accounts.sales.invoices.index') }}" class="acm-btn acm-btn-outline" title="View Invoices List">
                <i class="bi bi-receipt"></i> All Invoices
            </a>
            <a href="{{ route('accounts.sales.invoices.create') }}" class="acm-btn acm-btn-primary">
                <i class="bi bi-plus-lg"></i> New Invoice
            </a>
        </div>
    </div>

    <div class="inv-body">

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ✕ {{ session('error') }}
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="acm-stats-grid">
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="acm-stat-label">Net Received (Bank/Cash)</div>
                <div class="acm-stat-val" style="color:#16a34a;">₹{{ number_format($stats['total_received'], 2) }}</div>
                <div class="acm-stat-sub">Directly Collected</div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fff7ed; color:#c2410c;">
                <i class="bi bi-tag-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">TDS Deductions</div>
                <div class="acm-stat-val" style="color:#c2410c;">₹{{ number_format($stats['total_tds'], 2) }}</div>
                <div class="acm-stat-sub">{{ $stats['tds_count'] }} Payments with TDS</div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-check2-all"></i>
            </div>
            <div>
                <div class="acm-stat-label">Total Settled</div>
                <div class="acm-stat-val" style="color:#2563eb;">₹{{ number_format($stats['total_settled'], 2) }}</div>
                <div class="acm-stat-sub">Received + TDS Combined</div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#faf5ff; color:#7e22ce;">
                <i class="bi bi-receipt-cutoff"></i>
            </div>
            <div>
                <div class="acm-stat-label">Receipts Count</div>
                <div class="acm-stat-val" style="color:#7e22ce;">{{ number_format($stats['total_count']) }}</div>
                <div class="acm-stat-sub">Payment Transactions</div>
            </div>
        </div>
    </div>

    @php
        $hasActiveFilters = request()->filled('search')
            || request()->filled('payment_mode')
            || request()->filled('has_tds')
            || request()->filled('from_date')
            || request()->filled('to_date');
        $activeFilterCount = collect([
            request('search'),
            request('payment_mode'),
            request('has_tds'),
            request('from_date'),
            request('to_date')
        ])->filter(fn($v) => filled($v))->count();
    @endphp

    {{-- Filter Accordion --}}
    <details class="acc-filter-accordion" id="paymentsFilterAccordion" {{ $hasActiveFilters ? 'open' : '' }}>
        <summary class="acc-filter-toggle">
            <div class="acc-filter-toggle-left">
                <div class="acc-filter-icon">
                    <i class="bi bi-funnel-fill"></i>
                </div>
                <div>
                    <div class="acc-filter-title">
                        <span>Filter Payments</span>
                        @if($activeFilterCount > 0)
                            <span class="acc-filter-badge">{{ $activeFilterCount }} Active</span>
                        @endif
                    </div>
                    <div class="acc-filter-sub">Filter by receipt #, invoice #, customer, mode, TDS, or date range.</div>
                </div>
            </div>
            <div class="acc-filter-toggle-right">
                <span class="acc-filter-toggle-hint"></span>
                <span class="acc-filter-chevron">
                    <i class="bi bi-chevron-down"></i>
                </span>
            </div>
        </summary>
        <div class="acc-filter-body">
            <form method="GET" action="{{ route('accounts.sales.payments.index') }}" class="inv-filter-single-row">
                <div class="filter-col filter-col-search">
                    <label class="filter-label">Search</label>
                    <div class="filter-input-wrap">
                        <i class="bi bi-search filter-icon"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Receipt #, Invoice #, Customer, Bank..." class="filter-control">
                    </div>
                </div>

                <div class="filter-col filter-col-mode">
                    <label class="filter-label">Payment Mode</label>
                    <select name="payment_mode" class="filter-control filter-select">
                        <option value="">All Modes</option>
                        @foreach(\App\Models\InvoicePayment::PAYMENT_MODES as $k => $lbl)
                            <option value="{{ $k }}" {{ request('payment_mode') == $k ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-col filter-col-tds">
                    <label class="filter-label">TDS Status</label>
                    <select name="has_tds" class="filter-control filter-select">
                        <option value="">All Payments</option>
                        <option value="1" {{ request('has_tds') === '1' ? 'selected' : '' }}>With TDS Deduction</option>
                        <option value="0" {{ request('has_tds') === '0' ? 'selected' : '' }}>Without TDS</option>
                    </select>
                </div>

                <div class="filter-col filter-col-date">
                    <label class="filter-label">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="filter-control">
                </div>

                <div class="filter-col filter-col-date">
                    <label class="filter-label">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="filter-control">
                </div>

                <div class="filter-col filter-col-actions">
                    <button type="submit" class="acm-btn acm-btn-primary" style="height:38px; padding:0 18px; white-space:nowrap;">
                        <i class="bi bi-funnel"></i> Apply
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ route('accounts.sales.payments.index') }}" class="acm-btn acm-btn-outline" style="height:38px; padding:0 12px; color:#dc2626; border-color:#fca5a5; white-space:nowrap;" title="Reset filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </details>

    {{-- Payments Table Card --}}
    <div class="acm-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div style="font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-list-check" style="color:#16a34a;"></i>
                <span>Payment Records ({{ $payments->total() }})</span>
            </div>
            <div style="font-size:12px; color:#64748b;">
                Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} entries
            </div>
        </div>

        @if($payments->isEmpty())
            <div style="text-align:center; padding:50px 20px; color:#64748b;">
                <div style="width:54px; height:54px; border-radius:50%; background:#f1f5f9; color:#94a3b8; display:flex; align-items:center; justify-content:center; margin:0 auto 12px auto; font-size:24px;">
                    <i class="bi bi-inbox"></i>
                </div>
                <div style="font-size:16px; font-weight:800; color:#0f172a; margin-bottom:4px;">No Payment Records Found</div>
                <div style="font-size:13px; max-width:400px; margin:0 auto 16px auto;">
                    {{ $hasActiveFilters ? 'No payments match your current filter criteria. Try clearing some filters.' : 'No customer invoice payments have been recorded yet.' }}
                </div>
                @if($hasActiveFilters)
                    <a href="{{ route('accounts.sales.payments.index') }}" class="acm-btn acm-btn-outline">
                        <i class="bi bi-arrow-clockwise"></i> Clear Filters
                    </a>
                @else
                    <a href="{{ route('accounts.sales.invoices.index') }}" class="acm-btn acm-btn-primary">
                        <i class="bi bi-receipt"></i> Go to Sales Invoices
                    </a>
                @endif
            </div>
        @else
            <div style="overflow-x:auto; min-height:220px; padding-bottom:10px;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th style="width:130px; white-space:nowrap;">Receipt #</th>
                            <th style="width:130px; white-space:nowrap;">Invoice #</th>
                            <th style="min-width:180px;">Customer / Company</th>
                            <th style="width:120px; white-space:nowrap;">Date</th>
                            <th style="width:130px; white-space:nowrap;">Payment Mode</th>
                            <th style="width:130px; text-align:right; white-space:nowrap;">Received (₹)</th>
                            <th style="width:110px; text-align:right; white-space:nowrap;">TDS (₹)</th>
                            <th style="width:140px; white-space:nowrap;">Recorded By</th>
                            <th style="width:85px; text-align:right; white-space:nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $p)
                        <tr>
                            <td style="white-space:nowrap;">
                                <span class="pay-num-pill" title="Receipt #{{ $p->payment_number }}">
                                    <i class="bi bi-receipt-cutoff"></i>
                                    <span>{{ $p->payment_number }}</span>
                                </span>
                            </td>
                            <td style="white-space:nowrap;">
                                @if($p->invoice)
                                    <a href="{{ route('accounts.sales.invoices.show', $p->invoice_id) }}" class="pay-inv-pill" title="View Invoice #{{ $p->invoice->invoice_number }}">
                                        <i class="bi bi-receipt"></i>
                                        <span>#{{ $p->invoice->invoice_number }}</span>
                                    </a>
                                @else
                                    <span style="color:#94a3b8; font-size:12px;">—</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:9px;">
                                    <div class="pay-cust-avatar">
                                        {{ strtoupper(substr($p->customer?->customer_name ?: ($p->invoice?->customer_name ?: 'C'), 0, 1)) }}
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="font-weight:700; color:#0f172a; font-size:12.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:240px;" title="{{ $p->customer?->customer_name ?: ($p->invoice?->customer_name ?: 'Customer') }}">
                                            {{ $p->customer?->customer_name ?: ($p->invoice?->customer_name ?: 'Customer') }}
                                        </div>
                                        @if($p->customer?->company_name || $p->invoice?->company_name)
                                            <div style="font-size:11px; color:#64748b; margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:240px;" title="{{ $p->customer?->company_name ?: $p->invoice?->company_name }}">
                                                <i class="bi bi-building" style="font-size:10px;"></i> {{ $p->customer?->company_name ?: $p->invoice?->company_name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="white-space:nowrap;">
                                <div class="pay-date-cell">
                                    <i class="bi bi-calendar3"></i>
                                    <span>{{ $p->payment_date ? $p->payment_date->format('d M Y') : '—' }}</span>
                                </div>
                            </td>
                            <td style="white-space:nowrap;">
                                <span class="mode-badge {{ $p->payment_mode }}">
                                    @if($p->payment_mode === 'bank_transfer')
                                        <i class="bi bi-bank"></i>
                                    @elseif($p->payment_mode === 'upi')
                                        <i class="bi bi-qr-code"></i>
                                    @elseif($p->payment_mode === 'cash')
                                        <i class="bi bi-cash"></i>
                                    @elseif($p->payment_mode === 'cheque')
                                        <i class="bi bi-card-text"></i>
                                    @elseif($p->payment_mode === 'card')
                                        <i class="bi bi-credit-card"></i>
                                    @else
                                        <i class="bi bi-check-circle"></i>
                                    @endif
                                    <span>{{ $p->payment_mode_label }}</span>
                                </span>
                                @if($p->reference_number || $p->bank_name)
                                    <div style="font-size:10px; color:#64748b; margin-top:2px; font-family:ui-monospace, monospace; white-space:nowrap;">
                                        @if($p->bank_name)<span style="font-weight:600;">{{ \Illuminate\Support\Str::limit($p->bank_name, 14) }}</span>@endif
                                        @if($p->bank_name && $p->reference_number) • @endif
                                        @if($p->reference_number)<span title="Ref: {{ $p->reference_number }}">#{{ \Illuminate\Support\Str::limit($p->reference_number, 12) }}</span>@endif
                                    </div>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:800; color:#16a34a; white-space:nowrap; font-size:13px; font-variant-numeric:tabular-nums;">
                                ₹{{ number_format((float) $p->amount_received, 2) }}
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                @if((float) $p->tds_amount > 0)
                                    <span class="tds-pill" title="TDS Deducted ({{ $p->tds_percentage }}%)">
                                        <i class="bi bi-tag-fill" style="font-size:9px;"></i>
                                        <span>-₹{{ number_format((float) $p->tds_amount, 2) }}</span>
                                        @if((float) $p->tds_percentage > 0)
                                            <small style="opacity:0.85; font-size:9.5px;">({{ $p->tds_percentage }}%)</small>
                                        @endif
                                    </span>
                                @else
                                    <span style="color:#cbd5e1; font-size:12px;">—</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                <div style="font-size:11.5px; font-weight:700; color:#0f172a;">
                                    {{ $p->recordedBy?->name ?: 'System' }}
                                </div>
                                <div style="font-size:10px; color:#94a3b8; margin-top:1px;">
                                    {{ $p->created_at?->format('d M Y, h:i A') }}
                                </div>
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:5px;">
                                    @if($p->attachment_path)
                                        <a href="{{ asset($p->attachment_path) }}" target="_blank" class="acm-btn-icon" style="color:#2563eb; border-color:#bfdbfe; background:#eff6ff;" title="View Attachment: {{ $p->attachment_name ?: 'File' }}">
                                            <i class="bi bi-paperclip"></i>
                                        </a>
                                    @endif
                                    @if($p->invoice_id)
                                        <a href="{{ route('accounts.sales.invoices.show', $p->invoice_id) }}" class="acm-btn-icon" title="View Invoice">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('accounts.sales.payments.destroy', $p->id) }}" onsubmit="return confirm('Are you sure you want to delete payment receipt #{{ $p->payment_number }} (₹{{ number_format((float)$p->total_settled_amount, 2) }})? The invoice balance will be updated automatically.')" style="margin:0; display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn-icon btn-icon-danger" title="Delete Payment Record">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($payments->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #e2e8f0;">
                    {{ $payments->links() }}
                </div>
            @endif
        @endif
    </div>{{-- /acm-card --}}

    </div>{{-- /inv-body --}}
</div>{{-- /inv-page --}}
@endsection
