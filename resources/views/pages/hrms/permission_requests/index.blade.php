@extends('layouts.app')

@section('title', 'Permission Requests')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .pr-page { display:flex; flex-direction:column; gap:18px; }
        .pr-summary-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
        .pr-summary-card {
            background:linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
            border:1px solid #dce9f8;
            border-radius:18px;
            padding:18px;
            box-shadow:0 14px 28px rgba(18, 18, 18, 0.04);
            cursor:pointer;
            transition:transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .pr-summary-card:hover {
            transform:translateY(-2px);
            box-shadow:0 16px 32px rgba(18, 18, 18, 0.08);
            border-color:#93c5fd;
        }
        .pr-summary-card.active {
            border-color:#1d4ed8;
            background:linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
            box-shadow:0 16px 32px rgba(29, 78, 216, 0.08);
        }
        .pr-summary-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#355e8b; }
        .pr-summary-value { margin-top:10px; font-size:28px; font-weight:800; color:#121212; line-height:1; }
        .pr-summary-sub { margin-top:8px; font-size:12px; color:#7a7a7a; line-height:1.5; }
        
        /* Tab Navigation */
        .pr-nav { display:flex; flex-wrap:wrap; gap:10px; border-bottom:1px solid #f0eef2; padding-bottom:14px; }
        .pr-nav-link {
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
        .pr-nav-link:hover {
            background:#eff6ff;
            border-color:#93c5fd;
            color:#1d4ed8;
        }
        .pr-nav-link.active {
            background:#1d4ed8;
            border-color:#1d4ed8;
            color:#ffffff;
            box-shadow:0 6px 16px rgba(29, 78, 216, 0.28);
        }
        .pr-nav-count {
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
        .pr-nav-link.active .pr-nav-count {
            background:rgba(255, 255, 255, 0.25);
            color:#ffffff;
        }

        /* Tab Panels */
        .pr-tab-panel {
            display:none;
            animation:prFadeIn 0.25s ease;
        }
        .pr-tab-panel.active {
            display:block;
        }
        @keyframes prFadeIn {
            from { opacity:0; transform:translateY(6px); }
            to { opacity:1; transform:translateY(0); }
        }

        .pr-section-head { padding:18px 22px; border-bottom:1px solid #f0eef2; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
        .pr-section-title { font-size:16px; font-weight:800; color:#121212; }
        .pr-section-sub { margin-top:4px; font-size:12px; color:#8c8c8c; line-height:1.5; }
        .pr-section-badge { display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .pr-table-shell { padding:0 22px 22px; }
        .pr-table-scroll { overflow:auto; max-height:550px; border:1px solid #e8eef5; border-radius:16px; }
        .pr-table-scroll .eob-list-table thead th { position:sticky; top:0; z-index:1; background:#f8fbff; }
        .pr-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .pr-status-pending { background:#fff7ed; color:#c2410c; }
        .pr-status-approved { background:#f0fdf4; color:#15803d; }
        .pr-status-rejected { background:#fef2f2; color:#b91c1c; }
        .pr-status-skipped { background:#f4f4f5; color:#71717a; }
        .pr-pill { display:inline-flex; align-items:center; padding:4px 9px; border-radius:999px; background:#eef4ff; color:#3355aa; font-size:11px; font-weight:800; }
        .pr-meta-stack { display:flex; flex-direction:column; gap:2px; }
        .pr-empty { padding:36px; text-align:center; color:#9e9e9e; font-size:13px; }
        @media (max-width: 1100px) {
            .pr-summary-grid { grid-template-columns:1fr; }
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
            {{-- Summary Cards (Clickable Tabs) --}}
            <div class="pr-summary-grid">
                @if(auth()->user()->can('permission_requests.approve') || $pendingApprovals->isNotEmpty())
                <div class="pr-summary-card" data-tab="permission-approvals" onclick="switchPermissionTab('permission-approvals')">
                    <div class="pr-summary-label">Waiting For Me</div>
                    <div class="pr-summary-value" style="color: #ea580c;">{{ $pendingApprovals->count() }}</div>
                    <div class="pr-summary-sub">Permission approvals currently sitting in your queue.</div>
                </div>
                @endif
                <div class="pr-summary-card" data-tab="permission-my-requests" onclick="switchPermissionTab('permission-my-requests')">
                    <div class="pr-summary-label">{{ ($isCompanyAdmin ?? false) ? 'All Requests' : 'My Requests' }}</div>
                    <div class="pr-summary-value" style="color: #0d9488;">{{ $permissionRequests->total() }}</div>
                    <div class="pr-summary-sub">{{ ($isCompanyAdmin ?? false) ? 'All employee permission requests across the company.' : 'All permission requests you have raised across dates and slots.' }}</div>
                </div>
                @if(auth()->user()->can('permission_requests.approve') || $handledApprovals->isNotEmpty())
                <div class="pr-summary-card" data-tab="permission-decisions" onclick="switchPermissionTab('permission-decisions')">
                    <div class="pr-summary-label">Recent Decisions</div>
                    <div class="pr-summary-value" style="color: #6366f1;">{{ $handledApprovals->count() }}</div>
                    <div class="pr-summary-sub">Permission decisions you completed recently.</div>
                </div>
                @endif
            </div>

            {{-- Nav Tabs --}}
            <div class="pr-nav" role="tablist">
                @if(auth()->user()->can('permission_requests.approve') || $pendingApprovals->isNotEmpty())
                <button type="button" class="pr-nav-link" data-tab="permission-approvals" onclick="switchPermissionTab('permission-approvals')">
                    <span>Waiting For My Approval</span>
                    <span class="pr-nav-count">{{ $pendingApprovals->count() }}</span>
                </button>
                @endif
                <button type="button" class="pr-nav-link" data-tab="permission-my-requests" onclick="switchPermissionTab('permission-my-requests')">
                    <span>{{ ($isCompanyAdmin ?? false) ? 'All Permission Requests' : 'My Permission Requests' }}</span>
                    <span class="pr-nav-count">{{ $permissionRequests->total() }}</span>
                </button>
                @if(auth()->user()->can('permission_requests.approve') || $handledApprovals->isNotEmpty())
                <button type="button" class="pr-nav-link" data-tab="permission-decisions" onclick="switchPermissionTab('permission-decisions')">
                    <span>My Recent Decisions</span>
                    <span class="pr-nav-count">{{ $handledApprovals->count() }}</span>
                </button>
                @endif
            </div>

            {{-- Tab 1: Waiting For My Approval --}}
            @if(auth()->user()->can('permission_requests.approve') || $pendingApprovals->isNotEmpty())
            <div id="permission-approvals" class="eob-table-card pr-tab-panel">
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
            @endif

            {{-- Tab 2: My Permission Requests --}}
            <div id="permission-my-requests" class="eob-table-card pr-tab-panel">
                <div class="pr-section-head">
                    <div>
                        <div class="pr-section-title">{{ ($isCompanyAdmin ?? false) ? 'All Permission Requests' : 'My Permission Requests' }}</div>
                        <div class="pr-section-sub">{{ ($isCompanyAdmin ?? false) ? 'Monitor all company permission requests and their hierarchy approval status.' : 'Track your permission request hierarchy approval status.' }}</div>
                    </div>
                    <div class="pr-section-badge">{{ $permissionRequests->total() }} request(s)</div>
                </div>

                @if($permissionRequests->isEmpty())
                    <div class="pr-empty">No permission requests submitted yet. Click "New Permission Request" to submit one.</div>
                @else
                    <div class="pr-table-shell">
                        <div class="pr-table-scroll">
                            <table class="eob-list-table">
                                <thead>
                                    <tr>
                                        @if($isCompanyAdmin ?? false)
                                            <th>Employee</th>
                                        @endif
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
                                            @if($isCompanyAdmin ?? false)
                                                <td>
                                                    <div class="pr-meta-stack">
                                                        <div class="eob-cell-title">{{ $permissionRequest->employee?->name ?: $permissionRequest->user?->name }}</div>
                                                        <div class="eob-cell-sub">{{ $permissionRequest->employee?->employee_id ?: $permissionRequest->user?->email }}</div>
                                                    </div>
                                                </td>
                                            @endif
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

            {{-- Tab 3: My Recent Decisions --}}
            @if(auth()->user()->can('permission_requests.approve') || $handledApprovals->isNotEmpty())
            <div id="permission-decisions" class="eob-table-card pr-tab-panel">
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
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchPermissionTab(tabId) {
    var targetPanel = document.getElementById(tabId);
    if (!targetPanel) return;

    // Toggle nav buttons
    document.querySelectorAll('.pr-nav-link').forEach(function(btn) {
        if (btn.getAttribute('data-tab') === tabId) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Toggle summary cards
    document.querySelectorAll('.pr-summary-card').forEach(function(card) {
        if (card.getAttribute('data-tab') === tabId) {
            card.classList.add('active');
        } else {
            card.classList.remove('active');
        }
    });

    // Toggle tab panels
    document.querySelectorAll('.pr-tab-panel').forEach(function(panel) {
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
        initialTab = 'permission-my-requests';
    } else {
        @if(auth()->user()->can('permission_requests.approve') || $pendingApprovals->isNotEmpty())
            @if($pendingApprovals->isNotEmpty())
                initialTab = 'permission-approvals';
            @else
                initialTab = 'permission-my-requests';
            @endif
        @else
            initialTab = 'permission-my-requests';
        @endif
    }

    if (initialTab && document.getElementById(initialTab)) {
        switchPermissionTab(initialTab);
    } else {
        var firstPanel = document.querySelector('.pr-tab-panel');
        if (firstPanel) {
            switchPermissionTab(firstPanel.id);
        }
    }
});
</script>
@endpush
