@extends('layouts.app')

@section('title', 'Tax Invoice #' . $invoice->invoice_number . ' - Accounts Sales')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar {
    background: #ffffff;
    padding: 12px 28px;
    border-bottom: 1px solid #e1dee3;
    border-radius: 0;
    box-shadow: none;
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.inv-body { padding: 22px 28px 34px; display: flex; flex-direction: column; gap: 20px; flex: 1; }
@media (max-width: 900px) {
    .inv-topbar { padding: 12px 20px; }
    .inv-body { padding: 16px 20px 24px; }
}
.inv-top-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 12px;
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
    padding: 5px 12px;
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
.inv-nav-right {
    display: flex;
    align-items: center;
    gap: 8px;
}
.inv-utility-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    text-decoration: none;
    transition: all .15s ease;
}
.inv-utility-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.inv-utility-btn.danger-btn {
    background: #ffffff;
    border-color: #fee2e2;
    color: #dc2626;
    padding: 5px 10px;
}
.inv-utility-btn.danger-btn:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #b91c1c;
}

/* Main Row */
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
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    border: 1px solid #fed7aa;
    color: #fe5f04;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(254,95,4,0.1);
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
    cursor: pointer;
    transition: all .15s ease;
}
.inv-num-pill:hover {
    background: #fff7ed;
    border-color: #fdba74;
    color: #fe5f04;
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

/* Primary Action Buttons (Compact & Small) */
.inv-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.inv-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 13px;
    border-radius: 8px;
    font-size: 12px;
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
.inv-btn-success {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 13px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #ffffff !important;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(22,163,74,0.18);
    transition: all .15s ease;
    line-height: 1.2;
}
.inv-btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(22,163,74,0.28);
    color: #ffffff !important;
}
.inv-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
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
.inv-btn-danger-outline {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    background: #fef2f2;
    color: #b91c1c !important;
    border: 1px solid #fecaca;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    transition: all .15s ease;
    line-height: 1.2;
}
.inv-btn-danger-outline:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #991b1b !important;
}

