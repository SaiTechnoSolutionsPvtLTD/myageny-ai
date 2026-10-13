@extends('layouts.app')

@section('title', 'Expense Pivot Report - Accounts')

@push('styles')
<style>
.acc-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.acc-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); flex-wrap:wrap; gap:12px; }
.acc-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; display:flex; align-items:center; gap:10px; }
.acc-sub { font-size:13px; color:#64748b; }

.acc-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acc-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acc-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acc-btn-secondary { background:#f1f5f9; color:#334155; }
.acc-btn-secondary:hover { background:#e2e8f0; }
.acc-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acc-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

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

/* Pivot Table */
.pivot-tbl { width:100%; border-collapse:collapse; }
.pivot-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#475569; padding:13px 18px; border-bottom:2px solid #cbd5e1; background:#f8fafc; text-align:left; white-space:nowrap; }
.pivot-tbl td { padding:12px 18px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

/* Category master row */
.pivot-cat-row { background:#f8fafc; font-weight:800; border-top:1px solid #e2e8f0; }
.pivot-cat-row td { color:#0f172a; font-size:13.5px; border-bottom:1px solid #e2e8f0; padding-top:14px; padding-bottom:14px; }
.pivot-cat-title { display:flex; align-items:center; gap:8px; }
.pivot-cat-badge { font-size:10px; font-weight:700; background:#e2e8f0; color:#475569; padding:2px 6px; border-radius:4px; }

/* Subcategory row */
.pivot-subcat-row td { background:#fff; font-size:13px; }
.pivot-subcat-row:hover td { background:#fcfcfd; }
.pivot-subcat-name { padding-left:26px; display:flex; align-items:center; gap:6px; color:#334155; }
.pivot-subcat-bullet { color:#94a3b8; font-size:14px; }

/* Grand total row */
.pivot-grand-row { background:#ecfdf5 !important; border-top:2px solid #059669; }
.pivot-grand-row td { font-weight:900; font-size:15px; color:#065f46; padding:16px 18px; }

.amt-cell { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.amt-fixed { color:#4338ca; font-weight:600; }
.amt-var { color:#b45309; font-weight:600; }
.amt-total { color:#0f172a; font-weight:800; }

.percent-bar-wrap { width:70px; background:#e2e8f0; height:6px; border-radius:3px; overflow:hidden; display:inline-block; vertical-align:middle; margin-right:6px; }
.percent-bar-fill { height:100%; background:linear-gradient(90deg, #fe5f04, #ff7c30); }

@media print {
    .acc-topbar, .acc-filter-grid, .sidebar, header, .no-print { display:none !important; }
    .acc-page { padding:0 !important; background:#fff !important; }
    .acc-card { box-shadow:none !important; border:none !important; }
}
</style>
@endpush

@section('content')
<div class="acc-page">

    {{-- Topbar --}}
    <div class="acc-topbar">
        <div>
            <div style="margin-bottom:6px;">
                <a href="{{ route('accounts.expenses.index') }}" class="acc-btn acc-btn-secondary" style="padding:4px 10px; font-size:12px;">
                    <i class="bi bi-arrow-left"></i> Back to Expense List
                </a>
            </div>
            <div class="acc-title">
                <i class="bi bi-table" style="color:#fe5f04;"></i>
                <span>Expense Pivot Report</span>
            </div>
            <div class="acc-sub">Category & Subcategory-wise aggregation with Fixed vs Variable split and total amount breakdown</div>
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
        <form method="GET" action="{{ route('accounts.expenses.pivot') }}" class="acc-filter-grid">
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
                    <a href="{{ route('accounts.expenses.pivot', ['month' => now()->addMonth()->format('Y-m')]) }}" style="font-size:10px; color:#7c3aed; font-weight:800; text-decoration:none;" title="Forecast for {{ now()->addMonth()->format('M Y') }}">
                        Next: {{ now()->addMonth()->format('M Y') }} &rarr;
                    </a>
                </div>
                <input type="month" name="month" value="{{ request('month') }}" class="filter-control">
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

            {{-- Row 2 Filters & Actions (5 Columns) --}}
            <div class="filter-col" style="grid-column: span 3; display:flex; align-items:center; height:38px;">
                <label style="font-size:12px; font-weight:600; color:#475569; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="checkbox" name="show_all" value="1" {{ request('show_all') ? 'checked' : '' }} onchange="this.form.submit()">
                    <span>Include all master categories & subcategories (even with ₹0 expenses)</span>
                </label>
            </div>

            <div class="filter-col">
                <button type="submit" class="acc-btn acc-btn-primary" style="width:100%; justify-content:center; height:38px;">
                    <i class="bi bi-funnel-fill"></i> Apply Filter
                </button>
            </div>

            <div class="filter-col">
                <a href="{{ route('accounts.expenses.pivot') }}" class="acc-btn acc-btn-secondary" style="width:100%; justify-content:center; height:38px; text-decoration:none;">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>

        {{-- Future Month Forecast Notice in Pivot --}}
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

    {{-- Pivot Table Card --}}
    <div class="acc-card">
        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#fff;">
            <div>
                <span style="font-size:15px; font-weight:800; color:#0f172a;">Expense Pivot Aggregation</span>
                <span style="font-size:12px; color:#64748b; margin-left:8px;">
                    Showing {{ count($pivotRows) }} categories
                    @if(request('date_from') || request('date_to') || request('month'))
                        (Filtered Date Range)
                    @endif
                </span>
            </div>
            <div style="font-size:12px; color:#64748b;">
                Values in Indian Rupees (₹)
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="pivot-tbl">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Category / Subcategory</th>
                        <th style="text-align:center; width:90px;">Bills Count</th>
                        <th style="text-align:right; width:150px;">Fixed Expense (₹)</th>
                        <th style="text-align:right; width:150px;">Variable Expense (₹)</th>
                        <th style="text-align:right; width:170px;">Total Amount (₹)</th>
                        <th style="text-align:right; width:130px;">% Share</th>
                    </tr>
                </thead>
                <tbody>
                    @php $catIndex = 1; @endphp
                    @forelse($pivotRows as $row)
                        @php
                            $catShare = $grandTotalAmount > 0 ? ($row['total'] / $grandTotalAmount) * 100 : 0;
                        @endphp
                        {{-- Category Header Row --}}
                        <tr class="pivot-cat-row">
                            <td style="font-weight:800;">{{ $catIndex++ }}</td>
                            <td>
                                <div class="pivot-cat-title">
                                    <i class="bi bi-folder2-open" style="color:#fe5f04; font-size:15px;"></i>
                                    <span>{{ $row['category_name'] }}</span>
                                    @if(!empty($row['category_code']))
                                        <span class="pivot-cat-badge">{{ $row['category_code'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align:center; font-weight:800;">
                                {{ $row['count'] }}
                            </td>
                            <td class="amt-cell amt-fixed" style="font-weight:800;">
                                ₹{{ number_format($row['fixed'], 2) }}
                            </td>
                            <td class="amt-cell amt-var" style="font-weight:800;">
                                ₹{{ number_format($row['variable'], 2) }}
                            </td>
                            <td class="amt-cell amt-total" style="font-size:14px; color:#0f172a;">
                                ₹{{ number_format($row['total'], 2) }}
                            </td>
                            <td class="amt-cell" style="font-weight:800;">
                                <div class="percent-bar-wrap">
                                    <div class="percent-bar-fill" style="width:{{ min(100, $catShare) }}%;"></div>
                                </div>
                                {{ number_format($catShare, 1) }}%
                            </td>
                        </tr>

                        {{-- Subcategory Rows --}}
                        @if(!empty($row['subcategories']))
                            @foreach($row['subcategories'] as $sub)
                                @php
                                    $subShare = $grandTotalAmount > 0 ? ($sub['total'] / $grandTotalAmount) * 100 : 0;
                                @endphp
                                <tr class="pivot-subcat-row">
                                    <td></td>
                                    <td>
                                        <div class="pivot-subcat-name">
                                            <span class="pivot-subcat-bullet">↳</span>
                                            <span>{{ $sub['name'] }}</span>
                                            @if(!empty($sub['code']))
                                                <span style="font-size:10px; color:#94a3b8;">({{ $sub['code'] }})</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="text-align:center; color:#64748b; font-size:12px;">
                                        {{ $sub['count'] }}
                                    </td>
                                    <td class="amt-cell amt-fixed" style="font-size:12.5px;">
                                        ₹{{ number_format($sub['fixed'], 2) }}
                                    </td>
                                    <td class="amt-cell amt-var" style="font-size:12.5px;">
                                        ₹{{ number_format($sub['variable'], 2) }}
                                    </td>
                                    <td class="amt-cell amt-total" style="font-size:13px; font-weight:700;">
                                        ₹{{ number_format($sub['total'], 2) }}
                                    </td>
                                    <td class="amt-cell" style="font-size:12px; color:#64748b;">
                                        {{ number_format($subShare, 1) }}%
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">
                            <i class="bi bi-table" style="font-size:36px; display:block; margin-bottom:8px; opacity:0.6;"></i>
                            No expense records found matching the specified filters.
                            <div style="margin-top:10px;">
                                <a href="{{ route('accounts.expenses.pivot', ['show_all' => 1]) }}" class="acc-btn acc-btn-outline" style="font-size:12px;">
                                    View All Master Categories
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                    {{-- Grand Total Row --}}
                    @if(!empty($pivotRows))
                    <tr class="pivot-grand-row">
                        <td colspan="2" style="font-weight:900;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <i class="bi bi-calculator-fill" style="font-size:17px;"></i>
                                <span>GRAND TOTAL</span>
                            </div>
                        </td>
                        <td style="text-align:center; font-weight:900;">
                            {{ $grandTotalCount }}
                        </td>
                        <td class="amt-cell" style="color:#3730a3; font-weight:900;">
                            ₹{{ number_format($grandTotalFixed, 2) }}
                        </td>
                        <td class="amt-cell" style="color:#92400e; font-weight:900;">
                            ₹{{ number_format($grandTotalVariable, 2) }}
                        </td>
                        <td class="amt-cell" style="color:#065f46; font-weight:900; font-size:16px;">
                            ₹{{ number_format($grandTotalAmount, 2) }}
                        </td>
                        <td class="amt-cell" style="font-weight:900; color:#065f46;">
                            100.0%
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
