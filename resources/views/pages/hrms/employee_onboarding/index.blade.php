@extends('layouts.app')

@section('title', 'Employee Onboarding')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Employee Onboarding</div>
            <div class="eob-breadcrumb">HRMS > Employee Onboarding</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('employee-onboarding.create') }}" class="eob-btn eob-btn-primary">Add Employee</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div class="eob-filter-card">
            <form method="GET" action="{{ route('employee-onboarding.index') }}" class="eob-filter-form">
                <div class="eob-field">
                    <label class="eob-label">Search</label>
                    <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Employee ID, name, email, mobile, aadhaar">
                </div>
                <div class="eob-field" style="max-width:200px;">
                    <label class="eob-label">Branch</label>
                    <select name="branch_id" class="eob-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-field" style="max-width:200px;">
                    <label class="eob-label">Department</label>
                    <select name="department_id" class="eob-select">
                        <option value="">All Departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-field" style="max-width:200px;">
                    <label class="eob-label">Role</label>
                    <select name="role_id" class="eob-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>
                                {{ $role->display_name ?: ucfirst(str_replace(['_', '-'], ' ', Str::afterLast($role->name, '__'))) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-field" style="max-width:170px;">
                    <label class="eob-label">Status</label>
                    <select name="status" class="eob-select">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        <option value="resigned" @selected(request('status') === 'resigned')>Resigned</option>
                    </select>
                </div>
                <div class="eob-actions">
                    <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                    @if(request()->hasAny(['search', 'branch_id', 'department_id', 'role_id', 'status']))
                        <a href="{{ route('employee-onboarding.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="eob-table-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Onboarding Records</div>
                    <div class="eob-card-sub">Track candidate profiles, uploaded documents, and onboarding status.</div>
                </div>
                <div class="eob-results">{{ $employees->total() }} employee(s)</div>
            </div>

            @if($employees->isEmpty())
                <div class="eob-empty">No onboarding records found.</div>
            @else
                <div style="overflow-x:auto;">
                    <table class="eob-list-table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Role / Department</th>
                                <th>Progress</th>
                                <th>Contact</th>
                                <th>DOB</th>
                                <th>Status</th>
                                <th>Employee Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                                <tr>
                                    <td>
                                        <div class="eob-cell-title">{{ $employee->employee_id }}</div>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $employee->name }}</div>
                                        <div class="eob-cell-sub">
                                            {{ $employee->sourceIntern ? 'Converted from intern ' . ($employee->sourceIntern->intern_id ?: $employee->sourceIntern->name) : ($employee->father_name ?: 'Father name not added') }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="eob-badge-branch" title="Branch: {{ $employee->branch_name }}">
                                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                            {{ $employee->branch_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $employee->role?->display_name ?: ($employee->role?->name ?: 'No role') }}</div>
                                        <div class="eob-cell-sub">{{ $employee->department?->name ?: 'No department' }}</div>
                                    </td>
                                    <td>
                                        @php
                                            $pct = $employee->profile_completion_percentage;
                                            $fillClass = $pct >= 80 ? 'eob-progress-high' : ($pct >= 50 ? 'eob-progress-mid' : 'eob-progress-low');
                                        @endphp
                                        <div class="eob-progress-wrap">
                                            <div class="eob-progress-info">
                                                <span>{{ $pct }}%</span>
                                            </div>
                                            <div class="eob-progress-bar-bg">
                                                <div class="eob-progress-bar-fill {{ $fillClass }}" style="width: {{ $pct }}%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $employee->mobile }}</div>
                                        <div class="eob-cell-sub">{{ $employee->email }}</div>
                                    </td>
                                    <td>{{ optional($employee->date_of_birth)->format('d M Y') }}</td>
                                    <td>
                                        <button type="button"
                                                class="eob-chip eob-chip-{{ $employee->status }} eob-status-trigger"
                                                data-status-trigger
                                                data-name="{{ $employee->name }}"
                                                data-empid="{{ $employee->employee_id }}"
                                                data-status="{{ $employee->status }}"
                                                data-action="{{ route('employee-onboarding.update-status', $employee) }}"
                                                title="Click to update status">
                                            <span>{{ ucfirst($employee->status) }}</span>
                                            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="margin-left:3px; opacity:.7;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                    </td>
                                    <td>
                                        @if($employee->employee_type === 'non_billable')
                                            <span class="eob-chip eob-chip-non-billable">Non Billable</span>
                                        @else
                                            <span class="eob-chip eob-chip-billable">Billable</span>
                                        @endif
                                    </td>
                                    <td>
                                        <details class="eob-table-dropdown">
                                            <summary class="eob-table-dropdown-trigger">Actions</summary>
                                            <div class="eob-table-dropdown-menu">
                                                <a href="{{ route('employee-onboarding.show', $employee) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                                <a href="{{ route('employee-onboarding.edit', $employee) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
                                                <button
                                                    type="button"
                                                    class="eob-table-dropdown-item"
                                                    data-status-trigger
                                                    data-name="{{ $employee->name }}"
                                                    data-empid="{{ $employee->employee_id }}"
                                                    data-status="{{ $employee->status }}"
                                                    data-action="{{ route('employee-onboarding.update-status', $employee) }}"
                                                ><i class="bi bi-arrow-repeat"></i> Change Status</button>
                                                <button
                                                    type="button"
                                                    class="eob-table-dropdown-item danger"
                                                    data-delete-trigger
                                                    data-name="{{ $employee->name }}"
                                                    data-action="{{ route('employee-onboarding.destroy', $employee) }}"
                                                ><i class="bi bi-trash"></i> Delete</button>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($employees->hasPages())
                    @include('partials.table-pagination', ['paginator' => $employees])
                @endif
            @endif
        </div>
    </div>
</div>

{{-- Status Update Modal --}}
<div class="eob-modal" id="statusModal">
    <div class="eob-modal-card" style="width:min(100%, 460px);">
        <form method="POST" id="statusModalForm">
            @csrf
            @method('PATCH')
            <div class="eob-modal-head">
                <div class="eob-modal-title" style="display:flex; align-items:center; gap:8px;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:10px; background:#fff7ed; color:#fe5f04;">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </span>
                    Update Employee Status
                </div>
                <div class="eob-modal-copy" style="margin-top:6px;">
                    Changing status for <strong id="statusEmployeeName">Employee</strong> (<span id="statusEmployeeId">ID</span>)
                </div>
            </div>
            <div style="padding:0 22px 18px; display:flex; flex-direction:column; gap:14px;">
                <div class="eob-group">
                    <label class="eob-label" style="font-size:12px;">Select New Status <span class="eob-label-required">*</span></label>
                    <select name="status" id="statusModalSelect" class="eob-select" required>
                        <option value="active">Active (User account active)</option>
                        <option value="inactive">Inactive (User account deactivated)</option>
                        <option value="resigned">Resigned (User account deactivated)</option>
                    </select>
                </div>
                <div id="statusNotice" style="font-size:11.5px; padding:10px 12px; border-radius:10px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; line-height:1.45;">
                    <span style="font-weight:700;">Note:</span> Setting status to <strong>Inactive</strong> or <strong>Resigned</strong> will automatically deactivate the employee's user portal account.
                </div>
            </div>
            <div class="eob-modal-foot" style="background:#fafafa; border-top:1px solid #f0eef2;">
                <button type="button" class="eob-btn eob-btn-ghost" id="statusModalCancel">Cancel</button>
                <button type="submit" class="eob-btn eob-btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="eob-modal" id="deleteModal">
    <div class="eob-modal-card">
        <div class="eob-modal-head">
            <div class="eob-modal-title">Delete employee record?</div>
            <div class="eob-modal-copy">This will remove the onboarding profile, repeated details, and all uploaded files for <strong id="deleteEmployeeName">this employee</strong>.</div>
        </div>
        <div class="eob-modal-foot">
            <button type="button" class="eob-btn eob-btn-ghost" id="deleteModalCancel">Cancel</button>
            <form method="POST" id="deleteModalForm">
                @csrf
                @method('DELETE')
                <button type="submit" class="eob-btn eob-btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Status Modal ────────────────────────────────────────────────
    const statusModal = document.getElementById('statusModal');
    const statusModalForm = document.getElementById('statusModalForm');
    const statusEmployeeName = document.getElementById('statusEmployeeName');
    const statusEmployeeId = document.getElementById('statusEmployeeId');
    const statusModalSelect = document.getElementById('statusModalSelect');
    const statusModalCancel = document.getElementById('statusModalCancel');

    document.querySelectorAll('[data-status-trigger]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Close any open dropdowns
            document.querySelectorAll('details.eob-table-dropdown[open]').forEach(d => d.removeAttribute('open'));

            const action = button.getAttribute('data-action');
            const name = button.getAttribute('data-name');
            const empId = button.getAttribute('data-empid');
            const status = button.getAttribute('data-status');

            statusModalForm.setAttribute('action', action);
            statusEmployeeName.textContent = name;
            statusEmployeeId.textContent = empId;
            if (statusModalSelect) {
                statusModalSelect.value = status || 'active';
            }
            statusModal.classList.add('is-open');
        });
    });

    if (statusModalCancel) {
        statusModalCancel.addEventListener('click', function () {
            statusModal.classList.remove('is-open');
        });
    }

    if (statusModal) {
        statusModal.addEventListener('click', function (event) {
            if (event.target === statusModal) {
                statusModal.classList.remove('is-open');
            }
        });
    }

    // ── Delete Modal ────────────────────────────────────────────────
    const deleteModal = document.getElementById('deleteModal');
    const deleteModalForm = document.getElementById('deleteModalForm');
    const deleteEmployeeName = document.getElementById('deleteEmployeeName');
    const deleteModalCancel = document.getElementById('deleteModalCancel');

    document.querySelectorAll('[data-delete-trigger]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Close any open dropdowns
            document.querySelectorAll('details.eob-table-dropdown[open]').forEach(d => d.removeAttribute('open'));

            deleteModalForm.setAttribute('action', button.getAttribute('data-action'));
            deleteEmployeeName.textContent = button.getAttribute('data-name');
            deleteModal.classList.add('is-open');
        });
    });

    if (deleteModalCancel) {
        deleteModalCancel.addEventListener('click', function () {
            deleteModal.classList.remove('is-open');
        });
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', function (event) {
            if (event.target === deleteModal) {
                deleteModal.classList.remove('is-open');
            }
        });
    }

    // ── Escape key closes modals & dropdowns ────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (statusModal) statusModal.classList.remove('is-open');
            if (deleteModal) deleteModal.classList.remove('is-open');
            document.querySelectorAll('details.eob-table-dropdown[open]').forEach(d => d.removeAttribute('open'));
        }
    });
});
</script>
@endpush
