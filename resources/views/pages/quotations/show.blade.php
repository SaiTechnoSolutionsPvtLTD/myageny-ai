@extends('layouts.app')

@section('title', 'Estimate #' . $quotation->quotation_no . ' - Quotations')

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
}
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
}
.inv-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(254,95,4,0.28);
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
}
.inv-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a !important;
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
}
.inv-canvas-surface {
    background: #cbd5e1;
    border-radius: 14px;
    padding: 36px 16px;
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
    align-items: center;
    padding: 16px 24px 14px 20px;
    border-bottom: 1px solid #000000;
}
.inv-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.inv-logo {
    height: 68px;
    width: auto;
    max-width: 140px;
    object-fit: contain;
}
.inv-comp-title {
    font-size: 15px;
    font-weight: 800;
    color: #000000;
    line-height: 1.25;
    margin-bottom: 4px;
}
.inv-comp-address {
    font-size: 11px;
    color: #000000;
    line-height: 1.4;
}
.inv-tax-title {
    font-size: 26px;
    font-weight: 900;
    color: #000000;
    letter-spacing: 0.5px;
    text-align: right;
    white-space: nowrap;
    line-height: 1;
    margin: 0;
    padding-right: 4px;
}

/* 2-Column Metadata Box (50% / 50% split) */
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

/* Parties Table (Exact 50% / 50% matching Metadata Grid above) */
.parties-tbl-wrap {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1px solid #000000;
}
.party-head {
    background: #f3f4f6;
    color: #000000;
    font-size: 11px;
    font-weight: 800;
    padding: 5px 12px;
    text-align: left;
    border-bottom: 1px solid #000000;
}
.party-body {
    padding: 8px 12px;
    font-size: 11px;
    line-height: 1.4;
    color: #000000;
    vertical-align: top;
    height: 105px;
}
.party-name {
    font-weight: 800;
    color: #000000;
    margin-bottom: 2px;
}
.col-border-left {
    border-left: 1px solid #000000 !important;
}

/* Line Items Table */
.inv-items-tbl {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 11px;
    margin: 0;
    padding: 0;
    border-bottom: 1px solid #000000;
}
.th-item {
    background: #f3f4f6;
    color: #000000;
    font-weight: 800;
    border-bottom: 1px solid #000000;
    border-right: 1px solid #000000;
    padding: 6px 8px;
    vertical-align: middle;
}
.th-item:last-child {
    border-right: none !important;
}

/* Items Data Cells */
.inv-items-tbl tbody td.td-item {
    padding: 8px 10px;
    vertical-align: top;
    border-right: 1px solid #000000;
    border-bottom: 1px solid #000000;
    color: #000000;
}
.inv-items-tbl tbody tr:last-child td.td-item {
    border-bottom: none !important;
}
.inv-items-tbl tbody tr.item-row:hover td.td-item {
    background-color: #fafbfc;
}
.td-item:last-child {
    border-right: none !important;
}
.td-item, .t-val, .meta-val {
    font-variant-numeric: tabular-nums;
}

/* Bottom Section Table */
.inv-bottom-tbl {
    width: 100%;
    border-collapse: collapse;
}
.bottom-left-cell {
    border-right: 1px solid #000000 !important;
    padding: 12px 14px 14px 14px !important;
    vertical-align: top !important;
}
.bottom-right-cell {
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
    max-width: 820px;
    width: 100%;
    margin: 0 auto;
}

