@extends('layouts.app')

@section('title', 'Expense Subcategory Wise Report - Accounts')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
.acm-btn-excel { background:#059669; color:#fff; box-shadow:0 3px 10px rgba(5,150,105,0.25); }
.acm-btn-excel:hover { background:#047857; color:#fff; }
.acm-btn-pdf { background:#ef4444; color:#fff; box-shadow:0 3px 10px rgba(239,68,68,0.25); }
.acm-btn-pdf:hover { background:#dc2626; color:#fff; }
.acm-btn-print { background:#0284c7; color:#fff; box-shadow:0 3px 10px rgba(2,132,199,0.25); }
.acm-btn-print:hover { background:#0369a1; color:#fff; }

/* Filter Section */
.filter-card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:18px 22px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.filter-grid { display:grid; grid-template-columns: minmax(180px, 1.3fr) minmax(180px, 1.3fr) minmax(140px, 1fr) minmax(140px, 1fr) auto; gap:14px; align-items:flex-end; }
@media (max-width: 1100px) {
    .filter-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .filter-grid { grid-template-columns: 1fr; }
}
.f-label { display:block; font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px; }
.f-input { width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:13px; color:#1e293b; background:#fff; outline:none; transition:border-color .15s; }
.f-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

/* Select2 Customization */
.select2-container .select2-selection--single { height:40px !important; border:1px solid #cbd5e1 !important; border-radius:8px !important; display:flex !important; align-items:center !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height:38px !important; color:#1e293b !important; font-size:13px !important; font-weight:600 !important; padding-left:12px !important; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height:38px !important; right:8px !important; }

/* Quick Presets Pills */
.presets-wrap { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:12px; border-top:1px dashed #e2e8f0; }
.preset-pill { font-size:11.5px; font-weight:600; padding:4px 10px; border-radius:6px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; cursor:pointer; text-decoration:none; transition:all .15s; }
.preset-pill:hover { background:#fee2e2; border-color:#fca5a5; color:#b91c1c; }

/* Stat Cards */
.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:20px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.1; }
.acm-stat-sub { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; }

/* Main Card & Table */
.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.card-top { padding:18px 22px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; flex-wrap:wrap; gap:12px; }
.card-title { font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.card-sub { font-size:12px; color:#64748b; margin-top:2px; }

.tbl-wrap { width:100%; overflow-x:auto; }
.report-tbl { width:100%; border-collapse:collapse; font-size:13px; text-align:left; }
.report-tbl thead th { background:#f8fafc; color:#475569; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding:12px 16px; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.report-tbl tbody td { padding:13px 16px; border-bottom:1px solid #f1f5f9; color:#1e293b; vertical-align:middle; }
.report-tbl tbody tr.parent-row:hover td { background:#fafbfc; }
.report-tbl tfoot td { background:#f8fafc; font-weight:800; color:#0f172a; padding:14px 16px; border-top:2px solid #cbd5e1; border-bottom:2px solid #cbd5e1; }

.text-right { text-align:right !important; }
.text-center { text-align:center !important; }

/* Badges */
.badge-code { background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:700; font-family:monospace; }
.badge-category { background:#fff7ed; color:#c2410c; padding:2px 8px; border-radius:6px; font-size:11.5px; font-weight:700; border:1px solid #ffedd5; }
.badge-fixed { background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; }
.badge-var { background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; }
.progress-bar-wrap { width:100px; height:7px; background:#e2e8f0; border-radius:999px; overflow:hidden; display:inline-block; vertical-align:middle; margin-left:6px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#2563eb,#60a5fa); border-radius:999px; }

/* Accordion Drilldown Subtable */
.drilldown-row { display:none; background:#fbfcfe; }
.drilldown-row.show { display:table-row; }
.drilldown-container { padding:14px 20px 20px; background:#f8fafc; border-top:1px dashed #cbd5e1; border-bottom:1px solid #e2e8f0; }
.sub-table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; border:1px solid #e2e8f0; font-size:12px; }
.sub-table th { background:#f1f5f9; color:#475569; font-weight:700; padding:9px 12px; font-size:10.5px; text-transform:uppercase; border-bottom:1px solid #e2e8f0; }
.sub-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; color:#334155; }
.sub-table tbody tr:hover td { background:#f0f7ff; }

.btn-toggle-row { background:#fff; border:1px solid #cbd5e1; color:#475569; width:28px; height:28px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s; }
.btn-toggle-row:hover { border-color:#2563eb; color:#2563eb; }
.btn-toggle-row.open { transform:rotate(90deg); background:#eff6ff; border-color:#bfdbfe; color:#2563eb; }

@media print {
    .inv-topbar, .filter-card, .btn-toggle-row, .inv-actions { display:none !important; }
    .drilldown-row { display:table-row !important; }
    .inv-page { background:#fff; }
    .inv-body { padding:0; }
    .acm-card { border:none; box-shadow:none; }
}
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-diagram-3-fill" style="color:#2563eb;"></i>
                <span>Expense Subcategory Wise Report</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.reports.index') }}">Reports</a>
                <span>/</span>
                <span style="color:#2563eb; font-weight:600;">Expense Subcategory Wise</span>
            </div>
        </div>

        <div class="inv-actions">
            <a href="{{ route('accounts.reports.index') }}" class="acm-btn acm-btn-secondary" title="Back to All Reports">
                <i class="bi bi-grid-fill"></i>
                <span>All Reports</span>
            </a>
            <a href="{{ route('accounts.reports.expense-subcategory-wise.export-excel', request()->query()) }}" class="acm-btn acm-btn-excel" title="Download Excel Spreadsheet">
                <i class="bi bi-file-earmark-excel-fill"></i>
                <span>Export Excel</span>
            </a>
            <a href="{{ route('accounts.reports.expense-subcategory-wise.export-pdf', request()->query()) }}" class="acm-btn acm-btn-pdf" title="Download PDF Report">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span>Export PDF</span>
            </a>
            <button type="button" onclick="window.print()" class="acm-btn acm-btn-print" title="Print this view">
                <i class="bi bi-printer-fill"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div class="inv-body">

        {{-- Filter Card --}}
        <div class="filter-card">
            <form method="GET" action="{{ route('accounts.reports.expense-subcategory-wise') }}" id="filterForm">
                <div class="filter-grid">
                    <div>
                        <label class="f-label" for="categorySelect">
                            <i class="bi bi-tags-fill"></i> 1. Parent Category
                        </label>
                        <select name="category_id" id="categorySelect" class="f-input">
                            <option value="all" {{ empty($selectedCategory) ? 'selected' : '' }}>
                                — All Expense Categories —
                            </option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (isset($selectedCategory) && $selectedCategory->id == $cat->id) ? 'selected' : '' }}>
                                    {{ $cat->name }} {{ $cat->code ? '('.$cat->code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="f-label" for="subcategorySelect">
                            <i class="bi bi-diagram-2-fill"></i> 2. Subcategory
                        </label>
                        <select name="subcategory_id" id="subcategorySelect" class="f-input">
                            <option value="all" {{ empty($selectedSubcategory) && $subcategoryId !== 'unassigned' ? 'selected' : '' }}>
                                — All Subcategories —
                            </option>
                            <option value="unassigned" {{ $subcategoryId === 'unassigned' ? 'selected' : '' }}>
                                [ Unassigned / General ]
                            </option>
                            @foreach($subcategories as $sub)
                                <option value="{{ $sub->id }}" {{ (isset($selectedSubcategory) && $selectedSubcategory->id == $sub->id) ? 'selected' : '' }}>
                                    {{ $sub->name }} {{ $sub->code ? '('.$sub->code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="f-label" for="dateFrom">
                            <i class="bi bi-calendar3"></i> From Date
                        </label>
                        <input type="date" name="date_from" id="dateFrom" value="{{ $dateFrom }}" class="f-input">
                    </div>

                    <div>
                        <label class="f-label" for="dateTo">
                            <i class="bi bi-calendar3"></i> To Date
                        </label>
                        <input type="date" name="date_to" id="dateTo" value="{{ $dateTo }}" class="f-input">
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="acm-btn acm-btn-primary" style="height:40px; white-space:nowrap; background:linear-gradient(135deg,#2563eb,#3b82f6); box-shadow:0 4px 14px rgba(37,99,235,0.25);">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                        <a href="{{ route('accounts.reports.expense-subcategory-wise') }}" class="acm-btn acm-btn-secondary" style="height:40px;" title="Reset filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>

                {{-- Quick Presets --}}
                <div class="presets-wrap">
                    <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Quick Ranges:</span>
                    <button type="button" class="preset-pill" onclick="setPreset('today')">Today</button>
                    <button type="button" class="preset-pill" onclick="setPreset('week')">This Week</button>
                    <button type="button" class="preset-pill" onclick="setPreset('month')">This Month</button>
                    <button type="button" class="preset-pill" onclick="setPreset('last_month')">Last Month</button>
                    <button type="button" class="preset-pill" onclick="setPreset('quarter')">This Quarter</button>
                    <button type="button" class="preset-pill" onclick="setPreset('fy')">This Financial Year</button>
                    <button type="button" class="preset-pill" onclick="setPreset('all_time')">All Time</button>
                </div>
            </form>
        </div>

        {{-- Stat Cards Grid --}}
        <div class="acm-stats-grid">
            {{-- Total Spend --}}
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#eff6ff; color:#2563eb;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Total Subcategory Spend</div>
                    <div class="acm-stat-val">₹{{ number_format((float) $grandTotalAmount, 2) }}</div>
                    <div class="acm-stat-sub">{{ $grandTotalCount }} total transactions</div>
                </div>
            </div>

            {{-- Fixed Expenses --}}
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#e0f2fe; color:#0284c7;">
                    <i class="bi bi-pin-angle-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Fixed Expenses</div>
                    <div class="acm-stat-val" style="color:#0284c7;">₹{{ number_format((float) $grandTotalFixed, 2) }}</div>
                    <div class="acm-stat-sub">
                        {{ $grandTotalAmount > 0 ? round(($grandTotalFixed / $grandTotalAmount) * 100, 1) : 0 }}% of subcategory spend
                    </div>
                </div>
            </div>

            {{-- Variable Expenses --}}
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fef3c7; color:#d97706;">
                    <i class="bi bi-shuffle"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Variable Expenses</div>
                    <div class="acm-stat-val" style="color:#d97706;">₹{{ number_format((float) $grandTotalVariable, 2) }}</div>
                    <div class="acm-stat-sub">
                        {{ $grandTotalAmount > 0 ? round(($grandTotalVariable / $grandTotalAmount) * 100, 1) : 0 }}% of subcategory spend
                    </div>
                </div>
            </div>

            {{-- Active Subcategories Count & Top Spend --}}
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Top Subcategory Spend</div>
                    <div class="acm-stat-val" style="font-size:16px; color:#16a34a;">
                        {{ $topSubcategory ? $topSubcategory['subcategory_name'] : 'None' }}
                    </div>
                    <div class="acm-stat-sub">
                        @if($topSubcategory && $topSubcategory['total'] > 0)
                            ₹{{ number_format((float) $topSubcategory['total'], 2) }} ({{ $topSubcategory['percentage'] }}%)
                        @else
                            {{ $activeSubcategoriesCount }} active subcategories
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Subcategory Table Card --}}
        <div class="acm-card">
            <div class="card-top">
                <div>
                    <div class="card-title">
                        <i class="bi bi-table" style="color:#2563eb;"></i>
                        <span>Subcategory Wise Expense Breakdown</span>
                    </div>
                    <div class="card-sub">
                        Period: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d M, Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($dateTo)->format('d M, Y') }}</strong>
                        @if($selectedCategory)
                            &bull; Category: <span class="badge-category">{{ $selectedCategory->name }}</span>
                        @endif
                        @if($selectedSubcategory)
                            &bull; Subcategory: <span class="badge-code">{{ $selectedSubcategory->name }}</span>
                        @endif
                    </div>
                </div>
                <div style="font-size:12px; color:#64748b;">
                    Click <i class="bi bi-chevron-right" style="font-size:11px;"></i> to expand & view transactions under subcategory
                </div>
            </div>

            <div class="tbl-wrap">
                <table class="report-tbl">
                    <thead>
                        <tr>
                            <th style="width:44px;" class="text-center">#</th>
                            <th style="width:160px;">Parent Category</th>
                            <th style="width:100px;">Code</th>
                            <th>Subcategory Name</th>
                            <th class="text-center" style="width:100px;">Entries</th>
                            <th class="text-right" style="width:140px;">Fixed (₹)</th>
                            <th class="text-right" style="width:140px;">Variable (₹)</th>
                            <th class="text-right" style="width:160px;">Total Amount (₹)</th>
                            <th style="width:160px;">% Share</th>
                            <th style="width:70px;" class="text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subcategoryRows as $index => $row)
                            <tr class="parent-row" id="parent-row-{{ $index }}">
                                <td class="text-center" style="color:#94a3b8; font-weight:600;">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="badge-category">{{ $row['category_name'] }}</span>
                                </td>
                                <td>
                                    @if(!empty($row['subcategory_code']) && $row['subcategory_code'] !== '—')
                                        <span class="badge-code">{{ $row['subcategory_code'] }}</span>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <strong style="color:#0f172a; font-size:13.5px;">{{ $row['subcategory_name'] }}</strong>
                                </td>
                                <td class="text-center">
                                    <span style="font-weight:700; color:#475569;">{{ $row['count'] }}</span>
                                </td>
                                <td class="text-right" style="font-variant-numeric:tabular-nums; color:#0369a1; font-weight:600;">
                                    ₹{{ number_format((float) $row['fixed'], 2) }}
                                </td>
                                <td class="text-right" style="font-variant-numeric:tabular-nums; color:#b45309; font-weight:600;">
                                    ₹{{ number_format((float) $row['variable'], 2) }}
                                </td>
                                <td class="text-right" style="font-variant-numeric:tabular-nums; font-weight:800; color:#0f172a; font-size:14px;">
                                    ₹{{ number_format((float) $row['total'], 2) }}
                                </td>
                                <td>
                                    <span style="font-weight:700; font-size:12px; color:#475569;">{{ $row['percentage'] }}%</span>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width:{{ min(100, $row['percentage']) }}%;"></div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($row['count'] > 0)
                                        <button type="button" class="btn-toggle-row" onclick="toggleDetails({{ $index }})" title="View line items">
                                            <i class="bi bi-chevron-right" id="chevron-{{ $index }}"></i>
                                        </button>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- Expandable Transactions Drawer --}}
                            @if($row['count'] > 0)
                                <tr class="drilldown-row" id="drilldown-row-{{ $index }}">
                                    <td colspan="10" style="padding:0;">
                                        <div class="drilldown-container">
                                            <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                                                <span style="font-size:12px; font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:0.04em;">
                                                    <i class="bi bi-list-task" style="color:#2563eb;"></i> Transactions under: {{ $row['category_name'] }} &rarr; {{ $row['subcategory_name'] }} ({{ $row['count'] }} entries)
                                                </span>
                                                <span style="font-size:12px; font-weight:800; color:#0f172a;">
                                                    Subtotal: ₹{{ number_format((float) $row['total'], 2) }}
                                                </span>
                                            </div>
                                            <table class="sub-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:40px;" class="text-center">#</th>
                                                        <th style="width:110px;">Date</th>
                                                        <th style="width:140px;">Expense No</th>
                                                        <th style="width:130px;">Invoice / Bill No</th>
                                                        <th>Vendor</th>
                                                        <th style="width:100px;">Type</th>
                                                        <th class="text-right" style="width:130px;">Amount (₹)</th>
                                                        <th>Remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($row['expenses'] as $expIdx => $exp)
                                                        <tr>
                                                            <td class="text-center" style="color:#94a3b8;">{{ $expIdx + 1 }}</td>
                                                            <td>
                                                                {{ $exp->expense_date ? $exp->expense_date->format('d M, Y') : '—' }}
                                                            </td>
                                                            <td>
                                                                <strong style="color:#0f172a;">{{ $exp->expense_number }}</strong>
                                                            </td>
                                                            <td>
                                                                {{ $exp->invoice_no ?: '—' }}
                                                            </td>
                                                            <td>
                                                                {{ $exp->vendor ? ($exp->vendor->vendor_name ?: $exp->vendor->company_name) : '—' }}
                                                            </td>
                                                            <td>
                                                                <span class="{{ $exp->expense_type === 'Fixed' ? 'badge-fixed' : 'badge-var' }}">
                                                                    {{ $exp->expense_type }}
                                                                </span>
                                                            </td>
                                                            <td class="text-right" style="font-weight:700; color:#0f172a; font-variant-numeric:tabular-nums;">
                                                                ₹{{ number_format((float) $exp->amount, 2) }}
                                                            </td>
                                                            <td style="color:#64748b; font-size:11.5px; max-width:240px; white-space:normal;">
                                                                {{ $exp->remarks ?: '—' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="10" style="text-align:center; padding:48px 20px;">
                                    <div style="font-size:32px; color:#cbd5e1; margin-bottom:8px;"><i class="bi bi-inbox"></i></div>
                                    <div style="font-size:15px; font-weight:700; color:#334155;">No subcategory expense records found</div>
                                    <div style="font-size:13px; color:#94a3b8; margin-top:4px;">No expense entries matched the selected category and subcategory filters for this period.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($subcategoryRows))
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-transform:uppercase; font-size:12px; letter-spacing:0.04em;">
                                    Grand Total Summary
                                </td>
                                <td class="text-center" style="font-size:13px;">{{ $grandTotalCount }}</td>
                                <td class="text-right" style="color:#0369a1; font-size:13px;">
                                    ₹{{ number_format((float) $grandTotalFixed, 2) }}
                                </td>
                                <td class="text-right" style="color:#b45309; font-size:13px;">
                                    ₹{{ number_format((float) $grandTotalVariable, 2) }}
                                </td>
                                <td class="text-right" style="font-size:15px; color:#2563eb;">
                                    ₹{{ number_format((float) $grandTotalAmount, 2) }}
                                </td>
                                <td>100.0%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const subcategoriesMaster = @json($allSubcategories);

$(document).ready(function() {
    $('#categorySelect, #subcategorySelect').select2({
        width: '100%',
        placeholder: 'Select an option'
    });

    $('#categorySelect').on('change', function() {
        const catId = $(this).val();
        updateSubcategoryOptions(catId);
    });
});

function updateSubcategoryOptions(catId) {
    const $sub = $('#subcategorySelect');
    const currentVal = $sub.val();
    $sub.empty();

    $sub.append(new Option('— All Subcategories —', 'all'));
    $sub.append(new Option('[ Unassigned / General ]', 'unassigned'));

    let filtered = subcategoriesMaster;
    if (catId && catId !== 'all') {
        filtered = subcategoriesMaster.filter(item => String(item.account_expense_category_id) === String(catId));
    }

    filtered.forEach(item => {
        const label = item.code ? `${item.name} (${item.code})` : item.name;
        const opt = new Option(label, item.id);
        $sub.append(opt);
    });

    if (currentVal && $sub.find(`option[value="${currentVal}"]`).length > 0) {
        $sub.val(currentVal).trigger('change.select2');
    } else {
        $sub.val('all').trigger('change.select2');
    }
}

function toggleDetails(index) {
    const row = document.getElementById('drilldown-row-' + index);
    const chevron = document.getElementById('chevron-' + index);
    const btn = chevron ? chevron.parentElement : null;

    if (!row) return;

    if (row.classList.contains('show')) {
        row.classList.remove('show');
        if (btn) btn.classList.remove('open');
    } else {
        row.classList.add('show');
        if (btn) btn.classList.add('open');
    }
}

function setPreset(type) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');

    let from = '';
    let to = `${yyyy}-${mm}-${dd}`;

    if (type === 'today') {
        from = `${yyyy}-${mm}-${dd}`;
    } else if (type === 'week') {
        const firstDayOfWeek = new Date(today);
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1);
        firstDayOfWeek.setDate(diff);
        from = `${firstDayOfWeek.getFullYear()}-${String(firstDayOfWeek.getMonth() + 1).padStart(2, '0')}-${String(firstDayOfWeek.getDate()).padStart(2, '0')}`;
    } else if (type === 'month') {
        from = `${yyyy}-${mm}-01`;
    } else if (type === 'last_month') {
        const lastMonthDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
        from = `${lastMonthDate.getFullYear()}-${String(lastMonthDate.getMonth() + 1).padStart(2, '0')}-01`;
        to = `${lastMonthEnd.getFullYear()}-${String(lastMonthEnd.getMonth() + 1).padStart(2, '0')}-${String(lastMonthEnd.getDate()).padStart(2, '0')}`;
    } else if (type === 'quarter') {
        const currentQuarter = Math.floor(today.getMonth() / 3);
        const qStartMonth = currentQuarter * 3;
        const qStartDate = new Date(yyyy, qStartMonth, 1);
        from = `${qStartDate.getFullYear()}-${String(qStartDate.getMonth() + 1).padStart(2, '0')}-01`;
    } else if (type === 'fy') {
        const fyStart = (today.getMonth() + 1) >= 4 ? yyyy : yyyy - 1;
        from = `${fyStart}-04-01`;
    } else if (type === 'all_time') {
        from = '2020-01-01';
    }

    document.getElementById('dateFrom').value = from;
    document.getElementById('dateTo').value = to;
    document.getElementById('filterForm').submit();
}
</script>
@endpush
