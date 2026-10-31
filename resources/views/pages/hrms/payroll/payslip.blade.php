@extends('layouts.app')

@section('title', 'Payslip - ' . $item->employee_name . ' (' . $payroll->salary_month->format('F Y') . ')')

@php
    $cycle = $payroll->salary_cycle ?? $item->employee?->salary_cycle ?? '26_to_25';
    [$cStart, $cEnd] = \App\Http\Controllers\PayrollController::getCycleDateRange($payroll->salary_month, $cycle);
    $cycleLabel = in_array($cycle, ['1_to_30', '10_to_10'], true) ? '1 to 30 cycle' : '26 to 25 cycle';
    $monthLabel = $payroll->salary_month->format('F Y') . ' (' . $cStart->format('d M') . ' - ' . $cEnd->format('d M') . ')';
    $issuedOn = optional($payroll->salary_date)->format('d M, Y') ?: now()->format('d M, Y');
@endphp

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar {
    background: #ffffff;
    padding: 14px 28px;
    border-bottom: 1px solid #e1dee3;
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.inv-body { padding: 22px 28px 36px; display: flex; flex-direction: column; gap: 20px; flex: 1; }
@media (max-width: 900px) {
    .inv-topbar { padding: 12px 20px; }
    .inv-body { padding: 16px 20px 24px; }
}
.inv-top-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.inv-nav-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.inv-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    text-decoration: none;
    transition: all .15s ease;
}
.inv-back-btn:hover {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #fe5f04;
    transform: translateX(-1px);
}
.inv-breadcrumbs {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}
.inv-breadcrumbs a {
    color: #64748b;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: color .15s;
}
.inv-breadcrumbs a:hover {
    color: #fe5f04;
}
.inv-main-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
}
.inv-identity-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}
.inv-icon-badge {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    border: 1px solid #fed7aa;
    color: #fe5f04;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(254,95,4,0.12);
}
.inv-identity-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}
.inv-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.inv-title {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.2;
    margin: 0;
}
.inv-num-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 3px 10px;
    border-radius: 8px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    cursor: default;
}
.inv-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #64748b;
    flex-wrap: wrap;
}
.inv-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.inv-meta-dot {
    color: #cbd5e1;
    font-size: 10px;
}

/* Primary Action Buttons */
.inv-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.inv-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    text-decoration: none;
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #ffffff !important;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(254,95,4,0.18);
    transition: all .15s ease;
    line-height: 1.2;
}
.inv-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(254,95,4,0.28);
    color: #ffffff !important;
}
.inv-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    text-decoration: none;
    background: #ffffff;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    transition: all .15s ease;
    line-height: 1.2;
}
.inv-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a !important;
}

/* Status Badges */
.acm-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11.5px; font-weight:800; white-space:nowrap; }
.status-paid { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-cycle { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

/* KPI Overview Grid */
.inv-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
}
.inv-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.02);
    transition: all .2s ease;
}
.inv-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    border-color: #cbd5e1;
}
.inv-kpi-card.highlight {
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
    border-color: #86efac;
}
.inv-kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.inv-kpi-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}
.inv-kpi-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.inv-kpi-val {
    font-size: 20px;
    font-weight: 900;
    color: #0f172a;
    margin-top: 2px;
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}
.inv-kpi-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 3px;
    font-weight: 600;
}

/* Document Viewer Canvas & Toolbar */
.inv-canvas-wrap {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
}
.inv-doc-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 8px 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    flex-wrap: wrap;
    gap: 10px;
}
.inv-doc-tool-left, .inv-doc-tool-center, .inv-doc-tool-right {
    display: flex;
    align-items: center;
    gap: 8px;
}
.inv-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #334155;
}
.inv-tool-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
}
.inv-tool-btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}
.inv-tool-btn.primary {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
}
.inv-tool-btn.primary:hover {
    background: #fe5f04;
    border-color: #fe5f04;
    color: #ffffff;
}
.inv-zoom-label {
    font-size: 12px;
    font-weight: 800;
    color: #0f172a;
    min-width: 44px;
    text-align: center;
    font-family: monospace;
}
.inv-canvas-surface {
    background: #cbd5e1;
    border-radius: 14px;
    padding: 32px 16px 40px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    overflow-x: auto;
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.04);
}

