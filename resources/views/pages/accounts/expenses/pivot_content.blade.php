<style>
/* Pivot Content Styles */
.pivot-wrapper { display:flex; flex-direction:column; gap:20px; font-family:'Inter',sans-serif; }
.pivot-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); flex-wrap:wrap; gap:12px; }
.pivot-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; display:flex; align-items:center; gap:10px; }
.pivot-sub { font-size:13px; color:#64748b; }

.acc-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acc-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acc-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acc-btn-secondary { background:#f1f5f9; color:#334155; }
.acc-btn-secondary:hover { background:#e2e8f0; }
.acc-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acc-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acc-btn-sm { padding:5px 11px; font-size:12px; border-radius:6px; }

/* KPI Stats */
.acc-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.acc-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acc-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acc-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acc-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.acc-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }

/* 5 Column Filter Grid */
.acc-filter-grid { display:grid; grid-template-columns:repeat(5, 1fr); gap:12px; padding:16px 20px; border-bottom:1px solid #e2e8f0; background:#fff; align-items:end; }
@media (max-width: 1200px) { .acc-filter-grid { grid-template-columns:repeat(3, 1fr); } }
@media (max-width: 768px) { .acc-filter-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 480px) { .acc-filter-grid { grid-template-columns:1fr; } }
.filter-col { display:flex; flex-direction:column; gap:5px; }
.filter-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.filter-control { width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; box-sizing:border-box; height:38px; }
.filter-control:focus { border-color:#fe5f04; box-shadow:0 0 0 2px rgba(254,95,4,.1); }

/* Accordion Specific Styling */
.pivot-accordion-container { display:flex; flex-direction:column; gap:12px; padding:18px 20px; background:#f8fafc; }
.pivot-accordion-item { background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.03); transition:all .2s ease; }
.pivot-accordion-item:hover { border-color:#cbd5e1; box-shadow:0 4px 12px rgba(0,0,0,.04); }
.pivot-accordion-item.is-open { border-color:#fed7aa; box-shadow:0 6px 18px rgba(254,95,4,.08); }

.pivot-accordion-header { padding:14px 18px; background:#ffffff; display:flex; align-items:center; justify-content:space-between; gap:16px; cursor:pointer; user-select:none; transition:background .15s; }
.pivot-accordion-item.is-open .pivot-accordion-header { background:#fffaf5; border-bottom:1px solid #fed7aa; }
.pivot-accordion-header:hover { background:#f8fafc; }
.pivot-accordion-item.is-open .pivot-accordion-header:hover { background:#fff7ed; }

.pivot-acc-left { display:flex; align-items:center; gap:14px; min-width:0; flex:1; }
.pivot-acc-chevron { width:28px; height:28px; border-radius:8px; background:#f1f5f9; color:#64748b; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; transition:transform .2s ease, background .2s, color .2s; }
.pivot-accordion-item.is-open .pivot-acc-chevron { transform:rotate(90deg); background:#fe5f04; color:#ffffff; }
.pivot-acc-icon { width:40px; height:40px; border-radius:10px; background:#fff7ed; color:#fe5f04; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.pivot-acc-title-wrap { min-width:0; flex:1; }
.pivot-acc-title { font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.pivot-cat-badge { font-size:10px; font-weight:800; background:#e2e8f0; color:#475569; padding:2px 7px; border-radius:5px; letter-spacing:.04em; }
.pivot-acc-meta { font-size:12px; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

.pivot-acc-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
.pivot-pill { padding:5px 11px; border-radius:8px; font-size:12px; font-weight:700; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
.pivot-pill-bills { background:#f1f5f9; color:#475569; }
.pivot-pill-fixed { background:#e0e7ff; color:#4338ca; }
.pivot-pill-var { background:#fef3c7; color:#b45309; }
.pivot-pill-total { background:#0f172a; color:#ffffff; font-size:13px; font-weight:800; }
.pivot-share-wrap { display:flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:#475569; min-width:90px; }
.pivot-share-bar { width:50px; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden; }
.pivot-share-fill { height:100%; background:linear-gradient(90deg, #fe5f04, #ff7c30); }

/* Accordion Body */
.pivot-accordion-body { display:none; padding:20px; background:#ffffff; flex-direction:column; gap:18px; border-top:1px solid #f1f5f9; animation:fadeIn .2s ease; }
.pivot-accordion-item.is-open > .pivot-accordion-body { display:flex !important; }
@keyframes fadeIn { from{opacity:0; transform:translateY(-4px);} to{opacity:1; transform:translateY(0);} }

/* Subcategory table inside accordion */
.pivot-sub-section { background:#ffffff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); }
.pivot-sec-header { padding:10px 16px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:12px; font-weight:800; color:#334155; text-transform:uppercase; letter-spacing:.05em; display:flex; align-items:center; justify-content:space-between; }

.pivot-inner-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.pivot-inner-tbl th { background:#f8fafc; padding:10px 14px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#475569; border-bottom:1px solid #e2e8f0; text-align:left; white-space:nowrap; }
.pivot-inner-tbl td { padding:10px 14px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.pivot-inner-tbl tr:last-child td { border-bottom:none; }
.pivot-inner-tbl tr:hover td { background:#fafbfc; }
.pivot-inner-tbl tfoot td { background:#f8fafc; font-weight:800; border-top:2px solid #e2e8f0; color:#0f172a; padding:11px 14px; }

/* Expense items inside accordion */
.pivot-exp-section { background:#ffffff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.02); }
.type-fixed-badge { background:#e0e7ff; color:#4338ca; border:1px solid #c7d2fe; padding:2px 8px; border-radius:6px; font-size:10.5px; font-weight:700; display:inline-flex; align-items:center; gap:4px; }
.type-variable-badge { background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:2px 8px; border-radius:6px; font-size:10.5px; font-weight:700; display:inline-flex; align-items:center; gap:4px; }

/* Grand total card */
.pivot-grand-card { background:linear-gradient(135deg, #065f46, #047857); color:#fff; border-radius:14px; padding:18px 24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; box-shadow:0 4px 16px rgba(4,120,87,.2); margin:18px 20px; }
.pivot-grand-title { font-size:16px; font-weight:900; letter-spacing:.04em; text-transform:uppercase; display:flex; align-items:center; gap:10px; }
.pivot-grand-stats { display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
.pivot-grand-stat-item { text-align:right; }
.pivot-grand-stat-lbl { font-size:11px; text-transform:uppercase; letter-spacing:.05em; opacity:0.85; font-weight:700; }
.pivot-grand-stat-val { font-size:18px; font-weight:900; line-height:1.2; margin-top:2px; }

@media print {
    .pivot-topbar, .acc-filter-grid, .no-print, .pivot-acc-right .acc-btn { display:none !important; }
    .pivot-accordion-body { display:block !important; }
    .pivot-accordion-item { break-inside:avoid; border:1px solid #cbd5e1 !important; margin-bottom:12px !important; }
    .pivot-wrapper { padding:0 !important; background:#fff !important; }
}
</style>

<div class="pivot-wrapper">

    {{-- Standalone Page Topbar (Shown only if not in modal) --}}
    @if(empty($isModal))
    <div class="pivot-topbar">
        <div>
            <div style="margin-bottom:6px;">
                <a href="{{ route('accounts.expenses.index') }}" class="acc-btn acc-btn-secondary" style="padding:4px 10px; font-size:12px;">
                    <i class="bi bi-arrow-left"></i> Back to Expense List
                </a>
            </div>
            <div class="pivot-title">
                <i class="bi bi-table" style="color:#fe5f04;"></i>
                <span>Expense Pivot Report</span>
            </div>
            <div class="pivot-sub">Category & Subcategory-wise aggregation with Fixed vs Variable split and full expense breakdown</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;" class="no-print">
            <button type="button" class="acc-btn acc-btn-outline" onclick="window.print()">
                <i class="bi bi-printer"></i> Print / Export
            </button>
            <a href="{{ route('accounts.expenses.create') }}" class="acc-btn acc-btn-primary">
                <i class="bi bi-plus-lg"></i> Add Expense
            </a>
        </div>
    </div>
    @endif

    {{-- KPI Cards --}}
    <div class="acc-stats-grid">
        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-currency-rupee"></i>
            </div>
            <div>
                <div class="acc-stat-label">Grand Total Expense</div>
                <div class="acc-stat-val">₹{{ number_format($grandTotalAmount, 2) }}</div>
            </div>
        </div>

        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#e0e7ff; color:#4338ca;">
                <i class="bi bi-pin-angle-fill"></i>
            </div>
            <div>
                <div class="acc-stat-label">Fixed Total ({{ $grandTotalAmount > 0 ? number_format(($grandTotalFixed / $grandTotalAmount) * 100, 1) : 0 }}%)</div>
                <div class="acc-stat-val">₹{{ number_format($grandTotalFixed, 2) }}</div>
            </div>
        </div>

        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#fef3c7; color:#d97706;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div>
                <div class="acc-stat-label">Variable Total ({{ $grandTotalAmount > 0 ? number_format(($grandTotalVariable / $grandTotalAmount) * 100, 1) : 0 }}%)</div>
                <div class="acc-stat-val">₹{{ number_format($grandTotalVariable, 2) }}</div>
            </div>
        </div>

        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-tags-fill"></i>
            </div>
            <div>
                <div class="acc-stat-label">Categories in Report</div>
                <div class="acc-stat-val">{{ count($pivotRows) }}</div>
            </div>
        </div>

        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#f1f5f9; color:#475569;">
                <i class="bi bi-collection"></i>
            </div>
            <div>
                <div class="acc-stat-label">Total Recorded Bills</div>
                <div class="acc-stat-val">{{ $grandTotalCount }}</div>
            </div>
        </div>
    </div>

    {{-- Filter Card (5 Columns Grid) --}}
    <div class="acc-card no-print">
        <form method="GET" action="{{ route('accounts.expenses.pivot') }}" id="pivotFilterForm" class="acc-filter-grid pivot-filter-form">
            @if(!empty($isModal))
                <input type="hidden" name="modal" value="1">
            @endif

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

            {{-- Col 3: Month --}}
            <div class="filter-col">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label class="filter-label" style="margin-bottom:0;">Month</label>
                    <a href="javascript:void(0)" onclick="setPivotMonth('{{ now()->addMonth()->format('Y-m') }}')" style="font-size:10px; color:#7c3aed; font-weight:800; text-decoration:none;" title="Forecast for {{ now()->addMonth()->format('M Y') }}">
                        Next: {{ now()->addMonth()->format('M Y') }} &rarr;
                    </a>
                </div>
                <input type="month" name="month" id="pivotMonthInput" value="{{ request('month') }}" class="filter-control">
            </div>

            {{-- Col 4: Category Filter --}}
            <div class="filter-col">
                <label class="filter-label">Category Filter</label>
                <select name="category_id" class="filter-control">
                    <option value="">All Categories</option>
                    @foreach($allCategories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Col 5: Expense Type --}}
            <div class="filter-col">
                <label class="filter-label">Expense Type</label>
                <select name="expense_type" class="filter-control">
                    <option value="">All (Fixed + Variable)</option>
                    <option value="Fixed" {{ request('expense_type') === 'Fixed' ? 'selected' : '' }}>Fixed Only</option>
                    <option value="Variable" {{ request('expense_type') === 'Variable' ? 'selected' : '' }}>Variable Only</option>
                </select>
            </div>

            {{-- Row 2 Filters & Actions --}}
            <div class="filter-col" style="grid-column: span 3; display:flex; align-items:center; height:38px;">
                <label style="font-size:12px; font-weight:600; color:#475569; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="checkbox" name="show_all" value="1" {{ request('show_all') ? 'checked' : '' }} onchange="submitPivotForm()">
                    <span>Include all master categories & subcategories (even with ₹0 expenses)</span>
                </label>
            </div>

            <div class="filter-col">
                <button type="submit" class="acc-btn acc-btn-primary" style="width:100%; justify-content:center; height:38px;">
                    <i class="bi bi-funnel-fill"></i> Apply Filter
                </button>
            </div>

            <div class="filter-col">
                <button type="button" onclick="resetPivotFilters()" class="acc-btn acc-btn-secondary" style="width:100%; justify-content:center; height:38px;">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
            </div>
        </form>

        {{-- Future Month Forecast Notice --}}
        @if(request('month') && request('month') >= now()->addMonth()->format('Y-m'))
            @php $futureCarbon = \Carbon\Carbon::createFromFormat('Y-m', request('month')); @endphp
            <div style="background: linear-gradient(135deg, #f5f3ff, #ede9fe); border-top: 1px solid #ddd6fe; padding: 12px 20px; display:flex; align-items:center; gap:12px;">
                <i class="bi bi-magic" style="color:#7c3aed; font-size:18px;"></i>
                <div style="font-size:12px; color:#6d28d9;">
                    <strong>{{ $futureCarbon->format('F Y') }} Forecast Report:</strong> Active recurring fixed expenses are automatically calculated and projected into category & subcategory breakdown totals below.
                </div>
            </div>
        @endif
    </div>

    {{-- Expense Pivot Aggregation Card with Category-wise Accordion --}}
    <div class="acc-card">
        {{-- Section Header & Quick Controls --}}
        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#fff; flex-wrap:wrap; gap:12px;">
            <div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:16px; font-weight:800; color:#0f172a;">Expense Pivot Aggregation</span>
                    <span style="font-size:11px; font-weight:800; background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:999px;">
                        Category Accordion
                    </span>
                </div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    Showing <strong>{{ count($pivotRows) }}</strong> categories &bull; Click any category accordion to view subcategory-wise breakdown aggregation
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;" class="no-print">
                <div style="position:relative; width:220px;">
                    <i class="bi bi-search" style="position:absolute; left:10px; top:10px; color:#94a3b8; font-size:12px;"></i>
                    <input type="text" placeholder="Search category..." oninput="filterPivotAccordions(this.value)" class="filter-control" style="padding-left:30px; height:34px; font-size:12px; border-radius:6px;">
                </div>
                <button type="button" class="acc-btn acc-btn-outline acc-btn-sm" onclick="expandAllPivotAccordions()" title="Expand all categories">
                    <i class="bi bi-arrows-expand"></i> Expand All
                </button>
                <button type="button" class="acc-btn acc-btn-outline acc-btn-sm" onclick="collapseAllPivotAccordions()" title="Collapse all categories">
                    <i class="bi bi-arrows-collapse"></i> Collapse All
                </button>
            </div>
        </div>

        {{-- Accordion Items Container --}}
        <div class="pivot-accordion-container" id="pivotAccordionContainer">
            @php $catIndex = 1; @endphp
            @forelse($pivotRows as $row)
                @php
                    $catShare = $grandTotalAmount > 0 ? ($row['total'] / $grandTotalAmount) * 100 : 0;
                    $searchKeywords = strtolower($row['category_name'] . ' ' . ($row['category_code'] ?? ''));
                    foreach($row['subcategories'] as $sub) {
                        $searchKeywords .= ' ' . strtolower($sub['name'] . ' ' . ($sub['code'] ?? ''));
                    }
                @endphp

                <div class="pivot-accordion-item" data-search="{{ $searchKeywords }}" id="accordion-cat-{{ $row['category_id'] ?? 'unlinked' }}">
                    {{-- Accordion Header (Clickable) --}}
                    <div class="pivot-accordion-header" onclick="togglePivotAccordion(this)">
                        <div class="pivot-acc-left">
                            <span class="pivot-acc-chevron"><i class="bi bi-chevron-right"></i></span>
                            <div class="pivot-acc-icon">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                            <div class="pivot-acc-title-wrap">
                                <div class="pivot-acc-title">
                                    <span>#{{ $catIndex++ }} {{ $row['category_name'] }}</span>
                                    @if(!empty($row['category_code']))
                                        <span class="pivot-cat-badge">{{ $row['category_code'] }}</span>
                                    @endif
                                </div>
                                <div class="pivot-acc-meta">
                                    <span><i class="bi bi-diagram-3"></i> {{ count($row['subcategories']) }} {{ count($row['subcategories']) === 1 ? 'Subcategory' : 'Subcategories' }}</span>
                                    <span>&bull;</span>
                                    <span><i class="bi bi-receipt"></i> {{ $row['count'] }} {{ $row['count'] === 1 ? 'Bill' : 'Bills' }}</span>
                                    <span>&bull;</span>
                                    <span class="pivot-acc-hint" style="color:#fe5f04; font-weight:700; display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                                        <i class="bi bi-chevron-down"></i> <span class="pivot-hint-text">Click to view breakdown</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="pivot-acc-right">
                            <div class="pivot-pill pivot-pill-bills" title="Recorded Bills">
                                <i class="bi bi-receipt"></i>
                                <span>{{ $row['count'] }} Bills</span>
                            </div>
                            <div class="pivot-pill pivot-pill-fixed" title="Fixed Expenses">
                                <span style="font-weight:600; opacity:0.8;">Fixed:</span>
                                <span>₹{{ number_format($row['fixed'], 2) }}</span>
                            </div>
                            <div class="pivot-pill pivot-pill-var" title="Variable Expenses">
                                <span style="font-weight:600; opacity:0.8;">Var:</span>
                                <span>₹{{ number_format($row['variable'], 2) }}</span>
                            </div>
                            <div class="pivot-pill pivot-pill-total" title="Category Total">
                                <span>₹{{ number_format($row['total'], 2) }}</span>
                            </div>
                            <div class="pivot-share-wrap" title="Share of Grand Total">
                                <div class="pivot-share-bar">
                                    <div class="pivot-share-fill" style="width:{{ min(100, $catShare) }}%;"></div>
                                </div>
                                <span>{{ number_format($catShare, 1) }}%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Accordion Body (Collapsible Content) --}}
                    <div class="pivot-accordion-body">

                        {{-- Section 1: Subcategory Aggregation Breakdown --}}
                        <div class="pivot-sub-section">
                            <div class="pivot-sec-header">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <i class="bi bi-diagram-3-fill" style="color:#fe5f04;"></i>
                                    <span>Subcategory Breakdown Aggregation</span>
                                </div>
                                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:none;">
                                    {{ count($row['subcategories']) }} Subcategory {{ count($row['subcategories']) === 1 ? 'record' : 'records' }}
                                </div>
                            </div>

                            @if(!empty($row['subcategories']) && count($row['subcategories']) > 0)
                            <div style="overflow-x:auto;">
                                <table class="pivot-inner-tbl">
                                    <thead>
                                        <tr>
                                            <th style="width:30px;">#</th>
                                            <th>Subcategory</th>
                                            <th style="width:100px;">Code</th>
                                            <th style="text-align:center; width:90px;">Bills Count</th>
                                            <th style="text-align:right; width:130px;">Fixed Cost (₹)</th>
                                            <th style="text-align:right; width:130px;">Variable Cost (₹)</th>
                                            <th style="text-align:right; width:150px;">Total Amount (₹)</th>
                                            <th style="text-align:right; width:100px;">% of Category</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $subIdx = 1; @endphp
                                        @foreach($row['subcategories'] as $sub)
                                            @php
                                                $subOfCatPercent = $row['total'] > 0 ? ($sub['total'] / $row['total']) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td style="color:#94a3b8; font-weight:700;">{{ $subIdx++ }}</td>
                                                <td>
                                                    <div style="font-weight:700; color:#1e293b; display:flex; align-items:center; gap:6px;">
                                                        <i class="bi bi-arrow-return-right" style="color:#94a3b8; font-size:11px;"></i>
                                                        <span>{{ $sub['name'] }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if(!empty($sub['code']))
                                                        <span class="pivot-cat-badge">{{ $sub['code'] }}</span>
                                                    @else
                                                        <span style="color:#cbd5e1;">—</span>
                                                    @endif
                                                </td>
                                                <td style="text-align:center; font-weight:700; color:#475569;">
                                                    {{ $sub['count'] }}
                                                </td>
                                                <td style="text-align:right; color:#4338ca; font-weight:700; font-variant-numeric:tabular-nums;">
                                                    ₹{{ number_format($sub['fixed'], 2) }}
                                                </td>
                                                <td style="text-align:right; color:#b45309; font-weight:700; font-variant-numeric:tabular-nums;">
                                                    ₹{{ number_format($sub['variable'], 2) }}
                                                </td>
                                                <td style="text-align:right; font-weight:800; color:#0f172a; font-variant-numeric:tabular-nums;">
                                                    ₹{{ number_format($sub['total'], 2) }}
                                                </td>
                                                <td style="text-align:right; font-weight:700; color:#64748b;">
                                                    {{ number_format($subOfCatPercent, 1) }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" style="text-transform:uppercase; letter-spacing:.04em;">Subtotal ({{ $row['category_name'] }})</td>
                                            <td style="text-align:center;">{{ $row['count'] }}</td>
                                            <td style="text-align:right; color:#4338ca;">₹{{ number_format($row['fixed'], 2) }}</td>
                                            <td style="text-align:right; color:#b45309;">₹{{ number_format($row['variable'], 2) }}</td>
                                            <td style="text-align:right; font-size:13.5px; color:#0f172a;">₹{{ number_format($row['total'], 2) }}</td>
                                            <td style="text-align:right;">100.0%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @else
                                <div style="padding:20px; text-align:center; color:#94a3b8; font-size:12px;">
                                    No subcategories configured for this category.
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            @empty
                <div style="background:#fff; border-radius:14px; padding:40px; text-align:center; color:#94a3b8; border:1px solid #e2e8f0;">
                    <i class="bi bi-table" style="font-size:36px; display:block; margin-bottom:10px; opacity:0.6;"></i>
                    <div style="font-size:16px; font-weight:800; color:#334155; margin-bottom:4px;">No expense categories found</div>
                    <div>No expense records found matching the specified filters.</div>
                    <div style="margin-top:14px;">
                        <button type="button" onclick="resetPivotFilters()" class="acc-btn acc-btn-outline" style="font-size:12px;">
                            Clear All Filters
                        </button>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Grand Total Bottom Summary Card --}}
        @if(!empty($pivotRows))
        <div class="pivot-grand-card">
            <div class="pivot-grand-title">
                <i class="bi bi-calculator-fill" style="font-size:22px;"></i>
                <span>GRAND TOTAL EXPENSE SUMMARY</span>
            </div>
            <div class="pivot-grand-stats">
                <div class="pivot-grand-stat-item">
                    <div class="pivot-grand-stat-lbl">Total Recorded Bills</div>
                    <div class="pivot-grand-stat-val">{{ $grandTotalCount }}</div>
                </div>
                <div class="pivot-grand-stat-item">
                    <div class="pivot-grand-stat-lbl">Fixed Expense Total</div>
                    <div class="pivot-grand-stat-val">₹{{ number_format($grandTotalFixed, 2) }}</div>
                </div>
                <div class="pivot-grand-stat-item">
                    <div class="pivot-grand-stat-lbl">Variable Expense Total</div>
                    <div class="pivot-grand-stat-val">₹{{ number_format($grandTotalVariable, 2) }}</div>
                </div>
                <div class="pivot-grand-stat-item" style="border-left:1px solid rgba(255,255,255,0.3); padding-left:18px;">
                    <div class="pivot-grand-stat-lbl">Total Aggregation Amount</div>
                    <div class="pivot-grand-stat-val" style="font-size:22px; color:#fef08a;">₹{{ number_format($grandTotalAmount, 2) }}</div>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>

<script>
// Category Accordion Toggle
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

// Expand All
window.expandAllPivotAccordions = function() {
    document.querySelectorAll('.pivot-accordion-item').forEach(item => {
        item.classList.add('is-open');
        const body = item.querySelector('.pivot-accordion-body');
        if (body) body.style.display = 'flex';
        const hintText = item.querySelector('.pivot-hint-text');
        if (hintText) hintText.textContent = 'Click to hide breakdown';
    });
};

// Collapse All
window.collapseAllPivotAccordions = function() {
    document.querySelectorAll('.pivot-accordion-item').forEach(item => {
        item.classList.remove('is-open');
        const body = item.querySelector('.pivot-accordion-body');
        if (body) body.style.display = 'none';
        const hintText = item.querySelector('.pivot-hint-text');
        if (hintText) hintText.textContent = 'Click to view breakdown';
    });
};

// Live Search Filter for Category Accordions
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

// Quick Month Setter
window.setPivotMonth = function(monthStr) {
    const input = document.getElementById('pivotMonthInput');
    if (input) {
        input.value = monthStr;
        window.submitPivotForm();
    }
};

// Submit Pivot Filter
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

// Reset Pivot Filters
window.resetPivotFilters = function() {
    if (typeof openExpensePivotModal === 'function' && document.getElementById('expensePivotModal')?.classList.contains('active')) {
        openExpensePivotModal("{{ route('accounts.expenses.pivot') }}?modal=1");
    } else {
        window.location.href = "{{ route('accounts.expenses.pivot') }}";
    }
};
</script>
