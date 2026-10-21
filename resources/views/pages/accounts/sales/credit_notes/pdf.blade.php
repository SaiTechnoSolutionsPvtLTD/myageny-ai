<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Credit Note #{{ $creditNote->credit_note_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm 12mm 14mm;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #000000;
            font-size: 10.5px;
            line-height: 1.4;
        }
        .cn-sheet {
            width: 100%;
            border: 1px solid #000000;
        }
        table {
            border-collapse: collapse;
        }
        .tbl-header {
            width: 100%;
            border-bottom: 1px solid #000000;
        }
        .tbl-header td {
            vertical-align: top;
            padding: 14px 16px 12px;
        }
        .badge-title {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.08em;
            color: #b91c1c;
            text-transform: uppercase;
        }
        .tbl-meta {
            margin-top: 8px;
            font-size: 11px;
            margin-left: auto;
        }
        .tbl-meta td {
            padding: 2px 4px !important;
        }
        .tbl-grid-2 {
            width: 100%;
            border-bottom: 1px solid #000000;
        }
        .tbl-grid-2 td {
            vertical-align: top;
            padding: 10px 14px;
        }
        .tbl-items {
            width: 100%;
            border-collapse: collapse;
        }
        .tbl-items th, .tbl-items td {
            border-right: 1px solid #000000;
            padding: 7px 10px;
        }
        .tbl-items th:last-child, .tbl-items td:last-child {
            border-right: none;
        }
        .tbl-items th {
            background: #f3f4f6;
            border-bottom: 1px solid #000000;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .tbl-items tr {
            border-bottom: 1px solid #000000;
        }
        .tbl-footer {
            width: 100%;
        }
        .tbl-footer td {
            vertical-align: top;
            padding: 12px 14px;
        }
        .totals-tbl {
            width: 100%;
            font-size: 11px;
        }
        .totals-tbl td {
            padding: 3px 0;
        }
        .total-highlight {
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
        }
        .total-highlight td {
            padding: 6px 0;
        }
    </style>
