<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Category Wise Report - {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</title>
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
        .co-name { font-size: 14px; font-weight: bold; color: #000000; line-height: 1.2; letter-spacing: -0.2px; }
        .co-info { font-size: 8px; color: #000000; margin-top: 3px; line-height: 1.4; }
        .doc-title { font-size: 14px; font-weight: bold; color: #000000; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-period { font-size: 10px; font-weight: bold; color: #000000; margin-top: 3px; }
        .doc-meta { font-size: 8px; color: #000000; margin-top: 2px; }

        /* Header Divider */
        .hdr-divider { width: 100%; height: 1px; background-color: #cbd5e1; margin: 8px 0 10px 0; }

        /* Section Title */
        .sec-title {
            font-size: 9px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 12px 0 5px 0;
            line-height: 1.2;
        }

        /* Tables - Single clean border */
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
            font-weight: bold;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .tbl-data td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            color: #000000;
            vertical-align: middle;
        }
        .tbl-data tr:nth-child(even) td { background-color: #fafbfc; }

        .tbl-data tfoot td {
            background-color: #f8fafc;
            font-weight: bold;
            padding: 6px;
            border: 1px solid #cbd5e1;
            color: #000000;
        }

        /* Helpers */
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }

        .type-pill {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
        }

        .code-pill {
            color: #000000;
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
        $color = array(0, 0, 0);
        $pdf->page_text($pdf->get_width() - 80, $pdf->get_height() - 25, "{PAGE_NUM} / {PAGE_COUNT}", $font, $size, $color);
        $pdf->page_text(45, $pdf->get_height() - 25, "myAgenci.ai Accounts Financial Intelligence • Confidential", $font, 7, $color);
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

        {{-- Category Summary Table --}}
        <div class="sec-title">1. Category Summary Breakdown</div>
        <table class="tbl-data">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 8%;" class="text-center">Code</th>
                    <th style="width: 27%;">Category Name</th>
                    <th style="width: 8%;" class="text-center">Entries</th>
                    <th style="width: 13%;" class="text-right">Fixed (₹)</th>
                    <th style="width: 13%;" class="text-right">Variable (₹)</th>
                    <th style="width: 15%;" class="text-right">Total Amount (₹)</th>
                    <th style="width: 11%;" class="text-right">% Share</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categoryRows as $row)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-center">
                            @if(!empty($row['category_code']))
                                <span class="code-pill">{{ $row['category_code'] }}</span>
                            @else
                                <span>—</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $row['category_name'] }}</strong>
                        </td>
                        <td class="text-center">
                            {{ $row['count'] }}
                        </td>
                        <td class="text-right">
                            {{ number_format((float) $row['fixed'], 2) }}
                        </td>
                        <td class="text-right">
                            {{ number_format((float) $row['variable'], 2) }}
                        </td>
                        <td class="text-right">
                            <strong>{{ number_format((float) $row['total'], 2) }}</strong>
                        </td>
                        <td class="text-right">
                            {{ number_format((float) $row['percentage'], 2) }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 12px;">No category records found for this period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; text-transform: uppercase;">
                        Grand Total Summary:
                    </td>
                    <td class="text-center">{{ $grandTotalCount }}</td>
                    <td class="text-right">{{ number_format((float) $grandTotalFixed, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $grandTotalVariable, 2) }}</td>
                    <td class="text-right">₹{{ number_format((float) $grandTotalAmount, 2) }}</td>
                    <td class="text-right">100.00%</td>
                </tr>
            </tfoot>
        </table>

        {{-- Detailed Transactions Table --}}
        <div class="sec-title" style="margin-top: 12px;">2. Detailed Expense Line Items</div>
        <table class="tbl-data">
            <thead>
                <tr>
                    <th style="width: 4%;" class="text-center">#</th>
                    <th style="width: 10%;" class="text-center">Date</th>
                    <th style="width: 14%;">Expense #</th>
                    <th style="width: 9%;">Invoice #</th>
                    <th style="width: 13%;">Category</th>
                    <th style="width: 11%;">Subcategory</th>
                    <th style="width: 10%;">Vendor</th>
                    <th style="width: 7%;" class="text-center">Type</th>
                    <th style="width: 12%;" class="text-right">Amount (₹)</th>
                    <th style="width: 10%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allExpenses as $idx => $exp)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center">
                            {{ $exp->expense_date ? $exp->expense_date->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            <strong>{{ $exp->expense_number }}</strong>
                        </td>
                        <td>
                            {{ $exp->invoice_no ?: '—' }}
                        </td>
                        <td>
                            {{ $exp->category ? $exp->category->name : '—' }}
                        </td>
                        <td>
                            {{ $exp->subcategory ? $exp->subcategory->name : '—' }}
                        </td>
                        <td>
                            {{ $exp->vendor ? ($exp->vendor->vendor_name ?: $exp->vendor->company_name) : '—' }}
                        </td>
                        <td class="text-center">
                            <span class="type-pill">
                                {{ $exp->expense_type }}
                            </span>
                        </td>
                        <td class="text-right">
                            <strong>{{ number_format((float) $exp->amount, 2) }}</strong>
                        </td>
                        <td>
                            {{ $exp->remarks ?: '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center" style="padding: 12px;">No transaction records available.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8" style="text-align: right; text-transform: uppercase;">
                        Total Transactions Amount:
                    </td>
                    <td class="text-right">₹{{ number_format((float) $grandTotalAmount, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
