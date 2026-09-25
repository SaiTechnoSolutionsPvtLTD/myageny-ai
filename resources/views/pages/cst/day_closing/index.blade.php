@extends('layouts.app')

@section('title', 'Customer Success Department Day Closing')

@push('styles')
<style>
.sdc-page { min-height:100%; background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%); font-family:'Inter',sans-serif; }
.sdc-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #e2e8f0; }
.sdc-title { font-size:22px; font-weight:900; color:#0f172a; letter-spacing:-0.02em; }
.sdc-breadcrumb { font-size:12px; color:#64748b; margin-top:4px; display:flex; align-items:center; gap:6px; }
.sdc-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

.sdc-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:9px 15px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#0f172a; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.sdc-btn-primary { background:#fe5f04; border-color:#fe5f04; color:#fff; box-shadow:0 4px 12px rgba(254,95,4,0.2); }
.sdc-btn-primary:hover { background:#ea580c; border-color:#ea580c; color:#fff; }
.sdc-btn-outline { border-color:#fe5f04; color:#fe5f04; background:#fff; }
.sdc-btn-outline:hover { background:#fff7ed; }

.sdc-body { padding:22px 28px 36px; display:grid; gap:20px; }

/* KPI Grid */
.sdc-kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.sdc-kpi-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; box-shadow:0 4px 14px rgba(15,23,42,0.03); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.sdc-kpi-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,0.06); }
.sdc-kpi-card.highlight { border-color:#fed7aa; background:linear-gradient(135deg,#fff 0%,#fffaf5 100%); }
.sdc-kpi-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.sdc-kpi-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:#64748b; }
.sdc-kpi-icon { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.sdc-kpi-value { font-size:24px; font-weight:900; color:#0f172a; margin-top:10px; line-height:1; }
.sdc-kpi-sub { font-size:11px; color:#94a3b8; font-weight:600; margin-top:6px; }

/* Filter Card */
.sdc-filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; box-shadow:0 4px 14px rgba(15,23,42,0.03); }
.sdc-filter-form { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; align-items:end; }
.sdc-filter-group { display:grid; gap:6px; }
.sdc-label { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.07em; color:#64748b; }
.sdc-input, .sdc-select, .sdc-textarea { width:100%; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font-size:13.5px; color:#0f172a; transition:border-color 0.15s, box-shadow 0.15s; }
.sdc-input, .sdc-select { height:40px; padding:8px 12px; }
.sdc-textarea { padding:12px; resize:vertical; min-height:120px; line-height:1.55; }
.sdc-input:focus, .sdc-select:focus, .sdc-textarea:focus { outline:none; border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,0.14); }

/* Main Card & Table */
.sdc-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; box-shadow:0 8px 24px rgba(15,23,42,0.04); }
.sdc-card-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:18px 22px; border-bottom:1px solid #e2e8f0; background:#fbfdff; flex-wrap:wrap; }
.sdc-card-title { font-size:15px; font-weight:900; color:#0f172a; }
.sdc-card-sub { font-size:12px; color:#64748b; margin-top:2px; }

.sdc-table-wrap { overflow-x:auto; }
.sdc-table { width:100%; border-collapse:collapse; min-width:1050px; }
.sdc-table th { padding:12px 16px; background:#f8fafc; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.07em; color:#64748b; border-bottom:1px solid #e2e8f0; text-align:left; vertical-align:middle; }
.sdc-table td { padding:14px 16px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#0f172a; vertical-align:middle; }
.sdc-table tr:hover td { background:#fafcff; }

/* Badges */
.sdc-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:800; line-height:1.3; }
.sdc-badge.submitted { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.sdc-badge.reviewed { background:#fff7ed; color:#ea580c; border:1px solid #fed7aa; }
.sdc-badge.approved { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.sdc-badge.metric { background:#f8fafc; color:#334155; border:1px solid #cbd5e1; }

.status-dropdown {
    padding:4px 22px 4px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    border:1px solid transparent;
    cursor:pointer;
    outline:none;
    appearance:none;
    background-image:url("data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2210%22%20height%3D%2210%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22currentColor%22%20stroke-width%3D%223%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E");
    background-repeat:no-repeat;
    background-position:right 7px center;
    background-size:8px;
    transition:all 0.2s;
}
.status-dropdown.submitted { background-color:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.status-dropdown.reviewed { background-color:#fff7ed; color:#ea580c; border-color:#fed7aa; }
.status-dropdown.approved { background-color:#f0fdf4; color:#15803d; border-color:#bbf7d0; }

/* Modals */
.sdc-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,0.52); z-index:1200; display:none; backdrop-filter:blur(2px); }
.sdc-modal-overlay.is-open { display:block; }
.sdc-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(820px, calc(100vw - 32px)); max-height:calc(100vh - 40px); overflow-y:auto; background:#fff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 24px 60px rgba(15,23,42,0.22); z-index:1210; display:none; }
.sdc-modal.is-open { display:block; }
.sdc-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 22px; border-bottom:1px solid #e2e8f0; background:#fbfdff; }
.sdc-modal-close { width:36px; height:36px; border-radius:9px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:16px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:all 0.15s; }
.sdc-modal-close:hover { background:#f1f5f9; color:#0f172a; }
.sdc-modal-body { padding:22px; }

/* Tomorrow Plans Grid */
.sdc-tomorrow-plan-row { display:grid; grid-template-columns:1.2fr 1.2fr 140px 36px; gap:8px; align-items:center; background:#fff; padding:8px 10px; border:1px solid #e2e8f0; border-radius:8px; transition:border-color 0.15s; }
.sdc-tomorrow-plan-row:hover { border-color:#cbd5e1; }
@media (max-width: 640px) {
    .sdc-tomorrow-plan-row { grid-template-columns:1fr; }
}

@media (max-width: 768px) {
    .sdc-topbar { padding:18px 16px; flex-direction:column; }
    .sdc-body { padding:16px; }
    .sdc-kpi-grid { grid-template-columns:1fr 1fr; }
}
</style>
@endpush

@section('content')
<div class="sdc-page">
    <!-- Topbar -->
    <div class="sdc-topbar">
        <div>
            <h1 class="sdc-title">Customer Success Department Day Closing</h1>
            <div class="sdc-breadcrumb">
                <span>Customer Success</span>
                <span>/</span>
                <span style="font-weight:700; color:#0f172a;">Daily Closing Updates &amp; Department Performance</span>
            </div>
        </div>
        <div class="sdc-actions">
            <a href="{{ route('dashboard.customer-success') }}" class="sdc-btn" style="color:#475569;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <span>CST Dashboard</span>
            </a>
            <button type="button" class="sdc-btn sdc-btn-primary" data-open-closing-modal>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Day Closing Update</span>
            </button>
        </div>
    </div>

    <div class="sdc-body">
        <!-- Session Flashes -->
        @if(session('success'))
            <div style="padding:12px 16px; border-radius:10px; background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div style="padding:12px 16px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- CST 8 Core Performance Metrics (Selected Date: {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}) -->
        <div>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.06em; display:flex; align-items:center; gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fe5f04" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                    <span>CST Department Metrics ({{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }})</span>
                </div>
                <div style="font-size:12px; color:#64748b; font-weight:600;">
                    Real-time aggregated department metrics
                </div>
            </div>

            <div class="sdc-kpi-grid">
                <!-- 1. Monthly Target (Current Month Prospect Value default) -->
                <div class="sdc-kpi-card highlight">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Monthly Target</span>
                        <div class="sdc-kpi-icon" style="background:#fff7ed; color:#fe5f04;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#fe5f04;">₹{{ number_format($kpiStats['monthly_target']) }}</div>
                    <div class="sdc-kpi-sub">Current month prospect value</div>
                </div>

                <!-- 2. Today's Revenue (Today Received Value) -->
                <div class="sdc-kpi-card">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Today's Revenue</span>
                        <div class="sdc-kpi-icon" style="background:#f0fdf4; color:#16a34a;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#16a34a;">₹{{ number_format($kpiStats['today_revenue']) }}</div>
                    <div class="sdc-kpi-sub">Today received value</div>
                </div>

                <!-- 3. Till Now Achieved -->
                <div class="sdc-kpi-card">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Till Now Achieved</span>
                        <div class="sdc-kpi-icon" style="background:#eff6ff; color:#2563eb;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#2563eb;">₹{{ number_format($kpiStats['till_now_achieved']) }}</div>
                    <div class="sdc-kpi-sub">Month start to selected date</div>
                </div>

                <!-- 4. Completed Percentage -->
                <div class="sdc-kpi-card">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Completed Pct</span>
                        <div class="sdc-kpi-icon" style="background:#faf5ff; color:#9333ea;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><path d="M16 12l-4-4-4 4M12 16V9"></path></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#9333ea;">{{ number_format($kpiStats['completed_percentage'], 1) }}%</div>
                    <div class="sdc-kpi-sub">Achieved vs monthly target</div>
                </div>

                <!-- 5. Current Week Meetings -->
                <div class="sdc-kpi-card">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Week Meetings</span>
                        <div class="sdc-kpi-icon" style="background:#fefce8; color:#ca8a04;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#ca8a04;">{{ number_format($kpiStats['current_week_meetings']) }}</div>
                    <div class="sdc-kpi-sub">Meetings attended this week</div>
                </div>

                <!-- 6. Total Allocated Account -->
                <div class="sdc-kpi-card">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Total Allocated</span>
                        <div class="sdc-kpi-icon" style="background:#f0fdfa; color:#0d9488;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#0d9488;">{{ number_format($kpiStats['total_allocated_accounts']) }}</div>
                    <div class="sdc-kpi-sub">Overall unique accounts</div>
                </div>

                <!-- 7. Today's Added Account -->
                <div class="sdc-kpi-card">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Today's Added</span>
                        <div class="sdc-kpi-icon" style="background:#ecfeff; color:#0891b2;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#0891b2;">{{ number_format($kpiStats['today_added_accounts']) }}</div>
                    <div class="sdc-kpi-sub">Newly added accounts today</div>
                </div>

                <!-- 8. Welcome Call Pending Account Count -->
                <div class="sdc-kpi-card">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Welcome Call Pending</span>
                        <div class="sdc-kpi-icon" style="background:#fff1f2; color:#e11d48;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#e11d48;">{{ number_format($kpiStats['welcome_call_pending_count']) }}</div>
                    <div class="sdc-kpi-sub">Production initiated, call pending</div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="sdc-filter-card">
            <form method="GET" action="{{ route('cst.day-closing.index') }}" class="sdc-filter-form">
                <div class="sdc-filter-group">
                    <label class="sdc-label">Date</label>
                    <input type="date" name="date" value="{{ request('date', $selectedDate) }}" class="sdc-input">
                </div>

                @if($isAdminLike || $isTlLike)
                    <div class="sdc-filter-group">
                        <label class="sdc-label">CST Executive</label>
                        <select name="user_id" class="sdc-select">
                            <option value="">All Team Members</option>
                            @foreach($assignableUsers as $u)
                                <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sdc-filter-group">
                        <label class="sdc-label">Branch</label>
                        <select name="branch_id" class="sdc-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="sdc-filter-group">
                    <label class="sdc-label">Status</label>
                    <select name="status" class="sdc-select">
                        <option value="">All Statuses</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                        <option value="reviewed" @selected(request('status') === 'reviewed')>Reviewed</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    </select>
                </div>

                <div class="sdc-filter-group">
                    <label class="sdc-label">Search Keyword</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search remarks or executive..." class="sdc-input">
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="submit" class="sdc-btn sdc-btn-primary" style="height:40px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('cst.day-closing.index') }}" class="sdc-btn" style="height:40px; color:#64748b;" title="Reset filters">Reset</a>
                </div>
            </form>
        </div>

        <!-- Daily Closing Submissions Table -->
        <div class="sdc-card">
            <div class="sdc-card-head">
                <div>
                    <div class="sdc-card-title">Daily Closing Submissions</div>
                    <div class="sdc-card-sub">Review daily performance, remarks, tomorrow targets, and attachments.</div>
                </div>
            </div>

            <div class="sdc-table-wrap">
                <table class="sdc-table">
                    <thead>
                        <tr>
                            <th style="width:40px; text-align:center;">#</th>
                            <th style="width:130px;">Closing Date</th>
                            <th style="width:180px;">CST Executive</th>
                            <th style="width:310px;">CST Metrics Breakdown</th>
                            <th style="min-width:240px;">Remarks / Update</th>
                            <th style="width:200px;">Tomorrow&apos;s Plan &amp; Target</th>
                            <th style="width:120px; text-align:center;">Review Status</th>
                            <th style="width:130px; text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($closings as $idx => $closing)
                            @php
                                $closingData = [
                                    'id'                         => $closing->id,
                                    'user_id'                    => $closing->user_id,
                                    'user_name'                  => $closing->user?->name ?? 'Team Member',
                                    'designation'                => $closing->user?->designation ?? 'Customer Success',
                                    'branch_name'                => $closing->branch?->name ?? ($closing->user?->branch_name ?? 'Head Office'),
                                    'closing_date'               => $closing->closing_date?->toDateString(),
                                    'formatted_date'             => $closing->closing_date?->format('d M Y'),
                                    'monthly_target'             => (float) $closing->monthly_target,
                                    'today_revenue'              => (float) $closing->today_revenue,
                                    'till_now_achieved'          => (float) $closing->till_now_achieved,
                                    'completed_percentage'       => (float) $closing->completed_percentage,
                                    'current_week_meetings'      => (int) $closing->current_week_meetings,
                                    'total_allocated_accounts'   => (int) $closing->total_allocated_accounts,
                                    'today_added_accounts'       => (int) $closing->today_added_accounts,
                                    'welcome_call_pending_count' => (int) $closing->welcome_call_pending_count,
                                    'remarks'                    => $closing->remarks ?? '',
                                    'is_on_leave_tomorrow'       => (bool) $closing->is_on_leave_tomorrow,
                                    'tomorrow_plans'             => $closing->tomorrow_plans ?: [],
                                    'total_expected_value'       => (float) $closing->total_expected_value,
                                    'plan_for_tomorrow'          => $closing->plan_for_tomorrow ?? '',
                                    'attachments'                => $closing->attachment_list,
                                    'status'                     => $closing->status,
                                    'can_edit'                   => ($closing->user_id === auth()->id() || $isAdminLike || $isTlLike),
                                    'update_url'                 => route('cst.day-closing.update', $closing->id),
                                ];
                                $closingJson = json_encode($closingData);
                            @endphp
                            <tr data-closing-id="{{ $closing->id }}">
                                <td style="text-align:center; font-weight:800; color:#64748b;">
                                    {{ $closings->firstItem() + $idx }}
                                </td>
                                <td>
                                    <div style="font-weight:800; color:#0f172a;">{{ $closing->closing_date?->format('d M Y') }}</div>
                                    <div style="font-size:11px; color:#64748b;">{{ $closing->closing_date?->format('l') }}</div>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="width:32px; height:32px; border-radius:50%; background:#fff7ed; color:#fe5f04; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:12px; border:1px solid #fed7aa; flex-shrink:0;">
                                            {{ strtoupper(substr($closing->user?->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:800; color:#0f172a;">{{ $closing->user?->name ?? 'Team Member' }}</div>
                                            <div style="font-size:11px; color:#64748b;">{{ $closing->branch?->name ?? 'General' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; flex-wrap:wrap; gap:5px; max-width:320px;">
                                        <span class="sdc-badge metric" title="Monthly Target" style="background:#fff7ed; color:#c2410c; border-color:#fed7aa;">
                                            🎯 Target: ₹{{ number_format($closing->monthly_target) }}
                                        </span>
                                        <span class="sdc-badge metric" title="Today's Revenue" style="background:#f0fdf4; color:#15803d; border-color:#bbf7d0;">
                                            💰 Today: ₹{{ number_format($closing->today_revenue) }}
                                        </span>
                                        <span class="sdc-badge metric" title="Till Now Achieved" style="background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">
                                            📈 Achieved: ₹{{ number_format($closing->till_now_achieved) }}
                                        </span>
                                        <span class="sdc-badge metric" title="Completed Percentage" style="background:#faf5ff; color:#7e22ce; border-color:#e9d5ff;">
                                            ⚡ {{ number_format($closing->completed_percentage, 1) }}%
                                        </span>
                                        <span class="sdc-badge metric" title="Current Week Meetings">
                                            📅 Meetings: {{ $closing->current_week_meetings }}
                                        </span>
                                        <span class="sdc-badge metric" title="Total Allocated Accounts">
                                            👥 Alloc: {{ $closing->total_allocated_accounts }}
                                        </span>
                                        <span class="sdc-badge metric" title="Today's Added Accounts">
                                            ➕ Added: {{ $closing->today_added_accounts }}
                                        </span>
                                        @if($closing->welcome_call_pending_count > 0)
                                            <span class="sdc-badge metric" title="Welcome Call Pending" style="background:#fff1f2; color:#be123c; border-color:#fecdd3;">
                                                📞 Welcome: {{ $closing->welcome_call_pending_count }} pend
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12.5px; color:#334155; max-width:280px; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; line-height:1.45;">
                                        {{ $closing->remarks ?: '—' }}
                                    </div>
                                    @if(!empty($closing->attachment_list))
                                        <div style="margin-top:6px; display:flex; align-items:center; gap:6px;">
                                            <span class="sdc-badge metric" style="background:#f1f5f9; color:#475569; font-size:10.5px;">
                                                📎 {{ count($closing->attachment_list) }} {{ Str::plural('attachment', count($closing->attachment_list)) }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($closing->is_on_leave_tomorrow)
                                        <span class="sdc-badge" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                            🏖️ Planned Leave
                                        </span>
                                    @elseif(is_array($closing->tomorrow_plans) && count($closing->tomorrow_plans) > 0)
                                        <div style="display:flex; flex-direction:column; gap:4px;">
                                            <span class="sdc-badge" style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; font-size:11px;">
                                                🎯 {{ count($closing->tomorrow_plans) }} Target(s) • ₹{{ number_format($closing->total_expected_value) }}
                                            </span>
                                            <div style="font-size:11px; color:#64748b; max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                                {{ $closing->plan_for_tomorrow }}
                                            </div>
                                        </div>
                                    @elseif($closing->plan_for_tomorrow)
                                        <div style="font-size:12px; color:#475569; max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            {{ $closing->plan_for_tomorrow }}
                                        </div>
                                    @else
                                        <span style="color:#94a3b8; font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($isAdminLike || $isTlLike)
                                        <select class="status-dropdown {{ $closing->status }}"
                                                data-update-url="{{ route('cst.day-closing.review', $closing->id) }}"
                                                title="Change Review Status">
                                            <option value="submitted" @selected($closing->status === 'submitted')>Submitted</option>
                                            <option value="reviewed" @selected($closing->status === 'reviewed')>Reviewed</option>
                                            <option value="approved" @selected($closing->status === 'approved')>Approved</option>
                                        </select>
                                    @else
                                        <span class="sdc-badge {{ $closing->status }}">
                                            {{ ucfirst($closing->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:inline-flex; align-items:center; gap:6px;">
                                        <button type="button" class="sdc-btn open-view-btn" style="padding:6px 9px; height:32px;" title="View Details" data-closing='{{ $closingJson }}'>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        </button>
                                        @if($closing->user_id === auth()->id() || $isAdminLike || $isTlLike)
                                            <button type="button" class="sdc-btn open-edit-btn" style="padding:6px 9px; height:32px; color:#fe5f04;" title="Edit Update" data-closing='{{ $closingJson }}'>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                            </button>
                                        @endif
                                        @if($closing->user_id === auth()->id() || $isAdminLike)
                                            <form action="{{ route('cst.day-closing.destroy', $closing->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this daily closing update?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sdc-btn" style="padding:6px 9px; height:32px; color:#ef4444;" title="Delete">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:48px 16px; color:#94a3b8;">
                                    <div style="font-size:14px; font-weight:700; color:#475569; margin-bottom:4px;">No CST Daily Closing submissions found</div>
                                    <div style="font-size:12px;">Submit your daily closing update using the button above.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($closings->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #e2e8f0;">
                    {{ $closings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal 1: Add / Edit Day Closing Modal -->
<div class="sdc-modal-overlay" data-closing-modal-overlay></div>
<div class="sdc-modal" data-closing-modal>
    <div class="sdc-modal-head">
        <div>
            <div class="sdc-card-title" id="closingModalTitle">Customer Success Day Closing Update</div>
            <div class="sdc-card-sub">Submit your daily performance metrics, remarks, tomorrow targets and attachments.</div>
        </div>
        <button type="button" class="sdc-modal-close" data-close-closing-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="sdc-modal-body">
        <form id="dayClosingForm" method="POST" action="{{ route('cst.day-closing.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="closingFormMethod" value="POST">

            <!-- Hidden Inputs to preserve auto-calculated stats -->
            <input type="hidden" name="monthly_target" id="modalInputMonthlyTarget" value="">
            <input type="hidden" name="today_revenue" id="modalInputTodayRevenue" value="">
            <input type="hidden" name="till_now_achieved" id="modalInputTillNowAchieved" value="">
            <input type="hidden" name="completed_percentage" id="modalInputCompletedPct" value="">
            <input type="hidden" name="current_week_meetings" id="modalInputCurrentWeekMeetings" value="">
            <input type="hidden" name="total_allocated_accounts" id="modalInputTotalAllocated" value="">
            <input type="hidden" name="today_added_accounts" id="modalInputTodayAdded" value="">
            <input type="hidden" name="welcome_call_pending_count" id="modalInputWelcomeCallPending" value="">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                <div>
                    <label class="sdc-label">Closing Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="closing_date" id="modalClosingDate" value="{{ $selectedDate ?: $today }}" max="{{ $today }}" class="sdc-input" required>
                </div>
                <div>
                    <label class="sdc-label">CST Executive</label>
                    @if($isAdminLike || $isTlLike)
                        <select name="user_id" id="modalUserId" class="sdc-select">
                            @foreach($assignableUsers as $u)
                                <option value="{{ $u->id }}" @selected($u->id === auth()->id())>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" value="{{ auth()->user()->name }}" class="sdc-input" readonly>
                        <input type="hidden" name="user_id" id="modalUserId" value="{{ auth()->id() }}">
                    @endif
                </div>
            </div>

            <!-- Auto-Calculated Live CST Department Stats Banner -->
            <div id="verifiedCallStatsBox" style="background:#fffaf5; border:1px solid #fed7aa; border-radius:10px; padding:12px 14px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <div style="font-size:11.5px; font-weight:800; color:#fe5f04; text-transform:uppercase; letter-spacing:0.06em; display:flex; align-items:center; gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        <span>CST Performance Stats for <span id="statsDateLabel">{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</span></span>
                    </div>
                    <span id="statsLoadingSpinner" style="font-size:11px; color:#fe5f04; display:none; font-weight:700;">Refreshing...</span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:8px;">
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Monthly Target</div>
                        <div id="statMonthlyTarget" style="font-size:16px; font-weight:900; color:#fe5f04;">₹0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Today's Revenue</div>
                        <div id="statTodayRevenue" style="font-size:16px; font-weight:900; color:#16a34a;">₹0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Till Now Achieved</div>
                        <div id="statTillNowAchieved" style="font-size:16px; font-weight:900; color:#2563eb;">₹0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Completed Pct</div>
                        <div id="statCompletedPct" style="font-size:16px; font-weight:900; color:#9333ea;">0%</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Week Meetings</div>
                        <div id="statCurrentWeekMeetings" style="font-size:16px; font-weight:900; color:#ca8a04;">0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Total Allocated</div>
                        <div id="statTotalAllocated" style="font-size:16px; font-weight:900; color:#0d9488;">0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Today's Added</div>
                        <div id="statTodayAdded" style="font-size:16px; font-weight:900; color:#0891b2;">0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Welcome Pending</div>
                        <div id="statWelcomePending" style="font-size:16px; font-weight:900; color:#e11d48;">0</div>
                    </div>
                </div>
            </div>

            <!-- Closing Remarks -->
            <div style="margin-bottom:16px;">
                <label class="sdc-label">Remarks / Day Closing Notes <span style="color:#ef4444;">*</span></label>
                <textarea name="remarks" id="modalRemarks" class="sdc-textarea" required placeholder="Write details about customer interactions, renewals, welcome calls made, challenges faced, or key accomplishments today..."></textarea>
            </div>

            <!-- Tomorrow's Plan & Committed Target Section -->
            <div style="margin-bottom:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <label class="sdc-label" style="margin-bottom:2px; color:#0f172a; font-size:12px;">Tomorrow&apos;s Plan &amp; Committed Target</label>
                        <div style="font-size:11px; color:#64748b;">Add target accounts, products &amp; expected value for tomorrow</div>
                    </div>
                    <!-- Planned Leave Checkbox -->
                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:12px; font-weight:700; color:#b45309; background:#fffbeb; padding:6px 12px; border-radius:999px; border:1px solid #fde68a; user-select:none;">
                        <input type="checkbox" name="is_on_leave_tomorrow" id="modalIsOnLeaveTomorrow" value="1" style="accent-color:#fe5f04; width:16px; height:16px; cursor:pointer;">
                        <span>🏖️ Planned Leave Tomorrow</span>
                    </label>
                </div>

                <!-- Banner when On Leave Tomorrow is checked -->
                <div id="modalLeaveTomorrowBanner" style="display:none; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px; color:#92400e; font-size:13px; font-weight:600; align-items:center; gap:10px;">
                    <span style="font-size:22px;">🏖️</span>
                    <div>
                        <div style="font-weight:800; color:#b45309;">Scheduled on Planned Leave Tomorrow</div>
                        <div style="font-size:11.5px; color:#a16207; font-weight:500;">No account targets or commitment values required for tomorrow.</div>
                    </div>
                </div>

                <!-- Dynamic Target Deals Container -->
                <div id="modalTomorrowPlansContainer">
                    <div style="display:grid; grid-template-columns:1.2fr 1.2fr 140px 36px; gap:8px; padding:0 10px 6px; font-size:10.5px; font-weight:800; text-transform:uppercase; color:#64748b; letter-spacing:0.05em;">
                        <div>Account / Company Name</div>
                        <div>Product / Service</div>
                        <div>Expected Value (₹)</div>
                        <div></div>
                    </div>

                    <div id="modalTomorrowPlansList" style="display:flex; flex-direction:column; gap:8px;"></div>

                    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:12px; padding-top:12px; border-top:1px dashed #cbd5e1; flex-wrap:wrap; gap:10px;">
                        <button type="button" id="modalAddTomorrowPlanBtn" class="sdc-btn" style="padding:6px 14px; font-size:12px; border-color:#fed7aa; color:#fe5f04; background:#fff7ed;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            <span>Add Planned Target</span>
                        </button>
                        <div style="display:flex; align-items:center; gap:6px; font-size:12.5px; color:#475569;">
                            <span>Total Expected Target:</span>
                            <strong id="modalTomorrowTotalValue" style="font-size:15px; font-weight:900; color:#16a34a;">₹0</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attachments Section -->
            <div style="margin-bottom:20px;">
                <label class="sdc-label">File Attachments (Optional)</label>
                <div style="font-size:11.5px; color:#64748b; margin-bottom:8px;">Upload sheets, screenshots, welcome call logs, or reports (Max 10MB each - PDF, Images, Excel, Docs)</div>
                <input type="file" name="attachments[]" id="modalAttachmentsInput" multiple class="sdc-input" style="padding-top:7px; height:42px;">

                <!-- Existing attachments list (when editing) -->
                <div id="modalExistingAttachments" style="display:none; margin-top:10px; display:flex; flex-direction:column; gap:6px;"></div>
            </div>

            <!-- Submit Buttons -->
            <div style="display:flex; align-items:center; justify-content:flex-end; gap:10px; border-top:1px solid #e2e8f0; padding-top:16px;">
                <button type="button" class="sdc-btn" data-close-closing-modal>Cancel</button>
                <button type="submit" id="closingSubmitBtn" class="sdc-btn sdc-btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Save CST Day Closing</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: View Day Closing Details Modal -->
<div class="sdc-modal-overlay" data-view-modal-overlay></div>
<div class="sdc-modal" data-view-modal style="width:min(780px, calc(100vw - 32px));">
    <div class="sdc-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:38px; height:38px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; color:#fe5f04; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><polyline points="9 16 12 19 16 14"></polyline></svg>
            </div>
            <div>
                <div class="sdc-card-title" id="viewModalUserName">Executive Name</div>
                <div class="sdc-card-sub" id="viewModalDateBranch">Date | Branch</div>
            </div>
        </div>
        <button type="button" class="sdc-modal-close" data-close-view-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="sdc-modal-body">
        <!-- 8 Metric chips -->
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid #e2e8f0;">
            <span class="sdc-badge metric" style="background:#fff7ed; border-color:#fed7aa; color:#c2410c; padding:6px 12px; font-size:12px;">
                🎯 Target: <strong id="viewMonthlyTarget" style="font-size:13px; margin-left:3px;">₹0</strong>
            </span>
            <span class="sdc-badge metric" style="background:#f0fdf4; border-color:#bbf7d0; color:#15803d; padding:6px 12px; font-size:12px;">
                💰 Today: <strong id="viewTodayRevenue" style="font-size:13px; margin-left:3px;">₹0</strong>
            </span>
            <span class="sdc-badge metric" style="background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; padding:6px 12px; font-size:12px;">
                📈 Achieved: <strong id="viewTillNowAchieved" style="font-size:13px; margin-left:3px;">₹0</strong>
            </span>
            <span class="sdc-badge metric" style="background:#faf5ff; border-color:#e9d5ff; color:#7e22ce; padding:6px 12px; font-size:12px;">
                ⚡ <strong id="viewCompletedPct" style="font-size:13px; margin-left:3px;">0%</strong>
            </span>
            <span class="sdc-badge metric" style="padding:6px 12px; font-size:12px;">
                📅 Meetings: <strong id="viewCurrentWeekMeetings" style="font-size:13px; margin-left:3px;">0</strong>
            </span>
            <span class="sdc-badge metric" style="padding:6px 12px; font-size:12px;">
                👥 Alloc: <strong id="viewTotalAllocated" style="font-size:13px; margin-left:3px;">0</strong>
            </span>
            <span class="sdc-badge metric" style="padding:6px 12px; font-size:12px;">
                ➕ Added: <strong id="viewTodayAdded" style="font-size:13px; margin-left:3px;">0</strong>
            </span>
            <span class="sdc-badge metric" style="background:#fff1f2; border-color:#fecdd3; color:#be123c; padding:6px 12px; font-size:12px;">
                📞 Welcome Pend: <strong id="viewWelcomePending" style="font-size:13px; margin-left:3px;">0</strong>
            </span>
        </div>

        <!-- Remarks Notes -->
        <div style="margin-bottom:18px;">
            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">Remarks / Day Closing Notes</div>
            <div id="viewRemarks" style="font-size:13.5px; line-height:1.65; color:#1e293b; background:#f8fafc; padding:14px; border-radius:10px; border:1px solid #e2e8f0; white-space:pre-wrap;"></div>
        </div>

        <!-- Tomorrow's Plan & Committed Target -->
        <div id="viewPlanTomorrowContainer" style="margin-bottom:18px;">
            <div style="font-size:11px; font-weight:800; color:#fe5f04; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fe5f04" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <span>Tomorrow&apos;s Plan &amp; Committed Target</span>
            </div>

            <div id="viewLeaveTomorrowBanner" style="display:none; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px; color:#92400e; font-size:13px; font-weight:700; align-items:center; gap:10px;">
                <span style="font-size:22px;">🏖️</span>
                <div>
                    <div>Planned Leave Tomorrow</div>
                    <div style="font-size:11.5px; color:#a16207; font-weight:500;">Executive is scheduled on leave tomorrow.</div>
                </div>
            </div>

            <div id="viewTomorrowPlansTableWrap" style="display:none; border:1px solid #fed7aa; border-radius:10px; overflow:hidden; background:#fff;">
                <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                    <thead>
                        <tr style="background:#fffaf5; border-bottom:1px solid #fed7aa;">
                            <th style="width:40px; text-align:center; padding:9px 12px; font-size:11px; font-weight:800; color:#7c2d12; text-transform:uppercase;">#</th>
                            <th style="text-align:left; padding:9px 12px; font-size:11px; font-weight:800; color:#7c2d12; text-transform:uppercase;">Account / Client</th>
                            <th style="text-align:left; padding:9px 12px; font-size:11px; font-weight:800; color:#7c2d12; text-transform:uppercase;">Product / Service</th>
                            <th style="text-align:right; padding:9px 12px; font-size:11px; font-weight:800; color:#7c2d12; text-transform:uppercase;">Expected Value</th>
                        </tr>
                    </thead>
                    <tbody id="viewTomorrowPlansTableBody"></tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; border-top:1.5px solid #fed7aa; font-weight:800;">
                            <td colspan="3" style="padding:10px 12px; text-align:right; color:#0f172a; font-size:12.5px;">Total Committed Target:</td>
                            <td id="viewTomorrowPlansTotalValue" style="padding:10px 12px; text-align:right; color:#16a34a; font-size:14px; font-weight:900;">₹0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div id="viewPlanTomorrowLegacy" style="display:none; font-size:13px; line-height:1.6; color:#334155; background:#fffaf5; padding:12px 14px; border-radius:10px; border:1px solid #fed7aa; white-space:pre-wrap;"></div>
        </div>

        <!-- Attached Files Section -->
        <div id="viewAttachmentsContainer" style="display:none; margin-bottom:18px;">
            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">Uploaded Attachments</div>
            <div id="viewAttachmentsList" style="display:flex; flex-direction:column; gap:6px;"></div>
        </div>

        <!-- Modal Footer Actions -->
        <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px; margin-top:20px; border-top:1px solid #e2e8f0; padding-top:16px;">
            <button type="button" class="sdc-btn" data-close-view-modal>Close</button>
            <button type="button" id="viewEditShortcutBtn" class="sdc-btn sdc-btn-primary">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                <span>Edit Update</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Modals
    const closingModal = document.querySelector('[data-closing-modal]');
    const closingOverlay = document.querySelector('[data-closing-modal-overlay]');
    const openClosingBtns = document.querySelectorAll('[data-open-closing-modal]');
    const closeClosingBtns = document.querySelectorAll('[data-close-closing-modal]');

    const viewModal = document.querySelector('[data-view-modal]');
    const viewOverlay = document.querySelector('[data-view-modal-overlay]');
    const closeViewBtns = document.querySelectorAll('[data-close-view-modal]');

    // Elements
    const modalClosingDate = document.getElementById('modalClosingDate');
    const modalUserId = document.getElementById('modalUserId');
    const modalRemarks = document.getElementById('modalRemarks');
    const modalIsOnLeaveTomorrow = document.getElementById('modalIsOnLeaveTomorrow');
    const modalLeaveTomorrowBanner = document.getElementById('modalLeaveTomorrowBanner');
    const modalTomorrowPlansContainer = document.getElementById('modalTomorrowPlansContainer');
    const modalTomorrowPlansList = document.getElementById('modalTomorrowPlansList');
    const modalAddTomorrowPlanBtn = document.getElementById('modalAddTomorrowPlanBtn');
    const modalTomorrowTotalValue = document.getElementById('modalTomorrowTotalValue');
    const closingModalTitle = document.getElementById('closingModalTitle');
    const dayClosingForm = document.getElementById('dayClosingForm');
    const closingFormMethod = document.getElementById('closingFormMethod');
    const modalExistingAttachments = document.getElementById('modalExistingAttachments');

    // Stats display elements
    const statsDateLabel = document.getElementById('statsDateLabel');
    const statsLoadingSpinner = document.getElementById('statsLoadingSpinner');
    const statMonthlyTarget = document.getElementById('statMonthlyTarget');
    const statTodayRevenue = document.getElementById('statTodayRevenue');
    const statTillNowAchieved = document.getElementById('statTillNowAchieved');
    const statCompletedPct = document.getElementById('statCompletedPct');
    const statCurrentWeekMeetings = document.getElementById('statCurrentWeekMeetings');
    const statTotalAllocated = document.getElementById('statTotalAllocated');
    const statTodayAdded = document.getElementById('statTodayAdded');
    const statWelcomePending = document.getElementById('statWelcomePending');

    // Hidden input fields
    const modalInputMonthlyTarget = document.getElementById('modalInputMonthlyTarget');
    const modalInputTodayRevenue = document.getElementById('modalInputTodayRevenue');
    const modalInputTillNowAchieved = document.getElementById('modalInputTillNowAchieved');
    const modalInputCompletedPct = document.getElementById('modalInputCompletedPct');
    const modalInputCurrentWeekMeetings = document.getElementById('modalInputCurrentWeekMeetings');
    const modalInputTotalAllocated = document.getElementById('modalInputTotalAllocated');
    const modalInputTodayAdded = document.getElementById('modalInputTodayAdded');
    const modalInputWelcomeCallPending = document.getElementById('modalInputWelcomeCallPending');

    let currentEditingClosing = null;

    function setModalState(isOpen) {
        if (!closingModal || !closingOverlay) return;
        closingModal.classList.toggle('is-open', isOpen);
        closingOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function setViewModalState(isOpen) {
        if (!viewModal || !viewOverlay) return;
        viewModal.classList.toggle('is-open', isOpen);
        viewOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateTomorrowPlansTotal() {
        if (!modalTomorrowTotalValue || !modalTomorrowPlansList) return;
        let total = 0;
        modalTomorrowPlansList.querySelectorAll('.tomorrow-value-input').forEach(inp => {
            const val = parseFloat(inp.value) || 0;
            total += val;
        });
        modalTomorrowTotalValue.textContent = '₹' + Math.round(total).toLocaleString('en-IN');
    }

    function reindexTomorrowPlanRows() {
        if (!modalTomorrowPlansList) return;
        const rows = modalTomorrowPlansList.querySelectorAll('.sdc-tomorrow-plan-row');
        rows.forEach((row, idx) => {
            const companyInput = row.querySelector('.tomorrow-company-input');
            const productInput = row.querySelector('.tomorrow-product-input');
            const valueInput = row.querySelector('.tomorrow-value-input');
            if (companyInput) companyInput.name = `tomorrow_plans[${idx}][company_name]`;
            if (productInput) productInput.name = `tomorrow_plans[${idx}][product_name]`;
            if (valueInput) valueInput.name = `tomorrow_plans[${idx}][expected_value]`;
        });
    }

    function createTomorrowPlanRow(company = '', product = '', value = '') {
        if (!modalTomorrowPlansList) return;
        const row = document.createElement('div');
        row.className = 'sdc-tomorrow-plan-row';
        const rowIndex = modalTomorrowPlansList.children.length;
        row.innerHTML = `
            <div>
                <input type="text" name="tomorrow_plans[${rowIndex}][company_name]" class="sdc-input tomorrow-company-input" style="height:36px; font-size:12.5px;" placeholder="Account / Client Name" value="${escapeHtml(company)}">
            </div>
            <div>
                <input type="text" name="tomorrow_plans[${rowIndex}][product_name]" class="sdc-input tomorrow-product-input" style="height:36px; font-size:12.5px;" placeholder="Product / Service" value="${escapeHtml(product)}">
            </div>
            <div style="position:relative;">
                <span style="position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#64748b; font-weight:700; font-size:12px;">₹</span>
                <input type="number" step="any" min="0" name="tomorrow_plans[${rowIndex}][expected_value]" class="sdc-input tomorrow-value-input" style="height:36px; font-size:12.5px; padding-left:22px;" placeholder="Exp. Value" value="${value !== '' && value !== null && value !== undefined ? escapeHtml(value) : ''}">
            </div>
            <button type="button" class="sdc-btn remove-tomorrow-row-btn" style="height:36px; width:36px; padding:0; color:#ef4444; border-color:#fecaca; background:#fff;" title="Remove Target">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </button>
        `;

        row.querySelector('.remove-tomorrow-row-btn').addEventListener('click', function () {
            row.remove();
            reindexTomorrowPlanRows();
            if (modalTomorrowPlansList.children.length === 0) {
                createTomorrowPlanRow();
            }
            updateTomorrowPlansTotal();
        });

        row.querySelector('.tomorrow-value-input').addEventListener('input', updateTomorrowPlansTotal);

        modalTomorrowPlansList.appendChild(row);
        reindexTomorrowPlanRows();
        updateTomorrowPlansTotal();
    }

    function setTomorrowLeaveState(isOnLeave) {
        if (modalIsOnLeaveTomorrow) {
            modalIsOnLeaveTomorrow.checked = isOnLeave;
        }
        if (modalLeaveTomorrowBanner) {
            modalLeaveTomorrowBanner.style.display = isOnLeave ? 'flex' : 'none';
        }
        if (modalTomorrowPlansContainer) {
            modalTomorrowPlansContainer.style.display = isOnLeave ? 'none' : 'block';
        }
    }

    if (modalIsOnLeaveTomorrow) {
        modalIsOnLeaveTomorrow.addEventListener('change', function () {
            setTomorrowLeaveState(this.checked);
        });
    }

    if (modalAddTomorrowPlanBtn) {
        modalAddTomorrowPlanBtn.addEventListener('click', function () {
            createTomorrowPlanRow('', '', '');
        });
    }

    function populateTomorrowPlans(plans, isOnLeave) {
        if (!modalTomorrowPlansList) return;
        modalTomorrowPlansList.innerHTML = '';
        setTomorrowLeaveState(Boolean(isOnLeave));

        if (Array.isArray(plans) && plans.length > 0) {
            plans.forEach(p => {
                createTomorrowPlanRow(p.company_name || '', p.product_name || '', p.expected_value || '');
            });
        } else {
            createTomorrowPlanRow('', '', '');
        }
        updateTomorrowPlansTotal();
    }

    function applyStatsToUi(s) {
        if (statMonthlyTarget) statMonthlyTarget.textContent = '₹' + Math.round(s.monthly_target || 0).toLocaleString('en-IN');
        if (statTodayRevenue) statTodayRevenue.textContent = '₹' + Math.round(s.today_revenue || 0).toLocaleString('en-IN');
        if (statTillNowAchieved) statTillNowAchieved.textContent = '₹' + Math.round(s.till_now_achieved || 0).toLocaleString('en-IN');
        if (statCompletedPct) statCompletedPct.textContent = (s.completed_percentage || 0) + '%';
        if (statCurrentWeekMeetings) statCurrentWeekMeetings.textContent = s.current_week_meetings || 0;
        if (statTotalAllocated) statTotalAllocated.textContent = s.total_allocated_accounts || 0;
        if (statTodayAdded) statTodayAdded.textContent = s.today_added_accounts || 0;
        if (statWelcomePending) statWelcomePending.textContent = s.welcome_call_pending_count || 0;

        if (modalInputMonthlyTarget) modalInputMonthlyTarget.value = s.monthly_target || 0;
        if (modalInputTodayRevenue) modalInputTodayRevenue.value = s.today_revenue || 0;
        if (modalInputTillNowAchieved) modalInputTillNowAchieved.value = s.till_now_achieved || 0;
        if (modalInputCompletedPct) modalInputCompletedPct.value = s.completed_percentage || 0;
        if (modalInputCurrentWeekMeetings) modalInputCurrentWeekMeetings.value = s.current_week_meetings || 0;
        if (modalInputTotalAllocated) modalInputTotalAllocated.value = s.total_allocated_accounts || 0;
        if (modalInputTodayAdded) modalInputTodayAdded.value = s.today_added_accounts || 0;
        if (modalInputWelcomeCallPending) modalInputWelcomeCallPending.value = s.welcome_call_pending_count || 0;
    }

    // Dynamic stats fetch when date or user changes
    function fetchLiveStats() {
        const dateVal = modalClosingDate ? modalClosingDate.value : '';
        const userVal = modalUserId ? modalUserId.value : '';
        if (!dateVal) return;

        if (statsLoadingSpinner) statsLoadingSpinner.style.display = 'inline';

        fetch(`/cst/day-closing/stats?closing_date=${encodeURIComponent(dateVal)}&user_id=${encodeURIComponent(userVal)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (statsLoadingSpinner) statsLoadingSpinner.style.display = 'none';
            if (res.success && res.stats) {
                applyStatsToUi(res.stats);
                if (statsDateLabel) statsDateLabel.textContent = res.date;

                if (!currentEditingClosing && res.exists && res.closing) {
                    if (modalRemarks) modalRemarks.value = res.closing.remarks || '';
                    populateTomorrowPlans(res.closing.tomorrow_plans || [], res.closing.is_on_leave_tomorrow);
                    if (closingModalTitle) closingModalTitle.textContent = 'Edit Customer Success Day Closing';
                    if (closingFormMethod) closingFormMethod.value = 'PATCH';
                    if (dayClosingForm) dayClosingForm.action = res.closing.update_url;
                } else if (!currentEditingClosing && !res.exists) {
                    if (closingModalTitle) closingModalTitle.textContent = 'Customer Success Day Closing Update';
                    if (closingFormMethod) closingFormMethod.value = 'POST';
                    if (dayClosingForm) dayClosingForm.action = '{{ route("cst.day-closing.store") }}';
                    populateTomorrowPlans([], false);
                }
            }
        })
        .catch(err => {
            if (statsLoadingSpinner) statsLoadingSpinner.style.display = 'none';
            console.error('Error fetching CST stats:', err);
        });
    }

    if (modalClosingDate) modalClosingDate.addEventListener('change', fetchLiveStats);
    if (modalUserId) modalUserId.addEventListener('change', fetchLiveStats);

    // Open Add Modal
    openClosingBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            currentEditingClosing = null;
            if (closingModalTitle) closingModalTitle.textContent = 'Customer Success Day Closing Update';
            if (closingFormMethod) closingFormMethod.value = 'POST';
            if (dayClosingForm) {
                dayClosingForm.action = '{{ route("cst.day-closing.store") }}';
                dayClosingForm.reset();
            }
            if (modalClosingDate) modalClosingDate.value = '{{ $selectedDate ?: $today }}';
            if (modalExistingAttachments) modalExistingAttachments.style.display = 'none';
            populateTomorrowPlans([], false);
            setModalState(true);
            fetchLiveStats();
        });
    });

    closeClosingBtns.forEach(btn => btn.addEventListener('click', () => setModalState(false)));
    if (closingOverlay) closingOverlay.addEventListener('click', () => setModalState(false));

    closeViewBtns.forEach(btn => btn.addEventListener('click', () => setViewModalState(false)));
    if (viewOverlay) viewOverlay.addEventListener('click', () => setViewModalState(false));

    // Delegate Click for View and Edit buttons
    document.addEventListener('click', function(e) {
        // VIEW DETAILS
        const viewBtn = e.target.closest('.open-view-btn');
        if (viewBtn) {
            let data = null;
            try {
                data = JSON.parse(viewBtn.getAttribute('data-closing') || '{}');
            } catch(err) {
                console.error(err);
                return;
            }

            document.getElementById('viewModalUserName').textContent = data.user_name || 'Team Member';
            document.getElementById('viewModalDateBranch').textContent = (data.formatted_date || data.closing_date || '') + ' | ' + (data.branch_name || 'Customer Success');

            document.getElementById('viewMonthlyTarget').textContent = '₹' + Math.round(data.monthly_target || 0).toLocaleString('en-IN');
            document.getElementById('viewTodayRevenue').textContent = '₹' + Math.round(data.today_revenue || 0).toLocaleString('en-IN');
            document.getElementById('viewTillNowAchieved').textContent = '₹' + Math.round(data.till_now_achieved || 0).toLocaleString('en-IN');
            document.getElementById('viewCompletedPct').textContent = (data.completed_percentage || 0) + '%';
            document.getElementById('viewCurrentWeekMeetings').textContent = data.current_week_meetings ?? 0;
            document.getElementById('viewTotalAllocated').textContent = data.total_allocated_accounts ?? 0;
            document.getElementById('viewTodayAdded').textContent = data.today_added_accounts ?? 0;
            document.getElementById('viewWelcomePending').textContent = data.welcome_call_pending_count ?? 0;

            document.getElementById('viewRemarks').textContent = data.remarks || 'No remarks recorded.';

            // Tomorrow's plan in View Modal
            const viewLeaveBanner = document.getElementById('viewLeaveTomorrowBanner');
            const viewPlansTableWrap = document.getElementById('viewTomorrowPlansTableWrap');
            const viewPlansTableBody = document.getElementById('viewTomorrowPlansTableBody');
            const viewPlansTotalValue = document.getElementById('viewTomorrowPlansTotalValue');
            const viewPlanLegacy = document.getElementById('viewPlanTomorrowLegacy');

            if (data.is_on_leave_tomorrow) {
                if (viewLeaveBanner) viewLeaveBanner.style.display = 'flex';
                if (viewPlansTableWrap) viewPlansTableWrap.style.display = 'none';
                if (viewPlanLegacy) viewPlanLegacy.style.display = 'none';
            } else if (Array.isArray(data.tomorrow_plans) && data.tomorrow_plans.length > 0) {
                if (viewLeaveBanner) viewLeaveBanner.style.display = 'none';
                if (viewPlanLegacy) viewPlanLegacy.style.display = 'none';
                if (viewPlansTableWrap) viewPlansTableWrap.style.display = 'block';
                if (viewPlansTableBody) {
                    viewPlansTableBody.innerHTML = '';
                    let sum = 0;
                    data.tomorrow_plans.forEach((p, pIdx) => {
                        const val = parseFloat(p.expected_value) || 0;
                        sum += val;
                        const rowTr = document.createElement('tr');
                        rowTr.style.borderBottom = '1px solid #fed7aa33';
                        rowTr.innerHTML = `
                            <td style="text-align:center; padding:9px 12px; color:#94a3b8; font-weight:700;">${pIdx + 1}</td>
                            <td style="padding:9px 12px; font-weight:800; color:#0f172a;">${escapeHtml(p.company_name || '—')}</td>
                            <td style="padding:9px 12px; color:#475569;">${escapeHtml(p.product_name || '—')}</td>
                            <td style="text-align:right; padding:9px 12px; font-weight:800; color:#16a34a;">${val > 0 ? '₹' + Math.round(val).toLocaleString('en-IN') : '—'}</td>
                        `;
                        viewPlansTableBody.appendChild(rowTr);
                    });
                    if (viewPlansTotalValue) {
                        viewPlansTotalValue.textContent = '₹' + Math.round(sum).toLocaleString('en-IN');
                    }
                }
            } else if (data.plan_for_tomorrow && data.plan_for_tomorrow.trim() !== '') {
                if (viewLeaveBanner) viewLeaveBanner.style.display = 'none';
                if (viewPlansTableWrap) viewPlansTableWrap.style.display = 'none';
                if (viewPlanLegacy) {
                    viewPlanLegacy.textContent = data.plan_for_tomorrow;
                    viewPlanLegacy.style.display = 'block';
                }
            } else {
                if (viewLeaveBanner) viewLeaveBanner.style.display = 'none';
                if (viewPlansTableWrap) viewPlansTableWrap.style.display = 'none';
                if (viewPlanLegacy) {
                    viewPlanLegacy.textContent = 'No commitment recorded for tomorrow.';
                    viewPlanLegacy.style.display = 'block';
                }
            }

            // Attachments
            const viewAttachmentsContainer = document.getElementById('viewAttachmentsContainer');
            const viewAttachmentsList = document.getElementById('viewAttachmentsList');
            if (Array.isArray(data.attachments) && data.attachments.length > 0) {
                viewAttachmentsContainer.style.display = 'block';
                viewAttachmentsList.innerHTML = '';
                data.attachments.forEach(att => {
                    const row = document.createElement('div');
                    row.style.cssText = 'display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:12.5px;';
                    row.innerHTML = `
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>📎</span>
                            <span style="font-weight:700; color:#0f172a;">${escapeHtml(att.name || 'Attachment')}</span>
                            <span style="font-size:11px; color:#64748b;">(${att.size_formatted || ''})</span>
                        </div>
                        <a href="/${att.path}" target="_blank" download class="sdc-btn" style="padding:4px 10px; font-size:11px; color:#fe5f04;">
                            Download
                        </a>
                    `;
                    viewAttachmentsList.appendChild(row);
                });
            } else {
                viewAttachmentsContainer.style.display = 'none';
            }

            // Edit shortcut
            const editShortcutBtn = document.getElementById('viewEditShortcutBtn');
            if (editShortcutBtn) {
                if (data.can_edit) {
                    editShortcutBtn.style.display = 'inline-flex';
                    editShortcutBtn.onclick = function () {
                        setViewModalState(false);
                        const tr = viewBtn.closest('tr');
                        const editBtn = tr ? tr.querySelector('.open-edit-btn') : null;
                        if (editBtn) editBtn.click();
                    };
                } else {
                    editShortcutBtn.style.display = 'none';
                }
            }

            setViewModalState(true);
            return;
        }

        // EDIT CLOSING UPDATE
        const editBtn = e.target.closest('.open-edit-btn');
        if (editBtn) {
            let data = null;
            try {
                data = JSON.parse(editBtn.getAttribute('data-closing') || '{}');
            } catch(err) {
                console.error(err);
                return;
            }

            currentEditingClosing = data;
            if (closingModalTitle) closingModalTitle.textContent = 'Edit Customer Success Day Closing (' + (data.user_name || '') + ')';
            if (closingFormMethod) closingFormMethod.value = 'PATCH';
            if (dayClosingForm) dayClosingForm.action = data.update_url;

            if (modalClosingDate) modalClosingDate.value = data.closing_date || '';
            if (modalUserId && data.user_id) modalUserId.value = data.user_id;
            if (modalRemarks) modalRemarks.value = data.remarks || '';

            applyStatsToUi(data);
            if (statsDateLabel) statsDateLabel.textContent = data.formatted_date || data.closing_date || '';

            populateTomorrowPlans(data.tomorrow_plans || [], data.is_on_leave_tomorrow);

            // Existing attachments list
            if (modalExistingAttachments) {
                if (Array.isArray(data.attachments) && data.attachments.length > 0) {
                    modalExistingAttachments.style.display = 'flex';
                    modalExistingAttachments.innerHTML = '<div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase;">Current Uploads</div>';
                    data.attachments.forEach(att => {
                        const itemDiv = document.createElement('div');
                        itemDiv.style.cssText = 'display:flex; align-items:center; justify-content:space-between; padding:6px 10px; background:#fff; border:1px solid #e2e8f0; border-radius:6px; font-size:12px;';
                        itemDiv.innerHTML = `
                            <div style="display:flex; align-items:center; gap:6px;">
                                <span>📎</span>
                                <span style="color:#0f172a; font-weight:700;">${escapeHtml(att.name)}</span>
                            </div>
                            <button type="button" class="delete-att-btn" data-path="${escapeHtml(att.path)}" style="background:none; border:none; color:#ef4444; cursor:pointer; font-weight:700; font-size:11px;">
                                Remove
                            </button>
                        `;
                        itemDiv.querySelector('.delete-att-btn').addEventListener('click', function () {
                            if (!confirm('Remove this attachment?')) return;
                            fetch(`{{ url('cst/day-closing') }}/${data.id}/attachments`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ path: att.path })
                            }).then(r => r.json()).then(resp => {
                                if (resp.success) {
                                    itemDiv.remove();
                                }
                            });
                        });
                        modalExistingAttachments.appendChild(itemDiv);
                    });
                } else {
                    modalExistingAttachments.style.display = 'none';
                }
            }

            setModalState(true);
            fetchLiveStats();
            return;
        }
    });

    // Handle Status Select Change (Submitted / Reviewed / Approved)
    document.querySelectorAll('.status-dropdown').forEach(selectEl => {
        selectEl.addEventListener('change', function () {
            const newStatus = this.value;
            const updateUrl = this.getAttribute('data-update-url');
            const currentSelect = this;

            currentSelect.style.opacity = '0.5';

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                currentSelect.style.opacity = '1';
                if (data.success) {
                    currentSelect.classList.remove('submitted', 'reviewed', 'approved');
                    currentSelect.classList.add(newStatus);
                } else {
                    alert(data.message || 'Failed to update status.');
                }
            })
            .catch(err => {
                currentSelect.style.opacity = '1';
                console.error('Error updating status:', err);
                alert('An error occurred while updating status.');
            });
        });
    });
});
</script>
@endpush
