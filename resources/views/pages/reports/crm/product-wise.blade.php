@extends('layouts.app')

@section('title', 'CRM Product Wise Report - myAgenci.ai')

@push('styles')
<style>
.crm-product-page { min-height: 100%; padding: 24px; background: linear-gradient(180deg, #f7f5f1 0%, #f3f6fa 100%); }
.crm-product-shell { max-width: 1440px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
.crm-product-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px; border: 1px solid #e7e5e4; border-radius: 24px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #ffedd5 100%); box-shadow: 0 14px 38px rgba(15, 23, 42, 0.05); }
.crm-product-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: #ffedd5; color: #c2410c; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-product-title { margin: 14px 0 6px; font-size: 30px; font-weight: 800; color: #111827; }
.crm-product-subtitle { margin: 0; font-size: 14px; line-height: 1.7; color: #6b7280; max-width: 720px; }
.crm-product-actions { display: flex; align-items: center; gap: 10px; }
.crm-product-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; color: #111827; font-size: 13px; font-weight: 800; text-decoration: none; }
.crm-product-btn:hover { border-color: #fdba74; background: #fff7ed; color: #c2410c; }
.crm-product-btn-primary { border-color: transparent; background: linear-gradient(135deg, #f97316, #fb923c); color: #fff; box-shadow: 0 6px 18px rgba(249, 115, 22, 0.22); }
.crm-product-btn-primary:hover { color: #fff; border-color: transparent; background: linear-gradient(135deg, #ea580c, #f97316); }
.crm-product-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.crm-product-stat { position: relative; overflow: hidden; padding: 20px; border-radius: 22px; border: 1px solid #e7e5e4; background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(248,250,252,.98) 100%); box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05); }
.crm-product-stat::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 5px; border-radius: 999px; background: var(--stat-accent, #2563eb); }
.crm-product-stat-top { position: relative; z-index: 1; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.crm-product-stat-icon { width: 46px; height: 46px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; background: var(--stat-soft, #dbeafe); color: var(--stat-accent, #2563eb); }
.crm-product-stat-icon svg { width: 20px; height: 20px; }
.crm-product-stat-chip { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,.88); color: var(--stat-accent, #2563eb); border: 1px solid color-mix(in srgb, var(--stat-accent, #2563eb) 22%, white 78%); font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-product-stat-label { margin-top: 16px; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
.crm-product-stat-value { margin-top: 10px; font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: -.03em; }
.crm-product-stat-note { margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(226, 232, 240, .9); font-size: 12px; color: #64748b; }
.crm-product-tabs { display: inline-flex; align-items: center; gap: 10px; padding: 10px; border-radius: 20px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #ffedd5 100%); border: 1px solid #fed7aa; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255,255,255,.9); width: fit-content; }
.crm-product-tab { padding: 12px 22px; border-radius: 14px; border: 1px solid transparent; background: rgba(255,255,255,.72); color: #475569; font-size: 13px; font-weight: 900; letter-spacing: .02em; transition: all .18s ease; }
.crm-product-tab:hover { border-color: #fdba74; background: #ffffff; color: #c2410c; transform: translateY(-1px); }
.crm-product-tab.is-active { background: linear-gradient(135deg, #f97316, #fb923c); color: #fff; border-color: #ea580c; box-shadow: 0 10px 22px rgba(249, 115, 22, 0.24); }
.crm-product-panel { display: none; flex-direction: column; gap: 18px; }
.crm-product-panel.is-active { display: flex; }
.crm-product-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-product-filter-head, .crm-product-table-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #f1f5f9; }
.crm-product-card-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-product-card-subtitle { margin-top: 3px; font-size: 12px; color: #6b7280; }
.crm-product-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 11px; font-weight: 800; }
.crm-product-filter-body { padding: 18px; display: flex; flex-direction: column; gap: 14px; }
.crm-product-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.crm-product-quick-label { font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin-right: 4px; }
.crm-qbtn-p { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 800; cursor: pointer; transition: all .15s ease; letter-spacing: .02em; }
.crm-qbtn-p:hover { border-color: #fb923c; background: #fff7ed; color: #c2410c; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(249,115,22,.12); }
.crm-qbtn-p.is-active { border-color: #f97316; background: linear-gradient(135deg, #f97316, #fb923c); color: #fff; box-shadow: 0 6px 16px rgba(249,115,22,.25); }
.crm-product-form { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.crm-product-field { display: flex; flex-direction: column; gap: 7px; }
.crm-product-label { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
.crm-product-input, .crm-product-select { width: 100%; padding: 11px 13px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 13px; outline: none; }
.crm-product-input:focus, .crm-product-select:focus { border-color: #fb923c; box-shadow: 0 0 0 4px rgba(251, 146, 60, 0.12); background: #fff; }
.crm-product-form-actions { display: flex; align-items: flex-end; gap: 10px; }
.crm-product-table-wrap { overflow-x: auto; }
.crm-product-table { width: 100%; border-collapse: collapse; min-width: 1180px; }
.crm-product-table th { padding: 12px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; white-space: nowrap; }
.crm-product-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #111827; vertical-align: middle; }
.crm-product-table tbody tr:hover td { background: #f8fbff; }
.crm-product-code { font-family: Consolas, monospace; font-size: 12px; color: #64748b; }
.crm-product-name { font-weight: 800; color: #111827; }
.crm-product-muted { color: #6b7280; font-size: 12px; }
.crm-product-money { font-weight: 800; white-space: nowrap; }
.crm-product-empty { padding: 56px 20px; text-align: center; color: #6b7280; }
.crm-product-empty strong { display: block; margin-bottom: 8px; font-size: 18px; color: #111827; }
.crm-product-analytics-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.crm-product-analytics-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 18px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-product-analytics-card.full { grid-column: 1 / -1; }
.crm-product-analytics-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.crm-product-analytics-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-product-analytics-subtitle { margin-top: 4px; font-size: 12px; color: #6b7280; }
.crm-product-analytics-pill { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: #fff7ed; color: #c2410c; font-size: 11px; font-weight: 800; }
.crm-product-analytics-chart { position: relative; min-height: 280px; }
.crm-product-analytics-chart.tall { min-height: 340px; }
@media (max-width: 1280px) {
    .crm-product-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .crm-product-form, .crm-product-analytics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .crm-product-page { padding: 18px; }
    .crm-product-topbar, .crm-product-filter-head, .crm-product-table-head { flex-direction: column; align-items: flex-start; }
    .crm-product-stats, .crm-product-form, .crm-product-analytics-grid { grid-template-columns: 1fr; }
    .crm-product-form-actions { flex-wrap: wrap; }
    .crm-product-tabs { width: 100%; flex-wrap: wrap; }
    .crm-product-quick-filters { gap: 6px; }
}
</style>
@endpush

@section('content')
@php
    $hasCustomFilters =
        request()->filled('product_id')
        || request()->filled('branch_id')
        || request()->filled('date_from')
        || request()->filled('date_to');
@endphp
<div class="crm-product-page">
    <div class="crm-product-shell">
        <div class="crm-product-topbar">
            <div>
                <span class="crm-product-kicker">CRM Reports</span>
                <h1 class="crm-product-title">Product Wise Report</h1>
                <p class="crm-product-subtitle">Track quantity sold, sales value, discounts, tax impact, and net revenue product by product.</p>
            </div>
            <div class="crm-product-actions">
                <a href="{{ route('reports.crm.product-wise.export', request()->query()) }}" class="crm-product-btn crm-product-btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('reports.crm.index') }}" class="crm-product-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <div class="crm-product-stats">
            <div class="crm-product-stat" style="--stat-accent:#f97316;--stat-soft:#ffedd5;">
                <div class="crm-product-stat-top">
                    <span class="crm-product-stat-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path d="M3 7h18"/>
                            <path d="M6 3h12l1 4H5l1-4z"/>
                            <path d="M5 11h14v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8z"/>
                        </svg>
                    </span>
                    <span class="crm-product-stat-chip">Lines</span>
                </div>
                <div class="crm-product-stat-label">Product Rows</div>
                <div class="crm-product-stat-value">{{ number_format($summary['rows']) }}</div>
                <div class="crm-product-stat-note">Visible grouped product rows for the selected period.</div>
            </div>
            <div class="crm-product-stat" style="--stat-accent:#fb923c;--stat-soft:#ffedd5;">
                <div class="crm-product-stat-top">
                    <span class="crm-product-stat-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path d="M20 7 9 18l-5-5"/>
                        </svg>
                    </span>
                    <span class="crm-product-stat-chip">Volume</span>
                </div>
                <div class="crm-product-stat-label">Quantity Sold</div>
                <div class="crm-product-stat-value">{{ number_format($summary['quantity_sold']) }}</div>
                <div class="crm-product-stat-note">Total sold quantity aggregated from lead product entries.</div>
            </div>
            <div class="crm-product-stat" style="--stat-accent:#ea580c;--stat-soft:#fed7aa;">
                <div class="crm-product-stat-top">
                    <span class="crm-product-stat-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H7"/>
                        </svg>
                    </span>
                    <span class="crm-product-stat-chip">Sales</span>
                </div>
                <div class="crm-product-stat-label">Sales Amount</div>
                <div class="crm-product-stat-value">Rs {{ number_format($summary['sales_amount'], 2) }}</div>
                <div class="crm-product-stat-note">Gross sales before tax-inclusive revenue comparison.</div>
            </div>
            <div class="crm-product-stat" style="--stat-accent:#c2410c;--stat-soft:#ffedd5;">
                <div class="crm-product-stat-top">
                    <span class="crm-product-stat-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path d="M12 2v20"/>
                            <path d="M17 6H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </span>
                    <span class="crm-product-stat-chip">Revenue</span>
                </div>
                <div class="crm-product-stat-label">Net Revenue</div>
                <div class="crm-product-stat-value">Rs {{ number_format($summary['net_revenue'], 2) }}</div>
                <div class="crm-product-stat-note">Discount-adjusted revenue currently recorded in CRM.</div>
            </div>
        </div>

        <div class="crm-product-tabs" id="crmProductTabs">
            <button type="button" class="crm-product-tab is-active" data-tab-target="product-data-panel">Data</button>
            <button type="button" class="crm-product-tab" data-tab-target="product-analytics-panel">Analytics</button>
        </div>

        <div class="crm-product-card">
            <div class="crm-product-filter-head">
                <div>
                    <div class="crm-product-card-title">Filters</div>
                    <div class="crm-product-card-subtitle">Products and Date-wise product performance view.</div>
                </div>
                <div class="crm-product-chip">{{ $reportRows->total() }} results</div>
            </div>
            <div class="crm-product-filter-body">
                <div class="crm-product-quick-filters">
                    <span class="crm-product-quick-label">Quick:</span>
                    <button type="button" class="crm-qbtn-p" data-preset="today">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                        Today
                    </button>
                    <button type="button" class="crm-qbtn-p" data-preset="month">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        This Month
                    </button>
                    <button type="button" class="crm-qbtn-p" data-preset="quarter">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3z"/><path d="M14 14h7v7h-7z" stroke-opacity=".35"/></svg>
                        This Quarter
                    </button>
                    <button type="button" class="crm-qbtn-p" data-preset="year">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        This Year
                    </button>
                </div>
                <form method="GET" action="{{ route('reports.crm.product-wise') }}" class="crm-product-form" id="productWiseForm">
                    <input type="hidden" name="tab" id="crmActiveTabInput" value="{{ request('tab', 'product-data-panel') }}">
                    <div class="crm-product-field">
                        <label class="crm-product-label" for="product_id">Products</label>
                        <select id="product_id" name="product_id" class="crm-product-select">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>
                                    {{ $product->package_name ?: $product->product_name }}{{ $product->sku ? ' - ' . $product->sku : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="crm-product-field">
                        <label class="crm-product-label" for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id" class="crm-product-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-product-field">
                        <label class="crm-product-label" for="date_from">Date From</label>
                        <input id="date_from" type="date" name="date_from" class="crm-product-input" value="{{ request('date_from') }}">
                    </div>

                    <div class="crm-product-field">
                        <label class="crm-product-label" for="date_to">Date To</label>
                        <input id="date_to" type="date" name="date_to" class="crm-product-input" value="{{ request('date_to') }}">
                    </div>

                    <div class="crm-product-form-actions">
                        <button type="submit" class="crm-product-btn crm-product-btn-primary">Apply Filters</button>
                        @if($hasCustomFilters)
                            <a href="{{ route('reports.crm.product-wise') }}" class="crm-product-btn">Reset</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="crm-product-panel is-active" id="product-data-panel">
            <div class="crm-product-card">
                <div class="crm-product-table-head">
                    <div>
                        <div class="crm-product-card-title">Product Wise Sheet</div>
                        <div class="crm-product-card-subtitle">Showing {{ $reportRows->firstItem() ?? 0 }}-{{ $reportRows->lastItem() ?? 0 }} of {{ $reportRows->total() }} rows</div>
                    </div>
                </div>

                @if($reportRows->isEmpty())
                    <div class="crm-product-empty">
                        <strong>No product-wise rows found</strong>
                        Try changing the product or date filters to widen the report.
                    </div>
                @else
                    <div class="crm-product-table-wrap">
                        <table class="crm-product-table">
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Quantity Sold</th>
                                    <th>Sales Amount</th>
                                    <th>Discount Amount</th>
                                    <th>Tax Amount</th>
                                    <th>Net Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportRows as $row)
                                    <tr>
                                        <td><span class="crm-product-code">{{ $row->product_code ?: '-' }}</span></td>
                                        <td><span class="crm-product-name">{{ $row->product_name ?: '-' }}</span></td>
                                        <td class="crm-product-muted">{{ $row->category_name ?: '-' }}</td>
                                        <td>{{ number_format((int) ($row->quantity_sold ?? 0)) }}</td>
                                        <td class="crm-product-money">Rs {{ number_format((float) ($row->sales_amount ?? 0), 2) }}</td>
                                        <td class="crm-product-money" style="color:#dc2626;">Rs {{ number_format((float) ($row->discount_amount ?? 0), 2) }}</td>
                                        <td class="crm-product-money" style="color:#1d4ed8;">Rs {{ number_format((float) ($row->tax_amount ?? 0), 2) }}</td>
                                        <td class="crm-product-money" style="color:#15803d;">Rs {{ number_format((float) ($row->net_revenue ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($reportRows->hasPages())
                        @include('partials.table-pagination', ['paginator' => $reportRows])
                    @endif
                @endif
            </div>
        </div>

        <div class="crm-product-panel" id="product-analytics-panel">
            <div class="crm-product-analytics-grid">
                <div class="crm-product-analytics-card full">
                    <div class="crm-product-analytics-head">
                        <div>
                            <div class="crm-product-analytics-title">Monthly Product Trend</div>
                            <div class="crm-product-analytics-subtitle">Quantity sold and net revenue trend over the selected date range.</div>
                        </div>
                        <span class="crm-product-analytics-pill">{{ count($analytics['monthly_trend']) }} periods</span>
                    </div>
                    <div class="crm-product-analytics-chart tall">
                        <canvas id="productTrendChart"></canvas>
                    </div>
                </div>

                <div class="crm-product-analytics-card">
                    <div class="crm-product-analytics-head">
                        <div>
                            <div class="crm-product-analytics-title">Top Products</div>
                            <div class="crm-product-analytics-subtitle">Best performing products by sold quantity.</div>
                        </div>
                        <span class="crm-product-analytics-pill">Top {{ count($analytics['products']) }}</span>
                    </div>
                    <div class="crm-product-analytics-chart">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>

                <div class="crm-product-analytics-card">
                    <div class="crm-product-analytics-head">
                        <div>
                            <div class="crm-product-analytics-title">Category Performance</div>
                            <div class="crm-product-analytics-subtitle">Category-wise quantity contribution.</div>
                        </div>
                        <span class="crm-product-analytics-pill">Top {{ count($analytics['categories']) }}</span>
                    </div>
                    <div class="crm-product-analytics-chart">
                        <canvas id="categoryPerformanceChart"></canvas>
                    </div>
                </div>

                <div class="crm-product-analytics-card full">
                    <div class="crm-product-analytics-head">
                        <div>
                            <div class="crm-product-analytics-title">Revenue by Product</div>
                            <div class="crm-product-analytics-subtitle">Net revenue generated by the leading products.</div>
                        </div>
                        <span class="crm-product-analytics-pill">Revenue View</span>
                    </div>
                    <div class="crm-product-analytics-chart">
                        <canvas id="productRevenueChart"></canvas>
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
(() => {
    const tabs = document.querySelectorAll('#crmProductTabs [data-tab-target]');
    const panels = document.querySelectorAll('.crm-product-panel');
    const tabInput = document.getElementById('crmActiveTabInput');

    function activateTab(targetId) {
        const activeTabBtn = document.querySelector(`#crmProductTabs [data-tab-target="${targetId}"]`);
        const activePanel = document.getElementById(targetId);
        if (activeTabBtn && activePanel) {
            tabs.forEach((button) => button.classList.remove('is-active'));
            panels.forEach((panel) => panel.classList.remove('is-active'));
            activeTabBtn.classList.add('is-active');
            activePanel.classList.add('is-active');
            if (tabInput) tabInput.value = targetId;
        }
    }

    const initialTab = new URLSearchParams(window.location.search).get('tab') || (tabInput ? tabInput.value : 'product-data-panel');
    if (initialTab) {
        activateTab(initialTab);
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.tabTarget;
            activateTab(targetId);
            const url = new URL(window.location);
            url.searchParams.set('tab', targetId);
            window.history.replaceState({}, '', url);
        });
    });

    const analytics = @json($analytics);
    const currency = (value) => 'Rs ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    const registry = {};

    const createChart = (id, config) => {
        const canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        if (registry[id]) {
            registry[id].destroy();
        }

        registry[id] = new Chart(canvas.getContext('2d'), config);
    };

    createChart('productTrendChart', {
        type: 'line',
        data: {
            labels: analytics.monthly_trend.map(item => item.label),
            datasets: [
                {
                    label: 'Quantity Sold',
                    data: analytics.monthly_trend.map(item => item.quantity_sold),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    fill: true,
                    tension: 0.35,
                    yAxisID: 'y',
                },
                {
                    label: 'Net Revenue',
                    data: analytics.monthly_trend.map(item => item.net_revenue),
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.08)',
                    fill: false,
                    tension: 0.35,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ctx.dataset.label === 'Quantity Sold'
                            ? `${ctx.dataset.label}: ${ctx.parsed.y}`
                            : `${ctx.dataset.label}: ${currency(ctx.parsed.y)}`
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Quantity' } },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { callback: (value) => currency(value) },
                    title: { display: true, text: 'Net Revenue' }
                }
            }
        }
    });

    createChart('topProductsChart', {
        type: 'bar',
        data: {
            labels: analytics.products.map(item => item.product_name),
            datasets: [{
                label: 'Quantity Sold',
                data: analytics.products.map(item => item.quantity_sold),
                backgroundColor: ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#1d4ed8'],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });

    createChart('categoryPerformanceChart', {
        type: 'doughnut',
        data: {
            labels: analytics.categories.map(item => item.label),
            datasets: [{
                data: analytics.categories.map(item => item.quantity_sold),
                backgroundColor: ['#16a34a', '#22c55e', '#4ade80', '#86efac', '#bbf7d0', '#15803d'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed} qty` } }
            }
        }
    });

    createChart('productRevenueChart', {
        type: 'bar',
        data: {
            labels: analytics.products.map(item => item.product_name),
            datasets: [{
                label: 'Net Revenue',
                data: analytics.products.map(item => item.net_revenue),
                backgroundColor: ['#7c3aed', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe', '#6d28d9'],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${currency(ctx.parsed.y)}` } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (value) => currency(value) }
                }
            }
        }
    });
})();

    // ── Quick Date Preset Buttons ──────────────────────────────────────────
    (() => {
        const fmtDate = (d) => d.toISOString().slice(0, 10);
        const today = new Date();
        const y = today.getFullYear(), m = today.getMonth(), q = Math.floor(m / 3);
        const presets = {
            today:   { from: fmtDate(today), to: fmtDate(today) },
            month:   { from: fmtDate(new Date(y, m, 1)), to: fmtDate(new Date(y, m + 1, 0)) },
            quarter: { from: fmtDate(new Date(y, q * 3, 1)), to: fmtDate(new Date(y, q * 3 + 3, 0)) },
            year:    { from: fmtDate(new Date(y, 0, 1)), to: fmtDate(new Date(y, 11, 31)) },
        };
        const urlParams = new URLSearchParams(window.location.search);
        const currentFrom = urlParams.get('date_from') || '';
        const currentTo   = urlParams.get('date_to')   || '';
        const fromInput = document.getElementById('date_from');
        const toInput   = document.getElementById('date_to');
        const form      = document.getElementById('productWiseForm');
        document.querySelectorAll('.crm-qbtn-p').forEach(btn => {
            const preset = presets[btn.dataset.preset];
            if (preset && currentFrom === preset.from && currentTo === preset.to) btn.classList.add('is-active');
            btn.addEventListener('click', () => {
                const p = presets[btn.dataset.preset];
                if (!p) return;
                fromInput.value = p.from;
                toInput.value   = p.to;
                form.submit();
            });
        });
    })();
</script>
@endpush
