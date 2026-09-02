@extends('layouts.app')

@section('title', 'Outside Office Requests')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .lr-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }
        .lr-summary-card {
            background: linear-gradient(135deg, #fffaf5 0%, #ffffff 100%);
            border: 1px solid #f1e5d7;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 10px 24px rgba(18, 18, 18, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .lr-summary-card.active-card {
            border-color: #fe5f04;
            background: #fff7ed;
        }
        .lr-summary-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #9a6b39;
        }
        .lr-summary-value {
            margin-top: 8px;
            font-size: 26px;
            font-weight: 900;
            color: #121212;
            line-height: 1.1;
        }
        .lr-summary-sub {
            margin-top: 6px;
            font-size: 12px;
            color: #7a7a7a;
            line-height: 1.4;
        }
        .lr-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .lr-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid #eadfce;
            background: #fff;
            color: #6b5b46;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            transition: all .18s ease;
        }
        .lr-nav-link:hover {
            background: #fff7ed;
            border-color: #fdba74;
            color: #c2410c;
        }
        .lr-nav-link.active {
            background: #fe5f04;
            border-color: #fe5f04;
            color: #ffffff;
        }
        .lr-nav-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            background: #f6efe7;
            color: #8a5b2f;
            font-size: 11px;
            font-weight: 800;
        }
        .lr-nav-link.active .lr-nav-count {
            background: rgba(255, 255, 255, 0.3);
            color: #ffffff;
        }
        .eob-modal.is-open {
            display: flex !important;
        }
        @media (max-width: 1024px) {
            .lr-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .lr-summary-grid { grid-template-columns: 1fr; }
        }

        /* Mail Process / Progress Bar Overlay */
        .oor-process-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999999;
            animation: oorFadeInOverlay 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes oorFadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .oor-process-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px 36px;
            width: 460px;
            max-width: calc(100vw - 32px);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
            text-align: center;
            transform: scale(0.95);
            animation: oorScaleInCard 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes oorScaleInCard {
            to { transform: scale(1); }
        }
        .oor-process-icon-wrap {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .oor-process-spinner {
            position: absolute;
            inset: 0;
            border: 3.5px solid #ffe6d5;
            border-top-color: #fe5f04;
            border-radius: 50%;
            animation: oorSpinOverlay 0.9s linear infinite;
        }
        @keyframes oorSpinOverlay {
            to { transform: rotate(360deg); }
        }
        .oor-process-icon {
            font-size: 32px;
            color: #fe5f04;
            animation: oorPulseIcon 1.5s ease-in-out infinite alternate;
        }
        @keyframes oorPulseIcon {
            from { transform: scale(0.88); opacity: 0.85; }
            to { transform: scale(1.12); opacity: 1; }
        }
        .oor-process-title {
            font-size: 19px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 6px;
        }
        .oor-process-subtitle {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 24px;
            line-height: 1.5;
        }
        .oor-progress-wrapper { width: 100%; }
        .oor-progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }
        .oor-progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 100%);
            border-radius: 999px;
            transition: width 0.3s ease;
        }
        .oor-progress-status {
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
    {{-- Standard HRMS Topbar --}}
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Outside Office Requests</div>
            <div class="eob-breadcrumb">HRMS > Outside Office Requests</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back to HRMS</a>
        </div>
    </div>

    <div class="eob-body">
        {{-- Flash Alerts --}}
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        {{-- Summary Cards matching other HRMS pages --}}
        <div class="lr-summary-grid">
            <div class="lr-summary-card {{ $activeStatus === 'pending' ? 'active-card' : '' }}">
                <div class="lr-summary-label">Pending Total</div>
                <div class="lr-summary-value" style="color: #fe5f04;">{{ $stats['pending_total'] }}</div>
                <div class="lr-summary-sub">Pending check-in & check-out requests.</div>
            </div>
            <div class="lr-summary-card">
                <div class="lr-summary-label">Pending Check-Ins</div>
                <div class="lr-summary-value" style="color: #0d9488;">{{ $stats['pending_checkins'] }}</div>
                <div class="lr-summary-sub">Awaiting outside check-in review.</div>
            </div>
            <div class="lr-summary-card">
                <div class="lr-summary-label">Pending Check-Outs</div>
                <div class="lr-summary-value" style="color: #4f46e5;">{{ $stats['pending_checkouts'] }}</div>
                <div class="lr-summary-sub">Awaiting outside check-out review.</div>
            </div>
            <div class="lr-summary-card">
                <div class="lr-summary-label">Total Handled</div>
                <div class="lr-summary-value" style="color: #15803d;">{{ $stats['approved_total'] + $stats['rejected_total'] }}</div>
                <div class="lr-summary-sub">{{ $stats['approved_total'] }} approved, {{ $stats['rejected_total'] }} rejected.</div>
            </div>
        </div>

        {{-- Quick Status Filter Nav --}}
        <div class="lr-nav">
            <a href="{{ route('hrms.outside-office-requests.index', array_merge(request()->query(), ['status' => 'pending', 'page' => 1])) }}"
               class="lr-nav-link {{ $activeStatus === 'pending' ? 'active' : '' }}">
                Pending <span class="lr-nav-count">{{ $stats['pending_total'] }}</span>
            </a>
            <a href="{{ route('hrms.outside-office-requests.index', array_merge(request()->query(), ['status' => 'approved', 'page' => 1])) }}"
               class="lr-nav-link {{ $activeStatus === 'approved' ? 'active' : '' }}">
                Approved <span class="lr-nav-count">{{ $stats['approved_total'] }}</span>
            </a>
            <a href="{{ route('hrms.outside-office-requests.index', array_merge(request()->query(), ['status' => 'rejected', 'page' => 1])) }}"
               class="lr-nav-link {{ $activeStatus === 'rejected' ? 'active' : '' }}">
                Rejected <span class="lr-nav-count">{{ $stats['rejected_total'] }}</span>
            </a>
            <a href="{{ route('hrms.outside-office-requests.index', array_merge(request()->query(), ['status' => 'all', 'page' => 1])) }}"
               class="lr-nav-link {{ $activeStatus === 'all' ? 'active' : '' }}">
                All Requests <span class="lr-nav-count">{{ $stats['all_total'] }}</span>
            </a>
        </div>

        {{-- Standard HRMS Filter Card --}}
        <div class="eob-filter-card">
            <form method="GET" action="{{ route('hrms.outside-office-requests.index') }}" class="eob-filter-form">
                <input type="hidden" name="status" value="{{ $activeStatus }}">

                <div class="eob-field">
                    <label class="eob-label">Search</label>
                    <input type="text" name="search" class="eob-input" value="{{ $search }}" placeholder="Employee name, ID, reason, or location...">
                </div>

                <div class="eob-field" style="max-width: 180px;">
                    <label class="eob-label">Request Type</label>
                    <select name="request_type" class="eob-select">
                        <option value="any" @selected($activeRequestType === 'any')>All Types</option>
                        <option value="checkin" @selected($activeRequestType === 'checkin')>Check-In</option>
                        <option value="checkout" @selected($activeRequestType === 'checkout')>Check-Out</option>
                    </select>
                </div>

                <div class="eob-field" style="max-width: 160px;">
                    <label class="eob-label">From Date</label>
                    <input type="date" name="from_date" class="eob-input" value="{{ $fromDate }}">
                </div>

                <div class="eob-field" style="max-width: 160px;">
                    <label class="eob-label">To Date</label>
                    <input type="date" name="to_date" class="eob-input" value="{{ $toDate }}">
                </div>

                <div class="eob-actions" style="margin-bottom: 2px;">
                    <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                    @if($search || $fromDate || $toDate || $activeStatus !== 'pending' || $activeRequestType !== 'any')
                        <a href="{{ route('hrms.outside-office-requests.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Standard HRMS Table Card --}}
        <div class="eob-table-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Outside Office Attendance Requests</div>
                    <div class="eob-card-sub">Review outside office check-in and check-out requests submitted by employees and interns.</div>
                </div>
                <div class="eob-results">{{ $requests->total() }} request(s)</div>
            </div>

            @if($requests->isEmpty())
                <div class="eob-empty">No outside office attendance requests found.</div>
            @else
                <div style="overflow-x:auto;">
                    <table class="eob-list-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Requested Time</th>
                                <th>Reason</th>
                                <th>Location</th>
                                <th>Photo</th>
                                <th>Status</th>
                                <th>Decision / Remarks</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $index => $req)
                                @php
                                    $isIntern = $req->attendee_type === 'intern';
                                    $portalUser = $isIntern ? $req->intern?->portalUser : $req->employee?->portalUser;
                                    $deptName = $isIntern ? 'Intern' : ($req->employee?->department?->name ?? 'Department');
                                    $branchName = $portalUser?->branch?->name ?? 'Head Office';
                                    $reqTime = optional($req->requested_at)->format('h:i A') ?? '--:--';
                                    $reqDate = optional($req->attendance_date)->format('d M Y') ?? optional($req->requested_at)->format('d M Y');
                                @endphp
                                <tr>
                                    <td style="color: #9ca3af; font-weight: 600;">
                                        {{ $requests->firstItem() + $index }}
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">
                                            {{ $req->employee_name ?? ($isIntern ? $req->intern?->name : $req->employee?->name) }}
                                        </div>
                                        <div class="eob-cell-sub">
                                            <span class="eob-chip {{ $isIntern ? 'eob-chip-billable' : 'eob-chip-non-billable' }}" style="font-size: 10px; padding: 1px 6px; margin-right: 4px;">
                                                {{ ucfirst($req->attendee_type) }}
                                            </span>
                                            {{ $deptName }} • {{ $branchName }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($req->request_type === 'checkin')
                                            <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; background:#f0fdfa; color:#0d9488; font-weight:800; font-size:11px; border:1px solid #ccfbf1;">
                                                ➔ Check-In
                                            </span>
                                        @else
                                            <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; background:#eef2ff; color:#4f46e5; font-weight:800; font-size:11px; border:1px solid #e0e7ff;">
                                                ➔ Check-Out
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $reqTime }}</div>
                                        <div class="eob-cell-sub">{{ $reqDate }}</div>
                                    </td>
                                    <td>
                                        <div style="max-width: 220px; font-size: 13px; color: #374151; line-height: 1.4;">
                                            {{ $req->reason }}
                                        </div>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; font-size: 12px; color: #4b5563;">
                                            {{ $req->location ?: ($req->latitude && $req->longitude ? "{$req->latitude}, {$req->longitude}" : 'N/A') }}
                                            @if($req->latitude && $req->longitude)
                                                <a href="https://www.google.com/maps?q={{ $req->latitude }},{{ $req->longitude }}" target="_blank" style="color:#fe5f04; text-decoration:underline; font-weight:700; margin-left:4px;">Map</a>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($req->photo)
                                            <img src="{{ asset($req->photo) }}" alt="Photo" style="width: 38px; height: 38px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; cursor: pointer; transition: transform .15s ease;"
                                                 onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'"
                                                 onclick="openPhotoModal('{{ asset($req->photo) }}', '{{ addslashes($req->employee_name) }}')"
                                                 title="Click to preview photo">
                                        @else
                                            <span style="color: #9ca3af; font-size: 11px;">No photo</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->status === 'approved')
                                            <span class="eob-chip eob-chip-active">Approved</span>
                                        @elseif($req->status === 'rejected')
                                            <span class="eob-chip eob-chip-rejected">Rejected</span>
                                        @else
                                            <span class="eob-chip eob-chip-pending">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($req->reviewed_by)
                                            <div class="eob-cell-title" style="font-size: 12px;">{{ $req->reviewer?->name ?? 'HR/Admin' }}</div>
                                            <div class="eob-cell-sub">{{ optional($req->reviewed_at)->format('d M Y, h:i A') }}</div>
                                            @if($req->admin_remarks)
                                                <div style="font-size: 11px; color: #6b7280; margin-top: 2px;">{{ $req->admin_remarks }}</div>
                                            @endif
                                        @else
                                            <span style="color: #9ca3af; font-size: 12px;">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        @if($req->status === 'pending')
                                            <div style="display: inline-flex; gap: 6px; justify-content: flex-end;">
                                                <button type="button" class="eob-btn eob-btn-primary eob-btn-sm" style="background: #059669; border-color: #059669; padding: 5px 10px;"
                                                        onclick="openApproveModal({{ $req->id }}, '{{ addslashes($req->employee_name) }}', '{{ $req->request_type }}', '{{ $reqDate }}', '{{ $reqTime }}')">
                                                    Approve
                                                </button>
                                                <button type="button" class="eob-btn eob-btn-danger eob-btn-sm" style="padding: 5px 10px;"
                                                        onclick="openRejectModal({{ $req->id }}, '{{ addslashes($req->employee_name) }}', '{{ $req->request_type }}')">
                                                    Reject
                                                </button>
                                            </div>
                                        @else
                                            <span style="font-size: 11px; color: #9ca3af; font-weight: 600;">Completed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                @if($requests->hasPages())
                    @include('partials.table-pagination', ['paginator' => $requests])
                @endif
            @endif
        </div>
    </div>
