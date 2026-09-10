@php
    $monthLabel = $payroll->salary_month->format('F Y');
    $issuedOn = optional($payroll->salary_date)->format('d M, Y') ?: now()->format('d M, Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $item->employee_name }} ({{ $item->employee_code }})</title>
    <style>
        @page {
            margin: 20px 24px;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            background: #ffffff;
        }
        .payslip-wrapper {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 20px 24px;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #fe5f04;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }
        .header-logo-cell {
            vertical-align: middle;
            text-align: left;
            width: 65%;
        }
        .header-title-cell {
            vertical-align: middle;
            text-align: right;
            width: 35%;
        }
        .company-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .company-meta {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.35;
        }
        .payslip-badge {
            display: inline-block;
            background: #fff7ed;
            color: #ea580c;
            border: 1px solid #ffedd5;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .payslip-month {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 4px;
        }
        .payslip-date {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Employee Info Table */
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 14px;
            overflow: hidden;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 6px 10px;
            font-size: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .info-label {
            font-weight: 700;
            color: #64748b;
            width: 20%;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.04em;
        }
        .info-val {
            font-weight: 600;
            color: #0f172a;
            width: 30%;
        }

        /* Salary Breakdown Table */
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .salary-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 8px 10px;
            border-bottom: 1px solid #cbd5e1;
            border-right: 1px solid #e2e8f0;
        }
        .salary-table th.section-head {
            background: #0f172a;
            color: #ffffff;
            font-size: 10.5px;
            text-align: center;
            letter-spacing: 0.08em;
            border-right: 1px solid #334155;
        }
        .salary-table th.section-head.deduction {
            background: #b91c1c;
        }
        .salary-table td {
            padding: 6px 10px;
            font-size: 10px;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #e2e8f0;
            color: #334155;
        }
        .salary-table td.text-right {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }
        .salary-table td.sub-total-label {
            background: #f8fafc;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            font-size: 9.5px;
        }
        .salary-table td.sub-total-val {
            background: #f8fafc;
            font-weight: 800;
            color: #0f172a;
            text-align: right;
            font-size: 10.5px;
        }

        /* Summary Cards */
        .summary-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .summary-container td {
            vertical-align: top;
        }
        .net-pay-card {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1.5px solid #fdba74;
            border-radius: 10px;
            padding: 12px 16px;
            width: 100%;
            text-align: left;
        }
        .net-pay-label {
            font-size: 10px;
            font-weight: 800;
            color: #9a3412;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .net-pay-amount {
            font-size: 22px;
            font-weight: 800;
            color: #ea580c;
            margin-top: 2px;
        }
        .net-pay-words {
            font-size: 9px;
            color: #7c2d12;
            margin-top: 4px;
            font-style: italic;
        }

        .stat-mini-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stat-mini-table td {
            padding: 5px 8px;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 10px;
        }
        .stat-mini-label {
            color: #64748b;
            font-weight: 600;
        }
        .stat-mini-val {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
        }

        /* Signatures & Footer */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .footer-table td {
            vertical-align: bottom;
            font-size: 9px;
            color: #64748b;
        }
        .sig-box {
            text-align: center;
            width: 180px;
            float: right;
        }
        .sig-img {
            max-height: 40px;
            display: block;
            margin: 0 auto 4px;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            font-weight: 700;
            color: #334155;
            font-size: 9.5px;
        }
        .note-text {
            font-size: 8.5px;
            color: #94a3b8;
            line-height: 1.4;
            margin-top: 8px;
            text-align: center;
            border-top: 1px dashed #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
<div class="payslip-wrapper">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Company Logo" style="max-height: 48px; max-width: 180px; display: block; margin-bottom: 6px;">
                @endif
                <div class="company-name">{{ $companyName }}</div>
                @if($companyAddress || $companyPhone || $companyEmail)
                    <div class="company-meta">
                        {{ trim(collect([$companyAddress, $companyPhone, $companyEmail])->filter()->implode(' • ')) }}
                    </div>
                @endif
            </td>
            <td class="header-title-cell">
                <span class="payslip-badge">Payslip</span>
                <div class="payslip-month">{{ $monthLabel }}</div>
                <div class="payslip-date">Generated on: {{ $issuedOn }}</div>
            </td>
        </tr>
    </table>

    <!-- Employee & Attendance Info Card -->
    <div class="info-card">
        <table class="info-table">
            <tr>
                <td class="info-label">Employee Name</td>
                <td class="info-val"><strong>{{ $item->employee_name }}</strong></td>
                <td class="info-label">Joining Date</td>
                <td class="info-val">{{ optional($item->date_of_joining)->format('d M Y') ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Employee ID</td>
                <td class="info-val">{{ $item->employee_code ?: 'N/A' }}</td>
                <td class="info-label">Working Days</td>
                <td class="info-val">{{ number_format((float) $item->working_days, 0) }} Days</td>
            </tr>
            <tr>
                <td class="info-label">Designation</td>
                <td class="info-val">{{ $item->designation ?: 'Employee' }}</td>
                <td class="info-label">Present / Paid Days</td>
                <td class="info-val">{{ number_format((float) $item->days_attended, 1) }} / {{ number_format((float) $item->payable_days, 1) }}</td>
            </tr>
            <tr>
                <td class="info-label">UAN / PF No.</td>
                <td class="info-val">{{ $item->uan_no ?: 'N/A' }}</td>
                <td class="info-label">Leave / LOP</td>
                <td class="info-val">{{ number_format((float) $item->leave_days, 1) }} / {{ number_format((float) $item->lop_days, 1) }}</td>
            </tr>
            <tr>
                <td class="info-label">ESI Number</td>
                <td class="info-val">{{ $item->esi_no ?: 'N/A' }}</td>
                <td class="info-label">Bank / Branch</td>
                <td class="info-val">{{ optional($item->employee)->bank_name ? (optional($item->employee)->bank_name . ' (' . (optional($item->employee)->bank_branch ?: 'Main') . ')') : 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Salary Annexure & Deductions Table -->
    <table class="salary-table">
        <thead>
            <tr>
                <th class="section-head" colspan="2">Earnings & Allowances</th>
                <th class="section-head deduction" colspan="2">Deductions</th>
            </tr>
            <tr>
                <th style="width:32%;">Particulars</th>
                <th style="width:18%;text-align:right;">Amount (₹)</th>
                <th style="width:32%;">Particulars</th>
                <th style="width:18%;text-align:right;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary</td>
                <td class="text-right">{{ number_format((float) $item->earned_basic_salary, 2) }}</td>
                <td>Provident Fund (PF {{ number_format((float) $item->pf_employee_percentage, 0) }}%)</td>
                <td class="text-right">{{ number_format((float) $item->pf_employee_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>House Rent Allowance (HRA)</td>
                <td class="text-right">{{ number_format((float) $item->earned_hra, 2) }}</td>
                <td>Employee State Insurance (ESI {{ number_format((float) $item->esi_employee_percentage, 0) }}%)</td>
                <td class="text-right">{{ number_format((float) $item->esi_employee_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>Travel Allowance</td>
                <td class="text-right">{{ number_format((float) $item->earned_travel_allowance, 2) }}</td>
                <td>Professional Tax (PT)</td>
                <td class="text-right">{{ number_format((float) $item->professional_tax, 2) }}</td>
            </tr>
            <tr>
                <td>Special / Other Allowance</td>
                <td class="text-right">{{ number_format((float) $item->earned_other_allowance, 2) }}</td>
                <td>Tax Deducted at Source (TDS)</td>
                <td class="text-right">{{ number_format((float) $item->tds_amount, 2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="text-right"></td>
                <td>Loan / Other Deductions</td>
                <td class="text-right">{{ number_format((float) ($item->loan_deduction + $item->other_deduction), 2) }}</td>
            </tr>
            <tr>
                <td class="sub-total-label">Gross Earnings</td>
                <td class="sub-total-val">₹ {{ number_format((float) $item->earned_gross_salary, 2) }}</td>
                <td class="sub-total-label">Total Deductions</td>
                <td class="sub-total-val" style="color:#b91c1c;">₹ {{ number_format((float) $item->total_deductions, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Summary & Net Pay Block -->
    <table class="summary-container">
        <tr>
            <td style="width:48%;padding-right:12px;">
                <table class="stat-mini-table">
                    <tr>
                        <td class="stat-mini-label">Monthly Gross CTC</td>
                        <td class="stat-mini-val">₹ {{ number_format((float) $item->gross_salary, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="stat-mini-label">Earned Gross Amount</td>
                        <td class="stat-mini-val">₹ {{ number_format((float) $item->earned_gross_salary, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="stat-mini-label">Total Deductions</td>
                        <td class="stat-mini-val" style="color:#b91c1c;">- ₹ {{ number_format((float) $item->total_deductions, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="stat-mini-label">PF & ESI Enrollment</td>
                        <td class="stat-mini-val">{{ $item->use_pf ? 'PF Active' : 'No PF' }} / {{ $item->use_esi ? 'ESI Active' : 'No ESI' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width:52%;">
                <div class="net-pay-card">
                    <div class="net-pay-label">Net Take-Home Salary</div>
                    <div class="net-pay-amount">₹ {{ number_format((float) $item->net_salary, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer with Signatures -->
    <table class="footer-table">
        <tr>
            <td style="width:60%;">
                <div style="font-weight:700;color:#334155;margin-bottom:2px;">Payment Confirmation</div>
                <div>This is a computer-generated salary document and requires no physical stamp if signed digitally.</div>
            </td>
            <td style="width:40%;">
                <div class="sig-box">
                    @if($signatureBase64)
                        <img src="{{ $signatureBase64 }}" alt="Signature" class="sig-img">
                    @else
                        <div style="height:32px;"></div>
                    @endif
                    <div class="sig-line">Authorized Signatory</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="note-text">
        Confidential Document • If you notice any discrepancy in your attendance or salary computations, please contact the HR department.
    </div>
</div>
</body>
</html>

