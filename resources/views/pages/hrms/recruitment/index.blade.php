@extends('layouts.app')

@section('title', 'Recruitment')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.recruitment.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Recruitment</div>
            <div class="eob-breadcrumb">HRMS > Recruitment</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('recruitment.create') }}" class="eob-btn eob-btn-primary">Add Candidate</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div class="rec-stats">
            <a href="{{ route('recruitment.index', request()->except('bucket', 'page')) }}" class="rec-stat {{ request('bucket') === null && request('status') === null ? 'is-active' : '' }}">
                <div class="rec-stat-label">All Candidates</div>
                <div class="rec-stat-value">{{ $counts['all'] }}</div>
            </a>
            <a href="{{ route('recruitment.index', array_merge(request()->except('page'), ['bucket' => 'active'])) }}" class="rec-stat {{ request('bucket') === 'active' ? 'is-active' : '' }}">
                <div class="rec-stat-label">Active Pipeline</div>
                <div class="rec-stat-value">{{ $counts['active'] }}</div>
            </a>
            <a href="{{ route('recruitment.index', array_merge(request()->except('page'), ['bucket' => 'selected'])) }}" class="rec-stat {{ request('bucket') === 'selected' ? 'is-active' : '' }}">
                <div class="rec-stat-label">Selected Bucket</div>
                <div class="rec-stat-value">{{ $counts['selected'] }}</div>
            </a>
            <a href="{{ route('recruitment.index', array_merge(request()->except('page'), ['bucket' => 'rejected'])) }}" class="rec-stat {{ request('bucket') === 'rejected' ? 'is-active' : '' }}">
                <div class="rec-stat-label">Rejected Bucket</div>
                <div class="rec-stat-value">{{ $counts['rejected'] }}</div>
            </a>
        </div>

        <div class="eob-filter-card">
            <form method="GET" action="{{ route('recruitment.index') }}" class="eob-filter-form">
                @if(request('bucket'))
                    <input type="hidden" name="bucket" value="{{ request('bucket') }}">
                @endif
                <div class="eob-field" style="min-width: 200px;">
                    <label class="eob-label">Search</label>
                    <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Name, mobile, email, job, location">
                </div>
                <div class="eob-field" style="max-width: 180px; min-width: 130px;">
                    <label class="eob-label">Status</label>
                    <select name="status" class="eob-select">
                        <option value="">All Status</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-field" style="max-width: 160px; min-width: 130px;">
                    <label class="eob-label">From Date</label>
                    <input type="date" name="date_from" class="eob-input" value="{{ request('date_from') }}">
                </div>
                <div class="eob-field" style="max-width: 160px; min-width: 130px;">
                    <label class="eob-label">To Date</label>
                    <input type="date" name="date_to" class="eob-input" value="{{ request('date_to') }}">
                </div>
                <div class="eob-actions">
                    <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                    @if(request()->hasAny(['search', 'status', 'bucket', 'date_from', 'date_to']))
                        <a href="{{ route('recruitment.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="eob-table-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Candidate Pipeline</div>
                    <div class="eob-card-sub">
                        @if(request('date_from') && request('date_to'))
                            Showing candidates from <strong>{{ \Carbon\Carbon::parse(request('date_from'))->format('d M Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse(request('date_to'))->format('d M Y') }}</strong>.
                        @elseif(request('date_from'))
                            Showing candidates from <strong>{{ \Carbon\Carbon::parse(request('date_from'))->format('d M Y') }}</strong> onwards.
                        @elseif(request('date_to'))
                            Showing candidates up to <strong>{{ \Carbon\Carbon::parse(request('date_to'))->format('d M Y') }}</strong>.
                        @else
                            Track applicants from first contact through interview, selection, or rejection.
                        @endif
                    </div>
                </div>
                <div class="eob-results">{{ $candidates->total() }} candidate(s)</div>
            </div>

            @if($candidates->isEmpty())
                <div class="eob-empty">No recruitment candidates found.</div>
            @else
                <div style="overflow-x:auto;">
                    <table class="eob-list-table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Applied For</th>
                                <th>Contact</th>
                                <th>HR Activity</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $candidate)
                                <tr>
                                    <td>
                                        <div class="eob-cell-title">{{ $candidate->name }}</div>
                                        <div class="eob-cell-sub">{{ $candidate->candidate_no }}</div>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $candidate->job_title }}</div>
                                        <div class="eob-cell-sub">{{ $candidate->source ?: 'Source not added' }}</div>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $candidate->mobile_number }}</div>
                                        <div class="eob-cell-sub">{{ $candidate->email ?: 'Email not added' }}</div>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $candidate->call_updates_count }} call update(s)</div>
                                        <div class="eob-cell-sub">{{ $candidate->interviews_count }} interview(s)</div>
                                    </td>
                                    <td>
                                        <span class="rec-chip rec-chip-{{ $candidate->status }}">{{ $candidate->status_label }}</span>
                                        @if($candidate->status === \App\Models\RecruitmentCandidate::STATUS_INTERVIEW_SCHEDULED && $candidate->latestInterview?->scheduled_at)
                                            <div class="eob-cell-sub" style="margin-top: 4px; font-weight: 600; color: #1d4ed8; font-size: 11px; display: flex; align-items: center; gap: 4px;">
                                                <i class="bi bi-calendar-event" style="color: #fe5f04;"></i>
                                                {{ $candidate->latestInterview->scheduled_at->format('d M Y, h:i A') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $candidate->created_at->format('d M Y') }}</td>
                                    <td>
                                        <details class="eob-table-dropdown">
                                            <summary class="eob-table-dropdown-trigger">Actions</summary>
                                            <div class="eob-table-dropdown-menu">
                                                <a href="{{ route('recruitment.show', $candidate) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                                <a href="{{ route('recruitment.edit', $candidate) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($candidates->hasPages())
                    @include('partials.table-pagination', ['paginator' => $candidates])
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
