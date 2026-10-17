<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Statement - {{ $selectedCustomer ? ($selectedCustomer->company_name ?: $selectedCustomer->customer_name) : 'All Customers' }}</title>
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
            font-size: 12px;
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
        .p-btn-print:hover { transform: translateY(-1px); color: #fff; }
        .p-btn-back { background: #f1f5f9; color: #475569; }
        .p-btn-back:hover { background: #e2e8f0; }

        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #000000;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Header */
        .hdr-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .hdr-left { vertical-align: top; width: 60%; }
        .hdr-right { vertical-align: top; width: 40%; text-align: right; }
        .co-logo { max-height: 50px; max-width: 200px; object-fit: contain; margin-bottom: 8px; }
        .co-name { font-size: 16px; font-weight: 800; color: #000; line-height: 1.2; }
        .co-info { font-size: 10.5px; color: #333; margin-top: 4px; line-height: 1.45; }
        .doc-title { font-size: 18px; font-weight: 900; color: #fe5f04; text-transform: uppercase; letter-spacing: 0.05em; }
        .doc-period { font-size: 11px; font-weight: 700; color: #333; margin-top: 4px; }
        .doc-date { font-size: 10px; color: #666; margin-top: 2px; }

        /* Customer & Summary Box */
        .meta-wrap {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid #000;
            padding: 12px 14px;
            margin-bottom: 18px;
            background: #fafafa;
        }
        .cust-box { width: 55%; font-size: 11px; line-height: 1.5; }
        .cust-head { font-size: 10px; font-weight: 800; color: #666; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 3px; }
        .cust-name { font-size: 13px; font-weight: 800; color: #000; }
        .cust-co { font-size: 12px; font-weight: 700; color: #333; }
        .summary-box { width: 45%; border-left: 1px solid #ddd; padding-left: 14px; font-size: 11px; }
        .sum-tbl { width: 100%; border-collapse: collapse; font-size: 11px; }
        .sum-tbl td { padding: 2px 0; }
        .sum-label { color: #555; }
        .sum-val { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
        .sum-total td { border-top: 1px solid #000; padding-top: 4px; font-weight: 900; font-size: 12px; color: #c2410c; }

        /* Ledger Table */
        .tbl-ledger { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10.5px; border: 1px solid #000; }
        .tbl-ledger th {
            background: #f1f5f9;
            color: #000;
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 8px 10px;
            border: 1px solid #000;
            text-align: left;
        }
        .tbl-ledger td {
            padding: 7px 10px;
            border: 1px solid #000;
            vertical-align: middle;
        }
        .tbl-ledger tfoot td {
            background: #f8fafc;
            font-weight: 800;
            padding: 8px 10px;
            border: 1px solid #000;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }

        /* Bank & Signatures */
        .btm-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 20px;
            padding-top: 14px;
            border-top: 1px solid #000;
            gap: 20px;
        }
        .bank-info { font-size: 10.5px; line-height: 1.6; color: #000; width: 60%; }
        .bank-head { font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
        .sig-box { width: 35%; text-align: right; }
        .sig-co { font-size: 11px; font-weight: 700; color: #000; }
        .sig-img { max-height: 45px; margin: 8px 0; }
        .sig-title { font-size: 10px; font-weight: 700; color: #555; text-transform: uppercase; border-top: 1px solid #555; display: inline-block; padding-top: 4px; margin-top: 35px; }

        @media print {
            body { background: #fff; padding: 0; font-size: 10px; }
            .print-btn-bar { display: none !important; }
            .sheet { border: none !important; box-shadow: none !important; padding: 0; width: 100% !important; max-width: 100% !important; }
            @page {
                size: A4 portrait;
                margin: 16mm 18mm 16mm 18mm;
                @bottom-right {
                    content: counter(page);
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    font-size: 10px;
                    color: #64748b;
                    font-weight: 500;
                }
            }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

    <div class="print-btn-bar">
        <a href="{{ route('accounts.reports.customer-balance-sheet', request()->query()) }}" class="p-btn p-btn-back">
            ← Back to Report
        </a>
        <div style="font-size:12px; font-weight:700; color:#334155;">
            Customer Balance Sheet Statement
        </div>
        <button type="button" onclick="window.print()" class="p-btn p-btn-print">
            Print Statement (Ctrl + P)
        </button>
    </div>

    <div class="sheet">
        {{-- Header --}}
        <table class="hdr-table">
            <tr>
                <td class="hdr-left">
                    @if($setting?->logo_path)
                        <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="Logo" class="co-logo">
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
            <div class="meta-wrap">
                <div class="cust-box">
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
                </div>

                <div class="summary-box">
                    <div class="cust-head">Account Summary</div>
                    <table class="sum-tbl">
                        <tr>
                            <td class="sum-label">Opening Balance:</td>
                            <td class="sum-val">₹{{ number_format((float) $openingBalance, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="sum-label">Invoiced (Debit):</td>
                            <td class="sum-val">₹{{ number_format((float) $totalPeriodDebit, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="sum-label">Paid / Receipts (Credit):</td>
                            <td class="sum-val">(-) ₹{{ number_format((float) $totalPeriodPaid, 2) }}</td>
                        </tr>
                        @if($totalPeriodCredited > 0)
                        <tr>
                            <td class="sum-label">Credit Notes (CN):</td>
                            <td class="sum-val">(-) ₹{{ number_format((float) $totalPeriodCredited, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="sum-total">
                            <td>Closing Balance Due:</td>
                            <td class="sum-val">₹{{ number_format((float) $closingBalance, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Ledger Table --}}
            <table class="tbl-ledger">
                <thead>
                    <tr>
                        <th style="width: 13%;">Date</th>
                        <th style="width: 14%;">Type</th>
                        <th style="width: 16%;">Ref #</th>
                        <th style="width: 27%;">Particulars</th>
                        <th class="text-right" style="width: 15%;">Debit (₹)</th>
                        <th class="text-right" style="width: 15%;">Credit (₹)</th>
                        <th class="text-right" style="width: 15%;">Balance (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Opening Balance Row --}}
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td>{{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }}</td>
                        <td>Opening</td>
                        <td>—</td>
                        <td>Opening Balance Carried Forward</td>
                        <td class="text-right">—</td>
                        <td class="text-right">—</td>
                        <td class="text-right">₹{{ number_format((float) $openingBalance, 2) }}</td>
                    </tr>

                    @forelse($rows as $r)
                        <tr>
                            <td>{{ $r['date']->format('d-M-Y') }}</td>
                            <td>{{ $r['type_label'] }}</td>
                            <td style="font-weight:700;">{{ $r['ref_no'] }}</td>
                            <td>
                                <div>{{ $r['particulars'] }}</div>
                                @if(!empty($r['details']))
                                    <div style="font-size:9.5px; color:#555;">{{ $r['details'] }}</div>
                                @endif
                            </td>
                            <td class="text-right font-weight:700;">
                                {{ $r['debit'] > 0 ? '₹' . number_format((float) $r['debit'], 2) : '—' }}
                            </td>
                            <td class="text-right" style="color:#047857; font-weight:700;">
                                {{ $r['credit'] > 0 ? '₹' . number_format((float) $r['credit'], 2) : '—' }}
                            </td>
                            <td class="text-right" style="font-weight:800; color:{{ $r['running_balance'] > 0 ? '#b91c1c' : '#000' }};">
                                ₹{{ number_format((float) $r['running_balance'], 2) }}
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
                        <td class="text-right">₹{{ number_format((float) $totalPeriodDebit, 2) }}</td>
                        <td class="text-right" style="color:#047857;">₹{{ number_format((float) $totalPeriodCredit, 2) }}</td>
                        <td class="text-right" style="color:#c2410c; font-size:12px;">₹{{ number_format((float) $closingBalance, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- Bank & Signature (Bank details normal weight as per preference) --}}
            <div class="btm-wrap">
                <div class="bank-info">
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
                </div>

                <div class="sig-box">
                    <div class="sig-co">For {{ $setting?->company_name ?: config('app.name', 'My Agency') }}</div>
                    @if($setting?->signature_path)
                        <img src="{{ asset('storage/' . $setting->signature_path) }}" alt="Signature" class="sig-img">
                        <div style="font-size:10px; font-weight:700;">{{ $setting->signatory_name ?: 'Authorized Signatory' }}</div>
                    @else
                        <div class="sig-title">Authorized Signatory</div>
                    @endif
                </div>
            </div>

        @else
            {{-- All Customers Summary Table for Print --}}
            <table class="tbl-ledger">
                <thead>
                    <tr>
                        <th>Customer / Company</th>
                        <th>Contact</th>
                        <th class="text-right">Billed in Period (₹)</th>
                        <th class="text-right">Collected in Period (₹)</th>
                        <th class="text-right">Credits in Period (₹)</th>
                        <th class="text-right">Current Balance Due (₹)</th>
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
                                    <div style="font-size:9.5px; color:#555;">{{ $cust->email }}</div>
                                @endif
                            </td>
                            <td class="text-right">₹{{ number_format((float) $row['invoiced_period'], 2) }}</td>
                            <td class="text-right">₹{{ number_format((float) $row['collected_period'], 2) }}</td>
                            <td class="text-right">₹{{ number_format((float) $row['credited_period'], 2) }}</td>
                            <td class="text-right" style="font-weight:800; color:{{ $row['current_balance'] > 0 ? '#b91c1c' : '#000' }};">
                                ₹{{ number_format((float) $row['current_balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right">Total:</td>
                        <td class="text-right">₹{{ number_format((float) $totalPeriodInvoiced, 2) }}</td>
                        <td class="text-right">₹{{ number_format((float) $totalPeriodCollected, 2) }}</td>
                        <td class="text-right">₹{{ number_format((float) $totalPeriodCredited, 2) }}</td>
                        <td class="text-right" style="color:#c2410c; font-size:12px;">₹{{ number_format((float) $totalReceivables, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

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
