<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 16mm 18mm 16mm 18mm;
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
        }

        /* Top Header Table */
        .header-tbl {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
            table-layout: fixed;
        }
        .header-tbl td {
            vertical-align: middle;
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
            font-size: 10.5px;
            color: #000000;
            line-height: 1.35;
        }
        .inv-tax-title {
            font-size: 19px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.5px;
            text-align: right;
            vertical-align: middle;
            white-space: nowrap;
            margin: 0;
            padding: 0;
        }

        /* 2-Column Metadata Table */
        .meta-tbl-wrap {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
            table-layout: fixed;
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

        /* Parties Header & Body (Dedicated 50/50 Table) */
        .parties-tbl {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
            table-layout: fixed;
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
            font-size: 10.5px;
            line-height: 1.4;
            color: #000000;
            vertical-align: top;
            height: 98px;
        }
        .party-name {
            font-weight: bold;
            color: #000000;
            margin-bottom: 2px;
        }

        /* Items Table */
        .items-tbl {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5px;
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
            font-size: 10.5px;
        }
        .td-item:last-child {
            border-right: none;
        }

        /* Bottom Summary Table */
        .summary-tbl {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10.5px;
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
            font-size: 10.5px;
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
            font-size: 11.5px;
            font-weight: bold;
            color: #000000;
            padding-top: 5px;
            padding-bottom: 5px;
        }
        .totals-tbl .balance-row td {
            font-size: 12px;
            font-weight: bold;
            color: #000000;
            border-top: 1px solid #000000;
            padding-top: 6px;
            padding-bottom: 6px;
        }

        .sig-container {
            padding: 12px 10px 10px 10px;
            text-align: center;
        }
        .sig-for {
            font-size: 10px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
        }
        .sig-image {
            max-height: 52px;
            max-width: 145px;
            margin: 5px auto;
        }
        .sig-auth {
            font-size: 10.5px;
            font-weight: bold;
            color: #000000;
        }

        /* Terms & Conditions Sheet (Page 2) */
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
    $sub = (float) $invoice->subtotal;
    $cgstPct = ($sub > 0 && $invoice->total_cgst > 0) ? round(($invoice->total_cgst / $sub) * 100, 2) : 9;
    $sgstPct = ($sub > 0 && $invoice->total_sgst > 0) ? round(($invoice->total_sgst / $sub) * 100, 2) : 9;
    $igstPct = ($sub > 0 && $invoice->total_igst > 0) ? round(($invoice->total_igst / $sub) * 100, 2) : 18;
    $isTNState = !empty($invoice->place_of_supply) ? (str_contains(strtolower($invoice->place_of_supply), 'tamil nadu') || str_contains($invoice->place_of_supply, '33')) : true;

    $pos = $invoice->place_of_supply ?: '[33] Tamil Nadu';
    if (preg_match('/\[(\d+)\]\s*(.*)/', $pos, $matches)) {
        $formattedPos = trim($matches[2]) . ' (' . $matches[1] . ')';
    } else {
        $formattedPos = $pos;
    }

    $logoBase64 = null;
    $logoFile = null;
    if ($setting?->logo_path && file_exists(public_path($setting->logo_path))) {
        $logoFile = public_path($setting->logo_path);
    } elseif (file_exists(public_path('images/LOGO_STS.png'))) {
        $logoFile = public_path('images/LOGO_STS.png');
    }
    if ($logoFile) {
        $ext = pathinfo($logoFile, PATHINFO_EXTENSION);
        $logoBase64 = 'data:image/' . ($ext ?: 'png') . ';base64,' . base64_encode(file_get_contents($logoFile));
    }

    $signatureBase64 = null;
    $sigFile = null;
    if ($setting?->signature_path && file_exists(public_path($setting->signature_path))) {
        $sigFile = public_path($setting->signature_path);
    } elseif (file_exists(public_path('images/sign.jpeg'))) {
        $sigFile = public_path('images/sign.jpeg');
    }
    if ($sigFile) {
        $ext = pathinfo($sigFile, PATHINFO_EXTENSION);
        $signatureBase64 = 'data:image/' . ($ext ?: 'jpeg') . ';base64,' . base64_encode(file_get_contents($sigFile));
    }
@endphp

<div class="invoice-sheet">

    {{-- 1. Top Header --}}
    <table class="header-tbl">
        <tr>
            <td style="width: 60%; padding: 12px 10px 10px 16px; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        @if($logoBase64)
                            <td style="width: 115px; vertical-align: middle; padding-right: 14px;">
                                <img src="{{ $logoBase64 }}" alt="Logo" class="inv-logo">
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
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
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; text-align: right; padding: 12px 16px 10px 6px; vertical-align: middle;">
                <div class="inv-tax-title">TAX INVOICE</div>
            </td>
        </tr>
    </table>

    {{-- 2. Invoice Metadata Grid --}}
    <table class="meta-tbl-wrap">
        <tr>
            <td class="meta-col-cell">
                <table class="meta-inner-tbl">
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
            </td>
            <td class="meta-col-cell border-left">
                <table class="meta-inner-tbl">
                    <tr>
                        <td class="meta-label">Place Of Supply</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val">{{ $formattedPos }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Sales person</td>
                        <td class="meta-colon">:</td>
                        <td class="meta-val">{{ $invoice->salesPerson?->name ?? ($invoice->creator?->name ?? '—') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- 3. Bill To & Ship To Grid (Strict 50/50 Equal Proportion) --}}
    <table class="parties-tbl">
        <tr>
            <th class="party-head" style="width: 50%;">Bill To</th>
            <th class="party-head col-border-left" style="width: 50%;">Ship To</th>
        </tr>
        <tr>
            <td class="party-body" style="width: 50%;">
                <div class="party-name">{{ $invoice->customer_name }}</div>
                @if($invoice->company_name && $invoice->company_name !== $invoice->customer_name)
                    <div style="font-weight:bold; color:#334155; margin-bottom:2px;">{{ $invoice->company_name }}</div>
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
                @php
                    $billingGstin = $invoice->billing_gstin ?: $invoice->customer_gstin;
                    $shippingGstin = $invoice->shipping_gstin ?: ($invoice->billing_gstin ?: $invoice->customer_gstin);
                @endphp
                @if($billingGstin)
                    <div style="margin-top:3px;"><strong>GSTIN</strong> {{ $billingGstin }}</div>
                @endif
            </td>
            <td class="party-body col-border-left" style="width: 50%;">
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
                    <div style="margin-top:3px;"><strong>GSTIN</strong> {{ $shippingGstin }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- 4. Line Items Table (Generous 45% for Description, Exact Columns) --}}
    <table class="items-tbl">
        <tr>
            <th class="th-item" style="width: 5%; text-align:center;">#</th>
            <th class="th-item" style="width: 45%; text-align:left;">Item &amp; Description</th>
            <th class="th-item" style="width: 11%; text-align:center;">HSN/SAC</th>
            <th class="th-item" style="width: 7%; text-align:center;">Qty</th>
            <th class="th-item" style="width: 16%; text-align:right;">Rate</th>
            <th class="th-item" style="width: 16%; text-align:right;">Amount</th>
        </tr>
        @foreach($invoice->items as $index => $item)
        <tr>
            <td class="td-item" style="text-align:center; font-weight:bold;">{{ $index + 1 }}</td>
            <td class="td-item" style="text-align:left;">
                <div style="font-weight:bold; color:#000;">{{ $item->item_name }}</div>
                @if($item->description)
                    <div style="font-size:9.5px; color:#334155; margin-top:2px; line-height:1.35;">
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
    </table>

    {{-- 5. Bottom Section: Total in Words, Notes, Bank (Left: 58%) & Totals, Signature (Right: 42%) --}}
    <table class="summary-tbl">
        <tr>
            <td class="bottom-left-cell" style="width: 58%;">
                <div>
                    <div class="sec-head">Total In Words</div>
                    <div style="font-size:10.5px; font-weight:bold; font-style:italic; color:#000; margin-top:2px;">
                        {{ $invoice->amount_in_words }}
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <div class="sec-head">Notes</div>
                    <div class="sec-body">
                        {{ $setting?->notes ?: ($invoice->customer_notes ?: 'Thanks for your business.') }}
                    </div>
                </div>

                <div style="margin-top:10px;">
                    <div style="font-size:10px; line-height:1.55; color:#000;">
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
            </td>

            <td class="bottom-right-cell" style="width: 42%;">
                <table class="totals-tbl">
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
                        <td class="t-val">&#8377;{{ number_format((float) $invoice->total_amount, 2) }}</td>
                    </tr>
                    @if((float) $invoice->total_credited > 0)
                        <tr>
                            <td class="t-label">Credits Applied (CN)</td>
                            <td class="t-val" style="color:#b91c1c; font-weight:bold;">
                                (-) {{ number_format((float) $invoice->total_credited, 2) }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="t-label">Payment Made</td>
                        <td class="t-val" style="color:{{ $invoice->paid_amount > 0 ? '#b91c1c' : '#000000' }}; font-weight:bold;">
                            (-) {{ number_format((float) $invoice->paid_amount, 2) }}
                        </td>
                    </tr>
                    @if((float) $invoice->total_tds > 0)
                        <tr>
                            <td class="t-label">Amount Withheld</td>
                            <td class="t-val" style="color:#b91c1c; font-weight:bold;">
                                (-) {{ number_format((float) $invoice->total_tds, 2) }}
                            </td>
                        </tr>
                    @endif
                    <tr class="balance-row">
                        <td class="t-label">Balance Due</td>
                        <td class="t-val">&#8377;{{ number_format((float) $invoice->balance_due, 2) }}</td>
                    </tr>
                </table>

                <div class="sig-container">
                    <div class="sig-for">{{ $setting?->signatory_name ?: ('FOR ' . strtoupper($setting?->company_name ?: 'Sai Techno Solutions Private Limited')) }}</div>
                    @if($signatureBase64)
                        <img src="{{ $signatureBase64 }}" alt="Signature" class="sig-image">
                    @else
                        <div style="height:52px;"></div>
                    @endif
                    <div class="sig-auth">Authorized Signature</div>
                </div>
            </td>
        </tr>
    </table>

</div>

{{-- PAGE BREAK FOR DEFAULT DEDICATED 2ND PAGE --}}
<div style="clear: both; page-break-before: always;"></div>

{{-- DEDICATED PAGE 2: TERMS & CONDITIONS ONLY (Full Width, Left Aligned) --}}
<div class="invoice-sheet" style="clear: both; width: 100%; border: 1px solid #000000;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 24px 28px; vertical-align: top; text-align: left;">
                <div style="font-size: 16px; font-weight: bold; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 10px; border-bottom: 1px solid #000000; margin-bottom: 18px; text-align: left;">
                    Terms &amp; Conditions
                </div>

                @php
                    $termsBody = !empty(trim((string) ($setting?->terms_conditions ?? '')))
                        ? $setting->terms_conditions
                        : (!empty(trim((string) ($invoice->terms_conditions ?? ''))) ? $invoice->terms_conditions : \App\Models\InvoiceSetting::defaultTerms());
                    $isHtmlBody = strip_tags($termsBody) !== $termsBody;
                @endphp

                <div class="terms-page-content" style="font-size: 11px; line-height: 1.7; color: #000000; text-align: left;">
                    @if($isHtmlBody)
                        {!! $termsBody !!}
                    @else
                        {!! nl2br(e($termsBody)) !!}
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
