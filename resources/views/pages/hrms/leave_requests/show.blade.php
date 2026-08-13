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

        /* Confirmation Modals */
        .lr-confirm-modal {
            position: fixed;
            inset: 0;
            background: rgba(18, 24, 38, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 20000;
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .lr-confirm-modal.show {
            display: flex;
            opacity: 1;
        }
        .lr-confirm-card {
            background: #ffffff;
            border-radius: 20px;
            width: 450px;
            max-width: calc(100vw - 32px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transform: translateY(15px);
            transition: transform 0.25s ease;
        }
        .lr-confirm-modal.show .lr-confirm-card {
            transform: translateY(0);
        }
        .lr-confirm-head {
            padding: 18px 24px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
        }
        .lr-confirm-title {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lr-confirm-body {
            padding: 24px;
            text-align: center;
        }
        .lr-confirm-foot {
            padding: 16px 24px;
            border-top: 1px solid #f3f4f6;
            background: #fafafa;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Mail Process Progress Overlay */
        .lr-process-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999999;
            animation: lrFadeInOverlay 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes lrFadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .lr-process-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px 36px;
            width: 460px;
            max-width: calc(100vw - 32px);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
            text-align: center;
            transform: scale(0.95);
            animation: lrScaleInCard 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes lrScaleInCard {
            to { transform: scale(1); }
        }
        .lr-process-icon-wrap {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lr-process-spinner {
            position: absolute;
            inset: 0;
            border: 3.5px solid #ffe6d5;
            border-top-color: #fe5f04;
            border-radius: 50%;
            animation: lrSpinOverlay 0.9s linear infinite;
        }
        @keyframes lrSpinOverlay {
            to { transform: rotate(360deg); }
        }
        .lr-process-icon {
            font-size: 32px;
            color: #fe5f04;
            animation: lrPulseIcon 1.5s ease-in-out infinite alternate;
        }
        @keyframes lrPulseIcon {
            from { transform: scale(0.88); opacity: 0.85; }
            to { transform: scale(1.12); opacity: 1; }
        }
        .lr-process-title {
            font-size: 19px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 6px;
        }
        .lr-process-subtitle {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 24px;
            line-height: 1.5;
        }
        .lr-progress-wrapper { width: 100%; }
        .lr-progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }
        .lr-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 100%);
            border-radius: 999px;
            transition: width 0.25s ease;
        }
        .lr-progress-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #4b5563;
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
                                            <form id="approveForm_{{ $approval->id }}" method="POST" action="{{ route('leave-requests.approve', [$leaveRequest, $approval]) }}" class="lr-action-form">
                                                @csrf
                                                @method('PATCH')
                                                <label class="eob-label">Approve Remarks</label>
                                                <textarea name="remarks" class="eob-textarea" placeholder="Optional remarks"></textarea>
                                                <button type="button" class="eob-btn eob-btn-primary" style="margin-top:10px;" onclick="showConfirmApproveModal('{{ $approval->id }}')">Approve</button>
                                            </form>

                                            <form id="rejectForm_{{ $approval->id }}" method="POST" action="{{ route('leave-requests.reject', [$leaveRequest, $approval]) }}" class="lr-action-form">
                                                @csrf
                                                @method('PATCH')
                                                <label class="eob-label">Reject Remarks</label>
                                                <textarea name="remarks" class="eob-textarea" placeholder="Reason for rejection"></textarea>
                                                <button type="button" class="eob-btn eob-btn-danger" style="margin-top:10px;" onclick="showConfirmRejectModal('{{ $approval->id }}')">Reject</button>
                                            </form>
                                        </div>

                                        {{-- Confirm Approve Step Modal --}}
                                        <div id="confirmApproveModal_{{ $approval->id }}" class="lr-confirm-modal">
                                            <div class="lr-confirm-card">
                                                <div class="lr-confirm-head" style="background:#f0fdf4; border-bottom-color:#bbf7d0;">
                                                    <div class="lr-confirm-title" style="color:#15803d;">
                                                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                                        Confirm Leave Approval
                                                    </div>
                                                    <button class="eob-btn-ghost" style="border:none; background:none; font-size:18px; cursor:pointer;" onclick="closeConfirmApproveModal('{{ $approval->id }}')">✕</button>
                                                </div>
                                                <div class="lr-confirm-body">
                                                    <p style="font-size:15px; font-weight:800; color:#111827; margin-bottom:8px;">Are you sure you want to approve Step {{ $approval->step_order }}?</p>
                                                    <p style="font-size:13px; color:#6b7280; line-height:1.5;">An email notification will be sent to the next approver (or applicant if final step).</p>
                                                </div>
                                                <div class="lr-confirm-foot">
                                                    <button type="button" class="eob-btn eob-btn-ghost" onclick="closeConfirmApproveModal('{{ $approval->id }}')">Cancel</button>
                                                    <button type="button" class="eob-btn eob-btn-primary" style="background:#16a34a;" onclick="submitApproveForm('{{ $approval->id }}')">Yes, Approve Step</button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Confirm Reject Step Modal --}}
                                        <div id="confirmRejectModal_{{ $approval->id }}" class="lr-confirm-modal">
                                            <div class="lr-confirm-card">
                                                <div class="lr-confirm-head" style="background:#fef2f2; border-bottom-color:#fecaca;">
                                                    <div class="lr-confirm-title" style="color:#b91c1c;">
                                                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                        Confirm Leave Rejection
                                                    </div>
                                                    <button class="eob-btn-ghost" style="border:none; background:none; font-size:18px; cursor:pointer;" onclick="closeConfirmRejectModal('{{ $approval->id }}')">✕</button>
                                                </div>
                                                <div class="lr-confirm-body">
                                                    <p style="font-size:15px; font-weight:800; color:#111827; margin-bottom:8px;">Are you sure you want to reject this leave request?</p>
                                                    <p style="font-size:13px; color:#6b7280; line-height:1.5;">An email notification with your rejection remarks will be sent to the applicant.</p>
                                                </div>
                                                <div class="lr-confirm-foot">
                                                    <button type="button" class="eob-btn eob-btn-ghost" onclick="closeConfirmRejectModal('{{ $approval->id }}')">Cancel</button>
                                                    <button type="button" class="eob-btn eob-btn-danger" style="background:#dc2626;" onclick="submitRejectForm('{{ $approval->id }}')">Yes, Reject Leave</button>
                                                </div>
                                            </div>
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

{{-- Process Overlay Modal --}}
<div id="leaveProcessOverlay" class="lr-process-overlay">
    <div class="lr-process-card">
        <div class="lr-process-icon-wrap">
            <div class="lr-process-spinner"></div>
            <svg class="lr-process-icon" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <h4 id="leaveProcessTitle" class="lr-process-title">Sending Email & Processing...</h4>
        <p id="leaveProcessSubtitle" class="lr-process-subtitle">Please wait while the leave approval action is being processed and email notification is sent...</p>

        <div class="lr-progress-wrapper">
            <div class="lr-progress-bar">
                <div id="leaveProgressFill" class="lr-progress-fill"></div>
            </div>
            <div class="lr-progress-status">
                <span id="leaveProgressText">Preparing email notification...</span>
                <span id="leaveProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showConfirmApproveModal(approvalId) {
        document.getElementById('confirmApproveModal_' + approvalId).classList.add('show');
    }
    function closeConfirmApproveModal(approvalId) {
        document.getElementById('confirmApproveModal_' + approvalId).classList.remove('show');
    }

    function showConfirmRejectModal(approvalId) {
        document.getElementById('confirmRejectModal_' + approvalId).classList.add('show');
    }
    function closeConfirmRejectModal(approvalId) {
        document.getElementById('confirmRejectModal_' + approvalId).classList.remove('show');
    }

    let leaveProcessProgressInterval = null;

    function showLeaveProcessOverlay(title, subtitle) {
        if (title) document.getElementById('leaveProcessTitle').innerText = title;
        if (subtitle) document.getElementById('leaveProcessSubtitle').innerText = subtitle;

        const overlay = document.getElementById('leaveProcessOverlay');
        const fill = document.getElementById('leaveProgressFill');
        const percentText = document.getElementById('leaveProgressPercent');
        const statusText = document.getElementById('leaveProgressText');

        overlay.style.display = 'flex';

        let currentProgress = 5;
        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
        statusText.innerText = 'Connecting to server...';

        if (leaveProcessProgressInterval) clearInterval(leaveProcessProgressInterval);

        leaveProcessProgressInterval = setInterval(function() {
            if (currentProgress < 30) {
                currentProgress += Math.floor(Math.random() * 8) + 4;
                statusText.innerText = 'Building leave notification email...';
            } else if (currentProgress < 70) {
                currentProgress += Math.floor(Math.random() * 6) + 3;
                statusText.innerText = 'Sending email via SMTP...';
            } else if (currentProgress < 92) {
                currentProgress += Math.floor(Math.random() * 3) + 1;
                statusText.innerText = 'Finalizing leave approval workflow...';
            }

            if (currentProgress > 94) {
                currentProgress = 94;
            }

            fill.style.width = currentProgress + '%';
            percentText.innerText = currentProgress + '%';
        }, 250);
    }

    function submitApproveForm(approvalId) {
        closeConfirmApproveModal(approvalId);
        showLeaveProcessOverlay(
            "Sending Email & Processing Approval...",
            "Please wait while the leave step is approved and email notification is sent..."
        );
        document.getElementById('approveForm_' + approvalId).submit();
    }

    function submitRejectForm(approvalId) {
        closeConfirmRejectModal(approvalId);
        showLeaveProcessOverlay(
            "Sending Email & Processing Rejection...",
            "Please wait while the leave request is rejected and email notification is sent..."
        );
        document.getElementById('rejectForm_' + approvalId).submit();
    }
</script>
@endpush
