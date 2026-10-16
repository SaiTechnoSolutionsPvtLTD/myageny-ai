<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Statement - {{ $selectedCustomer ? ($selectedCustomer->company_name ?: $selectedCustomer->customer_name) : 'All Customers' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 10mm 12mm 10mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #000000;
            font-size: 9.5px;
            line-height: 1.35;
        }

        .sheet {
            width: 100%;
        }

        /* Header */
        .hdr-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .hdr-left { vertical-align: top; width: 60%; }
        .hdr-right { vertical-align: top; width: 40%; text-align: right; }
        .co-logo { max-height: 40px; max-width: 160px; margin-bottom: 6px; }
        .co-name { font-size: 14px; font-weight: bold; color: #000; line-height: 1.2; }
        .co-info { font-size: 9px; color: #333; margin-top: 3px; line-height: 1.4; }
        .doc-title { font-size: 15px; font-weight: bold; color: #fe5f04; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-period { font-size: 10px; font-weight: bold; color: #333; margin-top: 3px; }
        .doc-date { font-size: 8.5px; color: #666; margin-top: 2px; }

        /* Customer & Summary Box */
        .meta-tbl { width: 100%; border-collapse: collapse; margin-bottom: 14px; border: 1px solid #000; }
        .meta-tbl td { vertical-align: top; padding: 8px 10px; font-size: 9.5px; }
        .cust-head { font-size: 8.5px; font-weight: bold; color: #666; text-transform: uppercase; margin-bottom: 3px; }
        .cust-name { font-size: 11.5px; font-weight: bold; color: #000; }
        .cust-co { font-size: 10px; font-weight: bold; color: #333; }

        .sum-tbl { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        .sum-tbl td { padding: 2px 0; }
        .sum-label { color: #555; }
        .sum-val { text-align: right; font-weight: bold; }
        .sum-total td { border-top: 1px solid #000; padding-top: 3px; font-weight: bold; font-size: 10.5px; color: #c2410c; }

        /* Ledger Table */
        .tbl-ledger { width: 100%; border-collapse: collapse; font-size: 9px; border: 1px solid #000; }
        .tbl-ledger th {
            background: #f1f5f9;
            color: #000;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #000;
            text-align: left;
        }
        .tbl-ledger td {
            padding: 5px 8px;
            border: 1px solid #000;
            vertical-align: middle;
        }
        .tbl-ledger tfoot td {
            background: #f8fafc;
            font-weight: bold;
            padding: 6px 8px;
            border: 1px solid #000;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        /* Bank & Signature (Bank details normal weight) */
        .btm-tbl { width: 100%; border-collapse: collapse; margin-top: 14px; border-top: 1px solid #000; padding-top: 8px; }
        .btm-tbl td { vertical-align: bottom; }
        .bank-info { font-size: 9px; line-height: 1.5; color: #000; }
        .bank-head { font-size: 9.5px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
        .sig-box { text-align: right; font-size: 9.5px; }
        .sig-co { font-weight: bold; color: #000; }
        .sig-img { max-height: 40px; margin: 4px 0; }
        .sig-title { font-size: 8.5px; font-weight: bold; color: #555; text-transform: uppercase; border-top: 1px solid #555; display: inline-block; padding-top: 3px; margin-top: 25px; }
    </style>
<body>
    <script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 8.5;
        $color = array(0.39, 0.45, 0.55);
        $pdf->page_text($pdf->get_width() - 36, $pdf->get_height() - 22, "{PAGE_NUM}", $font, $size, $color);
    }
    </script>

    <div class="sheet">
        {{-- Header --}}
        <table class="hdr-table">
            <tr>
                <td class="hdr-left">
                    @if($setting?->logo_path && file_exists(public_path('storage/' . $setting->logo_path)))
                        <img src="{{ public_path('storage/' . $setting->logo_path) }}" alt="Logo" class="co-logo">
                    @endif
                    <div class="co-name">{{ $setting?->company_name ?: config('app.name', 'My Agency') }}</div>
                    <div class="co-info">
                        @if($setting?->company_address)
                            <div>{{ $setting->company_address }}</div>
                        @endif
                        @php
                            $coCityState = array_filter([$setting?->company_city, $setting?->company_state, $setting?->company_pincode]);
                        @endphp
                        @if(!empty($coCityState))
                            <div>{{ implode(', ', $coCityState) }}</div>
                        @endif
                        @if($setting?->company_phone)
                            <div>Phone: {{ $setting->company_phone }}</div>
                        @endif
                        @if($setting?->company_email)
                            <div>Email: {{ $setting->company_email }}</div>
                        @endif
                        @if($setting?->company_gstin)
                            <div><strong>GSTIN:</strong> {{ $setting->company_gstin }}</div>
                        @endif
                    </div>
                </td>
                <td class="hdr-right">
                    <div class="doc-title">Statement of Accounts</div>
                    <div class="doc-period">
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M, Y') }}
                    </div>
                    <div class="doc-date">Generated on: {{ now()->format('d M, Y h:i A') }}</div>
                </td>
            </tr>
        </table>

        @if(!empty($selectedCustomer))
            {{-- Customer & Summary Banner --}}
            <table class="meta-tbl">
                <tr>
                    <td style="width: 55%; border-right: 1px solid #000; background: #fafafa;">
                        <div class="cust-head">To / Customer Details</div>
                        <div class="cust-name">{{ $selectedCustomer->customer_name }}</div>
                        @if($selectedCustomer->company_name)
                            <div class="cust-co">{{ $selectedCustomer->company_name }}</div>
                        @endif
                        @if($selectedCustomer->billing_address_street_1)
                            <div>{{ $selectedCustomer->billing_address_street_1 }}</div>
                        @endif
                        @php
                            $cLoc = array_filter([$selectedCustomer->billing_city, $selectedCustomer->billing_state, $selectedCustomer->billing_pin_code]);
                        @endphp
                        @if(!empty($cLoc))
                            <div>{{ implode(', ', $cLoc) }}</div>
                        @endif
                        @if($selectedCustomer->mobile_number)
                            <div>Phone: {{ $selectedCustomer->mobile_number }}</div>
                        @endif
                        @if($selectedCustomer->email)
                            <div>Email: {{ $selectedCustomer->email }}</div>
                        @endif
                        @if($selectedCustomer->gst_number)
                            <div><strong>GSTIN:</strong> {{ $selectedCustomer->gst_number }}</div>
                        @endif
                    </td>

                    <td style="width: 45%; background: #ffffff;">
                        <div class="cust-head">Account Summary</div>
                        <table class="sum-tbl">
                            <tr>
                                <td class="sum-label">Opening Balance:</td>
                                <td class="sum-val">Rs. {{ number_format((float) $openingBalance, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="sum-label">Invoiced (Debit):</td>
                                <td class="sum-val">Rs. {{ number_format((float) $totalPeriodDebit, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="sum-label">Paid (Credit):</td>
                                <td class="sum-val">(-) Rs. {{ number_format((float) $totalPeriodPaid, 2) }}</td>
                            </tr>
                            @if($totalPeriodCredited > 0)
                            <tr>
                                <td class="sum-label">Credit Notes (CN):</td>
                                <td class="sum-val">(-) Rs. {{ number_format((float) $totalPeriodCredited, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="sum-total">
                                <td>Closing Balance Due:</td>
                                <td class="sum-val">Rs. {{ number_format((float) $closingBalance, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Ledger Table --}}
            <table class="tbl-ledger">
                <thead>
                    <tr>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 14%;">Type</th>
                        <th style="width: 18%;">Ref #</th>
                        <th style="width: 26%;">Particulars</th>
                        <th class="text-right" style="width: 15%;">Debit (Rs.)</th>
                        <th class="text-right" style="width: 15%;">Credit (Rs.)</th>
                        <th class="text-right" style="width: 15%;">Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Opening Balance Row --}}
                    <tr style="background:#f8fafc; font-weight:bold;">
                        <td>{{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }}</td>
                        <td>Opening</td>
                        <td>—</td>
                        <td>Opening Balance Carried Forward</td>
                        <td class="text-right">—</td>
                        <td class="text-right">—</td>
                        <td class="text-right">Rs. {{ number_format((float) $openingBalance, 2) }}</td>
                    </tr>

                    @forelse($rows as $r)
                        <tr>
                            <td>{{ $r['date']->format('d-M-Y') }}</td>
                            <td>{{ $r['type_label'] }}</td>
                            <td style="font-weight:bold;">{{ $r['ref_no'] }}</td>
                            <td>
                                <div>{{ $r['particulars'] }}</div>
                                @if(!empty($r['details']))
                                    <div style="font-size:8px; color:#555;">{{ $r['details'] }}</div>
                                @endif
                            </td>
                            <td class="text-right" style="font-weight:bold;">
                                {{ $r['debit'] > 0 ? 'Rs. ' . number_format((float) $r['debit'], 2) : '—' }}
                            </td>
                            <td class="text-right" style="color:#047857; font-weight:bold;">
                                {{ $r['credit'] > 0 ? 'Rs. ' . number_format((float) $r['credit'], 2) : '—' }}
                            </td>
                            <td class="text-right" style="font-weight:bold; color:{{ $r['running_balance'] > 0 ? '#b91c1c' : '#000' }};">
                                Rs. {{ number_format((float) $r['running_balance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="padding:15px; color:#666;">
                                No transactions found in this date range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right">Total / Net Balance Due:</td>
                        <td class="text-right">Rs. {{ number_format((float) $totalPeriodDebit, 2) }}</td>
                        <td class="text-right" style="color:#047857;">Rs. {{ number_format((float) $totalPeriodCredit, 2) }}</td>
                        <td class="text-right" style="color:#c2410c; font-size:10px;">Rs. {{ number_format((float) $closingBalance, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Bank & Signature (Normal weight bank details) --}}
            <table class="btm-tbl">
                <tr>
                    <td style="width: 60%;" class="bank-info">
                        <div class="bank-head">Remittance / Bank Details:</div>
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
                    </td>

                    <td style="width: 40%;" class="sig-box">
                        <div class="sig-co">For {{ $setting?->company_name ?: config('app.name', 'My Agency') }}</div>
                        @if($setting?->signature_path && file_exists(public_path('storage/' . $setting->signature_path)))
                            <img src="{{ public_path('storage/' . $setting->signature_path) }}" alt="Signature" class="sig-img">
                            <div style="font-size:9px; font-weight:bold;">{{ $setting->signatory_name ?: 'Authorized Signatory' }}</div>
                        @else
                            <div class="sig-title">Authorized Signatory</div>
                        @endif
                    </td>
                </tr>
            </table>

        @else
            {{-- All Customers Summary Table for PDF --}}
            <table class="tbl-ledger">
                <thead>
                    <tr>
                        <th>Customer / Company</th>
                        <th>Contact</th>
                        <th class="text-right">Billed in Period (Rs.)</th>
                        <th class="text-right">Collected in Period (Rs.)</th>
                        <th class="text-right">Credits in Period (Rs.)</th>
                        <th class="text-right">Current Balance Due (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customerRows as $row)
                        @php $cust = $row['customer']; @endphp
                        <tr>
                            <td>
                                <strong>{{ $cust->customer_name }}</strong>
                                @if($cust->company_name)
                                    <div>{{ $cust->company_name }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $cust->mobile_number ?: '—' }}
                                @if($cust->email)
                                    <div style="font-size:8.5px; color:#555;">{{ $cust->email }}</div>
                                @endif
                            </td>
                            <td class="text-right">Rs. {{ number_format((float) $row['invoiced_period'], 2) }}</td>
                            <td class="text-right">Rs. {{ number_format((float) $row['collected_period'], 2) }}</td>
                            <td class="text-right">Rs. {{ number_format((float) $row['credited_period'], 2) }}</td>
                            <td class="text-right" style="font-weight:bold; color:{{ $row['current_balance'] > 0 ? '#b91c1c' : '#000' }};">
                                Rs. {{ number_format((float) $row['current_balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right">Total:</td>
                        <td class="text-right">Rs. {{ number_format((float) $totalPeriodInvoiced, 2) }}</td>
                        <td class="text-right">Rs. {{ number_format((float) $totalPeriodCollected, 2) }}</td>
                        <td class="text-right">Rs. {{ number_format((float) $totalPeriodCredited, 2) }}</td>
                        <td class="text-right" style="color:#c2410c; font-size:10px;">Rs. {{ number_format((float) $totalReceivables, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

</body>
</html>
