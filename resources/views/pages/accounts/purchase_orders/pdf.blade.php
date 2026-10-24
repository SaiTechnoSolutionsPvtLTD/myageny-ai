<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order #{{ $purchaseOrder->po_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm 12mm 14mm;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #000000;
            font-size: 10px;
            line-height: 1.35;
        }

        .po-sheet {
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
            padding: 12px 14px;
        }
        .comp-title {
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 3px;
        }
        .comp-address {
            font-size: 9.5px;
            color: #000000;
            line-height: 1.35;
        }
        .po-title {
            font-size: 18px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.5px;
            text-align: right;
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
        .meta-tbl {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-tbl td {
            padding: 2px 0;
            font-size: 9.5px;
            vertical-align: top;
        }
        .meta-label {
            width: 110px;
            color: #000000;
        }
        .meta-colon {
            width: 12px;
            text-align: left;
        }
        .meta-val {
            font-weight: bold;
            color: #000000;
        }

        /* 2-Column Parties Table */
        .party-tbl-wrap {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
            table-layout: fixed;
        }
        .party-head-cell {
            width: 50%;
            background: #f3f4f6;
            font-size: 9.5px;
            font-weight: bold;
            padding: 5px 14px;
            border-bottom: 1px solid #000000;
        }
        .party-head-cell.border-left {
            border-left: 1px solid #000000;
        }
        .party-body-cell {
            width: 50%;
            vertical-align: top;
            padding: 8px 14px;
            font-size: 9.5px;
            line-height: 1.4;
            min-height: 80px;
        }
        .party-body-cell.border-left {
            border-left: 1px solid #000000;
        }
        .party-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        /* Line Items Table */
        .items-tbl {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-bottom: 1px solid #000000;
        }
        .items-tbl th {
            background: #f3f4f6;
            font-size: 9px;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #000000;
            border-right: 1px solid #000000;
        }
        .items-tbl th:last-child {
            border-right: none;
        }
        .items-tbl td {
            font-size: 9px;
            padding: 6px 8px;
            vertical-align: top;
            border-bottom: 1px solid #000000;
            border-right: 1px solid #000000;
        }
        .items-tbl td:last-child {
            border-right: none;
        }
        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .item-desc {
            color: #333333;
            font-size: 8.5px;
            line-height: 1.3;
        }

        /* Bottom Summary Section */
        .bottom-tbl {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .bottom-left {
            width: 55%;
            vertical-align: top;
            padding: 10px 14px;
            border-right: 1px solid #000000;
        }
        .bottom-right {
            width: 45%;
            vertical-align: top;
            padding: 0;
        }

        .sec-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .sec-content {
            font-size: 8.5px;
            line-height: 1.35;
            color: #111111;
            margin-bottom: 8px;
        }

        .totals-tbl {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1px solid #000000;
        }
        .totals-tbl td {
            padding: 4px 10px;
            font-size: 9.5px;
        }
        .totals-tbl .lbl {
            text-align: right;
        }
        .totals-tbl .val {
            text-align: right;
            width: 90px;
            font-weight: bold;
        }
        .totals-tbl tr.grand-row td {
            border-top: 1px solid #000000;
            font-size: 11px;
            font-weight: bold;
            background: #fafafa;
            padding: 6px 10px;
        }

        .signatory-wrap {
            padding: 12px 14px;
            text-align: right;
        }
        .signatory-label {
            font-size: 8.5px;
            font-weight: bold;
            margin-bottom: 35px;
        }
        .signatory-title {
            font-size: 8.5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php
        $companyName = $setting->company_name ?? config('app.name', 'MyAgenci');
        $addressLines = array_filter([
            $setting->address,
            $setting->city ? ($setting->city . ($setting->pincode ? ' - ' . $setting->pincode : '')) : null,
            $setting->state ? ($setting->state . ', India') : null,
        ]);
        $compGstin = $setting->gst_number ?? null;
    @endphp

    <div class="po-sheet">
        {{-- 1. Top Header --}}
        <table class="header-tbl">
            <tr>
                <td style="width: 60%;">
                    <div class="comp-title">{{ $companyName }}</div>
                    <div class="comp-address">
                        @foreach($addressLines as $line)
                            {{ $line }}<br>
                        @endforeach
                        @if($compGstin)
                            <strong>GSTIN:</strong> {{ $compGstin }}
                        @endif
                    </div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="po-title">PURCHASE ORDER</div>
                </td>
            </tr>
        </table>

        {{-- 2. Metadata 2-Column --}}
        <table class="meta-tbl-wrap">
            <tr>
                <td class="meta-col-cell">
                    <table class="meta-tbl">
                        <tr>
                            <td class="meta-label">PO Number</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $purchaseOrder->po_number }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">PO Date</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $purchaseOrder->po_date ? \Carbon\Carbon::parse($purchaseOrder->po_date)->format('d/m/Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Payment Terms</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $purchaseOrder->payment_terms ?: 'As Agreed' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Expected Delivery</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $purchaseOrder->delivery_date ? \Carbon\Carbon::parse($purchaseOrder->delivery_date)->format('d/m/Y') : '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td class="meta-col-cell border-left">
                    <table class="meta-tbl">
                        <tr>
                            <td class="meta-label">Place Of Supply</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $purchaseOrder->place_of_supply ?: ($setting->state ?? 'Tamil Nadu (33)') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Delivery Terms</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $purchaseOrder->delivery_terms ?: 'Free Door Delivery at Company Site' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Issued By</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ $purchaseOrder->creator?->name ?? 'Accounts Department' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Order Status</td>
                            <td class="meta-colon">:</td>
                            <td class="meta-val">{{ ucfirst($purchaseOrder->status ?? 'Issued') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- 3. Party Details (Vendor vs Shipping Destination) --}}
        <table class="party-tbl-wrap">
            <tr>
                <td class="party-head-cell">Vendor (Supplier)</td>
                <td class="party-head-cell border-left">Ship To / Delivery Destination</td>
            </tr>
            <tr>
                <td class="party-body-cell">
                    <div class="party-name">{{ $purchaseOrder->supplier_name }}</div>
                    @if($purchaseOrder->company_name)
                        <div>{{ $purchaseOrder->company_name }}</div>
                    @endif
                    @if($purchaseOrder->supplier_address)
                        <div>{{ $purchaseOrder->supplier_address }}</div>
                    @endif
                    @if($purchaseOrder->supplier_city || $purchaseOrder->supplier_state || $purchaseOrder->supplier_pincode)
                        <div>{{ implode(', ', array_filter([$purchaseOrder->supplier_city, $purchaseOrder->supplier_state, $purchaseOrder->supplier_pincode])) }}</div>
                    @endif
                    @if($purchaseOrder->supplier_phone)
                        <div>Phone: {{ $purchaseOrder->supplier_phone }}</div>
                    @endif
                    @if($purchaseOrder->supplier_email)
                        <div>Email: {{ $purchaseOrder->supplier_email }}</div>
                    @endif
                    @if($purchaseOrder->supplier_gstin)
                        <div><strong>GSTIN:</strong> {{ $purchaseOrder->supplier_gstin }}</div>
                    @endif
                    @if($purchaseOrder->supplier_pan)
                        <div><strong>PAN:</strong> {{ $purchaseOrder->supplier_pan }}</div>
                    @endif
                </td>
                <td class="party-body-cell border-left">
                    <div class="party-name">{{ $purchaseOrder->shipping_attention ?: $companyName }}</div>
                    <div>{{ $purchaseOrder->shipping_address ?: ($setting->address ?? '') }}</div>
                    <div>{{ implode(', ', array_filter([
                        $purchaseOrder->shipping_city ?: ($setting->city ?? ''),
                        $purchaseOrder->shipping_state ?: ($setting->state ?? ''),
                        $purchaseOrder->shipping_pincode ?: ($setting->pincode ?? '')
                    ])) }}</div>
                    <div>India</div>
                    @if($purchaseOrder->delivery_terms)
                        <div style="margin-top: 4px;"><strong>Terms:</strong> {{ $purchaseOrder->delivery_terms }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- 4. Line Items Table --}}
        <table class="items-tbl">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">#</th>
                    <th style="width: 47%;">Item & Description</th>
                    <th style="width: 14%; text-align: center;">HSN/SAC</th>
                    <th style="width: 10%; text-align: right;">Qty</th>
                    <th style="width: 11%; text-align: right;">Rate</th>
                    <th style="width: 13%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrder->items as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->item_name }}</div>
                            @if($item->description)
                                <div class="item-desc">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $item->hsn_sac_code ?: '-' }}</td>
                        <td style="text-align: right;">{{ number_format((float) $item->qty, 2) }}</td>
                        <td style="text-align: right;">{{ number_format((float) $item->rate, 2) }}</td>
                        <td style="text-align: right;">{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 12px;">No items in this purchase order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- 5. Bottom Summary Section --}}
        <table class="bottom-tbl">
            <tr>
                <td class="bottom-left">
                    <div class="sec-title">Total In Words</div>
                    <div class="sec-content" style="font-style: italic; font-weight: bold;">
                        {{ $purchaseOrder->amount_in_words ?? '' }}
                    </div>

                    @if($purchaseOrder->terms_conditions)
                        <div class="sec-title" style="margin-top: 10px;">Terms & Conditions</div>
                        <div class="sec-content" style="white-space: pre-line;">
                            {{ $purchaseOrder->terms_conditions }}
                        </div>
                    @endif

                    @if($purchaseOrder->notes)
                        <div class="sec-title" style="margin-top: 10px;">Special Instructions / Notes</div>
                        <div class="sec-content">
                            {{ $purchaseOrder->notes }}
                        </div>
                    @endif
                </td>
                <td class="bottom-right">
                    <table class="totals-tbl">
                        <tr>
                            <td class="lbl">Sub Total</td>
                            <td class="val">{{ number_format((float) $purchaseOrder->subtotal, 2) }}</td>
                        </tr>
                        @if((float) $purchaseOrder->total_cgst > 0)
                            <tr>
                                <td class="lbl">CGST ({{ number_format((float) ($purchaseOrder->items->first()?->cgst_percent ?? 9), 0) }}%)</td>
                                <td class="val">{{ number_format((float) $purchaseOrder->total_cgst, 2) }}</td>
                            </tr>
                        @endif
                        @if((float) $purchaseOrder->total_sgst > 0)
                            <tr>
                                <td class="lbl">SGST ({{ number_format((float) ($purchaseOrder->items->first()?->sgst_percent ?? 9), 0) }}%)</td>
                                <td class="val">{{ number_format((float) $purchaseOrder->total_sgst, 2) }}</td>
                            </tr>
                        @endif
                        @if((float) $purchaseOrder->total_igst > 0)
                            <tr>
                                <td class="lbl">IGST ({{ number_format((float) ($purchaseOrder->items->first()?->igst_percent ?? 18), 0) }}%)</td>
                                <td class="val">{{ number_format((float) $purchaseOrder->total_igst, 2) }}</td>
                            </tr>
                        @endif
                        @if((float) $purchaseOrder->round_off != 0)
                            <tr>
                                <td class="lbl">Round Off</td>
                                <td class="val">{{ ((float) $purchaseOrder->round_off > 0 ? '+' : '') . number_format((float) $purchaseOrder->round_off, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="grand-row">
                            <td class="lbl">Total PO Value</td>
                            <td class="val">₹{{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
                        </tr>
                    </table>

                    <div class="signatory-wrap">
                        <div class="signatory-label">FOR {{ strtoupper($companyName) }}</div>
                        <div class="signatory-title">Authorized Signatory</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