/* Actions Dropdown */
.inv-actions { position:relative; display:inline-flex; }
.inv-action-trigger {
    display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:8px;
    font-size:12px; font-weight:700; color:#334155; background:#fff; border:1px solid #cbd5e1;
    cursor:pointer; transition:all .15s ease; font-family:inherit; line-height:1.2;
}
.inv-action-trigger:hover,
.inv-actions.open .inv-action-trigger {
    border-color:#fe5f04; color:#fe5f04; background:#fff7ed; box-shadow:0 2px 8px rgba(254,95,4,.15);
}
.inv-actions.open .inv-action-trigger .bi-chevron-down {
    transform:rotate(180deg);
}
.inv-action-menu {
    position:absolute; top:calc(100% + 6px); right:0; min-width:195px; padding:6px; border-radius:10px;
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
    border-radius:6px; font-size:12px; font-weight:600; color:#334155; text-decoration:none; cursor:pointer; font-family:inherit; text-align:left; box-sizing:border-box; transition:background .15s, color .15s;
}
.inv-action-item:hover { background:#f8fafc; color:#0f172a; }
.inv-action-item i { font-size:14px; width:16px; text-align:center; }
.inv-action-item.payment:hover { background:#f0fdf4; color:#16a34a; }
.inv-action-item.edit:hover { background:#fff7ed; color:#ea580c; }
.inv-action-item.mail:hover { background:#f0f9ff; color:#0284c7; }
.inv-action-item.print:hover { background:#f0fdf4; color:#16a34a; }
.inv-action-item.credit:hover { background:#fef2f2; color:#dc2626; }
.inv-action-item.delete { color:#dc2626; }
.inv-action-item.delete:hover { background:#fef2f2; color:#b91c1c; }
.inv-action-divider { height:1px; background:#f1f5f9; margin:4px 0; }
@keyframes simModalIn {
    from { opacity: 0; transform: scale(0.96) translateY(-8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes simSpin {
    to { transform: rotate(360deg); }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
.acm-btn-secondary:hover { background:#e2e8f0; color:#0f172a; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

/* Status Badges */
.acm-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:800; white-space:nowrap; }
.status-paid { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-partially-paid { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.status-sent { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
.status-created { background:#ede9fe; color:#6d28d9; border:1px solid #ddd6fe; }
.status-draft { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.status-overdue { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.status-cancelled { background:#f1f5f9; color:#94a3b8; border:1px solid #cbd5e1; text-decoration:line-through; }

/* KPI Overview Grid */
.inv-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
}
.inv-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.02);
    transition: all .2s;
}
.inv-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    border-color: #cbd5e1;
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
    font-size: 19px;
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
.inv-prog-track {
    width: 100%;
    height: 5px;
    background: #f1f5f9;
    border-radius: 999px;
    margin-top: 6px;
    overflow: hidden;
}
.inv-prog-bar {
    height: 100%;
    background: linear-gradient(90deg, #16a34a, #22c55e);
    border-radius: 999px;
    transition: width .4s ease;
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
.inv-pill-light {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
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
    background: #e2e8f0;
    border-radius: 14px;
    padding: 28px 16px 36px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    overflow-x: auto;
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.03);
}

/* Exact A4 Invoice Sheet Styling */
.invoice-sheet {
    background: #ffffff;
    width: 100%;
    max-width: 820px;
    margin: 0 auto;
    border: 1px solid #000000;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
    position: relative;
    font-size: 11px;
    line-height: 1.4;
    color: #000000;
    transform-origin: top center;
    transition: transform 0.15s ease-out;
}

/* Top Header */
.inv-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px 20px 14px 20px;
    border-bottom: 1px solid #000000;
}
.inv-header-left {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.inv-logo {
    height: 75px;
    width: auto;
    max-width: 140px;
    object-fit: contain;
}
.inv-comp-title {
    font-size: 14.5px;
    font-weight: 800;
    color: #000000;
    line-height: 1.25;
    margin-bottom: 3px;
}
.inv-comp-address {
    font-size: 11px;
    color: #000000;
    line-height: 1.35;
}
.inv-tax-title {
    font-size: 24px;
    font-weight: 900;
    color: #000000;
    letter-spacing: 0.5px;
    margin-top: 15px;
    text-align: right;
}

/* 2-Column Metadata Box */
.meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-bottom: 1px solid #000000;
}
.meta-col {
    padding: 6px 14px;
}
.meta-col.border-left {
    border-left: 1px solid #000000;
}
.meta-tbl {
    width: 100%;
    border-collapse: collapse;
}
.meta-tbl td {
    padding: 2.5px 0;
    font-size: 11px;
    vertical-align: top;
}
.meta-label {
    width: 115px;
    color: #000000;
}
.meta-colon {
    width: 14px;
    color: #000000;
    text-align: left;
}
.meta-val {
    font-weight: 800;
    color: #000000;
}

/* Unified Table for Parties, Items & Bottom Summary */
.inv-main-tbl {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 11px;
    margin: 0;
    padding: 0;
    border: none;
}

/* Parties Header & Body (Rows 1 & 2) */
.party-head {
    background: #f3f4f6;
    color: #000000;
    font-size: 11px;
    font-weight: 800;
    padding: 5px 12px;
    text-align: left;
    border-top: none !important; /* Exactly 1px below meta-grid border-bottom */
    border-bottom: 1px solid #000000;
}
.party-head.party-left, .party-body.party-left {
    border-left: none !important;
}
.party-head.party-right, .party-body.party-right {
    border-right: none !important;
}
.party-body {
    padding: 8px 12px;
    font-size: 11px;
    line-height: 1.4;
    color: #000000;
    vertical-align: top;
    height: 105px;
    border-top: none !important;
    border-bottom: 1px solid #000000;
}
.party-name {
    font-weight: 800;
    color: #000000;
    margin-bottom: 2px;
}
.col-border-left {
    border-left: 1px solid #000000 !important;
}

/* Items Header (Row 3) */
.th-item {
    background: #f3f4f6;
    color: #000000;
    font-weight: 800;
    border-top: none !important;
    border-bottom: 1px solid #000000;
    border-right: 1px solid #000000;
    padding: 6px 8px;
    vertical-align: middle;
}
.th-item:first-child, .td-item:first-child {
    border-left: none !important;
}
.th-item:last-child, .td-item:last-child {
    border-right: none !important;
}

/* Items Data Cells */
.inv-main-tbl tbody td.td-item {
    padding: 8px 10px;
    vertical-align: top;
    border-top: none !important;
    border-right: 1px solid #000000;
    border-bottom: 1px solid #000000;
    color: #000000;
}
.inv-main-tbl tbody tr.item-row:hover td.td-item {
    background-color: #fafbfc;
}
.td-item, .t-val, .meta-val {
    font-variant-numeric: tabular-nums;
}

/* Bottom Section Cells */
.bottom-left-cell {
    border-left: none !important;
    border-right: 1px solid #000000 !important;
    border-bottom: none !important;
    border-top: none !important;
    padding: 12px 14px 14px 14px !important;
    vertical-align: top !important;
}
.bottom-right-cell {
    border-left: none !important;
    border-right: none !important;
    border-bottom: none !important;
    border-top: none !important;
    padding: 0 !important;
    vertical-align: top !important;
    height: 100%;
}
.bottom-right-wrap {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    min-height: 290px;
}

.sec-head {
    font-size: 11px;
    font-weight: 800;
    color: #000000;
}
.sec-body {
    font-size: 11px;
    color: #000000;
    line-height: 1.45;
    margin-top: 2px;
}

/* Totals Table */
.totals-tbl {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    border-bottom: 1px solid #000000;
}
.totals-tbl td {
    padding: 4px 10px;
    vertical-align: middle;
    border: none;
}
.totals-tbl .t-label {
    text-align: right;
    color: #000000;
    font-size: 11px;
    width: 60%;
}
.totals-tbl .t-val {
    text-align: right;
    font-weight: 600;
    color: #000000;
    font-size: 11px;
    width: 40%;
}
.totals-tbl .total-row td {
    font-size: 12px;
    font-weight: 800;
    color: #000000;
    padding-top: 5px;
    padding-bottom: 5px;
}
.totals-tbl .balance-row td {
    font-size: 13px;
    font-weight: 800;
    color: #000000;
    border-top: 1px solid #000000;
    padding-top: 6px;
    padding-bottom: 6px;
}

.sig-container {
    padding: 14px 12px 10px 12px;
    text-align: center;
}
.sig-for {
    font-size: 10.5px;
    font-weight: 800;
    color: #000000;
    text-transform: uppercase;
}
.sig-image {
    max-height: 55px;
    max-width: 150px;
    object-fit: contain;
    margin: 6px auto;
    display: block;
}
.sig-auth {
    font-size: 11px;
    font-weight: 700;
    color: #000000;
}

.page-num {
    text-align: right;
    font-size: 11px;
    color: #64748b;
    padding-top: 6px;
    max-width: 800px;
    width: 100%;
    margin: 0 auto;
}
</style>
@endpush

@section('content')
@php
    $sub = (float) $invoice->subtotal;
    $totalAmt = (float) $invoice->total_amount;
    $paidAmt = (float) $invoice->paid_amount;
    $balDue = (float) $invoice->balance_due;
    $totalTds = (float) ($invoice->total_tds ?? 0);
    $totalSettled = $paidAmt + $totalTds;
    $paidPct = $totalAmt > 0 ? min(100, round(($totalSettled / $totalAmt) * 100)) : 0;

    $dueDate = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date) : \Carbon\Carbon::parse($invoice->invoice_date);
    $isOverdue = $balDue > 0 && $dueDate->isPast() && !$dueDate->isToday();
    $daysDiff = abs((int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false));

    $cgstPct = ($sub > 0 && $invoice->total_cgst > 0) ? round(($invoice->total_cgst / $sub) * 100, 2) : 9;
    $sgstPct = ($sub > 0 && $invoice->total_sgst > 0) ? round(($invoice->total_sgst / $sub) * 100, 2) : 9;
    $igstPct = ($sub > 0 && $invoice->total_igst > 0) ? round(($invoice->total_igst / $sub) * 100, 2) : 18;
    $isTNState = !empty($invoice->place_of_supply) ? (str_contains(strtolower($invoice->place_of_supply), 'tamil nadu') || str_contains($invoice->place_of_supply, '33')) : true;

    // Place of Supply formatted (e.g. "[33] Tamil Nadu" -> "Tamil Nadu (33)")
    $pos = $invoice->place_of_supply ?: '[33] Tamil Nadu';
    if (preg_match('/\[(\d+)\]\s*(.*)/', $pos, $matches)) {
        $formattedPos = trim($matches[2]) . ' (' . $matches[1] . ')';
    } else {
        $formattedPos = $pos;
    }

    $logoUrl = $setting?->logo_url ?: asset('images/LOGO_STS.png');
    $signatureUrl = $setting?->signature_url ?: asset('images/sign.jpeg');
@endphp
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        {{-- Row 1: Back Navigation & Secondary Utilities --}}
        <div class="inv-top-nav">
            <div class="inv-nav-left">
                <a href="{{ route('accounts.sales.invoices.index') }}" class="inv-back-btn" title="Back to All Invoices">
                    <i class="bi bi-arrow-left"></i>
                    <span>All Invoices</span>
                </a>
                <div class="inv-breadcrumbs">
                    <a href="{{ route('accounts.dashboard') }}"><i class="bi bi-house-door"></i> Accounts</a>
                    <span>/</span>
                    <a href="{{ route('accounts.sales.invoices.index') }}">Sales Invoices</a>
                    <span>/</span>
                    <span style="color:#0f172a; font-weight:800;">#{{ $invoice->invoice_number }}</span>
                </div>
            </div>

            <div class="inv-nav-right">
                <button type="button" class="inv-utility-btn" onclick="copyInvoiceLink()" title="Copy Shareable Link">
                    <i class="bi bi-share"></i>
                    <span>Share Link</span>
                </button>
                <form method="POST" action="{{ route('accounts.sales.invoices.destroy', $invoice->id) }}" onsubmit="return confirm('Are you sure you want to delete invoice #{{ $invoice->invoice_number }}? This action cannot be undone.')" style="margin:0; display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inv-utility-btn danger-btn" title="Delete Invoice">
                        <i class="bi bi-trash3"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Row 2: Main Invoice Header & Primary Actions --}}
        <div class="inv-main-header">
            <div class="inv-identity-wrap">
                <div class="inv-icon-badge">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="inv-identity-info">
                    <div class="inv-title-row">
                        <h1 class="inv-title">Invoice</h1>
                        <span class="inv-num-pill" onclick="copyInvoiceNumber('{{ $invoice->invoice_number }}')" title="Click to copy invoice number">
                            <span>#{{ $invoice->invoice_number }}</span>
                            <i class="bi bi-clipboard" style="font-size:11px; opacity:0.6;"></i>
                        </span>
                        <span id="invoiceStatusBadgeContainer">
                            {!! $invoice->status_badge !!}
                        </span>
                    </div>
                    <div class="inv-meta-row">
                        <span class="inv-meta-item">
                            <i class="bi bi-calendar3"></i>
                            <span>Issued {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') }}</span>
                        </span>
                        @if($invoice->due_date)
                            <span class="inv-meta-dot">•</span>
                            <span class="inv-meta-item">
                                <i class="bi bi-clock-history"></i>
                                <span>Due {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</span>
                            </span>
                        @endif
                        <span class="inv-meta-dot">•</span>
                        <span class="inv-meta-item">
                            <i class="bi bi-person"></i>
                            <span>Client: <strong style="color:#0f172a;">{{ $invoice->customer_name }}</strong></span>
                        </span>
                        @if($invoice->customer_phone)
                            <span class="inv-meta-dot">•</span>
                            <span class="inv-meta-item">
                                <i class="bi bi-telephone"></i>
                                <span>{{ $invoice->customer_phone }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="inv-header-actions">
              

                @if($balDue > 0)
                    <button type="button" class="inv-btn-success" onclick="openRecordPaymentModal()" title="Record Payment / TDS">
                        <i class="bi bi-cash-stack"></i>
                        <span>Record Payment</span>
                    </button>
                @endif

                <button type="button" class="inv-btn-danger-outline" onclick="openCreditNoteModal()" title="Issue Credit Note against this invoice">
                    <i class="bi bi-file-earmark-minus"></i>
                    <span>Add Credit Note</span>
                </button>

                <div class="inv-actions" data-inv-actions>
                    <button type="button" class="inv-action-trigger" onclick="toggleInvoiceActionMenu(event, this)" title="Actions for Invoice #{{ $invoice->invoice_number }}">
                        <i class="bi bi-gear-fill" style="font-size:11px; color:#fe5f04;"></i>
                        <span>Actions</span>
                        <i class="bi bi-chevron-down" style="font-size:10px; transition:transform .2s;"></i>
                    </button>
                    <div class="inv-action-menu">
                        <a href="{{ route('accounts.sales.invoices.edit', $invoice->id) }}" class="inv-action-item edit">
                            <i class="bi bi-pencil-square" style="color:#ea580c;"></i>
                            <span>Edit Invoice</span>
                        </a>

                        <button type="button" class="inv-action-item mail" onclick="openSendInvoiceMailModal(event)">
                            <i class="bi bi-envelope-paper-fill" style="color:#0284c7;"></i>
                            <span>Send Mail</span>
                        </button>

                        <a href="{{ route('accounts.sales.invoices.print', $invoice->id) }}" target="_blank" class="inv-action-item print">
                            <i class="bi bi-printer" style="color:#16a34a;"></i>
                            <span>Print / PDF</span>
                        </a>

                        @if($balDue > 0)
                            <button type="button" class="inv-action-item payment" onclick="openRecordPaymentModal()">
                                <i class="bi bi-cash-stack" style="color:#16a34a;"></i>
                                <span>Record Payment</span>
                            </button>
                        @endif

                        <button type="button" class="inv-action-item credit" onclick="openCreditNoteModal()">
                            <i class="bi bi-file-earmark-minus" style="color:#dc2626;"></i>
                            <span>Add Credit Note</span>
                        </button>

                        <button type="button" class="inv-action-item" onclick="copyInvoiceLink()">
                            <i class="bi bi-share" style="color:#2563eb;"></i>
                            <span>Share Link</span>
                        </button>

                        <div class="inv-action-divider"></div>

                        <form method="POST" action="{{ route('accounts.sales.invoices.destroy', $invoice->id) }}" onsubmit="return confirm('Are you sure you want to delete invoice #{{ $invoice->invoice_number }}? This action cannot be undone.')" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inv-action-item delete">
                                <i class="bi bi-trash3"></i>
                                <span>Delete Invoice</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="inv-body">

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
            <i class="bi bi-x-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Financial Overview Ribbon (4 Quick KPI Cards) --}}
    <div class="inv-kpi-grid">
        <div class="inv-kpi-card">
            <div class="inv-kpi-icon" style="background:#fff7ed; color:#ea580c;">
                <i class="bi bi-receipt-cutoff"></i>
            </div>
            <div class="inv-kpi-info">
                <div class="inv-kpi-label">Total Invoiced</div>
                <div class="inv-kpi-val">₹{{ number_format($totalAmt, 2) }}</div>
                <div class="inv-kpi-sub">Subtotal: ₹{{ number_format($sub, 2) }} • Taxes: ₹{{ number_format($totalAmt - $sub, 2) }}</div>
            </div>
        </div>

        <div class="inv-kpi-card">
            <div class="inv-kpi-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="inv-kpi-info">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span class="inv-kpi-label">Payment Received</span>
                    <span style="font-size:11px; font-weight:800; color:#16a34a;">{{ $paidPct }}%</span>
                </div>
                <div class="inv-kpi-val" style="color:#16a34a;">₹{{ number_format($paidAmt, 2) }}</div>
                <div class="inv-prog-track">
                    <div class="inv-prog-bar" style="width:{{ $paidPct }}%;"></div>
                </div>
                @if($totalTds > 0)
                    <div class="inv-kpi-sub" style="color:#ea580c; font-weight:700; margin-top:2px;">+ ₹{{ number_format($totalTds, 2) }} TDS (Total: ₹{{ number_format($totalSettled, 2) }})</div>
                @endif
            </div>
        </div>

        <div class="inv-kpi-card">
            <div class="inv-kpi-icon" style="background:{{ $balDue > 0 ? '#fef2f2' : '#f0fdf4' }}; color:{{ $balDue > 0 ? '#dc2626' : '#16a34a' }};">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="inv-kpi-info">
                <div class="inv-kpi-label">Balance Due</div>
                <div class="inv-kpi-val" style="color:{{ $balDue > 0 ? '#dc2626' : '#16a34a' }};">
                    ₹{{ number_format($balDue, 2) }}
                </div>
                <div class="inv-kpi-sub">
                    @if($balDue <= 0)
                        <span style="color:#16a34a; font-weight:800;">✓ Fully Cleared</span>
                    @else
                        <span style="color:#dc2626; font-weight:800;">Pending Payment</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="inv-kpi-card">
            <div class="inv-kpi-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-calendar3"></i>
            </div>
            <div class="inv-kpi-info">
                <div class="inv-kpi-label">Payment Terms</div>
                <div class="inv-kpi-val" style="font-size:16px;">{{ $invoice->payment_terms ?: 'Due on Receipt' }}</div>
                <div class="inv-kpi-sub">
                    @if($balDue <= 0)
                        <span style="color:#16a34a; font-weight:700;">Completed</span>
                    @elseif($isOverdue)
                        <span style="color:#dc2626; font-weight:700;">⚠️ Overdue by {{ $daysDiff }} {{ Str::plural('day', $daysDiff) }}</span>
                    @elseif($dueDate->isToday())
                        <span style="color:#ea580c; font-weight:700;">⏰ Due Today</span>
                    @else
                        <span style="color:#2563eb; font-weight:700;">Due on {{ $dueDate->format('d M Y') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Document Viewer Canvas with Zoom & Action Controls --}}
    <div class="inv-canvas-wrap">
        {{-- Floating/Sticky Canvas Control Bar --}}
        <div class="inv-doc-toolbar">
            <div class="inv-doc-tool-left">
                <span class="inv-pill"><i class="bi bi-file-earmark-pdf-fill" style="color:#ef4444;"></i> Tax Invoice (A4)</span>
                <span class="inv-pill-light"><i class="bi bi-file-text"></i> Page 1 of 1</span>
            </div>
            <div class="inv-doc-tool-center">
                <button type="button" class="inv-tool-btn" onclick="zoomInvoice(-0.1)" title="Zoom Out">
                    <i class="bi bi-dash-lg"></i>
                </button>
                <span id="zoomLevelDisplay" class="inv-zoom-label">100%</span>
                <button type="button" class="inv-tool-btn" onclick="zoomInvoice(0.1)" title="Zoom In">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <button type="button" class="inv-tool-btn" onclick="resetZoom()" title="Reset Zoom">
                    <i class="bi bi-arrows-angle-contract"></i> 100%
                </button>
                <button type="button" class="inv-tool-btn" onclick="toggleFitWidth()" id="fitWidthBtn" title="Fit Screen Width">
                    <i class="bi bi-arrows-expand"></i> Fit
                </button>
            </div>
            <div class="inv-doc-tool-right">
                <button type="button" class="inv-tool-btn" onclick="copyInvoiceLink()" title="Copy Link to Clipboard">
                    <i class="bi bi-link-45deg"></i> Share Link
                </button>
                <a href="{{ route('accounts.sales.invoices.print', $invoice->id) }}" target="_blank" class="inv-tool-btn primary" title="Open Print Dialog">
                    <i class="bi bi-printer-fill"></i> Print
                </a>
            </div>
        </div>

        {{-- Canvas Surface --}}
        <div class="inv-canvas-surface" id="invoiceCanvas">
            <div class="invoice-sheet" id="invoiceSheet">

            {{-- 1. Top Header --}}
            <div class="inv-header">
                <div class="inv-header-left">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="inv-logo">
                    @endif
                    <div>
                        <div class="inv-comp-title">{{ $setting?->company_name ?: 'Sai Techno Solutions Private Limited' }}</div>
                        <div class="inv-comp-address">
                            @if($setting?->company_address)
                                <div>{!! nl2br(e($setting->company_address)) !!}</div>
                            @else
                                <div>No: 6, 1st Floor, KCP Garden, Kaapi Kadai Stop, Saravanampatti</div>
                            @endif
                            @php
                                $cityStatePin = [];
                                if ($setting?->company_city) $cityStatePin[] = $setting->company_city;
                                if ($setting?->company_pincode) {
                                    if (!empty($cityStatePin)) {
                                        $cityStatePin[count($cityStatePin)-1] .= ' ' . $setting->company_pincode;
                                    } else {
                                        $cityStatePin[] = $setting->company_pincode;
                                    }
                                }
                                if ($setting?->company_state) $cityStatePin[] = $setting->company_state;
                            @endphp
                            @if(!empty($cityStatePin))
                                <div>{{ implode(', ', $cityStatePin) }}</div>
                            @else
                                <div>Coimbatore 641035, Tamil Nadu</div>
                            @endif
                            <div>India</div>
                            <div style="margin-top:2px;">
                                <strong>GSTIN</strong> {{ $setting?->company_gstin ?: '33ABPCS7671C1Z3' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="inv-tax-title">
                    TAX INVOICE
                </div>
            </div>

            {{-- 2. Invoice Metadata Grid --}}
            <div class="meta-grid">
                <div class="meta-col">
                    <table class="meta-tbl">
                        <tr>
                            <td class="meta-label">Invoice Number</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Invoice Date</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Terms</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $invoice->payment_terms ?: 'Due on Receipt' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Due Date</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                        </tr>
                    </table>
                </div>

                <div class="meta-col border-left">
                    <table class="meta-tbl">
                        <tr>
                            <td class="meta-label">Place Of Supply</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $formattedPos }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Sales person</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">
                                <span id="salesPersonDisplay">{{ $invoice->salesPerson?->name ?? ($invoice->creator?->name ?? '—') }}</span>
                                <button type="button" 
                                        onclick="openChangeSalesPersonModal()" 
                                        class="no-print" 
                                        title="Change Sales Person"
                                        style="margin-left:6px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; border-radius:4px; padding:1px 6px; font-size:10.5px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:3px; vertical-align:middle;">
                                    <i class="bi bi-pencil-fill" style="font-size:9.5px;"></i> Change
                                </button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- 3. Unified Table: Bill To / Ship To + Line Items + Bottom Summary --}}
            <table class="inv-main-tbl">
                <colgroup>
                    <col style="width: 5%;">   {{-- Col 1: # --}}
                    <col style="width: 45%;">  {{-- Col 2: Item & Description -> (Col 1 + Col 2 = 50% = Bill To) --}}
                    <col style="width: 10%;">  {{-- Col 3: HSN/SAC --}}
                    <col style="width: 8%;">   {{-- Col 4: Qty --}}
                    <col style="width: 16%;">  {{-- Col 5: Rate / Totals Label --}}
                    <col style="width: 16%;">  {{-- Col 6: Amount / Totals Val -> (Col 3..6 = 50% = Ship To) --}}
                </colgroup>
                <tbody>
                    {{-- Row 1: Bill To & Ship To Header --}}
                    <tr>
                        <th colspan="2" class="party-head party-left">Bill To</th>
                        <th colspan="4" class="party-head party-right col-border-left">Ship To</th>
                    </tr>

                    {{-- Row 2: Bill To & Ship To Content --}}
                    <tr>
                        <td colspan="2" class="party-body party-left">
                            <div class="party-name">{{ $invoice->customer_name }}</div>
                            @if($invoice->company_name && $invoice->company_name !== $invoice->customer_name)
                                <div style="font-weight:700; color:#334155; margin-bottom:2px;">{{ $invoice->company_name }}</div>
                            @endif
                            @if($invoice->billing_address)
                                <div>{!! nl2br(e($invoice->billing_address)) !!}</div>
                            @endif
                            @php
                                $bCityState = array_filter([$invoice->billing_pincode, $invoice->billing_city, $invoice->billing_state]);
                            @endphp
                            @if(!empty($bCityState))
                                <div>{{ implode(' ', $bCityState) }}</div>
                            @endif
                            <div>{{ $invoice->billing_country ?: 'India' }}</div>
                            @php
                                $billingGstin = $invoice->billing_gstin ?: $invoice->customer_gstin;
                                $shippingGstin = $invoice->shipping_gstin ?: ($invoice->billing_gstin ?: $invoice->customer_gstin);
                            @endphp
                            @if($billingGstin)
                                <div style="margin-top:4px;"><strong>GSTIN</strong> {{ $billingGstin }}</div>
                            @endif
                        </td>
                        <td colspan="4" class="party-body party-right col-border-left">
                            <div class="party-name">{{ $invoice->shipping_attention ?: $invoice->customer_name }}</div>
                            @if($invoice->shipping_address)
                                <div>{!! nl2br(e($invoice->shipping_address)) !!}</div>
                            @elseif($invoice->billing_address)
                                <div>{!! nl2br(e($invoice->billing_address)) !!}</div>
                            @endif
                            @php
                                $sCityState = array_filter([$invoice->shipping_pincode ?: $invoice->billing_pincode, $invoice->shipping_city ?: $invoice->billing_city, $invoice->shipping_state ?: $invoice->billing_state]);
                            @endphp
                            @if(!empty($sCityState))
                                <div>{{ implode(' ', $sCityState) }}</div>
                            @endif
                            <div>{{ $invoice->shipping_country ?: ($invoice->billing_country ?: 'India') }}</div>
                            @if($shippingGstin)
                                <div style="margin-top:4px;"><strong>GSTIN</strong> {{ $shippingGstin }}</div>
                            @endif
                        </td>
                    </tr>

                    {{-- Row 3: Line Items Header --}}
                    <tr>
                        <th class="th-item" style="text-align:center;">#</th>
                        <th class="th-item" style="text-align:left;">Item & Description</th>
                        <th class="th-item" style="text-align:center;">HSN/SAC</th>
                        <th class="th-item" style="text-align:center;">Qty</th>
                        <th class="th-item" style="text-align:right;">Rate</th>
                        <th class="th-item" style="text-align:right;">Amount</th>
                    </tr>

                    {{-- Row 4..N: Line Items Data --}}
                    @foreach($invoice->items as $index => $item)
                    <tr class="item-row">
                        <td class="td-item" style="text-align:center; font-weight:700;">{{ $index + 1 }}</td>
                        <td class="td-item">
                            <div style="font-weight:800; color:#000;">{{ $item->item_name }}</div>
                            @if($item->description)
                                <div style="font-size:10.5px; color:#334155; margin-top:2px; line-height:1.4;">
                                    {!! nl2br(e($item->description)) !!}
                                </div>
                            @endif
                        </td>
                        <td class="td-item" style="text-align:center;">{{ $item->hsn_sac_code ?: '—' }}</td>
                        <td class="td-item" style="text-align:center;">{{ number_format((float) $item->qty, 2) }}</td>
                        <td class="td-item" style="text-align:right;">{{ number_format((float) $item->rate, 2) }}</td>
                        <td class="td-item" style="text-align:right;">{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                    @endforeach

                    {{-- Row N+1: Bottom Section (Left: Words, Notes, Bank, Terms | Right: Totals & Signature) --}}
                    <tr>
                        {{-- Left Side: colspan="3" (5% + 45% + 10% = 60% width -> up to HSN/SAC right border) --}}
                        <td colspan="3" class="bottom-left-cell">
                            <div>
                                <div class="sec-head">Total In Words</div>
                                <div style="font-size:11.5px; font-weight:800; font-style:italic; color:#000; margin-top:2px;">
                                    {{ $invoice->amount_in_words }}
                                </div>
                            </div>

                            <div style="margin-top:12px;">
                                <div class="sec-head">Notes</div>
                                <div class="sec-body">
                                    {{ $setting?->notes ?: ($invoice->customer_notes ?: 'Thanks for your business.') }}
                                </div>
                            </div>

                            <div style="margin-top:12px;">
                                <div style="font-size:11px; line-height:1.6; color:#000;">
                                    @if($setting?->bank_name)
                                        <div>Bank Name - {{ $setting->bank_name }}</div>
                                    @endif
                                    @if($setting?->account_name)
                                        <div>Account Name - {{ $setting->account_name }}</div>
                                    @endif
                                    @if($setting?->account_number)
                                        <div>Account Number - {{ $setting->account_number }}</div>
                                    @endif
                                    @if($setting?->ifsc_code)
                                        <div>IFSC Code - {{ $setting->ifsc_code }}</div>
                                    @endif
                                    @if($setting?->branch)
                                        <div>Branch - {{ $setting->branch }}</div>
                                    @endif
                                    @if($setting?->upi_id)
                                        <div>UPI ID - {{ $setting->upi_id }}</div>
                                    @endif
                                    @if($setting?->swift_code)
                                        <div>Swift Code - {{ $setting->swift_code }}</div>
                                    @endif
                                </div>
                            </div>

                            @php
                                $termsText = $setting?->terms_conditions ?: ($invoice->terms_conditions ?: '100% Advance Payment');
                            @endphp
                            @if($termsText)
                                <div style="margin-top:12px;">
                                    <div class="sec-head">Terms & Conditions</div>
                                    <div class="sec-body" style="font-size:10.5px; line-height:1.45;">
                                        {!! nl2br(e($termsText)) !!}
                                    </div>
                                </div>
                            @endif
                        </td>

                        {{-- Right Side: colspan="3" (8% + 16% + 16% = 40% width -> Qty, Rate, Amount) --}}
                        <td colspan="3" class="bottom-right-cell">
                            <div class="bottom-right-wrap">
                                <table class="totals-tbl">
                                    <colgroup>
                                        <col style="width: 60%;">
                                        <col style="width: 40%;">
                                    </colgroup>
                                    <tr>
                                        <td class="t-label">Sub Total</td>
                                        <td class="t-val">{{ number_format((float) $invoice->subtotal, 2) }}</td>
                                    </tr>
                                    @if($invoice->total_cgst > 0 || $invoice->total_sgst > 0 || ($isTNState && $invoice->total_igst == 0))
                                        <tr>
                                            <td class="t-label">CGST ({{ $cgstPct }}%)</td>
                                            <td class="t-val">{{ number_format((float) $invoice->total_cgst, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="t-label">SGST ({{ $sgstPct }}%)</td>
                                            <td class="t-val">{{ number_format((float) $invoice->total_sgst, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if($invoice->total_igst > 0 || (!$isTNState && $invoice->total_cgst == 0))
                                        <tr>
                                            <td class="t-label">IGST ({{ $igstPct }}%)</td>
                                            <td class="t-val">{{ number_format((float) $invoice->total_igst, 2) }}</td>
                                        </tr>
                                    @endif
                                    @if((float) $invoice->round_off != 0)
                                        <tr>
                                            <td class="t-label">Round Off</td>
                                            <td class="t-val">{{ $invoice->round_off > 0 ? '(+) ' : '(-) ' }}{{ number_format(abs((float) $invoice->round_off), 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr class="total-row">
                                        <td class="t-label">Total</td>
                                        <td class="t-val">₹{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    </tr>
                                    @if((float) $invoice->total_credited > 0)
                                        <tr>
                                            <td class="t-label">Credits Applied (CN)</td>
                                            <td class="t-val" style="color:#b91c1c; font-weight:700;">
                                                (-) {{ number_format((float) $invoice->total_credited, 2) }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="t-label">Payment Made</td>
                                        <td class="t-val" style="color:{{ $invoice->paid_amount > 0 ? '#b91c1c' : '#000' }}; font-weight:700;">
                                            (-) {{ number_format((float) $invoice->paid_amount, 2) }}
                                        </td>
                                    </tr>
                                    @if((float) $invoice->total_tds > 0)
                                        <tr>
                                            <td class="t-label">Amount Withheld</td>
                                            <td class="t-val" style="color:#b91c1c; font-weight:700;">
                                                (-) {{ number_format((float) $invoice->total_tds, 2) }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr class="balance-row">
                                        <td class="t-label">Balance Due</td>
                                        <td class="t-val">₹{{ number_format((float) $invoice->balance_due, 2) }}</td>
                                    </tr>
                                </table>

                                <div class="sig-container">
                                    <div class="sig-for">{{ $setting?->signatory_name ?: ('FOR ' . strtoupper($setting?->company_name ?: 'Sai Techno Solutions Private Limited')) }}</div>
                                    @if($signatureUrl)
                                        <img src="{{ $signatureUrl }}" alt="Signature" class="sig-image">
                                    @else
                                        <div style="height:55px;"></div>
                                    @endif
                                    <div class="sig-auth">Authorized Signature</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            </div>

            <div class="page-num">1</div>
        </div>
    </div>

    {{-- Recorded Payments & TDS Deductions Section --}}
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,.03); overflow:hidden;">
        <div style="padding:16px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; background:#f8fafc;">
            <div>
                <div style="font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-cash-stack" style="color:#16a34a;"></i>
                    <span>Recorded Payments & TDS Deductions</span>
                    <span style="font-size:12px; font-weight:700; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:999px;">{{ $invoice->payments->count() }} {{ Str::plural('Entry', $invoice->payments->count()) }}</span>
                </div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    Track payment receipts, bank credits, TDS deductions under Sec 194C/194J, and proof attachments.
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                @if($balDue > 0)
                    <button type="button" class="acm-btn" style="background:linear-gradient(135deg, #16a34a, #15803d); color:#fff; box-shadow:0 4px 14px rgba(22,163,74,0.25);" onclick="openRecordPaymentModal()">
                        <i class="bi bi-plus-lg"></i> Record Payment / TDS
                    </button>
                @else
                    <span style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:800; color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; padding:6px 14px; border-radius:8px;">
                        <i class="bi bi-check-circle-fill"></i> Invoice Fully Settled
                    </span>
                @endif
            </div>
        </div>

        @if($invoice->payments->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                            <th style="padding:12px 16px;">Date & Voucher #</th>
                            <th style="padding:12px 16px;">Payment Mode</th>
                            <th style="padding:12px 16px;">Reference / UTR</th>
                            <th style="padding:12px 16px; text-align:right;">Amount Received (₹)</th>
                            <th style="padding:12px 16px; text-align:right;">TDS Deducted (₹)</th>
                            <th style="padding:12px 16px; text-align:right;">Total Settled (₹)</th>
                            <th style="padding:12px 16px; text-align:center;">Attachment</th>
                            <th style="padding:12px 16px;">Recorded By</th>
                            <th style="padding:12px 16px; text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                            <tr style="border-bottom:1px solid #f1f5f9; transition:background .15s;">
                                <td style="padding:12px 16px;">
                                    <div style="font-weight:700; color:#0f172a;">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '—' }}</div>
                                    <div style="font-size:11px; font-family:monospace; color:#64748b;">{{ $payment->payment_number ?: ('PAY#' . $payment->id) }}</div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <span style="display:inline-flex; align-items:center; gap:5px; background:#eff6ff; color:#1e40af; border:1px solid #dbeafe; font-size:11.5px; font-weight:700; padding:2px 8px; border-radius:6px;">
                                        <i class="bi bi-wallet-fill" style="font-size:10px;"></i> {{ $payment->payment_mode_label }}
                                    </span>
                                    @if($payment->bank_name)
                                        <div style="font-size:11px; color:#64748b; margin-top:2px;">{{ $payment->bank_name }}</div>
                                    @endif
                                </td>
                                <td style="padding:12px 16px;">
                                    @if($payment->reference_number)
                                        <span style="font-family:monospace; font-weight:600; color:#334155; font-size:12px;">{{ $payment->reference_number }}</span>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                    @if($payment->notes)
                                        <div style="font-size:11.5px; color:#64748b; margin-top:2px; font-style:italic;" title="{{ $payment->notes }}">{{ Str::limit($payment->notes, 35) }}</div>
                                    @endif
                                </td>
                                <td style="padding:12px 16px; text-align:right; font-weight:800; color:#16a34a; white-space:nowrap;">
                                    ₹{{ number_format((float) $payment->amount_received, 2) }}
                                </td>
                                <td style="padding:12px 16px; text-align:right; white-space:nowrap;">
                                    @if($payment->is_tds_deducted && $payment->tds_amount > 0)
                                        <div style="font-weight:800; color:#ea580c;">₹{{ number_format((float) $payment->tds_amount, 2) }}</div>
                                        @if($payment->tds_percentage > 0)
                                            <span style="font-size:10.5px; font-weight:700; background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; padding:1px 6px; border-radius:4px;">{{ (float) $payment->tds_percentage }}% TDS</span>
                                        @endif
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="padding:12px 16px; text-align:right; font-weight:800; color:#0f172a; white-space:nowrap;">
                                    ₹{{ number_format((float) $payment->total_settled_amount, 2) }}
                                </td>
                                <td style="padding:12px 16px; text-align:center;">
                                    @if($payment->attachment_url)
                                        <a href="{{ $payment->attachment_url }}" target="_blank" class="acm-btn acm-btn-outline" style="padding:4px 10px; font-size:11.5px; border-radius:6px; color:#2563eb; border-color:#bfdbfe; background:#eff6ff;" title="{{ $payment->attachment_name ?: 'Download Proof' }}">
                                            <i class="bi bi-paperclip"></i> <span>View File</span>
                                        </a>
                                    @else
                                        <span style="color:#94a3b8; font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td style="padding:12px 16px;">
                                    <div style="font-size:12px; font-weight:600; color:#334155;">{{ $payment->recordedBy?->name ?: 'System' }}</div>
                                    <div style="font-size:10.5px; color:#94a3b8;">{{ $payment->created_at ? $payment->created_at->format('d M, h:i A') : '' }}</div>
                                </td>
                                <td style="padding:12px 16px; text-align:right;">
                                    <form method="POST" action="{{ route('accounts.sales.invoices.payments.destroy', [$invoice->id, $payment->id]) }}" onsubmit="return confirm('Are you sure you want to delete payment entry #{{ $payment->payment_number }} (₹{{ number_format((float)$payment->total_settled_amount, 2) }})? The invoice balance will be adjusted accordingly.')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:5px 9px; font-size:11.5px; border-radius:6px;" title="Delete this payment record">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background:#f8fafc; font-weight:800; border-top:2px solid #e2e8f0; font-size:13px;">
                        <tr>
                            <td colspan="3" style="padding:12px 16px; text-align:right; color:#475569;">Total Settled Breakdown:</td>
                            <td style="padding:12px 16px; text-align:right; color:#16a34a;">₹{{ number_format((float)$invoice->payments->sum('amount_received'), 2) }}</td>
                            <td style="padding:12px 16px; text-align:right; color:#ea580c;">₹{{ number_format((float)$invoice->payments->where('is_tds_deducted', true)->sum('tds_amount'), 2) }}</td>
                            <td style="padding:12px 16px; text-align:right; color:#0f172a;">₹{{ number_format((float)$invoice->payments->sum('total_settled_amount'), 2) }}</td>
                            <td colspan="3" style="padding:12px 16px; text-align:right; color:{{ $balDue > 0 ? '#dc2626' : '#16a34a' }};">
                                Remaining Balance: ₹{{ number_format($balDue, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div style="padding:36px 20px; text-align:center; color:#64748b;">
                <div style="width:50px; height:50px; border-radius:50%; background:#f1f5f9; display:inline-flex; align-items:center; justify-content:center; font-size:22px; color:#94a3b8; margin-bottom:12px;">
                    <i class="bi bi-receipt"></i>
                </div>
                <div style="font-size:14px; font-weight:700; color:#334155; margin-bottom:4px;">No Payments Recorded Yet</div>
                <div style="font-size:12.5px; color:#64748b; max-width:420px; margin:0 auto 16px;">
                    Record customer payments received into your bank account or cash, including TDS deductions and receipt attachments.
                </div>
                @if($balDue > 0)
                    <button type="button" class="acm-btn" style="background:linear-gradient(135deg, #16a34a, #15803d); color:#fff;" onclick="openRecordPaymentModal()">
                        <i class="bi bi-plus-lg"></i> Record First Payment
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Credit Notes (CN) Section --}}
    @php
        $existingCredits = (float) $invoice->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
        $maxCreditable = max(0, (float) $invoice->total_amount - $existingCredits);
    @endphp
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,.03); overflow:hidden;">
        <div style="padding:16px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; background:#f8fafc;">
            <div>
                <div style="font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-file-earmark-minus" style="color:#dc2626;"></i>
                    <span>Credit Notes Issued (CN)</span>
                    <span style="font-size:12px; font-weight:700; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; padding:2px 8px; border-radius:999px;">{{ $invoice->creditNotes->count() }} {{ Str::plural('Note', $invoice->creditNotes->count()) }}</span>
                </div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    Track invoice cancellations, post-invoice discounts, scope deductions, and credit memo adjustments.
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                @if($maxCreditable > 0)
                    <button type="button" class="acm-btn" style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;" onclick="openCreditNoteModal()">
                        <i class="bi bi-plus-lg"></i> Add Credit Note
                    </button>
                @else
                    <span style="display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:#b91c1c; background:#fef2f2; border:1px solid #fecaca; padding:6px 12px; border-radius:8px;">
                        <i class="bi bi-info-circle-fill"></i> Fully Credited
                    </span>
                @endif
            </div>
        </div>

        @if($invoice->creditNotes->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                            <th style="padding:12px 16px;">Date & CN #</th>
                            <th style="padding:12px 16px;">Reason</th>
                            <th style="padding:12px 16px;">Remarks / Notes</th>
                            <th style="padding:12px 16px; text-align:right;">Credited Amount (₹)</th>
                            <th style="padding:12px 16px; text-align:center;">Status</th>
                            <th style="padding:12px 16px;">Issued By</th>
                            <th style="padding:12px 16px; text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->creditNotes as $cn)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:12px 16px;">
                                    <div style="font-weight:700; color:#0f172a;">{{ $cn->credit_note_date ? $cn->credit_note_date->format('d M Y') : '—' }}</div>
                                    <a href="{{ route('accounts.sales.credit-notes.show', $cn->id) }}" style="font-size:11.5px; font-family:monospace; color:#b91c1c; font-weight:800; text-decoration:none;">
                                        {{ $cn->credit_note_number }}
                                    </a>
                                </td>
                                <td style="padding:12px 16px;">
                                    <span style="display:inline-flex; align-items:center; gap:4px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:11px; font-weight:800; padding:2px 8px; border-radius:6px;">
                                        {{ \App\Models\CreditNote::REASONS[$cn->reason] ?? $cn->reason }}
                                    </span>
                                </td>
                                <td style="padding:12px 16px; color:#64748b; font-size:12px;">
                                    {{ $cn->notes ?: '—' }}
                                    @if($cn->attachment_url)
                                        <div style="margin-top:2px;">
                                            <a href="{{ $cn->attachment_url }}" target="_blank" style="color:#2563eb; font-weight:700; font-size:11px; text-decoration:none;">
                                                <i class="bi bi-paperclip"></i> Attachment
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td style="padding:12px 16px; text-align:right; font-weight:800; font-size:14px; color:#b91c1c;">
                                    ₹{{ number_format((float) $cn->total_amount, 2) }}
                                </td>
                                <td style="padding:12px 16px; text-align:center;">
                                    @if($cn->status === 'issued')
                                        <span style="font-size:11px; font-weight:800; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px;">Active</span>
                                    @else
                                        <span style="font-size:11px; font-weight:800; background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:2px 8px; border-radius:999px;">Void</span>
                                    @endif
                                </td>
                                <td style="padding:12px 16px; font-size:12px; color:#475569;">
                                    {{ $cn->creator?->name ?? 'System' }}
                                </td>
                                <td style="padding:12px 16px; text-align:right;">
                                    <div style="display:inline-flex; align-items:center; gap:6px;">
                                        <a href="{{ route('accounts.sales.credit-notes.show', $cn->id) }}" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="View Note">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('accounts.sales.credit-notes.print', $cn->id) }}" target="_blank" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Print Note">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <form method="POST" action="{{ route('accounts.sales.credit-notes.destroy', $cn->id) }}" onsubmit="return confirm('Are you sure you want to delete Credit Note #{{ $cn->credit_note_number }}? Invoice balance will update.')" style="margin:0; display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px; color:#dc2626;" title="Delete Note">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:800; border-top:2px solid #e2e8f0;">
                            <td colspan="3" style="padding:12px 16px; text-align:right; text-transform:uppercase; font-size:11.5px; color:#475569;">Total Active Credits Applied:</td>
                            <td style="padding:12px 16px; text-align:right; color:#b91c1c; font-size:14.5px;">₹{{ number_format($existingCredits, 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div style="padding:32px 20px; text-align:center;">
                <div style="width:48px; height:48px; border-radius:50%; background:#fef2f2; color:#dc2626; display:inline-flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:8px;">
                    <i class="bi bi-file-earmark-minus"></i>
                </div>
                <div style="font-size:14px; font-weight:700; color:#334155; margin-bottom:4px;">No Credit Notes Issued</div>
                <div style="font-size:12.5px; color:#64748b; max-width:420px; margin:0 auto 14px;">
                    Need to provide a post-invoice discount, handle a partial cancellation, or revise the bill? Issue a credit note against this invoice.
                </div>
                @if($maxCreditable > 0)
                    <button type="button" class="acm-btn" style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;" onclick="openCreditNoteModal()">
                        <i class="bi bi-plus-lg"></i> Add Credit Note
                    </button>
                @endif
            </div>
        @endif
    </div>

    </div>{{-- /inv-body --}}

{{-- Modal: Record Payment & TDS Deduction --}}
<div id="recordPaymentModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(3px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-width:560px; width:100%; overflow:hidden; max-height:90vh; display:flex; flex-direction:column;">
        {{-- Modal Header --}}
        <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:38px; height:38px; border-radius:10px; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:20px;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">Record Payment & TDS</h3>
                    <p style="font-size:12px; color:#64748b; margin:0;">Invoice #{{ $invoice->invoice_number }} • {{ $invoice->customer_name }}</p>
                </div>
            </div>
            <button type="button" onclick="closeRecordPaymentModal()" style="background:none; border:none; font-size:18px; color:#94a3b8; cursor:pointer; padding:4px;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Modal Body (Scrollable) --}}
        <form method="POST" action="{{ route('accounts.sales.invoices.payments.store', $invoice->id) }}" enctype="multipart/form-data" id="recordPaymentForm" style="display:flex; flex-direction:column; overflow-y:auto;">
            @csrf
            <div style="padding:22px; display:flex; flex-direction:column; gap:16px;">

                {{-- Summary Pills --}}
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px;">
                    <div>
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Invoice Total</div>
                        <div style="font-size:14.5px; font-weight:800; color:#0f172a;">₹{{ number_format($totalAmt, 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Already Settled</div>
                        <div style="font-size:14.5px; font-weight:800; color:#16a34a;">₹{{ number_format($totalSettled, 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Balance Due</div>
                        <div style="font-size:14.5px; font-weight:800; color:#dc2626;" id="rpmModalBalDue">₹{{ number_format($balDue, 2) }}</div>
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
                            <button type="button" onclick="fillFullBalance()" style="font-size:11px; font-weight:700; color:#2563eb; background:none; border:none; cursor:pointer; padding:0;">
                                Fill Full Due
                            </button>
                        </div>
                        <input type="number" step="0.01" min="0" max="{{ $balDue }}" name="amount_received" id="rpmAmountReceived" required placeholder="0.00" value="{{ $balDue }}" oninput="calculateLiveSettled()" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13.5px; font-weight:700; color:#0f172a; outline:none; background:#fff;">
                    </div>
                </div>

                {{-- TDS Deduction Toggle Section --}}
                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px 14px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0; font-size:13px; font-weight:700; color:#92400e;">
                        <input type="checkbox" name="is_tds_deducted" value="1" id="rpmIsTds" onchange="toggleTdsSection()" style="width:16px; height:16px; accent-color:#d97706; cursor:pointer;">
                        <span>Client Deducted TDS (Tax Deducted at Source)</span>
                    </label>

                    <div id="rpmTdsFields" style="display:none; margin-top:12px; padding-top:12px; border-top:1px dashed #fcd34d;">
                        <div style="font-size:11.5px; color:#78350f; margin-bottom:10px;">
                            💡 TDS is credited to your PAN in Form 26AS. Total Settled = <strong>Amount Received + TDS</strong>.
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:10px;">
                            <div>
                                <label style="display:block; font-size:11.5px; font-weight:700; color:#78350f; margin-bottom:4px;">
                                    TDS Rate (%)
                                </label>
                                <input type="number" step="0.01" min="0" max="100" name="tds_percentage" id="rpmTdsPct" placeholder="e.g. 2, 10" oninput="calculateTdsFromPercentage()" style="width:100%; border:1px solid #fcd34d; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; color:#78350f; outline:none; background:#fff;">
                                <div style="display:flex; gap:6px; margin-top:6px; flex-wrap:wrap;">
                                    <button type="button" onclick="setTdsPreset(2)" style="font-size:10.5px; font-weight:700; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:4px; padding:2px 6px; cursor:pointer;">2% (194C)</button>
                                    <button type="button" onclick="setTdsPreset(10)" style="font-size:10.5px; font-weight:700; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:4px; padding:2px 6px; cursor:pointer;">10% (194J)</button>
                                    <button type="button" onclick="setTdsPreset(1)" style="font-size:10.5px; font-weight:700; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:4px; padding:2px 6px; cursor:pointer;">1%</button>
                                    <button type="button" onclick="setTdsPreset(0.1)" style="font-size:10.5px; font-weight:700; background:#fef3c7; border:1px solid #fcd34d; color:#92400e; border-radius:4px; padding:2px 6px; cursor:pointer;">0.1%</button>
                                </div>
                            </div>
                            <div>
                                <label style="display:block; font-size:11.5px; font-weight:700; color:#78350f; margin-bottom:4px;">
                                    TDS Amount (₹)
                                </label>
                                <input type="number" step="0.01" min="0" name="tds_amount" id="rpmTdsAmount" placeholder="0.00" oninput="calculateLiveSettled()" style="width:100%; border:1px solid #fcd34d; border-radius:8px; padding:8px 10px; font-size:13px; font-weight:700; color:#78350f; outline:none; background:#fff;">
                                <div style="font-size:10.5px; color:#b45309; margin-top:4px;">Calculated on Subtotal (₹{{ number_format($sub, 2) }})</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Live Settled Summary Banner --}}
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase;">Total Settled This Entry</div>
                        <div style="font-size:12px; color:#166534;">
                            Received: <strong id="rpmPrevReceived">₹{{ number_format($balDue, 2) }}</strong> + TDS: <strong id="rpmPrevTds">₹0.00</strong>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:16px; font-weight:900; color:#15803d;" id="rpmPrevTotalSettled">₹{{ number_format($balDue, 2) }}</div>
                        <div style="font-size:11px; color:#166534;" id="rpmPrevBalRemaining">Remaining Balance: ₹0.00</div>
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
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                            Bank Name
                        </label>
                        <input type="text" name="bank_name" placeholder="e.g. HDFC Bank, ICICI Bank" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff;">
                    </div>

                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">
                            Attachment (Receipt / TDS Challan)
                        </label>
                        <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:7px 10px; font-size:12px; color:#0f172a; outline:none; background:#fff;">
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
                <button type="button" onclick="closeRecordPaymentModal()" style="padding:9px 18px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:13px; font-weight:700; color:#475569; cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" id="rpmSubmitBtn" style="padding:9px 22px; border-radius:8px; border:none; background:linear-gradient(135deg, #16a34a, #15803d); color:#fff; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(22,163,74,0.3); display:inline-flex; align-items:center; gap:6px;">
                    <i class="bi bi-check-lg"></i>
                    <span>Record Payment</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Quick Change Sales Person --}}
<div id="changeSalesPersonModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(3px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-width:440px; width:100%; overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#f8fafc;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="bi bi-person-gear"></i>
                </div>
                <div>
                    <h3 style="font-size:15px; font-weight:700; color:#0f172a; margin:0;">Assign Sales Person</h3>
                    <p style="font-size:11.5px; color:#64748b; margin:0;">Invoice #{{ $invoice->invoice_number }}</p>
                </div>
            </div>
            <button type="button" onclick="closeChangeSalesPersonModal()" style="background:none; border:none; font-size:18px; color:#94a3b8; cursor:pointer; padding:4px;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="changeSalesPersonForm" onsubmit="submitChangeSalesPerson(event)">
            <div style="padding:22px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#334155; margin-bottom:8px;">
                    Select Sales Person (Sales & CST Teams)
                </label>
                <select id="modalSalesPersonSelect" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff;">
                    <option value="">-- No Sales Person (Unassigned) --</option>
                    @foreach($salesUsers as $u)
                        <option value="{{ $u->id }}" {{ $invoice->sales_person_id == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->email }})
                        </option>
                    @endforeach
                </select>
                <div style="margin-top:8px; font-size:11.5px; color:#64748b;">
                    Updates dynamically on view and print preview without full page reload.
                </div>
            </div>

            <div style="padding:14px 22px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeChangeSalesPersonModal()" style="padding:8px 16px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:13px; font-weight:600; color:#475569; cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" id="saveSalesPersonBtn" style="padding:8px 18px; border-radius:8px; border:none; background:linear-gradient(135deg, #2563eb, #1d4ed8); color:#fff; font-size:13px; font-weight:600; cursor:pointer; box-shadow:0 2px 6px rgba(37,99,235,0.3); display:inline-flex; align-items:center; gap:6px;">
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>
{{-- Modal: Add Credit Note (CN) --}}
@php
    $nextCnNumber = \App\Models\CreditNote::generateNextNumber(auth()->user()?->company_id);
    $cnReasons = \App\Models\CreditNote::REASONS;
    $currBalDue = (float) $invoice->balance_due;
    $existingCreditsForModal = (float) $invoice->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
    $maxCreditableForModal = max(0, (float) $invoice->total_amount - $existingCreditsForModal);
@endphp
<div id="creditNoteModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(3px); z-index:99999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-width:540px; width:100%; overflow:hidden; max-height:90vh; display:flex; flex-direction:column;">
        <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:38px; height:38px; border-radius:10px; background:#fef2f2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:20px;">
                    <i class="bi bi-file-earmark-minus"></i>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">Issue Credit Note (CN)</h3>
                    <p style="font-size:12px; color:#64748b; margin:0;">Invoice #{{ $invoice->invoice_number }} • Auto #{{ $nextCnNumber }}</p>
                </div>
            </div>
            <button type="button" onclick="closeCreditNoteModal()" style="background:none; border:none; font-size:18px; color:#94a3b8; cursor:pointer; padding:4px;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('accounts.sales.invoices.credit-notes.store', $invoice->id) }}" enctype="multipart/form-data" id="creditNoteForm" style="display:flex; flex-direction:column; overflow-y:auto;">
            @csrf
            <div style="padding:22px; display:flex; flex-direction:column; gap:16px;">

                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px;">
                    <div>
                        <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase;">Invoice Total</div>
                        <div style="font-size:14px; font-weight:800; color:#0f172a;">₹{{ number_format((float) $invoice->total_amount, 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase;">Balance Due</div>
                        <div style="font-size:14px; font-weight:800; color:#ea580c;">₹{{ number_format($currBalDue, 2) }}</div>
                    </div>
                    <div>
                        <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase;">Max Creditable</div>
                        <div style="font-size:14px; font-weight:800; color:#15803d;">₹{{ number_format($maxCreditableForModal, 2) }}</div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">
                            Credit Note Date <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="date" name="credit_note_date" required value="{{ date('Y-m-d') }}" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">
                            Reason <span style="color:#dc2626;">*</span>
                        </label>
                        <select name="reason" required style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff;">
                            @foreach($cnReasons as $rKey => $rLabel)
                                <option value="{{ $rKey }}">{{ $rLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <label style="font-size:12px; font-weight:700; color:#334155; margin:0;">
                            Credit Amount (₹) <span style="color:#dc2626;">*</span>
                        </label>
                        <div style="display:flex; gap:6px; font-size:11px;">
                            @if($currBalDue > 0)
                                <button type="button" onclick="fillCnDue()" style="font-weight:700; color:#2563eb; background:none; border:none; cursor:pointer; padding:0;">
                                    Fill Balance Due
                                </button>
                                <span style="color:#cbd5e1;">•</span>
                            @endif
                            <button type="button" onclick="fillCnMax()" style="font-weight:700; color:#16a34a; background:none; border:none; cursor:pointer; padding:0;">
                                Fill Full Invoice
                            </button>
                        </div>
                    </div>
                    <input type="number" step="0.01" min="0.01" max="{{ $maxCreditableForModal }}" name="total_amount" id="cnModalAmount" required value="{{ $currBalDue > 0 ? $currBalDue : $maxCreditableForModal }}" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px; font-size:16px; font-weight:800; color:#b91c1c; outline:none; background:#fff;">
                    <div style="font-size:11px; color:#64748b; margin-top:3px;">
                        Max allowed for this invoice: <strong>₹{{ number_format($maxCreditableForModal, 2) }}</strong>
                    </div>
                </div>

                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">
                        Notes / Remarks / Justification (Optional)
                    </label>
                    <textarea name="notes" rows="2" placeholder="e.g. Approved discount on final milestone / Order cancelled by client..." style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff; resize:vertical;"></textarea>
                </div>

                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">
                        Attachment / Email Proof (Optional)
                    </label>
                    <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx" style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:6px 10px; font-size:12px; color:#0f172a; outline:none; background:#fff;">
                </div>

            </div>

            <div style="padding:14px 22px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; flex-shrink:0;">
                <button type="button" onclick="closeCreditNoteModal()" style="padding:9px 18px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:13px; font-weight:700; color:#475569; cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" style="padding:9px 22px; border-radius:8px; border:none; background:linear-gradient(135deg, #dc2626, #b91c1c); color:#fff; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(220,38,38,0.3); display:inline-flex; align-items:center; gap:6px;">
                    <i class="bi bi-check-lg"></i>
                    <span>Apply Credit Note</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Send Invoice Email Preview & Dispatch --}}
<div id="sendInvoiceMailModal" onclick="if(event.target===this) closeSendInvoiceMailModal()" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(3px); z-index:99999; align-items:center; justify-content:center; padding:16px; box-sizing:border-box;">
    <div style="background:#fff; border-radius:16px; box-shadow:0 20px 45px rgba(15,23,42,0.25); max-width:680px; width:100%; overflow:hidden; max-height:92vh; display:flex; flex-direction:column; animation:simModalIn .2s cubic-bezier(.16,1,.3,1);">
        
        {{-- Header --}}
        <div style="padding:18px 24px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:10px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:20px;">
                    <i class="bi bi-envelope-paper-fill"></i>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">Send Invoice Email</h3>
                    <p style="font-size:12px; color:#64748b; margin:2px 0 0;">Preview & dispatch invoice #{{ $invoice->invoice_number }} to customer</p>
                </div>
            </div>
            <button type="button" onclick="closeSendInvoiceMailModal()" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer; padding:4px; line-height:1;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- Body / Form --}}
        <form id="sendInvoiceMailForm" onsubmit="submitSendInvoiceMail(event)" style="display:flex; flex-direction:column; overflow-y:auto;">
            <div style="padding:22px 24px; display:flex; flex-direction:column; gap:16px;">
                
                {{-- Informational Banner --}}
                <div style="background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1; border-radius:10px; padding:12px 16px; font-size:12.5px; display:flex; align-items:flex-start; gap:10px;">
                    <i class="bi bi-info-circle-fill" style="font-size:16px; margin-top:1px; flex-shrink:0;"></i>
                    <div style="line-height:1.5;">
                        Review the email details below. Clicking <strong>Send Mail</strong> will immediately email the invoice PDF copy to the recipient, and the status will update to <span class="acm-badge status-sent" style="font-size:10.5px; padding:2px 8px; vertical-align:middle;"><i class="bi bi-send-fill"></i> Sent</span>.
                    </div>
                </div>

                {{-- Recipient & Subject Inputs --}}
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; display:flex; flex-direction:column; gap:14px;">
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#475569; margin-bottom:6px;">
                            Recipient Email (To:) <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="email" id="simRecipientEmail" required
                               value="{{ $invoice->customer_email ?: $invoice->customer?->email }}"
                               placeholder="customer@example.com"
                               style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff; font-family:inherit;">
                    </div>

                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#475569; margin-bottom:6px;">
                            Email Subject
                        </label>
                        <input type="text" id="simSubject"
                               value="Tax Invoice #{{ $invoice->invoice_number }} from {{ $setting->company_name ?: config('app.name') }}"
                               style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 12px; font-size:13px; color:#0f172a; outline:none; background:#fff; font-family:inherit;">
                    </div>
                </div>

                {{-- Email Mockup Preview --}}
                <div style="border:1px solid #cbd5e1; border-radius:12px; overflow:hidden; background:#ffffff;">
                    {{-- Preview Header --}}
                    <div style="background:linear-gradient(135deg, #fe5f04, #ff8533); padding:16px 20px; color:#ffffff; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-size:16px; font-weight:800; letter-spacing:-0.02em;">{{ $setting->company_name ?: config('app.name') }}</div>
                            <div style="font-size:11px; opacity:0.9; font-weight:600;">TAX INVOICE NOTIFICATION</div>
                        </div>
                        <span style="background:rgba(255,255,255,0.25); color:#fff; font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; border:1px solid rgba(255,255,255,0.3);">
                            {{ strtoupper($invoice->status ?? 'ISSUED') }}
                        </span>
                    </div>

                    {{-- Preview Body --}}
                    <div style="padding:18px 20px; font-size:13px; color:#334155; line-height:1.6;">
                        <p style="margin:0 0 10px;">Dear <strong>{{ $invoice->customer_name ?: 'Valued Customer' }}</strong>,</p>
                        <p style="margin:0 0 16px; color:#475569;">
                            Thank you for your business! Please find below your invoice summary for <strong>#{{ $invoice->invoice_number }}</strong>. A PDF copy has also been attached to this email.
                        </p>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:14px;">
                            <div>
                                <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block;">Invoice Date</span>
                                <span style="font-weight:600; color:#0f172a;">{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d M, Y') : '-' }}</span>
                            </div>
                            <div>
                                <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block;">Due Date</span>
                                <span style="font-weight:600; color:#dc2626;">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') : 'Due on Receipt' }}</span>
                            </div>
                            <div>
                                <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block;">Total Amount</span>
                                <span style="font-weight:800; color:#fe5f04; font-size:15px;">₹{{ number_format((float)$invoice->total_amount, 2) }}</span>
                            </div>
                            <div>
                                <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; display:block;">Balance Due</span>
                                <span style="font-weight:800; color:#ea580c; font-size:15px;">₹{{ number_format((float)$invoice->balance_due, 2) }}</span>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:8px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; padding:8px 12px; font-size:12px; color:#334155;">
                            <i class="bi bi-file-earmark-pdf-fill" style="color:#dc2626; font-size:18px;"></i>
                            <span>Attachment: <strong>Invoice_{{ str_replace(['/', '\\'], '_', $invoice->invoice_number) }}.pdf</strong> (Official PDF copy)</span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; flex-shrink:0;">
                <button type="button" onclick="closeSendInvoiceMailModal()" style="padding:9px 18px; border-radius:8px; border:1px solid #cbd5e1; background:#fff; font-size:13px; font-weight:700; color:#475569; cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" id="simSubmitBtn" style="padding:9px 22px; border-radius:8px; border:none; background:linear-gradient(135deg, #0284c7, #0369a1); color:#fff; font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(2,132,199,0.3); display:inline-flex; align-items:center; gap:6px;">
                    <i class="bi bi-send-fill"></i>
                    <span>Send Mail</span>
                </button>
            </div>
        </form>

    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
let currentZoom = 1.0;
let isFitWidth = false;

function applyZoom(zoom) {
    currentZoom = Math.min(1.4, Math.max(0.6, Math.round(zoom * 100) / 100));
    const sheet = document.getElementById('invoiceSheet');
    const display = document.getElementById('zoomLevelDisplay');
    if (sheet) {
        sheet.style.transform = `scale(${currentZoom})`;
    }
    if (display) {
        display.textContent = `${Math.round(currentZoom * 100)}%`;
    }
}

function zoomInvoice(delta) {
    isFitWidth = false;
    applyZoom(currentZoom + delta);
}

function resetZoom() {
    isFitWidth = false;
    applyZoom(1.0);
}

function toggleFitWidth() {
    const canvas = document.getElementById('invoiceCanvas');
    const sheet = document.getElementById('invoiceSheet');
    if (!canvas || !sheet) return;
    if (!isFitWidth) {
        const availableWidth = canvas.clientWidth - 48;
        const targetZoom = Math.min(1.15, availableWidth / 820);
        applyZoom(targetZoom);
        isFitWidth = true;
    } else {
        resetZoom();
    }
}

function copyInvoiceLink() {
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('✓ Shareable invoice link copied to clipboard!');
        });
    } else {
        prompt('Copy invoice link:', url);
    }
}

function copyInvoiceNumber(num) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(num).then(() => {
            showToast(`✓ Invoice number #${num} copied!`);
        });
    }
}

function showToast(msg) {
    let toast = document.getElementById('invToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'invToast';
        toast.style.cssText = 'position:fixed; bottom:24px; right:24px; background:#0f172a; color:#fff; font-size:13px; font-weight:700; padding:12px 20px; border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.25); z-index:99999; display:flex; align-items:center; gap:8px; opacity:0; transform:translateY(10px); transition:all 0.25s ease-out; pointer-events:none;';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
    }, 2500);
}

function openChangeSalesPersonModal() {
    const modal = document.getElementById('changeSalesPersonModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeChangeSalesPersonModal() {
    const modal = document.getElementById('changeSalesPersonModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function submitChangeSalesPerson(e) {
    e.preventDefault();
    const btn = document.getElementById('saveSalesPersonBtn');
    const select = document.getElementById('modalSalesPersonSelect');
    const salesPersonId = select.value;
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    }

    fetch('{{ route("accounts.sales.invoices.update-sales-person", $invoice->id) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ sales_person_id: salesPersonId || null })
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = 'Save Changes';
        }
        if (data.success) {
            const displaySpan = document.getElementById('salesPersonDisplay');
            if (displaySpan) {
                displaySpan.textContent = data.sales_person_name;
            }
            closeChangeSalesPersonModal();
            showToast('✓ Sales Person updated to ' + data.sales_person_name);
        } else {
            alert(data.message || 'Failed to update Sales Person.');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = 'Save Changes';
        }
        console.error(err);
        alert('An error occurred while updating the sales person.');
    });
}

const invoiceSubtotal = {{ $sub }};
let currentInvoiceBalanceDue = {{ $balDue }};

function openRecordPaymentModal() {
    const modal = document.getElementById('recordPaymentModal');
    if (modal) {
        modal.style.display = 'flex';
        calculateLiveSettled();
    }
}

function closeRecordPaymentModal() {
    const modal = document.getElementById('recordPaymentModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function toggleTdsSection() {
    const isChecked = document.getElementById('rpmIsTds')?.checked;
    const tdsFields = document.getElementById('rpmTdsFields');
    if (tdsFields) {
        tdsFields.style.display = isChecked ? 'block' : 'none';
    }
    if (!isChecked) {
        const tdsPct = document.getElementById('rpmTdsPct');
        const tdsAmt = document.getElementById('rpmTdsAmount');
        if (tdsPct) tdsPct.value = '';
        if (tdsAmt) tdsAmt.value = '';
    }
    calculateLiveSettled();
}

function setTdsPreset(percent) {
    const tdsPctInput = document.getElementById('rpmTdsPct');
    if (tdsPctInput) {
        tdsPctInput.value = percent;
        calculateTdsFromPercentage();
    }
}

function calculateTdsFromPercentage() {
    const pct = parseFloat(document.getElementById('rpmTdsPct')?.value) || 0;
    const baseAmount = invoiceSubtotal > 0 ? invoiceSubtotal : currentInvoiceBalanceDue;
    const tdsAmount = Math.round((baseAmount * (pct / 100)) * 100) / 100;
    const tdsAmtInput = document.getElementById('rpmTdsAmount');
    if (tdsAmtInput) {
        tdsAmtInput.value = tdsAmount > 0 ? tdsAmount : '';
    }
    const netReceived = Math.max(0, Math.round((currentInvoiceBalanceDue - tdsAmount) * 100) / 100);
    const amountReceivedInput = document.getElementById('rpmAmountReceived');
    if (amountReceivedInput) {
        amountReceivedInput.value = netReceived;
    }
    calculateLiveSettled();
}

function fillFullBalance() {
    const isTds = document.getElementById('rpmIsTds')?.checked;
    const tdsAmt = isTds ? (parseFloat(document.getElementById('rpmTdsAmount')?.value) || 0) : 0;
    const net = Math.max(0, Math.round((currentInvoiceBalanceDue - tdsAmt) * 100) / 100);
    const amountReceivedInput = document.getElementById('rpmAmountReceived');
    if (amountReceivedInput) {
        amountReceivedInput.value = net;
    }
    calculateLiveSettled();
}

function calculateLiveSettled() {
    const amtReceived = parseFloat(document.getElementById('rpmAmountReceived')?.value) || 0;
    const isTds = document.getElementById('rpmIsTds')?.checked;
    const tdsAmt = isTds ? (parseFloat(document.getElementById('rpmTdsAmount')?.value) || 0) : 0;
    const totalSettled = Math.round((amtReceived + tdsAmt) * 100) / 100;
    const remaining = Math.max(0, Math.round((currentInvoiceBalanceDue - totalSettled) * 100) / 100);

    const prevReceived = document.getElementById('rpmPrevReceived');
    const prevTds = document.getElementById('rpmPrevTds');
    const prevTotal = document.getElementById('rpmPrevTotalSettled');
    const prevRemaining = document.getElementById('rpmPrevBalRemaining');
    const submitBtn = document.getElementById('rpmSubmitBtn');

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
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> <span>Record Payment (₹' + totalSettled.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ')</span>';
    }
}

// Credit Note Modal Functions
function openCreditNoteModal() {
    const modal = document.getElementById('creditNoteModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeCreditNoteModal() {
    const modal = document.getElementById('creditNoteModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function fillCnDue() {
    const input = document.getElementById('cnModalAmount');
    if (input) {
        input.value = '{{ $currBalDue }}';
    }
}

function fillCnMax() {
    const input = document.getElementById('cnModalAmount');
    if (input) {
        input.value = '{{ $maxCreditableForModal }}';
    }
}

// Header Actions Dropdown
function toggleInvoiceActionMenu(event, button) {
    event.stopPropagation();
    const wrapper = button.closest('[data-inv-actions]');
    if (!wrapper) return;
    const shouldOpen = !wrapper.classList.contains('open');
    document.querySelectorAll('[data-inv-actions].open').forEach(menu => menu.classList.remove('open'));
    if (shouldOpen) {
        wrapper.classList.add('open');
        const menu = wrapper.querySelector('.inv-action-menu');
        if (menu) {
            const btnRect = button.getBoundingClientRect();
            const spaceBelow = window.innerHeight - btnRect.bottom;
            if (spaceBelow < 220 && btnRect.top > 220) {
                menu.style.top = 'auto';
                menu.style.bottom = 'calc(100% + 6px)';
            } else {
                menu.style.top = 'calc(100% + 6px)';
                menu.style.bottom = 'auto';
            }
        }
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('[data-inv-actions]')) {
        document.querySelectorAll('[data-inv-actions].open').forEach(menu => menu.classList.remove('open'));
    }
});

// Send Invoice Email Modal Functions
function openSendInvoiceMailModal(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    document.querySelectorAll('[data-inv-actions].open').forEach(menu => menu.classList.remove('open'));
    const modal = document.getElementById('sendInvoiceMailModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeSendInvoiceMailModal() {
    const modal = document.getElementById('sendInvoiceMailModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

async function submitSendInvoiceMail(e) {
    e.preventDefault();
    const btn = document.getElementById('simSubmitBtn');
    const recipientEmail = document.getElementById('simRecipientEmail')?.value?.trim();
    const subject = document.getElementById('simSubject')?.value?.trim();

    if (!recipientEmail) {
        alert('Please enter a valid recipient email address.');
        return;
    }

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span style="width:14px; height:14px; border:2px solid #fff; border-right-color:transparent; border-radius:50%; display:inline-block; animation:simSpin .6s linear infinite;"></span> <span>Sending Email...</span>';
    }

    try {
        const response = await fetch('{{ route("accounts.sales.invoices.send-email", $invoice->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                recipient_email: recipientEmail,
                subject: subject
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            closeSendInvoiceMailModal();
            if (data.status_badge) {
                const badgeEl = document.getElementById('invoiceStatusBadgeContainer');
                if (badgeEl) badgeEl.innerHTML = data.status_badge;
            }
            alert(data.message || 'Invoice email sent successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to send invoice email.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill"></i> <span>Send Mail</span>';
            }
        }
    } catch (err) {
        console.error('Send mail error:', err);
        alert('An error occurred while sending the email. Please try again.');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> <span>Send Mail</span>';
        }
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreditNoteModal();
        closeRecordPaymentModal();
        closeChangeSalesPersonModal();
        closeSendInvoiceMailModal();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.open('{{ route("accounts.sales.invoices.print", $invoice->id) }}', '_blank');
    }
});
</script>
@endpush
