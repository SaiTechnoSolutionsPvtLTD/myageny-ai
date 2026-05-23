@extends('layouts.app')

@section('title', 'Intern Joining Forms')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.Interns.intern_joining_forms.styles')
@endpush

@section('content')
<div class="intern-page">
    <div class="intern-shell">
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
        <div class="intern-body">
            @if(session('success'))
                <div class="intern-alert intern-alert-success">{!! session('success') !!}</div>
            @endif
            @if(session('error'))
                <div class="intern-alert" style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">{{ session('error') }}</div>
            @endif



            <div class="intern-card intern-list-card">
                <div class="intern-card-head">
                    <div>
                        <div class="intern-card-title">Intern Records</div>
                        <div class="intern-card-subtitle">Review submitted intern details, internship duration, and current status in one place.</div>
                    </div>
                    <div class="intern-results-chip">{{ $forms->total() }} total</div>
                </div>

                        <div class="intern-filter-wrap">
                    <form method="GET" action="{{ route('interns.index') }}" class="intern-filter-form">
                        <div class="intern-filter-field">
                            <label class="intern-filter-label">Search Intern</label>
                            <input type="text" name="search" class="intern-input" value="{{ request('search') }}" placeholder="Search by intern ID, name, email, mobile, aadhaar">
                        </div>
                        <div class="intern-filter-field">
                            <label class="intern-filter-label">Department</label>
                            <select name="department_id" class="intern-input">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="intern-filter-field">
                            <label class="intern-filter-label">Status</label>
                            <select name="internship_status" class="intern-input">
                                <option value="">All Status</option>
                                <option value="active" @selected(request('internship_status') === 'active')>Active</option>
                                <option value="resigned" @selected(request('internship_status') === 'resigned')>Resigned</option>
                            </select>
                        </div>
                        <div class="intern-filter-actions">
                            <button type="submit" class="btn btn-primary">Search</button>
                            @if(request()->filled('search') || request()->filled('department_id') || request()->filled('internship_status'))
                                <a href="{{ route('interns.index') }}" class="btn btn-outline-secondary">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="table-responsive intern-list-table-wrap">
                    <table class="table table-hover align-middle intern-list-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Intern</th>
                                <th>Contact</th>
                                <th>Internship Timeline</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($forms as $item)
                            <tr>
                                <td>
                                    <div class="intern-row-index">{{ ($forms->firstItem() ?? 1) + $loop->index }}</div>
                                </td>
                                <td>
                                    <div class="intern-person-cell">
                                        <div class="intern-person-avatar">{{ strtoupper(substr($item->name, 0, 1)) }}</div>
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
                                    <div class="intern-contact-main">{{ $item->mobile ?: 'N/A' }}</div>
                                    <div class="intern-contact-sub">{{ $item->email ?: 'Email not added' }}</div>
                                </td>
                                <td>
                                    <div class="intern-date-main">{{ optional($item->internship_start_date)->format('d M Y') ?: 'N/A' }} to {{ optional($item->internship_end_date)->format('d M Y') ?: 'N/A' }}</div>
                                    <div class="intern-date-sub">
                                        {{ $item->department?->name ?: 'Department not mapped' }}
                                        @if($item->internship_duration_months)
                                            • {{ $item->internship_duration_months }} month(s)
                                        @else
                                            • Duration not set
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="intern-date-main">{{ ucfirst($item->internship_status ?: 'active') }}</div>
                                    <div class="intern-date-sub">{{ optional($item->date_of_birth)->age ? optional($item->date_of_birth)->age . ' yrs' : 'Age unavailable' }}</div>
                                </td>
                                <td class="text-end">
                                    <details class="eob-table-dropdown">
                                        <summary class="eob-table-dropdown-trigger">Actions</summary>
                                        <div class="eob-table-dropdown-menu">
                                            <a href="{{ route('interns.show', $item) }}" class="eob-table-dropdown-item">View</a>
                                            @if($item->convertedEmployee)
                                                <a href="{{ route('employee-onboarding.show', $item->convertedEmployee) }}" class="eob-table-dropdown-item">Employee</a>
                                            @else
                                                <a href="{{ route('interns.convert-to-employee', $item) }}" class="eob-table-dropdown-item">Convert</a>
                                            @endif
                                            <a href="{{ route('interns.edit', $item) }}" class="eob-table-dropdown-item">Edit</a>
                                            <form method="POST" action="{{ route('interns.destroy', $item) }}" onsubmit="return confirm('Delete this intern form?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="eob-table-dropdown-item danger">Delete</button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-5">
                                    <div class="intern-empty-state">
                                        <strong>No intern forms found</strong>
                                        <span>Try a different search or create a new intern joining form.</span>
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
</div>
@endsection
