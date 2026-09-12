@extends('layouts.app')

@section('title', 'CRM Outstanding Report - myAgenci.ai')

@push('styles')
<style>
.crm-out-page { min-height: 100%; padding: 24px; background: linear-gradient(180deg, #f7f5f1 0%, #f3f6fa 100%); }
.crm-out-shell { max-width: 1440px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
.crm-out-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px; border: 1px solid #e7e5e4; border-radius: 24px; background: linear-gradient(135deg, #fffbeb 0%, #ffffff 58%, #fff7ed 100%); box-shadow: 0 14px 38px rgba(15, 23, 42, 0.05); }
.crm-out-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: #fef3c7; color: #b45309; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-out-title { margin: 14px 0 6px; font-size: 30px; font-weight: 800; color: #111827; }
.crm-out-subtitle { margin: 0; font-size: 14px; line-height: 1.7; color: #6b7280; max-width: 780px; }
.crm-out-actions { display: flex; align-items: center; gap: 10px; }
.crm-out-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; color: #111827; font-size: 13px; font-weight: 800; text-decoration: none; cursor: pointer; }
.crm-out-btn:hover { border-color: #fde68a; background: #fffbeb; color: #b45309; }
.crm-out-btn-primary { border-color: transparent; background: linear-gradient(135deg, #d97706, #f59e0b); color: #fff; box-shadow: 0 6px 18px rgba(217, 119, 6, 0.22); }
.crm-out-btn-primary:hover { color: #fff; border-color: transparent; background: linear-gradient(135deg, #b45309, #d97706); }
.crm-out-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.crm-out-stat { position: relative; overflow: hidden; padding: 20px; border-radius: 22px; border: 1px solid #e7e5e4; background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(248,250,252,.98) 100%); box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05); }
.crm-out-stat::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 5px; border-radius: 999px; background: var(--stat-accent, #f59e0b); }
.crm-out-stat-label { margin-top: 12px; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
.crm-out-stat-value { margin-top: 10px; font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: -.03em; }
.crm-out-stat-note { margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(226, 232, 240, .9); font-size: 12px; color: #64748b; }
.crm-out-stat-chip { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,.88); color: var(--stat-accent, #f59e0b); border: 1px solid color-mix(in srgb, var(--stat-accent, #f59e0b) 22%, white 78%); font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-out-tabs { display: inline-flex; align-items: center; gap: 10px; padding: 10px; border-radius: 20px; background: linear-gradient(135deg, #fffbeb 0%, #ffffff 58%, #fff7ed 100%); border: 1px solid #fde68a; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255,255,255,.9); width: fit-content; }
.crm-out-tab { padding: 12px 22px; border-radius: 14px; border: 1px solid transparent; background: rgba(255,255,255,.72); color: #475569; font-size: 13px; font-weight: 900; transition: all .18s ease; cursor: pointer; }
.crm-out-tab:hover { border-color: #fde68a; background: #ffffff; color: #b45309; transform: translateY(-1px); }
.crm-out-tab.is-active { background: linear-gradient(135deg, #d97706, #f59e0b); color: #fff; border-color: #b45309; box-shadow: 0 10px 22px rgba(217, 119, 6, 0.24); }
.crm-out-panel { display: none; flex-direction: column; gap: 18px; }
.crm-out-panel.is-active { display: flex; }
.crm-out-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-out-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #f1f5f9; }
.crm-out-card-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-out-card-subtitle { margin-top: 3px; font-size: 12px; color: #6b7280; }
.crm-out-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 11px; font-weight: 800; }
.crm-out-body { padding: 18px; display: flex; flex-direction: column; gap: 14px; }
.crm-out-form { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.crm-out-field { display: flex; flex-direction: column; gap: 7px; }
.crm-out-label { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
.crm-out-input, .crm-out-select { width: 100%; padding: 11px 13px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 13px; outline: none; }
.crm-out-input:focus, .crm-out-select:focus { border-color: #f59e0b; box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12); background: #fff; }
.crm-out-form-actions { display: flex; align-items: flex-end; gap: 10px; grid-column: 1 / -1; }
.crm-out-table-wrap { overflow-x: auto; }
.crm-out-table { width: 100%; border-collapse: collapse; min-width: 1300px; }
.crm-out-table th { padding: 12px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; white-space: nowrap; }
.crm-out-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #111827; vertical-align: middle; }
.crm-out-table tbody tr:hover td { background: #fffdfa; }
.crm-out-code { font-family: Consolas, monospace; font-size: 12px; color: #64748b; white-space: nowrap; font-weight: 700; }
.crm-out-name { font-weight: 800; color: #111827; }
.crm-out-muted { color: #6b7280; font-size: 12px; }
.crm-out-money { font-weight: 800; white-space: nowrap; }
.crm-out-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; white-space: nowrap; border: 1px solid transparent; }
.crm-out-badge-unpaid { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.crm-out-badge-partial { background: #fffbeb; color: #b45309; border-color: #fde68a; }
.crm-out-badge-cleared { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
.crm-out-badge-count { background: #f1f5f9; color: #475569; border-color: #e2e8f0; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.crm-out-empty { padding: 56px 20px; text-align: center; color: #6b7280; }
.crm-out-empty strong { display: block; margin-bottom: 8px; font-size: 18px; color: #111827; }
.crm-out-analytics-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.crm-out-analytics-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 18px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-out-analytics-card.full { grid-column: 1 / -1; }
.crm-out-analytics-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.crm-out-analytics-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-out-analytics-subtitle { margin-top: 4px; font-size: 12px; color: #6b7280; }
.crm-out-analytics-chart { position: relative; min-height: 290px; }
.crm-out-analytics-chart.tall { min-height: 340px; }
.crm-out-breakdown-list { display: flex; flex-direction: column; gap: 10px; margin-top: 12px; }
.crm-out-breakdown-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f8fafc; border-radius: 12px; font-size: 13px; font-weight: 700; }

@media (max-width: 1280px) {
    .crm-out-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .crm-out-form, .crm-out-analytics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .crm-out-page { padding: 18px; }
    .crm-out-topbar, .crm-out-head { flex-direction: column; align-items: flex-start; }
    .crm-out-stats, .crm-out-form, .crm-out-analytics-grid { grid-template-columns: 1fr; }
    .crm-out-actions, .crm-out-form-actions { flex-wrap: wrap; }
    .crm-out-tabs { width: 100%; flex-wrap: wrap; }
}

/* Select2 Custom Design */
.select2-container--default .select2-selection--single.out-select2-selection {
    height: auto;
    padding: 6px 4px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #0f172a;
    font-size: 13px;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single.out-select2-selection .select2-selection__rendered {
    color: #0f172a;
    padding-left: 8px;
    padding-right: 20px;
}
.select2-container--default .select2-selection--single.out-select2-selection .select2-selection__arrow {
    height: 100%;
    right: 8px;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single.out-select2-selection,
.select2-container--default .select2-selection--single.out-select2-selection {
    border-color: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12);
    background: #fff;
}
</style>
@endpush

@section('content')
@php
    $hasCustomFilters =
        request()->filled('branch_id')
        || request()->filled('assigned_to')
        || request()->filled('payment_status')
        || request()->filled('product_id')
        || request()->filled('search')
        || (request()->has('quick_date') && request('quick_date') !== 'month')
        || (request()->has('date_from') && request('date_from') !== $defaultFromDate)
        || (request()->has('date_to') && request('date_to') !== $defaultToDate);
@endphp
<div class="crm-out-page">
    <div class="crm-out-shell">
        <!-- Topbar -->
        <div class="crm-out-topbar">
            <div>
                <span class="crm-out-kicker">CRM Reports</span>
                <h1 class="crm-out-title">Outstanding Report</h1>
                <p class="crm-out-subtitle">Track lead-wise pending balances, collections, product deal values, and outstanding dues across branches and sales employees.</p>
            </div>
            <div class="crm-out-actions">
                <a href="{{ route('reports.crm.outstanding.export', request()->query()) }}" class="crm-out-btn crm-out-btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('reports.crm.index') }}" class="crm-out-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <!-- Summary Metric Cards -->
        <div class="crm-out-stats">
            <div class="crm-out-stat" style="--stat-accent:#2563eb;">
                <span class="crm-out-stat-chip">Leads</span>
                <div class="crm-out-stat-label">Total Leads</div>
                <div class="crm-out-stat-value">{{ number_format($summary['total_leads']) }}</div>
                <div class="crm-out-stat-note">Distinct leads in the selected filter period.</div>
            </div>
            <div class="crm-out-stat" style="--stat-accent:#0ea5e9;">
                <span class="crm-out-stat-chip">Total Contract</span>
                <div class="crm-out-stat-label">Total Deal Value</div>
                <div class="crm-out-stat-value">Rs {{ number_format($summary['total_deal_value'], 2) }}</div>
                <div class="crm-out-stat-note">Cumulative value of all lead products.</div>
            </div>
            <div class="crm-out-stat" style="--stat-accent:#16a34a;">
                <span class="crm-out-stat-chip">Collected</span>
                <div class="crm-out-stat-label">Amount Received</div>
                <div class="crm-out-stat-value">Rs {{ number_format($summary['total_received_value'], 2) }}</div>
                <div class="crm-out-stat-note">Total payments recorded against these leads.</div>
            </div>
            <div class="crm-out-stat" style="--stat-accent:#dc2626;">
                <span class="crm-out-stat-chip">Pending Dues</span>
                <div class="crm-out-stat-label">Total Outstanding</div>
                <div class="crm-out-stat-value" style="color: #dc2626;">Rs {{ number_format($summary['total_outstanding_value'], 2) }}</div>
                <div class="crm-out-stat-note">Net unpaid balance remaining across leads.</div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="crm-out-tabs" id="crmOutstandingTabs">
            <button type="button" class="crm-out-tab is-active" data-tab-target="outstanding-data-panel">Data Sheet</button>
            <button type="button" class="crm-out-tab" data-tab-target="outstanding-analytics-panel">Analytics</button>
        </div>

        <!-- Filter Card -->
        <div class="crm-out-card">
            <div class="crm-out-head">
                <div>
                    <div class="crm-out-card-title">Filter Outstanding Data</div>
                    <div class="crm-out-card-subtitle">Filter by quick date presets, custom dates, branch, employee, and payment status.</div>
                </div>
                <div class="crm-out-chip">{{ $reportRows->total() }} results</div>
            </div>
            <div class="crm-out-body">
                <form method="GET" action="{{ route('reports.crm.outstanding') }}" class="crm-out-form" id="outstandingReportForm">
                    <input type="hidden" name="tab" id="crmActiveTabInput" value="{{ request('tab', 'outstanding-data-panel') }}">
                    
                    <!-- Quick Dates -->
                    <div class="crm-out-field">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                            <label class="crm-out-label" for="quick_date_select" style="margin-bottom:0;">Quick Dates</label>
                            <span id="crmQuickDateRange" style="font-size:11px;font-weight:700;color:#d97706;"></span>
                        </div>
                        <select id="quick_date_select" name="quick_date" class="crm-out-select" onchange="onCrmQuickDateChange(this.value)">
                            <option value="today" {{ request('quick_date') == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ request('quick_date') == 'week' ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ request('quick_date', 'month') == 'month' ? 'selected' : '' }}>This Month</option>
                            <option value="quarter" {{ request('quick_date') == 'quarter' ? 'selected' : '' }}>This Quarter</option>
                            <option value="year" {{ request('quick_date') == 'year' ? 'selected' : '' }}>This Year</option>
                            <option value="all" {{ request('quick_date') == 'all' ? 'selected' : '' }}>Show All</option>
                            <option value="custom" {{ request('quick_date') == 'custom' ? 'selected' : '' }}>Custom Dates</option>
                        </select>
                    </div>

                    <!-- Custom Date From -->
                    <div class="crm-out-field" id="crmFromField" style="display: {{ request('quick_date') == 'custom' ? 'flex' : 'none' }};">
                        <label class="crm-out-label" for="date_from">Date From</label>
                        <input id="date_from" type="date" name="date_from" class="crm-out-input" value="{{ request('date_from', $defaultFromDate) }}">
                    </div>

                    <!-- Custom Date To -->
                    <div class="crm-out-field" id="crmToField" style="display: {{ request('quick_date') == 'custom' ? 'flex' : 'none' }};">
                        <label class="crm-out-label" for="date_to">Date To</label>
                        <input id="date_to" type="date" name="date_to" class="crm-out-input" value="{{ request('date_to', $defaultToDate) }}">
                    </div>

                    <!-- Branch Filter -->
                    <div class="crm-out-field">
                        <label class="crm-out-label" for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id" class="crm-out-select select2">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Employee Filter -->
                    <div class="crm-out-field">
                        <label class="crm-out-label" for="assigned_to">Assigned Employee</label>
                        <select id="assigned_to" name="assigned_to" class="crm-out-select select2">
                            <option value="">All Employees</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected((string) request('assigned_to') === (string) $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Payment Status Filter -->
                    <div class="crm-out-field">
                        <label class="crm-out-label" for="payment_status">Outstanding Status</label>
                        <select id="payment_status" name="payment_status" class="crm-out-select">
                            <option value="" {{ request('payment_status') === '' ? 'selected' : '' }}>All Records</option>
                            <option value="outstanding" {{ request('payment_status', 'outstanding') == 'outstanding' ? 'selected' : '' }}>Outstanding Only (&gt; 0)</option>
                            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid (0% Received)</option>
                            <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partially Paid</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Fully Cleared</option>
                        </select>
                    </div>

                    <!-- Product Filter -->
                    <div class="crm-out-field">
                        <label class="crm-out-label" for="product_id">Product</label>
                        <select id="product_id" name="product_id" class="crm-out-select select2">
                            <option value="">All Products</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}" @selected((string) request('product_id') === (string) $prod->id)>
                                    {{ $prod->package_name ?: $prod->product_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div class="crm-out-field">
                        <label class="crm-out-label" for="search">Search Lead / Customer</label>
                        <input id="search" type="text" name="search" class="crm-out-input" value="{{ request('search') }}" placeholder="Search company, contact, phone...">
                    </div>

                    @if(request()->filled('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif

                    <!-- Action Buttons -->
                    <div class="crm-out-form-actions">
                        <button type="submit" class="crm-out-btn crm-out-btn-primary">Apply Filters</button>
                        @if($hasCustomFilters)
                            <a href="{{ route('reports.crm.outstanding', ['reset' => 1]) }}" class="crm-out-btn">Reset</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel 1: Data Sheet -->
        <div class="crm-out-panel is-active" id="outstanding-data-panel">
            <div class="crm-out-card">
                <div class="crm-out-head">
                    <div>
                        <div class="crm-out-card-title">Lead-Wise Outstanding Data</div>
                        <div class="crm-out-card-subtitle">Showing {{ $reportRows->firstItem() ?? 0 }}-{{ $reportRows->lastItem() ?? 0 }} of {{ $reportRows->total() }} leads</div>
                    </div>
                    @if($reportRows->total() > 0)
                        <div style="display:flex;align-items:center;gap:12px;">
                            <form method="GET" action="{{ route('reports.crm.outstanding') }}" style="display:inline-flex;align-items:center;gap:6px;">
                                @foreach(request()->except(['per_page', 'page']) as $k => $v)
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endforeach
                                <span style="font-size:12px;font-weight:700;color:#64748b;">Per page:</span>
                                <select name="per_page" onchange="this.form.submit()" class="crm-out-select" style="padding:4px 8px;font-size:12px;width:auto;">
                                    @foreach([20, 50, 100] as $size)
                                        <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="crm-out-table-wrap">
                    <table class="crm-out-table">
                        <thead>
                            <tr>
                                <th>Lead ID</th>
                                <th>Company / Customer</th>
                                <th>Mobile &amp; Email</th>
                                <th>Branch</th>
                                <th>Assigned Employee</th>
                                <th>Products</th>
                                <th style="text-align:right;">Total Deal Value</th>
                                <th style="text-align:right;">Amount Received</th>
                                <th style="text-align:right;">Outstanding Balance</th>
                                <th style="text-align:center;">Status</th>
                                <th>Lead Date</th>
                                <th>Last Payment</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportRows as $row)
                                @php
                                    $dealVal = (float) ($row->total_deal_value ?? 0);
                                    $paidVal = (float) ($row->total_paid_value ?? 0);
                                    $outVal = (float) ($row->outstanding_balance ?? max(0, $dealVal - $paidVal));
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('leads.show', $row->lead_id) }}" class="crm-out-code" style="color:#d97706;text-decoration:none;" title="View Lead Details">
                                            #LD-{{ str_pad((string) $row->lead_id, 4, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="crm-out-name">{{ $row->company_name ?: ($row->contact_name ?: '-') }}</div>
                                        @if($row->company_name && $row->contact_name)
                                            <div class="crm-out-muted">{{ $row->contact_name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->mobile_number)
                                            <div><a href="tel:{{ $row->mobile_number }}" style="color:#2563eb;text-decoration:none;font-weight:700;">{{ $row->mobile_number }}</a></div>
                                        @endif
                                        @if($row->email)
                                            <div class="crm-out-muted">{{ $row->email }}</div>
                                        @elseif(!$row->mobile_number)
                                            <span class="crm-out-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="crm-out-badge crm-out-badge-count" style="background:#f8fafc;">{{ $row->branch_name ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <span style="font-weight:700;color:#334155;">{{ $row->assigned_to_name ?: 'Unassigned' }}</span>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span class="crm-out-badge crm-out-badge-count">{{ $row->total_products_count }}</span>
                                            <span style="font-size:12px;color:#475569;max-width:200px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $row->product_names }}">
                                                {{ $row->product_names ?: '-' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td style="text-align:right;" class="crm-out-money">
                                        Rs {{ number_format($dealVal, 2) }}
                                    </td>
                                    <td style="text-align:right;" class="crm-out-money" style="color:#16a34a;">
                                        Rs {{ number_format($paidVal, 2) }}
                                    </td>
                                    <td style="text-align:right;" class="crm-out-money" style="color: {{ $outVal > 0 ? '#dc2626' : '#16a34a' }};">
                                        Rs {{ number_format($outVal, 2) }}
                                    </td>
                                    <td style="text-align:center;">
                                        @if($dealVal > 0 && $paidVal >= $dealVal)
                                            <span class="crm-out-badge crm-out-badge-cleared">Cleared</span>
                                        @elseif($paidVal > 0)
                                            <span class="crm-out-badge crm-out-badge-partial">Partial</span>
                                        @else
                                            <span class="crm-out-badge crm-out-badge-unpaid">Unpaid</span>
                                        @endif
                                    </td>
                                    <td class="crm-out-muted" style="white-space:nowrap;">
                                        {{ $row->lead_date ? \Carbon\Carbon::parse($row->lead_date)->format('d M Y') : ($row->lead_created_at ? \Carbon\Carbon::parse($row->lead_created_at)->format('d M Y') : '-') }}
                                    </td>
                                    <td class="crm-out-muted" style="white-space:nowrap;">
                                        {{ $row->last_payment_date ? \Carbon\Carbon::parse($row->last_payment_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="{{ route('leads.show', $row->lead_id) }}" class="crm-out-btn" style="padding:6px 10px;font-size:11px;" title="View Lead Profile">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="crm-out-empty">
                                        <strong>No Outstanding Records Found</strong>
                                        <p>Try modifying your date filters or filter criteria.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reportRows->hasPages())
                    <div style="padding:16px 20px;border-top:1px solid #f1f5f9;">
                        {{ $reportRows->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Panel 2: Analytics -->
        <div class="crm-out-panel" id="outstanding-analytics-panel">
            <div class="crm-out-analytics-grid">
                <!-- Branch Breakdown Chart -->
                <div class="crm-out-analytics-card">
                    <div class="crm-out-analytics-head">
                        <div>
                            <div class="crm-out-analytics-title">Branch-Wise Outstanding</div>
                            <div class="crm-out-analytics-subtitle">Comparison of total deal value, received amount, and pending balance per branch.</div>
                        </div>
                    </div>
                    <div class="crm-out-analytics-chart">
                        <canvas id="branchOutstandingChart"></canvas>
                    </div>
                </div>

                <!-- Employee Breakdown Chart -->
                <div class="crm-out-analytics-card">
                    <div class="crm-out-analytics-head">
                        <div>
                            <div class="crm-out-analytics-title">Employee-Wise Outstanding</div>
                            <div class="crm-out-analytics-subtitle">Pending collections assigned to top sales executives.</div>
                        </div>
                    </div>
                    <div class="crm-out-analytics-chart">
                        <canvas id="employeeOutstandingChart"></canvas>
                    </div>
                </div>

                <!-- Status Distribution Chart & List -->
                <div class="crm-out-analytics-card full">
                    <div class="crm-out-analytics-head">
                        <div>
                            <div class="crm-out-analytics-title">Outstanding Status Distribution</div>
                            <div class="crm-out-analytics-subtitle">Breakdown of leads by Unpaid (0%), Partially Paid, and Cleared statuses.</div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns: minmax(280px, 1fr) minmax(320px, 1.2fr);gap:24px;align-items:center;">
                        <div class="crm-out-analytics-chart" style="max-height:260px;">
                            <canvas id="statusOutstandingChart"></canvas>
                        </div>
                        <div class="crm-out-breakdown-list">
                            @foreach($analytics['statuses'] as $st)
                                <div class="crm-out-breakdown-row" style="border-left: 4px solid {{ $st['color'] }};">
                                    <div>
                                        <span style="color:#0f172a;">{{ $st['label'] }}</span>
                                        <span style="margin-left:8px;font-size:11px;color:#64748b;">({{ number_format($st['count']) }} leads)</span>
                                    </div>
                                    <div style="color: {{ $st['color'] }};">
                                        Rs {{ number_format($st['amount'], 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const tabs = document.querySelectorAll('#crmOutstandingTabs .crm-out-tab');
    const tabInput = document.getElementById('crmActiveTabInput');

    function switchTab(targetId) {
        tabs.forEach(t => {
            const isTarget = t.getAttribute('data-tab-target') === targetId;
            t.classList.toggle('is-active', isTarget);
        });
        document.querySelectorAll('.crm-out-panel').forEach(p => {
            p.classList.toggle('is-active', p.id === targetId);
        });
        if (tabInput) tabInput.value = targetId;
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            switchTab(this.getAttribute('data-tab-target'));
        });
    });

    const initTab = @json(request('tab', 'outstanding-data-panel'));
    if (initTab) {
        switchTab(initTab);
    }

    // Chart.js rendering
    const analytics = @json($analytics);
    const charts = {};

    function currency(val) {
        return 'Rs ' + Number(val || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    }

    function createChart(canvasId, config) {
        const el = document.getElementById(canvasId);
        if (!el) return;
        if (charts[canvasId]) charts[canvasId].destroy();
        charts[canvasId] = new Chart(el, config);
    }

    // 1. Branch Chart
    if (analytics.branches && analytics.branches.length > 0) {
        createChart('branchOutstandingChart', {
            type: 'bar',
            data: {
                labels: analytics.branches.map(b => b.label),
                datasets: [
                    {
                        label: 'Deal Value',
                        data: analytics.branches.map(b => b.total_deal_value),
                        backgroundColor: '#38bdf8',
                        borderRadius: 6,
                    },
                    {
                        label: 'Received',
                        data: analytics.branches.map(b => b.total_paid_value),
                        backgroundColor: '#22c55e',
                        borderRadius: 6,
                    },
                    {
                        label: 'Outstanding',
                        data: analytics.branches.map(b => b.outstanding_balance),
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: ${currency(ctx.parsed.y)}`
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => currency(v) } }
                }
            }
        });
    }

    // 2. Employee Chart
    if (analytics.employees && analytics.employees.length > 0) {
        createChart('employeeOutstandingChart', {
            type: 'bar',
            data: {
                labels: analytics.employees.map(e => e.label),
                datasets: [
                    {
                        label: 'Outstanding Balance',
                        data: analytics.employees.map(e => e.outstanding_balance),
                        backgroundColor: '#ef4444',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Outstanding: ${currency(ctx.parsed.x)}`
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true, ticks: { callback: (v) => currency(v) } }
                }
            }
        });
    }

    // 3. Status Doughnut Chart
    if (analytics.statuses && analytics.statuses.length > 0) {
        createChart('statusOutstandingChart', {
            type: 'doughnut',
            data: {
                labels: analytics.statuses.map(s => s.label),
                datasets: [{
                    data: analytics.statuses.map(s => s.count),
                    backgroundColor: analytics.statuses.map(s => s.color),
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${ctx.label}: ${ctx.parsed} leads (${currency(analytics.statuses[ctx.dataIndex].amount)})`
                        }
                    }
                }
            }
        });
    }

    // Select2 init
    if (window.jQuery && window.jQuery.fn.select2) {
        $('.crm-out-select.select2').each(function() {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                allowClear: true,
                placeholder: $el.find('option:first').text(),
                width: '100%'
            });
            $el.next('.select2-container').find('.select2-selection--single').addClass('out-select2-selection');
        });
    }
})();

function calcPresetDates(val) {
    const today = new Date();
    const fmt = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    if (val === 'today') {
        const d = fmt(today);
        return { from: d, to: d };
    }
    if (val === 'week') {
        const mon = new Date(today);
        const dayOfWeek = today.getDay();
        const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        mon.setDate(today.getDate() + diff);
        const sun = new Date(mon);
        sun.setDate(mon.getDate() + 6);
        return { from: fmt(mon), to: fmt(sun) };
    }
    if (val === 'month') {
        const first = new Date(today.getFullYear(), today.getMonth(), 1);
        const last = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        return { from: fmt(first), to: fmt(last) };
    }
    if (val === 'quarter') {
        const qStartMonth = Math.floor(today.getMonth() / 3) * 3;
        const firstQ = new Date(today.getFullYear(), qStartMonth, 1);
        const lastQ = new Date(today.getFullYear(), qStartMonth + 3, 0);
        return { from: fmt(firstQ), to: fmt(lastQ) };
    }
    if (val === 'year') {
        return { from: `${today.getFullYear()}-01-01`, to: `${today.getFullYear()}-12-31` };
    }
    return { from: '', to: '' };
}

function formatDisplayDate(dStr) {
    if (!dStr) return '';
    const parts = dStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dStr;
}

function updateCrmQuickDateRangeSpan(val) {
    const span = document.getElementById('crmQuickDateRange');
    if (!span) return;
    if (val === 'all') {
        span.textContent = '';
        return;
    }
    if (val === 'custom') {
        const f = document.getElementById('date_from')?.value;
        const t = document.getElementById('date_to')?.value;
        span.textContent = (f && t) ? `${formatDisplayDate(f)} - ${formatDisplayDate(t)}` : '';
        return;
    }
    const dates = calcPresetDates(val);
    if (dates.from && dates.to) {
        span.textContent = `${formatDisplayDate(dates.from)} - ${formatDisplayDate(dates.to)}`;
    } else {
        span.textContent = '';
    }
}

function onCrmQuickDateChange(val) {
    const fromField = document.getElementById('crmFromField');
    const toField = document.getElementById('crmToField');
    const f = document.getElementById('date_from');
    const t = document.getElementById('date_to');

    if (val === 'custom') {
        if (fromField) fromField.style.display = 'flex';
        if (toField) toField.style.display = 'flex';
    } else {
        if (fromField) fromField.style.display = 'none';
        if (toField) toField.style.display = 'none';
        if (val === 'all') {
            if (f) f.value = '';
            if (t) t.value = '';
        } else {
            const dates = calcPresetDates(val);
            if (f) f.value = dates.from;
            if (t) t.value = dates.to;
        }
    }
    updateCrmQuickDateRangeSpan(val);
}

document.addEventListener('DOMContentLoaded', function() {
    const f = document.getElementById('date_from');
    const t = document.getElementById('date_to');
    const q = document.getElementById('quick_date_select');

    if (f) f.addEventListener('change', () => {
        if (q) q.value = 'custom';
        const fromField = document.getElementById('crmFromField');
        const toField = document.getElementById('crmToField');
        if (fromField) fromField.style.display = 'flex';
        if (toField) toField.style.display = 'flex';
        updateCrmQuickDateRangeSpan('custom');
    });
    if (t) t.addEventListener('change', () => {
        if (q) q.value = 'custom';
        const fromField = document.getElementById('crmFromField');
        const toField = document.getElementById('crmToField');
        if (fromField) fromField.style.display = 'flex';
        if (toField) toField.style.display = 'flex';
        updateCrmQuickDateRangeSpan('custom');
    });

    if (q && q.value !== 'all' && (!f?.value || !t?.value)) {
        const dates = calcPresetDates(q.value || 'month');
        if (f && !f.value) f.value = dates.from;
        if (t && !t.value) t.value = dates.to;
    }
    updateCrmQuickDateRangeSpan(q?.value || 'month');
});
</script>
@endpush
