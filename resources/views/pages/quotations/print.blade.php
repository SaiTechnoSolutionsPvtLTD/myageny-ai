<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate #{{ $quotation->quotation_no }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #e2e8f0;
            padding: 30px 10px;
            color: #000000;
            font-size: 11px;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
        }

        .print-btn-bar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 10px 18px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            flex-wrap: wrap;
            gap: 10px;
        }
        .p-btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12.5px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all 0.2s ease;
        }
        .p-btn-print {
            background: linear-gradient(135deg, #fe5f04, #ff7c30);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25);
        }
        .p-btn-print:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(254, 95, 4, 0.35);
            color: #ffffff;
        }
        .p-btn-back {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }
        .p-btn-back:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .print-info-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
        }
        .print-hint {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        /* The Exact A4 Outline Box */
        .invoice-sheet {
            background: #ffffff;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            border: 1px solid #000000;
            box-shadow: 0 8px 24px rgba(0,0,0,.1);
            position: relative;
            -webkit-box-decoration-break: clone;
            box-decoration-break: clone;
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
            font-size: 20px;
            font-weight: 900;
            color: #000000;
            letter-spacing: 0.5px;
            text-align: right;
            white-space: nowrap;
            line-height: 1;
            margin: 0;
            padding-right: 4px;
        }

        /* 2-Column Metadata Box (Exact 50% / 50% split) */
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
            border-top: none !important;
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
            max-width: 820px;
            margin: 0 auto;
        }

        .invoice-sheet.terms-sheet {
            margin-top: 24px;
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
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-btn-bar {
                display: none !important;
            }
            .page-num {
                display: none !important;
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
            }
            .invoice-sheet.terms-sheet {
                page-break-before: always !important;
                break-before: page !important;
                page-break-after: auto !important;
                break-after: auto !important;
                margin: 0 auto !important;
            }
            .party-head.party-right, .party-body.party-right {
                border-right: none !important;
            }
            .th-item:last-child, .td-item:last-child {
                border-right: none !important;
            }
            .inv-main-tbl tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <div class="print-btn-bar">
        <a href="{{ route('quotations.show', $quotation->id) }}" class="p-btn p-btn-back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            <span>Back to Quotation</span>
        </a>

        <div class="print-info-tag">
            <span>Estimate #<strong>{{ $quotation->quotation_no }}</strong></span>
            <span style="color:#cbd5e1;">•</span>
            <span class="print-hint">Tip: Select <em>Save as PDF</em> in print destination</span>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <button onclick="window.print()" class="p-btn p-btn-print" style="padding: 8px 18px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <span>Save as PDF / Print</span>
                <span style="font-size:10px; opacity:0.85; padding:2px 6px; background:rgba(255,255,255,0.22); border-radius:4px; font-weight:800;">Ctrl+P</span>
            </button>
        </div>
    </div>

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
        $isTNState = !empty($quotation->customer_state) ? (str_contains(strtolower($quotation->customer_state), 'tamil nadu') || str_contains($quotation->customer_state, '33')) : true;

        // Terms Content for Page 2 strictly from Quotation Setting
        $termsBody = !empty(trim((string) ($quoteSetting['terms'] ?? ''))) ? $quoteSetting['terms'] : '';
        $isHtmlBody = strip_tags($termsBody) !== $termsBody;
    @endphp

    <div class="invoice-sheet">

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
                        <div>India</div>
                        @if(!empty($companyGstin))
                            <div style="margin-top:2px;">
                                <strong>GSTIN</strong> {{ $companyGstin }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="inv-tax-title">
                ESTIMATE
            </div>
        </div>

        {{-- 2. Metadata Grid (Exact 50% / 50% split) --}}
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
                        <td class="meta-val">{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d/m/Y') : '—' }}</td>
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
                        <td class="meta-val">{{ $salesPerson }}</td>
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
                        <div class="party-name">{{ $customerName }}</div>
                        @if($quotation->lead?->company_name && $quotation->lead?->company_name !== $quotation->lead?->contact_name)
                            <div style="font-weight:700; color:#334155; margin-bottom:2px;">{{ $quotation->lead->contact_name }}</div>
                        @endif
                        @if($quotation->bill_to_address)
                            <div>{!! nl2br(e($quotation->bill_to_address)) !!}</div>
                        @elseif($quotation->lead?->address)
                            <div>{!! nl2br(e($quotation->lead->address)) !!}</div>
                        @endif
                        @if($quotation->gst_number)
                            <div style="margin-top:4px;"><strong>GSTIN</strong> {{ $quotation->gst_number }}</div>
                        @endif
                    </td>
                    <td colspan="4" class="party-body party-right col-border-left">
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
                @foreach($quotation->items as $index => $item)
                <tr>
                    <td class="td-item" style="text-align:center; font-weight:700;">{{ $index + 1 }}</td>
                    <td class="td-item">
                        <div style="font-weight:800; color:#000;">{{ $item->product?->product_name ?? ($item->item_name ?? '—') }}</div>
                        @if(!empty($item->description))
                            <div style="font-size:10.5px; color:#334155; margin-top:2px; line-height:1.4;">
                                {!! nl2br(e($item->description)) !!}
                            </div>
                        @endif
                    </td>
                    <td class="td-item" style="text-align:center;">{{ $item->hsn_sac_code ?? ($item->product?->hsn_code ?: '—') }}</td>
                    <td class="td-item" style="text-align:center;">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="td-item" style="text-align:right;">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="td-item" style="text-align:right;">{{ number_format((float) $item->total, 2) }}</td>
                </tr>
                @endforeach

                {{-- Row N+1: Bottom Section (Left: Words, Notes, Bank | Right: Totals & Signature) --}}
                <tr>
                    {{-- Left Side: colspan="3" (5% + 45% + 10% = 60% width -> up to HSN/SAC right border) --}}
                    <td colspan="3" class="bottom-left-cell">
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
                            </div>
                        </div>
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
                                    <td class="t-val">{{ number_format($subtotal, 2) }}</td>
                                </tr>
                                @if($cgstAmount > 0 || $sgstAmount > 0 || ($isTNState && $igstAmount == 0))
                                    <tr>
                                        <td class="t-label">CGST ({{ $cgstRate }}%)</td>
                                        <td class="t-val">{{ number_format($cgstAmount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="t-label">SGST ({{ $sgstRate }}%)</td>
                                        <td class="t-val">{{ number_format($sgstAmount, 2) }}</td>
                                    </tr>
                                @endif
                                @if($igstAmount > 0 || (!$isTNState && $cgstAmount == 0))
                                    <tr>
                                        <td class="t-label">IGST ({{ $igstRate }}%)</td>
                                        <td class="t-val">{{ number_format($igstAmount, 2) }}</td>
                                    </tr>
                                @endif
                                @if($rounding != 0)
                                    <tr>
                                        <td class="t-label">Round Off</td>
                                        <td class="t-val">{{ $rounding > 0 ? '(+) ' : '(-) ' }}{{ number_format(abs($rounding), 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="total-row">
                                    <td class="t-label">Total</td>
                                    <td class="t-val">₹{{ number_format($totalAmount, 2) }}</td>
                                </tr>
                            </table>

                            <div class="sig-container">
                                <div class="sig-for">FOR {{ strtoupper($companyName) }}</div>
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

    <div class="page-num">Page 1 of {{ !empty(trim($termsBody)) ? '2' : '1' }}</div>

    @if(!empty(trim($termsBody)))
    {{-- DEDICATED PAGE 2: TERMS & CONDITIONS ONLY --}}
    <div class="invoice-sheet terms-sheet" style="padding: 32px 36px;">
        <div style="font-size: 18px; font-weight: 800; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 12px; border-bottom: 1px solid #000000; margin-bottom: 22px;">
            Terms &amp; Conditions
        </div>

        <div class="terms-page-content" style="font-size: 11px; line-height: 1.7; color: #0f172a;">
            @if($isHtmlBody)
                {!! $termsBody !!}
            @else
                {!! nl2br(e($termsBody)) !!}
            @endif
        </div>
    </div>

    <div class="page-num">Page 2 of 2</div>
    @endif

    <script>
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });

    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') === '1' || urlParams.get('print') === '1') {
            setTimeout(function() {
                window.print();
            }, 350);
        }
    });
    </script>
</body>
</html>
