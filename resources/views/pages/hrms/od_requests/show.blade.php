@extends('layouts.app')

@section('title', 'OD Request Details')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .od-show-layout { display:grid; grid-template-columns:360px minmax(0, 1fr); gap:18px; align-items:start; }
        .od-summary-list { display:flex; flex-direction:column; gap:12px; }
        .od-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .od-status-pending { background:#fff7ed; color:#c2410c; }
        .od-status-approved { background:#f0fdf4; color:#15803d; }
        .od-status-rejected { background:#fef2f2; color:#b91c1c; }
        .od-status-skipped { background:#f4f4f5; color:#71717a; }
        .od-timeline { display:flex; flex-direction:column; gap:14px; }
        .od-step { border:1px solid #e1dee3; border-radius:16px; background:#fff; overflow:hidden; }
        .od-step-head { display:flex; justify-content:space-between; gap:14px; padding:16px 18px; border-bottom:1px solid #f0eef2; }
        .od-step-title { font-size:15px; font-weight:800; color:#121212; }
        .od-step-sub { margin-top:4px; font-size:12px; color:#7c7c7c; }
        .od-step-body { padding:16px 18px; }
        .od-action-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:14px; }
        .od-action-form { padding:12px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
        .od-current { border-color:#fdba74; box-shadow:0 14px 30px rgba(254,95,4,.08); }
        .od-muted { color:#7c7c7c; font-size:12px; line-height:1.5; }
        @media (max-width: 980px) {
            .od-show-layout { grid-template-columns:1fr; }
            .od-action-grid { grid-template-columns:1fr; }
        }

        /* Mail Process / Progress Bar Overlay */
        .od-process-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999999;
            animation: odFadeInOverlay 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes odFadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .od-process-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px 36px;
            width: 460px;
            max-width: calc(100vw - 32px);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
            text-align: center;
            transform: scale(0.95);
            animation: odScaleInCard 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes odScaleInCard {
            to { transform: scale(1); }
        }
        .od-process-icon-wrap {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .od-process-spinner {
            position: absolute;
            inset: 0;
            border: 3.5px solid #ffe6d5;
            border-top-color: #fe5f04;
            border-radius: 50%;
            animation: odSpinOverlay 0.9s linear infinite;
        }
        @keyframes odSpinOverlay {
            to { transform: rotate(360deg); }
        }
        .od-process-icon {
            font-size: 32px;
            color: #fe5f04;
            animation: odPulseIcon 1.5s ease-in-out infinite alternate;
        }
        @keyframes odPulseIcon {
            from { transform: scale(0.88); opacity: 0.85; }
            to { transform: scale(1.12); opacity: 1; }
        }
        .od-process-title {
            font-size: 19px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 6px;
        }
        .od-process-subtitle {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 24px;
            line-height: 1.5;
        }
        .od-progress-wrapper { width: 100%; }
        .od-progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }
        .od-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 100%);
            border-radius: 999px;
            transition: width 0.3s ease;
        }
        .od-progress-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">OD Request Details</div>
            <div class="eob-breadcrumb">HRMS > OD Requests > #{{ $odRequest->id }}</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('od-requests.index') }}" class="eob-btn eob-btn-ghost">Back</a>
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

        <div class="od-show-layout">
            {{-- Left: Request Summary --}}
            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Request Summary</div>
                        <div class="eob-card-sub">Current status and permission dates.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="od-summary-list">
                        <div class="eob-show-item">
                            <div class="eob-show-label">Employee</div>
                            <div class="eob-show-value">{{ $odRequest->employee?->name ?: $odRequest->user?->name }}</div>
                            <div class="od-muted">{{ $odRequest->employee?->employee_id ?: $odRequest->user?->email }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">OD Date Range</div>
                            <div class="eob-show-value">{{ $odRequest->from_date->format('d M Y') }} - {{ $odRequest->to_date->format('d M Y') }}</div>
                            <div class="od-muted">{{ $odRequest->total_days }} day(s)</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Status</div>
                            <div class="eob-show-value">
                                <span class="od-status od-status-{{ $odRequest->status }}">{{ $odRequest->status }}</span>
                            </div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Submitted On</div>
                            <div class="eob-show-value">{{ optional($odRequest->submitted_at)->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Remarks / Purpose</div>
                            <div class="eob-show-value" style="font-size: 13px; line-height: 1.5; color: #374151; white-space: pre-line;">{{ $odRequest->reason }}</div>
                        </div>
                        @if($odRequest->status === 'approved')
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px; font-size: 12px; color: #166534; line-height: 1.5;">
                                <strong>Attendance Updated:</strong> Marked as <strong>OD (Present)</strong> in Daily Attendance for all dates in this range.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: Approval Flow Timeline --}}
            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Approval Hierarchy Flow</div>
                        <div class="eob-card-sub">Follow each stage of manager review and decision.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="od-timeline">
                        @foreach($odRequest->approvals as $approval)
                            @php
                                $isCurrent = $odRequest->status === 'pending' && $odRequest->current_step === $approval->step_key;
                                $canAct = $approvalActions[$approval->id] ?? false;
                            @endphp
                            <div class="od-step {{ $isCurrent ? 'od-current' : '' }}">
                                <div class="od-step-head">
                                    <div>
                                        <div class="od-step-title">{{ $approval->step_name }}</div>
                                        <div class="od-step-sub">Assigned to: <strong>{{ $approval->approver?->name }}</strong></div>
                                    </div>
                                    <span class="od-status od-status-{{ $approval->status }}">{{ $approval->status }}</span>
                                </div>

                                <div class="od-step-body">
                                    @if($approval->actioned_at)
                                        <div class="od-muted">
                                            Actioned by <strong>{{ $approval->actionedBy?->name ?: $approval->approver?->name }}</strong> on {{ $approval->actioned_at->format('d M Y, h:i A') }}
                                        </div>
                                    @elseif($isCurrent)
                                        <div class="od-muted" style="color: #ea580c; font-weight: 700;">Currently waiting for action at this level.</div>
                                    @else
                                        <div class="od-muted">Waiting for previous stage to complete.</div>
                                    @endif

                                    @if($approval->remarks)
                                        <div style="margin-top: 8px; font-size: 12px; color: #4b5563; background: #f9fafb; border-radius: 8px; padding: 8px 12px;">
                                            <strong>Remarks:</strong> {{ $approval->remarks }}
                                        </div>
                                    @endif

                                    {{-- Approval Action Forms for current approver --}}
                                    @if($canAct)
                                        <div class="od-action-grid">
                                            {{-- Approve Form --}}
                                            <form method="POST" action="{{ route('od-requests.approve', [$odRequest, $approval]) }}" class="od-action-form" onsubmit="return handleApprovalSubmit(this, 'Approve');">
                                                @csrf
                                                @method('PATCH')
                                                <div class="eob-group">
                                                    <label class="eob-label" style="font-size: 11px;">Optional Approval Remarks</label>
                                                    <input type="text" name="remarks" class="eob-input" placeholder="Approved notes..." style="padding: 8px 10px; font-size: 12px;">
                                                </div>
                                                <button type="submit" class="eob-btn eob-btn-primary eob-btn-sm" style="margin-top: 10px; width: 100%; background: #059669; border-color: #059669;">
                                                    Approve OD Request
                                                </button>
                                            </form>

                                            {{-- Reject Form --}}
                                            <form method="POST" action="{{ route('od-requests.reject', [$odRequest, $approval]) }}" class="od-action-form" onsubmit="return handleApprovalSubmit(this, 'Reject');">
                                                @csrf
                                                @method('PATCH')
                                                <div class="eob-group">
                                                    <label class="eob-label" style="font-size: 11px;">Rejection Reason <span class="eob-label-required">*</span></label>
                                                    <input type="text" name="remarks" required class="eob-input" placeholder="Explain reason for rejection..." style="padding: 8px 10px; font-size: 12px;">
                                                </div>
                                                <button type="submit" class="eob-btn eob-btn-danger eob-btn-sm" style="margin-top: 10px; width: 100%; background: #dc2626; color: #fff; border-color: #dc2626;">
                                                    Reject OD Request
                                                </button>
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

{{-- Mail Process / Progress Bar Overlay --}}
<div id="odProcessOverlay" class="od-process-overlay">
    <div class="od-process-card">
        <div class="od-process-icon-wrap">
            <div class="od-process-spinner"></div>
            <svg class="od-process-icon" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h4 id="odProcessTitle" class="od-process-title">Sending Email & Processing...</h4>
        <p id="odProcessSubtitle" class="od-process-subtitle">Please wait while the OD request action is being processed and email notification is sent...</p>

        <div class="od-progress-wrapper">
            <div class="od-progress-bar">
                <div id="odProgressFill" class="od-progress-fill"></div>
            </div>
            <div class="od-progress-status">
                <span id="odProgressText">Preparing email notification...</span>
                <span id="odProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let odProcessProgressInterval = null;

function handleApprovalSubmit(form, actionType) {
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    var title = actionType === 'Approve' ? 'Approving OD Request & Sending Email...' : 'Rejecting OD Request & Sending Email...';
    var subtitle = 'Please wait while the notification is being sent to the employee...';
    showOdProcessOverlay(title, subtitle);
    return true;
}

function showOdProcessOverlay(title, subtitle) {
    if (title) document.getElementById('odProcessTitle').innerText = title;
    if (subtitle) document.getElementById('odProcessSubtitle').innerText = subtitle;

    const overlay = document.getElementById('odProcessOverlay');
    const fill = document.getElementById('odProgressFill');
    const percentText = document.getElementById('odProgressPercent');
    const statusText = document.getElementById('odProgressText');

    overlay.style.display = 'flex';

    let currentProgress = 5;
    fill.style.width = currentProgress + '%';
    percentText.innerText = currentProgress + '%';
    statusText.innerText = 'Connecting to server...';

    if (odProcessProgressInterval) clearInterval(odProcessProgressInterval);

    odProcessProgressInterval = setInterval(function() {
        if (currentProgress < 30) {
            currentProgress += Math.floor(Math.random() * 8) + 4;
            statusText.innerText = 'Processing approval action...';
        } else if (currentProgress < 70) {
            currentProgress += Math.floor(Math.random() * 6) + 3;
            statusText.innerText = 'Sending notification email via SMTP...';
        } else if (currentProgress < 92) {
            currentProgress += Math.floor(Math.random() * 3) + 1;
            statusText.innerText = 'Finalizing OD record...';
        }

        if (currentProgress > 94) {
            currentProgress = 94;
        }

        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
    }, 300);
}
</script>
@endpush
@endsection
