@php
    $employee = $employee ?? null;
    $portalUser = $employee?->portalUser;
    $selectedTlUserId = old('tl_user_id', $portalUser?->managerMappings?->first()?->manager_id);
    $portalBranchId = old('branch_id', $portalUser?->branch_id ?? ((($method ?? 'POST') === 'POST') ? auth()->user()?->branch_id : null));
    $portalEmail = old('portal_email', $portalUser?->email ?? '');
    $portalPasswordRequired = ($method ?? 'POST') === 'POST';
    $portalAccountRequired = $portalPasswordRequired || (bool) $portalUser;

    $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $statusOptions = ['active' => 'Active', 'resigned' => 'Resigned'];

    $defaultEducations = [
        ['qualification' => 'PG', 'institution_name' => '', 'year_of_passing' => '', 'percentage' => '', 'specialization' => ''],
        ['qualification' => 'UG', 'institution_name' => '', 'year_of_passing' => '', 'percentage' => '', 'specialization' => ''],
        ['qualification' => 'HSC / 12th', 'institution_name' => '', 'year_of_passing' => '', 'percentage' => '', 'specialization' => ''],
        ['qualification' => 'SSLC / 10th', 'institution_name' => '', 'year_of_passing' => '', 'percentage' => '', 'specialization' => ''],
    ];
    $defaultEmployments = [
        ['organisation' => '', 'designation' => '', 'period_from' => '', 'period_to' => '', 'annual_ctc' => ''],
        ['organisation' => '', 'designation' => '', 'period_from' => '', 'period_to' => '', 'annual_ctc' => ''],
        ['organisation' => '', 'designation' => '', 'period_from' => '', 'period_to' => '', 'annual_ctc' => ''],
    ];
    $defaultFamilyDetails = [
        ['name' => '', 'relation' => '', 'occupation' => '', 'date_of_birth' => '', 'mobile_no' => ''],
    ];

    $educations = old('educations');
    if (!is_array($educations)) {
        $educations = $employee
            ? $employee->educations->map(fn ($row) => [
                'qualification' => $row->qualification,
                'institution_name' => $row->institution_name,
                'year_of_passing' => $row->year_of_passing,
                'percentage' => $row->percentage,
                'specialization' => $row->specialization,
            ])->toArray()
            : $defaultEducations;
    }
    if ($educations === []) {
        $educations = $defaultEducations;
    }

    $employments = old('employments');
    if (!is_array($employments)) {
        $employments = $employee
            ? $employee->employments->map(fn ($row) => [
                'organisation' => $row->organisation,
                'designation' => $row->designation,
                'period_from' => optional($row->period_from)->format('Y-m-d'),
                'period_to' => optional($row->period_to)->format('Y-m-d'),
                'annual_ctc' => $row->annual_ctc,
            ])->toArray()
            : $defaultEmployments;
    }
    if ($employments === []) {
        $employments = $defaultEmployments;
    }

    $familyDetails = old('family_details');
    if (!is_array($familyDetails)) {
        $familyDetails = $employee
            ? $employee->familyDetails->map(fn ($row) => [
                'name' => $row->name,
                'relation' => $row->relation,
                'occupation' => $row->occupation,
                'date_of_birth' => optional($row->date_of_birth)->format('Y-m-d'),
                'mobile_no' => $row->mobile_no,
            ])->toArray()
            : $defaultFamilyDetails;
    }
    if ($familyDetails === []) {
        $familyDetails = $defaultFamilyDetails;
    }

    $wizardSteps = [
        ['key' => 'personal', 'number' => '01', 'title' => 'Personal Details', 'sub' => 'Basic profile and identity information.'],
        ['key' => 'portal', 'number' => '02', 'title' => 'Employee Portal Account', 'sub' => 'Login, branch, role, and TL mapping.'],
        ['key' => 'emergency', 'number' => '03', 'title' => 'Emergency Contact', 'sub' => 'Primary emergency reach-out person.'],
        ['key' => 'education', 'number' => '04', 'title' => 'Educational Details', 'sub' => 'Academic records and qualifications.'],
        ['key' => 'employment', 'number' => '05', 'title' => 'Employment Details', 'sub' => 'Previous organisations and CTC history.'],
        ['key' => 'family', 'number' => '06', 'title' => 'Family Details', 'sub' => 'Immediate family and contact details.'],
        ['key' => 'reference', 'number' => '07', 'title' => 'Professional Reference', 'sub' => 'Reference contact information.'],
        ['key' => 'salary', 'number' => '08', 'title' => 'Employee Salaries', 'sub' => 'Salary, statutory deductions, and payroll setup.'],
        ['key' => 'bank', 'number' => '09', 'title' => 'Bank Account Details', 'sub' => 'Payroll and bank information.'],
        ['key' => 'documents', 'number' => '10', 'title' => 'Document Uploads', 'sub' => 'Certificates and identity proofs.'],
    ];

    $errorStepMap = [
        'name' => 'personal',
        'father_name' => 'personal',
        'correspondence_address' => 'personal',
        'permanent_address' => 'personal',
        'mobile' => 'personal',
        'email' => 'personal',
        'date_of_birth' => 'personal',
        'joining_date' => 'personal',
        'blood_group' => 'personal',
        'marital_status' => 'personal',
        'date_of_marriage' => 'personal',
        'aadhaar_card_no' => 'personal',
        'pan_card_no' => 'personal',
        'photograph' => 'personal',
        'status' => 'personal',
        'employee_type' => 'personal',
        'portal_email' => 'portal',
        'portal_password' => 'portal',
        'branch_id' => 'portal',
        'role_id' => 'portal',
        'department_id' => 'portal',
        'tl_user_id' => 'portal',
        'emergency_contact_name' => 'emergency',
        'emergency_relation' => 'emergency',
        'emergency_contact_no' => 'emergency',
        'reference_name' => 'reference',
        'reference_organization_name' => 'reference',
        'reference_designation' => 'reference',
        'reference_contact_no' => 'reference',
        'reference_mail_id' => 'reference',
        'salary_effective_from' => 'salary',
        'gross_salary' => 'salary',
        'basic_salary' => 'salary',
        'hra' => 'salary',
        'special_allowance' => 'salary',
        'other_allowance' => 'salary',
        'esi_enabled' => 'salary',
        'esi_no' => 'salary',
        'esi_employee_contribution' => 'salary',
        'esi_employer_contribution' => 'salary',
        'pf_enabled' => 'salary',
        'uan_no' => 'salary',
        'pf_account_no' => 'salary',
        'pf_employee_contribution' => 'salary',
        'pf_employer_contribution' => 'salary',
        'professional_tax' => 'salary',
        'tds_amount' => 'salary',
        'loan_deduction' => 'salary',
        'other_deduction' => 'salary',
        'total_deduction' => 'salary',
        'net_salary' => 'salary',
        'salary_payment_mode' => 'salary',
        'deduction_notes' => 'salary',
        'bank_name' => 'bank',
        'bank_account_name' => 'bank',
        'bank_account_no' => 'bank',
        'bank_ifsc_code' => 'bank',
        'bank_branch' => 'bank',
    ];

    foreach (array_keys($errors->toArray()) as $errorKey) {
        if (str_starts_with($errorKey, 'educations.')) {
            $errorStepMap[$errorKey] = 'education';
        } elseif (str_starts_with($errorKey, 'employments.')) {
            $errorStepMap[$errorKey] = 'employment';
        } elseif (str_starts_with($errorKey, 'family_details.')) {
            $errorStepMap[$errorKey] = 'family';
        } elseif (str_starts_with($errorKey, 'document_')) {
            $errorStepMap[$errorKey] = 'documents';
        }
    }

    $currentStepKey = 'personal';
    foreach (array_keys($errors->toArray()) as $errorKey) {
        if (isset($errorStepMap[$errorKey])) {
            $currentStepKey = $errorStepMap[$errorKey];
            break;
        }
    }
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="employeeWizardForm" data-initial-step="{{ $currentStepKey }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="eob-wizard-shell">
        <aside class="eob-wizard-nav">
            <div class="eob-wizard-nav-head">
                <div class="eob-wizard-kicker">Onboarding Wizard</div>
                <div class="eob-wizard-title">Complete one section at a time</div>
                <div class="eob-wizard-copy">Move through each onboarding block step by step and submit everything together at the end.</div>
            </div>

            <div class="eob-wizard-steps">
                @foreach($wizardSteps as $index => $step)
                    <button type="button" class="eob-wizard-step-btn" data-step-target="{{ $step['key'] }}" data-step-index="{{ $index }}">
                        <span class="eob-wizard-step-no">{{ $step['number'] }}</span>
                        <span class="eob-wizard-step-text">
                            <span class="eob-wizard-step-title">{{ $step['title'] }}</span>
                            <span class="eob-wizard-step-sub">{{ $step['sub'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </aside>

        <div class="eob-wizard-main">
            <div class="eob-wizard-validation-summary" id="employeeWizardValidationSummary" role="alert" aria-live="polite"></div>
            <div class="eob-wizard-progress-card">
                <div>
                    <div class="eob-card-title" id="wizardActiveTitle">Personal Details</div>
                    <div class="eob-card-sub" id="wizardActiveSub">Basic profile and identity information.</div>
                    <div class="eob-required-note"><span class="eob-label-required">*</span> indicates mandatory fields.</div>
                </div>
                <div class="eob-wizard-progress-meta">
                    <span id="wizardStepCounter">Step 1 of {{ count($wizardSteps) }}</span>
                    <div class="eob-wizard-progress-track">
                        <span id="wizardProgressBar"></span>
                    </div>
                </div>
            </div>

            <div class="eob-wizard-panels">
                <section class="eob-card eob-wizard-panel" data-step="personal" data-step-title="Personal Details" data-step-sub="Basic profile and identity information.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Personal Details</div>
                            <div class="eob-card-sub">Basic profile, identification, and contact information.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-form-grid">
                            <div class="eob-group">
                                <label class="eob-label">Employee ID</label>
                                <input type="text" class="eob-input" id="generatedEmployeeIdInput" value="{{ $generatedEmployeeId }}" readonly>
                                <div class="eob-help">Auto-generated by the system and cannot be edited.</div>
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Name <span class="eob-label-required">*</span></label>
                                <input type="text" name="name" class="eob-input" value="{{ old('name', $employee?->name) }}" required>
                                @error('name')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Father's Name</label>
                                <input type="text" name="father_name" class="eob-input" value="{{ old('father_name', $employee?->father_name) }}">
                                @error('father_name')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group full">
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                    <label class="eob-label mb-0">Correspondence Address</label>
                                    <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" data-copy-address data-copy-source="correspondence_address" data-copy-target="permanent_address">
                                        Copy to Permanent
                                    </button>
                                </div>
                                <textarea name="correspondence_address" class="eob-textarea">{{ old('correspondence_address', $employee?->correspondence_address) }}</textarea>
                                @error('correspondence_address')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group full">
                                <label class="eob-label">Permanent Address</label>
                                <textarea name="permanent_address" class="eob-textarea">{{ old('permanent_address', $employee?->permanent_address) }}</textarea>
                                @error('permanent_address')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Mobile <span class="eob-label-required">*</span></label>
                                <input type="text" name="mobile" class="eob-input" value="{{ old('mobile', $employee?->mobile) }}" required>
                                @error('mobile')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Email ID <span class="eob-label-required">*</span></label>
                                <input type="email" name="email" class="eob-input" value="{{ old('email', $employee?->email) }}" required>
                                @error('email')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Date of Birth <span class="eob-label-required">*</span></label>
                                <input type="date" name="date_of_birth" class="eob-input" value="{{ old('date_of_birth', optional($employee?->date_of_birth)->format('Y-m-d')) }}" required>
                                @error('date_of_birth')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Joining Date</label>
                                <input type="date" name="joining_date" class="eob-input" value="{{ old('joining_date', optional($employee?->joining_date)->format('Y-m-d')) }}">
                                @error('joining_date')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Blood Group</label>
                                <select name="blood_group" class="eob-select">
                                    <option value="">Select blood group</option>
                                    @foreach($bloodGroups as $bloodGroup)
                                        <option value="{{ $bloodGroup }}" @selected(old('blood_group', $employee?->blood_group) === $bloodGroup)>{{ $bloodGroup }}</option>
                                    @endforeach
                                </select>
                                @error('blood_group')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Marital Status <span class="eob-label-required">*</span></label>
                                <div class="eob-radio-group">
                                    <label class="eob-radio">
                                        <input type="radio" name="marital_status" value="single" @checked(old('marital_status', $employee?->marital_status ?? 'single') === 'single')>
                                        <span>Single</span>
                                    </label>
                                    <label class="eob-radio">
                                        <input type="radio" name="marital_status" value="married" @checked(old('marital_status', $employee?->marital_status) === 'married')>
                                        <span>Married</span>
                                    </label>
                                </div>
                                @error('marital_status')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group" id="marriageDateGroup" style="{{ old('marital_status', $employee?->marital_status ?? 'single') === 'married' ? '' : 'display:none;' }}">
                                <label class="eob-label">Date of Marriage</label>
                                <input type="date" name="date_of_marriage" class="eob-input" value="{{ old('date_of_marriage', optional($employee?->date_of_marriage)->format('Y-m-d')) }}">
                                @error('date_of_marriage')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Aadhaar Card No</label>
                                <input type="text" name="aadhaar_card_no" class="eob-input" value="{{ old('aadhaar_card_no', $employee?->aadhaar_card_no) }}">
                                @error('aadhaar_card_no')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Pan Card No</label>
                                <input type="text" name="pan_card_no" class="eob-input" value="{{ old('pan_card_no', $employee?->pan_card_no) }}">
                                @error('pan_card_no')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Photograph</label>
                                <input type="file" name="photograph" class="eob-input" accept=".jpg,.jpeg,.png">
                                @if($employee?->photograph)
                                    <div class="eob-file-links">
                                        <a href="{{ $employee->getFileUrl('photograph') }}" target="_blank" class="eob-btn eob-btn-ghost eob-btn-sm">View Existing</a>
                                    </div>
                                @endif
                                @error('photograph')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Status <span class="eob-label-required">*</span></label>
                                <select name="status" class="eob-select" required>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $employee?->status ?? 'active') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Employee Type <span class="eob-label-required">*</span></label>
                                <select name="employee_type" class="eob-select" required>
                                    <option value="billable" @selected(old('employee_type', $employee?->employee_type ?? 'billable') === 'billable')>Billable</option>
                                    <option value="non_billable" @selected(old('employee_type', $employee?->employee_type ?? 'billable') === 'non_billable')>Non Billable</option>
                                </select>
                                @error('employee_type')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="portal" data-step-title="Employee Portal Account" data-step-sub="Login, branch, role, and TL mapping.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Employee Portal Account</div>
                            <div class="eob-card-sub">Create the login account and place the employee under the correct TL.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-form-grid">
                            <div class="eob-group">
                                <label class="eob-label">User Email @if($portalAccountRequired)<span class="eob-label-required">*</span>@endif</label>
                                <input
                                    type="email"
                                    name="portal_email"
                                    id="employeePortalEmail"
                                    class="eob-input"
                                    value="{{ $portalEmail }}"
                                    autocomplete="username"
                                    @if($portalAccountRequired) required @endif
                                >
                                <div class="eob-help">This email will be used for employee portal login.</div>
                                @error('portal_email')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Password @if($portalPasswordRequired)<span class="eob-label-required">*</span>@endif</label>
                                <input
                                    type="password"
                                    name="portal_password"
                                    class="eob-input"
                                    autocomplete="new-password"
                                    @if($portalPasswordRequired) required @endif
                                >
                                <div class="eob-help">{{ $portalPasswordRequired ? 'Minimum 8 characters.' : 'Leave blank to keep the current password.' }}</div>
                                @error('portal_password')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Branch @if($portalAccountRequired)<span class="eob-label-required">*</span>@endif</label>
                                <select name="branch_id" class="eob-select" id="employeeBranchSelect" @if($portalAccountRequired) required @endif>
                                    <option value="">Select branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) $portalBranchId === (string) $branch->id)>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Department @if($portalAccountRequired)<span class="eob-label-required">*</span>@endif</label>
                                <select name="department_id" class="eob-select" id="employeeDepartmentSelect" @if($portalAccountRequired) required @endif>
                                    <option value="">Select department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id', $employee?->department_id) === (string) $department->id)>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Role @if($portalAccountRequired)<span class="eob-label-required">*</span>@endif</label>
                                <select name="role_id" class="eob-select" id="employeeRoleSelect" @if($portalAccountRequired) required @endif>
                                    <option value="">Select role</option>
                                    @foreach($roles as $role)
                                        <option
                                            value="{{ $role->id }}"
                                            data-department-id="{{ $role->department_id }}"
                                            data-department-name="{{ $role->department?->name ?? '' }}"
                                            data-parent-role-id="{{ $role->roleParentMapping?->parent_role_id ?? '' }}"
                                            data-parent-role-name="{{ $role->roleParentMapping?->parentRole ? ($role->roleParentMapping->parentRole->display_name ?: \Illuminate\Support\Str::of(\Illuminate\Support\Str::afterLast($role->roleParentMapping->parentRole->name, '__'))->replace('_', ' ')->title()->value()) : '' }}"
                                            @selected((string) old('role_id', $employee?->role_id) === (string) $role->id)
                                        >
                                            {{ $role->display_name ?: ucfirst(str_replace('_', ' ', \Illuminate\Support\Str::afterLast($role->name, '__'))) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="eob-help">After selecting a department, only roles from that department will be shown.</div>
                                @error('role_id')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">TL Mapping @if($portalAccountRequired)<span class="eob-label-required">*</span>@endif</label>
                                <select name="tl_user_id" class="eob-select" id="employeeTlSelect" data-selected-tl="{{ $selectedTlUserId }}" @if($portalAccountRequired) required @endif>
                                    <option value="">Select department and role first</option>
                                    @foreach($tlUsers as $tlUser)
                                        <option
                                            value="{{ $tlUser['id'] }}"
                                            data-branch-id="{{ $tlUser['branch_id'] }}"
                                            data-department-ids="{{ implode(',', $tlUser['department_ids']) }}"
                                            data-role-ids="{{ implode(',', $tlUser['role_ids'] ?? []) }}"
                                            @selected((string) $selectedTlUserId === (string) $tlUser['id'])
                                        >
                                            {{ $tlUser['name'] }} - {{ $tlUser['role_label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="eob-help" id="employeeTlHelp">TLs will appear after department and role selection.</div>
                                @error('tl_user_id')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group full">
                                <label class="eob-label">Portal Mapping Summary</label>
                                <div class="eob-help" id="employeePortalMappingSummary">Select department and role to view available TLs and current mapping details.</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="emergency" data-step-title="Emergency Contact" data-step-sub="Primary emergency reach-out person.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Emergency Contact</div>
                            <div class="eob-card-sub">Store the primary person to reach during emergencies.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-form-grid">
                            <div class="eob-group">
                                <label class="eob-label">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name" class="eob-input" value="{{ old('emergency_contact_name', $employee?->emergency_contact_name) }}">
                                @error('emergency_contact_name')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Relation</label>
                                <input type="text" name="emergency_relation" class="eob-input" value="{{ old('emergency_relation', $employee?->emergency_relation) }}">
                                @error('emergency_relation')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Contact No</label>
                                <input type="text" name="emergency_contact_no" class="eob-input" value="{{ old('emergency_contact_no', $employee?->emergency_contact_no) }}">
                                @error('emergency_contact_no')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="education" data-step-title="Educational Details" data-step-sub="Academic records and qualifications.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Educational Details</div>
                            <div class="eob-card-sub">Default rows are included and you can add more if needed.</div>
                        </div>
                        <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" data-add-row="education">Add Row</button>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-table-wrap">
                            <table class="eob-table">
                                <thead>
                                    <tr>
                                        <th>Qualification</th>
                                        <th>Institution Name</th>
                                        <th>Year of Passing</th>
                                        <th>Percentage</th>
                                        <th>Specialization</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="educationRows">
                                    @foreach($educations as $index => $education)
                                        <tr>
                                            <td>
                                                <input type="text" name="educations[{{ $index }}][qualification]" class="eob-input" value="{{ $education['qualification'] ?? '' }}">
                                                @error("educations.$index.qualification")<div class="eob-error">{{ $message }}</div>@enderror
                                            </td>
                                            <td><input type="text" name="educations[{{ $index }}][institution_name]" class="eob-input" value="{{ $education['institution_name'] ?? '' }}"></td>
                                            <td><input type="text" name="educations[{{ $index }}][year_of_passing]" class="eob-input" value="{{ $education['year_of_passing'] ?? '' }}"></td>
                                            <td><input type="text" name="educations[{{ $index }}][percentage]" class="eob-input" value="{{ $education['percentage'] ?? '' }}"></td>
                                            <td><input type="text" name="educations[{{ $index }}][specialization]" class="eob-input" value="{{ $education['specialization'] ?? '' }}"></td>
                                            <td><button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-row>Remove</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="employment" data-step-title="Employment Details" data-step-sub="Previous organisations and CTC history.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Employment Details</div>
                            <div class="eob-card-sub">Capture the employee's previous organisations and compensation history.</div>
                        </div>
                        <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" data-add-row="employment">Add Row</button>
                    </div>
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="employmentRows">
                                    @foreach($employments as $index => $employment)
                                        <tr>
                                            <td><input type="text" name="employments[{{ $index }}][organisation]" class="eob-input" value="{{ $employment['organisation'] ?? '' }}"></td>
                                            <td><input type="text" name="employments[{{ $index }}][designation]" class="eob-input" value="{{ $employment['designation'] ?? '' }}"></td>
                                            <td><input type="date" name="employments[{{ $index }}][period_from]" class="eob-input" value="{{ $employment['period_from'] ?? '' }}"></td>
                                            <td><input type="date" name="employments[{{ $index }}][period_to]" class="eob-input" value="{{ $employment['period_to'] ?? '' }}"></td>
                                            <td><input type="text" name="employments[{{ $index }}][annual_ctc]" class="eob-input" value="{{ $employment['annual_ctc'] ?? '' }}"></td>
                                            <td><button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-row>Remove</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="family" data-step-title="Family Details" data-step-sub="Immediate family and contact details.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Family Details</div>
                            <div class="eob-card-sub">Add immediate family details with occupation and mobile information.</div>
                        </div>
                        <button type="button" class="eob-btn eob-btn-ghost eob-btn-sm" data-add-row="family">Add Row</button>
                    </div>
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="familyRows">
                                    @foreach($familyDetails as $index => $family)
                                        <tr>
                                            <td><input type="text" name="family_details[{{ $index }}][name]" class="eob-input" value="{{ $family['name'] ?? '' }}"></td>
                                            <td><input type="text" name="family_details[{{ $index }}][relation]" class="eob-input" value="{{ $family['relation'] ?? '' }}"></td>
                                            <td><input type="text" name="family_details[{{ $index }}][occupation]" class="eob-input" value="{{ $family['occupation'] ?? '' }}"></td>
                                            <td><input type="date" name="family_details[{{ $index }}][date_of_birth]" class="eob-input" value="{{ $family['date_of_birth'] ?? '' }}"></td>
                                            <td><input type="text" name="family_details[{{ $index }}][mobile_no]" class="eob-input" value="{{ $family['mobile_no'] ?? '' }}"></td>
                                            <td><button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-row>Remove</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="reference" data-step-title="Professional Reference" data-step-sub="Reference contact information.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Professional Reference</div>
                            <div class="eob-card-sub">One reference is enough for now, but you can expand later if needed.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-form-grid">
                            <div class="eob-group">
                                <label class="eob-label">Name</label>
                                <input type="text" name="reference_name" class="eob-input" value="{{ old('reference_name', $employee?->reference_name) }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Organization Name</label>
                                <input type="text" name="reference_organization_name" class="eob-input" value="{{ old('reference_organization_name', $employee?->reference_organization_name) }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Designation</label>
                                <input type="text" name="reference_designation" class="eob-input" value="{{ old('reference_designation', $employee?->reference_designation) }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Contact No</label>
                                <input type="text" name="reference_contact_no" class="eob-input" value="{{ old('reference_contact_no', $employee?->reference_contact_no) }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Mail ID</label>
                                <input type="email" name="reference_mail_id" class="eob-input" value="{{ old('reference_mail_id', $employee?->reference_mail_id) }}">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="salary" data-step-title="Employee Salaries" data-step-sub="Salary, statutory deductions, and payroll setup.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Employee Salaries</div>
                            <div class="eob-card-sub">Capture gross salary, PF, ESI, deductions, and payroll-related account details.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-form-grid">
                            <div class="eob-group">
                                <label class="eob-label">Salary Effective From</label>
                                <input type="date" name="salary_effective_from" class="eob-input" value="{{ old('salary_effective_from', optional($employee?->salary_effective_from)->format('Y-m-d')) }}">
                                @error('salary_effective_from')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Salary Payment Mode</label>
                                <select name="salary_payment_mode" class="eob-select">
                                    <option value="">Select payment mode</option>
                                    @foreach(['bank_transfer' => 'Bank Transfer', 'cash' => 'Cash', 'cheque' => 'Cheque', 'upi' => 'UPI'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('salary_payment_mode', $employee?->salary_payment_mode) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('salary_payment_mode')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">CTC Salary</label>
                                <input type="number" step="0.01" min="0" name="gross_salary" class="eob-input" data-salary-input="gross_salary" value="{{ old('gross_salary', $employee?->gross_salary) }}">
                                @error('gross_salary')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Basic Salary</label>
                                <input type="number" step="0.01" min="0" name="basic_salary" class="eob-input" data-salary-input="basic_salary" value="{{ old('basic_salary', $employee?->basic_salary) }}" readonly>
                                <div class="eob-help">Auto-calculated as 50% of gross salary. PF is estimated from this when enabled.</div>
                                @error('basic_salary')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">HRA</label>
                                <input type="number" step="0.01" min="0" name="hra" class="eob-input" data-salary-input="hra" value="{{ old('hra', $employee?->hra) }}" readonly>
                                @error('hra')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Travel Allowance</label>
                                <input type="number" step="0.01" min="0" name="special_allowance" class="eob-input" data-salary-input="special_allowance" value="{{ old('special_allowance', $employee?->special_allowance) }}" readonly>
                                @error('special_allowance')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Other Allowance</label>
                                <input type="number" step="0.01" min="0" name="other_allowance" class="eob-input" data-salary-input="other_allowance" value="{{ old('other_allowance', $employee?->other_allowance) }}" readonly>
                                <div class="eob-help">Balance amount after basic, HRA, and special allowance split.</div>
                                @error('other_allowance')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Professional Tax</label>
                                <input type="number" step="0.01" min="0" name="professional_tax" class="eob-input" data-salary-input="professional_tax" value="{{ old('professional_tax', $employee?->professional_tax) }}">
                                @error('professional_tax')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">TDS Amount</label>
                                <input type="number" step="0.01" min="0" name="tds_amount" class="eob-input" data-salary-input="tds_amount" value="{{ old('tds_amount', $employee?->tds_amount) }}">
                                @error('tds_amount')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Loan Deduction</label>
                                <input type="number" step="0.01" min="0" name="loan_deduction" class="eob-input" data-salary-input="loan_deduction" value="{{ old('loan_deduction', $employee?->loan_deduction) }}">
                                @error('loan_deduction')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Other Deduction</label>
                                <input type="number" step="0.01" min="0" name="other_deduction" class="eob-input" data-salary-input="other_deduction" value="{{ old('other_deduction', $employee?->other_deduction) }}">
                                @error('other_deduction')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="eob-group full">
                                <div class="eob-radio-group">
                                    <label class="eob-radio">
                                        <input type="hidden" name="pf_enabled" value="0">
                                        <input type="checkbox" name="pf_enabled" value="1" data-salary-toggle="pf" @checked((bool) old('pf_enabled', $employee?->pf_enabled))>
                                        <span>Enable PF Calculation</span>
                                    </label>
                                    <label class="eob-radio">
                                        <input type="hidden" name="esi_enabled" value="0">
                                        <input type="checkbox" name="esi_enabled" value="1" data-salary-toggle="esi" @checked((bool) old('esi_enabled', $employee?->esi_enabled))>
                                        <span>Enable ESI Calculation</span>
                                    </label>
                                </div>
                            </div>

                            <div class="eob-group">
                                <label class="eob-label">UAN No</label>
                                <input type="text" name="uan_no" class="eob-input" value="{{ old('uan_no', $employee?->uan_no) }}">
                                @error('uan_no')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">PF Account No</label>
                                <input type="text" name="pf_account_no" class="eob-input" value="{{ old('pf_account_no', $employee?->pf_account_no) }}">
                                @error('pf_account_no')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">PF Employee Contribution</label>
                                <input type="number" step="0.01" min="0" name="pf_employee_contribution" class="eob-input" data-salary-output="pf_employee_contribution" value="{{ old('pf_employee_contribution', $employee?->pf_employee_contribution) }}" readonly>
                                @error('pf_employee_contribution')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">PF Employer Contribution</label>
                                <input type="number" step="0.01" min="0" name="pf_employer_contribution" class="eob-input" data-salary-output="pf_employer_contribution" value="{{ old('pf_employer_contribution', $employee?->pf_employer_contribution) }}" readonly>
                                @error('pf_employer_contribution')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="eob-group">
                                <label class="eob-label">ESI No</label>
                                <input type="text" name="esi_no" class="eob-input" value="{{ old('esi_no', $employee?->esi_no) }}">
                                <div class="eob-help" id="esiEligibilityHelp">ESI amount is calculated as 4% of gross salary whenever ESI is enabled.</div>
                                @error('esi_no')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">ESI Employee Contribution</label>
                                <input type="number" step="0.01" min="0" name="esi_employee_contribution" class="eob-input" data-salary-output="esi_employee_contribution" value="{{ old('esi_employee_contribution', $employee?->esi_employee_contribution) }}" readonly>
                                @error('esi_employee_contribution')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">ESI Employer Contribution</label>
                                <input type="number" step="0.01" min="0" name="esi_employer_contribution" class="eob-input" data-salary-output="esi_employer_contribution" value="{{ old('esi_employer_contribution', $employee?->esi_employer_contribution) }}" readonly>
                                @error('esi_employer_contribution')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Total Deduction</label>
                                <input type="number" step="0.01" min="0" name="total_deduction" class="eob-input" data-salary-output="total_deduction" value="{{ old('total_deduction', $employee?->total_deduction) }}" readonly>
                                @error('total_deduction')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Net Salary</label>
                                <input type="number" step="0.01" min="0" name="net_salary" class="eob-input" data-salary-output="net_salary" value="{{ old('net_salary', $employee?->net_salary) }}" readonly>
                                @error('net_salary')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                            <div class="eob-group full">
                                <label class="eob-label">Deduction Notes</label>
                                <textarea name="deduction_notes" class="eob-textarea">{{ old('deduction_notes', $employee?->deduction_notes) }}</textarea>
                                @error('deduction_notes')<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="bank" data-step-title="Bank Account Details" data-step-sub="Payroll and bank information.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Bank Account Details</div>
                            <div class="eob-card-sub">Optional banking details for payroll and reimbursements.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-form-grid">
                            <div class="eob-group">
                                <label class="eob-label">Bank Name</label>
                                <input type="text" name="bank_name" class="eob-input" value="{{ old('bank_name', $employee?->bank_name) }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Account Name</label>
                                <input type="text" name="bank_account_name" class="eob-input" value="{{ old('bank_account_name', $employee?->bank_account_name) }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Account No</label>
                                <input type="text" name="bank_account_no" class="eob-input" value="{{ old('bank_account_no', $employee?->bank_account_no) }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">IFSC Code</label>
                                <input type="text" name="bank_ifsc_code" class="eob-input" value="{{ old('bank_ifsc_code', $employee?->bank_ifsc_code) }}">
                            </div>
                            <div class="eob-group">
                                <label class="eob-label">Branch</label>
                                <input type="text" name="bank_branch" class="eob-input" value="{{ old('bank_branch', $employee?->bank_branch) }}">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="eob-card eob-wizard-panel" data-step="documents" data-step-title="Document Uploads" data-step-sub="Certificates and identity proofs.">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Document Uploads</div>
                            <div class="eob-card-sub">Allowed formats: PDF, JPG, JPEG, PNG. Maximum size: 5MB.</div>
                        </div>
                    </div>
                    <div class="eob-card-body">
                        <div class="eob-file-grid">
                            @foreach($documentLabels as $field => $label)
                                @continue(in_array($field, ['photograph', 'signature'], true))
                                <div class="eob-file-card">
                                    <div>
                                        <label class="eob-label">{{ $label }}</label>
                                        <div class="eob-help">Upload a fresh file or keep the existing one.</div>
                                    </div>
                                    <input type="file" name="{{ $field }}" class="eob-input" accept=".pdf,.jpg,.jpeg,.png">
                                    @if($employee?->{$field})
                                        <div class="eob-file-meta">
                                            <div class="eob-file-name">{{ basename($employee->{$field}) }}</div>
                                            <div class="eob-file-links">
                                                <a href="{{ $employee->getFileUrl($field) }}" target="_blank" class="eob-btn eob-btn-ghost eob-btn-sm">View</a>
                                                <a href="{{ $employee->getFileUrl($field) }}" download class="eob-btn eob-btn-ghost eob-btn-sm">Download</a>
                                            </div>
                                        </div>
                                    @endif
                                    @error($field)<div class="eob-error">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

            </div>

            <div class="eob-foot eob-wizard-footer">
                <a href="{{ $cancelRoute }}" class="eob-btn eob-btn-ghost">Cancel</a>
                <div class="eob-wizard-footer-actions">
                    <button type="button" class="eob-btn eob-btn-ghost" id="wizardPrevBtn">Previous</button>
                    <button type="button" class="eob-btn eob-btn-primary" id="wizardNextBtn">Next Step</button>
                    <button type="submit" class="eob-btn eob-btn-primary" id="wizardSubmitBtn">{{ $submitLabel }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="educationRowTemplate">
    <tr>
        <td><input type="text" name="educations[__INDEX__][qualification]" class="eob-input"></td>
        <td><input type="text" name="educations[__INDEX__][institution_name]" class="eob-input"></td>
        <td><input type="text" name="educations[__INDEX__][year_of_passing]" class="eob-input"></td>
        <td><input type="text" name="educations[__INDEX__][percentage]" class="eob-input"></td>
        <td><input type="text" name="educations[__INDEX__][specialization]" class="eob-input"></td>
        <td><button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-row>Remove</button></td>
    </tr>
</template>

<template id="employmentRowTemplate">
    <tr>
        <td><input type="text" name="employments[__INDEX__][organisation]" class="eob-input"></td>
        <td><input type="text" name="employments[__INDEX__][designation]" class="eob-input"></td>
        <td><input type="date" name="employments[__INDEX__][period_from]" class="eob-input"></td>
        <td><input type="date" name="employments[__INDEX__][period_to]" class="eob-input"></td>
        <td><input type="text" name="employments[__INDEX__][annual_ctc]" class="eob-input"></td>
        <td><button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-row>Remove</button></td>
    </tr>
</template>

<template id="familyRowTemplate">
    <tr>
        <td><input type="text" name="family_details[__INDEX__][name]" class="eob-input"></td>
        <td><input type="text" name="family_details[__INDEX__][relation]" class="eob-input"></td>
        <td><input type="text" name="family_details[__INDEX__][occupation]" class="eob-input"></td>
        <td><input type="date" name="family_details[__INDEX__][date_of_birth]" class="eob-input"></td>
        <td><input type="text" name="family_details[__INDEX__][mobile_no]" class="eob-input"></td>
        <td><button type="button" class="eob-btn eob-btn-danger eob-btn-sm" data-remove-row>Remove</button></td>
    </tr>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('employeeWizardForm');
    const addressCopyButtons = Array.from(document.querySelectorAll('[data-copy-address]'));
    const maritalInputs = document.querySelectorAll('input[name="marital_status"]');
    const marriageDateGroup = document.getElementById('marriageDateGroup');
    const roleSelect = document.getElementById('employeeRoleSelect');
    const departmentSelect = document.getElementById('employeeDepartmentSelect');
    const branchSelect = document.getElementById('employeeBranchSelect');
    const tlSelect = document.getElementById('employeeTlSelect');
    const tlHelp = document.getElementById('employeeTlHelp');
    const personalEmailInput = form.querySelector('input[name="email"]');
    const portalEmailInput = document.getElementById('employeePortalEmail');
    const stepButtons = Array.from(document.querySelectorAll('.eob-wizard-step-btn'));
    const panels = Array.from(document.querySelectorAll('.eob-wizard-panel'));
    const prevButton = document.getElementById('wizardPrevBtn');
    const nextButton = document.getElementById('wizardNextBtn');
    const submitButton = document.getElementById('wizardSubmitBtn');
    const stepCounter = document.getElementById('wizardStepCounter');
    const progressBar = document.getElementById('wizardProgressBar');
    const activeTitle = document.getElementById('wizardActiveTitle');
    const activeSub = document.getElementById('wizardActiveSub');
    const validationSummary = document.getElementById('employeeWizardValidationSummary');
    const tlUsers = @json($tlUsers);
    const serverErrors = @json($errors->toArray());
    const currentPortalUserId = '{{ $portalUser?->id }}';
    const portalSummary = document.getElementById('employeePortalMappingSummary');

    function toggleMarriageDate() {
        const selected = document.querySelector('input[name="marital_status"]:checked');
        marriageDateGroup.style.display = selected && selected.value === 'married' ? '' : 'none';
    }

    maritalInputs.forEach(function (input) {
        input.addEventListener('change', toggleMarriageDate);
    });
    toggleMarriageDate();

    const isEdit = {{ $employee ? 'true' : 'false' }};

    if (branchSelect && !isEdit) {
        branchSelect.addEventListener('change', function () {
            const branchId = branchSelect.value;
            fetch(`/employee-onboarding/generate-id?branch_id=${branchId}`)
                .then(res => res.json())
                .then(data => {
                    const empIdInput = document.getElementById('generatedEmployeeIdInput');
                    if (empIdInput && data.employee_id) {
                        empIdInput.value = data.employee_id;
                    }
                })
                .catch(err => console.error('Error fetching generated Employee ID:', err));
        });
    }

    if (personalEmailInput && portalEmailInput) {
        personalEmailInput.addEventListener('input', function () {
            if (!portalEmailInput.dataset.touched && portalEmailInput.value.trim() === '') {
                portalEmailInput.value = personalEmailInput.value.trim();
            }
        });

        portalEmailInput.addEventListener('input', function () {
            portalEmailInput.dataset.touched = '1';
        });
    }

    const allRoleOptions = roleSelect ? Array.from(roleSelect.querySelectorAll('option')).map(function (option) {
        return {
            value: option.value,
            label: option.textContent,
            departmentId: option.getAttribute('data-department-id') || '',
            parentRoleId: option.getAttribute('data-parent-role-id') || '',
            parentRoleLabel: option.getAttribute('data-parent-role-name') || '',
            selected: option.selected
        };
    }) : [];

    function syncDepartmentFromRole(force) {
        if (!roleSelect || !departmentSelect) {
            return;
        }

        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const departmentId = selectedOption ? selectedOption.getAttribute('data-department-id') : '';

        if (!departmentId) {
            return;
        }

        if (force || !departmentSelect.value) {
            departmentSelect.value = departmentId;
        }
    }

    function filterRolesByDepartment() {
        if (!roleSelect || !departmentSelect) {
            return;
        }

        const selectedDepartmentId = departmentSelect.value;
        const selectedRoleId = roleSelect.value;
        const filteredRoleOptions = allRoleOptions.filter(function (option) {
            if (option.value === '') {
                return true;
            }

            if (selectedDepartmentId === '') {
                return true;
            }

            return option.departmentId === '' || option.departmentId === selectedDepartmentId;
        });

        roleSelect.innerHTML = '';

        filteredRoleOptions.forEach(function (option) {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.label;

            if (option.departmentId) {
                optionElement.setAttribute('data-department-id', option.departmentId);
            }

            if (option.parentRoleId) {
                optionElement.setAttribute('data-parent-role-id', option.parentRoleId);
            }

            if (option.parentRoleLabel) {
                optionElement.setAttribute('data-parent-role-name', option.parentRoleLabel);
            }

            if (option.value === selectedRoleId) {
                optionElement.selected = true;
            }

            roleSelect.appendChild(optionElement);
        });

        const selectedRoleStillExists = filteredRoleOptions.some(function (option) {
            return option.value === selectedRoleId;
        });

        if (!selectedRoleStillExists) {
            roleSelect.value = '';
        }
    }

    function selectedRoleMeta() {
        if (!roleSelect || !roleSelect.value) {
            return null;
        }

        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const savedOption = allRoleOptions.find(function (option) {
            return String(option.value) === String(roleSelect.value);
        });

        return {
            value: roleSelect.value,
            label: selectedOption?.textContent.trim() || savedOption?.label || '',
            departmentId: selectedOption?.getAttribute('data-department-id') || savedOption?.departmentId || '',
            parentRoleId: selectedOption?.getAttribute('data-parent-role-id') || savedOption?.parentRoleId || '',
            parentRoleLabel: selectedOption?.getAttribute('data-parent-role-name') || savedOption?.parentRoleLabel || '',
        };
    }

    function normalizedRoleLabel(label) {
        return String(label || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    }

    function tlUserMatchesMappedParentRole(tlUser, parentRoleId, parentRoleLabel) {
        const roleIds = Array.isArray(tlUser.role_ids) ? tlUser.role_ids.map(String) : [];

        if (parentRoleId && roleIds.includes(String(parentRoleId))) {
            return true;
        }

        const parentKey = normalizedRoleLabel(parentRoleLabel);

        return Boolean(tlUser.is_super_admin && ['super_admin', 'admin'].includes(parentKey));
    }

    function filteredTlUsersForSelection() {
        const selectedDepartmentId = departmentSelect ? departmentSelect.value : '';
        const selectedRole = selectedRoleMeta();
        const selectedBranchId = branchSelect ? branchSelect.value : '';
        const parentRoleId = selectedRole?.parentRoleId || '';
        const parentRoleLabel = selectedRole?.parentRoleLabel || '';

        return tlUsers.filter(function (tlUser) {
            if (currentPortalUserId && String(tlUser.id) === String(currentPortalUserId)) {
                return false;
            }

            const matchesBranch = !selectedBranchId || !tlUser.branch_id || String(tlUser.branch_id) === String(selectedBranchId) || tlUser.is_super_admin;

            if (parentRoleId) {
                return matchesBranch && tlUserMatchesMappedParentRole(tlUser, parentRoleId, parentRoleLabel);
            }

            const departments = Array.isArray(tlUser.department_ids) ? tlUser.department_ids.map(String) : [];
            const matchesDepartment = departments.includes(String(selectedDepartmentId)) || departments.includes('') || tlUser.is_super_admin;

            return matchesDepartment && matchesBranch;
        });
    }

    function updateTlOptions() {
        if (!tlSelect) {
            return;
        }

        const selectedDepartmentId = departmentSelect ? departmentSelect.value : '';
        const selectedRoleId = roleSelect ? roleSelect.value : '';
        const selectedRole = selectedRoleMeta();
        const parentRoleId = selectedRole?.parentRoleId || '';
        const parentRoleLabel = selectedRole?.parentRoleLabel || 'mapped parent role';
        const previousValue = tlSelect.value || tlSelect.getAttribute('data-selected-tl') || '';

        tlSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';

        if (!selectedDepartmentId || !selectedRoleId) {
            placeholder.textContent = 'Select department and role first';
            tlSelect.appendChild(placeholder);
            tlSelect.value = '';
            if (tlHelp) {
                tlHelp.textContent = 'TLs will appear after department and role selection.';
            }
            return;
        }

        const filteredTlUsers = filteredTlUsersForSelection();

        placeholder.textContent = filteredTlUsers.length
            ? (parentRoleId ? 'Select ' + parentRoleLabel : 'Select TL')
            : (parentRoleId ? 'No user found with ' + parentRoleLabel + ' role' : 'No TL found for this department');
        tlSelect.appendChild(placeholder);

        filteredTlUsers.forEach(function (tlUser) {
            const option = document.createElement('option');
            option.value = tlUser.id;
            option.textContent = tlUser.name + ' - ' + (tlUser.role_label || 'Team Lead');

            if (tlUser.email) {
                option.textContent += ' (' + tlUser.email + ')';
            }

            if (String(tlUser.id) === String(previousValue)) {
                option.selected = true;
            }

            tlSelect.appendChild(option);
        });

        if (!filteredTlUsers.some(function (tlUser) {
            return String(tlUser.id) === String(previousValue);
        })) {
            tlSelect.value = '';
        }

        if (tlHelp) {
            tlHelp.textContent = filteredTlUsers.length
                ? (
                    parentRoleId
                        ? filteredTlUsers.length + ' user' + (filteredTlUsers.length === 1 ? '' : 's') + ' available from mapped parent role: ' + parentRoleLabel + '.'
                        : filteredTlUsers.length + ' TL' + (filteredTlUsers.length === 1 ? '' : 's') + ' available for the selected department.'
                )
                : (
                    parentRoleId
                        ? 'No active users found for mapped parent role: ' + parentRoleLabel + '.'
                        : 'No active TLs found. Create or assign a TL role for this department first.'
                );
        }

        updatePortalMappingSummary();
    }

    function updatePortalMappingSummary() {
        if (!portalSummary || !departmentSelect || !roleSelect || !tlSelect) {
            return;
        }

        const departmentLabel = departmentSelect.options[departmentSelect.selectedIndex]?.textContent.trim() || 'No department selected';
        const roleLabel = roleSelect.options[roleSelect.selectedIndex]?.textContent.trim() || 'No role selected';
        const selectedRole = selectedRoleMeta();
        const parentRoleLabel = selectedRole?.parentRoleLabel || '';
        const selectedTlOption = tlSelect.options[tlSelect.selectedIndex];
        const selectedTlLabel = selectedTlOption && selectedTlOption.value ? selectedTlOption.textContent.trim() : null;

        const availableRoleLabels = allRoleOptions
            .filter(function (option) {
                if (!option.value) {
                    return false;
                }

                if (!departmentSelect.value) {
                    return true;
                }

                return option.departmentId === departmentSelect.value || option.departmentId === '';
            })
            .map(function (option) { return option.label; });

        const filteredTlUsers = filteredTlUsersForSelection();

        let summary = 'Department: ' + departmentLabel + '. Role: ' + roleLabel + '. ';

        if (departmentSelect.value) {
            summary += 'Available roles for this department: ' + (availableRoleLabels.length ? availableRoleLabels.join(', ') : 'None') + '. ';
        }

        if (selectedTlLabel) {
            summary += 'Selected TL mapping: ' + selectedTlLabel + '.';
        } else {
            summary += parentRoleLabel
                ? 'Mapped parent role: ' + parentRoleLabel + '. Available users: ' + filteredTlUsers.length + '.'
                : 'Available TLs for selected department: ' + filteredTlUsers.length + ' ' + (filteredTlUsers.length === 1 ? 'TL' : 'TLs') + '.';
        }

        portalSummary.textContent = summary;
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', function () {
            syncDepartmentFromRole(true);
            filterRolesByDepartment();
            updateTlOptions();
            updatePortalMappingSummary();
        });
    }

    if (departmentSelect) {
        departmentSelect.addEventListener('change', function () {
            filterRolesByDepartment();
            updateTlOptions();
            updatePortalMappingSummary();
        });
    }

    if (branchSelect) {
        branchSelect.addEventListener('change', function () {
            updateTlOptions();
            updatePortalMappingSummary();
        });
    }

    if (departmentSelect && !departmentSelect.value && roleSelect && roleSelect.value) {
        syncDepartmentFromRole(true);
    }

    filterRolesByDepartment();
    updateTlOptions();

    const salaryInputs = {
        grossSalary: form.querySelector('[data-salary-input="gross_salary"]'),
        basicSalary: form.querySelector('[data-salary-input="basic_salary"]'),
        hra: form.querySelector('[data-salary-input="hra"]'),
        specialAllowance: form.querySelector('[data-salary-input="special_allowance"]'),
        otherAllowance: form.querySelector('[data-salary-input="other_allowance"]'),
        professionalTax: form.querySelector('[data-salary-input="professional_tax"]'),
        tdsAmount: form.querySelector('[data-salary-input="tds_amount"]'),
        loanDeduction: form.querySelector('[data-salary-input="loan_deduction"]'),
        otherDeduction: form.querySelector('[data-salary-input="other_deduction"]'),
        pfToggle: form.querySelector('[data-salary-toggle="pf"]'),
        esiToggle: form.querySelector('[data-salary-toggle="esi"]'),
        pfEmployeeContribution: form.querySelector('[data-salary-output="pf_employee_contribution"]'),
        pfEmployerContribution: form.querySelector('[data-salary-output="pf_employer_contribution"]'),
        esiEmployeeContribution: form.querySelector('[data-salary-output="esi_employee_contribution"]'),
        esiEmployerContribution: form.querySelector('[data-salary-output="esi_employer_contribution"]'),
        totalDeduction: form.querySelector('[data-salary-output="total_deduction"]'),
        netSalary: form.querySelector('[data-salary-output="net_salary"]')
    };

    function parseAmount(input) {
        const value = input ? parseFloat(input.value) : 0;
        return Number.isFinite(value) ? value : 0;
    }

    function formatAmount(value) {
        return (Math.round(value * 100) / 100).toFixed(2);
    }

    function setSalaryFieldValue(input, value) {
        if (input) {
            input.value = formatAmount(value);
        }
    }

    function syncSalaryBreakdownFromGross() {
        const grossSalary = parseAmount(salaryInputs.grossSalary);

        if (grossSalary <= 0) {
            setSalaryFieldValue(salaryInputs.basicSalary, 0);
            setSalaryFieldValue(salaryInputs.hra, 0);
            setSalaryFieldValue(salaryInputs.specialAllowance, 0);
            setSalaryFieldValue(salaryInputs.otherAllowance, 0);
            return {
                grossSalary: 0,
                basicSalary: 0,
                hra: 0,
                specialAllowance: 0,
                otherAllowance: 0,
            };
        }

        const basicSalary = grossSalary * 0.50;
        const hra = grossSalary * 0.30;
        const specialAllowance = grossSalary * 0.10;
        const otherAllowance = Math.max(grossSalary - basicSalary - hra - specialAllowance, 0);

        setSalaryFieldValue(salaryInputs.basicSalary, basicSalary);
        setSalaryFieldValue(salaryInputs.hra, hra);
        setSalaryFieldValue(salaryInputs.specialAllowance, specialAllowance);
        setSalaryFieldValue(salaryInputs.otherAllowance, otherAllowance);

        return {
            grossSalary: grossSalary,
            basicSalary: basicSalary,
            hra: hra,
            specialAllowance: specialAllowance,
            otherAllowance: otherAllowance,
        };
    }

    function updateSalaryCalculations() {
        if (!salaryInputs.grossSalary || !salaryInputs.netSalary) {
            return;
        }

        const breakdown = syncSalaryBreakdownFromGross();
        const grossSalary = breakdown.grossSalary;
        const basicSalary = breakdown.basicSalary;
        const professionalTax = parseAmount(salaryInputs.professionalTax);
        const tdsAmount = parseAmount(salaryInputs.tdsAmount);
        const loanDeduction = parseAmount(salaryInputs.loanDeduction);
        const otherDeduction = parseAmount(salaryInputs.otherDeduction);
        const pfEnabled = salaryInputs.pfToggle ? salaryInputs.pfToggle.checked : false;
        const esiEnabled = salaryInputs.esiToggle ? salaryInputs.esiToggle.checked : false;

        const pfBaseAmount = grossSalary > 21000
            ? 15000
            : (breakdown.basicSalary + breakdown.specialAllowance + breakdown.otherAllowance);
        const pfEmployee = pfEnabled ? Math.round(pfBaseAmount * 0.25) : 0;
        const pfEmployer = pfEnabled ? Math.round(pfBaseAmount * 0.25) : 0;
        const esiEmployee = esiEnabled ? Math.round(grossSalary * 0.04) : 0;
        const esiEmployer = esiEnabled ? Math.round(grossSalary * 0.04) : 0;
        const totalDeduction = pfEmployee + esiEmployee + professionalTax + tdsAmount + loanDeduction + otherDeduction;
        const netSalary = Math.max(grossSalary - totalDeduction, 0);

        if (salaryInputs.pfEmployeeContribution) {
            salaryInputs.pfEmployeeContribution.value = formatAmount(pfEmployee);
        }
        if (salaryInputs.pfEmployerContribution) {
            salaryInputs.pfEmployerContribution.value = formatAmount(pfEmployer);
        }
        if (salaryInputs.esiEmployeeContribution) {
            salaryInputs.esiEmployeeContribution.value = formatAmount(esiEmployee);
        }
        if (salaryInputs.esiEmployerContribution) {
            salaryInputs.esiEmployerContribution.value = formatAmount(esiEmployer);
        }
        if (salaryInputs.totalDeduction) {
            salaryInputs.totalDeduction.value = formatAmount(totalDeduction);
        }
        if (salaryInputs.netSalary) {
            salaryInputs.netSalary.value = formatAmount(netSalary);
        }

        updateEsiEligibilityHelp(esiEnabled);
    }

    function updateEsiEligibilityHelp(esiEnabled) {
        const esiHelp = document.getElementById('esiEligibilityHelp');
        if (!esiHelp) {
            return;
        }

        if (!esiEnabled) {
            esiHelp.textContent = 'ESI amount is calculated as 4% of gross salary whenever ESI is enabled.';
            esiHelp.style.color = '';
            return;
        }

        esiHelp.textContent = 'ESI calculation is active at 4% of gross salary.';
        esiHelp.style.color = '#166534';
    }

    [
        salaryInputs.grossSalary,
        salaryInputs.professionalTax,
        salaryInputs.tdsAmount,
        salaryInputs.loanDeduction,
        salaryInputs.otherDeduction,
        salaryInputs.pfToggle,
        salaryInputs.esiToggle
    ].forEach(function (input) {
        input?.addEventListener('input', updateSalaryCalculations);
        input?.addEventListener('change', updateSalaryCalculations);
    });

    updateSalaryCalculations();

    const repeaters = {
        education: { tbody: document.getElementById('educationRows'), template: document.getElementById('educationRowTemplate') },
        employment: { tbody: document.getElementById('employmentRows'), template: document.getElementById('employmentRowTemplate') },
        family: { tbody: document.getElementById('familyRows'), template: document.getElementById('familyRowTemplate') }
    };

    document.querySelectorAll('[data-add-row]').forEach(function (button) {
        button.addEventListener('click', function () {
            const type = button.getAttribute('data-add-row');
            const config = repeaters[type];
            if (!config) {
                return;
            }

            const index = config.tbody.querySelectorAll('tr').length;
            const html = config.template.innerHTML.split('__INDEX__').join(index);
            config.tbody.insertAdjacentHTML('beforeend', html);
        });
    });

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-row]');
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest('tr');
        const tbody = row ? row.parentElement : null;

        if (!row || !tbody || tbody.querySelectorAll('tr').length === 1) {
            row?.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
            return;
        }

        row.remove();
    });

    let activeIndex = Math.max(0, panels.findIndex(function (panel) {
        return panel.getAttribute('data-step') === form.getAttribute('data-initial-step');
    }));

    function humanizeFieldName(name) {
        return (name || 'This field')
            .replace(/\.\d+\./g, ' ')
            .replace(/\./g, ' ')
            .replace(/_/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
    }

    function resolveFieldLabel(input) {
        const directLabel = input.closest('.eob-group, td')?.querySelector('.eob-label');
        if (directLabel) {
            return directLabel.textContent.replace('*', '').trim();
        }

        const td = input.closest('td');
        const row = input.closest('tr');
        const table = input.closest('table');
        if (td && row && table) {
            const cellIndex = Array.from(row.children).indexOf(td);
            const header = table.querySelectorAll('thead th')[cellIndex];
            if (header) {
                return header.textContent.trim();
            }
        }

        return humanizeFieldName(input.name);
    }

    function clearValidationSummary() {
        if (!validationSummary) {
            return;
        }

        validationSummary.classList.remove('is-visible');
        validationSummary.innerHTML = '';
    }

    function showValidationSummary(invalidFields) {
        if (!validationSummary || !invalidFields.length) {
            return;
        }

        const items = invalidFields.map(function (field) {
            return '<li>' + field + '</li>';
        }).join('');

        validationSummary.innerHTML = '<strong>Please review the highlighted fields before continuing.</strong><ul>' + items + '</ul>';
        validationSummary.classList.add('is-visible');
    }

    function clearStepValidationState() {
        if (!panels[activeIndex]) {
            return;
        }

        panels[activeIndex].querySelectorAll('.is-invalid').forEach(function (node) {
            node.classList.remove('is-invalid');
        });

        clearValidationSummary();
    }

    function fieldNamesForErrorKey(errorKey) {
        const bracketName = errorKey.replace(/\.(\d+)\./g, '[$1][').replace(/\.(\w+)$/g, '[$1]');

        if (bracketName.includes('[')) {
            return [bracketName];
        }

        return [errorKey];
    }

    function stepIndexForFieldName(fieldName) {
        const directInput = form.querySelector('[name="' + fieldName + '"]');
        if (!directInput) {
            return -1;
        }

        const panel = directInput.closest('.eob-wizard-panel');
        return panel ? panels.indexOf(panel) : -1;
    }

    function serverErrorStepIndices() {
        return Array.from(new Set(Object.keys(serverErrors).map(function (errorKey) {
            const candidateNames = fieldNamesForErrorKey(errorKey);
            for (const fieldName of candidateNames) {
                const index = stepIndexForFieldName(fieldName);
                if (index !== -1) {
                    return index;
                }
            }

            return -1;
        }).filter(function (index) {
            return index !== -1;
        })));
    }

    function applyServerErrorHighlights() {
        const highlightedLabels = [];

        Object.keys(serverErrors).forEach(function (errorKey) {
            fieldNamesForErrorKey(errorKey).forEach(function (fieldName) {
                const input = form.querySelector('[name="' + fieldName + '"]');
                if (!input) {
                    return;
                }

                input.classList.add('is-invalid');

                const panel = input.closest('.eob-wizard-panel');
                if (panel && panels.indexOf(panel) === activeIndex) {
                    highlightedLabels.push(resolveFieldLabel(input));
                }
            });
        });

        const uniqueLabels = Array.from(new Set(highlightedLabels));
        showValidationSummary(uniqueLabels);
    }

    function updateWizard() {
        clearValidationSummary();

        panels.forEach(function (panel, index) {
            panel.classList.toggle('is-active', index === activeIndex);
        });

        const errorSteps = serverErrorStepIndices();
        stepButtons.forEach(function (button, index) {
            button.classList.toggle('is-active', index === activeIndex);
            button.classList.toggle('is-complete', index < activeIndex);
            button.classList.toggle('has-errors', errorSteps.includes(index));
        });

        const activePanel = panels[activeIndex];
        const percent = ((activeIndex + 1) / panels.length) * 100;

        stepCounter.textContent = 'Step ' + (activeIndex + 1) + ' of ' + panels.length;
        progressBar.style.width = percent + '%';
        activeTitle.textContent = activePanel.getAttribute('data-step-title');
        activeSub.textContent = activePanel.getAttribute('data-step-sub');

        prevButton.style.display = activeIndex === 0 ? 'none' : 'inline-flex';
        nextButton.style.display = activeIndex === panels.length - 1 ? 'none' : 'inline-flex';
        submitButton.style.display = activeIndex === panels.length - 1 ? 'inline-flex' : 'none';

        if (Object.keys(serverErrors).length) {
            applyServerErrorHighlights();
        }
    }

    function validateCurrentStep() {
        clearStepValidationState();

        const inputs = Array.from(panels[activeIndex].querySelectorAll('input, select, textarea')).filter(function (input) {
            return !input.disabled && typeof input.checkValidity === 'function';
        });
        const invalidInputs = [];
        const invalidLabels = [];

        for (const input of inputs) {
            if (!input.checkValidity()) {
                invalidInputs.push(input);
                invalidLabels.push(resolveFieldLabel(input));
                input.classList.add('is-invalid');
            }
        }

        if (!invalidInputs.length) {
            return true;
        }

        showValidationSummary(Array.from(new Set(invalidLabels)));
        invalidInputs[0].focus();
        invalidInputs[0].reportValidity();
        return false;
    }

    addressCopyButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const source = form.querySelector('[name="' + button.getAttribute('data-copy-source') + '"]');
            const target = form.querySelector('[name="' + button.getAttribute('data-copy-target') + '"]');

            if (!source || !target) {
                return;
            }

            target.value = source.value;
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    stepButtons.forEach(function (button, index) {
        button.addEventListener('click', function () {
            if (index <= activeIndex) {
                activeIndex = index;
                updateWizard();
                return;
            }

            if (validateCurrentStep()) {
                activeIndex = index;
                updateWizard();
            }
        });
    });

    prevButton.addEventListener('click', function () {
        if (activeIndex > 0) {
            activeIndex -= 1;
            updateWizard();
        }
    });

    nextButton.addEventListener('click', function () {
        if (validateCurrentStep() && activeIndex < panels.length - 1) {
            activeIndex += 1;
            updateWizard();
        }
    });

    form.querySelectorAll('input, select, textarea').forEach(function (input) {
        input.addEventListener('input', function () {
            input.classList.remove('is-invalid');
        });

        input.addEventListener('change', function () {
            input.classList.remove('is-invalid');
        });
    });

    updateWizard();
});
</script>
@endpush
