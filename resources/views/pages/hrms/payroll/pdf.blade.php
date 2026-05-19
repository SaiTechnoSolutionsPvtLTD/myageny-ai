@php
    $monthLabel = $payroll->salary_month->format('M-y');
    $issuedOn = optional($payroll->salary_date)->format('jS M Y') ?: now()->format('jS M Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip {{ $item->employee_code }}</title>
    <style>
        body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111;margin:22px}
        .header{text-align:center;margin-bottom:12px}
        .header h1{margin:0;font-size:20px;letter-spacing:.4px;text-transform:uppercase}
        .header .meta{margin-top:4px;font-size:11px;color:#555}
        .title-row{display:flex;justify-content:space-between;align-items:flex-end;margin:14px 0 8px}
        .title-row .slip{font-size:18px;font-weight:700}
        .title-row .month{font-size:18px;font-weight:700}
        .issued{text-align:right;font-size:11px;color:#555;margin-bottom:8px}
        table{width:100%;border-collapse:collapse}
        .info td{padding:6px 8px;border:1px solid #000}
        .info td.label{width:24%;font-weight:700;background:#f6f6f6}
        .section{margin-top:12px}
        .salary th,.salary td{border:1px solid #000;padding:7px 8px}
        .salary th{background:#f6f6f6;font-size:11px;text-transform:uppercase}
        .salary .subhead{background:#fcfcfc;font-weight:700;text-align:left}
        .text-right{text-align:right}
        .totals{margin-top:14px;width:45%;margin-left:auto}
        .totals td{padding:6px 8px;border:1px solid #000}
        .totals td.label{font-weight:700;background:#f6f6f6}
        .signature{margin-top:34px;text-align:right}
        .signature img{height:44px;display:block;margin-left:auto;margin-bottom:4px}
        .signature .line{display:inline-block;min-width:180px;border-top:1px solid #000;padding-top:6px;text-align:center;font-weight:700}
        .note{margin-top:6px;font-size:10px;color:#666}
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyName }}</h1>
        @if($companyAddress || $companyPhone || $companyEmail)
            <div class="meta">
                {{ trim(collect([$companyAddress, $companyPhone, $companyEmail])->filter()->implode(' | ')) }}
            </div>
        @endif
    </div>

    <div class="title-row">
        <div class="slip">Salary Slip</div>
        <div class="month">{{ $monthLabel }}</div>
    </div>
    <div class="issued">{{ $issuedOn }}</div>

    <table class="info">
        <tr>
            <td class="label">Employee Name</td>
            <td>{{ $item->employee_name }}</td>
            <td class="label">Date of Joining</td>
            <td>{{ optional($item->date_of_joining)->format('d-m-Y') ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Employee ID</td>
            <td>{{ $item->employee_code ?: 'N/A' }}</td>
            <td class="label">Total Working Days</td>
            <td>{{ number_format((float) $item->working_days, 0) }}</td>
        </tr>
        <tr>
            <td class="label">Designation</td>
            <td>{{ $item->designation ?: 'Employee' }}</td>
            <td class="label">Days Attended</td>
            <td>{{ number_format((float) $item->days_attended, 2) }}</td>
        </tr>
        <tr>
            <td class="label">UAN Number</td>
            <td>{{ $item->uan_no ?: 'N/A' }}</td>
            <td class="label">LOP</td>
            <td>{{ number_format((float) $item->lop_days, 2) }}</td>
        </tr>
        <tr>
            <td class="label">ESI Number</td>
            <td>{{ $item->esi_no ?: 'N/A' }}</td>
            <td class="label">Payable Days</td>
            <td>{{ number_format((float) $item->payable_days, 2) }}</td>
        </tr>
    </table>

    <div class="section">
        <table class="salary">
            <thead>
                <tr>
                    <th colspan="2">Salary Annexure</th>
                    <th colspan="2">Deductions</th>
                </tr>
                <tr>
                    <th>Particulars</th>
                    <th class="text-right">Amount</th>
                    <th>Particulars</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="text-right">{{ number_format((float) $item->earned_basic_salary, 2) }}</td>
                    <td>PF ({{ number_format((float) $item->pf_employee_percentage, 2) }}%)</td>
                    <td class="text-right">{{ number_format((float) $item->pf_employee_contribution, 2) }}</td>
                </tr>
                <tr>
                    <td>HRA</td>
                    <td class="text-right">{{ number_format((float) $item->earned_hra, 2) }}</td>
                    <td>ESI ({{ number_format((float) $item->esi_employee_percentage, 2) }}%)</td>
                    <td class="text-right">{{ number_format((float) $item->esi_employee_contribution, 2) }}</td>
                </tr>
                <tr>
                    <td>Travel Allowance</td>
                    <td class="text-right">{{ number_format((float) $item->earned_travel_allowance, 2) }}</td>
                    <td>Professional Tax</td>
                    <td class="text-right">{{ number_format((float) $item->professional_tax, 2) }}</td>
                </tr>
                <tr>
                    <td>Other Allowance</td>
                    <td class="text-right">{{ number_format((float) $item->earned_other_allowance, 2) }}</td>
                    <td>TDS</td>
                    <td class="text-right">{{ number_format((float) $item->tds_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="subhead">Total</td>
                    <td class="text-right subhead">{{ number_format((float) $item->earned_gross_salary, 2) }}</td>
                    <td>Loan / Other Deductions</td>
                    <td class="text-right">{{ number_format((float) ($item->loan_deduction + $item->other_deduction), 2) }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td class="subhead">Total</td>
                    <td class="text-right subhead">{{ number_format((float) $item->total_deductions, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <table class="totals">
        <tr>
            <td class="label">CTC Salary</td>
            <td class="text-right">{{ number_format((float) $item->gross_salary, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Net Salary</td>
            <td class="text-right">{{ number_format((float) $item->net_salary, 2) }}</td>
        </tr>
    </table>

    <div class="signature">
        @if($signatureBase64)
            <img src="{{ $signatureBase64 }}" alt="Signature">
        @endif
        <div class="line">Employer Signature</div>
        <div class="note">PF: {{ $item->use_pf ? 'With PF' : 'Without PF' }} | ESI: {{ $item->use_esi ? 'With ESI' : 'Without ESI' }}</div>
    </div>
</body>
</html>
