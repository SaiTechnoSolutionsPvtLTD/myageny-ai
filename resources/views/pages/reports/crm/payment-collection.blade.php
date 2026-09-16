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
.crm-pay-stats-group { display: flex; flex-direction: column; gap: 16px; }
.crm-pay-stats-r1 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.crm-pay-stats-r2 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
.crm-pay-stat {
    position: relative;
    overflow: hidden;
    padding: 20px 22px;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.crm-pay-stat:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--stat-accent, #16a34a) 35%, #e2e8f0 65%);
    box-shadow: 0 14px 28px -4px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.03);
}
.crm-pay-stat::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3.5px;
    background: var(--stat-accent, #16a34a);
    border-radius: 999px 999px 0 0;
}
.crm-pay-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.crm-pay-stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 999px;
    background: var(--stat-bg, #f1f5f9);
    color: var(--stat-accent, #16a34a);
    border: 1px solid var(--stat-border, #cbd5e1);
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.crm-pay-stat-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--stat-icon-bg, #f8fafc);
    color: var(--stat-accent, #16a34a);
    border: 1px solid var(--stat-border, #e2e8f0);
    flex-shrink: 0;
}
.crm-pay-stat-label {
    margin-top: 14px;
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: #64748b;
    line-height: 1.35;
}
.crm-pay-stat-value {
    margin-top: 6px;
    font-size: 22px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -.03em;
    word-break: break-word;
    display: flex;
    align-items: baseline;
    gap: 5px;
}
.crm-pay-stat-value .curr {
    font-size: 14px;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: normal;
}
.crm-pay-stat-note {
    margin-top: 14px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
    font-size: 11.5px;
    color: #64748b;
    line-height: 1.4;
    display: flex;
    align-items: center;
    gap: 6px;
}
.crm-pay-stat-note .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--stat-accent, #16a34a);
    flex-shrink: 0;
}
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
@media (max-width: 1400px) {
    .crm-pay-stats-r1 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .crm-pay-stats-r2 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 1200px) {
    .crm-pay-stats-r1 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .crm-pay-stats-r2 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .crm-pay-form, .crm-pay-analytics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
    .crm-pay-stats-r1 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .crm-pay-stats-r2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .crm-pay-page { padding: 18px; }
    .crm-pay-topbar, .crm-pay-head { flex-direction: column; align-items: flex-start; }
    .crm-pay-stats-r1, .crm-pay-stats-r2, .crm-pay-form, .crm-pay-analytics-grid { grid-template-columns: 1fr; }
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
    background: #22c55e !important;
    color: #fff !important;
}
.crm-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
    letter-spacing: 0.02em;
}
.crm-type-badge.new-sales {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.crm-type-badge.new-sales .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
}
.crm-type-badge.balance-payment {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
}
.crm-type-badge.balance-payment .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #ea580c;
}
.crm-type-badge.renewals {
    background: #f5f3ff;
    color: #7c3aed;
    border: 1px solid #ddd6fe;
}
.crm-type-badge.renewals .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #8b5cf6;
}

.crm-type-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    background: #ffffff;
    color: #475569;
    border: 1.5px solid #e2e8f0;
    transition: all 0.15s ease;
}
.crm-type-pill:hover {
    border-color: #cbd5e1;
    color: #0f172a;
    background: #f8fafc;
}
.crm-type-pill.is-active {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
}
</style>
@endpush

