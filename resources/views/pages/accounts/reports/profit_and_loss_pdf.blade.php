<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss Statement - {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 12mm 12mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #ffffff;
            color: #1e293b;
            font-size: 8.5px;
            line-height: 1.4;
        }

        .sheet { width: 100%; }

        /* Top Header */
        .hdr-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .hdr-left { vertical-align: top; width: 55%; }
        .hdr-right { vertical-align: top; width: 45%; text-align: right; }
        .co-logo { max-height: 38px; max-width: 150px; margin-bottom: 4px; }
        .co-name { font-size: 14px; font-weight: bold; color: #0f172a; line-height: 1.2; }
        .co-info { font-size: 8px; color: #64748b; margin-top: 3px; line-height: 1.4; }
        .doc-title { font-size: 15px; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-period { font-size: 9.5px; font-weight: bold; color: #0f172a; margin-top: 3px; }
        .doc-meta { font-size: 8px; color: #64748b; margin-top: 2px; }

        .hdr-divider { width: 100%; height: 1px; background-color: #e2e8f0; margin: 6px 0 10px 0; border-bottom: 2px solid #059669; }

        /* Summary KPI Cards */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 12px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 10px;
            vertical-align: middle;
        }
        .kpi-card.rev { border-left: 3.5px solid #10b981; background: #fafffd; }
        .kpi-card.exp { border-left: 3.5px solid #f97316; background: #fffcf9; }
        .kpi-card.profit { border-left: 3.5px solid #059669; background: #f0fdf4; }
        .kpi-card.loss { border-left: 3.5px solid #dc2626; background: #fef2f2; }
        .kpi-card.cash { border-left: 3.5px solid #2563eb; background: #f8faff; }

        .kpi-label { font-size: 7.5px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .kpi-val { font-size: 12.5px; font-weight: 800; margin-top: 2px; line-height: 1.1; }
        .kpi-sub { font-size: 7px; color: #94a3b8; margin-top: 2px; }

        /* Tables */
        .sec-title {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 12px 0 5px 0;
            padding-left: 6px;
            border-left: 3px solid #059669;
            line-height: 1.2;
        }

        .tbl-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 10px;
        }
        .tbl-data th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 7.5px;
        }
        .tbl-data td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: middle;
        }
        .tbl-data .section-head {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 8.5px;
            color: #0f172a;
            text-transform: uppercase;
        }
        .tbl-data .subtotal-row td {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 1.5px solid #94a3b8;
            border-bottom: 1.5px solid #94a3b8;
        }
        .tbl-data .grand-row td {
            background-color: #ecfdf5;
            font-weight: 800;
            font-size: 10px;
            color: #064e3b;
            border-top: 2px solid #059669;
            border-bottom: 2.5px double #059669;
            padding: 7px 8px;
        }
        .tbl-data .grand-row.loss td {
            background-color: #fef2f2;
            color: #991b1b;
            border-top: 2px solid #dc2626;
            border-bottom: 2.5px double #dc2626;
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .pl-indent { padding-left: 20px !important; }

        /* Bottom Signoff */
        .signoff-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .signoff-table td {
            vertical-align: bottom;
            font-size: 8px;
            color: #64748b;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            width: 140px;
            margin-top: 30px;
            margin-left: auto;
            text-align: center;
            padding-top: 3px;
            font-weight: bold;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 7.5;
        $color = array(0.39, 0.45, 0.55);
        $pdf->page_text($pdf->get_width() - 40, $pdf->get_height() - 20, "{PAGE_NUM} / {PAGE_COUNT}", $font, $size, $color);
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
                        @if($setting?->company_address){{ $setting->company_address }}<br>@endif
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

        {{-- KPI Cards --}}
        <table class="kpi-table">
            <tr>
                <td class="kpi-card rev" style="width: 25%;">
                    <div class="kpi-label">Operating Revenue</div>
                    <div class="kpi-val" style="color:#065f46;">INR {{ number_format($netOperatingRevenue, 2) }}</div>
                    <div class="kpi-sub">Gross: {{ number_format($grossSales, 2) }}</div>
                </td>
                <td class="kpi-card exp" style="width: 25%;">
                    <div class="kpi-label">Operating Expenses</div>
                    <div class="kpi-val" style="color:#9a3412;">INR {{ number_format($totalExpenses, 2) }}</div>
                    <div class="kpi-sub">Fixed: {{ number_format($totalFixedExpenses, 2) }} | Var: {{ number_format($totalVariableExpenses, 2) }}</div>
                </td>
                <td class="kpi-card {{ $netProfit >= 0 ? 'profit' : 'loss' }}" style="width: 25%;">
                    <div class="kpi-label">{{ $netProfit >= 0 ? 'Net Operating Profit' : 'Net Operating Loss' }}</div>
                    <div class="kpi-val" style="color:{{ $netProfit >= 0 ? '#047857' : '#b91c1c' }};">
                        {{ $netProfit < 0 ? '-' : '' }}INR {{ number_format(abs($netProfit), 2) }}
                    </div>
                    <div class="kpi-sub">Margin: {{ $profitMargin }}%</div>
                </td>
                <td class="kpi-card cash" style="width: 25%;">
                    <div class="kpi-label">Cash Collected</div>
                    <div class="kpi-val" style="color:#1e40af;">INR {{ number_format($cashRealized, 2) }}</div>
                    <div class="kpi-sub">Due: {{ number_format($totalBalanceDue, 2) }}</div>
                </td>
            </tr>
        </table>

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
                        <div style="font-size:7px; color:#64748b;">Total billed sales across {{ $invoicesCount }} finalized invoices</div>
                    </td>
                    <td class="text-center">SALES</td>
                    <td class="text-right">{{ number_format($netSalesSubtotal, 2) }}</td>
                    <td class="text-right">-</td>
                </tr>
                @if($totalCreditNotesSubtotal > 0)
                <tr>
                    <td class="pl-indent" style="color:#b91c1c;">
                        Less: Credit Notes &amp; Returns ({{ $creditNotesCount }} notes)
                    </td>
                    <td class="text-center" style="color:#b91c1c;">CN</td>
                    <td class="text-right" style="color:#b91c1c;">-{{ number_format($totalCreditNotesSubtotal, 2) }}</td>
                    <td class="text-right">-</td>
                </tr>
                @endif
                @if($totalSalesTax > 0)
                <tr>
                    <td class="pl-indent" style="color:#64748b;">
                        Output GST Collected (Tax Liability)
                    </td>
                    <td class="text-center">GST</td>
                    <td class="text-right" style="color:#64748b;">{{ number_format($totalSalesTax, 2) }}</td>
                    <td class="text-right">-</td>
                </tr>
                @endif
                <tr class="subtotal-row">
                    <td><strong>TOTAL NET OPERATING REVENUE (A)</strong></td>
                    <td class="text-center">REVENUE</td>
                    <td class="text-right">-</td>
                    <td class="text-right" style="color:#047857;"><strong>{{ number_format($netOperatingRevenue, 2) }}</strong></td>
                </tr>

                {{-- SECTION II: EXPENSES --}}
                <tr class="section-head">
                    <td colspan="4">II. Operating Expenses (Expenditure)</td>
                </tr>
                @foreach($categoriesBreakdown as $cat)
                <tr>
                    <td class="pl-indent">
                        <strong>{{ $cat['name'] }}</strong>
                        <span style="font-size:7px; color:#64748b;">({{ $cat['count'] }} entries &bull; Fixed: {{ number_format($cat['fixed'], 2) }} | Var: {{ number_format($cat['variable'], 2) }})</span>
                    </td>
                    <td class="text-center">{{ $cat['code'] }}</td>
                    <td class="text-right">{{ number_format($cat['total'], 2) }}</td>
                    <td class="text-right">-</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td>
                        <strong>TOTAL OPERATING EXPENSES (B)</strong>
                        <div style="font-size:7px; color:#64748b;">Fixed: {{ number_format($totalFixedExpenses, 2) }} | Var: {{ number_format($totalVariableExpenses, 2) }}</div>
                    </td>
                    <td class="text-center">EXPENSE</td>
                    <td class="text-right">-</td>
                    <td class="text-right" style="color:#b91c1c;"><strong>{{ number_format($totalExpenses, 2) }}</strong></td>
                </tr>

                {{-- SECTION III: NET PROFIT / LOSS --}}
                <tr class="grand-row {{ $netProfit >= 0 ? '' : 'loss' }}">
                    <td colspan="2">
                        <strong>{{ $netProfit >= 0 ? 'NET OPERATING PROFIT (A - B)' : 'NET OPERATING LOSS (A - B)' }}</strong>
                        <span style="font-size:7.5px; opacity:.85;">(Profit Margin: {{ $profitMargin }}% &bull; Expense Ratio: {{ $expenseRatio }}%)</span>
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
                @foreach($categoriesBreakdown as $cat)
                <tr>
                    <td><strong>{{ $cat['name'] }}</strong> ({{ $cat['count'] }} entries)</td>
                    <td class="text-center">{{ $cat['code'] }}</td>
                    <td class="text-right">{{ number_format($cat['fixed'], 2) }}</td>
                    <td class="text-right">{{ number_format($cat['variable'], 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($cat['total'], 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal-row">
                    <td colspan="2"><strong>Total Expenses</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalFixedExpenses, 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($totalVariableExpenses, 2) }}</strong></td>
                    <td class="text-right" style="color:#b91c1c;"><strong>{{ number_format($totalExpenses, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>

        {{-- Signoff Footer --}}
        <table class="signoff-table">
            <tr>
                <td style="width: 60%;">
                    <div>* This is an automated system-generated Financial Statement of Profit and Loss.</div>
                    <div>* Based on finalized accrual invoices and registered operating expense ledgers.</div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="sig-line">
                        Authorized Signatory
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
