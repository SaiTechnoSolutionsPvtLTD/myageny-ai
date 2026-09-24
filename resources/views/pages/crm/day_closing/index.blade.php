@extends('layouts.app')

@section('title', 'Sales Department Day Closing')

@push('styles')
<style>
.sdc-page { min-height:100%; background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%); font-family:'Inter',sans-serif; }
.sdc-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #e2e8f0; }
.sdc-title { font-size:22px; font-weight:900; color:#0f172a; letter-spacing:-0.02em; }
.sdc-breadcrumb { font-size:12px; color:#64748b; margin-top:4px; display:flex; align-items:center; gap:6px; }
.sdc-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

.sdc-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:9px 15px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#0f172a; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.sdc-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; box-shadow:0 4px 12px rgba(234,88,12,0.2); }
.sdc-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.sdc-btn-outline { border-color:#ea580c; color:#ea580c; background:#fff; }
.sdc-btn-outline:hover { background:#fff7ed; }

.sdc-body { padding:22px 28px 36px; display:grid; gap:20px; }

/* KPI Grid */
.sdc-kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.sdc-kpi-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; box-shadow:0 4px 14px rgba(15,23,42,0.03); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.2s cubic-bezier(0.4,0,0.2,1); }
.sdc-kpi-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,0.06); }
.sdc-kpi-card.highlight { border-color:#fed7aa; background:linear-gradient(135deg,#fff 0%,#fffaf5 100%); }
.sdc-kpi-clickable { cursor:pointer; position:relative; user-select:none; }
.sdc-kpi-clickable:hover { transform:translateY(-3px); box-shadow:0 12px 24px rgba(234,88,12,0.12); border-color:#ea580c; }
.sdc-kpi-clickable:active { transform:translateY(-1px); }
.sdc-kpi-badge-hint { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#94a3b8; margin-top:10px; padding-top:8px; border-top:1px dashed #e2e8f0; transition:color 0.15s; }
.sdc-kpi-clickable:hover .sdc-kpi-badge-hint { color:#ea580c; }
@keyframes sdc-spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.sdc-spin { animation:sdc-spin 0.9s linear infinite; }
.sdc-kpi-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.sdc-kpi-label { font-size:11.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:#64748b; }
.sdc-kpi-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.sdc-kpi-value { font-size:26px; font-weight:900; color:#0f172a; margin-top:10px; line-height:1; }
.sdc-kpi-sub { font-size:11px; color:#94a3b8; font-weight:600; margin-top:6px; }

/* Filter Card */
.sdc-filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; box-shadow:0 4px 14px rgba(15,23,42,0.03); }
.sdc-filter-form { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; align-items:end; }
.sdc-filter-group { display:grid; gap:6px; }
.sdc-label { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.07em; color:#64748b; }
.sdc-input, .sdc-select, .sdc-textarea { width:100%; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font-size:13.5px; color:#0f172a; transition:border-color 0.15s, box-shadow 0.15s; }
.sdc-input, .sdc-select { height:40px; padding:8px 12px; }
.sdc-textarea { padding:12px; resize:vertical; min-height:130px; line-height:1.55; }
.sdc-input:focus, .sdc-select:focus, .sdc-textarea:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 3px rgba(234,88,12,0.14); }

/* Main Card & Table */
.sdc-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; box-shadow:0 8px 24px rgba(15,23,42,0.04); }
.sdc-card-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:18px 22px; border-bottom:1px solid #e2e8f0; background:#fbfdff; flex-wrap:wrap; }
.sdc-card-title { font-size:15px; font-weight:900; color:#0f172a; }
.sdc-card-sub { font-size:12px; color:#64748b; margin-top:2px; }

.sdc-table-wrap { overflow-x:auto; }
.sdc-table { width:100%; border-collapse:collapse; min-width:980px; }
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
.sdc-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(780px, calc(100vw - 32px)); max-height:calc(100vh - 40px); overflow-y:auto; background:#fff; border:1px solid #e2e8f0; border-radius:16px; box-shadow:0 24px 60px rgba(15,23,42,0.22); z-index:1210; display:none; }
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
            <h1 class="sdc-title">Sales Department Day Closing</h1>
            <div class="sdc-breadcrumb">
                <span>CRM</span>
                <span>/</span>
                <span style="font-weight:700; color:#0f172a;">Daily Closing Updates &amp; Call Metrics</span>
            </div>
        </div>
        <div class="sdc-actions">
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

        <!-- Live Call Metrics & Sales KPIs (Selected Date: {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}) -->
        <div>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                <div style="font-size:12px; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:0.06em; display:flex; align-items:center; gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    <span>System Verified Call Performance ({{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }})</span>
                </div>
                <div style="font-size:12px; color:#64748b; font-weight:600;">
                    Real-time aggregated metrics from CRM Call Logs
                </div>
            </div>

            <div class="sdc-kpi-grid">
                <!-- 1. Unique Calls -->
                <div class="sdc-kpi-card highlight sdc-kpi-clickable" data-open-call-details data-metric="unique_calls" title="Click to view Unique Calls details">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Unique Calls</span>
                        <div class="sdc-kpi-icon" style="background:#fff7ed; color:#ea580c;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#ea580c;">{{ number_format($kpiStats['unique_calls']) }}</div>
                    <div class="sdc-kpi-sub">Distinct leads contacted</div>
                    <div class="sdc-kpi-badge-hint">
                        <span>Click to view call logs</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>

                <!-- 2. Total New Calls -->
                <div class="sdc-kpi-card sdc-kpi-clickable" data-open-call-details data-metric="new_calls" title="Click to view Total New Calls details">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Total New Calls</span>
                        <div class="sdc-kpi-icon" style="background:#f0fdf4; color:#16a34a;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#16a34a;">{{ number_format($kpiStats['new_calls']) }}</div>
                    <div class="sdc-kpi-sub">First-time fresh outreach</div>
                    <div class="sdc-kpi-badge-hint">
                        <span>Click to view call logs</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>

                <!-- 3. Followup Calls -->
                <div class="sdc-kpi-card sdc-kpi-clickable" data-open-call-details data-metric="followup_calls" title="Click to view Followup Calls details">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Followup Calls</span>
                        <div class="sdc-kpi-icon" style="background:#eff6ff; color:#2563eb;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#2563eb;">{{ number_format($kpiStats['followup_calls']) }}</div>
                    <div class="sdc-kpi-sub">Pipeline re-engagement</div>
                    <div class="sdc-kpi-badge-hint">
                        <span>Click to view call logs</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>

                <!-- 4. All One Time Calls -->
                <div class="sdc-kpi-card sdc-kpi-clickable" data-open-call-details data-metric="onetime_calls" title="Click to view One Time Calls details">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">All One Time Calls</span>
                        <div class="sdc-kpi-icon" style="background:#f5f3ff; color:#7c3aed;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value" style="color:#7c3aed;">{{ number_format($kpiStats['onetime_calls']) }}</div>
                    <div class="sdc-kpi-sub">Closed / No future followup</div>
                    <div class="sdc-kpi-badge-hint">
                        <span>Click to view call logs</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>

                <!-- 5. Total Dials Made -->
                <div class="sdc-kpi-card sdc-kpi-clickable" data-open-call-details data-metric="total_calls" title="Click to view Total Dials details">
                    <div class="sdc-kpi-top">
                        <span class="sdc-kpi-label">Total Dials</span>
                        <div class="sdc-kpi-icon" style="background:#f1f5f9; color:#475569;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </div>
                    </div>
                    <div class="sdc-kpi-value">{{ number_format($kpiStats['total_calls']) }}</div>
                    <div class="sdc-kpi-sub">Total call attempts</div>
                    <div class="sdc-kpi-badge-hint">
                        <span>Click to view call logs</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="sdc-filter-card">
            <form method="GET" action="{{ route('crm.day-closing.index') }}" class="sdc-filter-form">
                <div class="sdc-filter-group">
                    <label class="sdc-label">Date</label>
                    <input type="date" name="date" value="{{ request('date', $selectedDate) }}" class="sdc-input">
                </div>

                @if($isAdminLike || $isTlLike)
                    <div class="sdc-filter-group">
                        <label class="sdc-label">Sales Executive</label>
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
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search notes or team member..." class="sdc-input">
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="submit" class="sdc-btn sdc-btn-primary" style="height:40px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('crm.day-closing.index') }}" class="sdc-btn" style="height:40px; color:#64748b;" title="Reset filters">Reset</a>
                </div>
            </form>
        </div>

        <!-- Daily Closing Submissions Table -->
        <div class="sdc-card">
            <div class="sdc-card-head">
                <div>
                    <div class="sdc-card-title">Daily Closing Submissions</div>
                    <div class="sdc-card-sub">Review daily performance, call logs reconciliation, attachments, and commitments.</div>
                </div>
            </div>

            <div class="sdc-table-wrap">
                <table class="sdc-table">
                    <thead>
                        <tr>
                            <th style="width:40px; text-align:center;">#</th>
                            <th style="width:130px;">Closing Date</th>
                            <th style="width:180px;">Sales Executive</th>
                            <th style="width:280px;">Call Metrics Breakdown</th>
                            <th style="min-width:240px;">Day Closing Update</th>
                            <th style="width:200px;">Tomorrow&apos;s Plan &amp; Target</th>
                            <th style="width:120px; text-align:center;">Review Status</th>
                            <th style="width:140px; text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($closings as $idx => $closing)
                            @php
                                $closingData = [
                                    'id' => $closing->id,
                                    'user_id' => $closing->user_id,
                                    'user_name' => $closing->user?->name ?? 'Team Member',
                                    'designation' => $closing->user?->designation ?? 'Sales',
                                    'branch_name' => $closing->branch?->name ?? ($closing->user?->branch_name ?? 'Head Office'),
                                    'closing_date' => $closing->closing_date?->toDateString(),
                                    'formatted_date' => $closing->closing_date?->format('d M Y'),
                                    'total_calls' => (int) $closing->total_calls,
                                    'unique_calls' => (int) $closing->unique_calls,
                                    'new_calls' => (int) $closing->new_calls,
                                    'followup_calls' => (int) $closing->followup_calls,
                                    'onetime_calls' => (int) $closing->onetime_calls,
                                    'converted_count' => (int) $closing->converted_count,
                                    'quotations_count' => (int) $closing->quotations_count,
                                    'closing_notes' => $closing->closing_notes ?? '',
                                    'is_on_leave_tomorrow' => (bool) $closing->is_on_leave_tomorrow,
                                    'tomorrow_plans' => $closing->tomorrow_plans ?: [],
                                    'total_expected_value' => (float) $closing->total_expected_value,
                                    'plan_for_tomorrow' => $closing->plan_for_tomorrow ?? '',
                                    'status' => $closing->status,
                                    'can_edit' => ($closing->user_id === auth()->id() || $isAdminLike || $isTlLike),
                                    'update_url' => route('crm.day-closing.update', $closing->id),
                                ];
                                $closingJson = json_encode($closingData);
                            @endphp
                            <tr data-closing-id="{{ $closing->id }}">
                                <td style="text-align:center; font-weight:800; color:#64748b;">
                                    {{ $closings->firstItem() + $idx }}
                                </td>
                                <td>
                                    <div style="font-weight:800; color:#0f172a;">{{ $closing->closing_date?->format('d M Y') }}</div>
                                    <div style="font-size:11px; color:#64748b; font-weight:600;">{{ $closing->closing_date?->format('l') }}</div>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="width:32px; height:32px; border-radius:8px; background:#fff7ed; border:1px solid #fed7aa; color:#ea580c; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:12px; flex-shrink:0;">
                                            {{ strtoupper(substr($closing->user?->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <div style="min-width:0;">
                                            <div style="font-weight:800; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $closing->user?->name ?? 'Unknown' }}</div>
                                            <div style="font-size:11px; color:#64748b;">{{ $closing->branch?->name ?? ($closing->user?->branch_name ?? 'Sales') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; flex-wrap:wrap; gap:5px; align-items:center;">
                                        <span class="sdc-badge metric" style="background:#fff7ed; border-color:#fed7aa; color:#c2410c;" title="Unique Leads Contacted">
                                            <strong>{{ $closing->unique_calls }}</strong> Unique
                                        </span>
                                        <span class="sdc-badge metric" style="background:#f0fdf4; border-color:#bbf7d0; color:#15803d;" title="Total New (Fresh) Calls">
                                            <strong>{{ $closing->new_calls }}</strong> New
                                        </span>
                                        <span class="sdc-badge metric" style="background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8;" title="Follow-up Calls">
                                            <strong>{{ $closing->followup_calls }}</strong> Followup
                                        </span>
                                        <span class="sdc-badge metric" style="background:#f5f3ff; border-color:#ddd6fe; color:#6d28d9;" title="All One-Time Calls">
                                            <strong>{{ $closing->onetime_calls }}</strong> One-time
                                        </span>
                                        <span class="sdc-badge metric" title="Total Call Attempts">
                                            <strong>{{ $closing->total_calls }}</strong> Dials
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div style="line-height:1.5; color:#334155; font-size:12.5px; max-width:320px; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;" title="{{ $closing->closing_notes }}">
                                        {{ $closing->closing_notes }}
                                    </div>
                                </td>
                                <td>
                                    @if($closing->is_on_leave_tomorrow)
                                        <span class="sdc-badge" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a; font-weight:800; font-size:11px;">
                                            🏖️ On Leave Tomorrow
                                        </span>
                                    @elseif(!empty($closing->tomorrow_plans) && count($closing->tomorrow_plans) > 0)
                                        <div>
                                            <div style="font-weight:900; color:#16a34a; font-size:13px;">
                                                ₹{{ number_format($closing->total_expected_value) }}
                                            </div>
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                                {{ count($closing->tomorrow_plans) }} Planned Target{{ count($closing->tomorrow_plans) > 1 ? 's' : '' }}
                                            </div>
                                        </div>
                                    @elseif($closing->plan_for_tomorrow)
                                        <div style="font-size:11.5px; color:#475569; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $closing->plan_for_tomorrow }}">
                                            {{ $closing->plan_for_tomorrow }}
                                        </div>
                                    @else
                                        <span style="font-size:12px; color:#94a3b8;">None</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    @if($isAdminLike || $isTlLike)
                                        <select class="status-dropdown {{ $closing->status }}"
                                                data-update-url="{{ route('crm.day-closing.update-status', $closing->id) }}"
                                                title="Change review status">
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
                                    <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
                                        <button type="button" class="sdc-btn sdc-btn-outline open-view-btn"
                                                data-closing="{{ $closingJson }}"
                                                style="padding:4px 8px; font-size:12px; gap:4px;" title="View Details">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            <span>View</span>
                                        </button>
                                        @if($closing->user_id === auth()->id() || $isAdminLike || $isTlLike)
                                            <button type="button" class="sdc-btn open-edit-btn"
                                                    data-closing="{{ $closingJson }}"
                                                    style="padding:4px 8px; font-size:12px; gap:4px; background:#f8fafc;" title="Edit Update">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding:44px 20px; text-align:center; color:#64748b;">
                                    <div style="width:48px; height:48px; border-radius:12px; background:#f1f5f9; display:inline-flex; align-items:center; justify-content:center; color:#94a3b8; margin-bottom:10px;">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    </div>
                                    <div style="font-weight:700; color:#1e293b; font-size:14px;">No Day Closing Submissions Found</div>
                                    <div style="font-size:12px; margin-top:4px;">No daily closing records submitted for the selected filter criteria.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($closings->hasPages())
                <div style="padding:16px 22px; border-top:1px solid #e2e8f0;">
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
            <div class="sdc-card-title" id="closingModalTitle">Sales Department Day Closing Update</div>
            <div class="sdc-card-sub" id="closingModalSubTitle">Submit your daily call performance, tasks, and tomorrow&apos;s commitment.</div>
        </div>
        <button type="button" class="sdc-modal-close" data-close-closing-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="sdc-modal-body">
        <form id="dayClosingForm" method="POST" action="{{ route('crm.day-closing.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="closingFormMethod" value="POST">
            <input type="hidden" id="closingFormActionUrl" value="{{ route('crm.day-closing.store') }}">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <!-- Closing Date -->
                <div>
                    <label class="sdc-label">Closing Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="closing_date" id="modalClosingDate" max="{{ $today }}" value="{{ $selectedDate ?: $today }}" class="sdc-input" required>
                </div>

                <!-- Sales Executive (if Admin / TL) -->
                <div>
                    <label class="sdc-label">Sales Executive</label>
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

            <!-- Auto-Calculated Verified Call Stats Banner -->
            <div id="verifiedCallStatsBox" style="background:#fffaf5; border:1px solid #fed7aa; border-radius:10px; padding:12px 14px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                    <div style="font-size:11.5px; font-weight:800; color:#ea580c; text-transform:uppercase; letter-spacing:0.06em; display:flex; align-items:center; gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        <span>Verified CRM Call Logs for <span id="statsDateLabel">{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</span></span>
                    </div>
                    <span id="statsLoadingSpinner" style="font-size:11px; color:#ea580c; display:none;">Refreshing...</span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:8px;">
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Unique Calls</div>
                        <div id="statUniqueCalls" style="font-size:18px; font-weight:900; color:#ea580c;">0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Total New</div>
                        <div id="statNewCalls" style="font-size:18px; font-weight:900; color:#16a34a;">0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Followup</div>
                        <div id="statFollowupCalls" style="font-size:18px; font-weight:900; color:#2563eb;">0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">One-Time</div>
                        <div id="statOnetimeCalls" style="font-size:18px; font-weight:900; color:#7c3aed;">0</div>
                    </div>
                    <div style="background:#fff; border:1px solid #ffedd5; padding:8px 10px; border-radius:8px;">
                        <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase;">Total Dials</div>
                        <div id="statTotalCalls" style="font-size:18px; font-weight:900; color:#0f172a;">0</div>
                    </div>
                </div>
            </div>

            <!-- Closing Update Notes -->
            <div style="margin-bottom:16px;">
                <label class="sdc-label">Day Closing Update Notes <span style="color:#ef4444;">*</span></label>
                <textarea name="closing_notes" id="modalClosingNotes" class="sdc-textarea" required placeholder="Write details about client interactions, discussions, deals progressed, challenges faced, or key accomplishments today..."></textarea>
            </div>

            <!-- Tomorrow's Plan & Committed Target Section -->
            <div style="margin-bottom:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <label class="sdc-label" style="margin-bottom:2px; color:#0f172a; font-size:12px;">Tomorrow&apos;s Plan &amp; Committed Target</label>
                        <div style="font-size:11px; color:#64748b;">Add target companies, products &amp; expected deal values for tomorrow</div>
                    </div>
                    <!-- Planned Leave Checkbox -->
                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:12px; font-weight:700; color:#b45309; background:#fffbeb; padding:6px 12px; border-radius:999px; border:1px solid #fde68a; user-select:none; transition:all 0.15s;">
                        <input type="checkbox" name="is_on_leave_tomorrow" id="modalIsOnLeaveTomorrow" value="1" style="accent-color:#ea580c; width:16px; height:16px; cursor:pointer;">
                        <span>🏖️ Planned Leave Tomorrow</span>
                    </label>
                </div>

                <!-- Banner when On Leave Tomorrow is checked -->
                <div id="modalLeaveTomorrowBanner" style="display:none; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px; color:#92400e; font-size:13px; font-weight:600; align-items:center; gap:10px;">
                    <span style="font-size:22px;">🏖️</span>
                    <div>
                        <div style="font-weight:800; color:#b45309;">Scheduled on Planned Leave Tomorrow</div>
                        <div style="font-size:11.5px; color:#a16207; font-weight:500;">No company targets or commitment values required for tomorrow.</div>
                    </div>
                </div>

                <!-- Dynamic Target Deals Container -->
                <div id="modalTomorrowPlansContainer">
                    <!-- Column Header Hints -->
                    <div style="display:grid; grid-template-columns:1.2fr 1.2fr 140px 36px; gap:8px; padding:0 10px 6px; font-size:10.5px; font-weight:800; text-transform:uppercase; color:#64748b; letter-spacing:0.05em;">
                        <div>Company / Client Name</div>
                        <div>Product / Service</div>
                        <div>Expected Value (₹)</div>
                        <div></div>
                    </div>

                    <div id="modalTomorrowPlansList" style="display:flex; flex-direction:column; gap:8px;">
                        <!-- Dynamic deal item rows inserted here via JS -->
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:12px; padding-top:12px; border-top:1px dashed #cbd5e1; flex-wrap:wrap; gap:10px;">
                        <button type="button" id="modalAddTomorrowPlanBtn" class="sdc-btn" style="padding:6px 14px; font-size:12px; border-color:#fed7aa; color:#ea580c; background:#fff7ed;">
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

            <!-- Submit Buttons -->
            <div style="display:flex; align-items:center; justify-content:flex-end; gap:10px; border-top:1px solid #e2e8f0; padding-top:16px;">
                <button type="button" class="sdc-btn" data-close-closing-modal>Cancel</button>
                <button type="submit" id="closingSubmitBtn" class="sdc-btn sdc-btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Save Day Closing Update</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: View Day Closing Details Modal -->
<div class="sdc-modal-overlay" data-view-modal-overlay></div>
<div class="sdc-modal" data-view-modal style="width:min(760px, calc(100vw - 32px));">
    <div class="sdc-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:38px; height:38px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; color:#ea580c; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
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
        <!-- Call metrics chips -->
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid #e2e8f0;">
            <span class="sdc-badge metric" style="background:#fff7ed; border-color:#fed7aa; color:#c2410c; padding:6px 12px; font-size:12px;">
                <strong id="viewUniqueCalls" style="font-size:14px;">0</strong> Unique Calls
            </span>
            <span class="sdc-badge metric" style="background:#f0fdf4; border-color:#bbf7d0; color:#15803d; padding:6px 12px; font-size:12px;">
                <strong id="viewNewCalls" style="font-size:14px;">0</strong> Total New Calls
            </span>
            <span class="sdc-badge metric" style="background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; padding:6px 12px; font-size:12px;">
                <strong id="viewFollowupCalls" style="font-size:14px;">0</strong> Followup Calls
            </span>
            <span class="sdc-badge metric" style="background:#f5f3ff; border-color:#ddd6fe; color:#6d28d9; padding:6px 12px; font-size:12px;">
                <strong id="viewOnetimeCalls" style="font-size:14px;">0</strong> All One-Time
            </span>
            <span class="sdc-badge metric" style="padding:6px 12px; font-size:12px;">
                <strong id="viewTotalCalls" style="font-size:14px;">0</strong> Total Dials
            </span>
        </div>

        <!-- Closing Notes -->
        <div style="margin-bottom:18px;">
            <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px;">Day Closing Update Details</div>
            <div id="viewClosingNotes" style="font-size:13.5px; line-height:1.65; color:#1e293b; background:#f8fafc; padding:14px; border-radius:10px; border:1px solid #e2e8f0; white-space:pre-wrap;"></div>
        </div>

        <!-- Tomorrow's Plan & Committed Target -->
        <div id="viewPlanTomorrowContainer" style="margin-bottom:18px;">
            <div style="font-size:11px; font-weight:800; color:#ea580c; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <span>Tomorrow&apos;s Plan &amp; Committed Target</span>
            </div>

            <!-- When on planned leave -->
            <div id="viewLeaveTomorrowBanner" style="display:none; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px; color:#92400e; font-size:13px; font-weight:700; display:flex; align-items:center; gap:10px;">
                <span style="font-size:22px;">🏖️</span>
                <div>
                    <div>Planned Leave Tomorrow</div>
                    <div style="font-size:11.5px; color:#a16207; font-weight:500;">Executive is scheduled on leave tomorrow.</div>
                </div>
            </div>

            <!-- When target plans exist -->
            <div id="viewTomorrowPlansTableWrap" style="display:none; border:1px solid #fed7aa; border-radius:10px; overflow:hidden; background:#fff;">
                <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                    <thead>
                        <tr style="background:#fffaf5; border-bottom:1px solid #fed7aa;">
                            <th style="width:40px; text-align:center; padding:9px 12px; font-size:11px; font-weight:800; color:#7c2d12; text-transform:uppercase;">#</th>
                            <th style="text-align:left; padding:9px 12px; font-size:11px; font-weight:800; color:#7c2d12; text-transform:uppercase;">Company / Client</th>
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

            <!-- Fallback text for legacy plain text -->
            <div id="viewPlanTomorrowLegacy" style="display:none; font-size:13px; line-height:1.6; color:#334155; background:#fffaf5; padding:12px 14px; border-radius:10px; border:1px solid #fed7aa; white-space:pre-wrap;"></div>
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

<!-- Modal 3: Call Details Modal (Live CRM Logs for Clicked Metric) -->
<div class="sdc-modal-overlay" data-call-details-modal-overlay></div>
<div class="sdc-modal" data-call-details-modal style="width:min(1150px, calc(100vw - 32px)); max-height:calc(100vh - 36px);">
    <div class="sdc-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; color:#ea580c; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            </div>
            <div>
                <div class="sdc-card-title" id="callDetailsModalTitle">Call Update Details</div>
                <div class="sdc-card-sub" id="callDetailsModalSubTitle">CRM Live Call Logs</div>
            </div>
        </div>
        <button type="button" class="sdc-modal-close" data-close-call-details-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="sdc-modal-body" style="padding:18px 22px;">
        <!-- Top Toolbar: Quick search & record counter -->
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap;">
            <div style="position:relative; flex:1; min-width:240px; max-width:420px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="callDetailsSearchInput" class="sdc-input" style="padding-left:36px; height:38px; font-size:13px;" placeholder="Search company, contact, phone, caller, outcome...">
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="sdc-badge metric" id="callDetailsCounter" style="padding:6px 12px; font-size:12px; background:#fff7ed; border-color:#fed7aa; color:#c2410c;">
                    Total Records: <strong id="callDetailsCountNumber" style="font-size:13px; margin-left:4px;">0</strong>
                </span>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="callDetailsLoading" style="display:none; text-align:center; padding:45px 20px;">
            <div style="display:inline-flex; align-items:center; gap:10px; font-size:14px; font-weight:700; color:#ea580c;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="sdc-spin"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                <span>Loading call records from CRM...</span>
            </div>
        </div>

        <!-- Table Container -->
        <div id="callDetailsTableWrap" class="sdc-table-wrap" style="border:1px solid #e2e8f0; border-radius:10px; max-height:460px; overflow-y:auto;">
            <table class="sdc-table" style="min-width:920px;">
                <thead>
                    <tr style="position:sticky; top:0; z-index:2; background:#f8fafc;">
                        <th style="width:45px;">#</th>
                        <th>Lead / Company</th>
                        <th>Phone / Mobile</th>
                        <th>Time &amp; Type</th>
                        <th>Caller</th>
                        <th>Outcome / Category</th>
                        <th>Next Follow-up</th>
                        <th>Discussion Notes</th>
                    </tr>
                </thead>
                <tbody id="callDetailsTableBody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="callDetailsEmptyState" style="display:none; text-align:center; padding:45px 20px;">
            <div style="width:48px; height:48px; border-radius:12px; background:#f1f5f9; color:#94a3b8; display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
            </div>
            <div style="font-size:14px; font-weight:800; color:#334155;">No Call Records Found</div>
            <div style="font-size:12px; color:#64748b; margin-top:4px;">No call entries match this metric on the selected date.</div>
        </div>

        <!-- Modal Footer Actions -->
        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:16px; border-top:1px solid #e2e8f0; padding-top:14px; flex-wrap:wrap;">
            <div style="font-size:11.5px; color:#64748b;">
                Showing CRM call interactions logged for <span id="callDetailsDateNote" style="font-weight:700; color:#0f172a;"></span>
            </div>
            <button type="button" class="sdc-btn" data-close-call-details-modal>Close</button>
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

    // Call Details Modal (Modal 3)
    const callDetailsModal = document.querySelector('[data-call-details-modal]');
    const callDetailsOverlay = document.querySelector('[data-call-details-modal-overlay]');
    const closeCallDetailsBtns = document.querySelectorAll('[data-close-call-details-modal]');
    const callDetailsTitle = document.getElementById('callDetailsModalTitle');
    const callDetailsSubTitle = document.getElementById('callDetailsModalSubTitle');
    const callDetailsSearchInput = document.getElementById('callDetailsSearchInput');
    const callDetailsCountNumber = document.getElementById('callDetailsCountNumber');
    const callDetailsLoading = document.getElementById('callDetailsLoading');
    const callDetailsTableWrap = document.getElementById('callDetailsTableWrap');
    const callDetailsTableBody = document.getElementById('callDetailsTableBody');
    const callDetailsEmptyState = document.getElementById('callDetailsEmptyState');
    const callDetailsDateNote = document.getElementById('callDetailsDateNote');

    // Elements
    const modalClosingDate = document.getElementById('modalClosingDate');
    const modalUserId = document.getElementById('modalUserId');
    const modalClosingNotes = document.getElementById('modalClosingNotes');
    const modalIsOnLeaveTomorrow = document.getElementById('modalIsOnLeaveTomorrow');
    const modalLeaveTomorrowBanner = document.getElementById('modalLeaveTomorrowBanner');
    const modalTomorrowPlansContainer = document.getElementById('modalTomorrowPlansContainer');
    const modalTomorrowPlansList = document.getElementById('modalTomorrowPlansList');
    const modalAddTomorrowPlanBtn = document.getElementById('modalAddTomorrowPlanBtn');
    const modalTomorrowTotalValue = document.getElementById('modalTomorrowTotalValue');
    const closingModalTitle = document.getElementById('closingModalTitle');
    const dayClosingForm = document.getElementById('dayClosingForm');
    const closingFormMethod = document.getElementById('closingFormMethod');
    const closingSubmitBtn = document.getElementById('closingSubmitBtn');

    // Stats display elements
    const statsDateLabel = document.getElementById('statsDateLabel');
    const statsLoadingSpinner = document.getElementById('statsLoadingSpinner');
    const statUniqueCalls = document.getElementById('statUniqueCalls');
    const statNewCalls = document.getElementById('statNewCalls');
    const statFollowupCalls = document.getElementById('statFollowupCalls');
    const statOnetimeCalls = document.getElementById('statOnetimeCalls');
    const statTotalCalls = document.getElementById('statTotalCalls');

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

    function setCallDetailsModalState(isOpen) {
        if (!callDetailsModal || !callDetailsOverlay) return;
        callDetailsModal.classList.toggle('is-open', isOpen);
        callDetailsOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    closeCallDetailsBtns.forEach(btn => btn.addEventListener('click', () => setCallDetailsModalState(false)));
    if (callDetailsOverlay) callDetailsOverlay.addEventListener('click', () => setCallDetailsModalState(false));

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Tomorrow Plans Row Management & Planned Leave
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
                <input type="text" name="tomorrow_plans[${rowIndex}][company_name]" class="sdc-input tomorrow-company-input" style="height:36px; font-size:12.5px;" placeholder="Company / Client Name" value="${escapeHtml(company)}">
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

    // Dynamic stats fetch when date or user changes
    function fetchLiveStats() {
        const dateVal = modalClosingDate ? modalClosingDate.value : '';
        const userVal = modalUserId ? modalUserId.value : '';
        if (!dateVal) return;

        if (statsLoadingSpinner) statsLoadingSpinner.style.display = 'inline';

        fetch(`/crm/day-closing/stats?closing_date=${encodeURIComponent(dateVal)}&user_id=${encodeURIComponent(userVal)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (statsLoadingSpinner) statsLoadingSpinner.style.display = 'none';
            if (res.success && res.stats) {
                const s = res.stats;
                if (statUniqueCalls) statUniqueCalls.textContent = s.unique_calls;
                if (statNewCalls) statNewCalls.textContent = s.new_calls;
                if (statFollowupCalls) statFollowupCalls.textContent = s.followup_calls;
                if (statOnetimeCalls) statOnetimeCalls.textContent = s.onetime_calls;
                if (statTotalCalls) statTotalCalls.textContent = s.total_calls;
                if (statsDateLabel) statsDateLabel.textContent = res.date;

                // If editing is not explicitly locked and existing closing found for this date
                if (!currentEditingClosing && res.exists && res.closing) {
                    // Prepopulate with existing closing data
                    if (modalClosingNotes) modalClosingNotes.value = res.closing.closing_notes || '';
                    populateTomorrowPlans(res.closing.tomorrow_plans || [], res.closing.is_on_leave_tomorrow);
                    if (closingModalTitle) closingModalTitle.textContent = 'Edit Day Closing Update';
                    if (closingFormMethod) closingFormMethod.value = 'PATCH';
                    if (dayClosingForm) dayClosingForm.action = res.closing.update_url;
                } else if (!currentEditingClosing && !res.exists) {
                    if (closingModalTitle) closingModalTitle.textContent = 'Sales Department Day Closing Update';
                    if (closingFormMethod) closingFormMethod.value = 'POST';
                    if (dayClosingForm) dayClosingForm.action = '{{ route("crm.day-closing.store") }}';
                    populateTomorrowPlans([], false);
                }
            }
        })
        .catch(err => {
            if (statsLoadingSpinner) statsLoadingSpinner.style.display = 'none';
            console.error('Error fetching live call stats:', err);
        });
    }

    if (modalClosingDate) {
        modalClosingDate.addEventListener('change', fetchLiveStats);
    }
    if (modalUserId) {
        modalUserId.addEventListener('change', fetchLiveStats);
    }

    // Open Add Modal
    openClosingBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            currentEditingClosing = null;
            if (closingModalTitle) closingModalTitle.textContent = 'Sales Department Day Closing Update';
            if (closingFormMethod) closingFormMethod.value = 'POST';
            if (dayClosingForm) {
                dayClosingForm.action = '{{ route("crm.day-closing.store") }}';
                dayClosingForm.reset();
            }
            if (modalClosingDate) modalClosingDate.value = '{{ $selectedDate ?: $today }}';
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
            document.getElementById('viewModalDateBranch').textContent = (data.formatted_date || data.closing_date || '') + ' | ' + (data.branch_name || 'Sales');
            document.getElementById('viewUniqueCalls').textContent = data.unique_calls ?? 0;
            document.getElementById('viewNewCalls').textContent = data.new_calls ?? 0;
            document.getElementById('viewFollowupCalls').textContent = data.followup_calls ?? 0;
            document.getElementById('viewOnetimeCalls').textContent = data.onetime_calls ?? 0;
            document.getElementById('viewTotalCalls').textContent = data.total_calls ?? 0;
            document.getElementById('viewClosingNotes').textContent = data.closing_notes || 'No closing notes recorded.';

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

            // Hook edit shortcut button
            const editShortcutBtn = document.getElementById('viewEditShortcutBtn');
            if (editShortcutBtn) {
                if (data.can_edit) {
                    editShortcutBtn.style.display = 'inline-flex';
                    editShortcutBtn.onclick = function () {
                        setViewModalState(false);
                        const tr = viewBtn.closest('tr');
                        const editBtn = tr ? tr.querySelector('.open-edit-btn') : null;
                        if (editBtn) {
                            editBtn.click();
                        }
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
            if (closingModalTitle) closingModalTitle.textContent = 'Edit Day Closing Update (' + (data.user_name || '') + ')';
            if (closingFormMethod) closingFormMethod.value = 'PATCH';
            if (dayClosingForm) dayClosingForm.action = data.update_url;

            if (modalClosingDate) {
                modalClosingDate.value = data.closing_date || '';
            }
            if (modalUserId && data.user_id) {
                modalUserId.value = data.user_id;
            }
            if (modalClosingNotes) {
                modalClosingNotes.value = data.closing_notes || '';
            }

            // Immediately display saved metric numbers in the modal KPI boxes
            if (statUniqueCalls) statUniqueCalls.textContent = data.unique_calls ?? 0;
            if (statNewCalls) statNewCalls.textContent = data.new_calls ?? 0;
            if (statFollowupCalls) statFollowupCalls.textContent = data.followup_calls ?? 0;
            if (statOnetimeCalls) statOnetimeCalls.textContent = data.onetime_calls ?? 0;
            if (statTotalCalls) statTotalCalls.textContent = data.total_calls ?? 0;
            if (statsDateLabel) statsDateLabel.textContent = data.formatted_date || data.closing_date || '';

            populateTomorrowPlans(data.tomorrow_plans || [], data.is_on_leave_tomorrow);

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
                method: 'PATCH',
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

    // Call Details Modal Logic
    let currentCallRecords = [];

    function renderCallRows(rows) {
        if (!callDetailsTableBody) return;
        callDetailsTableBody.innerHTML = '';

        if (!rows || rows.length === 0) {
            if (callDetailsTableWrap) callDetailsTableWrap.style.display = 'none';
            if (callDetailsEmptyState) callDetailsEmptyState.style.display = 'block';
            if (callDetailsCountNumber) callDetailsCountNumber.textContent = '0';
            return;
        }

        if (callDetailsTableWrap) callDetailsTableWrap.style.display = 'block';
        if (callDetailsEmptyState) callDetailsEmptyState.style.display = 'none';
        if (callDetailsCountNumber) callDetailsCountNumber.textContent = rows.length;

        rows.forEach((row, idx) => {
            const tr = document.createElement('tr');

            // Format phone link
            let phoneHtml = '<span style="color:#94a3b8;">—</span>';
            if (row.mobile_number && row.mobile_number !== '—') {
                phoneHtml = `
                    <a href="tel:${escapeHtml(row.mobile_number)}" style="font-weight:700; color:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:5px;" title="Call ${escapeHtml(row.mobile_number)}">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        <span>${escapeHtml(row.mobile_number)}</span>
                    </a>
                `;
            }

            // Outcome badge
            const outcomeBg = row.outcome_color?.bg || '#f1f5f9';
            const outcomeColor = row.outcome_color?.text || '#475569';
            const outcomeBadgeHtml = `
                <div style="display:inline-flex; flex-direction:column; gap:3px;">
                    <span class="sdc-badge" style="background:${outcomeBg}; color:${outcomeColor}; border:1px solid ${outcomeColor}33; font-weight:800; font-size:11px;">
                        ${escapeHtml(row.outcome || 'Call Logged')}
                    </span>
                    ${row.outcome_subcategory ? `<span style="font-size:10.5px; color:#64748b; font-weight:600; padding-left:2px;">${escapeHtml(row.outcome_subcategory)}</span>` : ''}
                </div>
            `;

            // Call type & time badge
            const isIncoming = (row.call_type || '').toLowerCase() === 'incoming';
            const callTypeBg = isIncoming ? '#eff6ff' : '#fff7ed';
            const callTypeColor = isIncoming ? '#1d4ed8' : '#ea580c';
            const timeHtml = `
                <div>
                    <div style="font-weight:700; color:#0f172a; font-size:12px;">${escapeHtml(row.called_time)}</div>
                    <span style="font-size:10px; font-weight:800; text-transform:uppercase; background:${callTypeBg}; color:${callTypeColor}; padding:1px 6px; border-radius:4px; display:inline-block; margin-top:2px;">
                        ${escapeHtml(row.call_type)}
                    </span>
                </div>
            `;

            // Next follow up
            let followUpHtml = '<span style="color:#94a3b8; font-size:12px;">—</span>';
            if (row.next_follow_up && row.next_follow_up !== '—') {
                followUpHtml = `
                    <div style="display:inline-flex; align-items:center; gap:5px; font-weight:700; color:#0f172a; font-size:11.5px; background:#f8fafc; padding:3px 7px; border-radius:6px; border:1px solid #e2e8f0; white-space:nowrap;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span>${escapeHtml(row.next_follow_up)}</span>
                    </div>
                `;
            }

            // Lead info
            const leadLinkHtml = `
                <div>
                    <a href="${escapeHtml(row.lead_url)}" target="_blank" style="font-weight:800; color:#0f172a; text-decoration:none; display:inline-block; line-height:1.3; font-size:13px;" onmouseover="this.style.color='#ea580c'" onmouseout="this.style.color='#0f172a'">
                        ${escapeHtml(row.company_name)}
                    </a>
                    <div style="font-size:11px; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:6px;">
                        <span style="font-family:monospace; font-weight:700; color:#ea580c;">${escapeHtml(row.lead_id)}</span>
                        ${row.contact_name && row.contact_name !== '—' && row.contact_name !== row.company_name ? `<span>•</span><span>${escapeHtml(row.contact_name)}</span>` : ''}
                    </div>
                </div>
            `;

            tr.innerHTML = `
                <td style="color:#94a3b8; font-weight:700; font-size:11.5px;">${idx + 1}</td>
                <td>${leadLinkHtml}</td>
                <td>${phoneHtml}</td>
                <td>${timeHtml}</td>
                <td>
                    <div style="font-weight:700; color:#1e293b; font-size:12px;">${escapeHtml(row.caller_name)}</div>
                </td>
                <td>${outcomeBadgeHtml}</td>
                <td>${followUpHtml}</td>
                <td style="max-width:260px;">
                    <div style="font-size:12px; line-height:1.45; color:#334155; white-space:pre-wrap; word-break:break-word; max-height:75px; overflow-y:auto;" title="${escapeHtml(row.notes)}">
                        ${escapeHtml(row.notes)}
                    </div>
                </td>
            `;

            callDetailsTableBody.appendChild(tr);
        });
    }

    // Live search filter inside modal
    if (callDetailsSearchInput) {
        callDetailsSearchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            if (!query) {
                renderCallRows(currentCallRecords);
                return;
            }

            const filtered = currentCallRecords.filter(r => {
                return (r.company_name && r.company_name.toLowerCase().includes(query)) ||
                       (r.contact_name && r.contact_name.toLowerCase().includes(query)) ||
                       (r.mobile_number && r.mobile_number.toLowerCase().includes(query)) ||
                       (r.lead_id && r.lead_id.toLowerCase().includes(query)) ||
                       (r.caller_name && r.caller_name.toLowerCase().includes(query)) ||
                       (r.outcome && r.outcome.toLowerCase().includes(query)) ||
                       (r.outcome_subcategory && r.outcome_subcategory.toLowerCase().includes(query)) ||
                       (r.notes && r.notes.toLowerCase().includes(query));
            });

            renderCallRows(filtered);
        });
    }

    // Function to fetch call details when a KPI card is clicked
    function openCallDetailsModal(metric, titleHint) {
        const currentDate = '{{ request("date", $selectedDate ?: $today) }}';
        const currentUserId = '{{ request("user_id") }}';
        const currentBranchId = '{{ request("branch_id") }}';

        if (callDetailsTitle) callDetailsTitle.textContent = titleHint || 'Call Update Details';
        if (callDetailsSubTitle) callDetailsSubTitle.textContent = `CRM Call Logs for ${currentDate}`;
        if (callDetailsDateNote) callDetailsDateNote.textContent = currentDate;
        if (callDetailsSearchInput) callDetailsSearchInput.value = '';
        if (callDetailsTableWrap) callDetailsTableWrap.style.display = 'none';
        if (callDetailsEmptyState) callDetailsEmptyState.style.display = 'none';
        if (callDetailsLoading) callDetailsLoading.style.display = 'block';
        if (callDetailsCountNumber) callDetailsCountNumber.textContent = '...';

        setCallDetailsModalState(true);

        const params = new URLSearchParams({
            metric: metric,
            date: currentDate,
        });
        if (currentUserId) params.append('user_id', currentUserId);
        if (currentBranchId) params.append('branch_id', currentBranchId);

        fetch(`/crm/day-closing/call-details?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (callDetailsLoading) callDetailsLoading.style.display = 'none';
            if (res.success) {
                if (callDetailsTitle && res.title) {
                    callDetailsTitle.textContent = res.title;
                }
                if (callDetailsSubTitle) {
                    callDetailsSubTitle.textContent = `CRM Call Logs for ${res.date}`;
                }
                if (callDetailsDateNote) {
                    callDetailsDateNote.textContent = res.date;
                }
                currentCallRecords = res.rows || [];
                renderCallRows(currentCallRecords);
            } else {
                currentCallRecords = [];
                renderCallRows([]);
            }
        })
        .catch(err => {
            if (callDetailsLoading) callDetailsLoading.style.display = 'none';
            console.error('Error fetching call details:', err);
            currentCallRecords = [];
            renderCallRows([]);
        });
    }

    // Attach click listeners to KPI cards
    document.querySelectorAll('[data-open-call-details]').forEach(card => {
        card.addEventListener('click', function () {
            const metric = this.getAttribute('data-metric');
            const label = this.querySelector('.sdc-kpi-label')?.textContent || 'Call Details';
            openCallDetailsModal(metric, label);
        });
    });

    // Escape key listener
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setModalState(false);
            setViewModalState(false);
            setCallDetailsModalState(false);
        }
    });
});
</script>
@endpush
