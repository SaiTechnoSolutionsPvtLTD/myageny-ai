@extends('layouts.app')

@section('title', 'Payroll & Attendance Settings')

@push('styles')
<style>
.pset-page{min-height:100%;padding:28px;background:linear-gradient(180deg,#f8f6f2 0%,#f3f5f8 100%)}
.pset-shell{max-width:980px;margin:0 auto;display:flex;flex-direction:column;gap:18px}
.pset-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:24px;box-shadow:0 10px 28px rgba(15,23,42,.04)}
.pset-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.pset-title{margin:0;font-size:24px;font-weight:800;color:#111827}
.pset-sub{margin:8px 0 0;font-size:14px;line-height:1.7;color:#6b7280;max-width:700px}
.pset-actions{display:flex;gap:10px;flex-wrap:wrap}
.pset-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 16px;border-radius:10px;border:1px solid transparent;background:#fff;color:#121212;text-decoration:none;font-size:13px;font-weight:700}
.pset-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);border-color:#fe5f04;color:#fff}
.pset-btn-ghost{border-color:#e1dee3}
.pset-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.pset-field{display:flex;flex-direction:column;gap:8px}
.pset-label{font-size:13px;font-weight:700;color:#374151}
.pset-input{height:44px;border:1px solid #e1dee3;border-radius:10px;padding:0 14px;background:#fff;color:#20222a;font-size:14px}
.pset-hint{font-size:12px;color:#8a8a8a;line-height:1.6}
.pset-banner{padding:14px 16px;border-radius:12px;background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8;font-size:13px;font-weight:600}
@media (max-width: 768px){.pset-page{padding:18px}.pset-grid{grid-template-columns:1fr}.pset-head{flex-direction:column;align-items:flex-start}}
</style>
@endpush

@section('content')
<div class="pset-page">
    <div class="pset-shell">
        @if(session('success'))
            <div class="pset-card" style="border-color:#bbf7d0;background:#f0fdf4;color:#166534;font-size:13px;font-weight:700;">
                {{ session('success') }}
            </div>
        @endif

        <div class="pset-card">
            <div class="pset-head">
                <div>
                    <h2 class="pset-title">Payroll & Attendance Settings</h2>
                    <p class="pset-sub">Set company-level payroll, leave, permission, and grace-time rules here. These values auto-fill during payroll processing and are also used while converting attendance into payable days and LOP.</p>
                </div>
                <div class="pset-actions">
                    <a href="{{ route('hrms.masters.index') }}" class="pset-btn pset-btn-ghost">Back</a>
                </div>
            </div>
        </div>

        <div class="pset-card">
            <div class="pset-banner">Payroll uses approved attendance, leave requests, permission requests, and grace-time rules from this page while calculating payable days and LOP.</div>

            <form method="POST" action="{{ route('settings.payroll.update') }}" style="margin-top:20px;">
                @csrf
                <div id="leave-settings" style="margin-bottom:20px;">
                    <div style="font-size:16px;font-weight:800;color:#111827;">Leave Settings</div>
                    <div class="pset-hint" style="margin-top:6px;">Set how many leave days should be treated as paid leave in payroll for each month.</div>
                </div>
                <div class="pset-grid">
                    <div class="pset-field">
                        <label class="pset-label">Paid Leave Days Per Month</label>
                        <input type="number" step="0.01" min="0" name="paid_leave_days" class="pset-input" value="{{ old('paid_leave_days', $settings->paid_leave_days) }}">
                        <div class="pset-hint">Approved leave beyond this limit will automatically fall into LOP through payroll payable-day calculation.</div>
                    </div>
                </div>

                <div id="permission-settings" style="margin:28px 0 20px;">
                    <div style="font-size:16px;font-weight:800;color:#111827;">Permission Settings</div>
                    <div class="pset-hint" style="margin-top:6px;">Control how many permission days are allowed in a month and how many hours can be used on one day.</div>
                </div>
                <div class="pset-grid">
                    <div class="pset-field">
                        <label class="pset-label">Permission Days Per Month</label>
                        <input type="number" step="1" min="0" name="permission_days_per_month" class="pset-input" value="{{ old('permission_days_per_month', $settings->permission_days_per_month) }}">
                        <div class="pset-hint">Each approved permission date, and each late login beyond grace time, consumes one permission day.</div>
                    </div>
                    <div class="pset-field">
                        <label class="pset-label">Permission Hours Per Day</label>
                        <input type="number" step="0.01" min="0" name="permission_hours_per_day" class="pset-input" value="{{ old('permission_hours_per_day', $settings->permission_hours_per_day) }}">
                        <div class="pset-hint">If approved permission hours exceed this daily limit, the excess portion reduces payable days in payroll.</div>
                    </div>
                </div>

                <div id="grace-time-settings" style="margin:28px 0 20px;">
                    <div style="font-size:16px;font-weight:800;color:#111827;">Grace Time Settings</div>
                    <div class="pset-hint" style="margin-top:6px;">Set the daily grace cutoff time for employee login.</div>
                </div>
                <div class="pset-grid">
                    <div class="pset-field">
                        <label class="pset-label">Grace Login Time</label>
                        <input type="time" name="grace_login_time" class="pset-input" value="{{ old('grace_login_time', substr((string) $settings->grace_login_time, 0, 5)) }}">
                        <div class="pset-hint">If an employee logs in after this time, one permission day is consumed for that date.</div>
                    </div>
                </div>

                <div id="payroll-settings" style="margin:28px 0 20px;">
                    <div style="font-size:16px;font-weight:800;color:#111827;">Payroll Settings</div>
                    <div class="pset-hint" style="margin-top:6px;">These defaults are used when a payroll month is opened. Individual rows can still be adjusted before saving.</div>
                </div>
                <div class="pset-grid">
                    <div class="pset-field">
                        <label class="pset-label">PF Employee Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="pf_employee_percentage" class="pset-input" value="{{ old('pf_employee_percentage', $settings->pf_employee_percentage) }}">
                        <div class="pset-hint">Example: `12.00` means 12% employee PF deduction on earned basic salary.</div>
                    </div>
                    <div class="pset-field">
                        <label class="pset-label">PF Employer Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="pf_employer_percentage" class="pset-input" value="{{ old('pf_employer_percentage', $settings->pf_employer_percentage) }}">
                        <div class="pset-hint">Used for payroll storage and reporting on employer PF contribution.</div>
                    </div>
                    <div class="pset-field">
                        <label class="pset-label">ESI Employee Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="esi_employee_percentage" class="pset-input" value="{{ old('esi_employee_percentage', $settings->esi_employee_percentage) }}">
                        <div class="pset-hint">Example: `0.75` means 0.75% employee ESI deduction on earned gross salary.</div>
                    </div>
                    <div class="pset-field">
                        <label class="pset-label">ESI Employer Percentage</label>
                        <input type="number" step="0.01" min="0" max="100" name="esi_employer_percentage" class="pset-input" value="{{ old('esi_employer_percentage', $settings->esi_employer_percentage) }}">
                        <div class="pset-hint">Used for employer-side ESI contribution values in payroll records.</div>
                    </div>
                    <div class="pset-field">
                        <label class="pset-label">ESI Salary Limit</label>
                        <input type="number" step="0.01" min="0" name="esi_salary_limit" class="pset-input" value="{{ old('esi_salary_limit', $settings->esi_salary_limit) }}">
                        <div class="pset-hint">ESI applies only when earned gross salary is less than or equal to this limit.</div>
                    </div>
                </div>

                <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                    <button type="submit" class="pset-btn pset-btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
