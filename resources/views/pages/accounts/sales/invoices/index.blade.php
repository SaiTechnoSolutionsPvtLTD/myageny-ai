@extends('layouts.app')

@section('title', 'Invoices - Accounts Sales')

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
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
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
.inv-filter-single-row { display:flex; align-items:flex-end; gap:12px; width:100%; }
.filter-col { display:flex; flex-direction:column; gap:6px; }
.filter-col-search { flex:2.2; min-width:220px; }
.filter-col-status { flex:1.2; min-width:145px; }
.filter-col-date { flex:1; min-width:135px; }
.filter-col-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; margin-bottom:0; }
.filter-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; line-height:1; }
.filter-input-wrap { position:relative; width:100%; }
.filter-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
.filter-control { width:100%; height:38px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; color:#0f172a; box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
.filter-control:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.filter-input-wrap .filter-control { padding-left:36px; }
.filter-select { cursor:pointer; }

@media (max-width: 991px) {
    .inv-filter-single-row { flex-wrap:wrap; }
    .filter-col-search { flex:1 1 100%; }
    .filter-col-status, .filter-col-date { flex:1 1 calc(50% - 6px); }
    .filter-col-actions { width:100%; justify-content:flex-end; }
}

.acm-tbl { width:100%; border-collapse:separate; border-spacing:0; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#475569; padding:13px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.acm-tbl tbody tr { transition:background-color .15s ease; }
.acm-tbl tbody tr:hover { background-color:#f8fafc; }
.acm-tbl tbody tr:last-child td { border-bottom:none; }

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
.status-paid { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-partially-paid { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.status-sent { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
.status-created { background:#ede9fe; color:#6d28d9; border:1px solid #ddd6fe; }
.status-draft { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.status-overdue { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.status-cancelled { background:#f1f5f9; color:#94a3b8; border:1px solid #cbd5e1; text-decoration:line-through; }

.inv-code {
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:2px 7px;
    background:#fff7ed;
    border:1px solid #fed7aa;
    border-radius:5px;
    font-family:'JetBrains Mono', Consolas, monospace;
    font-weight:700;
    color:#c2410c;
    text-decoration:none;
    font-size:11.5px;
    white-space:nowrap;
    transition:all .15s ease;
}
.inv-code i {
    font-size:10px;
}
.inv-code:hover {
    background:#fe5f04;
    border-color:#ea580c;
    color:#fff;
    text-decoration:none;
    box-shadow:0 2px 6px rgba(254,95,4,.25);
}
.inv-code:hover i {
    color:#fff !important;
}

/* Actions Dropdown */
.inv-actions { position:relative; display:inline-flex; justify-content:flex-end; }
.inv-action-trigger {
    display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:8px;
    font-size:12px; font-weight:700; color:#334155; background:#fff; border:1px solid #cbd5e1;
    cursor:pointer; transition:all .15s ease; font-family:inherit;
}
.inv-action-trigger:hover,
.inv-actions.open .inv-action-trigger {
    border-color:#fe5f04; color:#fe5f04; background:#fff7ed; box-shadow:0 2px 8px rgba(254,95,4,.15);
}
.inv-actions.open .inv-action-trigger .bi-chevron-down {
    transform:rotate(180deg);
}
.inv-action-menu {
    position:absolute; top:calc(100% + 6px); right:0; min-width:165px; padding:6px; border-radius:10px;
    border:1px solid #e2e8f0; background:#fff; box-shadow:0 12px 30px rgba(15,23,42,.14); z-index:1000; display:none;
}
.inv-actions.open .inv-action-menu {
    display:block; animation:invActionIn .15s cubic-bezier(.16,1,.3,1);
}
@keyframes invActionIn {
    from { opacity:0; transform:translateY(-4px); }
    to { opacity:1; transform:translateY(0); }
}
.inv-action-item {
    width:100%; display:flex; align-items:center; gap:9px; padding:8px 12px; border:none; background:transparent;
    border-radius:6px; font-size:12.5px; font-weight:600; color:#334155; text-decoration:none; cursor:pointer; font-family:inherit; text-align:left; box-sizing:border-box; transition:background .15s, color .15s;
}
.inv-action-item:hover { background:#f8fafc; color:#0f172a; }
.inv-action-item i { font-size:14px; width:16px; text-align:center; }
.inv-action-item.view:hover { background:#eff6ff; color:#2563eb; }
.inv-action-item.payment:hover { background:#f0fdf4; color:#16a34a; }
.inv-action-item.edit:hover { background:#fff7ed; color:#ea580c; }
.inv-action-item.print:hover { background:#f0fdf4; color:#16a34a; }
.inv-action-item.delete { color:#dc2626; }
.inv-action-divider { height:1px; background:#f1f5f9; margin:4px 0; }
@keyframes acmSpin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.acm-spin { display:inline-block; animation:acmSpin .8s linear infinite; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-receipt" style="color:#fe5f04;"></i>
                <span>Sales Invoices</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <span>Sales</span>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Invoices</span>
            </div>
        </div>
        <div class="inv-actions">
            @can('accounts_master.view')
            <a href="{{ route('accounts.master.invoice-settings.index') }}" class="acm-btn acm-btn-outline" title="Configure Invoice Format & Company Details">
                <i class="bi bi-gear"></i> Invoice Settings
            </a>
            @endcan
            @can('accounts_invoices.create')
            <a href="{{ route('accounts.sales.invoices.create') }}" class="acm-btn acm-btn-primary">
                <i class="bi bi-plus-lg"></i> New Invoice
            </a>
            @endcan
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
            <div class="acm-stat-icon" style="background:#fff7ed; color:#ea580c;">
                <i class="bi bi-receipt-cutoff"></i>
            </div>
            <div>
                <div class="acm-stat-label">Total Invoiced</div>
                <div class="acm-stat-val">₹{{ number_format($stats['total_amount'], 2) }}</div>
                <div class="acm-stat-sub">{{ $stats['total_count'] }} Invoices Total</div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <div class="acm-stat-label">Paid / Received</div>
                <div class="acm-stat-val" style="color:#16a34a;">₹{{ number_format($stats['paid_amount'], 2) }}</div>
                <div class="acm-stat-sub">
                    {{ $stats['paid_count'] }} Fully Paid
                    @if(!empty($stats['total_tds']) && $stats['total_tds'] > 0)
                        • <span style="color:#c2410c; font-weight:700;" title="Total TDS Deducted Across Invoices">TDS: ₹{{ number_format($stats['total_tds'], 2) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fef2f2; color:#dc2626;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="acm-stat-label">Balance Due</div>
                <div class="acm-stat-val" style="color:#dc2626;">₹{{ number_format($stats['balance_due'], 2) }}</div>
                <div class="acm-stat-sub">{{ $stats['partial_count'] }} Partially Paid</div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div>
                <div class="acm-stat-label">Draft / Open</div>
                <div class="acm-stat-val">{{ $stats['draft_count'] }}</div>
                <div class="acm-stat-sub">{{ $stats['overdue_count'] }} Overdue</div>
            </div>
        </div>
    </div>

    @php
        $filterKeys = ['search', 'status', 'from_date', 'to_date'];
        $activeFilterCount = collect($filterKeys)->filter(fn($k) => request()->filled($k))->count();
        $hasActiveFilters = $activeFilterCount > 0;
    @endphp

    {{-- Filter Accordion --}}
    <details class="acc-filter-accordion" id="invoiceFiltersAccordion" open>
        <summary class="acc-filter-toggle">
            <div class="acc-filter-toggle-left">
                <span class="acc-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                <div>
                    <div class="acc-filter-title">
                        <span>Filter Invoices</span>
                        @if($hasActiveFilters)
                            <span class="acc-filter-badge">{{ $activeFilterCount }} Active</span>
                        @endif
                    </div>
                    <div class="acc-filter-sub">Filter by invoice #, customer name, company, phone, status, or date range</div>
                </div>
            </div>
            <div class="acc-filter-toggle-right">
                <span class="acc-filter-toggle-hint"></span>
                <span class="acc-filter-chevron"><i class="bi bi-chevron-down"></i></span>
            </div>
        </summary>
        <div class="acc-filter-body">
            <form method="GET" action="{{ route('accounts.sales.invoices.index') }}" class="inv-filter-single-row">
                {{-- Search --}}
                <div class="filter-col filter-col-search">
                    <label class="filter-label">Search</label>
                    <div class="filter-input-wrap">
                        <i class="bi bi-search filter-icon"></i>
                        <input type="text" name="search" class="filter-control"
                               placeholder="Invoice #, customer, company, phone, email..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Status --}}
                <div class="filter-col filter-col-status">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-control filter-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>Created</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                {{-- From Date --}}
                <div class="filter-col filter-col-date">
                    <label class="filter-label">From Date</label>
                    <input type="date" name="from_date" class="filter-control" value="{{ request('from_date') }}" onchange="this.form.submit()">
                </div>

                {{-- To Date --}}
                <div class="filter-col filter-col-date">
                    <label class="filter-label">To Date</label>
                    <input type="date" name="to_date" class="filter-control" value="{{ request('to_date') }}" onchange="this.form.submit()">
                </div>

                {{-- Actions --}}
                <div class="filter-col-actions">
                    <button type="submit" class="acm-btn acm-btn-primary" style="height:38px; padding:0 16px; white-space:nowrap;">
                        <i class="bi bi-funnel"></i> <span>Filter</span>
                    </button>

                    @if($hasActiveFilters)
                        <a href="{{ route('accounts.sales.invoices.index') }}" class="acm-btn acm-btn-outline" style="height:38px; padding:0 12px; color:#dc2626; border-color:#fca5a5; white-space:nowrap;" title="Reset filters">
                            <i class="bi bi-x-circle"></i> <span>Clear</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </details>

    {{-- Invoices Table Card --}}
    <div class="acm-card">

        {{-- Table --}}
        @if($invoices->isEmpty())
            <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <i class="bi bi-receipt" style="font-size:48px; display:block; margin-bottom:12px; color:#cbd5e1;"></i>
                <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Invoices Found</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:20px;">Get started by issuing your first invoice to a client.</div>
                <a href="{{ route('accounts.sales.invoices.create') }}" class="acm-btn acm-btn-primary">
                    <i class="bi bi-plus-lg"></i> Create New Invoice
                </a>
            </div>
        @else
            <div style="overflow-x:auto; min-height:220px; padding-bottom:30px;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th style="white-space:nowrap;">Invoice #</th>
                            <th style="min-width:180px;">Customer & Company</th>
                            <th style="white-space:nowrap;">Date</th>
                            <th style="white-space:nowrap;">Due Date</th>
                            <th style="text-align:right; white-space:nowrap;">Amount (₹)</th>
                            <th style="text-align:right; white-space:nowrap;">Paid (₹)</th>
                            <th style="text-align:right; white-space:nowrap;">Balance Due (₹)</th>
                            <th style="text-align:center; white-space:nowrap;">Status</th>
                            <th style="text-align:right; white-space:nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                        <tr>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('accounts.sales.invoices.show', $inv->id) }}" class="inv-code" title="View Invoice {{ $inv->invoice_number }}">
                                    <i class="bi bi-receipt" style="color:#ea580c; font-size:10.5px;"></i>
                                    <span>{{ $inv->invoice_number }}</span>
                                </a>
                                @if($inv->salesPerson)
                                    <div style="font-size:10.5px; color:#64748b; margin-top:2px; white-space:nowrap; display:flex; align-items:center; gap:3px;">
                                        <i class="bi bi-person" style="font-size:10.5px; color:#94a3b8;"></i>
                                        <span>{{ $inv->salesPerson->name }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $displayCustName = $inv->customer_name ?: ($inv->customer?->customer_name ?: ($inv->customer?->company_name ?: ($inv->company_name ?: '—')));
                                    $displayCompName = ($inv->company_name && $inv->company_name !== $displayCustName) ? $inv->company_name : ($inv->customer?->company_name && $inv->customer?->company_name !== $displayCustName ? $inv->customer?->company_name : null);
                                @endphp
                                <div style="font-size:12px; font-weight:600; color:#0f172a; line-height:1.3;">{{ $displayCustName }}</div>
                                @if($displayCompName)
                                    <div style="font-size:10.5px; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:4px;">
                                        <i class="bi bi-building" style="font-size:9.5px; color:#94a3b8;"></i>
                                        <span>{{ $displayCompName }}</span>
                                    </div>
                                @endif
                                @if($inv->lead)
                                    <div style="margin-top:2px;">
                                        <a href="{{ route('leads.show', $inv->lead_id) }}" target="_blank" style="display:inline-flex; align-items:center; gap:3px; font-size:10.5px; font-weight:700; color:#2563eb; text-decoration:none;" title="Linked Lead: {{ $inv->lead->contact_name ?: $inv->lead->company_name }}">
                                            <i class="bi bi-person-check-fill" style="font-size:10px;"></i>
                                            <span>Lead #{{ $inv->lead->id }}</span>
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                <div style="font-size:12.5px; font-weight:600; color:#334155; white-space:nowrap; display:inline-flex; align-items:center; gap:6px;">
                                    <i class="bi bi-calendar3" style="color:#94a3b8; font-size:11px;"></i>
                                    <span>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</span>
                                </div>
                            </td>
                            <td style="white-space:nowrap;">
                                @if($inv->due_date)
                                    @php
                                        $isOverdue = \Carbon\Carbon::parse($inv->due_date)->isPast() && $inv->balance_due > 0;
                                    @endphp
                                    <div style="font-size:12.5px; font-weight:600; color:{{ $isOverdue ? '#dc2626' : '#334155' }}; white-space:nowrap; display:inline-flex; align-items:center; gap:6px;">
                                        <i class="bi {{ $isOverdue ? 'bi-exclamation-circle-fill' : 'bi-calendar-event' }}" style="color:{{ $isOverdue ? '#dc2626' : '#94a3b8' }}; font-size:11px;"></i>
                                        <span>{{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}</span>
                                        @if($isOverdue)
                                            <span style="font-size:9.5px; font-weight:800; color:#dc2626; background:#fee2e2; border:1px solid #fecaca; padding:1px 6px; border-radius:4px; line-height:1.2; text-transform:uppercase; letter-spacing:0.02em;">OVERDUE</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:#94a3b8; font-weight:500;">—</span>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:800; color:#0f172a; white-space:nowrap;">
                                ₹{{ number_format((float) $inv->total_amount, 2) }}
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <div style="font-weight:700; color:#16a34a; font-size:13px; white-space:nowrap;">
                                    ₹{{ number_format((float) $inv->paid_amount, 2) }}
                                </div>
                                @if((float) ($inv->total_tds ?? 0) > 0)
                                    <div style="margin-top:3px; white-space:nowrap;">
                                        <span style="display:inline-flex; align-items:center; gap:3px; font-size:10.5px; font-weight:800; color:#c2410c; background:#fff7ed; border:1px solid #fed7aa; padding:1px 6px; border-radius:4px; white-space:nowrap;" title="TDS Deducted: ₹{{ number_format((float) $inv->total_tds, 2) }} | Total Settled: ₹{{ number_format((float) $inv->paid_amount + (float) $inv->total_tds, 2) }}">
                                            <i class="bi bi-tag-fill" style="font-size:9px;"></i>
                                            <span>TDS: -₹{{ number_format((float) $inv->total_tds, 2) }}</span>
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:800; color:{{ $inv->balance_due > 0 ? '#dc2626' : '#16a34a' }}; white-space:nowrap;">
                                ₹{{ number_format((float) $inv->balance_due, 2) }}
                            </td>
                            <td style="text-align:center; white-space:nowrap;">
                                {!! $inv->status_badge !!}
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <div class="inv-actions" data-inv-actions>
                                    <button type="button" class="inv-action-trigger" onclick="toggleInvoiceActionMenu(event, this)" title="Actions for Invoice #{{ $inv->invoice_number }}">
                                        <span>Action</span>
                                        <i class="bi bi-chevron-down" style="font-size:10px; transition:transform .2s;"></i>
                                    </button>
                                    <div class="inv-action-menu">
                                        <a href="{{ route('accounts.sales.invoices.show', $inv->id) }}" class="inv-action-item view">
                                            <i class="bi bi-eye" style="color:#2563eb;"></i> <span>View</span>
                                        </a>
                                        @if($inv->balance_due > 0)
                                            <button type="button" class="inv-action-item payment" onclick="openIndexPaymentModal({{ json_encode([
                                                'id' => $inv->id,
                                                'invoice_number' => $inv->invoice_number,
                                                'customer_name' => $inv->customer_name,
                                                'subtotal' => (float) $inv->subtotal,
                                                'total_amount' => (float) $inv->total_amount,
                                                'paid_amount' => (float) $inv->paid_amount,
                                                'total_tds' => (float) ($inv->total_tds ?? 0),
                                                'balance_due' => (float) $inv->balance_due,
                                                'action_url' => route('accounts.sales.invoices.payments.store', $inv->id),
                                            ]) }})" style="width:100%; text-align:left; background:none; border:none; cursor:pointer;">
                                                <i class="bi bi-cash-stack" style="color:#16a34a;"></i> <span>Record Payment</span>
                                            </button>
                                        @endif
                                        <a href="{{ route('accounts.sales.invoices.edit', $inv->id) }}" class="inv-action-item edit">
                                            <i class="bi bi-pencil-square" style="color:#ea580c;"></i> <span>Edit</span>
                                        </a>
                                        <a href="{{ route('accounts.sales.invoices.print', $inv->id) }}" target="_blank" class="inv-action-item print">
                                            <i class="bi bi-printer" style="color:#16a34a;"></i> <span>Print</span>
                                        </a>
                                        <div class="inv-action-divider"></div>
                                        <form method="POST" action="{{ route('accounts.sales.invoices.destroy', $inv->id) }}" onsubmit="return confirmDeleteInvoice('{{ $inv->invoice_number }}', {{ (int) ($inv->payments_count ?? 0) }}, {{ (int) ($inv->credit_notes_count ?? 0) }})" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inv-action-item delete">
                                                <i class="bi bi-trash"></i> <span>Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                    {{ $invoices->links() }}
                </div>
            @endif
        @endif
    </div>{{-- /acm-card --}}

    </div>{{-- /inv-body --}}
</div>{{-- /inv-page --}}

{{-- Modal: Record Payment & TDS Deduction (Index) --}}
<div id="indexRecordPaymentModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(3px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-width:560px; width:100%; overflow:hidden; max-height:90vh; display:flex; flex-direction:column;">
        {{-- Modal Header --}}
        <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:38px; height:38px; border-radius:10px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:20px;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">Record Payment & TDS</h3>
                    <p style="font-size:12px; color:#64748b; margin:0;" id="idxModalSubtitle">Invoice #---</p>
                </div>
            </div>
            <button type="button" onclick="closeIndexPaymentModal()" style="background:none; border:none; font-size:18px; color:#94a3b8; cursor:pointer; padding:4px;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Modal Body (Scrollable) --}}
        <form method="POST" action="" enctype="multipart/form-data" id="indexRecordPaymentForm" style="display:flex; flex-direction:column; overflow-y:auto;">
            @csrf
            <div style="padding:22px; display:flex; flex-direction:column; gap:16px;">

                {{-- Summary Pills --}}
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px;">
                    <div>
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Invoice Total</div>
                        <div style="font-size:14.5px; font-weight:800; color:#0f172a;" id="idxModalTotal">₹0.00</div>
                    </div>
                    <div>
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Already Settled</div>
                        <div style="font-size:14.5px; font-weight:800; color:#16a34a;" id="idxModalSettled">₹0.00</div>
                    </div>
                    <div>
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Balance Due</div>
                        <div style="font-size:14.5px; font-weight:800; color:#dc2626;" id="idxModalBalDue">₹0.00</div>
                    </div>
                </div>

                {{-- Row 1: Payment Date & Amount Received --}}
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                            Payment Date <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff;">
                    </div>

                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <label style="font-size:12.5px; font-weight:700; color:#334155; margin:0;">
                                Amount Received (₹) <span style="color:#dc2626;">*</span>
                            </label>
                            <button type="button" onclick="fillIndexFullBalance()" style="font-size:11px; font-weight:700; color:#2563eb; background:none; border:none; cursor:pointer; padding:0;">
                                Fill Full Due
                            </button>
                        </div>
                        <input type="number" step="0.01" min="0" name="amount_received" id="idxAmountReceived" required placeholder="0.00" oninput="calculateIndexLiveSettled()" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13.5px; font-weight:700; color:#0f172a; outline:none; background:#fff;">
                    </div>
                </div>

                {{-- TDS Deduction Toggle Section --}}
                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px 14px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0; font-size:13px; font-weight:700; color:#92400e;">
                        <input type="checkbox" name="is_tds_deducted" value="1" id="idxIsTds" onchange="toggleIndexTdsSection()" style="width:16px; height:16px; accent-color:#d97706; cursor:pointer;">
                        <span>Client Deducted TDS (Tax Deducted at Source)</span>
                    </label>

                    <div id="idxTdsFields" style="display:none; margin-top:12px; padding-top:12px; border-top:1px dashed #fcd34d;">
                        <div style="font-size:11.5px; color:#78350f; margin-bottom:10px;">
                            💡 TDS is credited to your PAN in Form 26AS. Total Settled = <strong>Amount Received + TDS</strong>.
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:10px;">
                            <div>
                                <label style="display:block; font-size:11.5px; font-weight:700; color:#78350f; margin-bottom:4px;">
                                    TDS Rate (%)
                                </label>
                                <input type="number" step="0.01" min="0" max="100" name="tds_percentage" id="idxTdsPct" placeholder="e.g. 2, 10" oninput="calculateIndexTdsFromPercentage()" style="width:100%; border:1px solid #fcd34d; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; color:#78350f; outline:none; background:#fff;">
                                <div style="display:flex; gap:6px; margin-top:6px; flex-wrap:wrap;">
                                    <button type="button" onclick="setIndexTdsPreset(2)" style="font-size:10.5px; font-weight:700; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:4px; padding:2px 6px; cursor:pointer;">2% (194C)</button>
                                    <button type="button" onclick="setIndexTdsPreset(10)" style="font-size:10.5px; font-weight:700; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:4px; padding:2px 6px; cursor:pointer;">10% (194J)</button>
                                    <button type="button" onclick="setIndexTdsPreset(1)" style="font-size:10.5px; font-weight:700; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:4px; padding:2px 6px; cursor:pointer;">1%</button>
                                    <button type="button" onclick="setIndexTdsPreset(0.1)" style="font-size:10.5px; font-weight:700; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:4px; padding:2px 6px; cursor:pointer;">0.1%</button>
                                </div>
                            </div>
                            <div>
                                <label style="display:block; font-size:11.5px; font-weight:700; color:#78350f; margin-bottom:4px;">
                                    TDS Amount (₹)
                                </label>
                                <input type="number" step="0.01" min="0" name="tds_amount" id="idxTdsAmount" placeholder="0.00" oninput="calculateIndexLiveSettled()" style="width:100%; border:1px solid #fcd34d; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; color:#78350f; outline:none; background:#fff;">
                                <div style="font-size:10.5px; color:#b45309; margin-top:4px;" id="idxSubtotalHelp">Calculated on Subtotal</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live Settled Summary Banner --}}
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase;">Total Settled This Entry</div>
                        <div style="font-size:12px; color:#166534;">
                            Received: <strong id="idxPrevReceived">₹0.00</strong> + TDS: <strong id="idxPrevTds">₹0.00</strong>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:16px; font-weight:900; color:#15803d;" id="idxPrevTotalSettled">₹0.00</div>
                        <div style="font-size:11px; color:#166534;" id="idxPrevBalRemaining">Remaining Balance: ₹0.00</div>
                    </div>
                </div>

                {{-- Row 2: Payment Mode & Reference / UTR --}}
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                            Payment Mode <span style="color:#dc2626;">*</span>
                        </label>
                        <select name="payment_mode" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff;">
                            <option value="bank_transfer" selected>Bank Transfer (NEFT/RTGS/IMPS)</option>
                            <option value="upi">UPI / QR Code</option>
                            <option value="cheque">Cheque / Demand Draft</option>
                            <option value="cash">Cash</option>
                            <option value="card">Credit / Debit Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                            Reference / UTR / Cheque #
                        </label>
                        <input type="text" name="reference_number" placeholder="e.g. UTR123456789" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff;">
                    </div>
                </div>

                {{-- Row 3: Bank Name & Attachment File --}}
                @php
                    $banks = $banks ?? \App\Models\BankMaster::query()->forCompany(auth()->user()?->company_id)->active()->orderByDesc('is_primary')->orderBy('bank_name')->get();
                @endphp
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; align-items:start;">
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; min-height:20px; margin-bottom:6px;">
                            <label style="font-size:12.5px; font-weight:700; color:#334155; margin:0; line-height:1.2;">
                                Bank Name
                            </label>
                            <a href="{{ route('accounts.master.banks.index') }}" target="_blank" style="font-size:11px; font-weight:700; color:#fe5f04; text-decoration:none; display:inline-flex; align-items:center; gap:2px;">
                                <i class="bi bi-plus-circle" style="font-size:10px;"></i> Bank Master
                            </a>
                        </div>
                        <select name="bank_name" data-allow-clear="false" style="width:100%; height:40px; border:1px solid #cbd5e1; border-radius:8px; padding:0 12px; font-size:13px; color:#0f172a; outline:none; background:#fff; cursor:pointer;">
                            <option value="">-- Select Bank --</option>
                            @foreach($banks->unique('bank_name') as $b)
                                <option value="{{ $b->bank_name }}" {{ $b->is_primary ? 'selected' : '' }}>
                                    {{ $b->bank_name }}
                                </option>
                            @endforeach
                        </select>
                        @if($banks->isEmpty())
                            <div style="font-size:11px; color:#94a3b8; margin-top:4px;">
                                No bank accounts configured in Bank Master.
                            </div>
                        @endif
                    </div>

                    <div>
                        <div style="display:flex; align-items:center; min-height:20px; margin-bottom:6px;">
                            <label style="font-size:12.5px; font-weight:700; color:#334155; margin:0; line-height:1.2; white-space:nowrap;">
                                Attachment (Receipt / Challan)
                            </label>
                        </div>
                        <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" style="width:100%; height:40px; border:1px solid #cbd5e1; border-radius:8px; padding:6px 10px; font-size:12px; color:#334155; outline:none; background:#fff; box-sizing:border-box; display:flex; align-items:center;">
                    </div>
                </div>

                {{-- Notes / Remarks --}}
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                        Notes / Remarks
                    </label>
                    <textarea name="notes" rows="2" placeholder="Optional internal notes, deduction details or remarks..." style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff; resize:vertical;"></textarea>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div style="padding:14px 22px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; flex-shrink:0;">
                <button type="button" onclick="closeIndexPaymentModal()" style="padding:9px 18px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:13px; font-weight:700; color:#475569; cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" id="idxSubmitBtn" style="padding:9px 22px; border-radius:8px; border:none; background:linear-gradient(135deg, #16a34a, #15803d); color:#fff; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(22,163,74,0.3); display:inline-flex; align-items:center; gap:6px;">
                    <i class="bi bi-check-lg"></i>
                    <span>Record Payment</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterAcc = document.getElementById('invoiceFiltersAccordion');
    if (filterAcc) {
        const hasActive = {{ $hasActiveFilters ? 'true' : 'false' }};
        const saved = localStorage.getItem('inv_filter_accordion_open');
        
        if (hasActive) {
            filterAcc.open = true;
        } else if (saved !== null) {
            filterAcc.open = (saved === '1');
        }
        
        filterAcc.addEventListener('toggle', function() {
            localStorage.setItem('inv_filter_accordion_open', filterAcc.open ? '1' : '0');
        });
    }

    const idxPayForm = document.getElementById('indexRecordPaymentForm');
    if (idxPayForm) {
        idxPayForm.addEventListener('submit', function(e) {
            if (isIndexPaymentSubmitting) {
                e.preventDefault();
                return false;
            }

            if (!this.checkValidity()) {
                return;
            }

            isIndexPaymentSubmitting = true;
            const submitBtn = document.getElementById('idxSubmitBtn');
            if (submitBtn) {
                submitBtn.style.pointerEvents = 'none';
                submitBtn.style.opacity = '0.75';
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat acm-spin"></i> <span>Recording Payment...</span>';
                setTimeout(function() {
                    submitBtn.disabled = true;
                }, 10);
            }
        });
    }
});

function closeInvoiceActionMenus() {
    document.querySelectorAll('[data-inv-actions].open').forEach(menu => menu.classList.remove('open'));
}

function toggleInvoiceActionMenu(event, button) {
    event.stopPropagation();
    const wrapper = button.closest('[data-inv-actions]');
    const shouldOpen = !wrapper.classList.contains('open');
    closeInvoiceActionMenus();
    if (shouldOpen) {
        wrapper.classList.add('open');
        const menu = wrapper.querySelector('.inv-action-menu');
        if (menu) {
            const btnRect = button.getBoundingClientRect();
            const spaceBelow = window.innerHeight - btnRect.bottom;
            if (spaceBelow < 190 && btnRect.top > 190) {
                menu.style.top = 'auto';
                menu.style.bottom = 'calc(100% + 6px)';
            } else {
                menu.style.top = 'calc(100% + 6px)';
                menu.style.bottom = 'auto';
            }
        }
    }
}

document.addEventListener('click', closeInvoiceActionMenus);
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeInvoiceActionMenus();
        closeIndexPaymentModal();
    }
});

let currentIndexInvoiceData = null;
let isIndexPaymentSubmitting = false;

function resetIndexPaymentSubmitBtn() {
    isIndexPaymentSubmitting = false;
    const submitBtn = document.getElementById('idxSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.style.pointerEvents = 'auto';
        submitBtn.style.opacity = '1';
    }
}

function openIndexPaymentModal(data) {
    currentIndexInvoiceData = data;
    closeInvoiceActionMenus();
    resetIndexPaymentSubmitBtn();

    const modal = document.getElementById('indexRecordPaymentModal');
    const form = document.getElementById('indexRecordPaymentForm');
    const subtitle = document.getElementById('idxModalSubtitle');
    const totalEl = document.getElementById('idxModalTotal');
    const settledEl = document.getElementById('idxModalSettled');
    const balDueEl = document.getElementById('idxModalBalDue');
    const amtInput = document.getElementById('idxAmountReceived');
    const tdsCheck = document.getElementById('idxIsTds');
    const subtotalHelp = document.getElementById('idxSubtotalHelp');

    if (form) form.action = data.action_url;
    if (subtitle) subtitle.textContent = `Invoice #${data.invoice_number} • ${data.customer_name}`;
    if (totalEl) totalEl.textContent = '₹' + data.total_amount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (settledEl) settledEl.textContent = '₹' + (data.paid_amount + data.total_tds).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (balDueEl) balDueEl.textContent = '₹' + data.balance_due.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (subtotalHelp) subtotalHelp.textContent = 'Calculated on Subtotal (₹' + data.subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ')';

    if (tdsCheck) {
        tdsCheck.checked = false;
        toggleIndexTdsSection();
    }
    if (amtInput) {
        amtInput.max = data.balance_due;
        amtInput.value = data.balance_due;
    }

    calculateIndexLiveSettled();
    if (modal) modal.style.display = 'flex';
}

function closeIndexPaymentModal() {
    const modal = document.getElementById('indexRecordPaymentModal');
    if (modal) modal.style.display = 'none';
    currentIndexInvoiceData = null;
    resetIndexPaymentSubmitBtn();
}

function toggleIndexTdsSection() {
    const isChecked = document.getElementById('idxIsTds')?.checked;
    const tdsFields = document.getElementById('idxTdsFields');
    if (tdsFields) {
        tdsFields.style.display = isChecked ? 'block' : 'none';
    }
    if (!isChecked) {
        const tdsPct = document.getElementById('idxTdsPct');
        const tdsAmt = document.getElementById('idxTdsAmount');
        if (tdsPct) tdsPct.value = '';
        if (tdsAmt) tdsAmt.value = '';
    }
    calculateIndexLiveSettled();
}

function setIndexTdsPreset(percent) {
    const tdsPctInput = document.getElementById('idxTdsPct');
    if (tdsPctInput) {
        tdsPctInput.value = percent;
        calculateIndexTdsFromPercentage();
    }
}

function calculateIndexTdsFromPercentage() {
    if (!currentIndexInvoiceData) return;
    const pct = parseFloat(document.getElementById('idxTdsPct')?.value) || 0;
    const baseAmount = currentIndexInvoiceData.subtotal > 0 ? currentIndexInvoiceData.subtotal : currentIndexInvoiceData.balance_due;
    const tdsAmount = Math.round((baseAmount * (pct / 100)) * 100) / 100;
    const tdsAmtInput = document.getElementById('idxTdsAmount');
    if (tdsAmtInput) {
        tdsAmtInput.value = tdsAmount > 0 ? tdsAmount : '';
    }
    const netReceived = Math.max(0, Math.round((currentIndexInvoiceData.balance_due - tdsAmount) * 100) / 100);
    const amountReceivedInput = document.getElementById('idxAmountReceived');
    if (amountReceivedInput) {
        amountReceivedInput.value = netReceived;
    }
    calculateIndexLiveSettled();
}

function fillIndexFullBalance() {
    if (!currentIndexInvoiceData) return;
    const isTds = document.getElementById('idxIsTds')?.checked;
    const tdsAmt = isTds ? (parseFloat(document.getElementById('idxTdsAmount')?.value) || 0) : 0;
    const net = Math.max(0, Math.round((currentIndexInvoiceData.balance_due - tdsAmt) * 100) / 100);
    const amountReceivedInput = document.getElementById('idxAmountReceived');
    if (amountReceivedInput) {
        amountReceivedInput.value = net;
    }
    calculateIndexLiveSettled();
}

function calculateIndexLiveSettled() {
    if (!currentIndexInvoiceData) return;
    const amtReceived = parseFloat(document.getElementById('idxAmountReceived')?.value) || 0;
    const isTds = document.getElementById('idxIsTds')?.checked;
    const tdsAmt = isTds ? (parseFloat(document.getElementById('idxTdsAmount')?.value) || 0) : 0;
    const totalSettled = Math.round((amtReceived + tdsAmt) * 100) / 100;
    const remaining = Math.max(0, Math.round((currentIndexInvoiceData.balance_due - totalSettled) * 100) / 100);

    const prevReceived = document.getElementById('idxPrevReceived');
    const prevTds = document.getElementById('idxPrevTds');
    const prevTotal = document.getElementById('idxPrevTotalSettled');
    const prevRemaining = document.getElementById('idxPrevBalRemaining');
    const submitBtn = document.getElementById('idxSubmitBtn');

    if (prevReceived) prevReceived.textContent = '₹' + amtReceived.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (prevTds) prevTds.textContent = '₹' + tdsAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (prevTotal) prevTotal.textContent = '₹' + totalSettled.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (prevRemaining) {
        if (remaining <= 0) {
            prevRemaining.innerHTML = '<span style="color:#15803d; font-weight:800;">✓ Invoice Will Be Fully Settled</span>';
        } else {
            prevRemaining.textContent = 'Remaining Balance: ₹' + remaining.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
    if (submitBtn && !isIndexPaymentSubmitting) {
        submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> <span>Record Payment (₹' + totalSettled.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ')</span>';
    }
}

window.addEventListener('pageshow', function() {
    resetIndexPaymentSubmitBtn();
});

function confirmDeleteInvoice(invNumber, paymentsCount, creditNotesCount) {
    if (paymentsCount > 0 || creditNotesCount > 0) {
        let details = [];
        if (creditNotesCount > 0) {
            details.push(`${creditNotesCount} Credit Note` + (creditNotesCount > 1 ? 's' : ''));
        }
        if (paymentsCount > 0) {
            details.push(`${paymentsCount} Payment History record` + (paymentsCount > 1 ? 's' : ''));
        }
        const text = details.join(' and ');
        alert(`Cannot delete Invoice #${invNumber}.\n\nPlease delete the associated ${text} first before deleting this invoice.`);
        return false;
    }
    return confirm(`Are you sure you want to delete invoice #${invNumber}? This action cannot be undone.`);
}
</script>
@endpush