@section('content')
@php
    $hasCustomFilters =
        request()->filled('customer_id')
        || request()->filled('company_name')
        || request()->filled('sales_executive_id')
        || request()->filled('payment_mode')
        || request()->filled('branch_id')
        || request()->filled('collection_type')
        || (request()->has('quick_date') && request('quick_date') !== 'month')
        || (request()->has('date_from') && request('date_from') !== $defaultFromDate)
        || (request()->has('date_to') && request('date_to') !== $defaultToDate);
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

        <div class="crm-pay-stats-group">
            {{-- Row 1: 4 Cards --}}
            <div class="crm-pay-stats-r1">
                {{-- 1. Payment Rows --}}
                <div class="crm-pay-stat" style="--stat-accent:#334155; --stat-bg:#f8fafc; --stat-border:#cbd5e1; --stat-icon-bg:#f1f5f9;">
                    <div>
                        <div class="crm-pay-stat-top">
                            <span class="crm-pay-stat-chip">Receipts</span>
                            <div class="crm-pay-stat-icon-wrap" title="Payment Receipts">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="crm-pay-stat-label">Payment Rows</div>
                        <div class="crm-pay-stat-value">
                            {{ number_format($summary['rows']) }}
                        </div>
                    </div>
                    <div class="crm-pay-stat-note">
                        <span class="dot"></span>
                        <span>Visible payment entries for selected filters.</span>
                    </div>
                </div>

                {{-- 2. Total Collected Payment --}}
                <div class="crm-pay-stat" style="--stat-accent:#059669; --stat-bg:#ecfdf5; --stat-border:#a7f3d0; --stat-icon-bg:#d1fae5;">
                    <div>
                        <div class="crm-pay-stat-top">
                            <span class="crm-pay-stat-chip">Overall Collected</span>
                            <div class="crm-pay-stat-icon-wrap" title="Overall Collected">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                                    <circle cx="12" cy="12" r="2"></circle>
                                    <path d="M6 12h.01M18 12h.01"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="crm-pay-stat-label">Total Collected Payment</div>
                        <div class="crm-pay-stat-value">
                            <span class="curr">Rs</span> {{ number_format($summary['total_collected_payment'], 2) }}
                        </div>
                    </div>
                    <div class="crm-pay-stat-note">
                        <span class="dot"></span>
                        <span>Overall received amount for selected filters.</span>
                    </div>
                </div>

                {{-- 3. Deal Value (New Sale + Renewals) --}}
                <div class="crm-pay-stat" style="--stat-accent:#2563eb; --stat-bg:#eff6ff; --stat-border:#bfdbfe; --stat-icon-bg:#dbeafe;">
                    <div>
                        <div class="crm-pay-stat-top">
                            <span class="crm-pay-stat-chip">Deal Value</span>
                            <div class="crm-pay-stat-icon-wrap" title="Deal Value">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                    <polyline points="17 6 23 6 23 12"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="crm-pay-stat-label">Deal Value (New Sale + Renewals)</div>
                        <div class="crm-pay-stat-value">
                            <span class="curr">Rs</span> {{ number_format($summary['deal_value'], 2) }}
                        </div>
                    </div>
                    <div class="crm-pay-stat-note">
                        <span class="dot"></span>
                        <span>Total contract value for New Sales &amp; Renewals.</span>
                    </div>
                </div>

                {{-- 4. New & Renewal Received --}}
                <div class="crm-pay-stat" style="--stat-accent:#0d9488; --stat-bg:#f0fdfa; --stat-border:#99f6e4; --stat-icon-bg:#ccfbf1;">
                    <div>
                        <div class="crm-pay-stat-top">
                            <span class="crm-pay-stat-chip">New &amp; Renewal</span>
                            <div class="crm-pay-stat-icon-wrap" title="New & Renewal Received">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="crm-pay-stat-label">New &amp; Renewal Received</div>
                        <div class="crm-pay-stat-value">
                            <span class="curr">Rs</span> {{ number_format($summary['new_renewals_received'], 2) }}
                        </div>
                    </div>
                    <div class="crm-pay-stat-note">
                        <span class="dot"></span>
                        <span>Payment collected on New Sales &amp; Renewals.</span>
                    </div>
                </div>
            </div>

            {{-- Row 2: 3 Cards --}}
            <div class="crm-pay-stats-r2">
                {{-- 5. Balance Amount --}}
                <div class="crm-pay-stat" style="--stat-accent:#ea580c; --stat-bg:#fff7ed; --stat-border:#fed7aa; --stat-icon-bg:#ffedd5;">
                    <div>
                        <div class="crm-pay-stat-top">
                            <span class="crm-pay-stat-chip">Balance Payment</span>
                            <div class="crm-pay-stat-icon-wrap" title="Balance Amount">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>
                                    <path d="M12 18V6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="crm-pay-stat-label">Balance Amount</div>
                        <div class="crm-pay-stat-value">
                            <span class="curr">Rs</span> {{ number_format($summary['balance_received'], 2) }}
                        </div>
                    </div>
                    <div class="crm-pay-stat-note">
                        <span class="dot"></span>
                        <span>Amount collected on pending balance payments.</span>
                    </div>
                </div>

                {{-- 6. TDS Deduction Amount --}}
                <div class="crm-pay-stat" style="--stat-accent:#7c3aed; --stat-bg:#f5f3ff; --stat-border:#ddd6fe; --stat-icon-bg:#ede9fe;">
                    <div>
                        <div class="crm-pay-stat-top">
                            <span class="crm-pay-stat-chip">TDS Deducted</span>
                            <div class="crm-pay-stat-icon-wrap" title="TDS Deduction Amount">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="19" y1="5" x2="5" y2="19"></line>
                                    <circle cx="6.5" cy="6.5" r="2.5"></circle>
                                    <circle cx="17.5" cy="17.5" r="2.5"></circle>
                                </svg>
                            </div>
                        </div>
                        <div class="crm-pay-stat-label">TDS Deduction Amount</div>
                        <div class="crm-pay-stat-value">
                            <span class="curr">Rs</span> {{ number_format($summary['tds_deduction_amount'], 2) }}
                        </div>
                    </div>
                    <div class="crm-pay-stat-note">
                        <span class="dot"></span>
                        <span>Total TDS deducted on collected payments.</span>
                    </div>
                </div>

                {{-- 7. Outstanding Amount --}}
                <div class="crm-pay-stat" style="--stat-accent:#dc2626; --stat-bg:#fef2f2; --stat-border:#fecaca; --stat-icon-bg:#fee2e2;">
                    <div>
                        <div class="crm-pay-stat-top">
                            <span class="crm-pay-stat-chip">Pending</span>
                            <div class="crm-pay-stat-icon-wrap" title="Outstanding Amount">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="crm-pay-stat-label">Outstanding Amount</div>
                        <div class="crm-pay-stat-value">
                            <span class="curr">Rs</span> {{ number_format($summary['outstanding_amount'], 2) }}
                        </div>
                    </div>
                    <div class="crm-pay-stat-note">
                        <span class="dot"></span>
                        <span>Pending balance on deals for selected filters.</span>
                    </div>
                </div>
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
                <form method="GET" action="{{ route('reports.crm.payment-collection') }}" class="crm-pay-form" id="paymentCollectionForm">
                    <input type="hidden" name="tab" id="crmActiveTabInput" value="{{ request('tab', 'payment-data-panel') }}">
                    <div class="crm-pay-field">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                            <label class="crm-pay-label" for="quick_date_select" style="margin-bottom:0;">Quick Dates</label>
                            <span id="crmQuickDateRange" style="font-size:11px;font-weight:700;color:#16a34a;"></span>
                        </div>
                        <select id="quick_date_select" name="quick_date" class="crm-pay-select" onchange="onCrmQuickDateChange(this.value)">
                            <option value="today" {{ request('quick_date') == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ request('quick_date') == 'week' ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ request('quick_date', 'month') == 'month' ? 'selected' : '' }}>This Month</option>
                            <option value="quarter" {{ request('quick_date') == 'quarter' ? 'selected' : '' }}>This Quarter</option>
                            <option value="year" {{ request('quick_date') == 'year' ? 'selected' : '' }}>This Year</option>
                            <option value="all" {{ request('quick_date') == 'all' ? 'selected' : '' }}>Show All</option>
                            <option value="custom" {{ request('quick_date') == 'custom' ? 'selected' : '' }}>Custom Dates</option>
                        </select>
                    </div>

                    <div class="crm-pay-field" id="crmFromField" style="display: {{ request('quick_date') == 'custom' ? 'flex' : 'none' }};">
                        <label class="crm-pay-label" for="date_from">Date From</label>
                        <input id="date_from" type="date" name="date_from" class="crm-pay-input" value="{{ request('date_from', $defaultFromDate) }}">
                    </div>

                    <div class="crm-pay-field" id="crmToField" style="display: {{ request('quick_date') == 'custom' ? 'flex' : 'none' }};">
                        <label class="crm-pay-label" for="date_to">Date To</label>
                        <input id="date_to" type="date" name="date_to" class="crm-pay-input" value="{{ request('date_to', $defaultToDate) }}">
                    </div>

                    <div class="crm-pay-field">
                        <label class="crm-pay-label" for="company_name">Company</label>
                        <input id="company_name" type="text" name="company_name" class="crm-pay-input" value="{{ request('company_name') }}" placeholder="Search company...">
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
                        <label class="crm-pay-label" for="sales_executive_id">Sales Executive</label>
                        <select id="sales_executive_id" name="sales_executive_id" class="crm-pay-select select2">
                            <option value="">All Sales Executives</option>
                            @foreach($salesExecutives as $exec)
                                <option value="{{ $exec->id }}" @selected((string) request('sales_executive_id', request('user_id')) === (string) $exec->id)>{{ $exec->name }}</option>
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

                    <div class="crm-pay-field">
                        <label class="crm-pay-label" for="collection_type">Payment Type</label>
                        <select id="collection_type" name="collection_type" class="crm-pay-select">
                            <option value="">All Types</option>
                            <option value="new_sales" @selected(in_array(request('collection_type'), ['new_sales', 'new_sale']))>New Sales (New Converted Product)</option>
                            <option value="balance_payment" @selected(request('collection_type') === 'balance_payment')>Balance Payment</option>
                            <option value="renewals" @selected(in_array(request('collection_type'), ['renewals', 'renewal']))>Renewals</option>
                        </select>
                    </div>

                    @if(request()->filled('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif

                    <div class="crm-pay-form-actions">
                        <button type="submit" class="crm-pay-btn crm-pay-btn-primary">Apply</button>
                        @if($hasCustomFilters)
                            <a href="{{ route('reports.crm.payment-collection', ['reset' => 1]) }}" class="crm-pay-btn">Reset</a>
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
                    @if($reportRows->total() > 0)
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label for="per_page_select" style="font-size:12px; font-weight:700; color:#64748b;">Per page:</label>
                            <select id="per_page_select" class="crm-pay-select" style="width:auto; padding:6px 12px; font-size:12px; border-radius:10px;" onchange="window.location.href=this.value">
                                @foreach([10, 20, 50, 100] as $size)
                                    <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" @selected((int) request('per_page', 20) === $size)>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div style="padding:14px 20px 0 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <a href="{{ request()->fullUrlWithQuery(['collection_type' => null, 'page' => 1]) }}"
                           class="crm-type-pill {{ !request('collection_type') ? 'is-active' : '' }}">
                            <span>All Types</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['collection_type' => 'new_sales', 'page' => 1]) }}"
                           class="crm-type-pill {{ in_array(request('collection_type'), ['new_sales', 'new_sale']) ? 'is-active' : '' }}">
                            <span style="width:7px; height:7px; border-radius:50%; background:#10b981;"></span>
                            <span>New Sales</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['collection_type' => 'balance_payment', 'page' => 1]) }}"
                           class="crm-type-pill {{ request('collection_type') === 'balance_payment' ? 'is-active' : '' }}">
                            <span style="width:7px; height:7px; border-radius:50%; background:#ea580c;"></span>
                            <span>Balance Payment</span>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['collection_type' => 'renewals', 'page' => 1]) }}"
                           class="crm-type-pill {{ in_array(request('collection_type'), ['renewals', 'renewal']) ? 'is-active' : '' }}">
                            <span style="width:7px; height:7px; border-radius:50%; background:#8b5cf6;"></span>
                            <span>Renewals</span>
                        </a>
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
                                    <th>Branch</th>
                                    <th>Company Name</th>
                                    <th>Customer Name</th>
                                    <th>Payment Type</th>
                                    <th>Total Amount</th>
                                    <th>Received Amount</th>
                                    <th>TDS Amount (%)</th>
                                    <th>Outstanding Amount</th>
                                    <th>Payment Mode</th>
                                    <th>Transaction Reference</th>
                                    <th>Received By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportRows as $row)
                                    @php
                                        $rowReceivedAmount = (float) ($row->received_amount ?? 0);
                                        $rowOutstandingAmount = (float) ($row->outstanding_amount ?? 0);
                                        $rowTotalAmount = (float) ($row->total_amount ?? ($rowReceivedAmount + $rowOutstandingAmount));
                                        $rowTdsAmount = (float) ($row->tds_amount ?? 0);
                                        $rowTdsPercent = (float) ($row->tds_percentage ?? 0);
                                    @endphp
                                    <tr style="cursor:pointer;" onclick="window.location='{{ route('leads.show', $row->customer_id) }}'" title="Click to view lead details for {{ $row->company_name ?: $row->customer_name }}">
                                        <td><span class="crm-pay-code">PMT-{{ str_pad((string) $row->payment_id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                        <td>{{ $row->payment_date ? \Illuminate\Support\Carbon::parse($row->payment_date)->format('d M Y') : '-' }}</td>
                                        <td><span class="crm-pay-code">RCT-{{ str_pad((string) $row->payment_id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                        <td>
                                            <a href="{{ route('leads.show', $row->customer_id) }}" class="crm-pay-code" style="color:#ea580c; font-weight:800; text-decoration:none;">
                                                 LD-{{ str_pad((string) $row->customer_id, 4, '0', STR_PAD_LEFT) }}
                                            </a>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#334155;">{{ $row->branch_name ?: '-' }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('leads.show', $row->customer_id) }}" class="crm-pay-name" style="color:#0f172a; font-weight:800; text-decoration:none;">
                                                {{ $row->company_name ?: '-' }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('leads.show', $row->customer_id) }}" class="crm-pay-name" style="color:#334155; text-decoration:none;">
                                                {{ $row->customer_name ?: '-' }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $rowType = strtolower(str_replace([' ', '-'], '_', (string) ($row->payment_type ?: $row->collection_type)));
                                            @endphp
                                            @if(in_array($rowType, ['new_sales', 'new_sale']))
                                                <span class="crm-type-badge new-sales">
                                                    <span class="dot"></span> New Sales
                                                </span>
                                            @elseif($rowType === 'balance_payment')
                                                <span class="crm-type-badge balance-payment">
                                                    <span class="dot"></span> Balance Payment
                                                </span>
                                            @elseif(in_array($rowType, ['renewals', 'renewal']))
                                                <span class="crm-type-badge renewals">
                                                    <span class="dot"></span> Renewals
                                                </span>
                                            @else
                                                <span style="color:#94a3b8; font-size:12px;">{{ $row->payment_type ?: '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="crm-pay-money">
                                            @if($rowType === 'balance_payment' || $rowTotalAmount <= 0)
                                                <span style="color:#94a3b8; font-weight:600;">—</span>
                                            @else
                                                Rs {{ number_format($rowTotalAmount, 2) }}
                                            @endif
                                        </td>
                                        <td class="crm-pay-money" style="color:#047857;">Rs {{ number_format($rowReceivedAmount, 2) }}</td>
                                        <td class="crm-pay-money">
                                            @if($rowTdsAmount > 0)
                                                @php
                                                    $cleanPercent = rtrim(rtrim(number_format($rowTdsPercent, 2), '0'), '.');
                                                @endphp
                                                <span style="color:#7c3aed; font-weight:700;">
                                                    Rs {{ number_format($rowTdsAmount, 2) }}
                                                    @if($cleanPercent !== '' && $cleanPercent !== '0')
                                                        <span style="font-size:11px; background:#ede9fe; color:#6d28d9; padding:1px 5px; border-radius:4px; margin-left:3px; font-weight:700;">{{ $cleanPercent }}%</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span style="color:#94a3b8; font-weight:600;">—</span>
                                            @endif
                                        </td>
                                        <td class="crm-pay-money" style="color:#dc2626;">Rs {{ number_format($rowOutstandingAmount, 2) }}</td>
                                        <td><span class="crm-pay-mode">{{ $paymentModes[$row->payment_mode] ?? ucwords(str_replace('_', ' ', (string) $row->payment_mode)) }}</span></td>
                                        <td class="crm-pay-muted">{{ $row->transaction_reference ?: '-' }}</td>
                                        <td>
                                            <div style="display:flex; flex-direction:column; gap:2px;">
                                                <span style="font-weight:700; color:#111827;">{{ $row->received_by ?: '-' }}</span>
                                                @if($row->received_by_department)
                                                    <span style="font-size:11px; color:#6b7280; font-weight:600;">{{ $row->received_by_department }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background:#f8fafc; font-weight:800; border-top:2px solid #cbd5e1;">
                                    <td colspan="8" style="text-align:right; padding:14px 16px; color:#475569; font-size:12px; letter-spacing:.05em; text-transform:uppercase;">
                                        @if($reportRows->hasPages())
                                            Page Total ({{ $reportRows->count() }} rows):
                                        @else
                                             Total ({{ $reportRows->count() }} rows):
                                        @endif
                                    </td>
                                    <td class="crm-pay-money" style="padding:14px; font-size:13px; color:#0f172a;">
                                        Rs {{ number_format($reportRows->filter(fn($r) => !in_array(strtolower(str_replace([' ', '-'], '_', (string)($r->payment_type ?: $r->collection_type))), ['balance_payment']))->sum(fn($r) => (float)($r->total_amount ?? 0)), 2) }}
                                    </td>
                                    <td class="crm-pay-money" style="padding:14px; font-size:13px; color:#047857;">
                                        Rs {{ number_format($reportRows->sum('received_amount'), 2) }}
                                    </td>
                                    <td class="crm-pay-money" style="padding:14px; font-size:13px; color:#7c3aed;">
                                        Rs {{ number_format($reportRows->sum('tds_amount'), 2) }}
                                    </td>
                                    <td class="crm-pay-money" style="padding:14px; font-size:13px; color:#dc2626;">
                                        Rs {{ number_format($reportRows->sum('outstanding_amount'), 2) }}
                                    </td>
                                    <td colspan="3"></td>
                                </tr>
                                @if($reportRows->hasPages())
                                    <tr style="background:#f1f5f9; font-weight:900; border-top:1px solid #cbd5e1;">
                                        <td colspan="8" style="text-align:right; padding:14px 16px; color:#0f172a; font-size:12px; letter-spacing:.05em; text-transform:uppercase;">
                                            Overall Report Total ({{ number_format($summary['rows']) }} rows):
                                        </td>
                                        <td class="crm-pay-money" style="padding:14px; font-size:14px; color:#0f172a;">
                                            Rs {{ number_format($summary['total_amount'], 2) }}
                                        </td>
                                        <td class="crm-pay-money" style="padding:14px; font-size:14px; color:#047857;">
                                            Rs {{ number_format($summary['received_amount'], 2) }}
                                        </td>
                                        <td class="crm-pay-money" style="padding:14px; font-size:14px; color:#7c3aed;">
                                            Rs {{ number_format($summary['tds_deduction_amount'], 2) }}
                                        </td>
                                        <td class="crm-pay-money" style="padding:14px; font-size:14px; color:#dc2626;">
                                            Rs {{ number_format($summary['outstanding_amount'], 2) }}
                                        </td>
                                        <td colspan="3"></td>
                                    </tr>
                                @endif
                            </tfoot>
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
    const tabInput = document.getElementById('crmActiveTabInput');

    function activateTab(targetId) {
        const activeTabBtn = document.querySelector(`#crmPaymentTabs [data-tab-target="${targetId}"]`);
        const activePanel = document.getElementById(targetId);
        if (activeTabBtn && activePanel) {
            tabs.forEach((button) => button.classList.remove('is-active'));
            panels.forEach((panel) => panel.classList.remove('is-active'));
            activeTabBtn.classList.add('is-active');
            activePanel.classList.add('is-active');
            if (tabInput) tabInput.value = targetId;
        }
    }

    const initialTab = new URLSearchParams(window.location.search).get('tab') || (tabInput ? tabInput.value : 'payment-data-panel');
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
        $('.crm-pay-select.select2').each(function() {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                allowClear: true,
                placeholder: $el.find('option:first').text(),
                width: '100%'
            });
            $el.next('.select2-container').find('.select2-selection--single').addClass('pay-select2-selection');
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
