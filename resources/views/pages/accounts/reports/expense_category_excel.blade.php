<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expense Category Wise Report</title>
    <style>
        body { font-family: Calibri, 'DejaVu Sans', Arial, sans-serif; font-size: 11pt; color: #1e293b; }
        .hdr-title { font-size: 16pt; font-weight: bold; color: #fe5f04; }
        .hdr-sub { font-size: 10pt; color: #64748b; }
        .tbl-border { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        .tbl-border th { background-color: #f1f5f9; color: #334155; font-weight: bold; border: 1px solid #cbd5e1; padding: 7px 10px; font-size: 10pt; }
        .tbl-border td { border: 1px solid #e2e8f0; padding: 6px 10px; font-size: 10pt; vertical-align: middle; }
        .sec-head { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 11pt; padding: 8px 10px; text-align: left; }
        .tfoot-total td { background-color: #f8fafc; font-weight: bold; border-top: 2px solid #64748b; border-bottom: 2px solid #64748b; }
        .num-cell { text-align: right; }
        .text-center { text-align: center; }
        .badge-fixed { color: #0369a1; font-weight: bold; }
        .badge-var { color: #b45309; font-weight: bold; }
    </style>
</head>
<body>
    {{-- Header --}}
    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td colspan="8" class="hdr-title">{{ $setting?->company_name ?: config('app.name', 'My Agency') }}</td>
        </tr>
        @if($setting?->company_address)
        <tr>
            <td colspan="8" class="hdr-sub">{{ $setting->company_address }}</td>
        </tr>
        @endif
        @if($setting?->company_gstin)
        <tr>
            <td colspan="8" class="hdr-sub">GSTIN: {{ $setting->company_gstin }} | Phone: {{ $setting->company_phone }}</td>
        </tr>
        @endif
        <tr>
            <td colspan="8" style="font-size: 13pt; font-weight: bold; color: #0f172a; padding-top: 8px;">
                EXPENSE CATEGORY WISE REPORT
            </td>
        </tr>
        <tr>
            <td colspan="8" class="hdr-sub">
                Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }} |
                Category: {{ $selectedCategory ? $selectedCategory->name : 'All Categories' }} |
                Type: {{ ucfirst($expenseType) }} |
                Generated On: {{ now()->format('d-M-Y h:i A') }}
            </td>
        </tr>
    </table>

    {{-- Summary KPIs --}}
    <table class="tbl-border">
        <tr>
            <th colspan="2" style="background:#fff7ed; color:#c2410c;">Total Expense Spend</th>
            <th colspan="2" style="background:#f0f9ff; color:#0369a1;">Fixed Expenses</th>
            <th colspan="2" style="background:#fffbeb; color:#b45309;">Variable Expenses</th>
            <th colspan="2" style="background:#f8fafc; color:#334155;">Total Transactions</th>
        </tr>
        <tr>
            <td colspan="2" class="num-cell" style="font-size:12pt; font-weight:bold; color:#fe5f04;">
                INR {{ number_format((float) $grandTotalAmount, 2) }}
            </td>
            <td colspan="2" class="num-cell" style="font-weight:bold; color:#0369a1;">
                INR {{ number_format((float) $grandTotalFixed, 2) }}
            </td>
            <td colspan="2" class="num-cell" style="font-weight:bold; color:#b45309;">
                INR {{ number_format((float) $grandTotalVariable, 2) }}
            </td>
            <td colspan="2" class="text-center" style="font-weight:bold;">
                {{ $grandTotalCount }}
            </td>
        </tr>
    </table>

    <br/>

    {{-- Section 1: Category Summary Table --}}
    <table class="tbl-border">
        <thead>
            <tr>
                <th colspan="8" class="sec-head">1. CATEGORY WISE SUMMARY BREAKDOWN</th>
            </tr>
            <tr>
                <th style="width: 45px;" class="text-center">S.No</th>
                <th style="width: 80px;">Code</th>
                <th style="width: 240px;">Category Name</th>
                <th style="width: 80px;" class="text-center">Entries</th>
                <th style="width: 140px;" class="num-cell">Fixed Amount (INR)</th>
                <th style="width: 140px;" class="num-cell">Variable Amount (INR)</th>
                <th style="width: 150px;" class="num-cell">Total Amount (INR)</th>
                <th style="width: 80px;" class="num-cell">% Share</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categoryRows as $idx => $cat)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $cat['category_code'] ?: '—' }}</td>
                    <td><strong>{{ $cat['category_name'] }}</strong></td>
                    <td class="text-center">{{ $cat['count'] }}</td>
                    <td class="num-cell">{{ number_format((float) $cat['fixed'], 2) }}</td>
                    <td class="num-cell">{{ number_format((float) $cat['variable'], 2) }}</td>
                    <td class="num-cell" style="font-weight:bold;">{{ number_format((float) $cat['total'], 2) }}</td>
                    <td class="num-cell">{{ number_format((float) $cat['percentage'], 2) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="color: #64748b;">No category expense data found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="tfoot-total">
                <td colspan="3" style="text-align: right; font-weight: bold;">GRAND TOTAL:</td>
                <td class="text-center" style="font-weight: bold;">{{ $grandTotalCount }}</td>
                <td class="num-cell" style="font-weight: bold;">{{ number_format((float) $grandTotalFixed, 2) }}</td>
                <td class="num-cell" style="font-weight: bold;">{{ number_format((float) $grandTotalVariable, 2) }}</td>
                <td class="num-cell" style="font-weight: bold; color:#fe5f04;">{{ number_format((float) $grandTotalAmount, 2) }}</td>
                <td class="num-cell" style="font-weight: bold;">100.00%</td>
            </tr>
        </tfoot>
    </table>

    <br/>

    {{-- Section 2: Detailed Transactions List --}}
    <table class="tbl-border">
        <thead>
            <tr>
                <th colspan="9" class="sec-head">2. DETAILED EXPENSE TRANSACTIONS</th>
            </tr>
            <tr>
                <th style="width: 45px;" class="text-center">S.No</th>
                <th style="width: 95px;">Date</th>
                <th style="width: 140px;">Expense No</th>
                <th style="width: 130px;">Invoice / Bill No</th>
                <th style="width: 160px;">Category</th>
                <th style="width: 160px;">Subcategory</th>
                <th style="width: 160px;">Vendor</th>
                <th style="width: 80px;" class="text-center">Type</th>
                <th style="width: 140px;" class="num-cell">Amount (INR)</th>
            </tr>
        </thead>
        <tbody>
            @php $lineIndex = 1; @endphp
            @forelse($allExpenses as $exp)
                <tr>
                    <td class="text-center">{{ $lineIndex++ }}</td>
                    <td>{{ $exp->expense_date ? $exp->expense_date->format('d-M-Y') : '—' }}</td>
                    <td><strong>{{ $exp->expense_number }}</strong></td>
                    <td>{{ $exp->invoice_no ?: '—' }}</td>
                    <td>{{ $exp->category ? $exp->category->name : '—' }}</td>
                    <td>{{ $exp->subcategory ? $exp->subcategory->name : '—' }}</td>
                    <td>{{ $exp->vendor ? ($exp->vendor->vendor_name ?: $exp->vendor->company_name) : '—' }}</td>
                    <td class="text-center {{ $exp->expense_type === 'Fixed' ? 'badge-fixed' : 'badge-var' }}">
                        {{ $exp->expense_type }}
                    </td>
                    <td class="num-cell" style="font-weight:bold;">{{ number_format((float) $exp->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="color: #64748b;">No detailed transaction records found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="tfoot-total">
                <td colspan="8" style="text-align: right; font-weight: bold;">TOTAL TRANSACTIONS AMOUNT:</td>
                <td class="num-cell" style="font-weight: bold; color:#fe5f04;">{{ number_format((float) $grandTotalAmount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
