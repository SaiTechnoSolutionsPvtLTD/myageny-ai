<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Note #{{ $creditNote->credit_note_number }} - Print</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #fff;
            color: #000;
            padding: 20px;
            font-size: 11px;
            line-height: 1.45;
        }
        @media print {
            body { padding: 0; }
            @page { size: A4; margin: 12mm; }
        }
        .cn-sheet {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #000;
        }
        .sheet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 18px 20px 14px;
            border-bottom: 1px solid #000;
        }
        .badge-title {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0.08em;
            color: #b91c1c;
            text-transform: uppercase;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid #000;
        }
        .tbl-items {
            width: 100%;
            border-collapse: collapse;
        }
        .tbl-items th, .tbl-items td {
            border-right: 1px solid #000;
            padding: 8px 12px;
        }
        .tbl-items th:last-child, .tbl-items td:last-child {
            border-right: none;
        }
        .tbl-items th {
            background: #f3f4f6;
            border-bottom: 1px solid #000;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .tbl-items tr {
            border-bottom: 1px solid #000;
        }
    </style>
</head>
<body>
    <div class="cn-sheet">
        <div class="sheet-header">
            <div>
                @if($setting?->logo_url)
                    <img src="{{ $setting->logo_url }}" alt="Logo" style="max-height:55px; max-width:180px; object-fit:contain; margin-bottom:8px;">
                @endif
                <div style="font-size:15px; font-weight:800; color:#000;">
                    {{ $setting?->company_name ?: 'Sai Techno Solutions Private Limited' }}
                </div>
                <div style="font-size:11px; color:#333; max-width:380px; line-height:1.4;">
                    {{ $setting?->full_address ?: 'Plot No 8, Sai Nagar, Thoraipakkam, Chennai - 600097' }}
                </div>
                @if($setting?->company_gstin)
                    <div style="font-size:11px; font-weight:700; color:#000; margin-top:3px;">
                        GSTIN: {{ $setting->company_gstin }}
                    </div>
                @endif
            </div>

            <div style="text-align:right;">
                <div class="badge-title">CREDIT NOTE</div>
                <div style="font-size:10px; color:#555; margin-top:2px;">(Original for Recipient)</div>

                <table style="margin-top:10px; font-size:11.5px; border-collapse:collapse; margin-left:auto;">
                    <tr>
                        <td style="font-weight:700; padding:2px 8px 2px 0; text-align:right;">Credit Note #</td>
                        <td style="font-weight:700; padding:2px 4px;">:</td>
                        <td style="font-weight:800; font-family:monospace; color:#b91c1c; text-align:left;">{{ $creditNote->credit_note_number }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:700; padding:2px 8px 2px 0; text-align:right;">Credit Note Date</td>
                        <td style="font-weight:700; padding:2px 4px;">:</td>
                        <td style="font-weight:700; text-align:left;">{{ $creditNote->credit_note_date ? $creditNote->credit_note_date->format('d/m/Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:700; padding:2px 8px 2px 0; text-align:right;">Against Invoice #</td>
                        <td style="font-weight:700; padding:2px 4px;">:</td>
                        <td style="font-weight:800; font-family:monospace; color:#000; text-align:left;">{{ $creditNote->invoice?->invoice_number ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:700; padding:2px 8px 2px 0; text-align:right;">Original Inv. Date</td>
                        <td style="font-weight:700; padding:2px 4px;">:</td>
                        <td style="text-align:left;">{{ $creditNote->invoice?->invoice_date ? $creditNote->invoice->invoice_date->format('d/m/Y') : '—' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="grid-2">
            <div style="padding:12px 18px; border-right:1px solid #000;">
                <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#555; margin-bottom:4px;">Customer Details (Credited To)</div>
                <div style="font-size:13px; font-weight:800; color:#000;">{{ $creditNote->customer?->customer_name ?: ($creditNote->invoice?->customer_name ?: 'Valued Customer') }}</div>
                @if($creditNote->customer?->company_name)
                    <div style="font-size:11.5px; font-weight:700; color:#333;">{{ $creditNote->customer->company_name }}</div>
                @endif
                @if($creditNote->invoice?->billing_address)
                    <div style="font-size:11px; color:#444; margin-top:2px;">{{ $creditNote->invoice->billing_address }}</div>
                @endif
                @if($creditNote->invoice?->billing_gstin || $creditNote->customer?->gst_number)
                    <div style="font-size:11px; font-weight:700; color:#000; margin-top:2px;">
                        GSTIN: {{ $creditNote->invoice?->billing_gstin ?: $creditNote->customer?->gst_number }}
                    </div>
                @endif
            </div>

            <div style="padding:12px 18px; background:#fafafa;">
                <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#555; margin-bottom:4px;">Reason for Issue & Reference</div>
                <div style="font-size:12.5px; font-weight:800; color:#b91c1c;">
                    {{ \App\Models\CreditNote::REASONS[$creditNote->reason] ?? $creditNote->reason }}
                </div>
                @if($creditNote->notes)
                    <div style="font-size:11px; color:#444; margin-top:4px;">
                        <strong>Remarks:</strong> {{ $creditNote->notes }}
                    </div>
                @endif
            </div>
        </div>

        <table class="tbl-items">
            <thead>
                <tr>
                    <th style="width:5%; text-align:center;">#</th>
                    <th style="width:55%;">Description of Service / Adjustment</th>
                    <th style="width:15%; text-align:center;">Original Inv. Total</th>
                    <th style="width:25%; text-align:right;">Credit Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @if($creditNote->items->count() > 0)
                    @foreach($creditNote->items as $idx => $ci)
                        <tr>
                            <td style="text-align:center;">{{ $idx + 1 }}</td>
                            <td>
                                <div style="font-weight:700;">{{ $ci->item_name }}</div>
                                @if($ci->description)<div style="font-size:10px; color:#555;">{{ $ci->description }}</div>@endif
                            </td>
                            <td style="text-align:center; color:#555;">₹{{ number_format((float) ($creditNote->invoice?->total_amount ?? 0), 2) }}</td>
                            <td style="text-align:right; font-weight:700; color:#b91c1c;">₹{{ number_format((float) $ci->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td style="text-align:center;">1</td>
                        <td>
                            <div style="font-weight:700;">Credit adjustment against Invoice #{{ $creditNote->invoice?->invoice_number }}</div>
                            <div style="font-size:10px; color:#555;">Reason: {{ \App\Models\CreditNote::REASONS[$creditNote->reason] ?? $creditNote->reason }}</div>
                        </td>
                        <td style="text-align:center; color:#555;">₹{{ number_format((float) ($creditNote->invoice?->total_amount ?? 0), 2) }}</td>
                        <td style="text-align:right; font-weight:800; font-size:12px; color:#b91c1c;">₹{{ number_format((float) $creditNote->total_amount, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="grid-2" style="border-bottom:none;">
            <div style="padding:16px 18px; border-right:1px solid #000; display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#555;">Declaration</div>
                    <div style="font-size:10.5px; color:#333; margin-top:4px; line-height:1.45;">
                        This credit note is issued under Section 34 of the CGST/SGST Act, 2017. The tax liability of the supplier and input tax credit of the recipient shall be adjusted accordingly.
                    </div>
                </div>
                <div style="font-size:10px; color:#666; margin-top:20px;">
                    Issued by: <strong>{{ $creditNote->creator?->name ?? 'System' }}</strong> on {{ $creditNote->created_at?->format('d/m/Y') }}
                </div>
            </div>

            <div style="padding:16px 18px;">
                <table style="width:100%; font-size:11.5px; border-collapse:collapse;">
                    @if($creditNote->subtotal > 0 && $creditNote->total_tax > 0)
                        <tr>
                            <td style="padding:3px 0; color:#555;">Credited Subtotal</td>
                            <td style="padding:3px 0; text-align:right; font-weight:700;">₹{{ number_format((float) $creditNote->subtotal, 2) }}</td>
                        </tr>
                        @if($creditNote->cgst_amount > 0)
                            <tr>
                                <td style="padding:3px 0; color:#555;">CGST Adjustment</td>
                                <td style="padding:3px 0; text-align:right; font-weight:700;">₹{{ number_format((float) $creditNote->cgst_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($creditNote->sgst_amount > 0)
                            <tr>
                                <td style="padding:3px 0; color:#555;">SGST Adjustment</td>
                                <td style="padding:3px 0; text-align:right; font-weight:700;">₹{{ number_format((float) $creditNote->sgst_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($creditNote->igst_amount > 0)
                            <tr>
                                <td style="padding:3px 0; color:#555;">IGST Adjustment</td>
                                <td style="padding:3px 0; text-align:right; font-weight:700;">₹{{ number_format((float) $creditNote->igst_amount, 2) }}</td>
                            </tr>
                        @endif
                    @endif
                    <tr style="border-top:1px solid #000; border-bottom:1px solid #000;">
                        <td style="padding:8px 0; font-size:13px; font-weight:800; color:#000;">Total Credited Value</td>
                        <td style="padding:8px 0; font-size:15px; font-weight:900; text-align:right; color:#b91c1c;">₹{{ number_format((float) $creditNote->total_amount, 2) }}</td>
                    </tr>
                </table>

                <div style="margin-top:24px; text-align:right;">
                    <div style="font-size:10.5px; font-weight:700; color:#000;">{{ $setting?->signatory_name ?: ('FOR ' . strtoupper($setting?->company_name ?: 'Sai Techno Solutions Private Limited')) }}</div>
                    @if($setting?->signature_url)
                        <img src="{{ $setting->signature_url }}" alt="Signature" style="max-height:45px; margin:6px 0;">
                    @else
                        <div style="height:45px;"></div>
                    @endif
                    <div style="font-size:10px; color:#555;">Authorized Signatory</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.print();
        });
    </script>
</body>
</html>
