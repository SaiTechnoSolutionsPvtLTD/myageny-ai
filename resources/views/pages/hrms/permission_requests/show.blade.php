@extends('layouts.app')

@section('title', 'Permission Request Details')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .pr-show-layout { display:grid; grid-template-columns:360px minmax(0, 1fr); gap:18px; align-items:start; }
        .pr-summary-list { display:flex; flex-direction:column; gap:12px; }
        .pr-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .pr-status-pending { background:#fff7ed; color:#c2410c; }
        .pr-status-approved { background:#f0fdf4; color:#15803d; }
        .pr-status-rejected { background:#fef2f2; color:#b91c1c; }
        .pr-status-skipped { background:#f4f4f5; color:#71717a; }
        .pr-timeline { display:flex; flex-direction:column; gap:14px; }
        .pr-step { border:1px solid #e1dee3; border-radius:16px; background:#fff; overflow:hidden; }
        .pr-step-head { display:flex; justify-content:space-between; gap:14px; padding:16px 18px; border-bottom:1px solid #f0eef2; }
        .pr-step-title { font-size:15px; font-weight:800; color:#121212; }
        .pr-step-sub { margin-top:4px; font-size:12px; color:#7c7c7c; }
        .pr-step-body { padding:16px 18px; }
        .pr-action-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:14px; }
        .pr-action-form { padding:12px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
        .pr-current { border-color:#fdba74; box-shadow:0 14px 30px rgba(254,95,4,.08); }
        .pr-muted { color:#7c7c7c; font-size:12px; line-height:1.5; }
        @media (max-width: 980px) {
            .pr-show-layout { grid-template-columns:1fr; }
            .pr-action-grid { grid-template-columns:1fr; }
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Permission Request Details</div>
            <div class="eob-breadcrumb">HRMS > Permission Requests > #{{ $permissionRequest->id }}</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('permission-requests.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="eob-alert eob-alert-error">Please check the remarks field and try again.</div>
        @endif

        <div class="pr-show-layout">
            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Request Summary</div>
                        <div class="eob-card-sub">Current status and permission details.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="pr-summary-list">
                        <div class="eob-show-item">
                            <div class="eob-show-label">Employee</div>
                            <div class="eob-show-value">{{ $permissionRequest->employee?->name ?: $permissionRequest->user?->name }}</div>
                            <div class="pr-muted">{{ $permissionRequest->employee?->employee_id ?: $permissionRequest->user?->email }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Permission Date</div>
                            <div class="eob-show-value">{{ $permissionRequest->permission_date->format('d M Y') }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Permission Time</div>
                            <div class="eob-show-value">{{ \Carbon\Carbon::parse($permissionRequest->from_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($permissionRequest->to_time)->format('h:i A') }}</div>
                            <div class="pr-muted">{{ $permissionRequest->total_minutes }} minutes</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Status</div>
                            <div class="eob-show-value"><span class="pr-status pr-status-{{ $permissionRequest->status }}">{{ $permissionRequest->status }}</span></div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Reason</div>
                            <div class="pr-muted">{{ $permissionRequest->reason }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Approval Status</div>
                        <div class="eob-card-sub">Each mapped hierarchy level must approve before final approval.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="pr-timeline">
                        @foreach($permissionRequest->approvals as $approval)
                            @php
                                $isCurrent = $permissionRequest->current_step === $approval->step_key && $permissionRequest->status === 'pending';
                                $canAct = (bool) ($approvalActions[$approval->id] ?? false);
                            @endphp
                            <div class="pr-step {{ $isCurrent ? 'pr-current' : '' }}">
                                <div class="pr-step-head">
                                    <div>
                                        <div class="pr-step-title">{{ $approval->step_order }}. {{ $approval->step_name }}</div>
                                        <div class="pr-step-sub">
                                            @if($approval->actionedBy)
                                                {{ ucfirst($approval->status) }} by {{ $approval->actionedBy->name }} on {{ $approval->actioned_at?->format('d M Y h:i A') }}
                                            @elseif($isCurrent)
                                                Waiting for {{ $approval->approver?->name }}
                                            @elseif($approval->status === 'pending')
                                                Waiting for previous approval
                                            @else
                                                No action required
                                            @endif
                                        </div>
                                    </div>
                                    <div><span class="pr-status pr-status-{{ $approval->status }}">{{ $approval->status }}</span></div>
                                </div>

                                <div class="pr-step-body">
                                    <div class="pr-muted">Assigned approver: {{ $approval->approver?->name }} · {{ $approval->approver?->email }}</div>

                                    @if($approval->remarks)
                                        <div class="eob-show-item" style="margin-top:12px;">
                                            <div class="eob-show-label">Remarks</div>
                                            <div class="pr-muted">{{ $approval->remarks }}</div>
                                        </div>
                                    @endif

                                    @if($canAct)
                                        <div class="pr-action-grid">
                                            <form method="POST" action="{{ route('permission-requests.approve', [$permissionRequest, $approval]) }}" class="pr-action-form">
                                                @csrf
                                                @method('PATCH')
                                                <label class="eob-label">Approve Remarks</label>
                                                <textarea name="remarks" class="eob-textarea" placeholder="Optional remarks"></textarea>
                                                <button type="submit" class="eob-btn eob-btn-primary" style="margin-top:10px;">Approve</button>
                                            </form>

                                            <form method="POST" action="{{ route('permission-requests.reject', [$permissionRequest, $approval]) }}" class="pr-action-form" onsubmit="return confirm('Reject this permission request?')">
                                                @csrf
                                                @method('PATCH')
                                                <label class="eob-label">Reject Remarks</label>
                                                <textarea name="remarks" class="eob-textarea" placeholder="Reason for rejection"></textarea>
                                                <button type="submit" class="eob-btn eob-btn-danger" style="margin-top:10px;">Reject</button>
                                            </form>
                                        </div>
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
@endsection