</div>

{{-- Approve Modal --}}
<div class="eob-modal" id="approveModal">
    <div class="eob-modal-card" style="width: min(100%, 460px);">
        <form method="POST" id="approveForm" action="" onsubmit="return handleOorSubmit(this, 'Approve');">
            @csrf
            <div class="eob-modal-head">
                <div class="eob-modal-title">Approve Outside Office Request</div>
                <div class="eob-modal-copy" id="approveModalText">
                    Are you sure you want to approve this outside-office request?
                </div>
            </div>
            <div style="padding: 0 22px 14px;">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 10px 12px; font-size: 12px; color: #166534; margin-bottom: 12px;">
                    Approving will record the attendance entry with the employee's requested time and send a notification.
                </div>
                <div class="eob-group">
                    <label class="eob-label">Optional Remarks</label>
                    <input type="text" name="remarks" class="eob-input" placeholder="Optional notes for approval...">
                </div>
            </div>
            <div class="eob-modal-foot">
                <button type="button" class="eob-btn eob-btn-ghost" onclick="closeModal('approveModal')">Cancel</button>
                <button type="submit" class="eob-btn eob-btn-primary" style="background: #059669; border-color: #059669;">Confirm Approval</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div class="eob-modal" id="rejectModal">
    <div class="eob-modal-card" style="width: min(100%, 460px);">
        <form method="POST" id="rejectForm" action="" onsubmit="return handleOorSubmit(this, 'Reject');">
            @csrf
            <div class="eob-modal-head">
                <div class="eob-modal-title">Reject Outside Office Request</div>
                <div class="eob-modal-copy" id="rejectModalText">
                    Please provide a reason for rejecting this outside-office request.
                </div>
            </div>
            <div style="padding: 0 22px 14px;">
                <div class="eob-group">
                    <label class="eob-label">Rejection Reason <span class="eob-label-required">*</span></label>
                    <textarea name="remarks" required class="eob-textarea" rows="3" placeholder="Explain why this request is rejected..."></textarea>
                </div>
            </div>
            <div class="eob-modal-foot">
                <button type="button" class="eob-btn eob-btn-ghost" onclick="closeModal('rejectModal')">Cancel</button>
                <button type="submit" class="eob-btn eob-btn-danger" style="background: #dc2626; color: #fff; border-color: #dc2626;">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