/* Exact A4 Payslip Sheet Styling (Matching Invoice Document) */
.payslip-sheet {
    background: #ffffff;
    width: 100%;
    max-width: 820px;
    margin: 0 auto;
    border: 1px solid #000000;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
    position: relative;
    font-size: 11px;
    line-height: 1.4;
    color: #000000;
    transform-origin: top center;
    transition: transform 0.15s ease-out;
    box-sizing: border-box;
}

/* Header */
.ps-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px 14px 20px;
    border-bottom: 1px solid #000000;
}
.ps-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.ps-logo {
    height: 60px;
    width: auto;
    max-width: 150px;
    object-fit: contain;
}
.ps-comp-title {
    font-size: 15px;
    font-weight: 800;
    color: #000000;
    line-height: 1.25;
    margin-bottom: 4px;
}
.ps-comp-address {
    font-size: 10.5px;
    color: #000000;
    line-height: 1.4;
}
.ps-doc-title {
    font-size: 26px;
    font-weight: 900;
    color: #000000;
    letter-spacing: 0.5px;
    text-align: right;
    line-height: 1;
    margin: 0 0 6px 0;
}
.ps-doc-sub {
    font-size: 10px;
    color: #000000;
    text-align: right;
    font-weight: 600;
}

/* 2-Column Metadata Box */
.ps-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid #000000;
}
.ps-meta-col {
    padding: 8px 14px;
}
.ps-meta-col.border-left {
    border-left: 1px solid #000000;
}
.ps-meta-tbl {
    width: 100%;
    border-collapse: collapse;
}
.ps-meta-tbl td {
    padding: 3px 0;
    font-size: 11px;
    vertical-align: top;
}
.ps-meta-label {
    width: 110px;
    color: #334155;
    font-weight: 600;
}
.ps-meta-colon {
    width: 14px;
    color: #000000;
    text-align: left;
}
.ps-meta-val {
    font-weight: 800;
    color: #000000;
}

