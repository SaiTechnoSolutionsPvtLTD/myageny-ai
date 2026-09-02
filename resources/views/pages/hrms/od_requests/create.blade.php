@extends('layouts.app')

@section('title', 'Apply OD Request')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .od-create-layout { display:grid; grid-template-columns:minmax(0, 1fr) 340px; gap:18px; align-items:start; }
        .od-flow { display:flex; flex-direction:column; gap:10px; }
        .od-flow-step { padding:14px; border:1px solid #f0eef2; border-radius:14px; background:#fafafa; }
        .od-flow-title { font-size:13px; font-weight:800; color:#121212; }
        .od-flow-sub { margin-top:4px; font-size:12px; color:#7c7c7c; line-height:1.5; }
        .od-note { padding:12px 14px; border-radius:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:12px; line-height:1.5; }
        @media (max-width: 980px) { .od-create-layout { grid-template-columns:1fr; } }

        /* Confirmation Modal */
        .od-confirm-modal {
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
        .od-confirm-modal.show {
            display: flex;
            opacity: 1;
        }
        .od-confirm-card {
            background: #ffffff;
            border-radius: 20px;
            width: 450px;
            max-width: calc(100vw - 32px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transform: translateY(15px);
            transition: transform 0.25s ease;
        }
        .od-confirm-modal.show .od-confirm-card {
            transform: translateY(0);
        }
        .od-confirm-head {
            padding: 18px 24px;
            border-bottom: 1px solid #ffedd5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff7ed;
        }
        .od-confirm-title {
            font-size: 16px;
            font-weight: 800;
            color: #c2410c;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .od-confirm-body {
            padding: 24px;
            text-align: center;
        }
        .od-confirm-foot {
            padding: 16px 24px;
            border-top: 1px solid #f3f4f6;
            background: #fafafa;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Mail Process / Progress Bar Overlay (Support Portal Style) */
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
            <div class="eob-title">New OD Request</div>
            <div class="eob-breadcrumb">HRMS > OD Requests > New</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('od-requests.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="eob-alert eob-alert-error">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="od-create-layout">
            {{-- Form Card --}}
            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">OD Permission Application</div>
                        <div class="eob-card-sub">Submit an On Duty (OD) request. Once approved by your hierarchy, attendance will be recorded as OD.</div>
                    </div>
                </div>

                <form method="POST" id="createOdForm" action="{{ route('od-requests.store') }}">
                    @csrf
                    <div class="eob-card-body">
                        <div class="eob-form-grid">
                            {{-- Applicant Info --}}
                            <div class="eob-group full">
                                <label class="eob-label">Applicant</label>
                                <input type="text" class="eob-input" value="{{ $employee?->name ?: auth()->user()->name }} ({{ $employee?->employee_id ?: auth()->user()->email }})" readonly style="background: #f9fafb;">
                            </div>

                            {{-- From Date --}}
                            <div class="eob-group">
                                <label class="eob-label">From Date <span class="eob-label-required">*</span></label>
                                <input type="date" name="from_date" id="from_date" class="eob-input"
                                       min="{{ $minDate }}"
                                       value="{{ old('from_date', $minDate) }}" required>
                                <div class="eob-help">Past dates cannot be selected.</div>
                            </div>

                            {{-- To Date --}}
                            <div class="eob-group">
                                <label class="eob-label">To Date <span class="eob-label-required">*</span></label>
                                <input type="date" name="to_date" id="to_date" class="eob-input"
                                       min="{{ $minDate }}"
                                       value="{{ old('to_date', $minDate) }}" required>
                                <div class="eob-help" id="totalDaysHelp">Total: 1 day(s)</div>
                            </div>

                            {{-- Reason / Remarks --}}
                            <div class="eob-group full">
                                <label class="eob-label">Remarks / Reason <span class="eob-label-required">*</span></label>
                                <textarea name="reason" rows="4" class="eob-textarea" placeholder="Explain the purpose of your On Duty permission, location, and tasks..." required>{{ old('reason') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="eob-foot">
                        <a href="{{ route('od-requests.index') }}" class="eob-btn eob-btn-ghost">Cancel</a>
                        <button type="button" class="eob-btn eob-btn-primary" onclick="showConfirmOdModal()">Submit OD Request</button>
                    </div>
                </form>
            </div>

            {{-- Sidebar: Approval Hierarchy Preview --}}
            <div class="eob-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Approval Hierarchy</div>
                        <div class="eob-card-sub">Multi-stage approval configured for your role.</div>
                    </div>
                </div>

                <div class="eob-card-body">
                    @if($approvalChain->isEmpty())
                        <div class="od-note">
                            No approval hierarchy found for your role. Please contact HR or Admin to configure your reporting manager.
                        </div>
                    @else
                        <div class="od-flow">
                            @foreach($approvalChain as $index => $approver)
                                <div class="od-flow-step">
                                    <div class="od-flow-title">Level {{ $index + 1 }} Approval</div>
                                    <div class="od-flow-sub">
                                        <strong>{{ $approver->name }}</strong><br>
                                        <span style="color: #9e9e9e;">{{ $approver->email }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="od-note" style="margin-top: 14px;">
                            Upon final level approval, each date in this range is automatically recorded in Daily Attendance as <strong>OD (Present)</strong>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Confirmation Modal --}}
<div class="od-confirm-modal" id="confirmOdModal">
    <div class="od-confirm-card">
        <div class="od-confirm-head">
            <div class="od-confirm-title">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Confirm OD Request Submission
            </div>
            <button type="button" class="eob-icon-btn" onclick="closeConfirmOdModal()">&times;</button>
        </div>
        <div class="od-confirm-body">
            <p style="font-size: 15px; color: #1f2937; margin: 0; line-height: 1.6;">
                Are you sure you want to submit this <strong>On Duty (OD)</strong> request?
            </p>
            <p style="font-size: 13px; color: #6b7280; margin: 8px 0 0; line-height: 1.5;">
                An email notification will be dispatched to your Level 1 reporting manager for approval.
            </p>
        </div>
        <div class="od-confirm-foot">
            <button type="button" class="eob-btn eob-btn-ghost" onclick="closeConfirmOdModal()">Cancel</button>
            <button type="button" class="eob-btn eob-btn-primary" id="btnConfirmOdSubmit">Yes, Submit Request</button>
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
        <p id="odProcessSubtitle" class="od-process-subtitle">Please wait while your OD request is being submitted and email notification is sent...</p>

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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var fromInput = document.getElementById('from_date');
    var toInput = document.getElementById('to_date');
    var totalHelp = document.getElementById('totalDaysHelp');

    function calculateDays() {
        if (!fromInput.value || !toInput.value) return;
        var f = new Date(fromInput.value);
        var t = new Date(toInput.value);
        if (t < f) {
            toInput.value = fromInput.value;
            t = f;
        }
        var diffTime = Math.abs(t - f);
        var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        totalHelp.innerText = 'Total: ' + diffDays + ' day(s)';
    }

    fromInput.addEventListener('change', function() {
        toInput.min = fromInput.value;
        if (toInput.value < fromInput.value) {
            toInput.value = fromInput.value;
        }
        calculateDays();
    });

    toInput.addEventListener('change', function() {
        calculateDays();
    });

    calculateDays();

    // Confirmation & Preloader logic
    document.getElementById('btnConfirmOdSubmit').addEventListener('click', function() {
        closeConfirmOdModal();
        showOdProcessOverlay();
        document.getElementById('createOdForm').submit();
    });
});

function showConfirmOdModal() {
    var form = document.getElementById('createOdForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    document.getElementById('confirmOdModal').classList.add('show');
}

function closeConfirmOdModal() {
    document.getElementById('confirmOdModal').classList.remove('show');
}

let odProcessProgressInterval = null;

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
            statusText.innerText = 'Building OD notification email...';
        } else if (currentProgress < 70) {
            currentProgress += Math.floor(Math.random() * 6) + 3;
            statusText.innerText = 'Sending email via SMTP...';
        } else if (currentProgress < 92) {
            currentProgress += Math.floor(Math.random() * 3) + 1;
            statusText.innerText = 'Finalizing OD request submission...';
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
