<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit and Loss Statement</title>
    <style>
        body { font-family: Calibri, 'DejaVu Sans', Arial, sans-serif; font-size: 11pt; color: #1e293b; }
        .hdr-title { font-size: 16pt; font-weight: bold; color: #059669; }
        .hdr-sub { font-size: 10pt; color: #64748b; }
        .tbl-border { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        .tbl-border th { background-color: #f1f5f9; color: #334155; font-weight: bold; border: 1px solid #cbd5e1; padding: 7px 10px; font-size: 10pt; }
        .tbl-border td { border: 1px solid #e2e8f0; padding: 6px 10px; font-size: 10pt; vertical-align: middle; }
        .sec-head { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 11pt; padding: 8px 10px; text-align: left; }
        .tfoot-total td { background-color: #f8fafc; font-weight: bold; border-top: 2px solid #64748b; border-bottom: 2px solid #64748b; }
        .num-cell { text-align: right; }
        .text-center { text-align: center; }
        .profit-text { color: #047857; font-weight: bold; }
        .loss-text { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td colspan="4" class="hdr-title">{{ $setting?->company_name ?: config('app.name', 'My Agency') }}</td>
        </tr>
        @if($setting?->company_address)
        <tr>
            <td colspan="4" class="hdr-sub">{{ $setting->company_address }}</td>
        </tr>
        @endif
        @if($setting?->company_gstin)
        <tr>
            <td colspan="4" class="hdr-sub">GSTIN: {{ $setting->company_gstin }} | Phone: {{ $setting->company_phone }}</td>
        </tr>
        @endif
        <tr>
            <td colspan="4" style="font-size: 13pt; font-weight: bold; color: #0f172a; padding-top: 8px;">
                STATEMENT OF PROFIT & LOSS (P & L)
            </td>
        </tr>
        <tr>
            <td colspan="4" class="hdr-sub">
                Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }} |
                Generated On: {{ now()->format('d-M-Y h:i A') }}
            </td>
        </tr>
    </table>

    {{-- KPI Summary --}}
    <table class="tbl-border">
        <tr>
            <th style="background:#ecfdf5; color:#047857;">Net Operating Revenue</th>
            <th style="background:#fff7ed; color:#c2410c;">Operating Expenses</th>
            <th style="background:{{ $netProfit >= 0 ? '#ecfdf5' : '#fef2f2' }}; color:{{ $netProfit >= 0 ? '#047857' : '#b91c1c' }};">
                {{ $netProfit >= 0 ? 'Net Operating Profit' : 'Net Operating Loss' }}
            </th>
            <th style="background:#eff6ff; color:#1d4ed8;">Cash Collections Realized</th>
        </tr>
        <tr>
            <td class="num-cell" style="font-size:12pt; font-weight:bold; color:#047857;">
                INR {{ number_format($netOperatingRevenue, 2) }}
            </td>
            <td class="num-cell" style="font-size:12pt; font-weight:bold; color:#c2410c;">
                INR {{ number_format($totalExpenses, 2) }}
            </td>
            <td class="num-cell" style="font-size:12pt; font-weight:bold; color:{{ $netProfit >= 0 ? '#047857' : '#b91c1c' }};">
                INR {{ ($netProfit < 0 ? '-' : '') . number_format(abs($netProfit), 2) }} ({{ $profitMargin }}%)
            </td>
            <td class="num-cell" style="font-size:12pt; font-weight:bold; color:#1d4ed8;">
                INR {{ number_format($cashRealized, 2) }}
            </td>
        </tr>
    </table>

    {{-- Formal P&L Statement --}}
    <table class="tbl-border">
        <thead>
            <tr>
                <th colspan="4" class="sec-head">STATEMENT OF PROFIT AND LOSS</th>
            </tr>
            <tr>
                <th style="width:50%;">Particulars / Head of Account</th>
                <th class="text-center" style="width:15%;">Code / Type</th>
                <th class="num-cell" style="width:15%;">Subtotal (INR)</th>
                <th class="num-cell" style="width:20%;">Net Amount (INR)</th>
            </tr>
        </thead>
        <tbody>
            {{-- REVENUE --}}
            <tr style="background:#f8fafc; font-weight:bold;">
                <td colspan="4">I. OPERATING REVENUE / INCOME</td>
            </tr>
            <tr>
                <td style="padding-left:25px;">Gross Invoiced Sales (Subtotal)</td>
                <td class="text-center">SALES</td>
                <td class="num-cell">{{ number_format($netSalesSubtotal, 2) }}</td>
                <td class="num-cell">-</td>
            </tr>
            @if($totalCreditNotesSubtotal > 0)
            <tr>
                <td style="padding-left:25px; color:#dc2626;">Less: Credit Notes & Returns</td>
                <td class="text-center" style="color:#dc2626;">CN</td>
                <td class="num-cell" style="color:#dc2626;">-{{ number_format($totalCreditNotesSubtotal, 2) }}</td>
                <td class="num-cell">-</td>
            </tr>
            @endif
            @if($totalSalesTax > 0)
            <tr>
                <td style="padding-left:25px; color:#64748b;">GST Output Tax Liability (Collected)</td>
                <td class="text-center">GST</td>
                <td class="num-cell" style="color:#64748b;">{{ number_format($totalSalesTax, 2) }}</td>
                <td class="num-cell">-</td>
            </tr>
            @endif
            <tr style="background:#f0fdf4; font-weight:bold;">
                <td colspan="2">TOTAL NET OPERATING REVENUE (A)</td>
                <td class="num-cell">-</td>
                <td class="num-cell" style="color:#047857;">{{ number_format($netOperatingRevenue, 2) }}</td>
            </tr>

            {{-- EXPENSES --}}
            <tr style="background:#f8fafc; font-weight:bold;">
                <td colspan="4">II. OPERATING EXPENSES (EXPENDITURE)</td>
            </tr>
            @foreach($categoriesBreakdown as $cat)
            <tr>
                <td style="padding-left:25px;">{{ $cat['name'] }} (Fixed: {{ number_format($cat['fixed'], 2) }} | Var: {{ number_format($cat['variable'], 2) }})</td>
                <td class="text-center">{{ $cat['code'] }}</td>
                <td class="num-cell">{{ number_format($cat['total'], 2) }}</td>
                <td class="num-cell">-</td>
            </tr>
            @endforeach
            <tr style="background:#fef2f2; font-weight:bold;">
                <td colspan="2">TOTAL OPERATING EXPENSES (B)</td>
                <td class="num-cell">-</td>
                <td class="num-cell" style="color:#b91c1c;">{{ number_format($totalExpenses, 2) }}</td>
            </tr>

            {{-- NET PROFIT / LOSS --}}
            <tr style="background:{{ $netProfit >= 0 ? '#ecfdf5' : '#fef2f2' }}; font-weight:bold; font-size:12pt;">
                <td colspan="2" style="color:{{ $netProfit >= 0 ? '#047857' : '#b91c1c' }};">
                    {{ $netProfit >= 0 ? 'NET OPERATING PROFIT (A - B)' : 'NET OPERATING LOSS (A - B)' }}
                </td>
                <td class="num-cell" style="font-size:10pt;">Margin: {{ $profitMargin }}%</td>
                <td class="num-cell" style="color:{{ $netProfit >= 0 ? '#047857' : '#b91c1c' }};">
                    {{ ($netProfit < 0 ? '-' : '') . number_format(abs($netProfit), 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Category Expense Breakdown --}}
    <table class="tbl-border">
        <thead>
            <tr>
                <th colspan="6" class="sec-head">EXPENSE BREAKDOWN BY CATEGORY</th>
            </tr>
            <tr>
                <th>Category Name</th>
                <th class="text-center">Code</th>
                <th class="num-cell">Fixed Amount (INR)</th>
                <th class="num-cell">Variable Amount (INR)</th>
                <th class="num-cell">Total Spend (INR)</th>
                <th class="num-cell">% of Total Expense</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoriesBreakdown as $cat)
            <tr>
                <td>{{ $cat['name'] }}</td>
                <td class="text-center">{{ $cat['code'] }}</td>
                <td class="num-cell">{{ number_format($cat['fixed'], 2) }}</td>
                <td class="num-cell">{{ number_format($cat['variable'], 2) }}</td>
                <td class="num-cell" style="font-weight:bold;">{{ number_format($cat['total'], 2) }}</td>
                <td class="num-cell">{{ $cat['pct_of_expenses'] }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot-total">
                <td colspan="2">Total Operating Expenses</td>
                <td class="num-cell">{{ number_format($totalFixedExpenses, 2) }}</td>
                <td class="num-cell">{{ number_format($totalVariableExpenses, 2) }}</td>
                <td class="num-cell">{{ number_format($totalExpenses, 2) }}</td>
                <td class="num-cell">100.0%</td>
            </tr>
        </tfoot>
    </table>

    {{-- Monthly Trend Table (if applicable) --}}
    @if(count($monthlyTrend) > 1)
    <table class="tbl-border">
        <thead>
            <tr>
                <th colspan="5" class="sec-head">MONTHLY FINANCIAL PERFORMANCE TREND</th>
            </tr>
            <tr>
                <th>Month</th>
                <th class="num-cell">Invoiced Revenue (INR)</th>
                <th class="num-cell">Operating Expenses (INR)</th>
                <th class="num-cell">Net Profit / (Loss) (INR)</th>
                <th class="num-cell">Profit Margin %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyTrend as $m)
            <tr>
                <td>{{ $m['month_label'] }}</td>
                <td class="num-cell">{{ number_format($m['revenue'], 2) }}</td>
                <td class="num-cell">{{ number_format($m['expense'], 2) }}</td>
                <td class="num-cell" style="font-weight:bold; color:{{ $m['net_profit'] >= 0 ? '#047857' : '#b91c1c' }};">
                    {{ ($m['net_profit'] < 0 ? '-' : '') . number_format(abs($m['net_profit']), 2) }}
                </td>
                <td class="num-cell">{{ $m['margin'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>
</html>
