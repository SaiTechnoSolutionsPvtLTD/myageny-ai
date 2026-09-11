@extends('layouts.app')

@section('title', 'Leave Requests')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .lr-page { display:flex; flex-direction:column; gap:18px; }
        .lr-summary-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
        .lr-summary-grid.lr-summary-grid-single { grid-template-columns:minmax(0, 1fr); }
        .lr-summary-card {
            background:linear-gradient(135deg, #fffaf5 0%, #ffffff 100%);
            border:1px solid #f1e5d7;
            border-radius:18px;
            padding:18px;
            box-shadow:0 14px 28px rgba(18, 18, 18, 0.04);
            cursor:pointer;
            transition:transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .lr-summary-card:hover {
            transform:translateY(-2px);
            box-shadow:0 16px 32px rgba(18, 18, 18, 0.08);
            border-color:#fdba74;
        }
        .lr-summary-card.active {
            border-color:#ea580c;
            background:linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            box-shadow:0 16px 32px rgba(234, 88, 12, 0.08);
        }
        .lr-summary-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#9a6b39; }
        .lr-summary-value { margin-top:10px; font-size:28px; font-weight:800; color:#121212; line-height:1; }
        .lr-summary-sub { margin-top:8px; font-size:12px; color:#7a7a7a; line-height:1.5; }
        
        /* Tab Navigation */
        .lr-nav { display:flex; flex-wrap:wrap; gap:10px; border-bottom:1px solid #f0eef2; padding-bottom:14px; }
        .lr-nav-link {
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 18px;
            border-radius:999px;
            border:1px solid #eadfce;
            background:#fff;
            color:#6b5b46;
            text-decoration:none;
            font-size:13px;
            font-weight:800;
            cursor:pointer;
            transition:all 0.2s ease;
        }
        .lr-nav-link:hover {
            background:#fff7ed;
            border-color:#fdba74;
            color:#c2410c;
        }
        .lr-nav-link.active {
            background:#ea580c;
            border-color:#ea580c;
            color:#ffffff;
            box-shadow:0 6px 16px rgba(234, 88, 12, 0.28);
        }
        .lr-nav-count {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:24px;
            height:24px;
            padding:0 7px;
            border-radius:999px;
            background:#f6efe7;
            color:#8a5b2f;
            font-size:11px;
            font-weight:700;
            transition:all 0.2s ease;
        }
        .lr-nav-link.active .lr-nav-count {
            background:rgba(255, 255, 255, 0.25);
            color:#ffffff;
        }
        
        /* Tab Panels */
        .lr-tab-panel {
            display:none;
            animation:lrFadeIn 0.25s ease;
        }
        .lr-tab-panel.active {
            display:block;
        }
        @keyframes lrFadeIn {
            from { opacity:0; transform:translateY(6px); }
            to { opacity:1; transform:translateY(0); }
        }

        .lr-section-head { padding:18px 22px; border-bottom:1px solid #f0eef2; display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
        .lr-section-title { font-size:16px; font-weight:800; color:#121212; }
        .lr-section-sub { margin-top:4px; font-size:12px; color:#8c8c8c; line-height:1.5; }
        .lr-section-badge { display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px; background:#fff7ed; color:#c2410c; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .lr-table-shell { padding:0 22px 22px; }
        .lr-table-scroll { overflow:auto; max-height:550px; border:1px solid #f3ede7; border-radius:16px; }
        .lr-table-scroll .eob-list-table thead th { position:sticky; top:0; z-index:1; background:#fcfaf8; }
        .lr-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .lr-status-pending { background:#fff7ed; color:#c2410c; }
        .lr-status-approved { background:#f0fdf4; color:#15803d; }
        .lr-status-rejected { background:#fef2f2; color:#b91c1c; }
        .lr-status-skipped { background:#f4f4f5; color:#71717a; }
        .lr-pill { display:inline-flex; align-items:center; padding:4px 9px; border-radius:999px; background:#eef4ff; color:#3355aa; font-size:11px; font-weight:800; }
        .lr-meta-stack { display:flex; flex-direction:column; gap:2px; }
        .lr-empty { padding:36px; text-align:center; color:#9e9e9e; font-size:13px; }
        @media (max-width: 1100px) {
            .lr-summary-grid { grid-template-columns:1fr; }
        }

        /* Filter Panel & Quick Filters */
        .lr-filter-card {
            background: #ffffff;
            border: 1px solid #f1e5d7;
            border-radius: 18px;
            padding: 20px 24px;
            box-shadow: 0 10px 30px rgba(18, 18, 18, 0.04);
            margin-bottom: 6px;
        }
        .lr-qfilter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }
        .lr-qfilter-btn:hover {
            border-color: #fb923c;
            background: #fff7ed;
            color: #c2410c;
        }
        .lr-qfilter-btn.active {
            border-color: #ea580c;
            background: #ea580c;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);
        }
        .lr-filter-grid {
            display: grid;
            grid-template-columns: 1.4fr 1.2fr 1fr 1fr 1fr auto;
            gap: 14px;
            align-items: flex-end;
        }
        .lr-filter-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .lr-filter-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #4b5563;
        }
        .lr-filter-input {
            width: 100%;
            height: 42px;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            outline: none;
            background: #fff;
            font-family: inherit;
            color: #1f2937;
            transition: border-color .15s, box-shadow .15s;
            box-sizing: border-box;
        }
        select.lr-filter-input {
            cursor: pointer;
        }
        .lr-filter-input:focus {
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
        }
        @media (max-width: 1200px) {
            .lr-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .lr-filter-actions-field {
                grid-column: span 2;
            }
        }
        @media (max-width: 640px) {
            .lr-filter-grid {
                grid-template-columns: 1fr;
            }
            .lr-filter-actions-field {
                grid-column: span 1;
            }
        }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Leave Requests</div>
            <div class="eob-breadcrumb">HRMS > Leave Requests</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('leave-requests.create') }}" class="eob-btn eob-btn-primary">New Leave Request</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div class="lr-page">
            @if($isCompanyAdmin ?? false)
            {{-- Filter Panel for Admin / HR --}}
            <div class="lr-filter-card">
                <form method="GET" action="{{ route('leave-requests.index') }}" id="leaveFilterForm">
                    <input type="hidden" name="quick_filter" id="leave_quick_filter_input" value="{{ request('quick_filter') }}">
                    
                    {{-- Quick Filters Row --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px; padding-bottom:14px; border-bottom:1px solid #f1f5f9;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#9a6b39; margin-right:4px;">Quick Filters:</span>
                            <button type="button" class="lr-qfilter-btn {{ !request('quick_filter') && !request('date_from') && !request('date_to') ? 'active' : '' }}" onclick="applyLeaveQuickFilter('')">All</button>
                            <button type="button" class="lr-qfilter-btn {{ request('quick_filter') === 'today' ? 'active' : '' }}" onclick="applyLeaveQuickFilter('today')">Today</button>
                            <button type="button" class="lr-qfilter-btn {{ request('quick_filter') === 'tomorrow' ? 'active' : '' }}" onclick="applyLeaveQuickFilter('tomorrow')">Tomorrow</button>
                            <button type="button" class="lr-qfilter-btn {{ request('quick_filter') === 'weekly' ? 'active' : '' }}" onclick="applyLeaveQuickFilter('weekly')">Weekly</button>
                            <button type="button" class="lr-qfilter-btn {{ request('quick_filter') === 'monthly' ? 'active' : '' }}" onclick="applyLeaveQuickFilter('monthly')">Monthly</button>
                            <button type="button" class="lr-qfilter-btn {{ request('quick_filter') === 'year' ? 'active' : '' }}" onclick="applyLeaveQuickFilter('year')">Year</button>
                        </div>
                        @if(request()->hasAny(['quick_filter', 'employee_id', 'department_id', 'date_from', 'date_to', 'status']))
                            <a href="{{ route('leave-requests.index') }}" style="font-size:12px; font-weight:700; color:#ea580c; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                ✕ Clear All Filters
                            </a>
                        @endif
                    </div>

                    {{-- Detailed Filters Grid --}}
                    <div class="lr-filter-grid">
                        <div class="lr-filter-field">
                            <label class="lr-filter-label">Employee</label>
                            <select name="employee_id" class="lr-filter-input">
                                <option value="">All Employees</option>
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} {{ $emp->employee_id ? "({$emp->employee_id})" : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lr-filter-field">
                            <label class="lr-filter-label">Department</label>
                            <select name="department_id" class="lr-filter-input">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lr-filter-field">
                            <label class="lr-filter-label">Status</label>
                            <select name="status" class="lr-filter-input">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>

                        <div class="lr-filter-field">
                            <label class="lr-filter-label">From Date</label>
                            <input type="date" name="date_from" class="lr-filter-input" value="{{ request('date_from') }}">
                        </div>

                        <div class="lr-filter-field">
                            <label class="lr-filter-label">To Date</label>
                            <input type="date" name="date_to" class="lr-filter-input" value="{{ request('date_to') }}">
                        </div>

                        <div class="lr-filter-field lr-filter-actions-field">
                            <label class="lr-filter-label">&nbsp;</label>
                            <div style="display:flex; gap:8px;">
                                <button type="submit" class="eob-btn eob-btn-primary" style="flex:1; height:42px; justify-content:center; border-radius:10px; font-size:13px; font-weight:700;">
                                    Apply Filter
                                </button>
                                @if(request()->hasAny(['employee_id', 'department_id', 'date_from', 'date_to', 'status', 'quick_filter']))
                                <a href="{{ route('leave-requests.index') }}" class="eob-btn" style="height:42px; padding:0 14px; background:#f8fafc; border:1px solid #d1d5db; color:#6b7280; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
                                    Reset
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            @else
            {{-- Summary Cards (Clickable Tabs) --}}
            <div class="lr-summary-grid">
                @if(auth()->user()->can('leave_requests.approve') || $pendingApprovals->isNotEmpty())
                <div class="lr-summary-card" data-tab="leave-approvals" onclick="switchLeaveTab('leave-approvals')">
                    <div class="lr-summary-label">Waiting For Me</div>
                    <div class="lr-summary-value" style="color: #ea580c;">{{ $pendingApprovals->count() }}</div>
                    <div class="lr-summary-sub">Leave approvals currently blocked at your stage.</div>
                </div>
                @endif
                <div class="lr-summary-card" data-tab="leave-my-requests" onclick="switchLeaveTab('leave-my-requests')">
                    <div class="lr-summary-label">{{ ($isCompanyAdmin ?? false) ? 'All Requests' : 'My Requests' }}</div>
                    <div class="lr-summary-value" style="color: #0d9488;">{{ $leaveRequests->total() }}</div>
                    <div class="lr-summary-sub">{{ ($isCompanyAdmin ?? false) ? 'All employee leave requests across the company.' : 'Your leave requests with current approval status.' }}</div>
                </div>
                @if(auth()->user()->can('leave_requests.approve') || $handledApprovals->isNotEmpty())
                <div class="lr-summary-card" data-tab="leave-decisions" onclick="switchLeaveTab('leave-decisions')">
                    <div class="lr-summary-label">Recent Decisions</div>
                    <div class="lr-summary-value" style="color: #6366f1;">{{ $handledApprovals->count() }}</div>
                    <div class="lr-summary-sub">Approvals or rejections you completed recently.</div>
                </div>
                @endif
            </div>
            @endif

            {{-- Nav Tabs --}}
            @if(! ($isCompanyAdmin ?? false))
            <div class="lr-nav" role="tablist">
                @if(auth()->user()->can('leave_requests.approve') || $pendingApprovals->isNotEmpty())
                <button type="button" class="lr-nav-link" data-tab="leave-approvals" onclick="switchLeaveTab('leave-approvals')">
                    <span>Waiting For My Approval</span>
                    <span class="lr-nav-count">{{ $pendingApprovals->count() }}</span>
                </button>
                @endif
                <button type="button" class="lr-nav-link" data-tab="leave-my-requests" onclick="switchLeaveTab('leave-my-requests')">
                    <span>My Leave Requests</span>
                    <span class="lr-nav-count">{{ $leaveRequests->total() }}</span>
                </button>
                @if(auth()->user()->can('leave_requests.approve') || $handledApprovals->isNotEmpty())
                <button type="button" class="lr-nav-link" data-tab="leave-decisions" onclick="switchLeaveTab('leave-decisions')">
                    <span>My Recent Decisions</span>
                    <span class="lr-nav-count">{{ $handledApprovals->count() }}</span>
                </button>
                @endif
            </div>

            {{-- Tab 1: Waiting For My Approval --}}
            @if(auth()->user()->can('leave_requests.approve') || $pendingApprovals->isNotEmpty())
            <div id="leave-approvals" class="eob-table-card lr-tab-panel">
                <div class="lr-section-head">
                    <div>
                        <div class="lr-section-title">Waiting For My Approval</div>
                        <div class="lr-section-sub">Requests currently assigned to your approval stage.</div>
                    </div>
                    <div class="lr-section-badge">{{ $pendingApprovals->count() }} pending</div>
                </div>

                @if($pendingApprovals->isEmpty())
                    <div class="lr-empty">No leave approvals are waiting for you.</div>
                @else
                    <div class="lr-table-shell">
                        <div class="lr-table-scroll">
                            <table class="eob-list-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Leave Type</th>
                                        <th>Dates</th>
                                        <th>Stage</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingApprovals as $approval)
                                        <tr>
                                            <td>
                                                <div class="lr-meta-stack">
                                                    <div class="eob-cell-title">{{ $approval->leaveRequest->employee?->name ?: $approval->leaveRequest->user?->name }}</div>
                                                    <div class="eob-cell-sub">{{ $approval->leaveRequest->employee?->employee_id ?: $approval->leaveRequest->user?->email }}</div>
                                                </div>
                                            </td>
                                            <td>{{ $approval->leaveRequest->leaveType?->name }}</td>
                                            <td>
                                                <div class="lr-meta-stack">
                                                    <div class="eob-cell-title">{{ $approval->leaveRequest->start_date->format('d M Y') }} - {{ $approval->leaveRequest->end_date->format('d M Y') }}</div>
                                                    <div class="eob-cell-sub">{{ $approval->leaveRequest->total_days }} day(s)</div>
                                                </div>
                                            </td>
                                            <td><span class="lr-pill">{{ $approval->step_name }}</span></td>
                                            <td>{{ optional($approval->leaveRequest->submitted_at)->format('d M Y') }}</td>
                                            <td>
                                                <a href="{{ route('leave-requests.show', $approval->leaveRequest) }}" class="eob-btn eob-btn-primary eob-btn-sm">Review</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
            @endif
            @endif

            {{-- Tab 2: Leave Requests Table --}}
            <div id="leave-my-requests" class="eob-table-card @if(! ($isCompanyAdmin ?? false)) lr-tab-panel @endif">
                <div class="lr-section-head">
                    <div>
                        <div class="lr-section-title">{{ ($isCompanyAdmin ?? false) ? 'All Leave Requests' : 'My Leave Requests' }}</div>
                        <div class="lr-section-sub">{{ ($isCompanyAdmin ?? false) ? 'Monitor all company leave requests and their hierarchy approval status.' : 'Check whether your leave request is pending, approved, or rejected.' }}</div>
                    </div>
                    <div class="lr-section-badge">{{ $leaveRequests->total() }} request(s)</div>
                </div>

                @if($leaveRequests->isEmpty())
                    <div class="lr-empty">No leave requests submitted yet. Click "New Leave Request" to submit one.</div>
                @else
                    <div class="lr-table-shell">
                        <div class="lr-table-scroll">
                            <table class="eob-list-table">
                                <thead>
                                    <tr>
                                        @if($isCompanyAdmin ?? false)
                                            <th>Employee</th>
                                        @endif
                                        <th>Leave Type</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Current Stage</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leaveRequests as $leaveRequest)
                                        @php
                                            $currentApproval = $leaveRequest->approvals->firstWhere('step_key', $leaveRequest->current_step);
                                        @endphp
                                        <tr>
                                            @if($isCompanyAdmin ?? false)
                                                <td>
                                                    <div class="lr-meta-stack">
                                                        <div class="eob-cell-title">{{ $leaveRequest->employee?->name ?: $leaveRequest->user?->name }}</div>
                                                        <div class="eob-cell-sub">{{ $leaveRequest->employee?->employee_id ?: $leaveRequest->user?->email }}</div>
                                                    </div>
                                                </td>
                                            @endif
                                            <td>{{ $leaveRequest->leaveType?->name }}</td>
                                            <td>
                                                <div class="lr-meta-stack">
                                                    <div class="eob-cell-title">{{ $leaveRequest->start_date->format('d M Y') }} - {{ $leaveRequest->end_date->format('d M Y') }}</div>
                                                    <div class="eob-cell-sub">{{ $leaveRequest->total_days }} day(s)</div>
                                                </div>
                                            </td>
                                            <td><span class="lr-status lr-status-{{ $leaveRequest->status }}">{{ $leaveRequest->status }}</span></td>
                                            <td>
                                                @if($leaveRequest->status === 'pending')
                                                    {{ $currentApproval?->step_name ?: 'Waiting' }}
                                                @elseif($leaveRequest->status === 'approved')
                                                    Completed
                                                @else
                                                    Rejected
                                                @endif
                                            </td>
                                            <td>{{ optional($leaveRequest->submitted_at)->format('d M Y') }}</td>
                                            <td>
                                                <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="eob-icon-btn" title="View Status"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($leaveRequests->hasPages())
                        @include('partials.table-pagination', ['paginator' => $leaveRequests])
                    @endif
                @endif
            </div>

            {{-- Tab 3: My Recent Decisions --}}
            @if(! ($isCompanyAdmin ?? false) && (auth()->user()->can('leave_requests.approve') || $handledApprovals->isNotEmpty()))
            <div id="leave-decisions" class="eob-table-card lr-tab-panel">
                <div class="lr-section-head">
                    <div>
                        <div class="lr-section-title">My Recent Decisions</div>
                        <div class="lr-section-sub">Approvals or rejections you completed recently.</div>
                    </div>
                    <div class="lr-section-badge">{{ $handledApprovals->count() }} item(s)</div>
                </div>

                @if($handledApprovals->isEmpty())
                    <div class="lr-empty">No approval decisions recorded yet.</div>
                @else
                    <div class="lr-table-shell">
                        <div class="lr-table-scroll">
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
                                                <div class="lr-meta-stack">
                                                    <div class="eob-cell-title">{{ $approval->leaveRequest->employee?->name ?: $approval->leaveRequest->user?->name }}</div>
                                                    <div class="eob-cell-sub">{{ $approval->leaveRequest->leaveType?->name }}</div>
                                                </div>
                                            </td>
                                            <td>{{ $approval->step_name }}</td>
                                            <td><span class="lr-status lr-status-{{ $approval->status }}">{{ $approval->status }}</span></td>
                                            <td>{{ optional($approval->actioned_at)->format('d M Y h:i A') }}</td>
                                            <td>
                                                <a href="{{ route('leave-requests.show', $approval->leaveRequest) }}" class="eob-icon-btn" title="View"><i class="bi bi-eye"></i></a>
                                            </td>
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
function applyLeaveQuickFilter(val) {
    var input = document.getElementById('leave_quick_filter_input');
    if (input) {
        input.value = val;
    }
    if (val) {
        var fromEl = document.querySelector('input[name="date_from"]');
        var toEl = document.querySelector('input[name="date_to"]');
        if (fromEl) fromEl.value = '';
        if (toEl) toEl.value = '';
    }
    var form = document.getElementById('leaveFilterForm');
    if (form) {
        form.submit();
    }
}

function switchLeaveTab(tabId) {
    var targetPanel = document.getElementById(tabId);
    if (!targetPanel) return;

    // Toggle nav buttons
    document.querySelectorAll('.lr-nav-link').forEach(function(btn) {
        if (btn.getAttribute('data-tab') === tabId) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Toggle summary cards
    document.querySelectorAll('.lr-summary-card').forEach(function(card) {
        if (card.getAttribute('data-tab') === tabId) {
            card.classList.add('active');
        } else {
            card.classList.remove('active');
        }
    });

    // Toggle tab panels
    document.querySelectorAll('.lr-tab-panel').forEach(function(panel) {
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
    var isCompanyAdmin = {{ ($isCompanyAdmin ?? false) ? 'true' : 'false' }};
    if (isCompanyAdmin) {
        return;
    }

    var hash = window.location.hash ? window.location.hash.substring(1) : '';
    var initialTab = null;
    var params = new URLSearchParams(window.location.search);
    var hasFilterParams = params.has('requests_page') || params.has('quick_filter') || params.has('employee_id') || params.has('department_id') || params.has('date_from') || params.has('date_to') || params.has('status');

    if (hash && document.getElementById(hash)) {
        initialTab = hash;
    } else if (hasFilterParams) {
        initialTab = 'leave-my-requests';
    } else {
        @if(auth()->user()->can('leave_requests.approve') || $pendingApprovals->isNotEmpty())
            @if($pendingApprovals->isNotEmpty())
                initialTab = 'leave-approvals';
            @else
                initialTab = 'leave-my-requests';
            @endif
        @else
            initialTab = 'leave-my-requests';
        @endif
    }

    if (initialTab && document.getElementById(initialTab)) {
        switchLeaveTab(initialTab);
    } else {
        var firstPanel = document.querySelector('.lr-tab-panel');
        if (firstPanel) {
            switchLeaveTab(firstPanel.id);
        }
    }
});
</script>
@endpush
