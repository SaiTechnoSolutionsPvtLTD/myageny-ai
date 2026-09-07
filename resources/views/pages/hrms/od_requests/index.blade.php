@extends('layouts.app')

@section('title', 'OD Requests')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .od-page { display:flex; flex-direction:column; gap:18px; }
        .od-summary-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
        .od-summary-card {
            background:linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
            border:1px solid #dce9f8;
            border-radius:18px;
            padding:18px;
            box-shadow:0 14px 28px rgba(18, 18, 18, 0.04);
            cursor:pointer;
            transition:transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .od-summary-card:hover {
            transform:translateY(-2px);
            box-shadow:0 16px 32px rgba(18, 18, 18, 0.08);
            border-color:#93c5fd;
        }
        .od-summary-card.active {
            border-color:#0284c7;
            background:linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%);
            box-shadow:0 16px 32px rgba(2, 132, 199, 0.08);
        }
        .od-summary-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#355e8b; }
        .od-summary-value { margin-top:10px; font-size:28px; font-weight:800; color:#121212; line-height:1; }
        .od-summary-sub { margin-top:8px; font-size:12px; color:#7a7a7a; line-height:1.5; }
        
        /* Tab Navigation */
        .od-nav { display:flex; flex-wrap:wrap; gap:10px; border-bottom:1px solid #f0eef2; padding-bottom:14px; }
        .od-nav-link {
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 18px;
            border-radius:999px;
            border:1px solid #d7e4f1;
            background:#fff;
            color:#4a6685;
            text-decoration:none;
            font-size:13px;
            font-weight:800;
            cursor:pointer;
            transition:all 0.2s ease;
        }
        .od-nav-link:hover {
            background:#eff6ff;
            border-color:#93c5fd;
            color:#1d4ed8;
        }
        .od-nav-link.active {
            background:#0284c7;
            border-color:#0284c7;
            color:#ffffff;
            box-shadow:0 6px 16px rgba(2, 132, 199, 0.28);
        }
        .od-nav-count {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:24px;
            height:24px;
            padding:0 7px;
            border-radius:999px;
            background:#edf4fb;
            color:#365b84;
            font-size:11px;
            font-weight:700;
            transition:all 0.2s ease;
        }
        .od-nav-link.active .od-nav-count {
            background:rgba(255, 255, 255, 0.25);
            color:#ffffff;
        }

        /* Tab Panels */
        .od-tab-panel {
            display:none;
            animation:odFadeIn 0.25s ease;
        }
        .od-tab-panel.active {
            display:block;
        }
        @keyframes odFadeIn {
            from { opacity:0; transform:translateY(6px); }
            to { opacity:1; transform:translateY(0); }
        }

        .od-section-head { padding:18px 22px; border-bottom:1px solid #f0eef2; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
        .od-section-title { font-size:16px; font-weight:800; color:#121212; }
        .od-section-sub { margin-top:4px; font-size:12px; color:#8c8c8c; line-height:1.5; }
        .od-section-badge { display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .od-table-shell { padding:0 22px 22px; }
        .od-table-scroll { overflow:auto; max-height:550px; border:1px solid #e8eef5; border-radius:16px; }
        .od-table-scroll .eob-list-table thead th { position:sticky; top:0; z-index:1; background:#f8fbff; }
        .od-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .od-status-pending { background:#fff7ed; color:#c2410c; }
        .od-status-approved { background:#f0fdf4; color:#15803d; }
        .od-status-rejected { background:#fef2f2; color:#b91c1c; }
        .od-status-skipped { background:#f4f4f5; color:#71717a; }
        .od-pill { display:inline-flex; align-items:center; padding:4px 9px; border-radius:999px; background:#eef4ff; color:#3355aa; font-size:11px; font-weight:800; }
        .od-meta-stack { display:flex; flex-direction:column; gap:2px; }
        .od-empty { padding:36px; text-align:center; color:#9e9e9e; font-size:13px; }
        @media (max-width: 1100px) {
            .od-summary-grid { grid-template-columns:1fr; }
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">OD Requests</div>
            <div class="eob-breadcrumb">HRMS > OD Requests</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back to HRMS</a>
            <a href="{{ route('od-requests.create') }}" class="eob-btn eob-btn-primary">New OD Request</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div class="od-page">
            {{-- Summary Cards (Clickable Tabs) --}}
            <div class="od-summary-grid">
                @if($pendingApprovals->isNotEmpty())
                <div class="od-summary-card" data-tab="od-approvals" onclick="switchOdTab('od-approvals')">
                    <div class="od-summary-label">Waiting For My Approval</div>
                    <div class="od-summary-value" style="color: #fe5f04;">{{ $pendingApprovals->count() }}</div>
                    <div class="od-summary-sub">OD requests currently sitting in your approval queue.</div>
                </div>
                @endif
                <div class="od-summary-card" data-tab="od-my-requests" onclick="switchOdTab('od-my-requests')">
                    <div class="od-summary-label">My OD Requests</div>
                    <div class="od-summary-value" style="color: #0d9488;">{{ $odRequests->total() }}</div>
                    <div class="od-summary-sub">All On Duty permission requests submitted by you.</div>
                </div>
                @if($handledApprovals->isNotEmpty())
                <div class="od-summary-card" data-tab="od-handled" onclick="switchOdTab('od-handled')">
                    <div class="od-summary-label">Recent Decisions</div>
                    <div class="od-summary-value" style="color: #6366f1;">{{ $handledApprovals->count() }}</div>
                    <div class="od-summary-sub">OD requests you have approved or rejected recently.</div>
                </div>
                @endif
            </div>

            {{-- Nav Tabs --}}
            <div class="od-nav" role="tablist">
                @if($pendingApprovals->isNotEmpty())
                <button type="button" class="od-nav-link" data-tab="od-approvals" onclick="switchOdTab('od-approvals')">
                    <span>Waiting For Me</span>
                    <span class="od-nav-count">{{ $pendingApprovals->count() }}</span>
                </button>
                @endif
                <button type="button" class="od-nav-link" data-tab="od-my-requests" onclick="switchOdTab('od-my-requests')">
                    <span>My Requests</span>
                    <span class="od-nav-count">{{ $odRequests->total() }}</span>
                </button>
                @if($handledApprovals->isNotEmpty())
                <button type="button" class="od-nav-link" data-tab="od-handled" onclick="switchOdTab('od-handled')">
                    <span>Recently Handled</span>
                    <span class="od-nav-count">{{ $handledApprovals->count() }}</span>
                </button>
                @endif
            </div>

            {{-- Tab 1: Waiting For My Approval --}}
            @if($pendingApprovals->isNotEmpty())
            <div id="od-approvals" class="eob-table-card od-tab-panel">
                <div class="od-section-head">
                    <div>
                        <div class="od-section-title">Waiting For My Approval</div>
                        <div class="od-section-sub">OD requests currently waiting for your level approval.</div>
                    </div>
                    <div class="od-section-badge">{{ $pendingApprovals->count() }} pending</div>
                </div>

                <div class="od-table-shell">
                    <div class="od-table-scroll">
                        <table class="eob-list-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>OD Dates</th>
                                    <th>Total Days</th>
                                    <th>Current Stage</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingApprovals as $approval)
                                    <tr>
                                        <td>
                                            <div class="od-meta-stack">
                                                <div class="eob-cell-title">{{ $approval->odRequest->employee?->name ?: $approval->odRequest->user?->name }}</div>
                                                <div class="eob-cell-sub">{{ $approval->odRequest->employee?->employee_id ?: $approval->odRequest->user?->email }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="od-meta-stack">
                                                <div class="eob-cell-title">{{ $approval->odRequest->from_date->format('d M Y') }} - {{ $approval->odRequest->to_date->format('d M Y') }}</div>
                                                @if($approval->odRequest->gate_out_time || $approval->odRequest->gate_in_time)
                                                    <div class="eob-cell-sub" style="color: #6b7280; font-size: 11px;">
                                                        Gate: {{ $approval->odRequest->gate_out_time ? \Carbon\Carbon::parse($approval->odRequest->gate_out_time)->format('h:i A') : '-' }} - {{ $approval->odRequest->gate_in_time ? \Carbon\Carbon::parse($approval->odRequest->gate_in_time)->format('h:i A') : '-' }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="od-pill">{{ $approval->odRequest->total_days }} day(s)</span>
                                        </td>
                                        <td><span class="od-pill">{{ $approval->step_name }}</span></td>
                                        <td>{{ optional($approval->odRequest->submitted_at)->format('d M Y, h:i A') }}</td>
                                        <td>
                                            <a href="{{ route('od-requests.show', $approval->odRequest) }}" class="eob-btn eob-btn-primary eob-btn-sm">Review</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Tab 2: My OD Requests --}}
            <div id="od-my-requests" class="eob-table-card od-tab-panel">
                <div class="od-section-head">
                    <div>
                        <div class="od-section-title">My OD Requests</div>
                        <div class="od-section-sub">Track whether your OD request is pending approval, approved, or rejected.</div>
                    </div>
                    <div class="od-section-badge">{{ $odRequests->total() }} request(s)</div>
                </div>

                @if($odRequests->isEmpty())
                    <div class="od-empty">No OD requests submitted yet. Click "New OD Request" to submit one.</div>
                @else
                    <div class="od-table-shell">
                        <div class="od-table-scroll">
                            <table class="eob-list-table">
                                <thead>
                                    <tr>
                                        <th>OD Dates</th>
                                        <th>Total Days</th>
                                        <th>Status</th>
                                        <th>Current Stage</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($odRequests as $odRequest)
                                        @php
                                             $currentApproval = $odRequest->approvals->firstWhere('step_key', $odRequest->current_step);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="od-meta-stack">
                                                    <div class="eob-cell-title">{{ $odRequest->from_date->format('d M Y') }} - {{ $odRequest->to_date->format('d M Y') }}</div>
                                                    @if($odRequest->gate_out_time || $odRequest->gate_in_time)
                                                        <div class="eob-cell-sub" style="color: #6b7280; font-size: 11px;">
                                                            Gate: {{ $odRequest->gate_out_time ? \Carbon\Carbon::parse($odRequest->gate_out_time)->format('h:i A') : '-' }} - {{ $odRequest->gate_in_time ? \Carbon\Carbon::parse($odRequest->gate_in_time)->format('h:i A') : '-' }}
                                                        </div>
                                                    @endif
                                                    <div class="eob-cell-sub" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $odRequest->reason }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="od-pill">{{ $odRequest->total_days }} day(s)</span>
                                            </td>
                                            <td>
                                                <span class="od-status od-status-{{ $odRequest->status }}">{{ $odRequest->status }}</span>
                                            </td>
                                            <td>
                                                @if($odRequest->status === 'pending')
                                                    {{ $currentApproval?->step_name ?: 'In Approval Flow' }}
                                                @elseif($odRequest->status === 'approved')
                                                    <span style="color: #15803d; font-weight: 700;">Completed & Attendance Marked</span>
                                                @else
                                                    <span style="color: #b91c1c; font-weight: 700;">Rejected</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($odRequest->submitted_at)->format('d M Y') }}</td>
                                            <td>
                                                <a href="{{ route('od-requests.show', $odRequest) }}" class="eob-btn eob-btn-ghost eob-btn-sm">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($odRequests->hasPages())
                        @include('partials.table-pagination', ['paginator' => $odRequests])
                    @endif
                @endif
            </div>

            {{-- Tab 3: Handled Approvals --}}
            @if($handledApprovals->isNotEmpty())
            <div id="od-handled" class="eob-table-card od-tab-panel">
                <div class="od-section-head">
                    <div>
                        <div class="od-section-title">Recently Handled By Me</div>
                        <div class="od-section-sub">OD requests you have approved or rejected.</div>
                    </div>
                </div>

                <div class="od-table-shell">
                    <div class="od-table-scroll">
                        <table class="eob-list-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Dates</th>
                                    <th>My Action</th>
                                    <th>Actioned At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($handledApprovals as $handled)
                                    <tr>
                                        <td>
                                            <div class="od-meta-stack">
                                                <div class="eob-cell-title">{{ $handled->odRequest?->employee?->name ?: $handled->odRequest?->user?->name }}</div>
                                                <div class="eob-cell-sub">{{ $handled->odRequest?->employee?->employee_id ?: $handled->odRequest?->user?->email }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ optional($handled->odRequest?->from_date)->format('d M Y') }} - {{ optional($handled->odRequest?->to_date)->format('d M Y') }}
                                        </td>
                                        <td>
                                            <span class="od-status od-status-{{ $handled->status }}">{{ $handled->status }}</span>
                                        </td>
                                        <td>{{ optional($handled->actioned_at)->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchOdTab(tabId) {
    var targetPanel = document.getElementById(tabId);
    if (!targetPanel) return;

    // Toggle nav buttons
    document.querySelectorAll('.od-nav-link').forEach(function(btn) {
        if (btn.getAttribute('data-tab') === tabId) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Toggle summary cards
    document.querySelectorAll('.od-summary-card').forEach(function(card) {
        if (card.getAttribute('data-tab') === tabId) {
            card.classList.add('active');
        } else {
            card.classList.remove('active');
        }
    });

    // Toggle tab panels
    document.querySelectorAll('.od-tab-panel').forEach(function(panel) {
        if (panel.id === tabId) {
            panel.classList.add('active');
        } else {
            panel.classList.remove('active');
        }
    });

    // Update URL hash
    if (history.replaceState) {
        history.replaceState(null, null, '#' + tabId);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var hash = window.location.hash ? window.location.hash.substring(1) : '';
    var initialTab = null;

    if (hash && document.getElementById(hash)) {
        initialTab = hash;
    } else if (new URLSearchParams(window.location.search).has('requests_page')) {
        initialTab = 'od-my-requests';
    } else {
        @if($pendingApprovals->isNotEmpty())
            initialTab = 'od-approvals';
        @else
            initialTab = 'od-my-requests';
        @endif
    }

    if (initialTab && document.getElementById(initialTab)) {
        switchOdTab(initialTab);
    } else {
        var firstPanel = document.querySelector('.od-tab-panel');
        if (firstPanel) {
            switchOdTab(firstPanel.id);
        }
    }
});
</script>
@endpush
