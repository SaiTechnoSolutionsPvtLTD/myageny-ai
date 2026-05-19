@extends('layouts.app')

@section('title', 'View Intern Joining Form')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.Interns.intern_joining_forms.styles')
@endpush

@section('content')
@php
    $profileInitial = strtoupper(substr($form->name ?? 'I', 0, 1));
    $maritalStatus = $form->marital_status ? ucfirst($form->marital_status) : 'N/A';
@endphp
<div class="intern-page">
    <div class="intern-shell">
        <div class="eob-topbar">
            <div>
                <div class="eob-title">{{ $form->name }}</div>
                <div class="eob-breadcrumb">HRMS > Intern Joining Forms > View</div>
            </div>
            <div class="eob-actions">
                @if($form->convertedEmployee)
                    <a href="{{ route('employee-onboarding.show', $form->convertedEmployee) }}" class="eob-btn eob-btn-primary">View Employee</a>
                @else
                    <a href="{{ route('interns.convert-to-employee', $form) }}" class="eob-btn eob-btn-primary">Convert to Employee</a>
                @endif
                <a href="{{ route('interns.edit', $form) }}" class="eob-btn eob-btn-primary">Edit</a>
                <a href="{{ route('interns.index') }}" class="eob-btn eob-btn-ghost">Back</a>
            </div>
        </div>
        <div class="intern-body">
            @if(session('success'))
                <div class="intern-alert intern-alert-success">{!! session('success') !!}</div>
            @endif
            @if(session('error'))
                <div class="intern-alert" style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">{{ session('error') }}</div>
            @endif

            <div class="eob-show-layout intern-show-layout">
                <aside class="eob-profile eob-profile-sticky intern-show-sidebar">
                    <div class="eob-profile-banner"></div>
                    <div class="eob-profile-body">
                        <div class="eob-avatar intern-show-avatar">
                            @if($form->photograph)
                                <img src="{{ asset('storage/' . $form->photograph) }}" alt="{{ $form->name }}">
                            @else
                                <span>{{ $profileInitial }}</span>
                            @endif
                        </div>
                        <div class="eob-profile-name">{{ $form->name }}</div>
                        <div class="eob-profile-mail">{{ $form->email ?: 'Email not added' }}</div>

                        <div class="eob-empid-card">
                            <div class="eob-empid-label">Intern Snapshot</div>
                            <div class="eob-empid-value">{{ $form->intern_id ?: 'Pending' }}</div>
                            <div class="eob-empid-sub">
                                {{ $form->convertedEmployee ? 'Converted to employee ' . $form->convertedEmployee->employee_id : 'Generated intern ID' }}
                            </div>
                        </div>

                        <div class="eob-side-list">
                            <div class="eob-side-item">
                                <div class="eob-side-label">Internship Status</div>
                                <div class="eob-side-value">{{ ucfirst($form->internship_status ?: 'active') }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Date of Birth</div>
                                <div class="eob-side-value">{{ optional($form->date_of_birth)->format('d M Y') ?: 'N/A' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Blood Group</div>
                                <div class="eob-side-value">{{ $form->blood_group ?: 'N/A' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Marital Status</div>
                                <div class="eob-side-value">{{ $maritalStatus }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Date of Marriage</div>
                                <div class="eob-side-value">{{ optional($form->date_of_marriage)->format('d M Y') ?: 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="intern-show-main">
                    <div class="eob-show-card">
                        <div class="intern-section-head">
                            <div>
                                <div class="intern-section-title">Personal Details</div>
                                <div class="intern-section-subtitle">Core identity, emergency contact, and address information.</div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="eob-show-grid intern-detail-grid">
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Intern ID</div>
                                    <div class="eob-show-value">{{ $form->intern_id ?: 'Pending' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Conversion Status</div>
                                    <div class="eob-show-value">
                                        @if($form->convertedEmployee)
                                            <a href="{{ route('employee-onboarding.show', $form->convertedEmployee) }}" class="text-decoration-none">{{ $form->convertedEmployee->employee_id }} - Converted to Employee</a>
                                        @else
                                            Not converted yet
                                        @endif
                                    </div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Portal Account</div>
                                    <div class="eob-show-value">{{ $form->portalUser ? 'Enabled' : 'Not created' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Internship Start Date</div>
                                    <div class="eob-show-value">{{ optional($form->internship_start_date)->format('d M Y') ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Duration</div>
                                    <div class="eob-show-value">{{ $form->internship_duration_months ? $form->internship_duration_months . ' month(s)' : 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Internship End Date</div>
                                    <div class="eob-show-value">{{ optional($form->internship_end_date)->format('d M Y') ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Status</div>
                                    <div class="eob-show-value">{{ ucfirst($form->internship_status ?: 'active') }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Portal Email</div>
                                    <div class="eob-show-value">{{ $form->portalUser?->email ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Department</div>
                                    <div class="eob-show-value">{{ $form->department?->name ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Role</div>
                                    <div class="eob-show-value">{{ $form->role?->display_name ?: $form->role?->name ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Father's Name</div>
                                    <div class="eob-show-value">{{ $form->father_name ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Aadhaar Card No</div>
                                    <div class="eob-show-value">{{ $form->aadhaar_card_no ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Pan Card No</div>
                                    <div class="eob-show-value">{{ $form->pan_card_no ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Emergency Contact Name</div>
                                    <div class="eob-show-value">{{ $form->emergency_contact_name ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Emergency Relation</div>
                                    <div class="eob-show-value">{{ $form->emergency_contact_relation ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Emergency Contact No</div>
                                    <div class="eob-show-value">{{ $form->emergency_contact_no ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Correspondence Address</div>
                                    <div class="eob-show-value">{{ $form->correspondence_address ?: 'N/A' }}</div>
                                </div>
                                <div class="eob-show-item">
                                    <div class="eob-show-label">Permanent Address</div>
                                    <div class="eob-show-value">{{ $form->permanent_address ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="eob-show-card">
                        <div class="intern-section-head">
                            <div>
                                <div class="intern-section-title">Educational Details</div>
                                <div class="intern-section-subtitle">Academic qualifications submitted by the intern.</div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="table-responsive intern-show-table-wrap">
                                <table class="table intern-show-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Qualification</th>
                                            <th>Institution</th>
                                            <th>Year</th>
                                            <th>Percentage</th>
                                            <th>Specialization</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($form->educationalDetails as $row)
                                            <tr>
                                                <td>{{ $row->qualification ?: 'N/A' }}</td>
                                                <td>{{ $row->institution_name ?: 'N/A' }}</td>
                                                <td>{{ $row->year_of_passing ?: 'N/A' }}</td>
                                                <td>{{ $row->percentage ?: 'N/A' }}</td>
                                                <td>{{ $row->specialization ?: 'N/A' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-secondary">No educational details added.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="eob-show-card">
                        <div class="intern-section-head">
                            <div>
                                <div class="intern-section-title">Employment Details</div>
                                <div class="intern-section-subtitle">Previous work experience and compensation records.</div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="table-responsive intern-show-table-wrap">
                                <table class="table intern-show-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Organisation</th>
                                            <th>Designation</th>
                                            <th>Period From</th>
                                            <th>Period To</th>
                                            <th>Annual CTC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($form->employmentDetails as $row)
                                            <tr>
                                                <td>{{ $row->organisation ?: 'N/A' }}</td>
                                                <td>{{ $row->designation ?: 'N/A' }}</td>
                                                <td>{{ optional($row->period_from)->format('d M Y') ?: 'N/A' }}</td>
                                                <td>{{ optional($row->period_to)->format('d M Y') ?: 'N/A' }}</td>
                                                <td>{{ $row->annual_ctc ?: 'N/A' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-secondary">No employment history added.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="eob-show-card">
                        <div class="intern-section-head">
                            <div>
                                <div class="intern-section-title">Family Details</div>
                                <div class="intern-section-subtitle">Family and dependent contact details.</div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="table-responsive intern-show-table-wrap">
                                <table class="table intern-show-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Relation</th>
                                            <th>Occupation</th>
                                            <th>Date of Birth</th>
                                            <th>Mobile No</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($form->familyDetails as $row)
                                            <tr>
                                                <td>{{ $row->name ?: 'N/A' }}</td>
                                                <td>{{ $row->relation ?: 'N/A' }}</td>
                                                <td>{{ $row->occupation ?: 'N/A' }}</td>
                                                <td>{{ optional($row->date_of_birth)->format('d M Y') ?: 'N/A' }}</td>
                                                <td>{{ $row->mobile_no ?: 'N/A' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-secondary">No family details added.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="eob-show-card">
                        <div class="intern-section-head">
                            <div>
                                <div class="intern-section-title">Document Downloads</div>
                                <div class="intern-section-subtitle">Uploaded proofs and signed assets in one place.</div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="eob-doc-list intern-doc-list">
                                @foreach($documentLabels as $field => $label)
                                    <div class="eob-doc-card">
                                        <div class="eob-doc-title">{{ $label }}</div>
                                        <div class="eob-doc-sub">
                                            {{ $form->documents?->{$field} ? 'File available for download.' : 'Not uploaded yet.' }}
                                        </div>
                                        <div class="eob-doc-actions">
                                            @if($form->documents?->{$field})
                                                <a href="{{ asset('storage/' . $form->documents->{$field}) }}" target="_blank" class="btn btn-sm btn-outline-primary">Download</a>
                                            @else
                                                <span class="intern-status-pill is-muted">Not uploaded</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