{{-- Photo Preview Modal --}}
<div class="eob-modal" id="photoModal">
    <div class="eob-modal-card" style="width: min(100%, 500px);">
        <div class="eob-modal-head" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="eob-modal-title" id="photoModalTitle">Attendance Photo</div>
            <button type="button" class="eob-icon-btn" onclick="closeModal('photoModal')">✕</button>
        </div>
        <div style="padding: 16px 22px 22px; text-align: center;">
            <img id="photoModalImg" src="" alt="Preview" style="width: 100%; max-height: 460px; object-fit: contain; border-radius: 12px; border: 1px solid #e1dee3;">
        </div>
    </div>
</div>


{{-- Mail Process / Progress Bar Overlay --}}
<div id="oorProcessOverlay" class="oor-process-overlay">
    <div class="oor-process-card">
        <div class="oor-process-icon-wrap">
            <div class="oor-process-spinner"></div>
            <svg class="oor-process-icon" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h4 id="oorProcessTitle" class="oor-process-title">Sending Email & Processing...</h4>
        <p id="oorProcessSubtitle" class="oor-process-subtitle">Please wait while the request action is being processed and notification is sent...</p>

        <div class="oor-progress-wrapper">
            <div class="oor-progress-bar">
                <div id="oorProgressFill" class="oor-progress-fill"></div>
            </div>
            <div class="oor-progress-status">
                <span id="oorProgressText">Preparing notification...</span>
                <span id="oorProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openApproveModal(id, name, type, date, time) {
    var form = document.getElementById('approveForm');
    form.action = "{{ url('hrms/outside-office-requests') }}/" + id + "/approve";
    var text = document.getElementById('approveModalText');
    var typeLabel = type === 'checkin' ? 'Check-In' : 'Check-Out';
    text.innerHTML = "Approve <strong>" + typeLabel + "</strong> request for <strong>" + name + "</strong> on <strong>" + date + " " + time + "</strong>?";
    document.getElementById('approveModal').classList.add('is-open');
}

