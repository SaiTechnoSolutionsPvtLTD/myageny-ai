@extends('layouts.app')

@section('title', 'Expenses - Accounts')

@push('styles')
<style>
.acc-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.acc-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.acc-topbar-left { display:flex; flex-direction:column; justify-content:center; }
.acc-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.acc-breadcrumbs { font-size:12px; color:#9e9e9e; display:flex; align-items:center; gap:6px; margin-top:2px; }
.acc-breadcrumbs a { color:#9e9e9e; text-decoration:none; transition:color .15s; }
.acc-breadcrumbs a:hover { color:#fe5f04; }
.acc-breadcrumbs .sep { color:#ccc; }
.acc-breadcrumbs .current { color:#424242; font-weight:600; }
.acc-topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.acc-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .acc-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .acc-body { padding:16px 20px 24px; }
}

.acc-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acc-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acc-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acc-btn-secondary { background:#f1f5f9; color:#334155; }
.acc-btn-secondary:hover { background:#e2e8f0; }
.acc-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acc-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acc-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acc-btn-danger:hover { background:#fca5a5; }

.acc-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.acc-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acc-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acc-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acc-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.acc-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }

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
.acc-filter-accordion .acc-filter-grid { border-bottom:none; }
@media (max-width: 600px) {
    .acc-filter-toggle-hint, .acc-filter-sub { display:none; }
}

/* 5 Column Filter Grid */
.acc-filter-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:12px; padding:16px 20px; border-bottom:1px solid #e2e8f0; background:#fff; align-items:end; }
@media (max-width: 1200px) { .acc-filter-grid { grid-template-columns:repeat(3, 1fr); } }
@media (max-width: 768px) { .acc-filter-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 480px) { .acc-filter-grid { grid-template-columns:1fr; } }
.filter-col { display:flex; flex-direction:column; gap:5px; }
.filter-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.filter-control { width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; box-sizing:border-box; height:38px; }
.filter-control:focus { border-color:#fe5f04; box-shadow:0 0 0 2px rgba(254,95,4,.1); }

.acc-tbl { width:100%; border-collapse:collapse; }
.acc-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acc-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.acc-tbl tr:hover td { background:#fafbfc; }

.type-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.type-fixed-badge { background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe; }
.type-variable-badge { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }

.attachment-chip { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:#eff6ff; color:#2563eb; font-size:11px; font-weight:700; border:1px solid #bfdbfe; text-decoration:none; }
.attachment-chip:hover { background:#dbeafe; color:#1d4ed8; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:100005; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:94%; max-width:720px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:18px; }

/* Full Page Pivot Modal Styling */
.modal-pivot-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.7); backdrop-filter:blur(6px); z-index:99990; align-items:center; justify-content:center; padding:16px; box-sizing:border-box; }
.modal-pivot-overlay.active { display:flex; }
.modal-pivot-container { background:#f8fafc; width:100%; height:100%; max-width:1560px; max-height:96vh; border-radius:18px; border:1px solid #cbd5e1; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 70px -10px rgba(0,0,0,.45); animation:pivotModalIn .22s cubic-bezier(.16,1,.3,1); }
@keyframes pivotModalIn { from{ opacity:0; transform:scale(.97) translateY(8px); } to{ opacity:1; transform:scale(1) translateY(0); } }
.modal-pivot-header { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; background:#ffffff; border-bottom:1px solid #e2e8f0; flex-shrink:0; box-shadow:0 1px 4px rgba(0,0,0,.02); gap:12px; }
.modal-pivot-title-wrap { display:flex; align-items:center; gap:12px; }
.modal-pivot-icon { width:40px; height:40px; border-radius:10px; background:#fff7ed; color:#fe5f04; display:flex; align-items:center; justify-content:center; font-size:20px; }
.modal-pivot-title { font-size:18px; font-weight:800; color:#0f172a; line-height:1.2; }
.modal-pivot-sub { font-size:12px; color:#64748b; margin-top:2px; }
.modal-pivot-actions { display:flex; align-items:center; gap:10px; }
.pivot-close-btn { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; width:36px; height:36px; font-size:22px; color:#64748b; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s; line-height:1; }
.pivot-close-btn:hover { background:#fee2e2; color:#b91c1c; border-color:#fca5a5; }
.modal-pivot-body { flex:1; overflow-y:auto; padding:24px; display:flex; flex-direction:column; gap:20px; background:#f4f5f7; }
@media (max-width:768px) {
    .modal-pivot-overlay { padding:0; }
    .modal-pivot-container { max-height:100vh; height:100%; border-radius:0; border:none; }
    .modal-pivot-header { padding:12px 16px; }
    .modal-pivot-body { padding:14px; }
}

.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns:1fr; } }
.detail-item { background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0; }
.detail-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px; }
.detail-val { font-size:13px; font-weight:700; color:#0f172a; word-break:break-word; }
</style>
@endpush

@section('content')
<div class="acc-page">

    {{-- Standard Uniform Topbar --}}
    <div class="acc-topbar">
        <div class="acc-topbar-left">
            <div class="acc-title">
                <i class="bi bi-receipt-cutoff" style="color:#fe5f04;"></i>
                <span>Expenses</span>
            </div>
            <div class="acc-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <span class="current">Expenses</span>
            </div>
        </div>
        <div class="acc-topbar-actions">
            <form action="{{ route('accounts.expenses.sync-recurring') }}" method="POST" style="margin:0;" onsubmit="return confirm('Generate due recurring fixed expenses for this month now?')">
                @csrf
                <button type="submit" class="acc-btn acc-btn-outline" style="border-color:#16a34a; color:#15803d; background:#f0fdf4;" title="Generate recurring fixed expenses for current month">
                    <i class="bi bi-arrow-repeat"></i> Sync Fixed
                </button>
            </form>
            <button type="button" class="acc-btn acc-btn-outline" onclick="openExpensePivotFromIndex()" style="border-color:#fe5f04; color:#fe5f04; background:#fff7ed;" title="Open Expense Pivot Report in Full Page Modal">
                <i class="bi bi-table"></i> Expense Pivot
            </button>
            <a href="{{ route('accounts.expenses.create') }}" class="acc-btn acc-btn-primary">
                <i class="bi bi-plus-lg"></i> Add Expense
            </a>
        </div>
    </div>

    <div class="acc-body">

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

    {{-- Summary KPI Cards --}}
    <div class="acc-stats-grid">
        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-currency-rupee"></i>
            </div>
            <div>
                <div class="acc-stat-label">Total Expenses</div>
                <div class="acc-stat-val">₹{{ number_format($totalAmount, 2) }}</div>
            </div>
        </div>

        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <div class="acc-stat-label">This Month Expenses</div>
                <div class="acc-stat-val">₹{{ number_format($thisMonthAmount, 2) }}</div>
            </div>
        </div>

        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#e0e7ff; color:#4338ca;">
                <i class="bi bi-pin-angle"></i>
            </div>
            <div>
                <div class="acc-stat-label">Fixed Expenses</div>
                <div class="acc-stat-val">₹{{ number_format($fixedAmount, 2) }}</div>
            </div>
        </div>

        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#fef3c7; color:#d97706;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div>
                <div class="acc-stat-label">Variable Expenses</div>
                <div class="acc-stat-val">₹{{ number_format($variableAmount, 2) }}</div>
            </div>
        </div>

        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#f1f5f9; color:#475569;">
                <i class="bi bi-collection"></i>
            </div>
            <div>
                <div class="acc-stat-label">Total Entries</div>
                <div class="acc-stat-val">{{ $totalCount }}</div>
            </div>
        </div>
    </div>

    @php
        $filterKeys = ['search', 'category_id', 'subcategory_id', 'vendor_id', 'expense_type', 'date_from', 'date_to', 'month'];
        $activeFilterCount = collect($filterKeys)->filter(fn($k) => request()->filled($k))->count();
        $hasActiveFilters = $activeFilterCount > 0;
    @endphp

    {{-- Filter Accordion --}}
    <details class="acc-filter-accordion" id="expenseFiltersAccordion" {{ $hasActiveFilters ? 'open' : '' }}>
        <summary class="acc-filter-toggle">
            <div class="acc-filter-toggle-left">
                <span class="acc-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                <div>
                    <div class="acc-filter-title">
                        <span>Filter Expenses</span>
                        @if($hasActiveFilters)
                            <span class="acc-filter-badge">{{ $activeFilterCount }} Active</span>
                        @endif
                    </div>
                    <div class="acc-filter-sub">Filter by search keyword, category, subcategory, vendor, expense type or date range</div>
                </div>
            </div>
            <div class="acc-filter-toggle-right">
                <span class="acc-filter-toggle-hint"></span>
                <span class="acc-filter-chevron"><i class="bi bi-chevron-down"></i></span>
            </div>
        </summary>
        <div class="acc-filter-body">
            <form method="GET" action="{{ route('accounts.expenses.index') }}" class="acc-filter-grid">
                {{-- ROW 1 (5 Columns) --}}
                {{-- Col 1: Search --}}
                <div class="filter-col">
                    <label class="filter-label">Search</label>
                    <div style="position:relative;">
                        <i class="bi bi-search" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px;"></i>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Invoice #, remarks, vendor..."
                               class="filter-control"
                               style="padding-left:30px;">
                    </div>
                </div>

                {{-- Col 2: Category --}}
                <div class="filter-col">
                    <label class="filter-label">Category</label>
                    <select name="category_id" id="filterCategory" class="filter-control" onchange="filterCategoryChanged(this.value)">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Col 3: Subcategory (Master) --}}
                <div class="filter-col">
                    <label class="filter-label">Subcategory</label>
                    <select name="subcategory_id" id="filterSubcategory" class="filter-control">
                        <option value="">All Subcategories</option>
                        @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}" data-category-id="{{ $sub->account_expense_category_id }}" {{ request('subcategory_id') == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Col 4: Vendor --}}
                <div class="filter-col">
                    <label class="filter-label">Vendor</label>
                    <select name="vendor_id" class="filter-control">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $ven)
                            <option value="{{ $ven->id }}" {{ request('vendor_id') == $ven->id ? 'selected' : '' }}>
                                {{ $ven->vendor_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Col 5: Expense Type --}}
                <div class="filter-col">
                    <label class="filter-label">Expense Type</label>
                    <select name="expense_type" class="filter-control">
                        <option value="">All Types</option>
                        <option value="Fixed" {{ request('expense_type') === 'Fixed' ? 'selected' : '' }}>Fixed</option>
                        <option value="Variable" {{ request('expense_type') === 'Variable' ? 'selected' : '' }}>Variable</option>
                    </select>
                </div>

                {{-- ROW 2 (5 Columns) --}}
                {{-- Col 1: Date From --}}
                <div class="filter-col">
                    <label class="filter-label">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-control">
                </div>

                {{-- Col 2: Date To --}}
                <div class="filter-col">
                    <label class="filter-label">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-control">
                </div>

                {{-- Col 3: Month Preset --}}
                <div class="filter-col">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <label class="filter-label" style="margin-bottom:0;">Month</label>
                        <a href="{{ route('accounts.expenses.index', ['month' => $nextMonthCarbon->format('Y-m')]) }}" style="font-size:10px; color:#7c3aed; font-weight:800; text-decoration:none;" title="Quick Forecast for {{ $nextMonthCarbon->format('M Y') }}">
                            Next: {{ $nextMonthCarbon->format('M Y') }} &rarr;
                        </a>
                    </div>
                    <input type="month" name="month" value="{{ request('month') }}" class="filter-control">
                </div>

                {{-- Col 4: Apply Filter Button --}}
                <div class="filter-col">
                    <label class="filter-label">&nbsp;</label>
                    <button type="submit" class="acc-btn acc-btn-primary" style="width:100%; justify-content:center; height:38px;">
                        <i class="bi bi-funnel-fill"></i> Filter
                    </button>
                </div>

                {{-- Col 5: Reset Button --}}
                <div class="filter-col">
                    <label class="filter-label">&nbsp;</label>
                    <a href="{{ route('accounts.expenses.index') }}" class="acc-btn acc-btn-secondary" style="width:100%; justify-content:center; height:38px; text-decoration:none;">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </details>

    {{-- Table Card --}}
    <div class="acc-card">

        {{-- Future Month Forecast Banner --}}
        @if($isFutureMonthFilter)
        <div style="background: linear-gradient(135deg, #f5f3ff, #ede9fe); border-bottom: 1px solid #ddd6fe; padding: 14px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:10px; background:#7c3aed; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div style="font-weight:800; font-size:14px; color:#4c1d95;">
                        {{ $futureMonthLabel }} — Fixed Expenses Projection & Forecast
                    </div>
                    <div style="font-size:12px; color:#6d28d9; margin-top:2px;">
                        Scheduled Monthly Recurring Total: <strong style="font-size:13px; color:#4c1d95;">₹{{ number_format($nextMonthProjectedAmount, 2) }}</strong> across {{ count($projectedExpenses) }} scheduled fixed item(s).
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <form action="{{ route('accounts.expenses.sync-recurring') }}" method="POST" style="margin:0;" onsubmit="return confirm('Generate fixed expenses for {{ $futureMonthLabel }} now?')">
                    @csrf
                    <input type="hidden" name="month" value="{{ request('month') }}">
                    <input type="hidden" name="force" value="1">
                    <button type="submit" class="acc-btn" style="background:#7c3aed; color:#fff; font-size:12px; padding:7px 14px; box-shadow:0 2px 8px rgba(124,58,237,.25);">
                        <i class="bi bi-lightning-charge-fill"></i> Generate Now for {{ $futureMonthLabel }}
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Table --}}
        <div style="overflow-x:auto;">
            <table class="acc-tbl">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Expense #</th>
                        <th>Invoice No</th>
                        <th>Category / Subcategory</th>
                        <th>Vendor</th>
                        <th>Type</th>
                        <th style="text-align:right;">Amount (₹)</th>
                        <th>Attachments</th>
                        <th>Recorded By</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- If looking at a future month, show scheduled recurring fixed expenses --}}
                    @if($isFutureMonthFilter && $projectedExpenses->isNotEmpty())
                        <tr style="background:#f5f3ff;">
                            <td colspan="10" style="padding:10px 16px; font-weight:800; font-size:12px; color:#6d28d9; border-top:1px solid #ddd6fe; border-bottom:1px solid #ddd6fe;">
                                <i class="bi bi-clock-history"></i> Scheduled Fixed Expenses for {{ $futureMonthLabel }} (Projected: ₹{{ number_format($nextMonthProjectedAmount, 2) }})
                            </td>
                        </tr>
                        @foreach($projectedExpenses as $pExp)
                        @php $tplId = $pExp->template_id ?? ($pExp->id ?? null); @endphp
                        <tr style="background:#faf5ff;">
                            <td style="white-space:nowrap; font-weight:700; color:#6d28d9;">
                                <i class="bi bi-calendar-event"></i> {{ $pExp->expense_date->format('d M Y') }}
                            </td>
                            <td style="white-space:nowrap;">
                                <span style="background:#ede9fe; color:#6d28d9; padding:3px 8px; border-radius:6px; font-weight:800; font-size:11px; border:1px solid #ddd6fe;">
                                    SCHEDULED #{{ $pExp->template?->expense_number ?? $tplId }}
                                </span>
                            </td>
                            <td style="white-space:nowrap; color:#64748b; font-size:12px;">
                                {{ $pExp->invoice_no ? 'Template: ' . $pExp->invoice_no : 'Monthly Fixed' }}
                            </td>
                            <td>
                                <div style="font-weight:700; color:#1e293b;">
                                    {{ $pExp->category?->name ?? '—' }}
                                </div>
                                @if($pExp->subcategory)
                                    <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                        ↳ {{ $pExp->subcategory->name }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($pExp->vendor)
                                    <div style="font-weight:700; color:#0f172a;">{{ $pExp->vendor->vendor_name }}</div>
                                    @if($pExp->vendor->company_name)
                                        <div style="font-size:11px; color:#64748b;">{{ $pExp->vendor->company_name }}</div>
                                    @endif
                                @else
                                    <span style="color:#94a3b8; font-style:italic;">No Vendor</span>
                                @endif
                            </td>
                            <td>
                                <span class="type-badge type-fixed-badge" style="background:#ede9fe; color:#6d28d9; border-color:#c4b5fd;">
                                    <i class="bi bi-arrow-repeat"></i> Recurring Fixed
                                </span>
                                <div style="font-size:10px; color:#6d28d9; margin-top:2px; font-weight:700;">
                                    Due Day {{ $pExp->recurring_day_of_month ?? $pExp->expense_date->day }}
                                </div>
                            </td>
                            <td style="text-align:right; font-weight:800; font-size:14px; color:#6d28d9; white-space:nowrap;">
                                ₹{{ number_format($pExp->amount, 2) }}
                            </td>
                            <td>
                                <span style="font-size:11px; color:#8b5cf6; font-style:italic;">Copies from template</span>
                            </td>
                            <td style="font-size:12px; color:#6d28d9;">
                                <i class="bi bi-robot"></i> Scheduled
                            </td>
                            <td style="text-align:center; white-space:nowrap;">
                                @if($tplId)
                                <a href="{{ route('accounts.expenses.show', $tplId) }}"
                                   class="acc-btn acc-btn-secondary"
                                   style="padding:5px 8px; font-size:11px;"
                                   title="View Recurring Template">
                                    <i class="bi bi-eye"></i> Template
                                </a>
                                <a href="{{ route('accounts.expenses.edit', $tplId) }}"
                                   class="acc-btn acc-btn-outline"
                                   style="padding:5px 8px; font-size:11px;"
                                   title="Edit Recurring Settings">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @endif

                    @forelse($expenses as $exp)
                    <tr>
                        <td style="white-space:nowrap; font-weight:600; color:#475569;">
                            {{ $exp->expense_date ? $exp->expense_date->format('d M Y') : '—' }}
                        </td>
                        <td style="white-space:nowrap; font-weight:700; color:#0f172a;">
                            <span style="cursor:pointer; color:#fe5f04;" onclick="viewExpense({{ $exp->id }})">
                                {{ $exp->expense_number }}
                            </span>
                        </td>
                        <td style="white-space:nowrap; font-weight:600;">
                            <div>{{ $exp->invoice_no ?: '—' }}</div>
                            @if(str_starts_with($exp->invoice_no ?? '', 'HRMS-REQ-'))
                                <span style="display:inline-flex; align-items:center; gap:3px; font-size:10px; font-weight:800; color:#0369a1; background:#e0f2fe; padding:1px 6px; border-radius:4px; margin-top:3px;">
                                    <i class="bi bi-person-badge"></i> HRMS Reimb.
                                </span>
                            @elseif(str_starts_with($exp->invoice_no ?? '', 'SUPPORT-TICKET-'))
                                <span style="display:inline-flex; align-items:center; gap:3px; font-size:10px; font-weight:800; color:#047857; background:#ecfdf5; padding:1px 6px; border-radius:4px; margin-top:3px;">
                                    <i class="bi bi-headset"></i> Support
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight:700; color:#1e293b;">
                                {{ $exp->category?->name ?? '—' }}
                            </div>
                            @if($exp->subcategory)
                                <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                    ↳ {{ $exp->subcategory->name }}
                                </div>
                            @endif
                            @if($exp->remarks)
                                @php
                                    $firstRemark = '';
                                    if (preg_match('/Approver Remarks:\s*([^\n]+)/i', $exp->remarks, $m)) {
                                        $firstRemark = $m[1];
                                    } elseif (preg_match('/Resolution Remarks:\s*([^\n]+)/i', $exp->remarks, $m)) {
                                        $firstRemark = $m[1];
                                    } else {
                                        $lines = array_filter(explode("\n", $exp->remarks));
                                        $firstRemark = reset($lines) ?: '';
                                    }
                                @endphp
                                @if($firstRemark)
                                    <div style="font-size:11px; color:#64748b; margin-top:3px; font-style:italic; max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $exp->remarks }}">
                                        💬 {{ Str::limit($firstRemark, 35) }}
                                    </div>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if($exp->vendor)
                                <div style="font-weight:700; color:#0f172a;">{{ $exp->vendor->vendor_name }}</div>
                                @if($exp->vendor->company_name)
                                    <div style="font-size:11px; color:#64748b;">{{ $exp->vendor->company_name }}</div>
                                @endif
                            @else
                                <span style="color:#94a3b8; font-style:italic;">No Vendor</span>
                            @endif
                        </td>
                        <td>
                            @if($exp->expense_type === 'Fixed')
                                <span class="type-badge type-fixed-badge">
                                    <i class="bi bi-pin-angle-fill"></i> Fixed
                                </span>
                            @else
                                <span class="type-badge type-variable-badge">
                                    <i class="bi bi-graph-up-arrow"></i> Variable
                                </span>
                            @endif

                            @if($exp->is_recurring)
                                <div style="margin-top:4px;">
                                    <span style="background:#dcfce7; color:#15803d; font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:3px;">
                                        <i class="bi bi-arrow-repeat"></i> Monthly (Day {{ $exp->recurring_day_of_month ?? ($exp->expense_date ? $exp->expense_date->day : 1) }})
                                    </span>
                                </div>
                            @elseif($exp->auto_generated)
                                <div style="margin-top:4px;">
                                    <span style="background:#ede9fe; color:#6d28d9; font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; display:inline-flex; align-items:center; gap:3px;">
                                        <i class="bi bi-robot"></i> Auto-Gen
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td style="text-align:right; font-weight:800; font-size:14px; color:#0f172a; white-space:nowrap;">
                            ₹{{ number_format($exp->amount, 2) }}
                        </td>
                        <td>
                            @php $attList = $exp->attachment_list; @endphp
                            @if(!empty($attList))
                                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                    @foreach($attList as $att)
                                        <a href="{{ $att['url'] }}" target="_blank" class="attachment-chip" title="{{ $att['name'] }} ({{ $att['formatted_size'] }})">
                                            <i class="bi {{ $att['is_image'] ? 'bi-image' : ($att['is_pdf'] ? 'bi-file-earmark-pdf' : 'bi-paperclip') }}"></i>
                                            <span>{{ Str::limit($att['name'], 12) }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <span style="color:#94a3b8; font-size:11px;">None</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:#64748b;">
                            {{ $exp->creator?->name ?? 'System' }}
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                <a href="{{ route('accounts.expenses.show', $exp->id) }}"
                                   class="acc-btn acc-btn-secondary"
                                   style="padding:5px 8px; font-size:12px;"
                                   title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('accounts.expenses.edit', $exp->id) }}"
                                   class="acc-btn acc-btn-outline"
                                   style="padding:5px 8px; font-size:12px;"
                                   title="Edit Expense">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button"
                                        class="acc-btn acc-btn-danger"
                                        style="padding:5px 8px; font-size:12px;"
                                        title="Delete Expense"
                                        onclick="confirmDeleteExpense({{ $exp->id }}, '{{ $exp->expense_number }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    @if(!$isFutureMonthFilter || $projectedExpenses->isEmpty())
                    <tr>
                        <td colspan="10" style="text-align:center; padding:40px; color:#94a3b8;">
                            <i class="bi bi-receipt" style="font-size:36px; display:block; margin-bottom:8px; opacity:0.6;"></i>
                            No accounts expenses found. Click <strong>"Add Expense"</strong> above to record your first expense.
                        </td>
                    </tr>
                    @endif
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($expenses->hasPages())
            <div style="padding:16px 20px; border-top:1px solid #e2e8f0;">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
    </div>
</div>

{{-- Quick View Modal --}}
<div class="modal-overlay" id="viewExpenseModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-receipt" style="color:#fe5f04;"></i>
                <span id="mvExpenseNumber">Expense Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewModalBody">
            <div style="text-align:center; padding:30px; color:#64748b;">
                <i class="bi bi-arrow-repeat spin" style="font-size:24px;"></i>
                <div style="margin-top:6px;">Loading expense details...</div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal-overlay" id="deleteExpenseModal">
    <div class="modal-box" style="max-width:440px;">
        <div class="modal-header">
            <div class="modal-title" style="color:#b91c1c;">
                <i class="bi bi-exclamation-triangle-fill"></i> Delete Expense
            </div>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size:14px; color:#334155; margin:0;">
                Are you sure you want to delete expense <strong id="delExpenseNum"></strong>? This record will be soft deleted.
            </p>
            <form id="deleteExpenseForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="acc-btn acc-btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="acc-btn acc-btn-danger">
                        <i class="bi bi-trash"></i> Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Expense Pivot Full Page Modal --}}
<div class="modal-pivot-overlay" id="expensePivotModal" role="dialog" aria-modal="true" aria-labelledby="pivotModalTitle">
    <div class="modal-pivot-container">
        <div class="modal-pivot-header">
            <div class="modal-pivot-title-wrap">
                <div class="modal-pivot-icon">
                    <i class="bi bi-table"></i>
                </div>
                <div>
                    <div class="modal-pivot-title" id="pivotModalTitle">Expense Pivot Report</div>
                    <div class="modal-pivot-sub">Category & Subcategory-wise aggregation with Fixed vs Variable split and full expense breakdown</div>
                </div>
            </div>
            <div class="modal-pivot-actions">
                <button type="button" class="acc-btn acc-btn-outline acc-btn-sm" onclick="reloadPivotModal()" title="Refresh Pivot Data">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <a href="{{ route('accounts.expenses.pivot') }}" target="_blank" class="acc-btn acc-btn-outline acc-btn-sm" title="Open Pivot Report in Separate Page">
                    <i class="bi bi-box-arrow-up-right"></i> Open in New Tab
                </a>
                <button type="button" class="pivot-close-btn" onclick="closeExpensePivotModal()" title="Close (Esc)">&times;</button>
            </div>
        </div>
        <div class="modal-pivot-body" id="expensePivotModalBody">
            <div style="text-align:center; padding:60px 20px; color:#64748b;">
                <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem; color:#fe5f04 !important;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div style="font-size:15px; font-weight:700; color:#0f172a; margin-top:14px;">Loading Expense Pivot Aggregation...</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:4px;">Aggregating category and subcategory expenses</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function viewExpense(id) {
    const modal = document.getElementById('viewExpenseModal');
    const body = document.getElementById('viewModalBody');
    const title = document.getElementById('mvExpenseNumber');

    modal.classList.add('active');
    body.innerHTML = `
        <div style="text-align:center; padding:30px; color:#64748b;">
            <i class="bi bi-arrow-repeat spin" style="font-size:24px;"></i>
            <div style="margin-top:6px;">Loading expense details...</div>
        </div>
    `;

    fetch(`{{ url('accounts/expenses') }}/${id}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.status) throw new Error('Failed to load');
        const exp = data.expense;
        const atts = data.attachment_list || [];

        title.textContent = `${exp.expense_number} Details`;

        let attachmentsHtml = '<div style="color:#94a3b8; font-size:12px;">No attachments uploaded</div>';
        if (atts.length > 0) {
            attachmentsHtml = '<div style="display:flex; flex-direction:column; gap:8px;">';
            atts.forEach(att => {
                attachmentsHtml += `
                    <div style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px;">
                        <div style="display:flex; align-items:center; gap:8px; overflow:hidden;">
                            <i class="bi ${att.is_image ? 'bi-image' : (att.is_pdf ? 'bi-file-earmark-pdf' : 'bi-paperclip')}" style="color:#2563eb; font-size:18px;"></i>
                            <div>
                                <div style="font-weight:600; font-size:12px; color:#1e293b;">${att.name}</div>
                                <div style="font-size:11px; color:#64748b;">${att.formatted_size || ''}</div>
                            </div>
                        </div>
                        <a href="${att.url}" target="_blank" class="acc-btn acc-btn-outline" style="padding:4px 10px; font-size:11px;">
                            <i class="bi bi-box-arrow-up-right"></i> View / Download
                        </a>
                    </div>
                `;
            });
            attachmentsHtml += '</div>';
        }

        body.innerHTML = `
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Expense Number</div>
                    <div class="detail-val" style="color:#fe5f04;">${exp.expense_number}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Expense Date</div>
                    <div class="detail-val">${exp.expense_date ? new Date(exp.expense_date).toLocaleDateString() : '—'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Expense Amount</div>
                    <div class="detail-val" style="font-size:16px; color:#059669;">₹${parseFloat(exp.amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Expense Type</div>
                    <div class="detail-val">
                        <span class="type-badge ${exp.expense_type === 'Fixed' ? 'type-fixed-badge' : 'type-variable-badge'}">
                            ${exp.expense_type}
                        </span>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Invoice / Bill No</div>
                    <div class="detail-val">${exp.invoice_no || '—'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Recurring Setting</div>
                    <div class="detail-val">
                        ${exp.is_recurring 
                            ? `<span style="color:#15803d; font-weight:800;"><i class="bi bi-arrow-repeat"></i> Auto-generates Monthly (Day ${exp.recurring_day_of_month || '1'})</span>` 
                            : (exp.auto_generated 
                                ? `<span style="color:#6d28d9; font-weight:700;"><i class="bi bi-robot"></i> Auto-generated Monthly Record</span>` 
                                : '<span style="color:#64748b; font-weight:normal;">One-off / Non-recurring</span>')}
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Category / Subcategory</div>
                    <div class="detail-val">
                        ${exp.category ? exp.category.name : '—'}
                        ${exp.subcategory ? '<br><span style="color:#64748b; font-size:11px;">↳ ' + exp.subcategory.name + '</span>' : ''}
                    </div>
                </div>
                <div class="detail-item" style="grid-column:1/-1;">
                    <div class="detail-label">Vendor Details</div>
                    <div class="detail-val">
                        ${exp.vendor ? `<strong>${exp.vendor.vendor_name}</strong> ${exp.vendor.company_name ? '— ' + exp.vendor.company_name : ''} ${exp.vendor.mobile_number ? ' | 📞 ' + exp.vendor.mobile_number : ''} ${exp.vendor.gst_number ? ' | GST: ' + exp.vendor.gst_number : ''}` : '<span style="color:#94a3b8;">No vendor associated</span>'}
                    </div>
                </div>
                <div class="detail-item" style="grid-column:1/-1;">
                    <div class="detail-label">Remarks / Description</div>
                    <div class="detail-val" style="font-weight:normal; line-height:1.5;">${exp.remarks || '<span style="color:#94a3b8;">No remarks provided</span>'}</div>
                </div>
                <div class="detail-item" style="grid-column:1/-1;">
                    <div class="detail-label" style="margin-bottom:8px;">Attachments (${atts.length})</div>
                    ${attachmentsHtml}
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                <a href="{{ url('accounts/expenses') }}/${exp.id}/edit" class="acc-btn acc-btn-primary" style="padding:6px 14px; font-size:12px;">
                    <i class="bi bi-pencil-square"></i> Edit Expense
                </a>
            </div>
        `;
    })
    .catch(err => {
        body.innerHTML = `
            <div style="text-align:center; padding:30px; color:#b91c1c;">
                <i class="bi bi-exclamation-triangle" style="font-size:24px;"></i>
                <div style="margin-top:6px;">Failed to load expense details.</div>
            </div>
        `;
    });
}

function closeViewModal() {
    document.getElementById('viewExpenseModal').classList.remove('active');
}

function confirmDeleteExpense(id, number) {
    document.getElementById('delExpenseNum').textContent = number;
    document.getElementById('deleteExpenseForm').action = `{{ url('accounts/expenses') }}/${id}`;
    document.getElementById('deleteExpenseModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteExpenseModal').classList.remove('active');
}

// Close standard modals on clicking overlay
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('active');
    });
});

// Full Page Expense Pivot Modal Management
let isPivotLoaded = false;
let currentPivotUrl = "{{ route('accounts.expenses.pivot') }}?modal=1";

function openExpensePivotFromIndex() {
    // Pass active filters from index page if present
    const month = document.querySelector('input[name="month"]')?.value;
    const dateFrom = document.querySelector('input[name="date_from"]')?.value;
    const dateTo = document.querySelector('input[name="date_to"]')?.value;
    const categoryId = document.querySelector('select[name="category_id"]')?.value;
    const expenseType = document.querySelector('select[name="expense_type"]')?.value;

    const params = new URLSearchParams();
    params.set('modal', '1');
    if (month) params.set('month', month);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    if (categoryId) params.set('category_id', categoryId);
    if (expenseType) params.set('expense_type', expenseType);

    const url = "{{ route('accounts.expenses.pivot') }}?" + params.toString();
    openExpensePivotModal(url);
}

function openExpensePivotModal(url) {
    const modal = document.getElementById('expensePivotModal');
    const body = document.getElementById('expensePivotModalBody');
    if (!modal) return;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    const targetUrl = url || currentPivotUrl;
    if (!isPivotLoaded || url) {
        body.innerHTML = `
            <div style="text-align:center; padding:60px 20px; color:#64748b;">
                <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem; color:#fe5f04 !important;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div style="font-size:15px; font-weight:700; color:#0f172a; margin-top:14px;">Loading Expense Pivot Aggregation...</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:4px;">Aggregating category and subcategory expenses</div>
            </div>
        `;

        fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Modal-Request': '1'
            }
        })
        .then(res => res.text())
        .then(html => {
            body.innerHTML = html;
            isPivotLoaded = true;
            currentPivotUrl = targetUrl;
            initPivotModalInteractions();

            // Execute script tags inside fetched HTML
            body.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        })
        .catch(err => {
            body.innerHTML = `
                <div style="text-align:center; padding:50px 20px; color:#b91c1c;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:36px; display:block; margin-bottom:10px;"></i>
                    <div style="font-size:16px; font-weight:700;">Failed to load Expense Pivot Report.</div>
                    <div style="font-size:13px; color:#64748b; margin-top:6px;">Please try again or view in separate page.</div>
                    <button type="button" class="acc-btn acc-btn-primary" style="margin-top:16px;" onclick="reloadPivotModal()">
                        <i class="bi bi-arrow-clockwise"></i> Try Again
                    </button>
                </div>
            `;
        });
    }
}

function closeExpensePivotModal() {
    const modal = document.getElementById('expensePivotModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function reloadPivotModal() {
    isPivotLoaded = false;
    openExpensePivotModal(currentPivotUrl);
}

function initPivotModalInteractions() {
    const modalBody = document.getElementById('expensePivotModalBody');
    if (!modalBody) return;

    const form = modalBody.querySelector('#pivotFilterForm');
    if (form) {
        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            params.set('modal', '1');
            const targetUrl = form.action + '?' + params.toString();
            openExpensePivotModal(targetUrl);
        };
    }
}

// Globally defined Accordion and Pivot handlers
window.togglePivotAccordion = function(el) {
    const item = el.closest('.pivot-accordion-item');
    if (!item) return;
    const isOpen = item.classList.toggle('is-open');
    const body = item.querySelector('.pivot-accordion-body');
    if (body) {
        body.style.display = isOpen ? 'flex' : 'none';
    }
    const hintText = item.querySelector('.pivot-hint-text');
    if (hintText) {
        hintText.textContent = isOpen ? 'Click to hide breakdown' : 'Click to view breakdown';
    }
};

window.expandAllPivotAccordions = function() {
    document.querySelectorAll('.pivot-accordion-item').forEach(item => {
        item.classList.add('is-open');
        const body = item.querySelector('.pivot-accordion-body');
        if (body) body.style.display = 'flex';
        const hintText = item.querySelector('.pivot-hint-text');
        if (hintText) hintText.textContent = 'Click to hide breakdown';
    });
};

window.collapseAllPivotAccordions = function() {
    document.querySelectorAll('.pivot-accordion-item').forEach(item => {
        item.classList.remove('is-open');
        const body = item.querySelector('.pivot-accordion-body');
        if (body) body.style.display = 'none';
        const hintText = item.querySelector('.pivot-hint-text');
        if (hintText) hintText.textContent = 'Click to view breakdown';
    });
};

window.filterPivotAccordions = function(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('.pivot-accordion-item').forEach(item => {
        const text = (item.getAttribute('data-search') || item.innerText || '').toLowerCase();
        if (!q || text.includes(q)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
};

window.setPivotMonth = function(monthStr) {
    const input = document.getElementById('pivotMonthInput');
    if (input) {
        input.value = monthStr;
        window.submitPivotForm();
    }
};

window.submitPivotForm = function() {
    const form = document.getElementById('pivotFilterForm');
    if (form) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }
};

window.resetPivotFilters = function() {
    if (typeof openExpensePivotModal === 'function' && document.getElementById('expensePivotModal')?.classList.contains('active')) {
        openExpensePivotModal("{{ route('accounts.expenses.pivot') }}?modal=1");
    } else {
        window.location.href = "{{ route('accounts.expenses.pivot') }}";
    }
};

// Close pivot modal on clicking outside container
document.getElementById('expensePivotModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeExpensePivotModal();
    }
});

// Global Escape Key Listener for Modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const viewModal = document.getElementById('viewExpenseModal');
        const deleteModal = document.getElementById('deleteExpenseModal');
        const pivotModal = document.getElementById('expensePivotModal');

        if (viewModal && viewModal.classList.contains('active')) {
            closeViewModal();
        } else if (deleteModal && deleteModal.classList.contains('active')) {
            closeDeleteModal();
        } else if (pivotModal && pivotModal.classList.contains('active')) {
            closeExpensePivotModal();
        }
    }
});

// Filter Dynamic Subcategory Loading
const filterSubcatUrl = "{{ route('accounts.master.expense-categories.subcategories', ['category' => ':id']) }}";
const initialFilterSubcatId = "{{ request('subcategory_id') }}";

function filterCategoryChanged(categoryId) {
    const subSelect = document.getElementById('filterSubcategory');
    if (!subSelect) return;

    if (!categoryId) {
        subSelect.innerHTML = '<option value="">All Subcategories</option>';
        return;
    }

    subSelect.innerHTML = '<option value="">Loading...</option>';

    fetch(filterSubcatUrl.replace(':id', categoryId), {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        subSelect.innerHTML = '<option value="">All Subcategories</option>';
        if (data.status && Array.isArray(data.data)) {
            data.data.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name + (sub.code ? ' (' + sub.code + ')' : '');
                if (String(sub.id) === String(initialFilterSubcatId)) {
                    opt.selected = true;
                }
                subSelect.appendChild(opt);
            });
        }
    })
    .catch(() => {
        subSelect.innerHTML = '<option value="">All Subcategories</option>';
    });
}
</script>
@endpush
@endsection
