@extends('layouts.app')

@section('title', 'Convert Intern To Employee')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.Interns.intern_joining_forms.styles')
@endpush

@section('content')
<div class="intern-page">
    <div class="intern-shell">
        <div class="eob-topbar">
            <div>
                <div class="eob-title">Convert Intern To Employee</div>
                <div class="eob-breadcrumb">HRMS > Intern Joining Forms > Convert</div>
            </div>
            <div class="eob-actions">
                <a href="{{ route('interns.show', $form) }}" class="eob-btn eob-btn-ghost">Back to Intern</a>
            </div>
        </div>

        <div class="intern-body">
            @if($errors->any())
                <div class="eob-alert eob-alert-error">Please review the portal account details and try again.</div>
            @endif
            @if($form->convertedEmployee)
                <div class="eob-alert eob-alert-success">
                    This intern is already converted to employee <strong>{{ $form->convertedEmployee->employee_id }}</strong>.
                    <a href="{{ route('employee-onboarding.show', $form->convertedEmployee) }}">Open employee profile</a>
                </div>
            @endif

            <div class="eob-show-layout intern-show-layout">
                <aside class="eob-profile eob-profile-sticky intern-show-sidebar">
                    <div class="eob-profile-banner"></div>
                    <div class="eob-profile-body">
                        <div class="eob-avatar intern-show-avatar">
                            @if($form->photograph)
                                <img src="{{ asset('storage/' . $form->photograph) }}" alt="{{ $form->name }}">
                            @else
                                <span>{{ strtoupper(substr($form->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="eob-profile-name">{{ $form->name }}</div>
                        <div class="eob-profile-mail">{{ $form->email }}</div>

                        <div class="eob-empid-card">
                            <div class="eob-empid-label">Ready To Convert</div>
                            <div class="eob-empid-value" id="convertEmployeeIdDisplay">{{ $generatedEmployeeId }}</div>
                            <div class="eob-empid-sub">This employee ID will be assigned after portal account creation.</div>
                        </div>

                        <div class="eob-side-list">
                            <div class="eob-side-item">
                                <div class="eob-side-label">Intern ID</div>
                                <div class="eob-side-value">{{ $form->intern_id ?: 'N/A' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Mobile</div>
                                <div class="eob-side-value">{{ $form->mobile ?: 'N/A' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Date of Birth</div>
                                <div class="eob-side-value">{{ optional($form->date_of_birth)->format('d M Y') ?: 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="intern-show-main">
                    <div class="eob-show-card">
                        <div class="eob-card-head">
                            <div>
                                <div class="eob-card-title">Employee Portal Account</div>
                                <div class="eob-card-sub">Create the login details and reporting setup before converting this intern.</div>
                            </div>
                        </div>
                        <div class="eob-card-body">
                            <form method="POST" action="{{ route('interns.convert-to-employee.store', $form) }}">
                                @csrf
                                <div class="eob-form-grid">
                                    <div class="eob-group">
                                        <label class="eob-label">Employee ID</label>
                                        <input type="text" class="eob-input" id="convertEmployeeIdInput" value="{{ $generatedEmployeeId }}" readonly>
                                        <div class="eob-help">Auto-generated during conversion.</div>
                                    </div>
                                    <div class="eob-group">
                                        <label class="eob-label">Portal Email <span class="eob-label-required">*</span></label>
                                        <input type="email" name="portal_email" class="eob-input" value="{{ old('portal_email', $form->email) }}" required>
                                        @error('portal_email')<div class="eob-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="eob-group">
                                        <label class="eob-label">Portal Password <span class="eob-label-required">*</span></label>
                                        <input type="password" name="portal_password" class="eob-input" required>
                                        <div class="eob-help">Minimum 8 characters.</div>
                                        @error('portal_password')<div class="eob-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="eob-group">
                                        <label class="eob-label">Branch <span class="eob-label-required">*</span></label>
                                        <select name="branch_id" class="eob-select" id="convertBranchSelect" required>
                                            <option value="">Select Branch</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" @selected((string) old('branch_id', auth()->user()?->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('branch_id')<div class="eob-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="eob-group">
                                        <label class="eob-label">Department <span class="eob-label-required">*</span></label>
                                        <select name="department_id" class="eob-select" id="convertDepartmentSelect" required>
                                            <option value="">Select Department</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('department_id')<div class="eob-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="eob-group">
                                        <label class="eob-label">Role <span class="eob-label-required">*</span></label>
                                        <select name="role_id" class="eob-select" id="convertRoleSelect" required>
                                            <option value="">Select Role</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    data-department-id="{{ $role->department_id }}"
                                                    data-parent-role-id="{{ $role->roleParentMapping?->parent_role_id }}"
                                                    data-parent-role-name="{{ $role->roleParentMapping?->parentRole?->display_name ?: $role->roleParentMapping?->parentRole?->name }}"
                                                    @selected((string) old('role_id') === (string) $role->id)>
                                                    {{ $role->display_name ?: $role->name }}{{ $role->department ? ' - ' . $role->department->name : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role_id')<div class="eob-error">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="eob-group full">
                                        <label class="eob-label">Team Lead <span class="eob-label-required">*</span></label>
                                        <select name="tl_user_id" class="eob-select" id="convertTlSelect" data-selected-tl="{{ old('tl_user_id') }}" required>
                                            <option value="">Select Team Lead</option>
                                            @foreach($tlUsers as $tlUser)
                                                <option value="{{ $tlUser['id'] }}" @selected((string) old('tl_user_id') === (string) $tlUser['id'])>
                                                    {{ $tlUser['name'] }} - {{ $tlUser['role_label'] }}{{ $tlUser['branch_name'] ? ' - ' . $tlUser['branch_name'] : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('tl_user_id')<div class="eob-error">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="eob-foot">
                                    <a href="{{ route('interns.show', $form) }}" class="eob-btn eob-btn-ghost">Cancel</a>
                                    @if(!$form->convertedEmployee)
                                        <button type="submit" class="eob-btn eob-btn-primary">Create Login And Convert</button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const branchSelect = document.getElementById('convertBranchSelect');
    const departmentSelect = document.getElementById('convertDepartmentSelect');
    const roleSelect = document.getElementById('convertRoleSelect');
    const tlSelect = document.getElementById('convertTlSelect');
    const isConverted = {{ $form->convertedEmployee ? 'true' : 'false' }};
    const tlUsersData = @json($tlUsers);

    if (branchSelect && !isConverted) {
        branchSelect.addEventListener('change', function () {
            const branchId = branchSelect.value;
            fetch(`/employee-onboarding/generate-id?branch_id=${branchId}`)
                .then(res => res.json())
                .then(data => {
                    const empIdInput = document.getElementById('convertEmployeeIdInput');
                    const empIdDisplay = document.getElementById('convertEmployeeIdDisplay');
                    if (data.employee_id) {
                        if (empIdInput) empIdInput.value = data.employee_id;
                        if (empIdDisplay) empIdDisplay.textContent = data.employee_id;
                    }
                })
                .catch(err => console.error('Error fetching generated Employee ID:', err));
            filterTlOptions();
        });
    }

    function filterRolesByDepartment() {
        if (!roleSelect) return;
        const selectedDeptId = departmentSelect ? departmentSelect.value : '';

        Array.from(roleSelect.options).forEach(option => {
            if (!option.value) return;
            const deptId = option.getAttribute('data-department-id');
            if (!selectedDeptId || !deptId || String(deptId) === String(selectedDeptId)) {
                option.style.display = '';
                option.disabled = false;
            } else {
                option.style.display = 'none';
                option.disabled = true;
            }
        });

        const selectedOpt = roleSelect.options[roleSelect.selectedIndex];
        if (selectedOpt && selectedOpt.value && selectedOpt.disabled) {
            roleSelect.value = '';
        }
    }

    function filterTlOptions() {
        if (!tlSelect) return;
        const selectedDeptId = departmentSelect ? departmentSelect.value : '';
        const selectedBranchId = branchSelect ? branchSelect.value : '';
        const selectedRoleOpt = roleSelect && roleSelect.selectedIndex >= 0 ? roleSelect.options[roleSelect.selectedIndex] : null;
        const parentRoleId = selectedRoleOpt ? selectedRoleOpt.getAttribute('data-parent-role-id') : null;
        const previousVal = tlSelect.value || tlSelect.getAttribute('data-selected-tl') || '';

        tlSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select Team Lead';
        tlSelect.appendChild(placeholder);

        const filtered = tlUsersData.filter(user => {
            const matchesBranch = !selectedBranchId || !user.branch_id || String(user.branch_id) === String(selectedBranchId) || user.is_super_admin;
            const depts = Array.isArray(user.department_ids) ? user.department_ids.map(String) : [];
            const matchesDept = !selectedDeptId || depts.includes(String(selectedDeptId)) || depts.includes('') || user.is_super_admin;

            let matchesRoleMapping = true;
            if (parentRoleId && Array.isArray(user.role_ids)) {
                matchesRoleMapping = user.role_ids.map(String).includes(String(parentRoleId)) || user.is_super_admin;
            }

            return matchesDept && matchesBranch && matchesRoleMapping;
        });

        filtered.forEach(user => {
            const opt = document.createElement('option');
            opt.value = user.id;
            opt.textContent = user.name + ' - ' + (user.role_label || 'Team Lead') + (user.branch_name ? ' - ' + user.branch_name : '');
            if (String(user.id) === String(previousVal)) {
                opt.selected = true;
            }
            tlSelect.appendChild(opt);
        });

        if (previousVal && !filtered.some(u => String(u.id) === String(previousVal))) {
            tlSelect.value = '';
        }
    }

    if (departmentSelect) {
        departmentSelect.addEventListener('change', function () {
            filterRolesByDepartment();
            filterTlOptions();
        });
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', function () {
            const selectedOpt = roleSelect.options[roleSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const roleDeptId = selectedOpt.getAttribute('data-department-id');
                if (roleDeptId && departmentSelect && !departmentSelect.value) {
                    departmentSelect.value = roleDeptId;
                    filterRolesByDepartment();
                }
            }
            filterTlOptions();
        });
    }

    filterRolesByDepartment();
    filterTlOptions();
});
</script>
@endpush
