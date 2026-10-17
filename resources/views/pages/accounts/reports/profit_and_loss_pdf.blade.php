<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss Statement - {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 16mm 15mm 16mm;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #000000;
            font-size: 8px;
            line-height: 1.35;
        }

        .sheet { width: 100%; }

        /* Top Header */
        .hdr-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .hdr-left { vertical-align: top; width: 55%; color: #000000; }
        .hdr-right { vertical-align: top; width: 45%; text-align: right; color: #000000; }
        .co-logo { max-height: 36px; max-width: 140px; margin-bottom: 4px; }
        .co-name { font-size: 14px; font-weight: bold; color: #000000; line-height: 1.2; }
        .co-info { font-size: 8px; color: #000000; margin-top: 3px; line-height: 1.4; }
        .doc-title { font-size: 14px; font-weight: bold; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-period { font-size: 10px; font-weight: bold; color: #000000; margin-top: 3px; }
        .doc-meta { font-size: 8px; color: #000000; margin-top: 2px; }

        .hdr-divider { width: 100%; height: 1px; background-color: #cbd5e1; margin: 8px 0 10px 0; }

        /* Tables */
        .sec-title {
            font-size: 9px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 12px 0 5px 0;
            line-height: 1.2;
        }

        .tbl-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            margin-bottom: 8px;
            border: 1px solid #cbd5e1;
        }
        .tbl-data th {
            background-color: #f8fafc;
            color: #000000;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            font-size: 7px;
        }
        .tbl-data td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            color: #000000;
            vertical-align: middle;
        }
        .tbl-data .section-head {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 8px;
            color: #000000;
            text-transform: uppercase;
        }
        .tbl-data .subtotal-row td {
            background-color: #f8fafc;
            font-weight: bold;
            border: 1px solid #cbd5e1;
            color: #000000;
        }
        .tbl-data .grand-row td {
            background-color: #f8fafc;
            font-weight: bold;
            font-size: 9px;
            color: #000000;
            border: 1px solid #cbd5e1;
            padding: 6px;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .pl-indent { padding-left: 16px !important; }

        /* Bottom Signoff */
        .signoff-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        .signoff-table td {
            vertical-align: bottom;
            font-size: 8px;
            color: #000000;
        }
        .sig-line {
            border-top: 1px solid #cbd5e1;
            width: 140px;
            margin-top: 25px;
            margin-left: auto;
            text-align: center;
            padding-top: 3px;
            font-weight: bold;
            color: #000000;
        }
    </style>
</head>
<body>
    <script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 7.5;
        $color = array(0, 0, 0);
        $pdf->page_text($pdf->get_width() - 80, $pdf->get_height() - 25, "{PAGE_NUM} / {PAGE_COUNT}", $font, $size, $color);
        $pdf->page_text(45, $pdf->get_height() - 25, "myAgenci.ai Accounts Financial Intelligence • Confidential", $font, 7, $color);
    }
    </script>

    <div class="sheet">
        {{-- Top Header --}}
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
                        @if($setting?->company_gstin)<strong>GSTIN:</strong> {{ $setting->company_gstin }} &bull; @endif
                        @if($setting?->company_phone)<strong>Phone:</strong> {{ $setting->company_phone }} &bull; @endif
                        @if($setting?->company_email)<strong>Email:</strong> {{ $setting->company_email }}@endif
                    </div>
                </td>
                <td class="hdr-right">
                    <div class="doc-title">Profit &amp; Loss Statement</div>
                    <div class="doc-period">Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</div>
                    <div class="doc-meta">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                </td>
            </tr>
        </table>

        <div class="hdr-divider"></div>

        {{-- Formal Statement Table --}}
        <div class="sec-title">Statement of Profit &amp; Loss</div>
        <table class="tbl-data">
            <thead>
                <tr>
                    <th style="width: 52%;">Particulars / Head of Account</th>
                    <th class="text-center" style="width: 14%;">Code / Type</th>
                    <th class="text-right" style="width: 17%;">Subtotal (INR)</th>
                    <th class="text-right" style="width: 17%;">Net Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                {{-- SECTION I: REVENUE --}}
                <tr class="section-head">
                    <td colspan="4">I. Operating Revenue / Income</td>
                </tr>
                <tr>
                    <td class="pl-indent">
                        <strong>Gross Invoiced Sales</strong>
                        <div style="font-size:7px;">Total billed sales across {{ $invoicesCount }} finalized invoices</div>
                    </td>
                    <td class="text-center">SALES</td>
                    <td class="text-right">{{ number_format($netSalesSubtotal, 2) }}</td>
                    <td class="text-right">-</td>
                </tr>
                @if($totalCreditNotesSubtotal > 0)
                <tr>
                    <td class="pl-indent">
                        Less: Credit Notes &amp; Returns ({{ $creditNotesCount }} notes)
                    </td>
                    <td class="text-center">CN</td>
                    <td class="text-right">-{{ number_format($totalCreditNotesSubtotal, 2) }}</td>
                    <td class="text-right">-</td>
                </tr>
                @endif
                @if($totalSalesTax > 0)
                <tr>
                    <td class="pl-indent">
                        Output GST Collected (Tax Liability)
                    </td>
                    <td class="text-center">GST</td>
                    <td class="text-right">{{ number_format($totalSalesTax, 2) }}</td>
                    <td class="text-right">-</td>
                </tr>
                @endif
                <tr class="subtotal-row">
                    <td><strong>TOTAL NET OPERATING REVENUE (A)</strong></td>
                    <td class="text-center">REVENUE</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><strong>{{ number_format($netOperatingRevenue, 2) }}</strong></td>
                </tr>

                {{-- SECTION II: EXPENSES --}}
                <tr class="section-head">
                    <td colspan="4">II. Operating Expenses (Expenditure)</td>
                </tr>
                @foreach($categoriesBreakdown as $cat)
                <tr>
                    <td class="pl-indent">
                        <strong>{{ $cat['name'] }}</strong>
                        <span style="font-size:7px;">({{ $cat['count'] }} entries &bull; Fixed: {{ number_format($cat['fixed'], 2) }} | Var: {{ number_format($cat['variable'], 2) }})</span>
                    </td>
                    <td class="text-center">{{ $cat['code'] }}</td>
                    <td class="text-right">{{ number_format($cat['total'], 2) }}</td>
                    <td class="text-right">-</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td>
                        <strong>TOTAL OPERATING EXPENSES (B)</strong>
                        <div style="font-size:7px;">Fixed: {{ number_format($totalFixedExpenses, 2) }} | Var: {{ number_format($totalVariableExpenses, 2) }}</div>
                    </td>
                    <td class="text-center">EXPENSE</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><strong>{{ number_format($totalExpenses, 2) }}</strong></td>
                </tr>

                {{-- SECTION III: NET PROFIT / LOSS --}}
                <tr class="grand-row">
                    <td colspan="2">
                        <strong>{{ $netProfit >= 0 ? 'NET OPERATING PROFIT (A - B)' : 'NET OPERATING LOSS (A - B)' }}</strong>
                        <span style="font-size:7.5px;">(Profit Margin: {{ $profitMargin }}% &bull; Expense Ratio: {{ $expenseRatio }}%)</span>
                    </td>
                    <td class="text-right">Margin: {{ $profitMargin }}%</td>
                    <td class="text-right">
                        <strong>{{ $netProfit < 0 ? '-' : '' }}INR {{ number_format(abs($netProfit), 2) }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Category Breakdown Table --}}
        <div class="sec-title">Expense Breakdown by Category Heads</div>
        <table class="tbl-data">
            <thead>
                <tr>
                    <th style="width: 40%;">Category</th>
                    <th class="text-center" style="width: 12%;">Code</th>
                    <th class="text-right" style="width: 16%;">Fixed Spend</th>
                    <th class="text-right" style="width: 16%;">Variable Spend</th>
                    <th class="text-right" style="width: 16%;">Total Spend</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categoriesBreakdown as $cat)
                <tr>
                    <td><strong>{{ $cat['name'] }}</strong></td>
                    <td class="text-center">{{ $cat['code'] ?: '—' }}</td>
                    <td class="text-right">{{ number_format($cat['fixed'], 2) }}</td>
                    <td class="text-right">{{ number_format($cat['variable'], 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($cat['total'], 2) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 10px;">No expense records found.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="subtotal-row">
                    <td colspan="2"><strong>Total Expenses</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalFixedExpenses, 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalVariableExpenses, 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalExpenses, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>

        {{-- Bottom Signoff --}}
        <table class="signoff-table">
            <tr>
                <td style="width: 60%;">
                    * This is an automated system-generated Financial Statement of Profit and Loss.<br>
                    * Based on finalized accrual invoices and registered operating expense ledgers.
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="sig-line">Authorized Signatory</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