.terms-page-content p { margin: 0 0 10px 0; }
.terms-page-content ol, .terms-page-content ul { margin: 0 0 12px 0; padding-left: 22px; }
.terms-page-content li { margin-bottom: 7px; line-height: 1.6; }
.terms-page-content h1, .terms-page-content h2, .terms-page-content h3, .terms-page-content h4 { margin: 12px 0 6px 0; color: #000; }
.terms-page-content table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.terms-page-content th, .terms-page-content td { border: 1px solid #000; padding: 6px 8px; }

@page {
    size: A4 portrait;
    margin: 12mm 14mm 12mm 14mm;
}
@media print {
    header, nav, aside, .app-header, .app-sidebar, .sidebar, .main-header, .inv-topbar, .inv-canvas-header, .page-num, .inv-back-btn, .inv-btn-primary, .inv-btn-secondary {
        display: none !important;
    }
    body {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .inv-page, .inv-body, .inv-canvas-container, .inv-canvas-surface {
        padding: 0 !important;
        margin: 0 !important;
        background: #ffffff !important;
        border: none !important;
        box-shadow: none !important;
    }
    .invoice-sheet {
        box-shadow: none !important;
        max-width: 100% !important;
        width: 100% !important;
        border: 1px solid #000000 !important;
        page-break-after: avoid;
        break-after: avoid;
        -webkit-box-decoration-break: clone !important;
        box-decoration-break: clone !important;
        margin: 0 auto !important;
        transform: none !important;
    }
    .invoice-sheet.terms-sheet {
        page-break-before: always !important;
        break-before: page !important;
        page-break-after: auto !important;
        break-after: auto !important;
        margin: 0 auto !important;
    }
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
/* Convert Quotation to Invoice Modal */
.cqi-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.cqi-modal-dialog {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.25);
    overflow: hidden;
    animation: cqiFadeIn .2s ease-out;
}
@keyframes cqiFadeIn {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
}
.cqi-modal-header {
    padding: 18px 22px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.cqi-close-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
    padding: 0 4px;
}
.cqi-close-btn:hover { color: #0f172a; }
.cqi-modal-body { padding: 20px 22px; }
.cqi-modal-footer {
    padding: 14px 22px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}
.cqi-input, .cqi-select {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    color: #0f172a;
    background: #fff;
    box-sizing: border-box;
}
.cqi-input:focus, .cqi-select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}
.cqi-btn-primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: none;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 6px rgba(37,99,235,0.25);
    text-decoration: none;
}
.cqi-btn-primary:hover {
    background: #1d4ed8;
    color: #fff;
}
.cqi-btn-outline {
    background: #fff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}
.cqi-btn-outline:hover {
    background: #eff6ff;
    color: #1d4ed8;
}
.cqi-btn-ghost {
    background: none;
    border: none;
    color: #64748b;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
.cqi-btn-ghost:hover { color: #0f172a; }
</style>
@endpush

@section('content')
@php
    // Quotation Settings (Company, Branding, Bank & Terms) strictly for this company
    $companyName = !empty($quoteSetting['company_name']) ? $quoteSetting['company_name'] : ($quotation->company?->company_name ?? 'Company');
    $companyAddress = !empty($quoteSetting['company_address']) ? $quoteSetting['company_address'] : ($quotation->company?->address ?? '');
    $companyPhone = !empty($quoteSetting['company_phone']) ? $quoteSetting['company_phone'] : ($quotation->company?->mobile_number ?? '');
    $companyEmail = !empty($quoteSetting['company_email']) ? $quoteSetting['company_email'] : ($quotation->company?->email ?? '');
    $companyGstin = !empty($quoteSetting['company_gstin']) ? $quoteSetting['company_gstin'] : '';

    $logoUrl = null;
    if (!empty($quoteSetting['logo']) && file_exists(public_path($quoteSetting['logo']))) {
        $logoUrl = asset($quoteSetting['logo']);
    }

    $signatureUrl = null;
    if (!empty($quoteSetting['signature']) && file_exists(public_path($quoteSetting['signature']))) {
        $signatureUrl = asset($quoteSetting['signature']);
    }

    $stateCodes = [
        'Jammu and Kashmir' => '01', 'Himachal Pradesh' => '02', 'Punjab' => '03', 'Chandigarh' => '04',
        'Uttarakhand' => '05', 'Haryana' => '06', 'Delhi' => '07', 'Rajasthan' => '08',
        'Uttar Pradesh' => '09', 'Bihar' => '10', 'Sikkim' => '11', 'Arunachal Pradesh' => '12',
        'Nagaland' => '13', 'Manipur' => '14', 'Mizoram' => '15', 'Tripura' => '16',
        'Meghalaya' => '17', 'Assam' => '18', 'West Bengal' => '19', 'Jharkhand' => '20',
        'Odisha' => '21', 'Chhattisgarh' => '22', 'Madhya Pradesh' => '23', 'Gujarat' => '24',
        'Dadra and Nagar Haveli and Daman and Diu' => '26', 'Maharashtra' => '27', 'Andhra Pradesh' => '37',
        'Karnataka' => '29', 'Goa' => '30', 'Lakshadweep' => '31', 'Kerala' => '32',
        'Tamil Nadu' => '33', 'Puducherry' => '34', 'Andaman and Nicobar Islands' => '35',
        'Telangana' => '36', 'Ladakh' => '38',
    ];

    $posRaw = $quotation->customer_state ?: ($quotation->seller_state ?: 'Tamil Nadu');
    $stateCode = null;
    if ($quotation->gst_number && strlen(trim($quotation->gst_number)) >= 2 && is_numeric(substr(trim($quotation->gst_number), 0, 2))) {
        $stateCode = substr(trim($quotation->gst_number), 0, 2);
    }
    if (!$stateCode) {
        foreach ($stateCodes as $stName => $code) {
            if (stripos($posRaw, $stName) !== false) {
                $stateCode = $code;
                $posRaw = $stName;
                break;
            }
        }
    }
    $formattedPos = $stateCode ? ($posRaw . ' (' . $stateCode . ')') : $posRaw;

    $salesPerson = $quotation->lead?->assignedTo?->name
        ?: ($quotation->createdBy?->name ?: ($quotation->lead?->createdBy?->name ?: '—'));

    $customerName = $quotation->lead?->company_name
        ?: ($quotation->lead?->contact_name ?: 'Customer');

    // Bank Details from Quotation Setting
    $bankName = !empty($quoteSetting['bank_name']) ? $quoteSetting['bank_name'] : '';
    $accountName = !empty($quoteSetting['account_name']) ? $quoteSetting['account_name'] : '';
    $accountNumber = !empty($quoteSetting['bank_account']) ? $quoteSetting['bank_account'] : '';
    $ifscCode = !empty($quoteSetting['bank_ifsc']) ? $quoteSetting['bank_ifsc'] : '';
    $branch = !empty($quoteSetting['bank_branch']) ? $quoteSetting['bank_branch'] : '';
    $upiId = !empty($quoteSetting['bank_upi']) ? $quoteSetting['bank_upi'] : '';
    $swiftCode = $quoteSetting['swift_code'] ?? null;

    $amountInWords = \App\Models\Invoice::convertToIndianCurrencyWords((float) $quotation->total_amount);

    $subtotal = (float) $quotation->subtotal;
    $cgstAmount = (float) $quotation->cgst_amount;
    $sgstAmount = (float) $quotation->sgst_amount;
    $igstAmount = (float) $quotation->igst_amount;
    $totalAmount = (float) $quotation->total_amount;

    $calcTotal = $subtotal + $cgstAmount + $sgstAmount + $igstAmount;
    $rounding = round($totalAmount - $calcTotal, 2);

    $cgstRate = (float) ($quotation->cgst_rate ?: 9);
    $sgstRate = (float) ($quotation->sgst_rate ?: 9);
    $igstRate = (float) ($quotation->igst_rate ?: 18);

    // Terms Content for Page 2 strictly from Quotation Setting
    $termsBody = !empty(trim((string) ($quoteSetting['terms'] ?? ''))) ? $quoteSetting['terms'] : '';
    $isHtmlTerms = strip_tags($termsBody) !== $termsBody;
@endphp

<div class="inv-page">

    {{-- Top Navigation & Action Bar --}}
    <div class="inv-topbar">
        <div class="inv-top-nav">
            <div class="inv-nav-left">
                <a href="{{ request()->routeIs('accounts.*') ? route('accounts.quotations.index') : route('quotations.index') }}" class="inv-back-btn">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Quotations</span>
                </a>
                <div class="inv-breadcrumbs">
                    <a href="{{ request()->routeIs('accounts.*') ? route('accounts.dashboard') : route('dashboard') }}"><i class="bi bi-house-door"></i> Home</a>
                    <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                    <a href="{{ request()->routeIs('accounts.*') ? route('accounts.quotations.index') : route('quotations.index') }}">Quotations</a>
                    <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                    <span style="color:#0f172a; font-weight:700;">{{ $quotation->quotation_no }}</span>
                </div>
            </div>
            <div class="inv-nav-right">
                @php
                    $convertedInvoice = $quotation->converted_invoice;
                    $zohoQuoteRecord = \App\Models\ZohoQuotation::where('migrated_native_quotation_id', $quotation->id)
                        ->orWhere('estimate_number', $quotation->quotation_no)
                        ->first();
                @endphp
                @if($zohoQuoteRecord)
                    <a href="{{ route('accounts.zoho.show-quotation', $zohoQuoteRecord->id) }}" target="_blank" class="inv-btn-secondary" style="background:#f0f9ff; border-color:#bae6fd; color:#0284c7; font-weight:700;" title="View Zoho Books Estimate ID: {{ $zohoQuoteRecord->zoho_estimate_id }}">
                        <i class="bi bi-cloud-check-fill"></i> Synced in Zoho ↗
                    </a>
                @else
                    <form action="{{ request()->routeIs('accounts.*') ? route('accounts.quotations.post-to-zoho', $quotation->id) : route('quotations.post-to-zoho', $quotation->id) }}" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Manually post Quotation #{{ $quotation->quotation_no }} to Zoho Books?');">
                        @csrf
                        <button type="submit" class="inv-btn-secondary" style="background:#fff7ed; border-color:#fed7aa; color:#ea580c; font-weight:700;">
                            <i class="bi bi-cloud-arrow-up-fill"></i> Post to Zoho
                        </button>
                    </form>
                @endif
                @if($convertedInvoice)
                    <a href="{{ route('accounts.sales.invoices.show', $convertedInvoice->id) }}" class="inv-btn-secondary" style="background:#ecfdf5; border-color:#a7f3d0; color:#047857; font-weight:700;">
                        <i class="bi bi-receipt-cutoff"></i> View Invoice (#{{ $convertedInvoice->invoice_number }})
                    </a>
                @endif
                <button type="button" onclick="openConvertModal()" class="inv-btn-primary" style="background:linear-gradient(135deg, #2563eb, #1d4ed8); border-color:#1d4ed8; color:#ffffff; font-weight:700; box-shadow:0 2px 8px rgba(37,99,235,0.25);">
                    <i class="bi bi-arrow-repeat"></i> {{ $convertedInvoice ? 'Convert Again' : 'Convert to Invoice' }}
                </button>
                <a href="{{ route('quotations.edit', $quotation) }}" class="inv-btn-secondary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="{{ route('quotation.print', ['quotation' => $quotation->id, 'autoprint' => 1]) }}" target="_blank" class="inv-btn-primary">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                </a>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div class="inv-title-row">
                <h1 class="inv-title">Estimate</h1>
                <span class="inv-num-pill">{{ $quotation->quotation_no }}</span>
                @if($zohoQuoteRecord)
                    <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:4px;" title="Zoho Books Estimate ID: {{ $zohoQuoteRecord->zoho_estimate_id }}">
                        <i class="bi bi-cloud-check-fill"></i> Zoho Synced
                    </span>
                @endif
                @if($convertedInvoice)
                    <a href="{{ route('accounts.sales.invoices.show', $convertedInvoice->id) }}" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:4px; text-decoration:none;" title="Click to view linked invoice">
                        <i class="bi bi-receipt-cutoff"></i> Invoiced: {{ $convertedInvoice->invoice_number }}
                    </a>
                @endif
                @if($quotation->is_approved)
                    <span style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                        <i class="bi bi-check-circle-fill"></i> Approved
                    </span>
                @else
                    <span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:4px;">
                        <i class="bi bi-clock-fill"></i> Pending Approval
                    </span>
                @endif

                @if($quotation->lead)
                    <a href="{{ route('leads.show', $quotation->lead_id) }}" target="_blank" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:800; display:inline-flex; align-items:center; gap:4px; text-decoration:none;" title="View Linked Lead in CRM">
                        <i class="bi bi-person-check-fill"></i> Lead: {{ $quotation->lead->contact_name ?: $quotation->lead->company_name }}
                    </a>
                    <button type="button" onclick="openMapLeadModal()" style="border:none; background:none; color:#64748b; cursor:pointer; font-size:11px; text-decoration:underline;" title="Change Linked Lead">
                        Change
                    </button>
                @else
                    <button type="button" onclick="openMapLeadModal()" style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; font-size:11px; font-weight:800; cursor:pointer;" title="Link this quotation to a CRM Lead">
                        <i class="bi bi-link-45deg"></i> Map to Lead
                    </button>
                @endif
            </div>

            <div class="inv-header-actions">
                @if(!$quotation->is_approved)
                <form method="POST" action="{{ route('quotations.approve', $quotation) }}" style="margin:0;">
                    @csrf @method('PATCH')
                    <button type="submit" class="inv-btn-primary" style="background:linear-gradient(135deg, #16a34a, #15803d); box-shadow:0 2px 6px rgba(22,163,74,0.18);">
                        <i class="bi bi-check-circle-fill"></i> Approve
                    </button>
                </form>
                @endif
                @if($convertedInvoice)
                    <a href="{{ route('accounts.sales.invoices.show', $convertedInvoice->id) }}" class="inv-btn-secondary" style="background:#ecfdf5; border-color:#a7f3d0; color:#047857; font-weight:700;">
                        <i class="bi bi-check2-circle"></i> Invoiced: {{ $convertedInvoice->invoice_number }}
                    </a>
                @endif
                <button type="button" onclick="openConvertModal()" class="inv-btn-primary" style="background:linear-gradient(135deg, #2563eb, #1d4ed8); border-color:#1d4ed8; color:#ffffff; font-weight:700; box-shadow:0 2px 8px rgba(37,99,235,0.25);">
                    <i class="bi bi-arrow-repeat"></i> {{ $convertedInvoice ? 'Convert Again' : 'Convert to Invoice' }}
                </button>
                <a href="{{ route('quotation.print', ['quotation' => $quotation->id, 'autoprint' => 1]) }}" target="_blank" class="inv-btn-secondary">
                    <i class="bi bi-printer-fill"></i> Print
                </a>
            </div>
        </div>
    </div>

    <div class="inv-body">

        @if(session('success'))
            <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($quotation->customer_signature || $quotation->customer_responded_at)
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div style="width:40px; height:40px; border-radius:10px; background:{{ $quotation->is_approved ? '#ecfdf5' : '#fef2f2' }}; color:{{ $quotation->is_approved ? '#059669' : '#dc2626' }}; display:inline-flex; align-items:center; justify-content:center; font-size:20px;">
                        <i class="bi {{ $quotation->is_approved ? 'bi-patch-check-fill' : 'bi-x-circle-fill' }}"></i>
                    </div>
                    <div>
                        <div style="font-size:14px; font-weight:700; color:#0f172a;">
                            Customer {{ $quotation->is_approved ? 'Approved' : 'Rejected' }}
                        </div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;">
                            Responded on {{ $quotation->customer_responded_at?->format('d M Y, h:i A') }}
                            @if($quotation->customer_location)
                                • 📍 {{ $quotation->customer_location }}
                            @endif
                            @if($quotation->customer_ip)
                                • 🌐 IP: {{ $quotation->customer_ip }}
                            @endif
                        </div>
                    </div>
                </div>
                @if($quotation->customer_signature)
                    <div style="display:flex; align-items:center; gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:6px 14px;">
                        <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Customer Signature:</span>
                        <img src="{{ asset($quotation->customer_signature) }}" alt="Customer Signature" style="max-height:45px; max-width:140px; object-fit:contain;">
                    </div>
                @endif
            </div>
        @endif

        {{-- Document Viewer Canvas --}}
        <div class="inv-canvas-wrap">
            <div class="inv-doc-toolbar">
                <div class="inv-doc-tool-left">
                    <span class="inv-pill"><i class="bi bi-file-earmark-pdf-fill" style="color:#ef4444;"></i> Estimate (A4)</span>
                    <span class="inv-pill-light"><i class="bi bi-file-text"></i> 2 Pages</span>
                </div>
                <div class="inv-doc-tool-center">
                    <button type="button" class="inv-tool-btn" onclick="zoomInvoice(-0.1)" title="Zoom Out">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <span id="zoomLevelDisplay" style="font-size:12px; font-weight:700; color:#334155; min-width:44px; text-align:center;">100%</span>
                    <button type="button" class="inv-tool-btn" onclick="zoomInvoice(0.1)" title="Zoom In">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                    <button type="button" class="inv-tool-btn" onclick="resetZoom()" title="Reset Zoom">
                        <i class="bi bi-arrows-angle-contract"></i> 100%
                    </button>
                </div>
                <div class="inv-doc-tool-right">
                    <a href="{{ route('quotation.print', ['quotation' => $quotation->id, 'autoprint' => 1]) }}" target="_blank" class="inv-tool-btn" style="background:#fff7ed; border-color:#fdba74; color:#ea580c;">
                        <i class="bi bi-file-earmark-arrow-down-fill"></i> Download PDF
                    </a>
                </div>
            </div>

            <div class="inv-canvas-surface" id="invoiceCanvas">
                
                {{-- PAGE 1: ESTIMATE SHEET --}}
                <div class="invoice-sheet" id="invoiceSheet">

                    {{-- 1. Top Header --}}
                    <div class="inv-header">
                        <div class="inv-header-left">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="inv-logo">
                            @endif
                            <div>
                                <div class="inv-comp-title">{{ $companyName }}</div>
                                <div class="inv-comp-address">
                                    <div>{!! nl2br(e($companyAddress)) !!}</div>
                                    @if(!empty($companyPhone) || !empty($companyEmail))
                                        <div style="margin-top:1px;">
                                            @if(!empty($companyPhone))<span>Phone: {{ $companyPhone }}</span>@endif
                                            @if(!empty($companyPhone) && !empty($companyEmail)) &middot; @endif
                                            @if(!empty($companyEmail))<span>Email: {{ $companyEmail }}</span>@endif
                                        </div>
                                    @endif
                                    @if(!empty($companyGstin))
                                        <div style="margin-top:2px;">
                                            <strong>GSTIN</strong> {{ $companyGstin }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="inv-tax-title">
                            Estimate
                        </div>
                    </div>

                    {{-- 2. Metadata Grid (50% / 50% split) --}}
                    <div class="meta-grid">
                        <div class="meta-col">
                            <table class="meta-tbl">
                                <tr>
                                    <td class="meta-label">Estimate Number</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-val">{{ $quotation->quotation_no }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Estimate Date</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-val">{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Valid Until</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-val">{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d/m/Y') : ($quotation->quotation_date ? \Carbon\Carbon::parse($quotation->quotation_date)->addDays(7)->format('d/m/Y') : '—') }}</td>
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
                                    <td class="meta-label">Sales Person</td>
                                    <td class="meta-colon">:</td>
                                    <td class="meta-val">{{ $salesPerson }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- 3. Bill To & Ship To (Exact 50% / 50% matching Metadata Grid border above) --}}
                    <table class="parties-tbl-wrap">
                        <tr>
                            <th class="party-head" style="width: 50%;">Bill To</th>
                            <th class="party-head col-border-left" style="width: 50%;">Ship To</th>
                        </tr>
                        <tr>
                            <td class="party-body" style="width: 50%;">
                                <div class="party-name">{{ $customerName }}</div>
                                @if($quotation->lead?->company_name && $quotation->lead?->contact_name && $quotation->lead?->company_name !== $quotation->lead?->contact_name)
                                    <div style="font-weight:700; color:#334155; margin-bottom:2px;">{{ $quotation->lead->company_name }}</div>
                                @endif
                                @if($quotation->bill_to_address)
                                    <div>{!! nl2br(e($quotation->bill_to_address)) !!}</div>
                                @elseif($quotation->lead?->address)
                                    <div>{!! nl2br(e($quotation->lead->address)) !!}</div>
                                @endif
                                <div>India</div>
                                @if($quotation->gst_number)
                                    <div style="margin-top:4px;"><strong>GSTIN</strong> {{ $quotation->gst_number }}</div>
                                @endif
                            </td>
                            <td class="party-body col-border-left" style="width: 50%;">
                                <div class="party-name">{{ $customerName }}</div>
                                @if($quotation->ship_to_address)
                                    <div>{!! nl2br(e($quotation->ship_to_address)) !!}</div>
                                @elseif($quotation->bill_to_address)
                                    <div>{!! nl2br(e($quotation->bill_to_address)) !!}</div>
                                @elseif($quotation->lead?->address)
                                    <div>{!! nl2br(e($quotation->lead->address)) !!}</div>
                                @endif
                                <div>India</div>
                                @if($quotation->gst_number)
                                    <div style="margin-top:4px;"><strong>GSTIN</strong> {{ $quotation->gst_number }}</div>
                                @endif
                            </td>
                        </tr>
                    </table>

                    {{-- 4. Line Items Table --}}
                    <table class="inv-items-tbl">
                        <colgroup>
                            <col style="width: 5%;">
                            <col style="width: 45%;">
                            <col style="width: 10%;">
                            <col style="width: 8%;">
                            <col style="width: 16%;">
                            <col style="width: 16%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="th-item" style="text-align:center;">#</th>
                                <th class="th-item" style="text-align:left;">Item &amp; Description</th>
                                <th class="th-item" style="text-align:center;">HSN/SAC</th>
                                <th class="th-item" style="text-align:center;">Qty</th>
                                <th class="th-item" style="text-align:right;">Rate</th>
                                <th class="th-item" style="text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quotation->items as $index => $item)
                            <tr class="item-row">
                                <td class="td-item" style="text-align:center; font-weight:700;">{{ $index + 1 }}</td>
                                <td class="td-item">
                                    <div style="font-weight:800; color:#000;">{{ $item->product?->product_name ?: ($item->product?->package_name ?: 'Item') }}</div>
                                    @if($item->description)
                                        <div style="font-size:10.5px; color:#334155; margin-top:2px; line-height:1.4;">
                                            {!! nl2br(e($item->description)) !!}
                                        </div>
                                    @endif
                                </td>
                                <td class="td-item" style="text-align:center;">{{ $item->hsn_sac ?? '9983' }}</td>
                                <td class="td-item" style="text-align:center;">{{ number_format((float) $item->qty, 2) }}</td>
                                <td class="td-item" style="text-align:right;">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="td-item" style="text-align:right;">{{ number_format((float) $item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- 5. Bottom Section Table --}}
                    <table class="inv-bottom-tbl">
                        <tr>
                            <td class="bottom-left-cell" style="width: 60%;">
                                <div>
                                    <div class="sec-head">Total In Words</div>
                                    <div style="font-size:11.5px; font-weight:800; font-style:italic; color:#000; margin-top:2px;">
                                        {{ $amountInWords }}
                                    </div>
                                </div>

                                <div style="margin-top:12px;">
                                    <div class="sec-head">Notes</div>
                                    <div class="sec-body">
                                        {{ $quotation->notes ?: 'Thanks for your business.' }}
                                    </div>
                                </div>

                                <div style="margin-top:12px;">
                                    <div style="font-size:11px; line-height:1.6; color:#000;">
                                        @if($bankName)
                                            <div>Bank Name - {{ $bankName }}</div>
                                        @endif
                                        @if($accountName)
                                            <div>Account Name - {{ $accountName }}</div>
                                        @endif
                                        @if($accountNumber)
                                            <div>Account Number - {{ $accountNumber }}</div>
                                        @endif
                                        @if($ifscCode)
                                            <div>IFSC Code - {{ $ifscCode }}</div>
                                        @endif
                                        @if($branch)
                                            <div>Branch - {{ $branch }}</div>
                                        @endif
                                        @if($upiId)
                                            <div>UPI ID - {{ $upiId }}</div>
                                        @endif
                                        @if($swiftCode)
                                            <div>Swift Code - {{ $swiftCode }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="bottom-right-cell" style="width: 40%;">
                                <div class="bottom-right-wrap">
                                    <table class="totals-tbl">
                                        <colgroup>
                                            <col style="width: 60%;">
                                            <col style="width: 40%;">
                                        </colgroup>
                                        <tr>
                                            <td class="t-label">Sub Total</td>
                                            <td class="t-val">{{ number_format($subtotal, 2) }}</td>
                                        </tr>
                                        @if($cgstAmount > 0 || $sgstAmount > 0 || ($igstAmount == 0))
                                            <tr>
                                                <td class="t-label">CGST ({{ number_format($cgstRate, 0) }}%)</td>
                                                <td class="t-val">{{ number_format($cgstAmount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="t-label">SGST ({{ number_format($sgstRate, 0) }}%)</td>
                                                <td class="t-val">{{ number_format($sgstAmount, 2) }}</td>
                                            </tr>
                                        @endif
                                        @if($igstAmount > 0)
                                            <tr>
                                                <td class="t-label">IGST ({{ number_format($igstRate, 0) }}%)</td>
                                                <td class="t-val">{{ number_format($igstAmount, 2) }}</td>
                                            </tr>
                                        @endif
                                        @if(abs($rounding) >= 0.01)
                                            <tr>
                                                <td class="t-label">Round Off</td>
                                                <td class="t-val">{{ $rounding > 0 ? '(+) ' : '(-) ' }}{{ number_format(abs($rounding), 2) }}</td>
                                            </tr>
                                        @endif
                                        <tr class="total-row">
                                            <td class="t-label">Total</td>
                                            <td class="t-val">&#8377;{{ number_format($totalAmount, 2) }}</td>
                                        </tr>
                                    </table>

                                    <div class="sig-container">
                                        <div class="sig-for">{{ 'FOR ' . strtoupper($companyName) }}</div>
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
                    </table>

                </div>

                <div class="page-num" style="text-align:right; font-size:11px; color:#64748b; padding-top:6px; max-width:820px; margin:0 auto 24px auto;">Page 1 of 2</div>

                {{-- DEDICATED PAGE 2: TERMS & CONDITIONS ONLY --}}
                <div class="invoice-sheet" id="invoiceSheetPage2" style="margin:0 auto; padding:32px 36px; box-sizing:border-box;">
                    <div style="font-size: 18px; font-weight: 800; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 12px; border-bottom: 1px solid #000000; margin-bottom: 22px;">
                        Terms &amp; Conditions
                    </div>

                    <div class="terms-page-content" style="font-size: 11px; line-height: 1.7; color: #0f172a;">
                        @if($isHtmlTerms)
                            {!! $termsBody !!}
                        @else
                            {!! nl2br(e($termsBody)) !!}
                        @endif
                    </div>
                </div>

                <div class="page-num" style="text-align:right; font-size:11px; color:#64748b; padding-top:6px; max-width:820px; margin:0 auto 10px auto;">Page 2 of 2</div>

            </div>
        </div>

    </div>

</div>

{{-- Convert Quotation to Invoice Modal --}}
<div id="convertInvoiceModal" class="cqi-modal-backdrop" style="display:none;" onclick="if(event.target === this) closeConvertModal()">
    <div class="cqi-modal-dialog">
        <div class="cqi-modal-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:38px; height:38px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:800; color:#0f172a;">Convert Quotation to Invoice</h3>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Quotation #{{ $quotation->quotation_no }}</div>
                </div>
            </div>
            <button type="button" class="cqi-close-btn" onclick="closeConvertModal()" title="Close">&times;</button>
        </div>

        <form method="POST" action="{{ route('quotations.convert-to-invoice', $quotation) }}" id="convertInvoiceForm">
            @csrf
            <div class="cqi-modal-body">
                @if($convertedInvoice)
                    <div style="background:#fffbeb; border:1px solid #fef3c7; border-radius:10px; padding:10px 14px; font-size:12px; color:#92400e; display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size:16px; color:#d97706;"></i>
                        <div>This quotation was already converted to <strong>Invoice #{{ $convertedInvoice->invoice_number }}</strong>. Converting again will create an additional invoice.</div>
                    </div>
                @endif

                {{-- Summary Overview Card --}}
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:18px; display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:13px;">
                    <div>
                        <span style="color:#64748b; font-size:11px; text-transform:uppercase; font-weight:700;">Customer / Company</span>
                        <div style="font-weight:700; color:#0f172a; margin-top:2px;">{{ $quotation->lead?->company_name ?: ($quotation->lead?->contact_name ?: 'Valued Customer') }}</div>
                        <div style="font-size:11px; color:#64748b;">{{ $quotation->lead?->mobile_number ?: 'No phone' }}</div>
                    </div>
                    <div style="text-align:right;">
                        <span style="color:#64748b; font-size:11px; text-transform:uppercase; font-weight:700;">Total Amount</span>
                        <div style="font-weight:800; color:#16a34a; font-size:17px; margin-top:2px;">₹{{ number_format((float) $quotation->total_amount, 2) }}</div>
                        <div style="font-size:11px; color:#64748b;">{{ $quotation->items->count() }} item(s) included</div>
                    </div>
                </div>

                {{-- Input Fields --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Invoice Date <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="invoice_date" class="cqi-input" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Due Date</label>
                        <input type="date" name="due_date" class="cqi-input" value="{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('Y-m-d') : date('Y-m-d', strtotime('+7 days')) }}">
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px;">Payment Terms</label>
                    <select name="payment_terms" class="cqi-select">
                        <option value="Due on Receipt" selected>Due on Receipt</option>
                        <option value="Net 15">Net 15 (15 Days)</option>
                        <option value="Net 30">Net 30 (30 Days)</option>
                        <option value="Net 45">Net 45 (45 Days)</option>
                        <option value="Net 60">Net 60 (60 Days)</option>
                    </select>
                </div>

                <div style="font-size:12px; color:#64748b; background:#f1f5f9; padding:10px 12px; border-radius:8px; line-height:1.4;">
                    ℹ️ Line items, unit prices, tax rates (CGST/SGST/IGST), customer details and addresses will be accurately converted to the new Invoice.
                </div>
            </div>

            <div class="cqi-modal-footer">
                <button type="button" class="cqi-btn-ghost" onclick="closeConvertModal()">Cancel</button>
                <a href="{{ route('accounts.sales.invoices.create', ['from_quotation_id' => $quotation->id]) }}" class="cqi-btn-outline" title="Open Invoice Create screen with pre-filled quotation items">
                    <i class="bi bi-pencil-square"></i> Open in Editor
                </a>
                <button type="submit" class="cqi-btn-primary">
                    <i class="bi bi-check2-circle"></i> Confirm & Convert
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentZoom = 1.0;
function zoomInvoice(delta) {
    currentZoom = Math.min(Math.max(0.6, currentZoom + delta), 1.5);
    applyZoom();
}
function resetZoom() {
    currentZoom = 1.0;
    applyZoom();
}
function applyZoom() {
    const s1 = document.getElementById('invoiceSheet');
    const s2 = document.getElementById('invoiceSheetPage2');
    const disp = document.getElementById('zoomLevelDisplay');
    if (s1) s1.style.transform = `scale(${currentZoom})`;
    if (s2) s2.style.transform = `scale(${currentZoom})`;
    if (disp) disp.innerText = `${Math.round(currentZoom * 100)}%`;
}

function openConvertModal() {
    const modal = document.getElementById('convertInvoiceModal');
    if (modal) modal.style.display = 'flex';
}
function closeConvertModal() {
    const modal = document.getElementById('convertInvoiceModal');
    if (modal) modal.style.display = 'none';
}

function openMapLeadModal() {
    document.getElementById('mapLeadModal').style.display = 'flex';
    document.getElementById('quoteLeadSearchBox').focus();
}
function closeMapLeadModal() {
    document.getElementById('mapLeadModal').style.display = 'none';
}
let quoteLeadSearchTimeout = null;
function searchLeadsForQuoteModal(q) {
    clearTimeout(quoteLeadSearchTimeout);
    quoteLeadSearchTimeout = setTimeout(() => {
        if (!q || q.length < 2) return;
        fetch('{{ route('api.leads.search') }}?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('modalQuoteLeadSelect');
                sel.innerHTML = '';
                if (!data.results || !data.results.length) {
                    sel.innerHTML = '<option value="" disabled>No leads found matching "' + q + '"</option>';
                    return;
                }
                data.results.forEach((item, idx) => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.text = item.text;
                    if (idx === 0) opt.selected = true;
                    sel.appendChild(opt);
                });
            });
    }, 250);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeConvertModal();
        closeMapLeadModal();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.open('{{ route("quotation.print", ["quotation" => $quotation->id, "autoprint" => 1]) }}', '_blank');
    }
});
</script>

{{-- MODAL: Map Quotation to Lead --}}
<div id="mapLeadModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:94%; max-width:520px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.25);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
            <div style="font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-person-check-fill" style="color:#2563eb;"></i>
                Map Quotation #{{ $quotation->quotation_no }} to CRM Lead
            </div>
            <button type="button" onclick="closeMapLeadModal()" style="border:none; background:none; font-size:22px; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="{{ route('quotations.map-lead', $quotation->id) }}" style="margin:0;">
            @csrf
            @method('PATCH')
            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="font-size:12px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">Search Lead by Name, Company, Phone, or ID:</label>
                    <input type="text" id="quoteLeadSearchBox" placeholder="Type name, company, or phone (e.g. 9840012345)..." onkeyup="searchLeadsForQuoteModal(this.value)" autocomplete="off" style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:12px; font-weight:700; color:#334155; display:block; margin-bottom:6px;">Select Matching Lead: <span style="color:#ef4444;">*</span></label>
                    <select name="lead_id" id="modalQuoteLeadSelect" required size="5" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; box-sizing:border-box; min-height:120px;">
                        <option value="" disabled selected>— Type in the search box above to find leads —</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:14px 20px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                <button type="button" onclick="closeMapLeadModal()" class="inv-btn-secondary" style="padding:8px 16px; border-radius:8px; font-size:13px;">Cancel</button>
                <button type="submit" class="inv-btn-primary" style="padding:8px 18px; border-radius:8px; font-size:13px; font-weight:700;">Save Mapping</button>
            </div>
        </form>
    </div>
</div>
@endsection
