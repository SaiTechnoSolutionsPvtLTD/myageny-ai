@extends('layouts.app')

@section('title', 'Create Permission Request')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .pr-create-layout { display:grid; grid-template-columns:minmax(0, 1fr) 340px; gap:18px; align-items:start; }
        .pr-flow { display:flex; flex-direction:column; gap:10px; }
        .pr-flow-step { padding:14px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
        .pr-flow-title { font-size:13px; font-weight:800; color:#121212; }
        .pr-flow-sub { margin-top:4px; font-size:12px; color:#7c7c7c; line-height:1.5; }
        .pr-note { padding:12px 14px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; line-height:1.5; }
        @media (max-width: 980px) { .pr-create-layout { grid-template-columns:1fr; } }

        /* Confirmation Modal */
        .pr-confirm-modal {
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
        .pr-confirm-modal.show {
            display: flex;
            opacity: 1;
        }
        .pr-confirm-card {
            background: #ffffff;
            border-radius: 20px;
            width: 450px;
            max-width: calc(100vw - 32px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transform: translateY(15px);
            transition: transform 0.25s ease;
        }
        .pr-confirm-modal.show .pr-confirm-card {
            transform: translateY(0);
        }
        .pr-confirm-head {
            padding: 18px 24px;
            border-bottom: 1px solid #ffedd5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff7ed;
        }
        .pr-confirm-title {
            font-size: 16px;
            font-weight: 800;
            color: #c2410c;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pr-confirm-body {
            padding: 24px;
            text-align: center;
        }
        .pr-confirm-foot {
            padding: 16px 24px;
            border-top: 1px solid #f3f4f6;
            background: #fafafa;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Process / Progress Bar Overlay */
        .pr-process-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999999;
            animation: prFadeInOverlay 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes prFadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .pr-process-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px 36px;
            width: 460px;
            max-width: calc(100vw - 32px);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
            text-align: center;
            transform: scale(0.95);
            animation: prScaleInCard 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes prScaleInCard {
            to { transform: scale(1); }
        }
        .pr-process-icon-wrap {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pr-process-spinner {
            position: absolute;
            inset: 0;
            border: 3.5px solid #ffe6d5;
            border-top-color: #fe5f04;
            border-radius: 50%;
            animation: prSpinOverlay 0.9s linear infinite;
        }
        @keyframes prSpinOverlay {
            to { transform: rotate(360deg); }
        }
        .pr-process-icon {
            font-size: 32px;
            color: #fe5f04;
            animation: prPulseIcon 1.5s ease-in-out infinite alternate;
        }
        @keyframes prPulseIcon {
            from { transform: scale(0.88); opacity: 0.85; }
            to { transform: scale(1.12); opacity: 1; }
        }
        .pr-process-title {
            font-size: 19px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 6px;
        }
        .pr-process-subtitle {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 24px;
            line-height: 1.5;
        }
        .pr-progress-wrapper { width: 100%; }
        .pr-progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }
        .pr-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 100%);
            border-radius: 999px;
            transition: width 0.25s ease;
        }
        .pr-progress-status {
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
            <div class="eob-title">Create Permission Request</div>
            <div class="eob-breadcrumb">HRMS > Permission Requests > Create</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('permission-requests.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please fix the highlighted fields and submit again.</div>
        @endif

        <div class="pr-create-layout">
            <form id="createPermissionForm" method="POST" action="{{ route('permission-requests.store') }}" class="eob-card">
                @csrf
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Permission Details</div>
                        <div class="eob-card-sub">Submit your permission request through your mapped approval hierarchy.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="eob-form-grid">
                        <div class="eob-group full">
                            <label class="eob-label">Permission Date <span class="eob-label-required">*</span></label>
                            <input type="date" name="permission_date" class="eob-input" value="{{ old('permission_date') }}" required>
                            @error('permission_date')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group">
                            <label class="eob-label">From Time <span class="eob-label-required">*</span></label>
                            <input type="time" name="from_time" class="eob-input" value="{{ old('from_time') }}" required>
                            @error('from_time')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group">
                            <label class="eob-label">To Time <span class="eob-label-required">*</span></label>
                            <input type="time" name="to_time" class="eob-input" value="{{ old('to_time') }}" required>
                            @error('to_time')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="eob-group full">
                            <label class="eob-label">Reason <span class="eob-label-required">*</span></label>
                            <textarea name="reason" class="eob-textarea" placeholder="Explain why you need permission" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="eob-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="eob-foot">
                    <a href="{{ route('permission-requests.index') }}" class="eob-btn eob-btn-ghost">Cancel</a>
                    <button type="button" class="eob-btn eob-btn-primary" onclick="showConfirmPermissionModal()">Submit Request</button>
                </div>
            </form>

            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Approval Hierarchy</div>
                        <div class="eob-card-sub">Permission request approval will follow your mapped Leave Hierarchy settings chain.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    @if($approvalChain->isEmpty())
                        <div class="pr-note">No approval hierarchy could be resolved for your user role. Please verify your role mapping in Leave Hierarchy Settings before submitting.</div>
                    @else
                        <div class="pr-flow">
                            @foreach($approvalChain as $approver)
                                <div class="pr-flow-step">
                                    <div class="pr-flow-title">Step {{ $loop->iteration }}: {{ $approver->name }}</div>
                                    <div class="pr-flow-sub">{{ $approver->roles->first()?->display_name ?? $approver->roles->first()?->name ?? 'No Role' }} · {{ $approver->email }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(! $employee)
                        <div class="pr-note" style="margin-top:14px;">No employee onboarding record was found for your login email. The request can still be submitted under your user account.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Confirmation Modal --}}
<div id="confirmPermissionModal" class="pr-confirm-modal">
    <div class="pr-confirm-card">
        <div class="pr-confirm-head">
            <div class="pr-confirm-title">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Confirm Permission Request
            </div>
            <button class="eob-btn-ghost" style="border:none; background:none; font-size:18px; cursor:pointer;" onclick="closeConfirmPermissionModal()">✕</button>
        </div>
        <div class="pr-confirm-body">
            <p style="font-size:15px; font-weight:800; color:#111827; margin-bottom:8px;">Are you sure you want to submit this permission request?</p>
            <p style="font-size:13px; color:#6b7280; line-height:1.5;">An automated email notification will be sent to Stage 1 approvers according to your mapped Leave Hierarchy.</p>
        </div>
        <div class="pr-confirm-foot">
            <button type="button" class="eob-btn eob-btn-ghost" onclick="closeConfirmPermissionModal()">Cancel</button>
            <button type="button" class="eob-btn eob-btn-primary" id="btnConfirmPermissionSubmit">Yes, Submit Request</button>
        </div>
    </div>
</div>

{{-- Process Overlay Modal --}}
<div id="permissionProcessOverlay" class="pr-process-overlay">
    <div class="pr-process-card">
        <div class="pr-process-icon-wrap">
            <div class="pr-process-spinner"></div>
            <svg class="pr-process-icon" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h4 id="permissionProcessTitle" class="pr-process-title">Sending Email & Processing...</h4>
        <p id="permissionProcessSubtitle" class="pr-process-subtitle">Please wait while your permission request is being submitted and email notification is sent...</p>

        <div class="pr-progress-wrapper">
            <div class="pr-progress-bar">
                <div id="permissionProgressFill" class="pr-progress-fill"></div>
            </div>
            <div class="pr-progress-status">
                <span id="permissionProgressText">Preparing email notification...</span>
                <span id="permissionProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        function showConfirmPermissionModal() {
            const form = document.getElementById('createPermissionForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            document.getElementById('confirmPermissionModal').classList.add('show');
        }
        function closeConfirmPermissionModal() {
            document.getElementById('confirmPermissionModal').classList.remove('show');
        }

        let permissionProcessProgressInterval = null;

        function showPermissionProcessOverlay() {
            const overlay = document.getElementById('permissionProcessOverlay');
            const fill = document.getElementById('permissionProgressFill');
            const percentText = document.getElementById('permissionProgressPercent');
            const statusText = document.getElementById('permissionProgressText');

            overlay.style.display = 'flex';

            let currentProgress = 5;
            fill.style.width = currentProgress + '%';
            percentText.innerText = currentProgress + '%';
            statusText.innerText = 'Connecting to server...';

            if (permissionProcessProgressInterval) clearInterval(permissionProcessProgressInterval);

            permissionProcessProgressInterval = setInterval(function() {
                if (currentProgress < 30) {
                    currentProgress += Math.floor(Math.random() * 8) + 4;
                    statusText.innerText = 'Building permission notification email...';
                } else if (currentProgress < 70) {
                    currentProgress += Math.floor(Math.random() * 6) + 3;
                    statusText.innerText = 'Sending email via SMTP...';
                } else if (currentProgress < 92) {
                    currentProgress += Math.floor(Math.random() * 3) + 1;
                    statusText.innerText = 'Finalizing permission approval workflow...';
                }

                if (currentProgress > 94) {
                    currentProgress = 94;
                }

                fill.style.width = currentProgress + '%';
                percentText.innerText = currentProgress + '%';
            }, 250);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const btnConfirm = document.getElementById('btnConfirmPermissionSubmit');
            if (btnConfirm) {
                btnConfirm.addEventListener('click', function() {
                    const form = document.getElementById('createPermissionForm');
                    closeConfirmPermissionModal();
                    showPermissionProcessOverlay();
                    form.submit();
                });
            }
        });
    </script>
@endpush
