<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice #{{ $invoice->invoice_number }}</title>
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
            max-width: 820px;
            margin: 0 auto;
        }

        @page {
            size: A4 portrait;
            margin: 16mm 16mm 16mm 16mm;
            @bottom-right {
                content: counter(page);
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                font-size: 10px;
                color: #64748b;
                font-weight: 500;
            }
        }
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .print-btn-bar {
                display: none !important;
            }
            .invoice-sheet {
                box-shadow: none !important;
                max-width: 100% !important;
                width: 100% !important;
                border: 1px solid #000000 !important;
                page-break-after: avoid;
                -webkit-box-decoration-break: clone !important;
                box-decoration-break: clone !important;
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
            .page-num {
                display: none !important;
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
        <a href="{{ route('accounts.sales.invoices.show', $invoice->id) }}" class="p-btn p-btn-back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            <span>Back to Invoice</span>
        </a>

        <div class="print-info-tag">
            <span>Invoice #<strong>{{ $invoice->invoice_number }}</strong></span>
            <span style="color:#cbd5e1;">•</span>
            <span class="print-hint">Tip: Select <em>Save as PDF</em> in print destination</span>
        </div>

        <button onclick="window.print()" class="p-btn p-btn-print">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            <span>Print / Save as PDF</span>
            <span style="font-size:10px; opacity:0.85; padding:2px 6px; background:rgba(255,255,255,0.22); border-radius:4px; font-weight:800;">Ctrl+P</span>
        </button>
    </div>

    @php
        $sub = (float) $invoice->subtotal;
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

    <div class="invoice-sheet">

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
                        <td class="meta-val">{{ $invoice->salesPerson?->name ?? ($invoice->creator?->name ?? '—') }}</td>
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
                <tr>
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

    <script>
    // Automatically open browser print preview on page load
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 400);
    });

    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });
    </script>
</body>
</html>
