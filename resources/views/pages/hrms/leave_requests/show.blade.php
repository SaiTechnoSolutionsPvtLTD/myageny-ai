@extends('layouts.app')

@section('title', 'Leave Request Details')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .lr-show-layout { display:grid; grid-template-columns:360px minmax(0, 1fr); gap:18px; align-items:start; }
        .lr-summary-list { display:flex; flex-direction:column; gap:12px; }
        .lr-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .lr-status-pending { background:#fff7ed; color:#c2410c; }
        .lr-status-approved { background:#f0fdf4; color:#15803d; }
        .lr-status-rejected { background:#fef2f2; color:#b91c1c; }
        .lr-status-skipped { background:#f4f4f5; color:#71717a; }
        .lr-timeline { display:flex; flex-direction:column; gap:14px; }
        .lr-step { border:1px solid #e1dee3; border-radius:16px; background:#fff; overflow:hidden; }
        .lr-step-head { display:flex; justify-content:space-between; gap:14px; padding:16px 18px; border-bottom:1px solid #f0eef2; }
        .lr-step-title { font-size:15px; font-weight:800; color:#121212; }
        .lr-step-sub { margin-top:4px; font-size:12px; color:#7c7c7c; }
        .lr-step-body { padding:16px 18px; }
        .lr-action-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:14px; }
        .lr-action-form { padding:12px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
        .lr-current { border-color:#fdba74; box-shadow:0 14px 30px rgba(254,95,4,.08); }
        .lr-muted { color:#7c7c7c; font-size:12px; line-height:1.5; }
        @media (max-width: 980px) {
            .lr-show-layout { grid-template-columns:1fr; }
            .lr-action-grid { grid-template-columns:1fr; }
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Leave Request Details</div>
            <div class="eob-breadcrumb">HRMS > Leave Requests > #{{ $leaveRequest->id }}</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('leave-requests.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please check the remarks field and try again.</div>
        @endif

        <div class="lr-show-layout">
            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Request Summary</div>
                        <div class="eob-card-sub">Current status and leave details.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="lr-summary-list">
                        <div class="eob-show-item">
                            <div class="eob-show-label">Employee</div>
                            <div class="eob-show-value">{{ $leaveRequest->employee?->name ?: $leaveRequest->user?->name }}</div>
                            <div class="lr-muted">{{ $leaveRequest->employee?->employee_id ?: $leaveRequest->user?->email }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Leave Type</div>
                            <div class="eob-show-value">{{ $leaveRequest->leaveType?->name }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Dates</div>
                            <div class="eob-show-value">{{ $leaveRequest->start_date->format('d M Y') }} - {{ $leaveRequest->end_date->format('d M Y') }}</div>
                            <div class="lr-muted">{{ $leaveRequest->total_days }} day(s)</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Status</div>
                            <div class="eob-show-value"><span class="lr-status lr-status-{{ $leaveRequest->status }}">{{ $leaveRequest->status }}</span></div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Reason</div>
                            <div class="lr-muted">{{ $leaveRequest->reason }}</div>
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
                    <div class="lr-timeline">
                        @foreach($leaveRequest->approvals as $approval)
                            @php
                                $isCurrent = $leaveRequest->current_step === $approval->step_key && $leaveRequest->status === 'pending';
                                $canAct = (bool) ($approvalActions[$approval->id] ?? false);
                            @endphp
                            <div class="lr-step {{ $isCurrent ? 'lr-current' : '' }}">
                                <div class="lr-step-head">
                                    <div>
                                        <div class="lr-step-title">{{ $approval->step_order }}. {{ $approval->step_name }}</div>
                                        <div class="lr-step-sub">
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
                                    <div><span class="lr-status lr-status-{{ $approval->status }}">{{ $approval->status }}</span></div>
                                </div>

                                <div class="lr-step-body">
                                    <div class="lr-muted">
                                        Assigned approver: {{ $approval->approver?->name }} · {{ $approval->approver?->email }}
                                    </div>

                                    @if($approval->remarks)
                                        <div class="eob-show-item" style="margin-top:12px;">
                                            <div class="eob-show-label">Remarks</div>
                                            <div class="lr-muted">{{ $approval->remarks }}</div>
                                        </div>
                                    @endif

                                    @if($canAct)
                                        <div class="lr-action-grid">
                                            <form method="POST" action="{{ route('leave-requests.approve', [$leaveRequest, $approval]) }}" class="lr-action-form">
                                                @csrf
                                                @method('PATCH')
                                                <label class="eob-label">Approve Remarks</label>
                                                <textarea name="remarks" class="eob-textarea" placeholder="Optional remarks"></textarea>
                                                <button type="submit" class="eob-btn eob-btn-primary" style="margin-top:10px;">Approve</button>
                                            </form>

                                            <form method="POST" action="{{ route('leave-requests.reject', [$leaveRequest, $approval]) }}" class="lr-action-form" onsubmit="return confirm('Reject this leave request?')">
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
