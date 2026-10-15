@extends('layouts.app')

@section('title', 'Tax Invoice #' . $invoice->invoice_number . ' - Accounts Sales')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f8fafc; font-family:'Inter',sans-serif; min-height:100%; }
.inv-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:16px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,.03); flex-wrap:wrap; gap:14px; }
.inv-breadcrumbs { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:700; color:#64748b; margin-bottom:4px; }
.inv-breadcrumbs a { color:#64748b; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color .15s; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-top-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.inv-sub { font-size:12.5px; color:#64748b; }

.inv-num-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    padding: 3px 10px;
    border-radius: 8px;
    font-family: monospace;
    font-size: 13.5px;
    font-weight: 800;
    color: #0f172a;
    cursor: pointer;
    transition: all .2s;
}
.inv-num-pill:hover {
    background: #fff7ed;
    border-color: #fdba74;
    color: #fe5f04;
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
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}"><i class="bi bi-house-door"></i> Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.invoices.index') }}">Sales Invoices</a>
                <span>/</span>
                <span style="color:#0f172a; font-weight:800;">#{{ $invoice->invoice_number }}</span>
            </div>
            <div class="inv-top-title">
                <i class="bi bi-receipt" style="color:#fe5f04;"></i>
                <span>Invoice</span>
                <span class="inv-num-pill" onclick="copyInvoiceNumber('{{ $invoice->invoice_number }}')" title="Click to copy invoice number">
                    <span>#{{ $invoice->invoice_number }}</span>
                    <i class="bi bi-clipboard" style="font-size:11px; opacity:0.6;"></i>
                </span>
                {!! $invoice->status_badge !!}
            </div>
            <div class="inv-sub">Issued on {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('F d, Y') }} • Client: <strong>{{ $invoice->customer_name }}</strong> {{ $invoice->customer_phone ? "• Tel: {$invoice->customer_phone}" : '' }}</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('accounts.sales.invoices.index') }}" class="acm-btn acm-btn-secondary" title="Back to All Invoices">
                <i class="bi bi-arrow-left"></i> All Invoices
            </a>
            <a href="{{ route('accounts.sales.invoices.edit', $invoice->id) }}" class="acm-btn acm-btn-secondary" title="Edit this invoice">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <a href="{{ route('accounts.sales.invoices.print', $invoice->id) }}" target="_blank" class="acm-btn acm-btn-primary" title="Print or download PDF">
                <i class="bi bi-printer"></i> Print / Download PDF
            </a>
            <button type="button" class="acm-btn acm-btn-outline" onclick="copyInvoiceLink()" title="Copy Shareable Link">
                <i class="bi bi-share"></i> Share Link
            </button>
            <form method="POST" action="{{ route('accounts.sales.invoices.destroy', $invoice->id) }}" onsubmit="return confirm('Are you sure you want to delete invoice #{{ $invoice->invoice_number }}? This action cannot be undone.')" style="margin:0; display:inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="acm-btn acm-btn-danger" title="Delete Invoice">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>

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

    @php
        $sub = (float) $invoice->subtotal;
        $totalAmt = (float) $invoice->total_amount;
        $paidAmt = (float) $invoice->paid_amount;
        $balDue = (float) $invoice->balance_due;
        $paidPct = $totalAmt > 0 ? min(100, round(($paidAmt / $totalAmt) * 100)) : 0;

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
                            <td class="meta-val">{{ $invoice->salesPerson?->name ?? 'Mr. Revanth' }}</td>
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
                            @if($invoice->customer_gstin)
                                <div style="margin-top:4px;"><strong>GSTIN</strong> {{ $invoice->customer_gstin }}</div>
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
                            @if($invoice->customer_gstin)
                                <div style="margin-top:4px;"><strong>GSTIN</strong> {{ $invoice->customer_gstin }}</div>
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
                                        <div>Bank Name - <strong>{{ $setting->bank_name }}</strong></div>
                                    @endif
                                    @if($setting?->account_name)
                                        <div>Account Name - <strong>{{ $setting->account_name }}</strong></div>
                                    @endif
                                    @if($setting?->account_number)
                                        <div>Account Number - <strong>{{ $setting->account_number }}</strong></div>
                                    @endif
                                    @if($setting?->ifsc_code)
                                        <div>IFSC Code - <strong>{{ $setting->ifsc_code }}</strong></div>
                                    @endif
                                    @if($setting?->branch)
                                        <div>Branch - <strong>{{ $setting->branch }}</strong></div>
                                    @endif
                                    @if($setting?->upi_id)
                                        <div>UPI ID - <strong>{{ $setting->upi_id }}</strong></div>
                                    @endif
                                    @if($setting?->swift_code)
                                        <div>Swift Code - <strong>{{ $setting->swift_code }}</strong></div>
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
                                    <tr>
                                        <td class="t-label">Payment Made</td>
                                        <td class="t-val" style="color:{{ $invoice->paid_amount > 0 ? '#b91c1c' : '#000' }}; font-weight:700;">
                                            (-) {{ number_format((float) $invoice->paid_amount, 2) }}
                                        </td>
                                    </tr>
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

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.open('{{ route("accounts.sales.invoices.print", $invoice->id) }}', '_blank');
    }
});
</script>
@endpush