function openRejectModal(id, name, type) {
    var form = document.getElementById('rejectForm');
    form.action = "{{ url('hrms/outside-office-requests') }}/" + id + "/reject";
    var text = document.getElementById('rejectModalText');
    var typeLabel = type === 'checkin' ? 'Check-In' : 'Check-Out';
    text.innerHTML = "Reject <strong>" + typeLabel + "</strong> request for <strong>" + name + "</strong>:";
    document.getElementById('rejectModal').classList.add('is-open');
}

function openPhotoModal(imgSrc, name) {
    document.getElementById('photoModalImg').src = imgSrc;
    document.getElementById('photoModalTitle').innerText = name + ' - Attendance Photo';
    document.getElementById('photoModal').classList.add('is-open');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('is-open');
}

// Close on outside click
document.querySelectorAll('.eob-modal').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('is-open');
        }
    });
});

let oorProcessProgressInterval = null;

function handleOorSubmit(form, actionType) {
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    closeModal('approveModal');
    closeModal('rejectModal');

    var title = actionType === 'Approve' ? 'Approving Request & Sending Email...' : 'Rejecting Request & Sending Email...';
    var subtitle = 'Please wait while attendance is recorded and notification is dispatched...';
    showOorProcessOverlay(title, subtitle);
    return true;
}

