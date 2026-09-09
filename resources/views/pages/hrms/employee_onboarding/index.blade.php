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
                <div class="eob-field" style="max-width:220px;">
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
                <div class="eob-field" style="max-width:220px;">
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
                    @if(request()->hasAny(['search', 'status', 'department_id']))
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
                                <th>Role / Department</th>
                                <th>Progress</th>
                                <th>Contact</th>
                                <th>DOB</th>
                                <th>Status</th>
                                <th>Employee Type</th>
                                <th>Created</th>
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
                                        <span class="eob-chip eob-chip-{{ $employee->status }}">{{ ucfirst($employee->status) }}</span>
                                    </td>
                                    <td>
                                        @if($employee->employee_type === 'non_billable')
                                            <span class="eob-chip eob-chip-non-billable">Non Billable</span>
                                        @else
                                            <span class="eob-chip eob-chip-billable">Billable</span>
                                        @endif
                                    </td>
                                    <td>{{ $employee->created_at->format('d M Y') }}</td>
                                    <td>
                                        <details class="eob-table-dropdown">
                                            <summary class="eob-table-dropdown-trigger">Actions</summary>
                                            <div class="eob-table-dropdown-menu">
                                                <a href="{{ route('employee-onboarding.show', $employee) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                                <a href="{{ route('employee-onboarding.edit', $employee) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
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
    const modal = document.getElementById('deleteModal');
    const modalForm = document.getElementById('deleteModalForm');
    const modalName = document.getElementById('deleteEmployeeName');
    const cancelButton = document.getElementById('deleteModalCancel');

    document.querySelectorAll('[data-delete-trigger]').forEach(function (button) {
        button.addEventListener('click', function () {
            modalForm.setAttribute('action', button.getAttribute('data-action'));
            modalName.textContent = button.getAttribute('data-name');
            modal.classList.add('is-open');
        });
    });

    cancelButton.addEventListener('click', function () {
        modal.classList.remove('is-open');
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.classList.remove('is-open');
        }
    });
});
</script>
@endpush
