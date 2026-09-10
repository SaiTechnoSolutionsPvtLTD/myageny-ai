@php
    $form = $form ?? null;
    $portalUser = $form?->portalUser;
    $selectedTlUserId = old('tl_user_id', $portalUser?->managerMappings?->first()?->manager_id);
    $portalBranchId = old('branch_id', $portalUser?->branch_id ?? auth()->user()?->branch_id);
    $portalDepartmentId = old('department_id', $form?->department_id);
    $portalRoleId = old('role_id', $form?->role_id);
    $portalEmail = old('portal_email', $portalUser?->email ?? $form?->email ?? '');
    $portalAccountEnabled = old('create_portal_account', $portalUser ? '1' : '0') === '1';

    $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $educationDefaults = [
        ['qualification' => 'PG', 'institution_name' => '', 'year_of_passing' => '', 'percentage' => '', 'specialization' => ''],
        ['qualification' => 'UG', 'institution_name' => '', 'year_of_passing' => '', 'percentage' => '', 'specialization' => ''],
        ['qualification' => 'HSC / 12th', 'institution_name' => '', 'year_of_passing' => '', 'percentage' => '', 'specialization' => ''],
        ['qualification' => 'SSLC / 10th', 'institution_name' => '', 'year_of_passing' => '', 'percentage' => '', 'specialization' => ''],
    ];
    $employmentDefaults = [
        ['organisation' => '', 'designation' => '', 'period_from' => '', 'period_to' => '', 'annual_ctc' => ''],
    ];
    $familyDefaults = [
        ['name' => '', 'relation' => '', 'occupation' => '', 'date_of_birth' => '', 'mobile_no' => ''],
    ];

    $educationRows = old('educational_details');
    if (! is_array($educationRows)) {
        $educationRows = $form
            ? $form->educationalDetails->map(fn ($row) => [
                'qualification' => $row->qualification,
                'institution_name' => $row->institution_name,
                'year_of_passing' => $row->year_of_passing,
                'percentage' => $row->percentage,
                'specialization' => $row->specialization,
            ])->toArray()
            : $educationDefaults;
    }
    if ($educationRows === []) {
        $educationRows = $educationDefaults;
    }

    $employmentRows = old('employment_details');
    if (! is_array($employmentRows)) {
        $employmentRows = $form
            ? $form->employmentDetails->map(fn ($row) => [
                'organisation' => $row->organisation,
                'designation' => $row->designation,
                'period_from' => optional($row->period_from)->format('Y-m-d'),
                'period_to' => optional($row->period_to)->format('Y-m-d'),
                'annual_ctc' => $row->annual_ctc,
            ])->toArray()
            : $employmentDefaults;
    }
    if ($employmentRows === []) {
        $employmentRows = $employmentDefaults;
    }

    $familyRows = old('family_details');
    if (! is_array($familyRows)) {
        $familyRows = $form
            ? $form->familyDetails->map(fn ($row) => [
                'name' => $row->name,
                'relation' => $row->relation,
                'occupation' => $row->occupation,
                'date_of_birth' => optional($row->date_of_birth)->format('Y-m-d'),
                'mobile_no' => $row->mobile_no,
            ])->toArray()
            : $familyDefaults;
    }
    if ($familyRows === []) {
        $familyRows = $familyDefaults;
    }

    $steps = [
        ['key' => 'personal', 'label' => 'Personal Details'],
        ['key' => 'portal', 'label' => 'Intern Portal Account'],
        ['key' => 'education', 'label' => 'Educational Details'],
        ['key' => 'employment', 'label' => 'Employment Details'],
        ['key' => 'family', 'label' => 'Family Details'],
        ['key' => 'documents', 'label' => 'Document Uploads'],
        ['key' => 'review', 'label' => 'Review & Submit'],
    ];
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="internWizardForm">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="intern-card wizard-layout">
        <aside class="wizard-sidebar">
            <div class="small text-uppercase fw-bold text-secondary mb-2">Interns</div>
            <div class="fs-5 fw-bold text-dark mb-2">Complete one section at a time</div>
            <div class="text-secondary small mb-4">Fill one section at a time and review everything before final submission.</div>
            <div class="wizard-sidebar-note">
                <strong>Quick tip</strong>
                <span>Keep documents ready before moving to the final review step.</span>
            </div>
            @foreach($steps as $index => $step)
                <button type="button" class="wizard-step-btn" data-step-button="{{ $step['key'] }}" data-step-index="{{ $index }}">
                    <span class="wizard-step-no">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="wizard-step-copy">
                        <strong>{{ $step['label'] }}</strong>
                        <small>Step {{ $index + 1 }}</small>
                    </span>
                </button>
            @endforeach
        </aside>

        <div class="wizard-main">
            <div class="wizard-validation-summary" id="wizardValidationSummary" role="alert" aria-live="polite"></div>
            <div class="wizard-header-card">
                <div>
                    <div class="small text-uppercase text-secondary fw-bold">Progress</div>
                    <div class="fw-bold fs-5" id="wizardStepTitle">Personal Details</div>
                </div>
                <div class="wizard-header-meta">
                    <span id="wizardStepCounter">Step 1 of {{ count($steps) }}</span>
                    <div class="wizard-progress"><span id="wizardProgressBar" style="width:14%;"></span></div>
                </div>
            </div>

            <section class="wizard-panel intern-card p-4" data-step-panel="personal">
                <div class="panel-intro">
                    <div>
                        <div class="panel-eyebrow">Step 1</div>
                        <h3 class="panel-title">Personal Details</h3>
                        <p class="panel-subtitle">Start with the intern's identity, contact details, and emergency information.</p>
                        <div class="intern-required-note"><span class="intern-label-required">*</span> indicates mandatory fields.</div>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="grid-half">
                        <div class="field-surface upload-surface">
                            <label class="intern-label">Photograph</label>
                            <p class="field-help">JPG or PNG works best for the profile preview.</p>
                            <input type="file" name="photograph" class="intern-input intern-file-input" accept=".jpg,.jpeg,.png">
                        </div>
                        @if($form?->photograph)
                            <img src="{{ asset('storage/' . $form->photograph) }}" alt="Photograph" class="intern-avatar-preview mt-2">
                        @endif
                        @error('photograph')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Name <span class="intern-label-required">*</span></label>
                        <input type="text" name="name" class="intern-input" value="{{ old('name', $form?->name) }}" required>
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Father's Name <span class="intern-label-required">*</span></label>
                        <input type="text" name="father_name" class="intern-input" value="{{ old('father_name', $form?->father_name) }}" required>
                        @error('father_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                      <div class="grid-half">
                        <label class="intern-label">Mobile <span class="intern-label-required">*</span></label>
                        <input type="tel" name="mobile" class="intern-input" value="{{ old('mobile', $form?->mobile) }}" required>
                        @error('mobile')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <label class="intern-label mb-0">Correspondence Address <span class="intern-label-required">*</span></label>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-copy-address data-copy-source="correspondence_address" data-copy-target="permanent_address">
                                Copy to Permanent
                            </button>
                        </div>
                        <textarea name="correspondence_address" class="intern-input intern-textarea" rows="3" required>{{ old('correspondence_address', $form?->correspondence_address) }}</textarea>
                        @error('correspondence_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Permanent Address <span class="intern-label-required">*</span></label>
                        <textarea name="permanent_address" class="intern-input intern-textarea" rows="3" required>{{ old('permanent_address', $form?->permanent_address) }}</textarea>
                        @error('permanent_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="grid-half">
                        <label class="intern-label">Email ID <span class="intern-label-required">*</span></label>
                        <input type="email" name="email" class="intern-input" value="{{ old('email', $form?->email) }}" required>
                        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Date of Birth <span class="intern-label-required">*</span></label>
                        <input type="date" name="date_of_birth" class="intern-input" value="{{ old('date_of_birth', optional($form?->date_of_birth)->format('Y-m-d')) }}" required>
                        @error('date_of_birth')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Internship Start Date <span class="intern-label-required">*</span></label>
                        <input type="date" name="internship_start_date" id="internshipStartDate" class="intern-input" value="{{ old('internship_start_date', optional($form?->internship_start_date)->format('Y-m-d')) }}" required>
                        @error('internship_start_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Duration in Months <span class="intern-label-required">*</span></label>
                        <input type="number" name="internship_duration_months" id="internshipDurationMonths" class="intern-input" min="1" max="60" value="{{ old('internship_duration_months', $form?->internship_duration_months) }}" required>
                        <div class="text-secondary small mt-1">End date will be calculated automatically.</div>
                        @error('internship_duration_months')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Internship End Date</label>
                        <input type="date" name="internship_end_date" id="internshipEndDate" class="intern-input" value="{{ old('internship_end_date', optional($form?->internship_end_date)->format('Y-m-d')) }}" readonly>
                        @error('internship_end_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Internship Status <span class="intern-label-required">*</span></label>
                        <select name="internship_status" class="intern-select" required>
                            <option value="active" @selected(old('internship_status', $form?->internship_status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('internship_status', $form?->internship_status) === 'inactive')>Inactive</option>
                            <option value="resigned" @selected(old('internship_status', $form?->internship_status) === 'resigned')>Resigned</option>
                        </select>
                        @error('internship_status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Blood Group</label>
                        <select name="blood_group" class="intern-select">
                            <option value="">Select</option>
                            @foreach($bloodGroups as $group)
                                <option value="{{ $group }}" @selected(old('blood_group', $form?->blood_group) === $group)>{{ $group }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Marital Status <span class="intern-label-required">*</span></label>
                        <div class="intern-choice intern-choice-inline">
                            <input class="intern-choice-input" type="radio" name="marital_status" value="single" @checked(old('marital_status', $form?->marital_status ?? 'single') === 'single')>
                            <label class="intern-choice-label">Single</label>
                        </div>
                        <div class="intern-choice intern-choice-inline">
                            <input class="intern-choice-input" type="radio" name="marital_status" value="married" @checked(old('marital_status', $form?->marital_status) === 'married')>
                            <label class="intern-choice-label">Married</label>
                        </div>
                        @error('marital_status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half" id="marriageDateField" style="{{ old('marital_status', $form?->marital_status ?? 'single') === 'married' ? '' : 'display:none;' }}">
                        <label class="intern-label">Date of Marriage</label>
                        <input type="date" name="date_of_marriage" class="intern-input" value="{{ old('date_of_marriage', optional($form?->date_of_marriage)->format('Y-m-d')) }}">
                        @error('date_of_marriage')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Aadhaar Card No <span class="intern-label-required">*</span></label>
                        <input type="text" name="aadhaar_card_no" class="intern-input" value="{{ old('aadhaar_card_no', $form?->aadhaar_card_no) }}" required>
                        @error('aadhaar_card_no')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Pan Card No <span class="intern-label-required">*</span></label>
                        <input type="text" name="pan_card_no" class="intern-input" value="{{ old('pan_card_no', $form?->pan_card_no) }}" required>
                        @error('pan_card_no')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Emergency Contact Name <span class="intern-label-required">*</span></label>
                        <input type="text" name="emergency_contact_name" class="intern-input" value="{{ old('emergency_contact_name', $form?->emergency_contact_name) }}" required>
                        @error('emergency_contact_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Emergency Contact Relation <span class="intern-label-required">*</span></label>
                        <input type="text" name="emergency_contact_relation" class="intern-input" value="{{ old('emergency_contact_relation', $form?->emergency_contact_relation) }}" required>
                        @error('emergency_contact_relation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="grid-half">
                        <label class="intern-label">Emergency Contact No <span class="intern-label-required">*</span></label>
                        <input type="tel" name="emergency_contact_no" class="intern-input" value="{{ old('emergency_contact_no', $form?->emergency_contact_no) }}" required>
                        @error('emergency_contact_no')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="wizard-panel intern-card p-4" data-step-panel="portal">
                <div class="panel-intro">
                    <div>
                        <div class="panel-eyebrow">Step 2</div>
                        <h3 class="panel-title">Intern Portal Account</h3>
                        <p class="panel-subtitle">Create login access for interns who need to use the portal.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="grid-full">
                        <div class="intern-check-card border rounded-4 p-3 bg-light">
                            <input
                                class="intern-choice-input mt-1"
                                type="checkbox"
                                value="1"
                                name="create_portal_account"
                                id="createPortalAccount"
                                @checked($portalAccountEnabled)
                            >
                            <label class="intern-choice-label fw-semibold" for="createPortalAccount">
                                Create intern portal login account
                            </label>
                        </div>
                        <div class="text-secondary small mt-2">
                            Enable this only if the intern needs portal access. Existing linked accounts will stay connected unless you update them here.
                        </div>
                    </div>

                    <div class="grid-full" id="internPortalFields">
                        <div class="border rounded-4 p-3 bg-white">
                            <div class="form-grid">
                                <div class="grid-half">
                                    <label class="intern-label">Portal Email <span class="intern-label-required">*</span></label>
                                    <input type="email" name="portal_email" id="portalEmailInput" class="intern-input" value="{{ $portalEmail }}" data-portal-field>
                                    <div class="text-secondary small mt-1">Defaulted from personal email. You can change it if needed.</div>
                                    @error('portal_email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="grid-half">
                                    <label class="intern-label">Portal Password {{ $portalUser ? '' : '*' }}</label>
                                    <input type="password" name="portal_password" id="portalPasswordInput" class="intern-input" data-portal-field>
                                    <div class="text-secondary small mt-1">{{ $portalUser ? 'Leave blank to keep current password.' : 'Minimum 8 characters.' }}</div>
                                    @error('portal_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="grid-half">
                                    <label class="intern-label">Branch <span class="intern-label-required">*</span></label>
                                    <select name="branch_id" id="portalBranchSelect" class="intern-select" data-portal-field>
                                        <option value="">Select branch</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) $portalBranchId === (string) $branch->id)>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="grid-half">
                                    <label class="intern-label">Department <span class="intern-label-required">*</span></label>
                                    <select name="department_id" id="portalDepartmentSelect" class="intern-select" data-portal-field>
                                        <option value="">Select department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" @selected((string) $portalDepartmentId === (string) $department->id)>{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="grid-half">
                                    <label class="intern-label">Role <span class="intern-label-required">*</span></label>
                                    <select name="role_id" id="portalRoleSelect" class="intern-select" data-portal-field>
                                        <option value="">Select role</option>
                                        @foreach($roles as $role)
                                            <option
                                                value="{{ $role->id }}"
                                                data-department-id="{{ $role->department_id }}"
                                                data-parent-role-id="{{ $role->roleParentMapping?->parent_role_id ?? '' }}"
                                                data-parent-role-name="{{ $role->roleParentMapping?->parentRole?->display_name ?: $role->roleParentMapping?->parentRole?->name ?? '' }}"
                                                @selected((string) $portalRoleId === (string) $role->id)
                                            >
                                                {{ $role->display_name ?: $role->name }}{{ $role->department ? ' - ' . $role->department->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="grid-half">
                                    <label class="intern-label">Team Lead <span class="intern-label-required">*</span></label>
                                    <select
                                        name="tl_user_id"
                                        id="portalTlUserSelect"
                                        class="intern-select"
                                        data-portal-field
                                        data-selected-tl="{{ $selectedTlUserId }}"
                                    >
                                        <option value="">Select TL</option>
                                    </select>
                                    <div class="text-secondary small mt-1" id="portalTlHelp">TL options will update based on branch, department, and role.</div>
                                    @error('tl_user_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="grid-full">
                                    <div class="border rounded-4 p-3 bg-light text-secondary small" id="portalSummary">
                                        Choose branch, department, and role to review the login mapping summary.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="wizard-panel intern-card p-4" data-step-panel="education">
                <div class="panel-intro">
                    <div>
                        <div class="panel-eyebrow">Step 3</div>
                        <h3 class="panel-title">Educational Details</h3>
                        <p class="panel-subtitle">Capture academic history in a simple, scan-friendly format.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Qualification</th>
                                <th>Institution Name</th>
                                <th>Year of Passing</th>
                                <th>Percentage</th>
                                <th>Specialization</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($educationRows as $index => $row)
                                <tr>
                                    <td><input type="text" name="educational_details[{{ $index }}][qualification]" class="intern-input" value="{{ $row['qualification'] ?? '' }}" readonly></td>
                                    <td><input type="text" name="educational_details[{{ $index }}][institution_name]" class="intern-input" value="{{ $row['institution_name'] ?? '' }}"></td>
                                    <td><input type="text" name="educational_details[{{ $index }}][year_of_passing]" class="intern-input" value="{{ $row['year_of_passing'] ?? '' }}"></td>
                                    <td><input type="text" name="educational_details[{{ $index }}][percentage]" class="intern-input" value="{{ $row['percentage'] ?? '' }}"></td>
                                    <td><input type="text" name="educational_details[{{ $index }}][specialization]" class="intern-input" value="{{ $row['specialization'] ?? '' }}"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="wizard-panel intern-card p-4" data-step-panel="employment">
                <div class="panel-intro">
                    <div>
                        <div class="panel-eyebrow">Step 4</div>
                        <h3 class="panel-title">Employment Details</h3>
                        <p class="panel-subtitle">Add previous experience only where applicable.</p>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-secondary small">Add up to 3 previous organisations.</div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addEmploymentRow">Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
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
                            @foreach($employmentRows as $index => $row)
                                <tr>
                                    <td><input type="text" name="employment_details[{{ $index }}][organisation]" class="intern-input" value="{{ $row['organisation'] ?? '' }}"></td>
                                    <td><input type="text" name="employment_details[{{ $index }}][designation]" class="intern-input" value="{{ $row['designation'] ?? '' }}"></td>
                                    <td><input type="date" name="employment_details[{{ $index }}][period_from]" class="intern-input" value="{{ $row['period_from'] ?? '' }}"></td>
                                    <td><input type="date" name="employment_details[{{ $index }}][period_to]" class="intern-input" value="{{ $row['period_to'] ?? '' }}"></td>
                                    <td><input type="text" name="employment_details[{{ $index }}][annual_ctc]" class="intern-input" value="{{ $row['annual_ctc'] ?? '' }}"></td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Remove</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="wizard-panel intern-card p-4" data-step-panel="family">
                <div class="panel-intro">
                    <div>
                        <div class="panel-eyebrow">Step 5</div>
                        <h3 class="panel-title">Family Details</h3>
                        <p class="panel-subtitle">List immediate family contacts that may be useful for records.</p>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-secondary small">Add up to 5 family members.</div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addFamilyRow">Add Row</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
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
                            @foreach($familyRows as $index => $row)
                                <tr>
                                    <td><input type="text" name="family_details[{{ $index }}][name]" class="intern-input" value="{{ $row['name'] ?? '' }}"></td>
                                    <td><input type="text" name="family_details[{{ $index }}][relation]" class="intern-input" value="{{ $row['relation'] ?? '' }}"></td>
                                    <td><input type="text" name="family_details[{{ $index }}][occupation]" class="intern-input" value="{{ $row['occupation'] ?? '' }}"></td>
                                    <td><input type="date" name="family_details[{{ $index }}][date_of_birth]" class="intern-input" value="{{ $row['date_of_birth'] ?? '' }}"></td>
                                    <td><input type="text" name="family_details[{{ $index }}][mobile_no]" class="intern-input" value="{{ $row['mobile_no'] ?? '' }}"></td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Remove</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="wizard-panel intern-card p-4" data-step-panel="documents">
                <div class="panel-intro">
                    <div>
                        <div class="panel-eyebrow">Step 6</div>
                        <h3 class="panel-title">Document Uploads</h3>
                        <p class="panel-subtitle">Upload the required proofs and certificates for onboarding.</p>
                    </div>
                </div>
                <div class="intern-doc-grid">
                    @foreach($documentLabels as $field => $label)
                        <div class="intern-doc-card">
                            <label class="intern-label fw-semibold">{{ $label }}</label>
                            <input type="file" name="{{ $field }}" class="intern-input intern-file-input" accept=".pdf,.jpg,.jpeg,.png">
                            @if($form?->documents?->{$field})
                                <a href="{{ asset('storage/' . $form->documents->{$field}) }}" target="_blank" class="btn btn-link px-0 mt-2">View Existing</a>
                            @endif
                            @error($field)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="wizard-panel intern-card p-4" data-step-panel="review">
                <div class="panel-intro">
                    <div>
                        <div class="panel-eyebrow">Step 7</div>
                        <h3 class="panel-title">Review & Submit</h3>
                        <p class="panel-subtitle">Take one final pass before creating the intern joining record.</p>
                    </div>
                </div>
                <div class="review-grid">
                    <div class="grid-half">
                        <div class="border rounded-4 p-4 h-100">
                            <h5 class="fw-bold mb-3">Review Summary</h5>
                            <dl class="review-list mb-0">
                                <dt>Name</dt><dd data-review="name">-</dd>
                                <dt>Email</dt><dd data-review="email">-</dd>
                                <dt>Portal Email</dt><dd data-review="portal_email">-</dd>
                                <dt>Mobile</dt><dd data-review="mobile">-</dd>
                                <dt>Internship Start</dt><dd data-review="internship_start_date">-</dd>
                                <dt>Internship End</dt><dd data-review="internship_end_date">-</dd>
                                <dt>Status</dt><dd data-review="internship_status">-</dd>
                                <dt>Marital Status</dt><dd data-review="marital_status">-</dd>
                                <dt>Emergency Contact</dt><dd data-review="emergency_contact_name">-</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="grid-half">
                        <div class="border rounded-4 p-4 h-100 bg-light">
                            <h5 class="fw-bold mb-3">Before You Submit</h5>
                            <ul class="mb-0 text-secondary">
                                <li>Use Previous to revisit any step.</li>
                                <li>Uploaded files will be stored in public storage.</li>
                                <li>Editing later allows document replacement.</li>
                                <li>Submit only after confirming all details are correct.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <div class="intern-card p-3 wizard-footer d-flex justify-content-between align-items-center">
                <a href="{{ $cancelRoute }}" class="btn btn-outline-secondary">Cancel</a>
                <div class="d-flex gap-2 wizard-footer-actions">
                    <button type="button" class="btn btn-outline-secondary" id="wizardPrevBtn">Previous</button>
                    <button type="button" class="btn btn-primary" id="wizardNextBtn">Next</button>
                    <button type="submit" class="btn btn-success d-none" id="wizardSubmitBtn">{{ $submitLabel }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="employmentRowTemplate">
    <tr>
        <td><input type="text" name="employment_details[__INDEX__][organisation]" class="intern-input"></td>
        <td><input type="text" name="employment_details[__INDEX__][designation]" class="intern-input"></td>
        <td><input type="date" name="employment_details[__INDEX__][period_from]" class="intern-input"></td>
        <td><input type="date" name="employment_details[__INDEX__][period_to]" class="intern-input"></td>
        <td><input type="text" name="employment_details[__INDEX__][annual_ctc]" class="intern-input"></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Remove</button></td>
    </tr>
</template>

<template id="familyRowTemplate">
    <tr>
        <td><input type="text" name="family_details[__INDEX__][name]" class="intern-input"></td>
        <td><input type="text" name="family_details[__INDEX__][relation]" class="intern-input"></td>
        <td><input type="text" name="family_details[__INDEX__][occupation]" class="intern-input"></td>
        <td><input type="date" name="family_details[__INDEX__][date_of_birth]" class="intern-input"></td>
        <td><input type="text" name="family_details[__INDEX__][mobile_no]" class="intern-input"></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Remove</button></td>
    </tr>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('internWizardForm');
    const addressCopyButtons = Array.from(document.querySelectorAll('[data-copy-address]'));
    const panels = Array.from(document.querySelectorAll('[data-step-panel]'));
    const stepButtons = Array.from(document.querySelectorAll('[data-step-button]'));
    const prevButton = document.getElementById('wizardPrevBtn');
    const nextButton = document.getElementById('wizardNextBtn');
    const submitButton = document.getElementById('wizardSubmitBtn');
    const stepTitle = document.getElementById('wizardStepTitle');
    const stepCounter = document.getElementById('wizardStepCounter');
    const progressBar = document.getElementById('wizardProgressBar');
    const marriageDateField = document.getElementById('marriageDateField');
    const employmentRows = document.getElementById('employmentRows');
    const familyRows = document.getElementById('familyRows');
    const addEmploymentRow = document.getElementById('addEmploymentRow');
    const addFamilyRow = document.getElementById('addFamilyRow');
    const validationSummary = document.getElementById('wizardValidationSummary');
    const internshipStartDate = document.getElementById('internshipStartDate');
    const internshipDurationMonths = document.getElementById('internshipDurationMonths');
    const internshipEndDate = document.getElementById('internshipEndDate');
    const createPortalAccount = document.getElementById('createPortalAccount');
    const portalFieldsWrap = document.getElementById('internPortalFields');
    const portalFieldInputs = Array.from(document.querySelectorAll('[data-portal-field]'));
    const personalEmailInput = form.querySelector('input[name="email"]');
    const portalEmailInput = document.getElementById('portalEmailInput');
    const portalPasswordInput = document.getElementById('portalPasswordInput');
    const branchSelect = document.getElementById('portalBranchSelect');
    const departmentSelect = document.getElementById('portalDepartmentSelect');
    const roleSelect = document.getElementById('portalRoleSelect');
    const tlSelect = document.getElementById('portalTlUserSelect');
    const tlHelp = document.getElementById('portalTlHelp');
    const portalSummary = document.getElementById('portalSummary');
    const tlUsers = @json($tlUsers ?? []);
    let activeIndex = 0;

    function updateReview() {
        document.querySelectorAll('[data-review]').forEach(function (node) {
            const field = node.getAttribute('data-review');
            const source = form.querySelector('[name="' + field + '"]');
            if (source) {
                if (source.type === 'radio') {
                    const checked = form.querySelector('[name="' + field + '"]:checked');
                    node.textContent = checked ? checked.value : '-';
                } else {
                    node.textContent = source.value || '-';
                }
            }
        });
    }

    function toggleMarriageDate() {
        const selected = form.querySelector('input[name="marital_status"]:checked');
        marriageDateField.style.display = selected && selected.value === 'married' ? '' : 'none';
    }

    function calculateInternshipEndDate() {
        if (!internshipStartDate || !internshipDurationMonths || !internshipEndDate) {
            return;
        }

        const startValue = internshipStartDate.value;
        const durationValue = parseInt(internshipDurationMonths.value || '', 10);

        if (!startValue || !Number.isFinite(durationValue) || durationValue < 1) {
            internshipEndDate.value = '';
            return;
        }

        const calculatedDate = new Date(startValue + 'T00:00:00');
        calculatedDate.setMonth(calculatedDate.getMonth() + durationValue);
        calculatedDate.setDate(calculatedDate.getDate() - 1);

        const year = calculatedDate.getFullYear();
        const month = String(calculatedDate.getMonth() + 1).padStart(2, '0');
        const day = String(calculatedDate.getDate()).padStart(2, '0');

        internshipEndDate.value = year + '-' + month + '-' + day;
    }

    form.querySelectorAll('input[name="marital_status"]').forEach(function (input) {
        input.addEventListener('change', toggleMarriageDate);
    });
    toggleMarriageDate();
    internshipStartDate?.addEventListener('change', calculateInternshipEndDate);
    internshipDurationMonths?.addEventListener('input', calculateInternshipEndDate);
    internshipDurationMonths?.addEventListener('change', calculateInternshipEndDate);
    calculateInternshipEndDate();

    const allRoleOptions = roleSelect ? Array.from(roleSelect.querySelectorAll('option')).map(function (option) {
        return {
            value: option.value,
            label: option.textContent,
            departmentId: option.getAttribute('data-department-id') || '',
            parentRoleId: option.getAttribute('data-parent-role-id') || '',
            parentRoleLabel: option.getAttribute('data-parent-role-name') || ''
        };
    }) : [];

    function portalAccountEnabled() {
        return !!createPortalAccount?.checked;
    }

    function togglePortalFields() {
        const enabled = portalAccountEnabled();

        if (portalFieldsWrap) {
            portalFieldsWrap.style.display = enabled ? '' : 'none';
        }

        portalFieldInputs.forEach(function (input) {
            input.disabled = !enabled;
            input.required = false;
        });

        if (!enabled) {
            return;
        }

        if (portalEmailInput) {
            portalEmailInput.required = true;
        }
        if (branchSelect) {
            branchSelect.required = true;
        }
        if (departmentSelect) {
            departmentSelect.required = true;
        }
        if (roleSelect) {
            roleSelect.required = true;
        }
        if (tlSelect) {
            tlSelect.required = true;
        }
        if (portalPasswordInput && !@json((bool) $portalUser)) {
            portalPasswordInput.required = true;
        }
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

    function syncDepartmentFromRole(force) {
        if (!roleSelect || !departmentSelect || !roleSelect.value) {
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
            if (!option.value || !selectedDepartmentId) {
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

        if (!filteredRoleOptions.some(function (option) { return option.value === selectedRoleId; })) {
            roleSelect.value = '';
        }
    }

    function selectedRoleMeta() {
        if (!roleSelect || !roleSelect.value) {
            return null;
        }

        const selectedOption = roleSelect.options[roleSelect.selectedIndex];

        return {
            parentRoleId: selectedOption?.getAttribute('data-parent-role-id') || '',
            parentRoleLabel: selectedOption?.getAttribute('data-parent-role-name') || ''
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
        const selectedBranchId = branchSelect ? branchSelect.value : '';
        const selectedRole = selectedRoleMeta();
        const parentRoleId = selectedRole?.parentRoleId || '';
        const parentRoleLabel = selectedRole?.parentRoleLabel || '';

        return tlUsers.filter(function (tlUser) {
            const matchesBranch = !selectedBranchId || !tlUser.branch_id || String(tlUser.branch_id) === String(selectedBranchId) || tlUser.is_super_admin;

            if (parentRoleId) {
                return matchesBranch && tlUserMatchesMappedParentRole(tlUser, parentRoleId, parentRoleLabel);
            }

            const departments = Array.isArray(tlUser.department_ids) ? tlUser.department_ids.map(String) : [];
            const matchesDepartment = departments.includes(String(selectedDepartmentId)) || departments.includes('') || tlUser.is_super_admin;

            return matchesDepartment && matchesBranch;
        });
    }

    function updatePortalMappingSummary() {
        if (!portalSummary || !departmentSelect || !roleSelect || !tlSelect) {
            return;
        }

        const departmentLabel = departmentSelect.options[departmentSelect.selectedIndex]?.textContent.trim() || 'No department selected';
        const roleLabel = roleSelect.options[roleSelect.selectedIndex]?.textContent.trim() || 'No role selected';
        const selectedTlOption = tlSelect.options[tlSelect.selectedIndex];
        const selectedTlLabel = selectedTlOption && selectedTlOption.value ? selectedTlOption.textContent.trim() : null;
        const filteredTlUsers = filteredTlUsersForSelection();

        portalSummary.textContent = selectedTlLabel
            ? 'Department: ' + departmentLabel + '. Role: ' + roleLabel + '. Selected TL: ' + selectedTlLabel + '.'
            : 'Department: ' + departmentLabel + '. Role: ' + roleLabel + '. Available TL count: ' + filteredTlUsers.length + '.';
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
            updatePortalMappingSummary();
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
            option.textContent = tlUser.name + ' - ' + (tlUser.role_label || 'Team Lead') + (tlUser.branch_name ? ' - ' + tlUser.branch_name : '');

            if (String(tlUser.id) === String(previousValue)) {
                option.selected = true;
            }

            tlSelect.appendChild(option);
        });

        if (!filteredTlUsers.some(function (tlUser) { return String(tlUser.id) === String(previousValue); })) {
            tlSelect.value = '';
        }

        if (tlHelp) {
            tlHelp.textContent = filteredTlUsers.length
                ? filteredTlUsers.length + ' TL option' + (filteredTlUsers.length === 1 ? '' : 's') + ' available for the current selection.'
                : 'No active TL users found for the current selection.';
        }

        updatePortalMappingSummary();
    }

    if (createPortalAccount) {
        createPortalAccount.addEventListener('change', function () {
            togglePortalFields();
            updateTlOptions();
        });
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', function () {
            syncDepartmentFromRole(true);
            filterRolesByDepartment();
            updateTlOptions();
        });
    }

    if (departmentSelect) {
        departmentSelect.addEventListener('change', function () {
            filterRolesByDepartment();
            updateTlOptions();
        });
    }

    if (branchSelect) {
        branchSelect.addEventListener('change', updateTlOptions);
    }

    if (tlSelect) {
        tlSelect.addEventListener('change', updatePortalMappingSummary);
    }

    if (departmentSelect && !departmentSelect.value && roleSelect && roleSelect.value) {
        syncDepartmentFromRole(true);
    }

    filterRolesByDepartment();
    togglePortalFields();
    updateTlOptions();

    function humanizeFieldName(name) {
        return (name || 'This field')
            .replace(/\[\d+\]/g, '')
            .replace(/\[(\w+)\]/g, ' $1')
            .replace(/_/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
    }

    function resolveFieldLabel(input) {
        const explicitLabel = input.dataset.label;
        if (explicitLabel) {
            return explicitLabel;
        }

        const choiceLabel = input.closest('.intern-choice')?.querySelector('.intern-choice-label');
        if (choiceLabel && input.type === 'radio') {
            const groupLabel = input.closest('.grid-half, .grid-full')?.querySelector('.intern-label');
            return groupLabel ? groupLabel.textContent.replace('*', '').trim() : choiceLabel.textContent.trim();
        }

        const directLabel = input.closest('.grid-half, .grid-full, .field-surface, .intern-doc-card, .intern-check-card, .signature-pad-wrap')?.querySelector('.intern-label');
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

    function clearStepValidationState() {
        panels[activeIndex].querySelectorAll('.is-invalid').forEach(function (node) {
            node.classList.remove('is-invalid');
        });

        if (validationSummary) {
            validationSummary.classList.remove('is-visible');
            validationSummary.innerHTML = '';
        }
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

    function validateCurrentStep() {
        clearStepValidationState();

        const inputs = Array.from(panels[activeIndex].querySelectorAll('input, select, textarea')).filter(function (input) {
            return !input.disabled && typeof input.checkValidity === 'function';
        });

        const invalidInputs = [];
        const invalidLabels = [];

        inputs.forEach(function (input) {
            if (!input.checkValidity()) {
                invalidInputs.push(input);
                invalidLabels.push(resolveFieldLabel(input));
                input.classList.add('is-invalid');
            }
        });

        if (!invalidInputs.length) {
            return true;
        }

        const uniqueLabels = Array.from(new Set(invalidLabels));
        showValidationSummary(uniqueLabels);
        invalidInputs[0].focus();
        invalidInputs[0].reportValidity();
        return false;
    }

    function updateWizard() {
        clearStepValidationState();

        panels.forEach(function (panel, index) {
            panel.classList.toggle('is-active', index === activeIndex);
        });

        stepButtons.forEach(function (button, index) {
            button.classList.toggle('is-active', index === activeIndex);
            button.classList.toggle('is-complete', index < activeIndex);
        });

        const stepLabel = stepButtons[activeIndex].querySelector('.wizard-step-copy strong');
        stepTitle.textContent = stepLabel ? stepLabel.textContent : 'Intern Joining Form';
        stepCounter.textContent = 'Step ' + (activeIndex + 1) + ' of ' + panels.length;
        progressBar.style.width = (((activeIndex + 1) / panels.length) * 100) + '%';
        prevButton.disabled = activeIndex === 0;
        nextButton.classList.toggle('d-none', activeIndex === panels.length - 1);
        submitButton.classList.toggle('d-none', activeIndex !== panels.length - 1);

        if (panels[activeIndex].getAttribute('data-step-panel') === 'review') {
            updateReview();
        }
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
            if (index <= activeIndex || validateCurrentStep()) {
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

    if (addEmploymentRow && employmentRows) {
        addEmploymentRow.addEventListener('click', function () {
            const currentRows = employmentRows.querySelectorAll('tr').length;
            if (currentRows >= 3) {
                return;
            }
            employmentRows.insertAdjacentHTML('beforeend', document.getElementById('employmentRowTemplate').innerHTML.replaceAll('__INDEX__', currentRows));
        });
    }

    if (addFamilyRow && familyRows) {
        addFamilyRow.addEventListener('click', function () {
            const currentRows = familyRows.querySelectorAll('tr').length;
            if (currentRows >= 5) {
                return;
            }
            familyRows.insertAdjacentHTML('beforeend', document.getElementById('familyRowTemplate').innerHTML.replaceAll('__INDEX__', currentRows));
        });
    }

    document.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-row]');
        if (!removeButton) {
            return;
        }
        const row = removeButton.closest('tr');
        row.remove();
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
