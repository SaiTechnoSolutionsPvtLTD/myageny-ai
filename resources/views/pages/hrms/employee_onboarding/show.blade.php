@extends('layouts.app')

@section('title', 'Employee Onboarding - ' . $employee->name)

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .avatar-container {
            margin-top: -38px;
            display: inline-block;
        }
        .eob-avatar {
            margin-top: 0 !important;
            position: relative !important;
            cursor: pointer;
        }
        .avatar-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            border-radius: 16px;
        }
        .eob-avatar:hover .avatar-upload-overlay {
            opacity: 1 !important;
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Employee Onboarding Profile</div>
            <div class="eob-breadcrumb">HRMS > Employee Onboarding > {{ $employee->name }}</div>
        </div>
        @php
            $isHrOrAdmin = auth()->user()?->isHrOrAdmin();
        @endphp
        <div class="eob-actions">
            @if($isHrOrAdmin)
                <a href="{{ route('employee-onboarding.edit', $employee) }}" class="eob-btn eob-btn-primary">Edit</a>
                <a href="{{ route('employee-onboarding.index') }}" class="eob-btn eob-btn-ghost">Back</a>
            @endif
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif

        <div class="eob-show-layout">
            <div class="eob-profile-sticky">
                <div class="eob-profile">
                    <div class="eob-profile-banner"></div>
                    <div class="eob-profile-body">
                        <form action="{{ route('employee-onboarding.update-photo', $employee) }}" method="POST" enctype="multipart/form-data" id="photo-upload-form" class="avatar-container">
                            @csrf
                            <div class="eob-avatar" onclick="document.getElementById('photo-input').click()">
                                @if($employee->photograph)
                                    <img src="{{ $employee->getFileUrl('photograph') }}" alt="{{ $employee->name }}">
                                @else
                                    {{ strtoupper(substr($employee->name, 0, 2)) }}
                                @endif
                                
                                <div class="avatar-upload-overlay">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                        <circle cx="12" cy="13" r="4"></circle>
                                    </svg>
                                </div>
                            </div>
                            <input type="file" id="photo-input" name="photograph" accept="image/*" style="display: none;" onchange="document.getElementById('photo-upload-form').submit()">
                        </form>
                        <div class="eob-profile-name">{{ $employee->name }}</div>
                        <div class="eob-profile-mail">{{ $employee->email }}</div>
                        <div style="margin-top:14px;">
                            <span class="eob-chip eob-chip-{{ $employee->status }}">{{ ucfirst($employee->status) }}</span>
                        </div>
                        @if($employee->sourceIntern)
                            <div style="margin-top:10px;">
                                <span class="eob-chip eob-chip-pending">Converted from Intern</span>
                            </div>
                        @endif

                        <div class="eob-empid-card">
                            <div class="eob-empid-label">Employee ID</div>
                            <div class="eob-empid-value">{{ $employee->employee_id }}</div>
                            <div class="eob-empid-sub">
                                {{ $employee->sourceIntern ? 'Created from intern ' . ($employee->sourceIntern->intern_id ?: $employee->sourceIntern->name) : 'Quick identity card for profile reference while scrolling.' }}
                            </div>
                        </div>

                        <div class="eob-empid-card" style="margin-top:12px;">
                            @php
                                $showPct = $employee->profile_completion_percentage;
                                $showFill = $showPct >= 80 ? 'eob-progress-high' : ($showPct >= 50 ? 'eob-progress-mid' : 'eob-progress-low');
                            @endphp
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <span class="eob-empid-label">Profile Completion</span>
                                <span style="font-size:13px; font-weight:800; color:#121212;">{{ $showPct }}%</span>
                            </div>
                            <div class="eob-progress-bar-bg">
                                <div class="eob-progress-bar-fill {{ $showFill }}" style="width: {{ $showPct }}%;"></div>
                            </div>
                        </div>

                        <div class="eob-side-list">
                            <div class="eob-side-item">
                                <div class="eob-side-label">Mobile</div>
                                <div class="eob-side-value">{{ $employee->mobile }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Role</div>
                                <div class="eob-side-value">{{ $employee->role?->display_name ?: ($employee->role?->name ?: 'N/A') }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Department</div>
                                <div class="eob-side-value">{{ $employee->department?->name ?: 'N/A' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Date of Birth</div>
                                <div class="eob-side-value">{{ optional($employee->date_of_birth)->format('d M Y') ?: 'N/A' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Created By</div>
                                <div class="eob-side-value">{{ $employee->creator?->name ?? 'System' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Updated By</div>
                                <div class="eob-side-value">{{ $employee->updater?->name ?? 'System' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="eob-section-stack">
                <div class="eob-show-card">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Personal Details</div>
                            <div class="eob-card-sub">Saved profile and identification information.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-show-grid">
                            <div class="eob-show-item"><div class="eob-show-label">Employee ID</div><div class="eob-show-value">{{ $employee->employee_id }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Source</div><div class="eob-show-value">@if($employee->sourceIntern)<a href="{{ route('interns.show', $employee->sourceIntern) }}">Intern {{ $employee->sourceIntern->intern_id ?: $employee->sourceIntern->name }}</a>@else Direct Employee Onboarding @endif</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Father's Name</div><div class="eob-show-value">{{ $employee->father_name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Blood Group</div><div class="eob-show-value">{{ $employee->blood_group ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Marital Status</div><div class="eob-show-value">{{ ucfirst($employee->marital_status) }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Date of Marriage</div><div class="eob-show-value">{{ optional($employee->date_of_marriage)->format('d M Y') ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Aadhaar Card No</div><div class="eob-show-value">{{ $employee->aadhaar_card_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Pan Card No</div><div class="eob-show-value">{{ $employee->pan_card_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Employee Type</div><div class="eob-show-value">{{ $employee->employee_type === 'non_billable' ? 'Non Billable' : 'Billable' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Correspondence Address</div><div class="eob-show-value">{{ $employee->correspondence_address ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Permanent Address</div><div class="eob-show-value">{{ $employee->permanent_address ?: 'N/A' }}</div></div>
                        </div>
                    </div>
                </div>

                @php
                    $portalManager = $employee->portalUser?->managerMappings?->first()?->manager;
                @endphp
                <div class="eob-show-card">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Employee Portal Account</div>
                            <div class="eob-card-sub">Login, branch, role, department, and TL mapping.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-show-grid">
                            <div class="eob-show-item"><div class="eob-show-label">User Email</div><div class="eob-show-value">{{ $employee->portalUser?->email ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Branch</div><div class="eob-show-value">{{ $employee->portalUser?->branch?->name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Department</div><div class="eob-show-value">{{ $employee->department?->name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Role</div><div class="eob-show-value">{{ $employee->role?->display_name ?: ($employee->role?->name ?: 'N/A') }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">TL Mapping</div><div class="eob-show-value">{{ $portalManager?->name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Account Status</div><div class="eob-show-value">{{ $employee->portalUser ? ($employee->portalUser->is_active ? 'Active' : 'Inactive') : 'N/A' }}</div></div>
                        </div>
                    </div>
                </div>

                <div class="eob-show-card">
                    <div class="eob-card-head"><div><div class="eob-card-title">Emergency Contact</div></div></div>
                    <div class="eob-card-body">
                        <div class="eob-show-grid">
                            <div class="eob-show-item"><div class="eob-show-label">Name</div><div class="eob-show-value">{{ $employee->emergency_contact_name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Relation</div><div class="eob-show-value">{{ $employee->emergency_relation ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Contact No</div><div class="eob-show-value">{{ $employee->emergency_contact_no ?: 'N/A' }}</div></div>
                        </div>
                    </div>
                </div>

                <div class="eob-show-card">
                    <div class="eob-card-head"><div><div class="eob-card-title">Educational Details</div></div></div>
                    <div class="eob-card-body">
                        <div class="eob-table-wrap">
                            <table class="eob-table">
                                <thead>
                                    <tr>
                                        <th>Qualification</th>
                                        <th>Institution</th>
                                        <th>Year</th>
                                        <th>Percentage</th>
                                        <th>Specialization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($employee->educations as $education)
                                        <tr>
                                            <td>{{ $education->qualification ?: 'N/A' }}</td>
                                            <td>{{ $education->institution_name ?: 'N/A' }}</td>
                                            <td>{{ $education->year_of_passing ?: 'N/A' }}</td>
                                            <td>{{ $education->percentage ?: 'N/A' }}</td>
                                            <td>{{ $education->specialization ?: 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="eob-muted">No education records added.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="eob-show-card">
                    <div class="eob-card-head"><div><div class="eob-card-title">Employment Details</div></div></div>
                    <div class="eob-card-body">
                        <div class="eob-table-wrap">
                            <table class="eob-table">
                                <thead>
                                    <tr>
                                        <th>Organisation</th>
                                        <th>Designation</th>
                                        <th>Period From</th>
                                        <th>Period To</th>
                                        <th>Annual CTC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($employee->employments as $employment)
                                        <tr>
                                            <td>{{ $employment->organisation ?: 'N/A' }}</td>
                                            <td>{{ $employment->designation ?: 'N/A' }}</td>
                                            <td>{{ optional($employment->period_from)->format('d M Y') ?: 'N/A' }}</td>
                                            <td>{{ optional($employment->period_to)->format('d M Y') ?: 'N/A' }}</td>
                                            <td>{{ $employment->annual_ctc ?: 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="eob-muted">No employment records added.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="eob-show-card">
                    <div class="eob-card-head"><div><div class="eob-card-title">Family Details</div></div></div>
                    <div class="eob-card-body">
                        <div class="eob-table-wrap">
                            <table class="eob-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Relation</th>
                                        <th>Occupation</th>
                                        <th>Date of Birth</th>
                                        <th>Mobile No</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($employee->familyDetails as $family)
                                        <tr>
                                            <td>{{ $family->name ?: 'N/A' }}</td>
                                            <td>{{ $family->relation ?: 'N/A' }}</td>
                                            <td>{{ $family->occupation ?: 'N/A' }}</td>
                                            <td>{{ optional($family->date_of_birth)->format('d M Y') ?: 'N/A' }}</td>
                                            <td>{{ $family->mobile_no ?: 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="eob-muted">No family details added.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="eob-show-card">
                    <div class="eob-card-head"><div><div class="eob-card-title">Professional Reference</div></div></div>
                    <div class="eob-card-body">
                        <div class="eob-show-grid">
                            <div class="eob-show-item"><div class="eob-show-label">Name</div><div class="eob-show-value">{{ $employee->reference_name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Organization Name</div><div class="eob-show-value">{{ $employee->reference_organization_name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Designation</div><div class="eob-show-value">{{ $employee->reference_designation ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Contact No</div><div class="eob-show-value">{{ $employee->reference_contact_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Mail ID</div><div class="eob-show-value">{{ $employee->reference_mail_id ?: 'N/A' }}</div></div>
                        </div>
                    </div>
                </div>

                <div class="eob-show-card">
                    <div class="eob-card-head"><div><div class="eob-card-title">Employee Salaries</div></div></div>
                    <div class="eob-card-body">
                        <div class="eob-show-grid">
                            <div class="eob-show-item"><div class="eob-show-label">Joining Date</div><div class="eob-show-value">{{ optional($employee->joining_date)->format('d M Y') ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Salary Effective From</div><div class="eob-show-value">{{ optional($employee->salary_effective_from)->format('d M Y') ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Salary Payment Mode</div><div class="eob-show-value">{{ $employee->salary_payment_mode ? ucwords(str_replace('_', ' ', $employee->salary_payment_mode)) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Gross Salary</div><div class="eob-show-value">{{ $employee->gross_salary !== null ? number_format((float) $employee->gross_salary, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Basic Salary</div><div class="eob-show-value">{{ $employee->basic_salary !== null ? number_format((float) $employee->basic_salary, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">HRA</div><div class="eob-show-value">{{ $employee->hra !== null ? number_format((float) $employee->hra, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Special Allowance</div><div class="eob-show-value">{{ $employee->special_allowance !== null ? number_format((float) $employee->special_allowance, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Other Allowance</div><div class="eob-show-value">{{ $employee->other_allowance !== null ? number_format((float) $employee->other_allowance, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">PF Enabled</div><div class="eob-show-value">{{ $employee->pf_enabled ? 'Yes' : 'No' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">UAN No</div><div class="eob-show-value">{{ $employee->uan_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">PF Account No</div><div class="eob-show-value">{{ $employee->pf_account_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">PF Employee Contribution</div><div class="eob-show-value">{{ $employee->pf_employee_contribution !== null ? number_format((float) $employee->pf_employee_contribution, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">PF Employer Contribution</div><div class="eob-show-value">{{ $employee->pf_employer_contribution !== null ? number_format((float) $employee->pf_employer_contribution, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">ESI Enabled</div><div class="eob-show-value">{{ $employee->esi_enabled ? 'Yes' : 'No' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">ESI No</div><div class="eob-show-value">{{ $employee->esi_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">ESI Employee Contribution</div><div class="eob-show-value">{{ $employee->esi_employee_contribution !== null ? number_format((float) $employee->esi_employee_contribution, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">ESI Employer Contribution</div><div class="eob-show-value">{{ $employee->esi_employer_contribution !== null ? number_format((float) $employee->esi_employer_contribution, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Professional Tax</div><div class="eob-show-value">{{ $employee->professional_tax !== null ? number_format((float) $employee->professional_tax, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">TDS Amount</div><div class="eob-show-value">{{ $employee->tds_amount !== null ? number_format((float) $employee->tds_amount, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Loan Deduction</div><div class="eob-show-value">{{ $employee->loan_deduction !== null ? number_format((float) $employee->loan_deduction, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Other Deduction</div><div class="eob-show-value">{{ $employee->other_deduction !== null ? number_format((float) $employee->other_deduction, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Total Deduction</div><div class="eob-show-value">{{ $employee->total_deduction !== null ? number_format((float) $employee->total_deduction, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Net Salary</div><div class="eob-show-value">{{ $employee->net_salary !== null ? number_format((float) $employee->net_salary, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Deduction Notes</div><div class="eob-show-value">{{ $employee->deduction_notes ?: 'N/A' }}</div></div>
                        </div>
                    </div>
                </div>
                            <div class="eob-show-item"><div class="eob-show-label">Gross Salary</div><div class="eob-show-value">{{ $employee->gross_salary !== null ? number_format((float) $employee->gross_salary, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Basic Salary</div><div class="eob-show-value">{{ $employee->basic_salary !== null ? number_format((float) $employee->basic_salary, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">HRA</div><div class="eob-show-value">{{ $employee->hra !== null ? number_format((float) $employee->hra, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Special Allowance</div><div class="eob-show-value">{{ $employee->special_allowance !== null ? number_format((float) $employee->special_allowance, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Other Allowance</div><div class="eob-show-value">{{ $employee->other_allowance !== null ? number_format((float) $employee->other_allowance, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">PF Enabled</div><div class="eob-show-value">{{ $employee->pf_enabled ? 'Yes' : 'No' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">UAN No</div><div class="eob-show-value">{{ $employee->uan_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">PF Account No</div><div class="eob-show-value">{{ $employee->pf_account_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">PF Employee Contribution</div><div class="eob-show-value">{{ $employee->pf_employee_contribution !== null ? number_format((float) $employee->pf_employee_contribution, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">PF Employer Contribution</div><div class="eob-show-value">{{ $employee->pf_employer_contribution !== null ? number_format((float) $employee->pf_employer_contribution, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">ESI Enabled</div><div class="eob-show-value">{{ $employee->esi_enabled ? 'Yes' : 'No' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">ESI No</div><div class="eob-show-value">{{ $employee->esi_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">ESI Employee Contribution</div><div class="eob-show-value">{{ $employee->esi_employee_contribution !== null ? number_format((float) $employee->esi_employee_contribution, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">ESI Employer Contribution</div><div class="eob-show-value">{{ $employee->esi_employer_contribution !== null ? number_format((float) $employee->esi_employer_contribution, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Professional Tax</div><div class="eob-show-value">{{ $employee->professional_tax !== null ? number_format((float) $employee->professional_tax, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">TDS Amount</div><div class="eob-show-value">{{ $employee->tds_amount !== null ? number_format((float) $employee->tds_amount, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Loan Deduction</div><div class="eob-show-value">{{ $employee->loan_deduction !== null ? number_format((float) $employee->loan_deduction, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Other Deduction</div><div class="eob-show-value">{{ $employee->other_deduction !== null ? number_format((float) $employee->other_deduction, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Total Deduction</div><div class="eob-show-value">{{ $employee->total_deduction !== null ? number_format((float) $employee->total_deduction, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Net Salary</div><div class="eob-show-value">{{ $employee->net_salary !== null ? number_format((float) $employee->net_salary, 2) : 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Deduction Notes</div><div class="eob-show-value">{{ $employee->deduction_notes ?: 'N/A' }}</div></div>
                        </div>
                    </div>
                </div>

                <div class="eob-show-card">
                    <div class="eob-card-head"><div><div class="eob-card-title">Bank Account Details</div></div></div>
                    <div class="eob-card-body">
                        <div class="eob-show-grid">
                            <div class="eob-show-item"><div class="eob-show-label">Bank Name</div><div class="eob-show-value">{{ $employee->bank_name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Account Name</div><div class="eob-show-value">{{ $employee->bank_account_name ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Account No</div><div class="eob-show-value">{{ $employee->bank_account_no ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">IFSC Code</div><div class="eob-show-value">{{ $employee->bank_ifsc_code ?: 'N/A' }}</div></div>
                            <div class="eob-show-item"><div class="eob-show-label">Branch</div><div class="eob-show-value">{{ $employee->bank_branch ?: 'N/A' }}</div></div>
                        </div>
                    </div>
                </div>

                <div class="eob-show-card">
                    <div class="eob-card-head"><div><div class="eob-card-title">Document Uploads</div></div></div>
                    <div class="eob-card-body">
                        <div class="eob-doc-list">
                            @foreach($documentLabels as $field => $label)
                                @continue($field === 'signature' || $field === 'photograph')
                                <div class="eob-doc-card">
                                    <div class="eob-doc-title">{{ $label }}</div>
                                    @if($employee->{$field})
                                        <div class="eob-doc-sub">{{ basename($employee->{$field}) }}</div>
                                        <div class="eob-doc-actions">
                                            <a href="{{ $employee->getFileUrl($field) }}" target="_blank" class="eob-btn eob-btn-ghost eob-btn-sm">View</a>
                                            <a href="{{ $employee->getFileUrl($field) }}" download class="eob-btn eob-btn-ghost eob-btn-sm">Download</a>
                                        </div>
                                    @else
                                        <div class="eob-doc-sub">No file uploaded.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