</head>
<body>
    <div class="cn-sheet">
        {{-- 1. HEADER --}}
        <table class="tbl-header">
            <tr>
                <td style="width: 58%;">
                    @if($setting?->logo_url)
                        <img src="{{ $setting->logo_url }}" alt="Logo" style="max-height: 48px; max-width: 160px; margin-bottom: 6px;">
                    @endif
                    <div style="font-size: 14px; font-weight: bold; color: #000000;">
                        {{ $setting?->company_name ?: 'Sai Techno Solutions Private Limited' }}
                    </div>
                    <div style="font-size: 10.5px; color: #333333; max-width: 360px; line-height: 1.35; margin-top: 2px;">
                        {{ $setting?->full_address ?: 'Plot No 8, Sai Nagar, Thoraipakkam, Chennai - 600097' }}
                    </div>
                    @if($setting?->company_gstin)
                        <div style="font-size: 10.5px; font-weight: bold; color: #000000; margin-top: 3px;">
                            GSTIN: {{ $setting->company_gstin }}
                        </div>
                    @endif
                </td>

                <td style="width: 42%; text-align: right;">
                    <div class="badge-title">CREDIT NOTE</div>
                    <div style="font-size: 9.5px; color: #555555; margin-top: 2px;">(Original for Recipient)</div>

                    <table class="tbl-meta" align="right">
                        <tr>
                            <td style="font-weight: bold; text-align: right;">Credit Note #</td>
                            <td style="font-weight: bold; text-align: center;">:</td>
                            <td style="font-weight: bold; font-family: monospace; color: #b91c1c; text-align: left;">{{ $creditNote->credit_note_number }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; text-align: right;">Credit Note Date</td>
                            <td style="font-weight: bold; text-align: center;">:</td>
                            <td style="font-weight: bold; text-align: left;">{{ $creditNote->credit_note_date ? $creditNote->credit_note_date->format('d/m/Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; text-align: right;">Against Invoice #</td>
                            <td style="font-weight: bold; text-align: center;">:</td>
                            <td style="font-weight: bold; font-family: monospace; color: #000000; text-align: left;">{{ $creditNote->invoice?->invoice_number ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; text-align: right;">Original Inv. Date</td>
                            <td style="font-weight: bold; text-align: center;">:</td>
                            <td style="text-align: left;">{{ $creditNote->invoice?->invoice_date ? $creditNote->invoice->invoice_date->format('d/m/Y') : '—' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- 2. CUSTOMER & REASON GRID --}}
        <table class="tbl-grid-2">
            <tr>
                <td style="width: 50%; border-right: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: bold; text-transform: uppercase; color: #555555; margin-bottom: 3px;">
                        Customer Details (Credited To)
                    </div>
                    <div style="font-size: 12.5px; font-weight: bold; color: #000000;">
                        {{ $creditNote->customer?->customer_name ?: ($creditNote->invoice?->customer_name ?: 'Valued Customer') }}
                    </div>
                    @if($creditNote->customer?->company_name)
                        <div style="font-size: 11px; font-weight: bold; color: #333333; margin-top: 1px;">
                            {{ $creditNote->customer->company_name }}
                        </div>
                    @endif
                    @if($creditNote->invoice?->billing_address)
                        <div style="font-size: 10px; color: #444444; margin-top: 2px; line-height: 1.35;">
                            {{ $creditNote->invoice->billing_address }}
                        </div>
                    @endif
                    @if($creditNote->invoice?->billing_gstin || $creditNote->customer?->gst_number)
                        <div style="font-size: 10.5px; font-weight: bold; color: #000000; margin-top: 3px;">
                            GSTIN: {{ $creditNote->invoice?->billing_gstin ?: $creditNote->customer?->gst_number }}
                        </div>
                    @endif
                </td>

                <td style="width: 50%; background: #fafafa;">
                    <div style="font-size: 9.5px; font-weight: bold; text-transform: uppercase; color: #555555; margin-bottom: 3px;">
                        Reason for Issue & Reference
                    </div>
                    <div style="font-size: 12px; font-weight: bold; color: #b91c1c;">
                        {{ \App\Models\CreditNote::REASONS[$creditNote->reason] ?? $creditNote->reason }}
                    </div>
                    @if($creditNote->notes)
                        <div style="font-size: 10px; color: #444444; margin-top: 4px; line-height: 1.35;">
                            <strong>Remarks:</strong> {{ $creditNote->notes }}
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- 3. LINE ITEMS / ADJUSTMENT TABLE --}}
        <table class="tbl-items">
            <thead>
                <tr>
                    <th style="width: 6%; text-align: center;">#</th>
                    <th style="width: 54%; text-align: left;">Description of Service / Adjustment</th>
                    <th style="width: 18%; text-align: center;">Original Inv. Total</th>
                    <th style="width: 22%; text-align: right;">Credit Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @if($creditNote->items && $creditNote->items->count() > 0)
                    @foreach($creditNote->items as $idx => $ci)
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td>
                                <div style="font-weight: bold; color: #000000;">{{ $ci->item_name }}</div>
                                @if($ci->description)
                                    <div style="font-size: 9.5px; color: #555555; margin-top: 2px;">{{ $ci->description }}</div>
                                @endif
                            </td>
                            <td style="text-align: center; color: #555555;">₹{{ number_format((float) ($creditNote->invoice?->total_amount ?? 0), 2) }}</td>
                            <td style="text-align: right; font-weight: bold; color: #b91c1c;">₹{{ number_format((float) $ci->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td style="text-align: center;">1</td>
                        <td>
                            <div style="font-weight: bold; color: #000000;">Credit adjustment against Invoice #{{ $creditNote->invoice?->invoice_number }}</div>
                            <div style="font-size: 9.5px; color: #555555; margin-top: 2px;">Reason: {{ \App\Models\CreditNote::REASONS[$creditNote->reason] ?? $creditNote->reason }}</div>
                        </td>
                        <td style="text-align: center; color: #555555;">₹{{ number_format((float) ($creditNote->invoice?->total_amount ?? 0), 2) }}</td>
                        <td style="text-align: right; font-weight: bold; font-size: 11.5px; color: #b91c1c;">₹{{ number_format((float) $creditNote->total_amount, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- 4. DECLARATION & TOTALS FOOTER --}}
        <table class="tbl-footer">
            <tr>
                <td style="width: 50%; border-right: 1px solid #000000;">
                    <div style="font-size: 9.5px; font-weight: bold; text-transform: uppercase; color: #555555;">
                        Declaration
                    </div>
                    <div style="font-size: 10px; color: #333333; margin-top: 4px; line-height: 1.4;">
                        This credit note is issued under Section 34 of the CGST/SGST Act, 2017. The tax liability of the supplier and input tax credit of the recipient shall be adjusted accordingly.
                    </div>
                    <div style="font-size: 9.5px; color: #666666; margin-top: 24px;">
                        Issued by: <strong>{{ $creditNote->creator?->name ?? 'System' }}</strong> on {{ $creditNote->created_at?->format('d/m/Y') }}
                    </div>
                </td>

                <td style="width: 50%;">
                    <table class="totals-tbl">
                        @if($creditNote->subtotal > 0 && $creditNote->total_tax > 0)
                            <tr>
                                <td style="color: #555555;">Credited Subtotal</td>
                                <td style="text-align: right; font-weight: bold;">₹{{ number_format((float) $creditNote->subtotal, 2) }}</td>
                            </tr>
                            @if($creditNote->cgst_amount > 0)
                                <tr>
                                    <td style="color: #555555;">CGST Adjustment</td>
                                    <td style="text-align: right; font-weight: bold;">₹{{ number_format((float) $creditNote->cgst_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($creditNote->sgst_amount > 0)
                                <tr>
                                    <td style="color: #555555;">SGST Adjustment</td>
                                    <td style="text-align: right; font-weight: bold;">₹{{ number_format((float) $creditNote->sgst_amount, 2) }}</td>
                                </tr>
                            @endif
                            @if($creditNote->igst_amount > 0)
                                <tr>
                                    <td style="color: #555555;">IGST Adjustment</td>
                                    <td style="text-align: right; font-weight: bold;">₹{{ number_format((float) $creditNote->igst_amount, 2) }}</td>
                                </tr>
                            @endif
                        @endif
                        <tr class="total-highlight">
                            <td style="font-size: 12px; font-weight: bold; color: #000000;">Total Credited Value</td>
                            <td style="font-size: 14px; font-weight: bold; text-align: right; color: #b91c1c;">₹{{ number_format((float) $creditNote->total_amount, 2) }}</td>
                        </tr>
                    </table>

                    <div style="margin-top: 20px; text-align: right;">
                        <div style="font-size: 10px; font-weight: bold; color: #000000;">
                            {{ $setting?->signatory_name ?: ('FOR ' . strtoupper($setting?->company_name ?: 'Sai Techno Solutions Private Limited')) }}
                        </div>
                        @if($setting?->signature_url)
                            <img src="{{ $setting->signature_url }}" alt="Signature" style="max-height: 40px; margin: 4px 0;">
                        @else
                            <div style="height: 38px;"></div>
                        @endif
                        <div style="font-size: 9.5px; color: #555555;">Authorized Signatory</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