/* Unified Salary Table */
.ps-main-tbl {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 11px;
    margin: 0;
    padding: 0;
    border: none;
}
.ps-th-head {
    background: #f3f4f6;
    color: #000000;
    font-size: 11px;
    font-weight: 800;
    padding: 6px 12px;
    border-bottom: 1px solid #000000;
    border-right: 1px solid #000000;
    text-align: left;
}
.ps-th-head:last-child {
    border-right: none !important;
}
.ps-th-head.text-right {
    text-align: right;
}
.ps-td-cell {
    padding: 7px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #e5e7eb;
    border-right: 1px solid #000000;
    color: #000000;
    font-size: 11px;
}
.ps-td-cell:last-child {
    border-right: none !important;
}
.ps-td-cell.text-right {
    text-align: right;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.ps-subtotal-row td {
    background: #f8fafc;
    font-weight: 800;
    border-top: 1px solid #000000;
    border-bottom: 1px solid #000000;
    padding: 7px 12px;
}

/* Bottom Summary Section */
.ps-bottom-grid {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    min-height: 180px;
}
.ps-bottom-left {
    border-right: 1px solid #000000;
    padding: 12px 14px 14px 14px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.ps-bottom-right {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.ps-sec-head {
    font-size: 11px;
    font-weight: 800;
    color: #000000;
    margin-bottom: 3px;
}
.ps-sec-body {
    font-size: 10.5px;
    color: #1e293b;
    line-height: 1.45;
}
.ps-words-box {
    margin-bottom: 12px;
    padding: 6px 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}
.ps-totals-tbl {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1px solid #000000;
}
.ps-totals-tbl td {
    padding: 5px 12px;
    vertical-align: middle;
}
.ps-totals-tbl .t-label {
    text-align: right;
    color: #000000;
    font-size: 11px;
    width: 55%;
}
.ps-totals-tbl .t-val {
    text-align: right;
    font-weight: 700;
    color: #000000;
    font-size: 11px;
    width: 45%;
    font-variant-numeric: tabular-nums;
}
.ps-totals-tbl .net-row td {
    background: #f0fdf4;
    border-top: 1px solid #000000;
    padding-top: 7px;
    padding-bottom: 7px;
}
.ps-sig-container {
    padding: 14px 12px 10px 12px;
    text-align: center;
}
.ps-sig-img {
    max-height: 44px;
    display: block;
    margin: 0 auto 4px auto;
}
.ps-sig-line {
    border-top: 1px solid #000000;
    padding-top: 4px;
    font-weight: 800;
    font-size: 10px;
    color: #000000;
}
.ps-footer-note {
    border-top: 1px solid #000000;
    padding: 6px 12px;
    font-size: 9.5px;
    color: #64748b;
    text-align: center;
    background: #fafafa;
}

/* Print Handling */
@media print {
    body { background: #fff !important; }
    .inv-topbar, .inv-kpi-grid, .inv-doc-toolbar, .navbar, .sidebar, header, footer { display: none !important; }
    .inv-page, .inv-body, .inv-canvas-surface, .inv-canvas-wrap {
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    .payslip-sheet {
        box-shadow: none !important;
        border: 1px solid #000 !important;
        max-width: 100% !important;
        transform: none !important;
        page-break-after: avoid;
    }
}
</style>
@endpush

@section('content')
<div class="inv-page">
    <!-- Topbar Navigation & Actions -->
    <div class="inv-topbar">
        <div class="inv-top-nav">
            <div class="inv-nav-left">
                <a href="{{ route('payroll.show', $payroll) }}" class="inv-back-btn">
                    <i class="bi bi-arrow-left"></i> Back to Payroll Batch
                </a>
                <div class="inv-breadcrumbs">
                    <a href="{{ route('home') }}"><i class="bi bi-house-door"></i></a>
                    <span>/</span>
                    <a href="{{ route('payroll.index') }}">HRMS</a>
                    <span>/</span>
                    <a href="{{ route('payroll.show', $payroll) }}">{{ $payroll->salary_month->format('F Y') }}</a>
                    <span>/</span>
                    <span>Payslip &bull; {{ $item->employee_name }}</span>
                </div>
            </div>
            <div class="inv-header-actions">
                <a href="{{ route('payroll.payslip', [$payroll, $item]) }}?format=pdf" target="_blank" class="inv-btn-primary">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                </a>
                <button type="button" onclick="window.print()" class="inv-btn-secondary">
                    <i class="bi bi-printer-fill"></i> Print
                </button>
                <a href="{{ route('payroll.show', $payroll) }}" class="inv-btn-secondary">
                    <i class="bi bi-x-lg"></i> Close
                </a>
            </div>
        </div>

        <div class="inv-main-header">
            <div class="inv-identity-wrap">
                <div class="inv-icon-badge">
                    <i class="bi bi-file-earmark-person-fill"></i>
                </div>
                <div class="inv-identity-info">
                    <div class="inv-title-row">
                        <h1 class="inv-title">{{ $item->employee_name }}</h1>
                        <span class="inv-num-pill">#{{ $item->employee_code }}</span>
                        <span class="acm-badge status-paid">
                            <i class="bi bi-check-circle-fill"></i> VERIFIED &amp; PAID
                        </span>
                        <span class="acm-badge status-cycle">
                            <i class="bi bi-arrow-repeat"></i> {{ $cycleLabel }}
                        </span>
                    </div>
                    <div class="inv-meta-row">
                        <span class="inv-meta-item">
                            <i class="bi bi-briefcase"></i> {{ $item->designation ?: 'Employee' }}
                        </span>
                        @if($item->branch_name && $item->branch_name !== '—')
                            <span class="inv-meta-dot">&bull;</span>
                            <span class="inv-meta-item">
                                <i class="bi bi-building"></i> {{ $item->branch_name }}
                            </span>
                        @endif
                        <span class="inv-meta-dot">&bull;</span>
                        <span class="inv-meta-item">
                            <i class="bi bi-calendar3"></i> Salary Month: <strong>{{ $payroll->salary_month->format('F Y') }}</strong> ({{ $cStart->format('d M') }} - {{ $cEnd->format('d M') }})
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Body -->
    <div class="inv-body">
        <!-- KPI Metric Cards Grid -->
        <div class="inv-kpi-grid">
            <div class="inv-kpi-card">
                <div class="inv-kpi-icon" style="background:#eff6ff;color:#2563eb;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label">Gross Earned Salary</span>
                    <span class="inv-kpi-val">₹ {{ number_format((float) $item->earned_gross_salary, 2) }}</span>
                    <span class="inv-kpi-sub">Monthly CTC: ₹ {{ number_format((float) $item->gross_salary, 2) }}</span>
                </div>
            </div>

            <div class="inv-kpi-card">
                <div class="inv-kpi-icon" style="background:#f5f3ff;color:#7c3aed;">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label">Payable Days</span>
                    <span class="inv-kpi-val">{{ number_format((float) $item->payable_days, 1) }} <small style="font-size:12px;font-weight:600;color:#64748b;">/ {{ (int)$item->working_days }}</small></span>
                    <span class="inv-kpi-sub">Present: {{ number_format((float) $item->days_attended, 1) }} &bull; LOP: {{ number_format((float) $item->lop_days, 1) }}</span>
                </div>
            </div>

            <div class="inv-kpi-card">
                <div class="inv-kpi-icon" style="background:#fef2f2;color:#dc2626;">
                    <i class="bi bi-dash-circle-fill"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label">Total Deductions</span>
                    <span class="inv-kpi-val" style="color:#b91c1c;">- ₹ {{ number_format((float) $item->total_deductions, 2) }}</span>
                    <span class="inv-kpi-sub">PF: ₹{{ number_format((float) ($item->pf_employee_contribution + $item->pf_employer_contribution), 2) }} &bull; ESI: ₹{{ number_format((float) $item->esi_employee_contribution, 2) }}</span>
                </div>
            </div>

            <div class="inv-kpi-card highlight">
                <div class="inv-kpi-icon" style="background:#dcfce7;color:#16a34a;">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label" style="color:#15803d;">Net Take-Home Salary</span>
                    <span class="inv-kpi-val" style="color:#15803d;">₹ {{ number_format((float) $item->net_salary, 2) }}</span>
                    <span class="inv-kpi-sub" style="color:#166534;font-weight:700;">Final credited take-home</span>
                </div>
            </div>
        </div>

        <!-- Document Viewer Canvas & Toolbar (Like Invoice Screen) -->
        <div class="inv-canvas-wrap">
            <div class="inv-doc-toolbar">
                <div class="inv-doc-tool-left">
                    <span class="inv-pill">
                        <i class="bi bi-file-earmark-text-fill" style="color:#fe5f04;"></i>
                        A4 Portrait &bull; Salary Slip Document
                    </span>
                    <span style="font-size:11.5px;color:#64748b;font-weight:600;">
                        Generated: {{ $issuedOn }}
                    </span>
                </div>

                <div class="inv-doc-tool-center">
                    <button type="button" class="inv-tool-btn" id="btnZoomOut" title="Zoom Out">
                        <i class="bi bi-dash"></i>
                    </button>
                    <span class="inv-zoom-label" id="zoomLevelLabel">100%</span>
                    <button type="button" class="inv-tool-btn" id="btnZoomIn" title="Zoom In">
                        <i class="bi bi-plus"></i>
                    </button>
                    <button type="button" class="inv-tool-btn" id="btnZoomReset" title="Reset Zoom">
                        Reset
                    </button>
                </div>

                <div class="inv-doc-tool-right">
                    <button type="button" onclick="window.print()" class="inv-tool-btn">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="{{ route('payroll.payslip', [$payroll, $item]) }}?format=pdf" target="_blank" class="inv-tool-btn primary">
                        <i class="bi bi-file-earmark-pdf"></i> Download PDF
                    </a>
                </div>
            </div>

            <!-- Canvas Surface containing the exact A4 Payslip Sheet -->
            <div class="inv-canvas-surface">
                <div class="payslip-sheet" id="payslipDocumentSheet">
                    <!-- Sheet Header -->
                    <div class="ps-header">
                        <div class="ps-header-left">
                            @if(!empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="Company Logo" class="ps-logo">
                            @endif
                            <div>
                                <div class="ps-comp-title">{{ $companyName }}</div>
                                @if($companyAddress || $companyPhone || $companyEmail)
                                    <div class="ps-comp-address">
                                        {{ trim(collect([$companyAddress, $companyPhone, $companyEmail])->filter()->implode(' • ')) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="ps-doc-title">PAYSLIP</div>
                            <div class="ps-doc-sub">FOR {{ strtoupper($payroll->salary_month->format('F Y')) }}</div>
                            <div class="ps-doc-sub" style="margin-top:2px;color:#64748b;">Issued on: {{ $issuedOn }}</div>
                        </div>
                    </div>

                    <!-- 2-Column Metadata Box -->
                    <div class="ps-meta-grid">
                        <div class="ps-meta-col">
                            <table class="ps-meta-tbl">
                                <tr>
                                    <td class="ps-meta-label">Employee Name</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ $item->employee_name }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">Employee ID</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ $item->employee_code ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">Designation</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ $item->designation ?: 'Employee' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">Date of Joining</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ optional($item->date_of_joining)->format('d M Y') ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">UAN / PF No.</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ $item->uan_no ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">ESI Number</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ $item->esi_no ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="ps-meta-col border-left">
                            <table class="ps-meta-tbl">
                                <tr>
                                    <td class="ps-meta-label">Bank &amp; Branch</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ optional($item->employee)->bank_name ? (optional($item->employee)->bank_name . ' (' . (optional($item->employee)->bank_branch ?: 'Main') . ')') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">Salary Cycle</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ $cycleLabel }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">Cycle Period</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ $cStart->format('d M Y') }} - {{ $cEnd->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">Working Days</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ number_format((float) $item->working_days, 0) }} Days</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">Present / Paid Days</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ number_format((float) $item->days_attended, 1) }} / {{ number_format((float) $item->payable_days, 1) }} Days</td>
                                </tr>
                                <tr>
                                    <td class="ps-meta-label">Leave / LOP</td>
                                    <td class="ps-meta-colon">:</td>
                                    <td class="ps-meta-val">{{ number_format((float) $item->leave_days, 1) }} / {{ number_format((float) $item->lop_days, 1) }}{{ (float) ($item->timesheet_lop_days ?? 0) > 0 ? ' (TS: ' . number_format((float) $item->timesheet_lop_days, 1) . ')' : '' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Salary Breakdown Annexure Table -->
                    <table class="ps-main-tbl">
                        <thead>
                            <tr>
                                <th class="ps-th-head" style="width:32%;">Earnings &amp; Allowances</th>
                                <th class="ps-th-head text-right" style="width:18%;">Amount (₹)</th>
                                <th class="ps-th-head" style="width:32%;">Deductions</th>
                                <th class="ps-th-head text-right" style="width:18%;">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-td-cell">Basic Salary</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->earned_basic_salary, 2) }}</td>
                                <td class="ps-td-cell">Provident Fund (PF Emp {{ number_format((float) $item->pf_employee_percentage, 0) }}%)</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->pf_employee_contribution, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-td-cell">House Rent Allowance (HRA)</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->earned_hra, 2) }}</td>
                                <td class="ps-td-cell">Employer PF ({{ number_format((float) $item->pf_employer_percentage, 0) }}%)</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->pf_employer_contribution, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-td-cell">Travel Allowance</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->earned_travel_allowance, 2) }}</td>
                                <td class="ps-td-cell">Employee State Insurance (ESI {{ number_format((float) $item->esi_employee_percentage, 0) }}%)</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->esi_employee_contribution, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-td-cell">Special / Other Allowance</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->earned_other_allowance, 2) }}</td>
                                <td class="ps-td-cell">Professional Tax (PT)</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->professional_tax, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-td-cell"></td>
                                <td class="ps-td-cell text-right"></td>
                                <td class="ps-td-cell">Tax Deducted at Source (TDS)</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) $item->tds_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-td-cell"></td>
                                <td class="ps-td-cell text-right"></td>
                                <td class="ps-td-cell">Loan / Other Deductions</td>
                                <td class="ps-td-cell text-right">{{ number_format((float) ($item->loan_deduction + $item->other_deduction), 2) }}</td>
                            </tr>
                            <tr class="ps-subtotal-row">
                                <td class="ps-td-cell" style="font-weight:800;">Total Gross Earnings</td>
                                <td class="ps-td-cell text-right" style="font-weight:800;">₹ {{ number_format((float) $item->earned_gross_salary, 2) }}</td>
                                <td class="ps-td-cell" style="font-weight:800;">Total Deductions</td>
                                <td class="ps-td-cell text-right" style="font-weight:800;color:#b91c1c;">₹ {{ number_format((float) $item->total_deductions, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Bottom Summary & Net Pay Section -->
                    <div class="ps-bottom-grid">
                        <div class="ps-bottom-left">
                            <div>
                                <div class="ps-sec-head">Net Salary in Words:</div>
                                <div class="ps-words-box">
                                    <strong style="color:#0f172a;text-transform:capitalize;">
                                        {{ function_exists('amount_in_words') ? amount_in_words($item->net_salary) : '' }}
                                    </strong>
                                </div>
                                <div class="ps-sec-head">Payment Mode &amp; Account:</div>
                                <div class="ps-sec-body">
                                    Direct Bank Transfer &bull; {{ optional($item->employee)->account_number ? 'A/C: ****' . substr($item->employee->account_number, -4) : 'Direct Account' }}
                                    @if(optional($item->employee)->ifsc_code)
                                        ({{ $item->employee->ifsc_code }})
                                    @endif
                                </div>
                            </div>
                            <div style="font-size:9.5px;color:#64748b;margin-top:10px;">
                                * This is a computer-generated document requiring no physical signature if signed electronically.
                            </div>
                        </div>

                        <div class="ps-bottom-right">
                            <table class="ps-totals-tbl">
                                <tr>
                                    <td class="t-label">Gross Earnings:</td>
                                    <td class="t-val">₹ {{ number_format((float) $item->earned_gross_salary, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="t-label">Total Deductions:</td>
                                    <td class="t-val" style="color:#b91c1c;">- ₹ {{ number_format((float) $item->total_deductions, 2) }}</td>
                                </tr>
                                <tr class="net-row">
                                    <td class="t-label" style="font-size:12.5px;font-weight:800;color:#15803d;">Net Salary Payable:</td>
                                    <td class="t-val" style="font-size:14px;font-weight:900;color:#15803d;">₹ {{ number_format((float) $item->net_salary, 2) }}</td>
                                </tr>
                            </table>

                            <div class="ps-sig-container">
                                @if(!empty($signatureUrl))
                                    <img src="{{ $signatureUrl }}" alt="Signature" class="ps-sig-img">
                                @elseif(!empty($signatureBase64))
                                    <img src="{{ $signatureBase64 }}" alt="Signature" class="ps-sig-img">
                                @else
                                    <div style="height:36px;"></div>
                                @endif
                                <div class="ps-sig-line">Authorized Signatory</div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Note -->
                    <div class="ps-footer-note">
                        Confidential &bull; Please review your payslip and reach out to the HR department within 7 business days if any discrepancy is noticed.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    let currentZoom = 1.0;
    const sheet = document.getElementById('payslipDocumentSheet');
    const label = document.getElementById('zoomLevelLabel');

    function applyZoom(z) {
        currentZoom = Math.min(Math.max(z, 0.6), 1.6);
        if (sheet) {
            sheet.style.transform = `scale(${currentZoom})`;
        }
        if (label) {
            label.textContent = Math.round(currentZoom * 100) + '%';
        }
    }

    const btnIn = document.getElementById('btnZoomIn');
    const btnOut = document.getElementById('btnZoomOut');
    const btnReset = document.getElementById('btnZoomReset');

    if (btnIn) btnIn.addEventListener('click', () => applyZoom(currentZoom + 0.1));
    if (btnOut) btnOut.addEventListener('click', () => applyZoom(currentZoom - 0.1));
    if (btnReset) btnReset.addEventListener('click', () => applyZoom(1.0));
})();
</script>
@endpush
@endsection
