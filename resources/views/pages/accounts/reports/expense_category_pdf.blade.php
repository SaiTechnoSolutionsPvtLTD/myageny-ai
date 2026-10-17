<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Category Wise Report - {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 14mm 14mm 14mm;
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
        .hdr-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .hdr-left { vertical-align: top; width: 55%; }
        .hdr-right { vertical-align: top; width: 45%; text-align: right; }
        .co-logo { max-height: 38px; max-width: 150px; margin-bottom: 4px; }
        .co-name { font-size: 15px; font-weight: bold; color: #0f172a; line-height: 1.2; letter-spacing: -0.2px; }
        .co-info { font-size: 8px; color: #64748b; margin-top: 3px; line-height: 1.45; }
        .doc-title { font-size: 15px; font-weight: 800; color: #fe5f04; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-period { font-size: 10.5px; font-weight: bold; color: #0f172a; margin-top: 3px; }
        .doc-meta { font-size: 8px; color: #64748b; margin-top: 2px; }

        /* Header Divider */
        .hdr-divider { width: 100%; height: 1px; background-color: #e2e8f0; margin: 8px 0 12px 0; border-bottom: 1.5px solid #fe5f04; }

        /* Summary KPI Cards */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 14px;
        }
        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            vertical-align: middle;
        }
        .kpi-card.total { border-left: 3.5px solid #fe5f04; background: #fffcf9; }
        .kpi-card.fixed { border-left: 3.5px solid #0284c7; background: #fbfdff; }
        .kpi-card.variable { border-left: 3.5px solid #d97706; background: #fffdfa; }
        .kpi-card.stats { border-left: 3.5px solid #10b981; background: #fafffd; }

        .kpi-label { font-size: 7.5px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-val { font-size: 13.5px; font-weight: 800; margin-top: 2px; line-height: 1.1; }
        .kpi-sub { font-size: 7.5px; color: #94a3b8; margin-top: 2px; }

        /* Section Title */
        .sec-title {
            font-size: 9.5px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 14px 0 6px 0;
            padding-left: 6px;
            border-left: 3px solid #fe5f04;
            line-height: 1.2;
        }

        /* Tables */
        .tbl-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 8px;
        }
        .tbl-data th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 8px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1.5px solid #cbd5e1;
            border-left: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .tbl-data td {
            padding: 5.5px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #f8fafc;
            border-right: 1px solid #f8fafc;
            color: #1e293b;
            vertical-align: middle;
        }
        .tbl-data tr:nth-child(even) td { background-color: #fafbfc; }

        .tbl-data tfoot td {
            background-color: #f8fafc;
            font-weight: bold;
            padding: 7px 8px;
            border-top: 1.5px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            color: #0f172a;
        }

        /* Helpers */
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }

        .type-pill {
            display: inline-block;
            padding: 1.5px 6px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .type-fixed { background: #e0f2fe; color: #0369a1; }
        .type-var { background: #fef3c7; color: #b45309; }

        .code-pill {
            background: #f1f5f9;
            color: #475569;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-family: monospace;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 7.5;
        $color = array(0.4, 0.45, 0.55);
        $pdf->page_text($pdf->get_width() - 55, $pdf->get_height() - 22, "{PAGE_NUM} / {PAGE_COUNT}", $font, $size, $color);
        $pdf->page_text(35, $pdf->get_height() - 22, "myAgenci.ai Accounts Financial Intelligence • Confidential", $font, 7, $color);
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
                        @if($setting?->company_phone || $setting?->company_email)
                            <div>Phone: {{ $setting->company_phone ?: '—' }} &bull; Email: {{ $setting->company_email ?: '—' }}</div>
                        @endif
                        @if($setting?->company_gstin)
                            <div><strong>GSTIN:</strong> {{ $setting->company_gstin }}</div>
                        @endif
                    </div>
                </td>
                <td class="hdr-right">
                    <div class="doc-title">EXPENSE CATEGORY WISE REPORT</div>
                    <div class="doc-period">
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M, Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M, Y') }}
                    </div>
                    <div class="doc-meta">
                        Category: <strong>{{ $selectedCategory ? $selectedCategory->name : 'All Categories' }}</strong>
                        @if(!empty($expenseType) && $expenseType !== 'all')
                            &bull; Type: <strong>{{ $expenseType }}</strong>
                        @endif
                    </div>
                    <div class="doc-meta">Generated: {{ now()->format('d M, Y h:i A') }}</div>
                </td>
            </tr>
        </table>

        {{-- Divider --}}
        <div class="hdr-divider"></div>

        {{-- Summary KPI Cards --}}
        <table class="kpi-table">
            <tr>
                <td class="kpi-card total" style="width: 25%;">
                    <div class="kpi-label">Total Expense Spend</div>
                    <div class="kpi-val" style="color: #fe5f04;">₹{{ number_format((float) $grandTotalAmount, 2) }}</div>
                    <div class="kpi-sub">{{ $grandTotalCount }} total transactions recorded</div>
                </td>
                <td class="kpi-card fixed" style="width: 25%;">
                    <div class="kpi-label">Fixed Expenses</div>
                    <div class="kpi-val" style="color: #0284c7;">₹{{ number_format((float) $grandTotalFixed, 2) }}</div>
                    <div class="kpi-sub">
                        {{ $grandTotalAmount > 0 ? round(($grandTotalFixed / $grandTotalAmount) * 100, 1) : 0 }}% of total spend
                    </div>
                </td>
                <td class="kpi-card variable" style="width: 25%;">
                    <div class="kpi-label">Variable Expenses</div>
                    <div class="kpi-val" style="color: #d97706;">₹{{ number_format((float) $grandTotalVariable, 2) }}</div>
                    <div class="kpi-sub">
                        {{ $grandTotalAmount > 0 ? round(($grandTotalVariable / $grandTotalAmount) * 100, 1) : 0 }}% of total spend
                    </div>
                </td>
                <td class="kpi-card stats" style="width: 25%;">
                    <div class="kpi-label">Top Category Spend</div>
                    <div class="kpi-val" style="font-size: 11px; color: #047857; margin-top: 4px;">
                        {{ $topCategory ? $topCategory['category_name'] : 'None' }}
                    </div>
                    <div class="kpi-sub">
                        @if($topCategory && $topCategory['total'] > 0)
                            ₹{{ number_format((float) $topCategory['total'], 2) }} ({{ $topCategory['percentage'] }}%)
                        @else
                            No spend in period
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- Category Summary Table --}}
        <div class="sec-title">1. Category Summary Breakdown</div>
        <table class="tbl-data">
            <thead>
                <tr>
                    <th style="width: 32px;" class="text-center">#</th>
                    <th style="width: 70px;" class="text-center">Code</th>
                    <th style="width: 260px;">Category Name</th>
                    <th style="width: 65px;" class="text-center">Entries</th>
                    <th style="width: 105px;" class="text-right">Fixed (₹)</th>
                    <th style="width: 105px;" class="text-right">Variable (₹)</th>
                    <th style="width: 125px;" class="text-right">Total Amount (₹)</th>
                    <th style="width: 70px;" class="text-right">% Share</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categoryRows as $row)
                    <tr>
                        <td class="text-center" style="color: #94a3b8;">{{ $loop->iteration }}</td>
                        <td class="text-center">
                            @if(!empty($row['category_code']))
                                <span class="code-pill">{{ $row['category_code'] }}</span>
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-size: 8.5px;">{{ $row['category_name'] }}</strong>
                        </td>
                        <td class="text-center" style="font-weight: bold; color: #475569;">
                            {{ $row['count'] }}
                        </td>
                        <td class="text-right" style="color: #0369a1;">
                            {{ number_format((float) $row['fixed'], 2) }}
                        </td>
                        <td class="text-right" style="color: #b45309;">
                            {{ number_format((float) $row['variable'], 2) }}
                        </td>
                        <td class="text-right" style="font-weight: bold; color: #0f172a; font-size: 9px;">
                            {{ number_format((float) $row['total'], 2) }}
                        </td>
                        <td class="text-right" style="font-weight: bold; color: #475569;">
                            {{ number_format((float) $row['percentage'], 2) }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="color: #94a3b8; padding: 14px;">No category records found for this period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; text-transform: uppercase; font-size: 8px; letter-spacing: 0.3px;">
                        Grand Total Summary:
                    </td>
                    <td class="text-center" style="font-size: 8.5px;">{{ $grandTotalCount }}</td>
                    <td class="text-right" style="color: #0369a1;">{{ number_format((float) $grandTotalFixed, 2) }}</td>
                    <td class="text-right" style="color: #b45309;">{{ number_format((float) $grandTotalVariable, 2) }}</td>
                    <td class="text-right" style="color: #fe5f04; font-size: 9.5px;">₹{{ number_format((float) $grandTotalAmount, 2) }}</td>
                    <td class="text-right">100.00%</td>
                </tr>
            </tfoot>
        </table>

        {{-- Detailed Transactions Table --}}
        <div class="sec-title" style="margin-top: 14px;">2. Detailed Expense Line Items</div>
        <table class="tbl-data">
            <thead>
                <tr>
                    <th style="width: 28px;" class="text-center">#</th>
                    <th style="width: 70px;" class="text-center">Date</th>
                    <th style="width: 105px;">Expense #</th>
                    <th style="width: 95px;">Invoice #</th>
                    <th style="width: 120px;">Category</th>
                    <th style="width: 110px;">Subcategory</th>
                    <th style="width: 125px;">Vendor</th>
                    <th style="width: 65px;" class="text-center">Type</th>
                    <th style="width: 95px;" class="text-right">Amount (₹)</th>
                    <th style="width: auto;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allExpenses as $idx => $exp)
                    <tr>
                        <td class="text-center" style="color: #94a3b8;">{{ $idx + 1 }}</td>
                        <td class="text-center" style="color: #475569;">
                            {{ $exp->expense_date ? $exp->expense_date->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            <strong style="color: #0f172a;">{{ $exp->expense_number }}</strong>
                        </td>
                        <td style="color: #475569;">
                            {{ $exp->invoice_no ?: '—' }}
                        </td>
                        <td>
                            {{ $exp->category ? $exp->category->name : '—' }}
                        </td>
                        <td style="color: #475569;">
                            {{ $exp->subcategory ? $exp->subcategory->name : '—' }}
                        </td>
                        <td>
                            {{ $exp->vendor ? ($exp->vendor->vendor_name ?: $exp->vendor->company_name) : '—' }}
                        </td>
                        <td class="text-center">
                            <span class="type-pill {{ $exp->expense_type === 'Fixed' ? 'type-fixed' : 'type-var' }}">
                                {{ $exp->expense_type }}
                            </span>
                        </td>
                        <td class="text-right" style="font-weight: bold; color: #0f172a;">
                            {{ number_format((float) $exp->amount, 2) }}
                        </td>
                        <td style="font-size: 7.5px; color: #64748b;">
                            {{ $exp->remarks ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center" style="color: #94a3b8; padding: 14px;">No transaction records available.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8" style="text-align: right; text-transform: uppercase; font-size: 8px; letter-spacing: 0.3px;">
                        Total Transactions Amount:
                    </td>
                    <td class="text-right" style="color: #fe5f04; font-size: 9.5px;">₹{{ number_format((float) $grandTotalAmount, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
