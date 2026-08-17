@extends('layouts.app')

@section('title', 'Create Leave Request')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .lr-create-layout { display:grid; grid-template-columns:minmax(0, 1fr) 340px; gap:18px; align-items:start; }
        .lr-flow { display:flex; flex-direction:column; gap:10px; }
        .lr-flow-step { padding:14px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
        .lr-flow-title { font-size:13px; font-weight:800; color:#121212; }
        .lr-flow-sub { margin-top:4px; font-size:12px; color:#7c7c7c; line-height:1.5; }
        .lr-note { padding:12px 14px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; line-height:1.5; }
        @media (max-width: 980px) { .lr-create-layout { grid-template-columns:1fr; } }

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
            border-bottom: 1px solid #ffedd5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff7ed;
        }
        .lr-confirm-title {
            font-size: 16px;
            font-weight: 800;
            color: #c2410c;
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

        /* Process / Progress Bar Overlay */
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
            <div class="eob-title">Create Leave Request</div>
            <div class="eob-breadcrumb">HRMS > Leave Requests > Create</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('leave-requests.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please fix the highlighted fields and submit again.</div>
        @endif

        <div class="lr-create-layout">
            <form id="createLeaveForm" method="POST" action="{{ route('leave-requests.store') }}" class="eob-card">
                @csrf
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Leave Details</div>
                        <div class="eob-card-sub">Submit your leave request through your mapped approval hierarchy.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="eob-form-grid">
                        <div class="eob-group full">
                            <label class="eob-label">Leave Type <span class="eob-label-required">*</span></label>
                            <select name="leave_type_id" class="eob-select" required>
                                <option value="">Select leave type</option>
                                @foreach($leaveTypes as $leaveType)
                                    <option value="{{ $leaveType->id }}" @selected(old('leave_type_id') == $leaveType->id)>{{ $leaveType->name }}</option>
                                @endforeach
                            </select>
                            @error('leave_type_id')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group">
                            <label class="eob-label">Start Date <span class="eob-label-required">*</span></label>
                            <input type="date" name="start_date" class="eob-input" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group">
                            <label class="eob-label">End Date <span class="eob-label-required">*</span></label>
                            <input type="date" name="end_date" class="eob-input" value="{{ old('end_date') }}" required>
                            @error('end_date')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group full">
                            <label class="eob-label">Reason <span class="eob-label-required">*</span></label>
                            <textarea name="reason" class="eob-textarea" placeholder="Explain why you need leave" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="eob-foot">
                    <a href="{{ route('leave-requests.index') }}" class="eob-btn eob-btn-ghost">Cancel</a>
                    <button type="button" class="eob-btn eob-btn-primary" onclick="showConfirmLeaveModal()">Submit Request</button>
                </div>
            </form>

            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Approval Hierarchy</div>
                        <div class="eob-card-sub">Leave request approval will follow your mapped Leave Hierarchy settings chain.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    @if($approvalChain->isEmpty())
                        <div class="lr-note">No approval hierarchy could be resolved for your user role. Please verify your role mapping in Leave Hierarchy Settings before submitting.</div>
                    @else
                        <div class="lr-flow">
                            @foreach($approvalChain as $approver)
                                <div class="lr-flow-step">
                                    <div class="lr-flow-title">Step {{ $loop->iteration }}: {{ $approver->name }}</div>
                                    <div class="lr-flow-sub">{{ $approver->roles->first()?->display_name ?? $approver->roles->first()?->name ?? 'No Role' }} · {{ $approver->email }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(! $employee)
                        <div class="lr-note" style="margin-top:14px;">
                            No employee onboarding record was found for your login email. The request can still be submitted under your user account.
                        </div>
                    @endif

                    @if($leaveTypes->isEmpty())
                        <div class="lr-note" style="margin-top:14px;">
                            Create at least one Leave Type from Masters before submitting leave requests.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Confirmation Modal --}}
<div id="confirmLeaveModal" class="lr-confirm-modal">
    <div class="lr-confirm-card">
        <div class="lr-confirm-head">
            <div class="lr-confirm-title">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Confirm Leave Request
            </div>
            <button class="eob-btn-ghost" style="border:none; background:none; font-size:18px; cursor:pointer;" onclick="closeConfirmLeaveModal()">✕</button>
        </div>
        <div class="lr-confirm-body">
            <p style="font-size:15px; font-weight:800; color:#111827; margin-bottom:8px;">Are you sure you want to submit this leave request?</p>
            <p style="font-size:13px; color:#6b7280; line-height:1.5;">An automated email notification will be sent to Stage 1 approvers according to your mapped Leave Hierarchy.</p>
        </div>
        <div class="lr-confirm-foot">
            <button type="button" class="eob-btn eob-btn-ghost" onclick="closeConfirmLeaveModal()">Cancel</button>
            <button type="button" class="eob-btn eob-btn-primary" id="btnConfirmLeaveSubmit">Yes, Submit Request</button>
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
        <p id="leaveProcessSubtitle" class="lr-process-subtitle">Please wait while your leave request is being submitted and email notification is sent...</p>

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
        function showConfirmLeaveModal() {
            const form = document.getElementById('createLeaveForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            document.getElementById('confirmLeaveModal').classList.add('show');
        }
        function closeConfirmLeaveModal() {
            document.getElementById('confirmLeaveModal').classList.remove('show');
        }

        let leaveProcessProgressInterval = null;

        function showLeaveProcessOverlay() {
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

        document.addEventListener('DOMContentLoaded', function () {
            const btnConfirm = document.getElementById('btnConfirmLeaveSubmit');
            if (btnConfirm) {
                btnConfirm.addEventListener('click', function() {
                    const form = document.getElementById('createLeaveForm');
                    closeConfirmLeaveModal();
                    showLeaveProcessOverlay();
                    form.submit();
                });
            }
        });
    </script>
@endpush
