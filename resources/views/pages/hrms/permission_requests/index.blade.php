@extends('layouts.app')

@section('title', 'Permission Requests')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .pr-page { display:flex; flex-direction:column; gap:18px; }
        .pr-summary-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
        .pr-summary-card { background:linear-gradient(135deg, #f7fbff 0%, #ffffff 100%); border:1px solid #dce9f8; border-radius:18px; padding:18px; box-shadow:0 14px 28px rgba(18, 18, 18, 0.04); }
        .pr-summary-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#355e8b; }
        .pr-summary-value { margin-top:10px; font-size:28px; font-weight:800; color:#121212; line-height:1; }
        .pr-summary-sub { margin-top:8px; font-size:12px; color:#7a7a7a; line-height:1.5; }
        .pr-nav { display:flex; flex-wrap:wrap; gap:10px; }
        .pr-nav-link { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; border:1px solid #d7e4f1; background:#fff; color:#4a6685; text-decoration:none; font-size:12px; font-weight:800; }
        .pr-nav-link:hover { background:#eff6ff; border-color:#93c5fd; color:#1d4ed8; }
        .pr-nav-count { display:inline-flex; align-items:center; justify-content:center; min-width:24px; height:24px; padding:0 7px; border-radius:999px; background:#edf4fb; color:#365b84; font-size:11px; }
        .pr-grid { display:grid; grid-template-columns:minmax(0, 1.3fr) minmax(0, 1fr); gap:16px; align-items:start; }
        .pr-grid .pr-section-card:first-child { grid-column:1 / -1; }
        .pr-section-card { scroll-margin-top:24px; }
        .pr-section-head { padding:18px 22px; border-bottom:1px solid #f0eef2; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
        .pr-section-title { font-size:16px; font-weight:800; color:#121212; }
        .pr-section-sub { margin-top:4px; font-size:12px; color:#8c8c8c; line-height:1.5; }
        .pr-section-badge { display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .pr-table-shell { padding:0 22px 22px; }
        .pr-table-scroll { overflow:auto; max-height:420px; border:1px solid #e8eef5; border-radius:16px; }
        .pr-table-scroll .eob-list-table thead th { position:sticky; top:0; z-index:1; background:#f8fbff; }
        .pr-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .pr-status-pending { background:#fff7ed; color:#c2410c; }
        .pr-status-approved { background:#f0fdf4; color:#15803d; }
        .pr-status-rejected { background:#fef2f2; color:#b91c1c; }
        .pr-status-skipped { background:#f4f4f5; color:#71717a; }
        .pr-pill { display:inline-flex; align-items:center; padding:4px 9px; border-radius:999px; background:#eef4ff; color:#3355aa; font-size:11px; font-weight:800; }
        .pr-meta-stack { display:flex; flex-direction:column; gap:2px; }
        .pr-empty { padding:28px; text-align:center; color:#9e9e9e; font-size:13px; }
        @media (max-width: 1100px) {
            .pr-summary-grid { grid-template-columns:1fr; }
            .pr-grid { grid-template-columns:1fr; }
            .pr-grid .pr-section-card:first-child { grid-column:auto; }
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Permission Requests</div>
            <div class="eob-breadcrumb">HRMS > Permission Requests</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('permission-requests.create') }}" class="eob-btn eob-btn-primary">New Permission Request</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div class="pr-page">
            <div class="pr-summary-grid">
                <div class="pr-summary-card">
                    <div class="pr-summary-label">Waiting For Me</div>
                    <div class="pr-summary-value">{{ $pendingApprovals->count() }}</div>
                    <div class="pr-summary-sub">Permission approvals currently sitting in your queue.</div>
                </div>
                <div class="pr-summary-card">
                    <div class="pr-summary-label">My Requests</div>
                    <div class="pr-summary-value">{{ $permissionRequests->total() }}</div>
                    <div class="pr-summary-sub">All permission requests you have raised across dates and slots.</div>
                </div>
                <div class="pr-summary-card">
                    <div class="pr-summary-label">Recent Decisions</div>
                    <div class="pr-summary-value">{{ $handledApprovals->count() }}</div>
                    <div class="pr-summary-sub">Permission decisions you completed recently.</div>
                </div>
            </div>

            <div class="pr-nav">
                <a href="#permission-approvals" class="pr-nav-link">Waiting For My Approval <span class="pr-nav-count">{{ $pendingApprovals->count() }}</span></a>
                <a href="#permission-my-requests" class="pr-nav-link">My Permission Requests <span class="pr-nav-count">{{ $permissionRequests->total() }}</span></a>
                <a href="#permission-decisions" class="pr-nav-link">My Recent Decisions <span class="pr-nav-count">{{ $handledApprovals->count() }}</span></a>
            </div>

            <div class="pr-grid">
            <div id="permission-approvals" class="eob-table-card pr-section-card">
                <div class="pr-section-head">
                    <div>
                        <div class="pr-section-title">Waiting For My Approval</div>
                        <div class="pr-section-sub">Permission requests currently assigned to your hierarchy stage.</div>
                    </div>
                    <div class="pr-section-badge">{{ $pendingApprovals->count() }} pending</div>
                </div>

                @if($pendingApprovals->isEmpty())
                    <div class="pr-empty">No permission approvals are waiting for you.</div>
                @else
                    <div class="pr-table-shell">
                    <div class="pr-table-scroll">
                        <table class="eob-list-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date / Time</th>
                                    <th>Stage</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingApprovals as $approval)
                                    <tr>
                                        <td>
                                            <div class="pr-meta-stack">
                                                <div class="eob-cell-title">{{ $approval->permissionRequest->employee?->name ?: $approval->permissionRequest->user?->name }}</div>
                                                <div class="eob-cell-sub">{{ $approval->permissionRequest->employee?->employee_id ?: $approval->permissionRequest->user?->email }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="pr-meta-stack">
                                                <div class="eob-cell-title">{{ $approval->permissionRequest->permission_date->format('d M Y') }}</div>
                                                <div class="eob-cell-sub">{{ \Carbon\Carbon::parse($approval->permissionRequest->from_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($approval->permissionRequest->to_time)->format('h:i A') }}</div>
                                            </div>
                                        </td>
                                        <td><span class="pr-pill">{{ $approval->step_name }}</span></td>
                                        <td>{{ optional($approval->permissionRequest->submitted_at)->format('d M Y') }}</td>
                                        <td><a href="{{ route('permission-requests.show', $approval->permissionRequest) }}" class="eob-btn eob-btn-primary eob-btn-sm">Review</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                @endif
            </div>

            <div id="permission-my-requests" class="eob-table-card pr-section-card">
                <div class="pr-section-head">
                    <div>
                        <div class="pr-section-title">My Permission Requests</div>
                        <div class="pr-section-sub">Track your permission request hierarchy approval status.</div>
                    </div>
                    <div class="pr-section-badge">{{ $permissionRequests->total() }} request(s)</div>
                </div>

                @if($permissionRequests->isEmpty())
                    <div class="pr-empty">No permission requests submitted yet.</div>
                @else
                    <div class="pr-table-shell">
                    <div class="pr-table-scroll">
                        <table class="eob-list-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Current Stage</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissionRequests as $permissionRequest)
                                    @php $currentApproval = $permissionRequest->approvals->firstWhere('step_key', $permissionRequest->current_step); @endphp
                                    <tr>
                                        <td>{{ $permissionRequest->permission_date->format('d M Y') }}</td>
                                        <td>
                                            <div class="pr-meta-stack">
                                                <div class="eob-cell-title">{{ \Carbon\Carbon::parse($permissionRequest->from_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($permissionRequest->to_time)->format('h:i A') }}</div>
                                                <div class="eob-cell-sub">{{ $permissionRequest->total_minutes }} minutes</div>
                                            </div>
                                        </td>
                                        <td><span class="pr-status pr-status-{{ $permissionRequest->status }}">{{ $permissionRequest->status }}</span></td>
                                        <td>
                                            @if($permissionRequest->status === 'pending')
                                                {{ $currentApproval?->step_name ?: 'Waiting' }}
                                            @elseif($permissionRequest->status === 'approved')
                                                Completed
                                            @else
                                                Rejected
                                            @endif
                                        </td>
                                        <td>{{ optional($permissionRequest->submitted_at)->format('d M Y') }}</td>
                                        <td><a href="{{ route('permission-requests.show', $permissionRequest) }}" class="eob-icon-btn" title="View"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>

                    @if($permissionRequests->hasPages())
                        @include('partials.table-pagination', ['paginator' => $permissionRequests])
                    @endif
                @endif
            </div>

            <div id="permission-decisions" class="eob-table-card pr-section-card">
                <div class="pr-section-head">
                    <div>
                        <div class="pr-section-title">My Recent Decisions</div>
                        <div class="pr-section-sub">Permission approvals or rejections you completed recently.</div>
                    </div>
                    <div class="pr-section-badge">{{ $handledApprovals->count() }} item(s)</div>
                </div>

                @if($handledApprovals->isEmpty())
                    <div class="pr-empty">No permission decisions recorded yet.</div>
                @else
                    <div class="pr-table-shell">
                    <div class="pr-table-scroll">
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
                                            <div class="pr-meta-stack">
                                                <div class="eob-cell-title">{{ $approval->permissionRequest->employee?->name ?: $approval->permissionRequest->user?->name }}</div>
                                                <div class="eob-cell-sub">{{ $approval->permissionRequest->permission_date->format('d M Y') }}</div>
                                            </div>
                                        </td>
                                        <td>{{ $approval->step_name }}</td>
                                        <td><span class="pr-status pr-status-{{ $approval->status }}">{{ $approval->status }}</span></td>
                                        <td>{{ optional($approval->actioned_at)->format('d M Y h:i A') }}</td>
                                        <td><a href="{{ route('permission-requests.show', $approval->permissionRequest) }}" class="eob-icon-btn" title="View"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                @endif
            </div>
            </div>
        </div>
    </div>
</div>
@endsection
