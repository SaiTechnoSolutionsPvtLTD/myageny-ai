@extends('layouts.app')

@section('title', 'Leave Requests')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .lr-page { display:flex; flex-direction:column; gap:18px; }
        .lr-summary-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
        .lr-summary-grid.lr-summary-grid-single { grid-template-columns:minmax(0, 1fr); }
        .lr-summary-card { background:linear-gradient(135deg, #fffaf5 0%, #ffffff 100%); border:1px solid #f1e5d7; border-radius:18px; padding:18px; box-shadow:0 14px 28px rgba(18, 18, 18, 0.04); }
        .lr-summary-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#9a6b39; }
        .lr-summary-value { margin-top:10px; font-size:28px; font-weight:800; color:#121212; line-height:1; }
        .lr-summary-sub { margin-top:8px; font-size:12px; color:#7a7a7a; line-height:1.5; }
        .lr-nav { display:flex; flex-wrap:wrap; gap:10px; }
        .lr-nav-link { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; border:1px solid #eadfce; background:#fff; color:#6b5b46; text-decoration:none; font-size:12px; font-weight:800; }
        .lr-nav-link:hover { background:#fff7ed; border-color:#fdba74; color:#c2410c; }
        .lr-nav-count { display:inline-flex; align-items:center; justify-content:center; min-width:24px; height:24px; padding:0 7px; border-radius:999px; background:#f6efe7; color:#8a5b2f; font-size:11px; }
        .lr-grid { display:grid; grid-template-columns:minmax(0, 1.3fr) minmax(0, 1fr); gap:16px; align-items:start; }
        .lr-grid .lr-section-card:first-child { grid-column:1 / -1; }
        .lr-section-card { scroll-margin-top:24px; }
        .lr-section-head { padding:18px 22px; border-bottom:1px solid #f0eef2; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
        .lr-section-title { font-size:16px; font-weight:800; color:#121212; }
        .lr-section-sub { margin-top:4px; font-size:12px; color:#8c8c8c; line-height:1.5; }
        .lr-section-badge { display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; background:#fff7ed; color:#c2410c; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .lr-table-shell { padding:0 22px 22px; }
        .lr-table-scroll { overflow:auto; max-height:420px; border:1px solid #f3ede7; border-radius:16px; }
        .lr-table-scroll .eob-list-table thead th { position:sticky; top:0; z-index:1; background:#fcfaf8; }
        .lr-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .lr-status-pending { background:#fff7ed; color:#c2410c; }
        .lr-status-approved { background:#f0fdf4; color:#15803d; }
        .lr-status-rejected { background:#fef2f2; color:#b91c1c; }
        .lr-status-skipped { background:#f4f4f5; color:#71717a; }
        .lr-pill { display:inline-flex; align-items:center; padding:4px 9px; border-radius:999px; background:#eef4ff; color:#3355aa; font-size:11px; font-weight:800; }
        .lr-meta-stack { display:flex; flex-direction:column; gap:2px; }
        .lr-empty { padding:28px; text-align:center; color:#9e9e9e; font-size:13px; }
        @media (max-width: 1100px) {
            .lr-summary-grid { grid-template-columns:1fr; }
            .lr-grid { grid-template-columns:1fr; }
            .lr-grid .lr-section-card:first-child { grid-column:auto; }
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Leave Requests</div>
            <div class="eob-breadcrumb">HRMS > Leave Requests</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('leave-requests.create') }}" class="eob-btn eob-btn-primary">New Leave Request</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div class="lr-page">
            <div class="lr-summary-grid">
                @can('leaverequest.approve')
                <div class="lr-summary-card">
                    <div class="lr-summary-label">Waiting For Me</div>
                    <div class="lr-summary-value">{{ $pendingApprovals->count() }}</div>
                    <div class="lr-summary-sub">Leave approvals currently blocked at your stage.</div>
                </div>
                @endcan
                <div class="lr-summary-card">
                    <div class="lr-summary-label">My Requests</div>
                    <div class="lr-summary-value">{{ $leaveRequests->total() }}</div>
                    <div class="lr-summary-sub">Your leave requests with current approval status.</div>
                </div>
                @can('leaverequest.approve')
                <div class="lr-summary-card">
                    <div class="lr-summary-label">Recent Decisions</div>
                    <div class="lr-summary-value">{{ $handledApprovals->count() }}</div>
                    <div class="lr-summary-sub">Approvals or rejections you completed recently.</div>
                </div>
                @endcan
            </div>

            <div class="lr-nav">
                @can('leaverequest.approve')
                <a href="#leave-approvals" class="lr-nav-link">Waiting For My Approval <span class="lr-nav-count">{{ $pendingApprovals->count() }}</span></a>
                @endcan
                <a href="#leave-my-requests" class="lr-nav-link">My Leave Requests <span class="lr-nav-count">{{ $leaveRequests->total() }}</span></a>
                @can('leaverequest.approve')
                <a href="#leave-decisions" class="lr-nav-link">My Recent Decisions <span class="lr-nav-count">{{ $handledApprovals->count() }}</span></a>
                @endcan
            </div>

            <div class="lr-grid">
            @can('leaverequest.approve')
            <div id="leave-approvals" class="eob-table-card lr-section-card">
                <div class="lr-section-head">
                    <div>
                        <div class="lr-section-title">Waiting For My Approval</div>
                        <div class="lr-section-sub">Requests currently assigned to your approval stage.</div>
                    </div>
                    <div class="lr-section-badge">{{ $pendingApprovals->count() }} pending</div>
                </div>

                @if($pendingApprovals->isEmpty())
                    <div class="lr-empty">No leave approvals are waiting for you.</div>
                @else
                    <div class="lr-table-shell">
                    <div class="lr-table-scroll">
                        <table class="eob-list-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Dates</th>
                                    <th>Stage</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingApprovals as $approval)
                                    <tr>
                                        <td>
                                            <div class="lr-meta-stack">
                                                <div class="eob-cell-title">{{ $approval->leaveRequest->employee?->name ?: $approval->leaveRequest->user?->name }}</div>
                                                <div class="eob-cell-sub">{{ $approval->leaveRequest->employee?->employee_id ?: $approval->leaveRequest->user?->email }}</div>
                                            </div>
                                        </td>
                                        <td>{{ $approval->leaveRequest->leaveType?->name }}</td>
                                        <td>
                                            <div class="lr-meta-stack">
                                                <div class="eob-cell-title">{{ $approval->leaveRequest->start_date->format('d M Y') }} - {{ $approval->leaveRequest->end_date->format('d M Y') }}</div>
                                                <div class="eob-cell-sub">{{ $approval->leaveRequest->total_days }} day(s)</div>
                                            </div>
                                        </td>
                                        <td><span class="lr-pill">{{ $approval->step_name }}</span></td>
                                        <td>{{ optional($approval->leaveRequest->submitted_at)->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('leave-requests.show', $approval->leaveRequest) }}" class="eob-btn eob-btn-primary eob-btn-sm">Review</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                @endif
            </div>
            @endcan

            <div id="leave-my-requests" class="eob-table-card lr-section-card">
                <div class="lr-section-head">
                    <div>
                        <div class="lr-section-title">My Leave Requests</div>
                        <div class="lr-section-sub">Check whether your leave request is pending, approved, or rejected.</div>
                    </div>
                    <div class="lr-section-badge">{{ $leaveRequests->total() }} request(s)</div>
                </div>

                @if($leaveRequests->isEmpty())
                    <div class="lr-empty">No leave requests submitted yet.</div>
                @else
                    <div class="lr-table-shell">
                    <div class="lr-table-scroll">
                        <table class="eob-list-table">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th>Current Stage</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaveRequests as $leaveRequest)
                                    @php
                                        $currentApproval = $leaveRequest->approvals->firstWhere('step_key', $leaveRequest->current_step);
                                    @endphp
                                    <tr>
                                        <td>{{ $leaveRequest->leaveType?->name }}</td>
                                        <td>
                                            <div class="lr-meta-stack">
                                                <div class="eob-cell-title">{{ $leaveRequest->start_date->format('d M Y') }} - {{ $leaveRequest->end_date->format('d M Y') }}</div>
                                                <div class="eob-cell-sub">{{ $leaveRequest->total_days }} day(s)</div>
                                            </div>
                                        </td>
                                        <td><span class="lr-status lr-status-{{ $leaveRequest->status }}">{{ $leaveRequest->status }}</span></td>
                                        <td>
                                            @if($leaveRequest->status === 'pending')
                                                {{ $currentApproval?->step_name ?: 'Waiting' }}
                                            @elseif($leaveRequest->status === 'approved')
                                                Completed
                                            @else
                                                Rejected
                                            @endif
                                        </td>
                                        <td>{{ optional($leaveRequest->submitted_at)->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="eob-icon-btn" title="View Status"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>

                    @if($leaveRequests->hasPages())
                        @include('partials.table-pagination', ['paginator' => $leaveRequests])
                    @endif
                @endif
            </div>
             @can('leaverequest.approve')
            <div id="leave-decisions" class="eob-table-card lr-section-card">
                <div class="lr-section-head">
                    <div>
                        <div class="lr-section-title">My Recent Decisions</div>
                        <div class="lr-section-sub">Approvals or rejections you completed recently.</div>
                    </div>
                    <div class="lr-section-badge">{{ $handledApprovals->count() }} item(s)</div>
                </div>

                @if($handledApprovals->isEmpty())
                    <div class="lr-empty">No approval decisions recorded yet.</div>
                @else
                    <div class="lr-table-shell">
                    <div class="lr-table-scroll">
                        <table class="eob-list-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Stage</th>
                                    <th>Decision</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($handledApprovals as $approval)
                                    <tr>
                                        <td>
                                            <div class="lr-meta-stack">
                                                <div class="eob-cell-title">{{ $approval->leaveRequest->employee?->name ?: $approval->leaveRequest->user?->name }}</div>
                                                <div class="eob-cell-sub">{{ $approval->leaveRequest->leaveType?->name }}</div>
                                            </div>
                                        </td>
                                        <td>{{ $approval->step_name }}</td>
                                        <td><span class="lr-status lr-status-{{ $approval->status }}">{{ $approval->status }}</span></td>
                                        <td>{{ optional($approval->actioned_at)->format('d M Y h:i A') }}</td>
                                        <td>
                                            <a href="{{ route('leave-requests.show', $approval->leaveRequest) }}" class="eob-icon-btn" title="View"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                @endif
            </div>
            @endcan
            </div>
        </div>
    </div>
</div>
@endsection
