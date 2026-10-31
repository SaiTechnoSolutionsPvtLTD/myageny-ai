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
.pr-select{height:44px;border:1px solid #e1dee3;border-radius:10px;padding:0 14px;background:#fff;color:#20222a;font-size:14px;min-width:180px}
.pr-textarea{min-height:90px;border:1px solid #e1dee3;border-radius:10px;padding:12px 14px;background:#fff;color:#20222a;font-size:14px}
.pr-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:10px;border:1px solid transparent;background:#fff;color:#121212;text-decoration:none;font-size:13px;font-weight:700;cursor:pointer}
.pr-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);border-color:#fe5f04;color:#fff}
.pr-btn-ghost{border-color:#e1dee3}
.pr-meta-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.pr-meta{padding:14px;border:1px solid #ece8e4;border-radius:14px;background:#faf8f6}
.pr-meta strong{display:block;font-size:24px;color:#121212}
.pr-meta span{font-size:12px;color:#8a8a8a}
.pr-table{width:100%;border-collapse:collapse;min-width:2550px}
.pr-table th,.pr-table td{padding:10px 12px;border-bottom:1px solid #f0eef2;text-align:left;vertical-align:top}
.pr-table th{font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#9e9e9e;background:#fafafa;white-space:nowrap}
.pr-table td{min-width:110px}
.pr-table td:first-child{min-width:220px}
.pr-table input[type="number"],.pr-table input[type="text"]{width:100%;min-width:96px;height:40px;border:1px solid #e1dee3;border-radius:8px;padding:0 12px;font-size:13px}
.pr-table input[readonly]{background:#f8f8f8}
.pr-check{width:18px;height:18px}
.pr-emp-name{font-weight:700;color:#121212}
.pr-emp-sub{margin-top:2px;font-size:11px;color:#8a8a8a}
/* Inline table loader */
#pr-table-loader{display:none;padding:48px 0;text-align:center;flex-direction:column;align-items:center;gap:12px}
#pr-table-loader.active{display:flex}
.pr-tbl-spinner{width:40px;height:40px;border:4px solid #ffe0cc;border-top-color:#fe5f04;border-radius:50%;animation:pr-spin 0.7s linear infinite;margin:0 auto}
.pr-tbl-loader-text{font-size:13px;font-weight:600;color:#fe5f04}
@keyframes pr-spin{to{transform:rotate(360deg)}}
#pr-table-section{display:none}
#pr-table-section.active{display:block}
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
            <div class="pr-breadcrumb">HRMS &gt; Payroll &gt; Create</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ route('payroll.index') }}" class="pr-btn pr-btn-ghost">Back</a>
        </div>
    </div>

    <div class="pr-body">
        @if($errors->any())
            <div class="pr-card" style="border-color:#fecaca;background:#fef2f2;color:#991b1b;margin-bottom:16px;">
                <div style="font-weight:700;margin-bottom:6px;">Please review the payroll inputs and try again:</div>
                <ul style="margin:0;padding-left:18px;font-size:13px;line-height:1.5;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Filter card --}}
        <div class="pr-card">
            <div class="pr-form-row">
                <div class="pr-field">
                    <label class="pr-label">Salary Month</label>
                    <input type="month" id="filterMonth" class="pr-input" value="{{ $selectedMonth->format('Y-m') }}">
                </div>
                <div class="pr-field">
                    <label class="pr-label">Branch</label>
                    <select id="filterBranch" class="pr-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="pr-field">
                    <label class="pr-label">Salary Cycle</label>
                    <select id="filterSalaryCycle" class="pr-select">
                        <option value="26_to_25" @selected(in_array(($selectedCycle ?? '26_to_25'), ['26_to_25', '25_to_25']))>26 to 25 cycle</option>
                        <option value="1_to_30" @selected(in_array(($selectedCycle ?? ''), ['1_to_30', '10_to_10']))>1 to 30 cycle</option>
                    </select>
                </div>
                <button type="button" id="btnLoadEmployees" class="pr-btn pr-btn-primary">Load Employees</button>
                <a href="{{ route('settings.payroll.index') }}" class="pr-btn pr-btn-ghost">Payroll Settings</a>
            </div>
        </div>

        {{-- Summary meta cards --}}
        <div class="pr-meta-grid">
            <div class="pr-meta"><strong id="metaEmpCount">—</strong><span>Verified Employees</span></div>
            <div class="pr-meta"><strong id="metaWorkingDays">—</strong><span>Total Working Days</span></div>
            <div class="pr-meta"><strong id="metaMonthLabel">{{ $selectedMonth->format('M Y') }}</strong><span>Payroll Month</span></div>
            <div class="pr-meta"><strong id="overallNet">Rs 0.00</strong><span>Total Net Salary</span></div>
        </div>

        {{-- Inline loader (shown while fetching) --}}
        <div class="pr-card">
            <div id="pr-table-loader">
                <div class="pr-tbl-spinner"></div>
                <div class="pr-tbl-loader-text">Loading employee data... Please wait</div>
            </div>

            {{-- Table section (hidden until data loads) --}}
            <div id="pr-table-section">
                <div style="font-size:16px;font-weight:700;color:#121212;">Employee Payroll Sheet</div>
                <div style="margin-top:4px;color:#9e9e9e;font-size:12px;">Attendance values can be adjusted before saving. Gross salary automatically splits into Basic 50%, HRA 30%, Travel 10%, and Other 10%.</div>
                <div style="margin-top:8px;padding:12px 14px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8;font-size:12px;font-weight:600;" id="payrollFormulaBanner">
                    PF formula: if Gross Salary is above {{ number_format((float) ($payrollSettings->pf_gross_threshold ?? 21000), 0) }}, PF uses fixed {{ number_format((float) ($payrollSettings->pf_basic_cap ?? 15000), 0) }}. Otherwise PF uses Basic + Travel + Other. Total PF deduction includes Employee PF ({{ (float) ($payrollSettings->pf_employee_percentage ?? 12) }}%) + Employer PF ({{ (float) ($payrollSettings->pf_employer_percentage ?? 13) }}%). ESI formula: {{ number_format((float) ($payrollSettings->esi_employee_percentage ?? 4), 0) }}% of Gross Salary.
                </div>
                <div style="margin-top:14px;padding:14px 16px;border-radius:12px;background:#fff8f4;border:1px solid #fed7aa;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:13px;font-weight:700;color:#9a3412;">⚡ Quick Set Working Days:</span>
                        <input type="number" step="0.01" min="0" id="bulkWorkingDaysInput" class="pr-input" style="width:110px;height:38px;" placeholder="Days">
                        <button type="button" id="btnApplyBulkWorkingDays" class="pr-btn pr-btn-primary" style="padding:7px 14px;font-size:12px;">Apply to All Employees</button>
                    </div>
                    <span style="font-size:12px;color:#c2410c;font-weight:600;">Changing working days will automatically recalculate payable days, LOP, deductions, and Net Salary.</span>
                </div>

                <div style="overflow-x:auto;margin-top:16px;">
                    <table class="pr-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Working Days</th>
                                <th>Present</th>
                                <th>Leave</th>
                                <th>Timesheet LOP</th>
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
                        <tbody id="payrollTbody"></tbody>
                    </table>
                </div>

                {{-- Hidden payroll form — submitted after rows are injected --}}
                <form method="POST" action="{{ route('payroll.store') }}" id="payrollForm" style="margin-top:16px;">
                    @csrf
                    <input type="hidden" id="formMonth" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                    <input type="hidden" id="formSalaryCycle" name="salary_cycle" value="{{ $selectedCycle ?? '26_to_25' }}">
                    <input type="hidden" id="formItemsJson" name="items_json" value="">
                    <div class="pr-card" style="margin-bottom:12px;">
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
                    <div id="hiddenInputsContainer"></div>
                    <div style="display:flex;justify-content:flex-end;">
                        <button type="submit" class="pr-btn pr-btn-primary">Save Payroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    // ── State ─────────────────────────────────────────────────────────────────
    let payrollRows = [];       // array of row objects from server
    let workingDays = 0;

    // ── DOM refs ─────────────────────────────────────────────────────────────
    const btnLoad           = document.getElementById('btnLoadEmployees');
    const filterMonth       = document.getElementById('filterMonth');
    const filterBranch      = document.getElementById('filterBranch');
    const filterSalaryCycle = document.getElementById('filterSalaryCycle');
    const tableLoader       = document.getElementById('pr-table-loader');
    const tableSection      = document.getElementById('pr-table-section');
    const tbody             = document.getElementById('payrollTbody');
    const hiddenContainer   = document.getElementById('hiddenInputsContainer');
    const payrollForm       = document.getElementById('payrollForm');
    const formItemsJson     = document.getElementById('formItemsJson');
    const formMonth         = document.getElementById('formMonth');
    const formSalaryCycle   = document.getElementById('formSalaryCycle');
    const metaEmpCount      = document.getElementById('metaEmpCount');
    const metaWorkingDays   = document.getElementById('metaWorkingDays');
    const metaMonthLabel    = document.getElementById('metaMonthLabel');
    const overallNet        = document.getElementById('overallNet');
    const bulkWdInput       = document.getElementById('bulkWorkingDaysInput');
    const btnBulkApply      = document.getElementById('btnApplyBulkWorkingDays');

    // ── Math helpers ──────────────────────────────────────────────────────────
    function n(v) { const x = parseFloat(v); return isFinite(x) ? x : 0; }
    function fmt(v) { return Number(v).toFixed(2); }

    function calcRow(r) {
        const wd    = n(r.working_days);
        const da    = n(r.days_attended);
        const ld    = n(r.leave_days);
        const tsLop = n(r.timesheet_lop_days);
        const attPayable = Math.max(da + ld, 0);
        const attLop = Math.max(wd - attPayable, 0);
        const lop = attLop + tsLop;
        const pay = Math.max(wd - lop, 0);
        const ratio = wd > 0 ? Math.min(pay / wd, 1) : 0;
        const gross  = n(r.gross_salary);
        const basic  = gross * 0.50;
        const hra    = gross * 0.30;
        const travel = gross * 0.10;
        const other  = Math.max(gross - basic - hra - travel, 0);
        const earnedGross  = (basic + hra + travel + other) * ratio || gross * ratio;
        const earnedBasic  = basic * ratio;
        const earnedTravel = travel * ratio;
        const earnedOther  = other * ratio;

        const defaultPfThreshold = {{ (float) ($payrollSettings->pf_gross_threshold ?? 21000) }};
        const defaultPfCap       = {{ (float) ($payrollSettings->pf_basic_cap ?? 15000) }};
        const defaultPfEmpPct    = {{ (float) ($payrollSettings->pf_employee_percentage ?? 12) }};
        const defaultPfEmprPct   = {{ (float) ($payrollSettings->pf_employer_percentage ?? 13) }};
        const defaultEsiEmpPct   = {{ (float) ($payrollSettings->esi_employee_percentage ?? 4) }};

        const pfThreshold = (r.pf_gross_threshold !== undefined && r.pf_gross_threshold !== null && r.pf_gross_threshold !== '')
            ? n(r.pf_gross_threshold) : (window.currentPayrollSettings?.pf_gross_threshold ?? defaultPfThreshold);
        const pfCap = (r.pf_basic_cap !== undefined && r.pf_basic_cap !== null && r.pf_basic_cap !== '')
            ? n(r.pf_basic_cap) : (window.currentPayrollSettings?.pf_basic_cap ?? defaultPfCap);

        const pfEmpPct = (r.pf_employee_percentage !== undefined && r.pf_employee_percentage !== null && r.pf_employee_percentage !== '')
            ? n(r.pf_employee_percentage) : (window.currentPayrollSettings?.pf_employee_percentage ?? defaultPfEmpPct);
        const pfEmprPct = (r.pf_employer_percentage !== undefined && r.pf_employer_percentage !== null && r.pf_employer_percentage !== '')
            ? n(r.pf_employer_percentage) : (window.currentPayrollSettings?.pf_employer_percentage ?? defaultPfEmprPct);

        const esiEmpPct = (r.esi_employee_percentage !== undefined && r.esi_employee_percentage !== null && r.esi_employee_percentage !== '')
            ? n(r.esi_employee_percentage) : (window.currentPayrollSettings?.esi_employee_percentage ?? defaultEsiEmpPct);

        const pfBase = gross > pfThreshold ? pfCap : (earnedBasic + earnedTravel + earnedOther);
        const pfEmployee = r.use_pf ? Math.round(pfBase * (pfEmpPct / 100) * 100) / 100 : 0;
        const pfEmployer = r.use_pf ? Math.round(pfBase * (pfEmprPct / 100) * 100) / 100 : 0;
        const pf  = pfEmployee + pfEmployer;
        const esi = r.use_esi ? Math.round(earnedGross * (esiEmpPct / 100) * 100) / 100 : 0;
        const pt  = n(r.professional_tax);
        const tds = n(r.tds_amount);
        const loan = n(r.loan_deduction);
        const otherDed = n(r.other_deduction);
        const totalDed = pf + esi + pt + tds + loan + otherDed;
        const net = Math.max(earnedGross - totalDed, 0);
        return { lop, pay, basic, hra, travel, other, pf, pfEmployee, pfEmployer, esi, totalDed, net };
    }

    // ── Debounced syncOverall ─────────────────────────────────────────────────
    let rafId = null;
    function scheduleOverall() {
        if (rafId) return;
        rafId = requestAnimationFrame(function () {
            rafId = null;
            let total = 0;
            document.querySelectorAll('.payroll-row').forEach(function (tr) {
                total += n(tr.querySelector('[data-key="net_salary"]')?.value);
            });
            overallNet.textContent = 'Rs ' + fmt(total);
        });
    }

    function syncRow(tr) {
        const get = key => tr.querySelector('[data-key="' + key + '"]');
        const row = {
            working_days:            n(get('working_days')?.value),
            days_attended:           n(get('days_attended')?.value),
            leave_days:              n(get('leave_days')?.value),
            timesheet_lop_days:      n(get('timesheet_lop_days')?.value),
            use_pf:                  get('use_pf')?.checked ?? false,
            use_esi:                 get('use_esi')?.checked ?? false,
            pf_employee_percentage:  n(get('pf_employee_percentage')?.value),
            pf_employer_percentage:  n(get('pf_employer_percentage')?.value),
            esi_employee_percentage: n(get('esi_employee_percentage')?.value),
            esi_employer_percentage: n(get('esi_employer_percentage')?.value),
            esi_salary_limit:        n(get('esi_salary_limit')?.value),
            pf_gross_threshold:      n(get('pf_gross_threshold')?.value),
            pf_basic_cap:            n(get('pf_basic_cap')?.value),
            gross_salary:            n(get('gross_salary')?.value),
            professional_tax:        n(get('professional_tax')?.value),
            tds_amount:              n(get('tds_amount')?.value),
            loan_deduction:          n(get('loan_deduction')?.value),
            other_deduction:         n(get('other_deduction')?.value),
        };
        const c = calcRow(row);
        const set = (key, v) => { const el = get(key); if (el) el.value = fmt(v); };
        set('basic_salary', c.basic);
        set('hra', c.hra);
        set('travel_allowance', c.travel);
        set('other_allowance', c.other);
        set('lop_days', c.lop);
        set('payable_days', c.pay);
        set('pf_value', c.pf);
        set('esi_value', c.esi);
        set('total_deductions', c.totalDed);
        set('net_salary', c.net);
    }

    // ── Render tbody from JSON ────────────────────────────────────────────────
    function buildRowHtml(row, index) {
        const c = calcRow(row);
        const inp = (key, val, name, extra) =>
            `<input type="number" step="0.01" min="0" ${name ? `name="items[${index}][${name}]"` : ''} value="${fmt(val)}" data-key="${key}" ${extra || ''}>`;
        const ro = (key, val) =>
            `<input type="text" value="${fmt(val)}" data-key="${key}" readonly>`;
        const chk = (key, name, checked) =>
            `<input type="hidden" name="items[${index}][${name}]" value="0">
             <input type="checkbox" class="pr-check" name="items[${index}][${name}]" value="1" data-key="${key}" ${checked ? 'checked' : ''}>`;

        const branch = row.branch_name && row.branch_name !== '—'
            ? ` | <span style="color:#fe5f04;font-weight:600;">${escHtml(row.branch_name)}</span>` : '';

        return `<tr class="payroll-row" data-index="${index}">
            <td>
                <div class="pr-emp-name">${escHtml(row.employee_name)}</div>
                <div class="pr-emp-sub">${escHtml(row.employee_code || '')} | ${escHtml(row.designation || '')}${branch}</div>
                <input type="hidden" name="items[${index}][employee_onboarding_id]" value="${row.employee_onboarding_id}" data-key="employee_onboarding_id">
                <input type="hidden" name="items[${index}][pf_gross_threshold]" value="${row.pf_gross_threshold || ''}" data-key="pf_gross_threshold">
                <input type="hidden" name="items[${index}][pf_basic_cap]" value="${row.pf_basic_cap || ''}" data-key="pf_basic_cap">
            </td>
            <td>${inp('working_days', row.working_days, 'working_days')}</td>
            <td>${inp('days_attended', row.days_attended, 'days_attended')}</td>
            <td>${inp('leave_days', row.leave_days, 'leave_days')}</td>
            <td>${inp('timesheet_lop_days', row.timesheet_lop_days || 0, 'timesheet_lop_days')}</td>
            <td>${inp('lop_days', c.lop, 'lop_days', 'readonly')}</td>
            <td>${inp('payable_days', c.pay, 'payable_days', 'readonly')}</td>
            <td>${chk('use_pf', 'use_pf', row.use_pf)}</td>
            <td>${chk('use_esi', 'use_esi', row.use_esi)}</td>
            <td>${inp('pf_employee_percentage', row.pf_employee_percentage, 'pf_employee_percentage', 'max="100"')}</td>
            <td>${inp('pf_employer_percentage', row.pf_employer_percentage, 'pf_employer_percentage', 'max="100"')}</td>
            <td>${inp('esi_employee_percentage', row.esi_employee_percentage, 'esi_employee_percentage', 'max="100"')}</td>
            <td>${inp('esi_employer_percentage', row.esi_employer_percentage, 'esi_employer_percentage', 'max="100"')}</td>
            <td>${inp('esi_salary_limit', row.esi_salary_limit, 'esi_salary_limit')}</td>
            <td>${inp('gross_salary', row.gross_salary, 'gross_salary')}</td>
            <td>${inp('basic_salary', c.basic, 'basic_salary')}</td>
            <td>${inp('hra', c.hra, 'hra')}</td>
            <td>${inp('travel_allowance', c.travel, 'travel_allowance')}</td>
            <td>${inp('other_allowance', c.other, 'other_allowance')}</td>
            <td>${ro('pf_value', c.pf)}</td>
            <td>${ro('esi_value', c.esi)}</td>
            <td>${inp('professional_tax', row.professional_tax, 'professional_tax')}</td>
            <td>${inp('tds_amount', row.tds_amount, 'tds_amount')}</td>
            <td>${inp('loan_deduction', row.loan_deduction, 'loan_deduction')}</td>
            <td>${inp('other_deduction', row.other_deduction, 'other_deduction')}</td>
            <td>${ro('total_deductions', c.totalDed)}</td>
            <td>${ro('net_salary', c.net)}</td>
        </tr>`;
    }

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderTable(data) {
        workingDays = data.working_days;
        payrollRows = data.rows;

        if (data.payroll_settings) {
            window.currentPayrollSettings = data.payroll_settings;
            const banner = document.getElementById('payrollFormulaBanner');
            if (banner) {
                const thresh = Number(data.payroll_settings.pf_gross_threshold || 21000).toLocaleString();
                const cap = Number(data.payroll_settings.pf_basic_cap || 15000).toLocaleString();
                const esiRate = Number(data.payroll_settings.esi_employee_percentage || 4);
                banner.textContent = `PF formula: if Gross Salary is above ${thresh}, PF uses fixed ${cap}. Otherwise PF uses Basic + Travel + Other. Total PF deduction includes Employee PF (${data.payroll_settings.pf_employee_percentage || 12}%) + Employer PF (${data.payroll_settings.pf_employer_percentage || 13}%). ESI formula: ${esiRate}% of Gross Salary.`;
            }
        }

        metaEmpCount.textContent    = payrollRows.length;
        metaWorkingDays.textContent = workingDays;
        metaMonthLabel.textContent  = data.month_label;
        bulkWdInput.value           = workingDays;
        formMonth.value             = filterMonth.value;
        if (formSalaryCycle && filterSalaryCycle) {
            formSalaryCycle.value = filterSalaryCycle.value;
        }

        // Build HTML in one shot — much faster than appending row by row
        tbody.innerHTML = payrollRows.map((row, i) => buildRowHtml(row, i)).join('');

        // Attach event listeners to all rows
        tbody.querySelectorAll('.payroll-row').forEach(function (tr) {
            let pending = false;
            tr.querySelectorAll('input, select').forEach(function (input) {
                function handle() {
                    if (pending) return;
                    pending = true;
                    requestAnimationFrame(function () {
                        pending = false;
                        syncRow(tr);
                        scheduleOverall();
                    });
                }
                input.addEventListener('input', handle);
                input.addEventListener('change', handle);
            });
        });

        scheduleOverall();

        tableLoader.classList.remove('active');
        tableSection.classList.add('active');
    }

    // ── Fetch rows from server ────────────────────────────────────────────────
    function loadRows() {
        const month       = filterMonth.value;
        const branchId    = filterBranch.value;
        const salaryCycle = filterSalaryCycle ? filterSalaryCycle.value : '26_to_25';

        tableSection.classList.remove('active');
        tableLoader.classList.add('active');
        tbody.innerHTML = '';

        const url = new URL('{{ route("payroll.loadRows") }}', window.location.origin);
        if (month)       url.searchParams.set('month', month);
        if (branchId)    url.searchParams.set('branch_id', branchId);
        if (salaryCycle) url.searchParams.set('salary_cycle', salaryCycle);

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('Server error ' + res.status);
            return res.json();
        })
        .then(function (data) {
            renderTable(data);
        })
        .catch(function (err) {
            tableLoader.classList.remove('active');
            alert('Failed to load employee data. Please try again.\n' + err.message);
        });
    }

    // ── Bulk working days ─────────────────────────────────────────────────────
    btnBulkApply.addEventListener('click', function () {
        const val = parseFloat(bulkWdInput.value);
        if (isNaN(val) || val < 0) { alert('Please enter a valid working days value.'); return; }
        tbody.querySelectorAll('.payroll-row').forEach(function (tr) {
            const wd = tr.querySelector('[data-key="working_days"]');
            if (wd) { wd.value = val; syncRow(tr); }
        });
        scheduleOverall();
    });

    // ── Button click & filter changes ─────────────────────────────────────────
    btnLoad.addEventListener('click', loadRows);
    if (filterSalaryCycle) {
        filterSalaryCycle.addEventListener('change', loadRows);
    }
    if (filterBranch) {
        filterBranch.addEventListener('change', loadRows);
    }
    if (filterMonth) {
        filterMonth.addEventListener('change', loadRows);
    }

    // ── Form submit handler ───────────────────────────────────────────────────
    if (payrollForm) {
        payrollForm.addEventListener('submit', function (e) {
            formMonth.value = filterMonth.value;
            if (formSalaryCycle && filterSalaryCycle) {
                formSalaryCycle.value = filterSalaryCycle.value;
            }

            const trList = tbody.querySelectorAll('.payroll-row');
            if (!trList.length) {
                e.preventDefault();
                alert('No employee records found. Please click "Load Employees" first.');
                return;
            }

            const items = [];
            trList.forEach(function (tr) {
                const get = key => tr.querySelector(`[data-key="${key}"]`);
                const getVal = key => {
                    const el = get(key);
                    return el ? el.value : '';
                };

                syncRow(tr);

                items.push({
                    employee_onboarding_id: getVal('employee_onboarding_id'),
                    working_days: n(getVal('working_days')),
                    days_attended: n(getVal('days_attended')),
                    leave_days: n(getVal('leave_days')),
                    timesheet_lop_days: n(getVal('timesheet_lop_days')),
                    lop_days: n(getVal('lop_days')),
                    payable_days: n(getVal('payable_days')),
                    use_pf: get('use_pf') ? (get('use_pf').checked ? 1 : 0) : 0,
                    use_esi: get('use_esi') ? (get('use_esi').checked ? 1 : 0) : 0,
                    pf_gross_threshold: n(getVal('pf_gross_threshold')),
                    pf_basic_cap: n(getVal('pf_basic_cap')),
                    pf_employee_percentage: n(getVal('pf_employee_percentage')),
                    pf_employer_percentage: n(getVal('pf_employer_percentage')),
                    esi_employee_percentage: n(getVal('esi_employee_percentage')),
                    esi_employer_percentage: n(getVal('esi_employer_percentage')),
                    esi_salary_limit: n(getVal('esi_salary_limit')),
                    gross_salary: n(getVal('gross_salary')),
                    basic_salary: n(getVal('basic_salary')),
                    hra: n(getVal('hra')),
                    travel_allowance: n(getVal('travel_allowance')),
                    other_allowance: n(getVal('other_allowance')),
                    professional_tax: n(getVal('professional_tax')),
                    tds_amount: n(getVal('tds_amount')),
                    loan_deduction: n(getVal('loan_deduction')),
                    other_deduction: n(getVal('other_deduction')),
                });
            });

            if (formItemsJson) {
                formItemsJson.value = JSON.stringify(items);
            }
        });
    }

    // ── Auto-load on page open ────────────────────────────────────────────────
    loadRows();
})();
</script>
@endpush
@endsection
