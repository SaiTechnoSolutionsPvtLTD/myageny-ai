<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Estimate #{{ $quotation->quotation_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 14mm 16mm 14mm 16mm;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #000000;
            font-size: 10.5px;
            line-height: 1.35;
        }

        .invoice-sheet {
            width: 100%;
            border: 1px solid #000000;
            background: #ffffff;
            border-collapse: collapse;
        }

        /* Top Header Table */
        .header-tbl {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
        }
        .header-tbl td {
            vertical-align: middle;
            padding: 14px 18px 12px 18px;
        }
        .inv-logo {
            height: 68px;
            max-width: 135px;
        }
        .inv-comp-title {
            font-size: 14px;
            font-weight: bold;
            color: #000000;
            line-height: 1.25;
            margin-bottom: 3px;
        }
        .inv-comp-address {
            font-size: 10px;
            color: #000000;
            line-height: 1.35;
        }
        .inv-estimate-title {
            font-size: 24px;
            font-weight: 900;
            color: #000000;
            letter-spacing: 0.5px;
            text-align: right;
            vertical-align: middle;
            white-space: nowrap;
        }

        /* 2-Column Metadata Table (Exact 50% / 50% split) */
        .meta-tbl-wrap {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
        }
        .meta-col-cell {
            width: 50%;
            vertical-align: top;
            padding: 6px 14px;
        }
        .meta-col-cell.border-left {
            border-left: 1px solid #000000;
        }
        .meta-inner-tbl {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-inner-tbl td {
            padding: 2.5px 0;
            font-size: 10.5px;
            vertical-align: top;
        }
        .meta-label {
            width: 110px;
            color: #000000;
        }
        .meta-colon {
            width: 14px;
            color: #000000;
            text-align: left;
        }
        .meta-val {
            font-weight: bold;
            color: #000000;
        }

        /* Parties Table (Exact 50% / 50% split) */
        .parties-tbl-wrap {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
        }
        .party-head {
            background: #f3f4f6;
            color: #000000;
            font-size: 10.5px;
            font-weight: bold;
            padding: 5px 12px;
            text-align: left;
            border-bottom: 1px solid #000000;
        }
        .col-border-left {
            border-left: 1px solid #000000;
        }
        .party-body {
            padding: 8px 12px;
            font-size: 10px;
            line-height: 1.4;
            color: #000000;
            vertical-align: top;
            height: 95px;
        }
        .party-name {
            font-weight: bold;
            color: #000000;
            margin-bottom: 2px;
        }

        /* Line Items Table without table-layout:fixed so width attributes set the exact columns */
        .inv-items-tbl {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
        }
        .th-item {
            background: #f3f4f6;
            color: #000000;
            font-weight: bold;
            font-size: 10.5px;
            border-bottom: 1px solid #000000;
            border-right: 1px solid #000000;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .th-item:last-child {
            border-right: none;
        }
        .td-item {
            padding: 7px 8px;
            vertical-align: top;
            border-right: 1px solid #000000;
            border-bottom: 1px solid #000000;
            color: #000000;
            font-size: 10px;
        }
        .td-item:last-child {
            border-right: none;
        }
        .inv-items-tbl tbody tr:last-child td {
            border-bottom: none;
        }

        /* Bottom Section Table */
        .inv-bottom-tbl {
            width: 100%;
            border-collapse: collapse;
        }
        .bottom-left-cell {
            border-right: 1px solid #000000;
            padding: 12px 14px;
            vertical-align: top;
        }
        .bottom-right-cell {
            padding: 0;
            vertical-align: top;
        }

        .sec-head {
            font-size: 10.5px;
            font-weight: bold;
            color: #000000;
        }
        .sec-body {
            font-size: 10px;
            color: #000000;
            line-height: 1.45;
            margin-top: 2px;
        }

        /* Totals Table */
        .totals-tbl {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
        }
        .totals-tbl td {
            padding: 4px 10px;
            vertical-align: middle;
            font-size: 10px;
        }
        .totals-tbl .t-label {
            text-align: right;
            color: #000000;
            width: 58%;
        }
        .totals-tbl .t-val {
            text-align: right;
            font-weight: 600;
            color: #000000;
            width: 42%;
        }
        .totals-tbl .total-row td {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
            border-top: 1px solid #000000;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .sig-container {
            padding: 10px 10px 8px 10px;
            text-align: center;
        }
        .sig-for {
            font-size: 9.5px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
        }
        .sig-image {
            max-height: 48px;
            max-width: 140px;
            margin: 4px auto;
        }
        .sig-auth {
            font-size: 10px;
            font-weight: bold;
            color: #000000;
        }

        .terms-page-content p { margin: 0 0 8px 0; }
        .terms-page-content ol, .terms-page-content ul { margin: 0 0 10px 0; padding-left: 20px; }
        .terms-page-content li { margin-bottom: 6px; line-height: 1.55; }
        .terms-page-content h1, .terms-page-content h2, .terms-page-content h3, .terms-page-content h4 { margin: 10px 0 4px 0; color: #000000; }
        .terms-page-content table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .terms-page-content th, .terms-page-content td { border: 1px solid #000000; padding: 4px 6px; }
    </style>
</head>
<body>
    <script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 8.5;
        $color = array(0.39, 0.45, 0.55);
        $pdf->page_text($pdf->get_width() - 85, $pdf->get_height() - 25, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, $size, $color);
    }
    </script>

@php
    // Quotation Settings (Company, Branding, Bank & Terms) strictly for this company
    $companyName = !empty($quoteSetting['company_name']) ? $quoteSetting['company_name'] : ($quotation->company?->company_name ?? 'Company');
    $companyAddress = !empty($quoteSetting['company_address']) ? $quoteSetting['company_address'] : ($quotation->company?->address ?? '');
    $companyPhone = !empty($quoteSetting['company_phone']) ? $quoteSetting['company_phone'] : ($quotation->company?->mobile_number ?? '');
    $companyEmail = !empty($quoteSetting['company_email']) ? $quoteSetting['company_email'] : ($quotation->company?->email ?? '');
    $companyGstin = !empty($quoteSetting['company_gstin']) ? $quoteSetting['company_gstin'] : '';

    // Logo Resolution strictly from Quotation Setting
    $logoBase64 = null;
    $logoFile = null;
    if (!empty($quoteSetting['logo']) && file_exists(public_path($quoteSetting['logo']))) {
        $logoFile = public_path($quoteSetting['logo']);
    }
    if ($logoFile) {
        $ext = pathinfo($logoFile, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . ($ext ?: 'png') . ';base64,' . base64_encode(file_get_contents($logoFile));
    }

    // Signature Resolution strictly from Quotation Setting
    $signatureBase64 = null;
    $sigFile = null;
    if (!empty($quoteSetting['signature']) && file_exists(public_path($quoteSetting['signature']))) {
        $sigFile = public_path($quoteSetting['signature']);
    }
    if ($sigFile) {
        $ext = pathinfo($sigFile, PATHINFO_EXTENSION);
        $signatureBase64 = 'data:image/' . ($ext ?: 'jpeg') . ';base64,' . base64_encode(file_get_contents($sigFile));
    }

    // Place of Supply with State Code
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

    // Sales Person
    $salesPerson = $quotation->lead?->assignedTo?->name
        ?: ($quotation->createdBy?->name ?: ($quotation->lead?->createdBy?->name ?: '—'));

    // Customer Name
    $customerName = $quotation->lead?->company_name
        ?: ($quotation->lead?->contact_name ?: 'Customer');

    // Bank Details from Quotation Setting
    $bankName = !empty($quoteSetting['bank_name']) ? $quoteSetting['bank_name'] : '';
    $accountName = !empty($quoteSetting['account_name']) ? $quoteSetting['account_name'] : '';
    $accountNumber = !empty($quoteSetting['bank_account']) ? $quoteSetting['bank_account'] : '';
    $ifscCode = !empty($quoteSetting['bank_ifsc']) ? $quoteSetting['bank_ifsc'] : '';
    $branch = !empty($quoteSetting['bank_branch']) ? $quoteSetting['bank_branch'] : '';
    $upiId = !empty($quoteSetting['bank_upi']) ? $quoteSetting['bank_upi'] : '';

    // Amount in Words
    $amountInWords = \App\Models\Invoice::convertToIndianCurrencyWords((float) $quotation->total_amount);

    // Rounding & Totals Calculation
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
    $isHtmlTerms = strip_tags($termsBody) !== $termsBody;
@endphp

<div class="invoice-sheet">

    {{-- 1. Top Header --}}
    <table class="header-tbl">
        <tr>
            <td style="width: 64%; padding: 12px 10px 10px 16px; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        @if($logoBase64)
                            <td style="width: 110px; vertical-align: middle; padding-right: 14px;">
                                <img src="{{ $logoBase64 }}" alt="Logo" class="inv-logo">
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
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
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 36%; text-align: right; padding: 12px 20px 10px 6px; vertical-align: middle;">
                <div class="inv-estimate-title">ESTIMATE</div>
            </td>
        </tr>
    </table>

    {{-- 2. Metadata Grid (Exact 50% / 50% split) --}}
    <table class="meta-tbl-wrap">
        <tr>
            <td class="meta-col-cell" style="width: 50%;">
                <table class="meta-inner-tbl">
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
            </td>
            <td class="meta-col-cell border-left" style="width: 50%;">
                <table class="meta-inner-tbl">
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
            </td>
        </tr>
    </table>

    {{-- 3. Bill To / Ship To Grid (Exact 50% / 50% split matching top meta) --}}
    <table class="parties-tbl-wrap">
        <tr>
            <th class="party-head" style="width: 50%;">Bill To</th>
            <th class="party-head col-border-left" style="width: 50%;">Ship To</th>
        </tr>
        <tr>
            <td class="party-body" style="width: 50%;">
                <div class="party-name">{{ $customerName }}</div>
                @if($quotation->lead?->company_name && $quotation->lead?->contact_name && $quotation->lead?->company_name !== $quotation->lead?->contact_name)
                    <div style="font-weight:bold; color:#334155; margin-bottom:2px;">{{ $quotation->lead->company_name }}</div>
                @endif
                @if($quotation->bill_to_address)
                    <div>{!! nl2br(e($quotation->bill_to_address)) !!}</div>
                @elseif($quotation->lead?->address)
                    <div>{!! nl2br(e($quotation->lead->address)) !!}</div>
                @endif
                <div>India</div>
                @if($quotation->gst_number)
                    <div style="margin-top:3px;"><strong>GSTIN</strong> {{ $quotation->gst_number }}</div>
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
                    <div style="margin-top:3px;"><strong>GSTIN</strong> {{ $quotation->gst_number }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- 4. Line Items Grid (Col 1 + Col 2 = 50%, Col 3 + Col 4 + Col 5 + Col 6 = 50% matching exactly) --}}
    <table class="inv-items-tbl">
        <thead>
            <tr>
                <th class="th-item" width="5%" style="text-align:center;">#</th>
                <th class="th-item" width="45%" style="text-align:left;">Item &amp; Description</th>
                <th class="th-item" width="10%" style="text-align:center;">HSN/SAC</th>
                <th class="th-item" width="8%" style="text-align:center;">Qty</th>
                <th class="th-item" width="16%" style="text-align:right;">Rate</th>
                <th class="th-item" width="16%" style="text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
            <tr>
                <td class="td-item" width="5%" style="text-align:center; font-weight:bold;">{{ $index + 1 }}</td>
                <td class="td-item" width="45%">
                    <div style="font-weight:bold; color:#000;">{{ $item->product?->product_name ?? ($item->item_name ?? '—') }}</div>
                    @if(!empty($item->description))
                        <div style="font-size:9.5px; color:#334155; margin-top:2px; line-height:1.35;">
                            {!! nl2br(e($item->description)) !!}
                        </div>
                    @endif
                </td>
                <td class="td-item" width="10%" style="text-align:center;">{{ $item->hsn_sac_code ?? ($item->product?->hsn_code ?: '—') }}</td>
                <td class="td-item" width="8%" style="text-align:center;">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="td-item" width="16%" style="text-align:right;">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="td-item" width="16%" style="text-align:right;">{{ number_format((float) $item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 5. Bottom Section: Notes & Bank (60%) + Totals & Signature (40%) --}}
    <table class="inv-bottom-tbl">
        <tr>
            <td class="bottom-left-cell" width="60%">
                <div>
                    <div class="sec-head">Total In Words</div>
                    <div style="font-size:10.5px; font-weight:bold; font-style:italic; color:#000; margin-top:2px;">
                        {{ $amountInWords }}
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <div class="sec-head">Notes</div>
                    <div class="sec-body">
                        {{ $quotation->notes ?: 'Thanks for your business.' }}
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <div style="font-size:10px; line-height:1.55; color:#000;">
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

            <td class="bottom-right-cell" width="40%">
                <table class="totals-tbl">
                    <tr>
                        <td class="t-label">Sub Total</td>
                        <td class="t-val">{{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @if($cgstAmount > 0 || $sgstAmount > 0 || ($isTNState && $igstAmount == 0))
                        <tr>
                            <td class="t-label">CGST ({{ number_format($cgstRate, 0) }}%)</td>
                            <td class="t-val">{{ number_format($cgstAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="t-label">SGST ({{ number_format($sgstRate, 0) }}%)</td>
                            <td class="t-val">{{ number_format($sgstAmount, 2) }}</td>
                        </tr>
                    @endif
                    @if($igstAmount > 0 || (!$isTNState && $cgstAmount == 0))
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
                    @if($signatureBase64)
                        <img src="{{ $signatureBase64 }}" alt="Signature" class="sig-image">
                    @else
                        <div style="height:48px;"></div>
                    @endif
                    <div class="sig-auth">Authorized Signature</div>
                </div>
            </td>
        </tr>
    </table>

</div>

@if(!empty(trim($termsBody)))
{{-- DEDICATED PAGE 2: TERMS & CONDITIONS ONLY --}}
<div style="page-break-before: always;"></div>

<div class="invoice-sheet" style="margin-top: 0; padding: 24px 28px;">

    <div style="font-size: 16px; font-weight: bold; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 10px; border-bottom: 1px solid #000000; margin-bottom: 18px;">
        Terms &amp; Conditions
    </div>

    <div class="terms-page-content" style="font-size: 11px; line-height: 1.7; color: #000000;">
        @if($isHtmlTerms)
            {!! $termsBody !!}
        @else
            {!! nl2br(e($termsBody)) !!}
        @endif
    </div>

</div>
@endif

</body>
</html>
