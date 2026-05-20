@extends('layouts.app')

@section('title', 'Run Payroll')

@push('styles')
<style>
.pr-page{display:flex;flex-direction:column;min-height:100%;background:#f4f5f7}
.pr-topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:0 28px;min-height:60px;background:#fff;border-bottom:1px solid #e1dee3}
.pr-body{padding:22px 28px 34px;display:flex;flex-direction:column;gap:16px}
.pr-title{font-size:18px;font-weight:800;color:#121212}
.pr-breadcrumb{margin-top:2px;color:#9e9e9e;font-size:12px}
.pr-card{background:#fff;border:1px solid #e1dee3;border-radius:16px;padding:20px}
.pr-form-row{display:flex;gap:14px;flex-wrap:wrap;align-items:end}
.pr-field{display:flex;flex-direction:column;gap:8px;min-width:180px}
.pr-label{font-size:13px;font-weight:700;color:#444}
.pr-input{height:44px;border:1px solid #e1dee3;border-radius:10px;padding:0 14px;background:#fff;color:#20222a;font-size:14px}
.pr-textarea{min-height:90px;border:1px solid #e1dee3;border-radius:10px;padding:12px 14px;background:#fff;color:#20222a;font-size:14px}
.pr-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:10px;border:1px solid transparent;background:#fff;color:#121212;text-decoration:none;font-size:13px;font-weight:700}
.pr-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);border-color:#fe5f04;color:#fff}
.pr-btn-ghost{border-color:#e1dee3}
.pr-meta-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.pr-meta{padding:14px;border:1px solid #ece8e4;border-radius:14px;background:#faf8f6}
.pr-meta strong{display:block;font-size:24px;color:#121212}
.pr-meta span{font-size:12px;color:#8a8a8a}
.pr-table{width:100%;border-collapse:collapse;min-width:2550px}
.pr-table th,.pr-table td{padding:10px 12px;border-bottom:1px solid #f0eef2;text-align:left;vertical-align:top}
.pr-table th{font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#9e9e9e;background:#fafafa}
.pr-table th{white-space:nowrap}
.pr-table td{min-width:110px}
.pr-table td:first-child{min-width:220px}
.pr-table input[type="number"],.pr-table input[type="text"]{width:100%;min-width:96px;height:40px;border:1px solid #e1dee3;border-radius:8px;padding:0 12px;font-size:13px}
.pr-table input[readonly]{background:#f8f8f8}
.pr-check{width:18px;height:18px}
.pr-emp-name{font-weight:700;color:#121212}
.pr-emp-sub{margin-top:2px;font-size:11px;color:#8a8a8a}
@media (max-width: 900px){
    .pr-topbar{padding:16px 20px;flex-direction:column;align-items:flex-start}
    .pr-body{padding:16px 20px 24px}
    .pr-meta-grid{grid-template-columns:1fr 1fr}
}
</style>
@endpush

@section('content')
<div class="pr-page">
    <div class="pr-topbar">
        <div>
            <div class="pr-title">Run Payroll</div>
            <div class="pr-breadcrumb">HRMS > Payroll > Create</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('payroll.index') }}" class="pr-btn pr-btn-ghost">Back</a>
        </div>
    </div>

    <div class="pr-body">
        @if($errors->any())
            <div class="pr-card" style="border-color:#fecaca;background:#fef2f2;color:#991b1b;">
                Please review the payroll inputs and try again.
            </div>
        @endif

        <div class="pr-card">
            <form method="GET" action="{{ route('payroll.create') }}" class="pr-form-row">
                <div class="pr-field">
                    <label class="pr-label">Salary Month</label>
                    <input type="month" name="month" class="pr-input" value="{{ $selectedMonth->format('Y-m') }}">
                </div>
                <button type="submit" class="pr-btn pr-btn-primary">Load Employees</button>
                <a href="{{ route('settings.payroll.index') }}" class="pr-btn pr-btn-ghost">Payroll Settings</a>
            </form>
        </div>

        <div class="pr-meta-grid">
            <div class="pr-meta"><strong>{{ $rows->count() }}</strong><span>Verified Employees</span></div>
            <div class="pr-meta"><strong>{{ $workingDays }}</strong><span>Total Working Days</span></div>
            <div class="pr-meta"><strong>{{ $selectedMonth->format('M Y') }}</strong><span>Payroll Month</span></div>
            <div class="pr-meta"><strong id="overallNet">Rs 0.00</strong><span>Total Net Salary</span></div>
        </div>

        <form method="POST" action="{{ route('payroll.store') }}" id="payrollForm">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">

            <div class="pr-card">
                <div class="pr-form-row">
                    <div class="pr-field">
                        <label class="pr-label">Salary Date</label>
                        <input type="date" name="salary_date" class="pr-input" value="{{ old('salary_date', now()->toDateString()) }}">
                    </div>
                    <div class="pr-field" style="flex:1;min-width:320px;">
                        <label class="pr-label">Notes</label>
                        <textarea name="notes" class="pr-textarea" placeholder="Optional payroll notes">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="pr-card">
                <div style="font-size:16px;font-weight:700;color:#121212;">Employee Payroll Sheet</div>
                <div style="margin-top:4px;color:#9e9e9e;font-size:12px;">Attendance values can be adjusted before saving. Gross salary automatically splits into Basic 50%, HRA 30%, Travel 10%, and Other 10%. PF and ESI follow the same formula used in employee onboarding.</div>
                <div style="margin-top:8px;color:#7c8595;font-size:12px;">Payable Days = Present + Leave, capped by Working Days. LOP = Working Days - Payable Days.</div>
                <div style="margin-top:12px;padding:12px 14px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8;font-size:12px;font-weight:600;">
                    PF formula: if Gross Salary is above 21,000, PF uses fixed 15,000. Otherwise PF uses Basic + Travel + Other. ESI formula: 4% of Gross Salary. Both PF and ESI round to the nearest whole number.
                </div>
                <div style="margin-top:8px;padding:12px 14px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;font-size:12px;font-weight:600;">
                    Paid Leave: {{ number_format((float) $payrollSettings->paid_leave_days, 2, '.', '') }} day(s) per month. Permission: {{ (int) $payrollSettings->permission_days_per_month }} day(s) per month up to {{ number_format((float) $payrollSettings->permission_hours_per_day, 2, '.', '') }} hour(s) per day. Late login after {{ \Carbon\Carbon::createFromFormat('H:i:s', (string) $payrollSettings->grace_login_time)->format('h:i A') }} consumes one permission day.
                </div>

                <div style="overflow-x:auto;margin-top:16px;">
                    <table class="pr-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Working Days</th>
                                <th>Present</th>
                                <th>Leave</th>
                                <th>LOP</th>
                                <th>Payable Days</th>
                                <th>With PF</th>
                                <th>With ESI</th>
                                <th>PF Emp %</th>
                                <th>PF Empr %</th>
                                <th>ESI Emp %</th>
                                <th>ESI Empr %</th>
                                <th>ESI Limit</th>
                                <th>Gross</th>
                                <th>Basic</th>
                                <th>HRA</th>
                                <th>Travel</th>
                                <th>Other</th>
                                <th>PF</th>
                                <th>ESI</th>
                                <th>PT</th>
                                <th>TDS</th>
                                <th>Loan</th>
                                <th>Other Ded.</th>
                                <th>Total Ded.</th>
                                <th>Net Salary</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $index => $row)
                                <tr class="payroll-row" data-index="{{ $index }}">
                                    <td>
                                        <div class="pr-emp-name">{{ $row['employee_name'] }}</div>
                                        <div class="pr-emp-sub">{{ $row['employee_code'] }} | {{ $row['designation'] }}</div>
                                        <input type="hidden" name="items[{{ $index }}][employee_onboarding_id]" value="{{ $row['employee_onboarding_id'] }}">
                                    </td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][working_days]" value="{{ old("items.$index.working_days", $row['working_days']) }}" data-key="working_days"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][days_attended]" value="{{ old("items.$index.days_attended", $row['days_attended']) }}" data-key="days_attended"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][leave_days]" value="{{ old("items.$index.leave_days", $row['leave_days']) }}" data-key="leave_days"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][lop_days]" value="{{ old("items.$index.lop_days", $row['lop_days']) }}" data-key="lop_days" readonly></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][payable_days]" value="{{ old("items.$index.payable_days", $row['payable_days']) }}" data-key="payable_days" readonly></td>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][use_pf]" value="0">
                                        <input type="checkbox" class="pr-check" name="items[{{ $index }}][use_pf]" value="1" data-key="use_pf" @checked(old("items.$index.use_pf", $row['use_pf']))>
                                    </td>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][use_esi]" value="0">
                                        <input type="checkbox" class="pr-check" name="items[{{ $index }}][use_esi]" value="1" data-key="use_esi" @checked(old("items.$index.use_esi", $row['use_esi']))>
                                    </td>
                                    <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][pf_employee_percentage]" value="{{ old("items.$index.pf_employee_percentage", $row['pf_employee_percentage']) }}" data-key="pf_employee_percentage"></td>
                                    <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][pf_employer_percentage]" value="{{ old("items.$index.pf_employer_percentage", $row['pf_employer_percentage']) }}" data-key="pf_employer_percentage"></td>
                                    <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][esi_employee_percentage]" value="{{ old("items.$index.esi_employee_percentage", $row['esi_employee_percentage']) }}" data-key="esi_employee_percentage"></td>
                                    <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][esi_employer_percentage]" value="{{ old("items.$index.esi_employer_percentage", $row['esi_employer_percentage']) }}" data-key="esi_employer_percentage"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][esi_salary_limit]" value="{{ old("items.$index.esi_salary_limit", $row['esi_salary_limit']) }}" data-key="esi_salary_limit"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][gross_salary]" value="{{ old("items.$index.gross_salary", $row['gross_salary']) }}" data-key="gross_salary"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][basic_salary]" value="{{ old("items.$index.basic_salary", $row['basic_salary']) }}" data-key="basic_salary"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][hra]" value="{{ old("items.$index.hra", $row['hra']) }}" data-key="hra"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][travel_allowance]" value="{{ old("items.$index.travel_allowance", $row['travel_allowance']) }}" data-key="travel_allowance"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][other_allowance]" value="{{ old("items.$index.other_allowance", $row['other_allowance']) }}" data-key="other_allowance"></td>
                                    <td><input type="text" value="{{ number_format((float) $row['pf_employee_contribution'], 2, '.', '') }}" data-key="pf_value" readonly></td>
                                    <td><input type="text" value="{{ number_format((float) $row['esi_employee_contribution'], 2, '.', '') }}" data-key="esi_value" readonly></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][professional_tax]" value="{{ old("items.$index.professional_tax", $row['professional_tax']) }}" data-key="professional_tax"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][tds_amount]" value="{{ old("items.$index.tds_amount", $row['tds_amount']) }}" data-key="tds_amount"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][loan_deduction]" value="{{ old("items.$index.loan_deduction", $row['loan_deduction']) }}" data-key="loan_deduction"></td>
                                    <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][other_deduction]" value="{{ old("items.$index.other_deduction", $row['other_deduction']) }}" data-key="other_deduction"></td>
                                    <td><input type="text" value="{{ number_format((float) $row['total_deductions'], 2, '.', '') }}" data-key="total_deductions" readonly></td>
                                    <td><input type="text" value="{{ number_format((float) $row['net_salary'], 2, '.', '') }}" data-key="net_salary" readonly></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="pr-btn pr-btn-primary">Save Payroll</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = Array.from(document.querySelectorAll('.payroll-row'));
    const overallNet = document.getElementById('overallNet');

    function amount(input) {
        const value = parseFloat(input?.value || '0');
        return Number.isFinite(value) ? value : 0;
    }

    function setValue(input, value) {
        if (input) {
            input.value = Number(value).toFixed(2);
        }
    }

    function syncRow(row) {
        const workingDays = amount(row.querySelector('[data-key="working_days"]'));
        const daysAttended = amount(row.querySelector('[data-key="days_attended"]'));
        const leaveDays = amount(row.querySelector('[data-key="leave_days"]'));
        const lopInput = row.querySelector('[data-key="lop_days"]');
        const payableInput = row.querySelector('[data-key="payable_days"]');
        const pfToggle = row.querySelector('[data-key="use_pf"]');
        const esiToggle = row.querySelector('[data-key="use_esi"]');
        const grossSalary = amount(row.querySelector('[data-key="gross_salary"]'));
        const professionalTax = amount(row.querySelector('[data-key="professional_tax"]'));
        const tds = amount(row.querySelector('[data-key="tds_amount"]'));
        const loan = amount(row.querySelector('[data-key="loan_deduction"]'));
        const otherDeduction = amount(row.querySelector('[data-key="other_deduction"]'));

        const basicSalary = grossSalary * 0.50;
        const hra = grossSalary * 0.30;
        const travel = grossSalary * 0.10;
        const other = Math.max(grossSalary - basicSalary - hra - travel, 0);
        const rawPayableDays = Math.max(daysAttended + leaveDays, 0);
        const payableDays = Math.min(workingDays > 0 ? workingDays : rawPayableDays, rawPayableDays);
        const lopDays = Math.max(workingDays - payableDays, 0);
        const ratio = workingDays > 0 ? Math.min(payableDays / workingDays, 1) : 0;
        const earnedGross = (basicSalary + hra + travel + other) * ratio || grossSalary * ratio;
        const earnedBasic = basicSalary * ratio;
        const earnedTravel = travel * ratio;
        const earnedOther = other * ratio;
        const pfBaseAmount = grossSalary > 21000 ? 15000 : (earnedBasic + earnedTravel + earnedOther);
        const pfValue = pfToggle && pfToggle.checked ? Math.round(pfBaseAmount * 0.25) : 0;
        const esiValue = esiToggle && esiToggle.checked ? Math.round(earnedGross * 0.04) : 0;
        const totalDeductions = pfValue + esiValue + professionalTax + tds + loan + otherDeduction;
        const netSalary = Math.max(earnedGross - totalDeductions, 0);

        setValue(row.querySelector('[data-key="basic_salary"]'), basicSalary);
        setValue(row.querySelector('[data-key="hra"]'), hra);
        setValue(row.querySelector('[data-key="travel_allowance"]'), travel);
        setValue(row.querySelector('[data-key="other_allowance"]'), other);
        setValue(lopInput, lopDays);
        setValue(payableInput, payableDays);
        setValue(row.querySelector('[data-key="pf_value"]'), pfValue);
        setValue(row.querySelector('[data-key="esi_value"]'), esiValue);
        setValue(row.querySelector('[data-key="total_deductions"]'), totalDeductions);
        setValue(row.querySelector('[data-key="net_salary"]'), netSalary);
    }

    function syncOverall() {
        const total = rows.reduce(function (sum, row) {
            return sum + amount(row.querySelector('[data-key="net_salary"]'));
        }, 0);

        overallNet.textContent = 'Rs ' + total.toFixed(2);
    }

    rows.forEach(function (row) {
        row.querySelectorAll('input').forEach(function (input) {
            input.addEventListener('input', function () {
                syncRow(row);
                syncOverall();
            });
            input.addEventListener('change', function () {
                syncRow(row);
                syncOverall();
            });
        });

        syncRow(row);
    });

    syncOverall();
});
</script>
@endpush
@endsection
