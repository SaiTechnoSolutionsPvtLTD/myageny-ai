@extends('layouts.app')

@section('title', 'Intern Joining Forms')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.Interns.intern_joining_forms.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Intern Joining Forms</div>
            <div class="eob-breadcrumb">HRMS > Intern Joining Forms</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('interns.create') }}" class="eob-btn eob-btn-primary">Add Intern Form</a>
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
            <form method="GET" action="{{ route('interns.index') }}" class="eob-filter-form">
                <div class="eob-field">
                    <label class="eob-label">Search</label>
                    <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Intern ID, name, email, mobile, aadhaar">
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
                                {{ $role->display_name ?: ucfirst(str_replace(['_', '-'], ' ', \Illuminate\Support\Str::afterLast($role->name, '__'))) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-field" style="max-width:170px;">
                    <label class="eob-label">Status</label>
                    <select name="internship_status" class="eob-select">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('internship_status') === 'active' || request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('internship_status') === 'inactive' || request('status') === 'inactive')>Inactive</option>
                        <option value="resigned" @selected(request('internship_status') === 'resigned' || request('status') === 'resigned')>Resigned</option>
                    </select>
                </div>
                <div class="eob-actions">
                    <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                    @if(request()->hasAny(['search', 'branch_id', 'department_id', 'role_id', 'internship_status', 'status']))
                        <a href="{{ route('interns.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="eob-table-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Intern Records</div>
                    <div class="eob-card-sub">Review submitted intern details, internship duration, and current status in one place.</div>
                </div>
                <span class="eob-badge">{{ $forms->total() }} total</span>
            </div>

            <div class="table-responsive eob-table-wrap">
                <table class="eob-table">
                    <thead>
                        <tr>
                            <th style="width:48px;">#</th>
                            <th>Intern</th>
                            <th>Contact</th>
                            <th>Internship Timeline</th>
                            <th>Status</th>
                            <th style="width:80px; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($forms as $item)
                        <tr>
                            <td class="eob-num">{{ ($forms->firstItem() ?? 1) + $loop->index }}</td>
                            <td>
                                <div class="intern-person-cell">
                                    <div class="intern-person-avatar">
                                        @if($item->photograph)
                                            <img src="{{ asset('storage/' . $item->photograph) }}" alt="{{ $item->name }}">
                                        @else
                                            {{ strtoupper(substr($item->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="intern-person-name">{{ $item->name }}</div>
                                        <div class="intern-person-sub">
                                            {{ $item->intern_id ?: 'Intern ID pending' }}
                                            @if($item->convertedEmployee)
                                                • Converted to {{ $item->convertedEmployee->employee_id }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="eob-cell-title">{{ $item->mobile ?: 'N/A' }}</div>
                                <div class="eob-cell-sub">{{ $item->email ?: 'Email not added' }}</div>
                            </td>
                            <td>
                                <div class="eob-cell-title">{{ optional($item->internship_start_date)->format('d M Y') ?: 'N/A' }} to {{ optional($item->internship_end_date)->format('d M Y') ?: 'N/A' }}</div>
                                <div class="eob-cell-sub">
                                    {{ $item->department?->name ?: 'Department not mapped' }}
                                    @if($item->internship_duration_months)
                                        • {{ $item->internship_duration_months }} month(s)
                                    @else
                                        • Duration not set
                                    @endif
                                </div>
                            </td>
                            <td>
                                <button type="button"
                                        class="eob-chip eob-chip-{{ $item->internship_status ?: 'active' }} eob-status-trigger"
                                        data-status-trigger
                                        data-name="{{ $item->name }}"
                                        data-empid="{{ $item->intern_id ?: ('#' . $item->id) }}"
                                        data-status="{{ $item->internship_status ?: 'active' }}"
                                        data-action="{{ route('interns.update-status', $item) }}"
                                        title="Click to update status">
                                    <span>{{ ucfirst($item->internship_status ?: 'active') }}</span>
                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="margin-left:3px; opacity:.7;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <div class="eob-cell-sub" style="margin-top:4px;">{{ optional($item->date_of_birth)->age ? optional($item->date_of_birth)->age . ' yrs' : 'Age unavailable' }}</div>
                            </td>
                            <td style="text-align:right;">
                                <details class="eob-table-dropdown">
                                    <summary class="eob-table-dropdown-trigger">Actions</summary>
                                    <div class="eob-table-dropdown-menu">
                                        <a href="{{ route('interns.show', $item) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                        @if($item->convertedEmployee)
                                            <a href="{{ route('employee-onboarding.show', $item->convertedEmployee) }}" class="eob-table-dropdown-item"><i class="bi bi-person-badge"></i> Employee</a>
                                        @else
                                            <a href="{{ route('interns.convert-to-employee', $item) }}" class="eob-table-dropdown-item"><i class="bi bi-arrow-right-circle"></i> Convert</a>
                                        @endif
                                        <a href="{{ route('interns.edit', $item) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
                                        <button
                                            type="button"
                                            class="eob-table-dropdown-item"
                                            data-status-trigger
                                            data-name="{{ $item->name }}"
                                            data-empid="{{ $item->intern_id ?: ('#' . $item->id) }}"
                                            data-status="{{ $item->internship_status ?: 'active' }}"
                                            data-action="{{ route('interns.update-status', $item) }}"
                                        ><i class="bi bi-arrow-repeat"></i> Change Status</button>
                                        <button
                                            type="button"
                                            class="eob-table-dropdown-item danger"
                                            data-delete-trigger
                                            data-name="{{ $item->name }}"
                                            data-action="{{ route('interns.destroy', $item) }}"
                                        ><i class="bi bi-trash"></i> Delete</button>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:48px 16px; color:#8a8a8a;">
                                <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                    <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="color:#d1d5db;"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <div style="font-size:14px; font-weight:700; color:#475569;">No intern forms found</div>
                                    <div style="font-size:12px; color:#94a3b8;">Try a different search or create a new intern joining form.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($forms->hasPages())
                @include('partials.table-pagination', ['paginator' => $forms])
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
                    Update Intern Status
                </div>
                <div class="eob-modal-copy" style="margin-top:6px;">
                    Changing status for <strong id="statusInternName">Intern</strong> (<span id="statusInternId">ID</span>)
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
                    <span style="font-weight:700;">Note:</span> Setting status to <strong>Inactive</strong> or <strong>Resigned</strong> will automatically deactivate the intern's user portal account.
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
            <div class="eob-modal-title">Delete intern record?</div>
            <div class="eob-modal-copy">This will remove the intern joining form, documents, and records for <strong id="deleteInternName">this intern</strong>.</div>
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
    const statusInternName = document.getElementById('statusInternName');
    const statusInternId = document.getElementById('statusInternId');
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
            statusInternName.textContent = name;
            statusInternId.textContent = empId;
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
    const deleteInternName = document.getElementById('deleteInternName');
    const deleteModalCancel = document.getElementById('deleteModalCancel');

    document.querySelectorAll('[data-delete-trigger]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Close any open dropdowns
            document.querySelectorAll('details.eob-table-dropdown[open]').forEach(d => d.removeAttribute('open'));

            deleteModalForm.setAttribute('action', button.getAttribute('data-action'));
            deleteInternName.textContent = button.getAttribute('data-name');
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