function showOorProcessOverlay(title, subtitle) {
    if (title) document.getElementById('oorProcessTitle').innerText = title;
    if (subtitle) document.getElementById('oorProcessSubtitle').innerText = subtitle;

    const overlay = document.getElementById('oorProcessOverlay');
    const fill = document.getElementById('oorProgressFill');
    const percentText = document.getElementById('oorProgressPercent');
    const statusText = document.getElementById('oorProgressText');

    overlay.style.display = 'flex';

    let currentProgress = 5;
    fill.style.width = currentProgress + '%';
    percentText.innerText = currentProgress + '%';
    statusText.innerText = 'Connecting to server...';

    if (oorProcessProgressInterval) clearInterval(oorProcessProgressInterval);

    oorProcessProgressInterval = setInterval(function() {
        if (currentProgress < 30) {
            currentProgress += Math.floor(Math.random() * 8) + 4;
            statusText.innerText = 'Processing attendance record...';
        } else if (currentProgress < 70) {
            currentProgress += Math.floor(Math.random() * 6) + 3;
            statusText.innerText = 'Sending notification email via SMTP...';
        } else if (currentProgress < 92) {
            currentProgress += Math.floor(Math.random() * 3) + 1;
            statusText.innerText = 'Finalizing outside office request...';
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
