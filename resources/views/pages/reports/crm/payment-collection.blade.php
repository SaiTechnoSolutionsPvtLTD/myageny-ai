@extends('layouts.app')

@section('title', 'CRM Payment Collection Report - myAgenci.ai')

@push('styles')
<style>
.crm-pay-page { min-height: 100%; padding: 24px; background: linear-gradient(180deg, #f7f5f1 0%, #f3f6fa 100%); }
.crm-pay-shell { max-width: 1440px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
.crm-pay-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px; border: 1px solid #e7e5e4; border-radius: 24px; background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 58%, #fff7ed 100%); box-shadow: 0 14px 38px rgba(15, 23, 42, 0.05); }
.crm-pay-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: #dcfce7; color: #047857; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-pay-title { margin: 14px 0 6px; font-size: 30px; font-weight: 800; color: #111827; }
.crm-pay-subtitle { margin: 0; font-size: 14px; line-height: 1.7; color: #6b7280; max-width: 780px; }
.crm-pay-actions { display: flex; align-items: center; gap: 10px; }
.crm-pay-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; color: #111827; font-size: 13px; font-weight: 800; text-decoration: none; cursor: pointer; }
.crm-pay-btn:hover { border-color: #86efac; background: #f0fdf4; color: #047857; }
.crm-pay-btn-primary { border-color: transparent; background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; box-shadow: 0 6px 18px rgba(22, 163, 74, 0.22); }
.crm-pay-btn-primary:hover { color: #fff; border-color: transparent; background: linear-gradient(135deg, #15803d, #16a34a); }
.crm-pay-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.crm-pay-stat { position: relative; overflow: hidden; padding: 20px; border-radius: 22px; border: 1px solid #e7e5e4; background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(248,250,252,.98) 100%); box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05); }
.crm-pay-stat::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 5px; border-radius: 999px; background: var(--stat-accent, #16a34a); }
.crm-pay-stat-label { margin-top: 12px; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
.crm-pay-stat-value { margin-top: 10px; font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: -.03em; }
.crm-pay-stat-note { margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(226, 232, 240, .9); font-size: 12px; color: #64748b; }
.crm-pay-stat-chip { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,.88); color: var(--stat-accent, #16a34a); border: 1px solid color-mix(in srgb, var(--stat-accent, #16a34a) 22%, white 78%); font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-pay-tabs { display: inline-flex; align-items: center; gap: 10px; padding: 10px; border-radius: 20px; background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 58%, #fff7ed 100%); border: 1px solid #bbf7d0; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255,255,255,.9); width: fit-content; }
.crm-pay-tab { padding: 12px 22px; border-radius: 14px; border: 1px solid transparent; background: rgba(255,255,255,.72); color: #475569; font-size: 13px; font-weight: 900; transition: all .18s ease; }
.crm-pay-tab:hover { border-color: #86efac; background: #ffffff; color: #047857; transform: translateY(-1px); }
.crm-pay-tab.is-active { background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; border-color: #15803d; box-shadow: 0 10px 22px rgba(22, 163, 74, 0.24); }
.crm-pay-panel { display: none; flex-direction: column; gap: 18px; }
.crm-pay-panel.is-active { display: flex; }
.crm-pay-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-pay-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #f1f5f9; }
.crm-pay-card-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-pay-card-subtitle { margin-top: 3px; font-size: 12px; color: #6b7280; }
.crm-pay-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; background: #ecfdf5; color: #047857; border: 1px solid #bbf7d0; font-size: 11px; font-weight: 800; }
.crm-pay-body { padding: 18px; display: flex; flex-direction: column; gap: 14px; }
.crm-pay-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.crm-pay-quick-label { font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin-right: 4px; }
.crm-qbtn-pay { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 800; cursor: pointer; transition: all .15s ease; letter-spacing: .02em; }
.crm-qbtn-pay:hover { border-color: #22c55e; background: #f0fdf4; color: #047857; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(22,163,74,.12); }
.crm-qbtn-pay.is-active { border-color: #16a34a; background: linear-gradient(135deg, #16a34a, #22c55e); color: #fff; box-shadow: 0 6px 16px rgba(22,163,74,.25); }
.crm-pay-form { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; }
.crm-pay-field { display: flex; flex-direction: column; gap: 7px; }
.crm-pay-label { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
.crm-pay-input, .crm-pay-select { width: 100%; padding: 11px 13px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 13px; outline: none; }
.crm-pay-input:focus, .crm-pay-select:focus { border-color: #22c55e; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12); background: #fff; }
.crm-pay-form-actions { display: flex; align-items: flex-end; gap: 10px; }
.crm-pay-table-wrap { overflow-x: auto; }
.crm-pay-table { width: 100%; border-collapse: collapse; min-width: 1360px; }
.crm-pay-table th { padding: 12px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; white-space: nowrap; }
.crm-pay-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #111827; vertical-align: middle; }
.crm-pay-table tbody tr:hover td { background: #f7fef9; }
.crm-pay-code { font-family: Consolas, monospace; font-size: 12px; color: #64748b; white-space: nowrap; }
.crm-pay-name { font-weight: 800; color: #111827; }
.crm-pay-muted { color: #6b7280; font-size: 12px; }
.crm-pay-money { font-weight: 800; white-space: nowrap; }
.crm-pay-mode { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: #f0fdf4; color: #047857; border: 1px solid #bbf7d0; font-size: 11px; font-weight: 800; white-space: nowrap; }
.crm-pay-empty { padding: 56px 20px; text-align: center; color: #6b7280; }
.crm-pay-empty strong { display: block; margin-bottom: 8px; font-size: 18px; color: #111827; }
.crm-pay-analytics-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.crm-pay-analytics-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 18px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-pay-analytics-card.full { grid-column: 1 / -1; }
.crm-pay-analytics-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.crm-pay-analytics-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-pay-analytics-subtitle { margin-top: 4px; font-size: 12px; color: #6b7280; }
.crm-pay-analytics-chart { position: relative; min-height: 290px; }
.crm-pay-analytics-chart.tall { min-height: 340px; }
@media (max-width: 1280px) {
    .crm-pay-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .crm-pay-form, .crm-pay-analytics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .crm-pay-page { padding: 18px; }
    .crm-pay-topbar, .crm-pay-head { flex-direction: column; align-items: flex-start; }
    .crm-pay-stats, .crm-pay-form, .crm-pay-analytics-grid { grid-template-columns: 1fr; }
    .crm-pay-actions, .crm-pay-form-actions { flex-wrap: wrap; }
    .crm-pay-tabs { width: 100%; flex-wrap: wrap; }
    .crm-pay-quick-filters { gap: 6px; }
}
/* Styling Select2 to match the design system */
.select2-container--default .select2-selection--single.pay-select2-selection {
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
.select2-container--default .select2-selection--single.pay-select2-selection .select2-selection__rendered {
    color: #0f172a;
    padding-left: 8px;
    padding-right: 20px;
}
.select2-container--default .select2-selection--single.pay-select2-selection .select2-selection__arrow {
    height: 100%;
    right: 8px;
    display: flex;
    align-items: center;
}
.select2-container--default.select2-container--focus .select2-selection--single.pay-select2-selection,
.select2-container--default.select2-container--open .select2-selection--single.pay-select2-selection {
    border-color: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
    background: #fff;
}
.select2-dropdown {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}
.select2-results__option {
    font-size: 13px;
    padding: 9px 12px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #22c55e;
    color: #fff;
}
</style>
@endpush

@section('content')
@php
    $hasCustomFilters =
        request()->filled('customer_id')
        || request()->filled('payment_mode')
        || request()->filled('branch_id')
        || request()->filled('date_from')
        || request()->filled('date_to');
@endphp
<div class="crm-pay-page">
    <div class="crm-pay-shell">
        <div class="crm-pay-topbar">
            <div>
                <span class="crm-pay-kicker">CRM Reports</span>
                <h1 class="crm-pay-title">Payment Collection Report</h1>
                <p class="crm-pay-subtitle">Track date-wise customer collections, receipt references, outstanding amounts, payment modes, and the users who received each payment.</p>
            </div>
            <div class="crm-pay-actions">
                <a href="{{ route('reports.crm.payment-collection.export', request()->query()) }}" class="crm-pay-btn crm-pay-btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('reports.crm.index') }}" class="crm-pay-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <div class="crm-pay-stats">
            <div class="crm-pay-stat" style="--stat-accent:#16a34a;">
                <span class="crm-pay-stat-chip">Receipts</span>
                <div class="crm-pay-stat-label">Payment Rows</div>
                <div class="crm-pay-stat-value">{{ number_format($summary['rows']) }}</div>
                <div class="crm-pay-stat-note">Visible payment entries for the selected filters.</div>
            </div>
            <div class="crm-pay-stat" style="--stat-accent:#2563eb;">
                <span class="crm-pay-stat-chip">Total</span>
                <div class="crm-pay-stat-label">Total Amount</div>
                <div class="crm-pay-stat-value">Rs {{ number_format($summary['total_amount'], 2) }}</div>
                <div class="crm-pay-stat-note">Product total value attached to listed payments.</div>
            </div>
            <div class="crm-pay-stat" style="--stat-accent:#047857;">
                <span class="crm-pay-stat-chip">Collected</span>
                <div class="crm-pay-stat-label">Received Amount</div>
                <div class="crm-pay-stat-value">Rs {{ number_format($summary['received_amount'], 2) }}</div>
                <div class="crm-pay-stat-note">Payment amount collected in this report range.</div>
            </div>
            <div class="crm-pay-stat" style="--stat-accent:#dc2626;">
                <span class="crm-pay-stat-chip">Pending</span>
                <div class="crm-pay-stat-label">Outstanding Amount</div>
                <div class="crm-pay-stat-value">Rs {{ number_format($summary['outstanding_amount'], 2) }}</div>
                <div class="crm-pay-stat-note">Current balance remaining against listed products.</div>
            </div>
        </div>

        <div class="crm-pay-tabs" id="crmPaymentTabs">
            <button type="button" class="crm-pay-tab is-active" data-tab-target="payment-data-panel">Data</button>
            <button type="button" class="crm-pay-tab" data-tab-target="payment-analytics-panel">Analytics</button>
        </div>

        <div class="crm-pay-card">
            <div class="crm-pay-head">
                <div>
                    <div class="crm-pay-card-title">Filters</div>
                    <div class="crm-pay-card-subtitle">Date-wise, Customer, and Payment Mode filters.</div>
                </div>
                <div class="crm-pay-chip">{{ $reportRows->total() }} results</div>
            </div>
            <div class="crm-pay-body">
                <div class="crm-pay-quick-filters">
                    <span class="crm-pay-quick-label">Quick:</span>
                    <button type="button" class="crm-qbtn-pay" data-preset="today">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                        Today
                    </button>
                    <button type="button" class="crm-qbtn-pay" data-preset="month">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        This Month
                    </button>
                    <button type="button" class="crm-qbtn-pay" data-preset="quarter">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3z"/><path d="M14 14h7v7h-7z" stroke-opacity=".35"/></svg>
                        This Quarter
                    </button>
                    <button type="button" class="crm-qbtn-pay" data-preset="year">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        This Year
                    </button>
                </div>
                <form method="GET" action="{{ route('reports.crm.payment-collection') }}" class="crm-pay-form" id="paymentCollectionForm">
                    <div class="crm-pay-field">
                        <label class="crm-pay-label" for="date_from">Date From</label>
                        <input id="date_from" type="date" name="date_from" class="crm-pay-input" value="{{ request('date_from') }}">
                    </div>
                    <div class="crm-pay-field">
                        <label class="crm-pay-label" for="date_to">Date To</label>
                        <input id="date_to" type="date" name="date_to" class="crm-pay-input" value="{{ request('date_to') }}">
                    </div>
                    <div class="crm-pay-field">
                        <label class="crm-pay-label" for="customer_id">Customer</label>
                        <select id="customer_id" name="customer_id" class="crm-pay-select select2">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) request('customer_id') === (string) $customer->id)>
                                    LD-{{ str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT) }} - {{ $customer->customer_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="crm-pay-field">
                        <label class="crm-pay-label" for="payment_mode">Payment Mode</label>
                        <select id="payment_mode" name="payment_mode" class="crm-pay-select">
                            <option value="">All Modes</option>
                            @foreach($paymentModes as $mode => $label)
                                <option value="{{ $mode }}" @selected(request('payment_mode') === $mode)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="crm-pay-field">
                        <label class="crm-pay-label" for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id" class="crm-pay-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-pay-form-actions">
                        <button type="submit" class="crm-pay-btn crm-pay-btn-primary">Apply</button>
                        @if($hasCustomFilters)
                            <a href="{{ route('reports.crm.payment-collection') }}" class="crm-pay-btn">Reset</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="crm-pay-panel is-active" id="payment-data-panel">
            <div class="crm-pay-card">
                <div class="crm-pay-head">
                    <div>
                        <div class="crm-pay-card-title">Payment Collection Sheet</div>
                        <div class="crm-pay-card-subtitle">Showing {{ $reportRows->firstItem() ?? 0 }}-{{ $reportRows->lastItem() ?? 0 }} of {{ $reportRows->total() }} rows</div>
                    </div>
                </div>

                @if($reportRows->isEmpty())
                    <div class="crm-pay-empty">
                        <strong>No payment records found</strong>
                        Try changing the date, customer, or payment mode filters.
                    </div>
                @else
                    <div class="crm-pay-table-wrap">
                        <table class="crm-pay-table">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Payment Date</th>
                                    <th>Receipt No</th>
                                    <th>Customer ID</th>
                                    <th>Customer Name</th>
                                    <th>Total Amount</th>
                                    <th>Received Amount</th>
                                    <th>Outstanding Amount</th>
                                    <th>Payment Mode</th>
                                    <th>Transaction Reference</th>
                                    <th>Received By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportRows as $row)
                                    <tr>
                                        <td><span class="crm-pay-code">PMT-{{ str_pad((string) $row->payment_id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                        <td>{{ $row->payment_date ? \Illuminate\Support\Carbon::parse($row->payment_date)->format('d M Y') : '-' }}</td>
                                        <td><span class="crm-pay-code">RCT-{{ str_pad((string) $row->payment_id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                        <td><span class="crm-pay-code">LD-{{ str_pad((string) $row->customer_id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                        <td><span class="crm-pay-name">{{ $row->customer_name ?: '-' }}</span></td>
                                        <td class="crm-pay-money">Rs {{ number_format((float) ($row->total_amount ?? 0), 2) }}</td>
                                        <td class="crm-pay-money" style="color:#047857;">Rs {{ number_format((float) ($row->received_amount ?? 0), 2) }}</td>
                                        <td class="crm-pay-money" style="color:#dc2626;">Rs {{ number_format((float) ($row->outstanding_amount ?? 0), 2) }}</td>
                                        <td><span class="crm-pay-mode">{{ $paymentModes[$row->payment_mode] ?? ucwords(str_replace('_', ' ', (string) $row->payment_mode)) }}</span></td>
                                        <td class="crm-pay-muted">{{ $row->transaction_reference ?: '-' }}</td>
                                        <td>{{ $row->received_by ?: '-' }}</td>
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

        <div class="crm-pay-panel" id="payment-analytics-panel">
            <div class="crm-pay-analytics-grid">
                <div class="crm-pay-analytics-card full">
                    <div class="crm-pay-analytics-head">
                        <div>
                            <div class="crm-pay-analytics-title">Monthly Collection Trend</div>
                            <div class="crm-pay-analytics-subtitle">Received and outstanding movement across selected payment months.</div>
                        </div>
                        <span class="crm-pay-chip">{{ count($analytics['monthly_trend']) }} periods</span>
                    </div>
                    <div class="crm-pay-analytics-chart tall">
                        <canvas id="paymentTrendChart"></canvas>
                    </div>
                </div>
                <div class="crm-pay-analytics-card">
                    <div class="crm-pay-analytics-head">
                        <div>
                            <div class="crm-pay-analytics-title">Payment Mode Split</div>
                            <div class="crm-pay-analytics-subtitle">Collection amount grouped by mode.</div>
                        </div>
                        <span class="crm-pay-chip">{{ count($analytics['payment_modes']) }} modes</span>
                    </div>
                    <div class="crm-pay-analytics-chart">
                        <canvas id="paymentModeChart"></canvas>
                    </div>
                </div>
                <div class="crm-pay-analytics-card">
                    <div class="crm-pay-analytics-head">
                        <div>
                            <div class="crm-pay-analytics-title">Collector Performance</div>
                            <div class="crm-pay-analytics-subtitle">Received amount by user.</div>
                        </div>
                        <span class="crm-pay-chip">Top {{ count($analytics['collectors']) }}</span>
                    </div>
                    <div class="crm-pay-analytics-chart">
                        <canvas id="collectorChart"></canvas>
                    </div>
                </div>
                <div class="crm-pay-analytics-card full">
                    <div class="crm-pay-analytics-head">
                        <div>
                            <div class="crm-pay-analytics-title">Customer Collection</div>
                            <div class="crm-pay-analytics-subtitle">Top customers by received amount with current outstanding view.</div>
                        </div>
                        <span class="crm-pay-chip">Top {{ count($analytics['customers']) }}</span>
                    </div>
                    <div class="crm-pay-analytics-chart">
                        <canvas id="customerCollectionChart"></canvas>
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
    const tabs = document.querySelectorAll('#crmPaymentTabs [data-tab-target]');
    const panels = document.querySelectorAll('.crm-pay-panel');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((button) => button.classList.remove('is-active'));
            panels.forEach((panel) => panel.classList.remove('is-active'));
            tab.classList.add('is-active');
            document.getElementById(tab.dataset.tabTarget)?.classList.add('is-active');
        });
    });

    const analytics = @json($analytics);
    const currency = (value) => 'Rs ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });

    const createChart = (id, config) => {
        const canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') return;
        new Chart(canvas.getContext('2d'), config);
    };

    createChart('paymentTrendChart', {
        type: 'line',
        data: {
            labels: analytics.monthly_trend.map(item => item.label),
            datasets: [
                {
                    label: 'Received',
                    data: analytics.monthly_trend.map(item => item.received_amount),
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.12)',
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: 'Outstanding',
                    data: analytics.monthly_trend.map(item => item.outstanding_amount),
                    borderColor: '#dc2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.08)',
                    fill: false,
                    tension: 0.35,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${currency(ctx.parsed.y)}` } } },
            scales: { y: { beginAtZero: true, ticks: { callback: (value) => currency(value) } } }
        }
    });

    createChart('paymentModeChart', {
        type: 'doughnut',
        data: {
            labels: analytics.payment_modes.map(item => item.label),
            datasets: [{
                data: analytics.payment_modes.map(item => item.received_amount),
                backgroundColor: ['#16a34a', '#2563eb', '#f97316', '#7c3aed', '#0284c7', '#dc2626'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${currency(ctx.parsed)}` } }
            }
        }
    });

    createChart('collectorChart', {
        type: 'bar',
        data: {
            labels: analytics.collectors.map(item => item.label),
            datasets: [{
                label: 'Received Amount',
                data: analytics.collectors.map(item => item.received_amount),
                backgroundColor: '#2563eb',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => currency(ctx.parsed.x) } } },
            scales: { x: { beginAtZero: true, ticks: { callback: (value) => currency(value) } } }
        }
    });

    createChart('customerCollectionChart', {
        type: 'bar',
        data: {
            labels: analytics.customers.map(item => item.label),
            datasets: [
                {
                    label: 'Received',
                    data: analytics.customers.map(item => item.received_amount),
                    backgroundColor: '#16a34a',
                    borderRadius: 8,
                    borderSkipped: false,
                },
                {
                    label: 'Outstanding',
                    data: analytics.customers.map(item => item.outstanding_amount),
                    backgroundColor: '#f97316',
                    borderRadius: 8,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${currency(ctx.parsed.y)}` } } },
            scales: { y: { beginAtZero: true, ticks: { callback: (value) => currency(value) } } }
        }
    });
    // Initialize Select2 with custom layout matching select fields
    if (window.jQuery && window.jQuery.fn.select2) {
        const $customerSelect = $('#customer_id');
        if ($customerSelect.hasClass('select2-hidden-accessible')) {
            $customerSelect.select2('destroy');
        }
        $customerSelect.select2({
            allowClear: true,
            placeholder: "Search Customer",
            width: '100%'
        });
        $customerSelect.next('.select2-container').find('.select2-selection--single').addClass('pay-select2-selection');
    }

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
        const form      = document.getElementById('paymentCollectionForm');
        document.querySelectorAll('.crm-qbtn-pay').forEach(btn => {
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
})();
</script>
@endpush
