@extends('layouts.app')

@section('title', 'Projects Dashboard')

@push('styles')
<style>
.pjd-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pjd-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.pjd-title { font-size:24px; font-weight:900; color:#111827; }
.pjd-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.pjd-chip { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:12px; font-weight:800; }
.pjd-body { padding:22px 28px 34px; display:grid; gap:18px; }
.pjd-card { background:#fff; border:1px solid #eee7df; border-radius:10px; overflow:hidden; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.pjd-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:18px 20px; border-bottom:1px solid #f2ede8; background:#fffdfb; }
.pjd-card-title { font-size:16px; font-weight:900; color:#111827; }
.pjd-card-sub { margin-top:4px; font-size:12px; color:#7c7c7c; }
.pjd-card-body { padding:20px; }
.pjd-filters { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; align-items:end; }
.pjd-field { display:grid; gap:7px; }
.pjd-label { font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#7c7c7c; }
.pjd-input, .pjd-select { width:100%; min-height:44px; padding:10px 12px; border:1px solid #d7dce2; border-radius:12px; background:#fff; color:#111827; font-size:14px; }
.pjd-input:focus, .pjd-select:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.12); }
.pjd-filter-actions { display:flex; gap:10px; flex-wrap:wrap; }
.pjd-btn { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 14px; border-radius:12px; border:1px solid #d7dce2; background:#fff; color:#111827; text-decoration:none; font-size:13px; font-weight:800; cursor:pointer; }
.pjd-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.pjd-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; }
.pjd-stat { position:relative; overflow:hidden; background:var(--stat-gradient, linear-gradient(135deg, var(--stat-color, #ea580c) 0%, var(--stat-color, #f97316) 100%)); border:none; border-radius:16px; padding:20px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1),0 8px 10px -6px rgba(0,0,0,0.05); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; min-height:140px; }
.pjd-stat:hover { transform:translateY(-5px); box-shadow:0 20px 25px -5px rgba(0,0,0,0.15),0 10px 10px -5px rgba(0,0,0,0.08); }
.pjd-stat-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.pjd-stat-icon { display:flex; align-items:center; justify-content:center; width:38px; height:38px; border-radius:12px; background:rgba(255,255,255,0.2); color:#fff; font-size:16px; backdrop-filter:blur(4px); }
.pjd-stat-label { font-size:11px; font-weight:800; color:rgba(255,255,255,0.95); text-transform:uppercase; letter-spacing:.06em; }
.pjd-stat-value { font-size:22px; font-weight:900; color:#fff; line-height:1.2; margin-top:8px; display:flex; justify-content:space-between; align-items:center; }
.pjd-stat-sub { margin-top:12px; font-size:12px; color:rgba(255,255,255,0.85); font-weight:500; }
.pjd-table-wrap { overflow-x:auto; }
.pjd-table { width:100%; border-collapse:collapse; min-width:880px; }
.pjd-table th { padding:12px 14px; text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#7c7c7c; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.pjd-table td { padding:14px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:top; }
.pjd-table tbody tr:hover td { background:#fffaf5; }
.pjd-product { font-weight:800; color:#111827; }
.pjd-meta { margin-top:4px; font-size:11px; color:#64748b; }
.pjd-pill { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid transparent; text-transform:uppercase; letter-spacing:.06em; }
.pjd-pill.pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.pjd-pill.allocated { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.pjd-pill.status-ontrack { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
.pjd-pill.status-hold { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.pjd-pill.status-delivered { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.pjd-money { font-weight:800; color:#0f172a; white-space:nowrap; }
.pjd-money.received { color:#15803d; }
.pjd-money.balance { color:#c2410c; }
.pjd-link { color:#ea580c; font-weight:800; text-decoration:none; }
.pjd-link:hover { text-decoration:underline; }
.pjd-kpis { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
.pjd-kpi { border:1px solid #f2ede8; border-radius:18px; padding:16px; background:linear-gradient(180deg,#fff 0%,#fffaf7 100%); }
.pjd-kpi-label { font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#7c7c7c; }
.pjd-kpi-value { margin-top:8px; font-size:28px; font-weight:900; color:#111827; }
.pjd-kpi-sub { margin-top:8px; font-size:12px; color:#64748b; }
.pjd-empty { padding:28px 20px; color:#7c7c7c; font-size:13px; text-align:center; }
.pjd-highlight { display:inline-flex; align-items:center; gap:8px; padding:7px 11px; border-radius:999px; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; font-size:11px; font-weight:800; }
.pjd-update-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.42); z-index:1200; display:none; }
.pjd-update-modal-overlay.is-open { display:block; }
.pjd-update-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(860px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:24px; box-shadow:0 24px 60px rgba(15,23,42,.22); z-index:1210; display:none; }
.pjd-update-modal.is-open { display:block; }
.pjd-update-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:20px 22px; border-bottom:1px solid #eef2f7; background:#fffdfb; }
.pjd-update-modal-body { padding:22px; }
.pjd-update-modal-close { width:42px; height:42px; border-radius:14px; border:1px solid #e5e7eb; background:#fff; color:#334155; font-size:16px; cursor:pointer; }
.ps-update-form { display:grid; gap:16px; }
.ps-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; align-items:start; }
.ps-form-grid-single { grid-template-columns:1fr; }
.ps-form-editor { display:grid; gap:8px; }
.ps-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; }
.ps-input, .ps-textarea { width:100%; border:1px solid #dbe1e8; border-radius:14px; background:#fff; font-size:14px; color:#111827; }
.ps-input { padding:12px 14px; min-height:48px; }
.ps-textarea { min-height:240px; padding:14px; resize:vertical; }
.ps-input:focus, .ps-textarea:focus { outline:none; border-color:#fdba74; box-shadow:0 0 0 4px rgba(254,95,4,.12); }
.ps-modal-note { margin-top:14px; padding:14px 16px; border:1px solid #e5e7eb; border-radius:16px; background:#fafcff; display:grid; gap:8px; }
.ps-modal-note strong { font-size:13px; color:#0f172a; }
.ps-modal-note span { font-size:12px; color:#64748b; line-height:1.6; }
.ps-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.ps-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 14px; border-radius:12px; border:1px solid #d1d5db; background:#fff; color:#111827; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; }
.ps-btn-primary { background:#166534; border-color:#166534; color:#fff; }
.ps-allocate-note { margin-top:10px; font-size:12px; color:#475569; }
.tox-tinymce { border-radius:16px !important; border-color:#dbe1e8 !important; }
@media (max-width: 1200px) {
    .pjd-filters { grid-template-columns:repeat(3,minmax(0,1fr)); }
}
@media (max-width: 768px) {
    .pjd-topbar { padding:18px 16px; flex-direction:column; }
    .pjd-body { padding:18px 16px 24px; }
    .pjd-filters, .pjd-kpis { grid-template-columns:1fr; }
    .ps-form-grid { grid-template-columns:1fr; }
    .pjd-update-modal { width:min(100vw - 20px, 860px); max-height:calc(100vh - 20px); }
    .pjd-update-modal-head { padding:18px 16px; }
    .pjd-update-modal-body { padding:16px; }
}
</style>
@endpush

@section('content')
@if($isDesigningDashboard ?? false)
    @php
        $pageTitle = 'Designing Projects Dashboard';
        $pageCrumb = 'Modules > Projects > Designing Dashboard';
    @endphp
    <div class="pjd-page">
        <div class="pjd-topbar">
            <div>
                <div class="pjd-title">{{ $pageTitle }}</div>
                <div class="pjd-breadcrumb">{{ $pageCrumb }}</div>
            </div>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                @if($isAdminLike ?? false)
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="pjd-label" style="font-weight:800; font-size:11px; color:#7c7c7c;">Dashboard View:</span>
                        <select onchange="window.location.href = '{{ route('projects.dashboard') }}?dashboard_type=' + this.value" class="pjd-select" style="min-height:36px; padding:6px 12px; border-radius:8px; width:160px; font-size:13px; font-weight:800; border:1px solid #eee7df;">
                            <option value="production" @selected(($selectedDashboard ?? 'design') === 'production')>Production</option>
                            <option value="design" @selected(($selectedDashboard ?? 'design') === 'design')>Designing</option>
                        </select>
                    </div>
                @endif
                <div class="pjd-chip" style="background:#f0fdf4; border-color:#bbf7d0; color:#166534;">Designing Team</div>
            </div>
        </div>

        <div class="pjd-body">
            @if(session('success'))
                <div style="padding: 14px 20px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 18px; box-shadow: 0 4px 12px rgba(22, 101, 52, 0.05);">
                    <i class="bi bi-check-circle-fill" style="margin-right: 8px;"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div style="padding: 14px 20px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 18px; box-shadow: 0 4px 12px rgba(153, 27, 27, 0.05);">
                    <i class="bi bi-exclamation-triangle-fill" style="margin-right: 8px;"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div style="padding: 14px 20px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 12px; font-size: 14px; font-weight: 600; margin-bottom: 18px; box-shadow: 0 4px 12px rgba(153, 27, 27, 0.05);">
                    <div style="margin-bottom: 6px;"><i class="bi bi-x-circle-fill" style="margin-right: 8px;"></i> Please fix the following errors:</div>
                    <ul style="margin: 0; padding-left: 24px; font-weight: 500;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{-- Metrics Cards --}}
            <div class="pjd-stats" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                {{-- Daily Task Goal Card --}}
                {{--  <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Daily Task Goal</span>
                        <span class="pjd-stat-icon"><i class="bi bi-bullseye"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>P: {{ $stats['daily_target_posters'] }}</span>
                        <span>V: {{ $stats['daily_target_videos'] }}</span>
                    </div>
                    <div class="pjd-stat-sub">Today's target posters/videos</div>
                </div>  --}}



                {{-- Total Accounts Card --}}
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #0e7490 0%, #06b6d4 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Total Accounts</span>
                        <span class="pjd-stat-icon"><i class="bi bi-briefcase-fill"></i></span>
                    </div>
                    <div class="pjd-stat-value" style="font-size: 28px;">
                        <span>{{ $stats['total_accounts'] }}</span>
                    </div>
                    <div class="pjd-stat-sub">Allocated active accounts</div>
                </div>

                {{-- Total Count Card --}}
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #581c87 0%, #8b5cf6 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Total Count</span>
                        <span class="pjd-stat-icon"><i class="bi bi-archive-fill"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>P: {{ $stats['total_posters'] }}</span>
                        <span>V: {{ $stats['total_videos'] }}</span>
                    </div>
                    <div class="pjd-stat-sub">Required across all projects</div>
                </div>

                {{-- Completed Count Card --}}
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #14532d 0%, #22c55e 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Completed Count</span>
                        <span class="pjd-stat-icon"><i class="bi bi-patch-check-fill"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>P: {{ $stats['completed_posters'] }}</span>
                        <span>V: {{ $stats['completed_videos'] }}</span>
                    </div>
                    <div class="pjd-stat-sub">Completed across all projects</div>
                </div>

                {{-- Pending Count Card --}}
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #7c2d12 0%, #f97316 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Pending Count</span>
                        <span class="pjd-stat-icon"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>P: {{ $stats['pending_posters'] }}</span>
                        <span>V: {{ $stats['pending_videos'] }}</span>
                    </div>
                    <div class="pjd-stat-sub">Remaining (excluding overdue)</div>
                </div>

                {{-- Overdue Count Card --}}
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #7f1d1d 0%, #ef4444 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Overdue Count</span>
                        <span class="pjd-stat-icon"><i class="bi bi-clock-history"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>P: {{ $stats['overdue_posters'] }}</span>
                        <span>V: {{ $stats['overdue_videos'] }}</span>
                    </div>
                    <div class="pjd-stat-sub">Pending assets past delivery date</div>
                </div>
            </div>

            {{-- Filters Card --}}
            <section class="pjd-card">
                <div class="pjd-card-head">
                    <div>
                        <div class="pjd-card-title">Filters</div>
                        <div class="pjd-card-sub">Narrow down planned tasks by account, date, and status.</div>
                    </div>
                </div>
                <div class="pjd-card-body">
                    <form method="GET" action="{{ route('projects.dashboard') }}" class="pjd-filters" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                        <div class="pjd-field">
                            <label class="pjd-label">Allocated Account</label>
                            <select name="project_id" class="pjd-select">
                                <option value="">All Accounts</option>
                                @foreach($designProjects as $proj)
                                    <option value="{{ $proj->id }}" @selected($filters['project_id'] == $proj->id)>
                                        {{ $proj->product_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pjd-field">
                            <label class="pjd-label">Date</label>
                            <input type="date" name="date" value="{{ $filters['date'] }}" class="pjd-input">
                        </div>
                        <div class="pjd-field">
                            <label class="pjd-label">Status</label>
                            <select name="status" class="pjd-select">
                                <option value="">All Statuses</option>
                                <option value="waiting_approval" @selected($filters['status'] === 'waiting_approval')>Waiting for content approval</option>
                                <option value="inprogress" @selected($filters['status'] === 'inprogress')>In Progress</option>
                                <option value="waiting_review" @selected($filters['status'] === 'waiting_review')>Waiting for Review</option>
                                <option value="completed" @selected($filters['status'] === 'completed')>Completed</option>
                                <option value="overdue" @selected($filters['status'] === 'overdue')>Overdue</option>
                            </select>
                        </div>
                        <div class="pjd-field pjd-filter-actions" style="margin-top: auto;">
                            <button type="submit" class="pjd-btn pjd-btn-primary" style="flex-grow:1; height:40px;">Filter</button>
                            <a href="{{ route('projects.dashboard') }}" class="pjd-btn" style="flex-grow:1; height:40px; text-align:center; line-height:22px;">Reset</a>
                        </div>
                    </form>
                </div>
            </section>

            {{-- Planned Tasks Table --}}
            <section class="pjd-card">
                <div class="pjd-card-head">
                    <div>
                        <div class="pjd-card-title">Today Planned Tasks</div>
                        <div class="pjd-card-sub">Task commitments, approval states, and completion counts for {{ \Carbon\Carbon::parse($filters['date'])->format('d M Y') }}.</div>
                    </div>
                </div>
                <div class="pjd-card-body" style="padding:0;">
                    <div class="pjd-table-wrap">
                        <table class="pjd-table">
                            <thead>
                                <tr>
                                    <th>Account Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Tenure</th>
                                    <th>Committed Poster</th>
                                    <th>Committed Video</th>
                                    <th>Waiting Poster</th>
                                    <th>Waiting Video</th>
                                    <th>Completed Poster</th>
                                    <th>Completed Video</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todayPlannedTasks as $task)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.show', ['productionInitiation' => $task['project']->id]) }}" class="pjd-product" style="text-decoration:none; color:#ea580c;">
                                                {{ $task['project']->product_name }}
                                            </a>
                                            <div class="pjd-meta">
                                                {{ $task['project']->company_name ?: ($task['project']->lead?->company_name ?: 'No Company') }}
                                            </div>
                                        </td>
                                        <td>
                                            <span style="color:#475569;">{{ $task['start_date'] }}</span>
                                        </td>
                                        <td>
                                            <span style="color:#475569;">{{ $task['end_date'] }}</span>
                                        </td>
                                        <td>
                                            <span style="color:#475569;">{{ $task['tenure'] }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#1e293b;">{{ $task['committed_posters'] }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#1e293b;">{{ $task['committed_videos'] }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#ea580c;">{{ $task['waiting_posters'] }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#ea580c;">{{ $task['waiting_videos'] }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#166534;">{{ $task['completed_posters'] }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#166534;">{{ $task['completed_videos'] }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $isPastDate = \Carbon\Carbon::parse($filters['date'])->lt(\Carbon\Carbon::today());
                                            @endphp
                                            <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                                                <button type="button"
                                                    class="pjd-btn"
                                                    style="min-height:30px; height:30px; padding:4px 10px; font-size:11px; border-radius:8px; @if($isPastDate) background:#cbd5e1; border-color:#cbd5e1; color:#64748b; cursor:not-allowed; @else background:#ea580c; border-color:#ea580c; color:#fff; @endif"
                                                    @disabled($isPastDate)
                                                    data-open-task-update-modal
                                                    data-project-id="{{ $task['project']->id }}"
                                                    data-project-name="{{ $task['project']->product_name }}"
                                                    data-committed-posters="{{ $task['committed_posters'] }}"
                                                    data-committed-videos="{{ $task['committed_videos'] }}"
                                                    data-waiting-posters="{{ $task['waiting_posters'] }}"
                                                    data-waiting-videos="{{ $task['waiting_videos'] }}"
                                                    data-completed-posters="{{ $task['completed_posters'] }}"
                                                    data-completed-videos="{{ $task['completed_videos'] }}"
                                                    data-day-closing-update="{{ $task['day_closing_update'] }}">
                                                    Update
                                                </button>
                                                @if($isTl ?? false)
                                                    <button type="button"
                                                        class="pjd-btn"
                                                        style="min-height:30px; height:30px; padding:4px 10px; font-size:11px; border-radius:8px; background:#3b82f6; border-color:#3b82f6; color:#fff;"
                                                        data-open-allocate-modal
                                                        data-prefill-project-id="{{ $task['project']->id }}"
                                                        data-prefill-committed-posters="{{ $task['per_day_posters'] }}"
                                                        data-prefill-committed-videos="{{ $task['per_day_videos'] }}">
                                                        Reallocate
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" style="text-align:center; padding:30px; color:#64748b;">
                                            No planned tasks found matching the criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {{-- Overdue Projects Section --}}
            <section class="pjd-card" style="border-color:#fca5a5;">
                <div class="pjd-card-head" style="background:linear-gradient(135deg,#fef2f2 0%,#fff5f5 100%); border-bottom-color:#fca5a5;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:10px; background:#fecaca; color:#b91c1c; font-size:15px;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </span>
                        <div>
                            <div class="pjd-card-title" style="color:#b91c1c;">Overdue Projects</div>
                            <div class="pjd-card-sub" style="color:#ef4444;">Projects past their end date with pending deliverables.</div>
                        </div>
                    </div>
                    <span class="pjd-highlight" style="background:#fef2f2; border-color:#fca5a5; color:#b91c1c;">
                        {{ $overdueTasksList->count() }} Overdue
                    </span>
                </div>
                <div class="pjd-card-body" style="padding:0;">
                    @if($overdueTasksList->isNotEmpty())
                        <div class="pjd-table-wrap">
                            <table class="pjd-table">
                                <thead>
                                    <tr style="background:#fef2f2;">
                                        <th>Account Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Tenure</th>
                                        <th>Pending Poster</th>
                                        <th>Pending Video</th>
                                        <th>Committed Poster</th>
                                        <th>Committed Video</th>
                                        <th>Waiting Poster</th>
                                        <th>Waiting Video</th>
                                        <th>Completed Poster</th>
                                        <th>Completed Video</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($overdueTasksList as $task)
                                        <tr style="background:#fffafa;">
                                            <td>
                                                <a href="{{ route('projects.show', ['productionInitiation' => $task['project']->id]) }}" style="text-decoration:none; color:#b91c1c; font-weight:800;">
                                                    {{ $task['project']->product_name }}
                                                </a>
                                                <div class="pjd-meta">{{ $task['project']->company_name ?: ($task['project']->lead?->company_name ?: 'No Company') }}</div>
                                                <span style="display:inline-block; margin-top:4px; padding:2px 8px; border-radius:999px; background:#fecaca; color:#b91c1c; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em;">OVERDUE</span>
                                            </td>
                                            <td><span style="color:#475569;">{{ $task['start_date'] }}</span></td>
                                            <td><span style="color:#b91c1c; font-weight:700;">{{ $task['end_date'] }}</span></td>
                                            <td><span style="color:#475569;">{{ $task['tenure'] }}</span></td>
                                            <td><span style="font-weight:700; color:#b91c1c;">{{ $task['pending_posters'] }}</span></td>
                                            <td><span style="font-weight:700; color:#b91c1c;">{{ $task['pending_videos'] }}</span></td>
                                            <td><span style="font-weight:600; color:#1e293b;">{{ $task['committed_posters'] }}</span></td>
                                            <td><span style="font-weight:600; color:#1e293b;">{{ $task['committed_videos'] }}</span></td>
                                            <td><span style="font-weight:600; color:#ea580c;">{{ $task['waiting_posters'] }}</span></td>
                                            <td><span style="font-weight:600; color:#ea580c;">{{ $task['waiting_videos'] }}</span></td>
                                            <td><span style="font-weight:600; color:#166534;">{{ $task['completed_posters'] }}</span></td>
                                            <td><span style="font-weight:600; color:#166534;">{{ $task['completed_videos'] }}</span></td>
                                            <td>
                                                @php $isPastDate = \Carbon\Carbon::parse($filters['date'])->lt(\Carbon\Carbon::today()); @endphp
                                                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                                                    <button type="button"
                                                        class="pjd-btn"
                                                        style="min-height:30px; height:30px; padding:4px 10px; font-size:11px; border-radius:8px; @if($isPastDate) background:#cbd5e1; border-color:#cbd5e1; color:#64748b; cursor:not-allowed; @else background:#b91c1c; border-color:#b91c1c; color:#fff; @endif"
                                                        @disabled($isPastDate)
                                                        data-open-task-update-modal
                                                        data-project-id="{{ $task['project']->id }}"
                                                        data-project-name="{{ $task['project']->product_name }}"
                                                        data-committed-posters="{{ $task['committed_posters'] }}"
                                                        data-committed-videos="{{ $task['committed_videos'] }}"
                                                        data-waiting-posters="{{ $task['waiting_posters'] }}"
                                                        data-waiting-videos="{{ $task['waiting_videos'] }}"
                                                        data-completed-posters="{{ $task['completed_posters'] }}"
                                                        data-completed-videos="{{ $task['completed_videos'] }}"
                                                        data-day-closing-update="{{ $task['day_closing_update'] }}">
                                                        Update
                                                    </button>
                                                    @if($isTl ?? false)
                                                        <button type="button"
                                                            class="pjd-btn"
                                                            style="min-height:30px; height:30px; padding:4px 10px; font-size:11px; border-radius:8px; background:#3b82f6; border-color:#3b82f6; color:#fff;"
                                                            data-open-allocate-modal
                                                            data-prefill-project-id="{{ $task['project']->id }}"
                                                            data-prefill-committed-posters="{{ $task['per_day_posters'] }}"
                                                            data-prefill-committed-videos="{{ $task['per_day_videos'] }}">
                                                            Reallocate
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div style="display:flex; align-items:center; justify-content:center; gap:10px; padding:28px 20px; color:#16a34a; font-size:14px; font-weight:600;">
                            <i class="bi bi-check-circle-fill" style="font-size:20px;"></i>
                            No Overdue — All accounts are on track!
                        </div>
                    @endif
                </div>
            </section>
        </div>

        {{-- Update Planned Task Modal --}}
        <div class="pjd-update-modal-overlay" data-task-modal-overlay></div>
        <div class="pjd-update-modal" data-task-modal>
            <div class="pjd-update-modal-head">
                <div>
                    <div class="pjd-card-title">Update Planned Task Counts</div>
                    <div class="pjd-card-sub" id="taskModalProjectName">Project Name</div>
                </div>
                <button type="button" class="pjd-update-modal-close" data-close-task-modal aria-label="Close modal">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="pjd-update-modal-body">
                <form id="taskUpdateForm" method="POST" action="{{ route('projects.dashboard.update-planned-task') }}" class="ps-update-form">
                    @csrf
                    <input type="hidden" name="production_initiation_id" id="taskModalProjectId">
                    <input type="hidden" name="timesheet_date" value="{{ $filters['date'] }}">

                    <div class="ps-form-grid" style="grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div class="pjd-field">
                            <label class="ps-label">Committed Posters</label>
                            <input type="number" name="committed_posters" id="taskModalCommittedPosters" class="ps-input" min="0" required readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                        </div>
                        <div class="pjd-field">
                            <label class="ps-label">Committed Videos</label>
                            <input type="number" name="committed_videos" id="taskModalCommittedVideos" class="ps-input" min="0" required readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                        </div>
                    </div>

                    <div class="ps-form-grid" style="grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div class="pjd-field">
                            <label class="ps-label">Waiting for Approval Posters</label>
                            <input type="number" name="waiting_posters" id="taskModalWaitingPosters" class="ps-input" min="0" required>
                        </div>
                        <div class="pjd-field">
                            <label class="ps-label">Waiting for Approval Videos</label>
                            <input type="number" name="waiting_videos" id="taskModalWaitingVideos" class="ps-input" min="0" required>
                        </div>
                    </div>

                    <div class="ps-form-grid" style="grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div class="pjd-field">
                            <label class="ps-label">Completed Posters</label>
                            <input type="number" name="poster_count" id="taskModalCompletedPosters" class="ps-input" min="0" required>
                        </div>
                        <div class="pjd-field">
                            <label class="ps-label">Completed Videos</label>
                            <input type="number" name="video_count" id="taskModalCompletedVideos" class="ps-input" min="0" required>
                        </div>
                    </div>

                    <div class="pjd-field">
                        <label class="ps-label">Day Closing Update (Optional)</label>
                        <textarea name="day_closing_update" id="taskModalDayClosingUpdate" class="ps-textarea" placeholder="Add details about your day closing update (optional)..." style="min-height: 120px;"></textarea>
                        <small style="color: #64748b; font-size: 11px;">Note: Day closing updates require at least 5 lines of tasks to submit successfully.</small>
                    </div>

                    <div class="ps-actions">
                        <button type="submit" class="ps-btn ps-btn-primary" style="background:#166534; border-color:#166534; color:#fff;">Save Changes</button>
                        <button type="button" class="ps-btn" data-close-task-modal>Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Allocation Modal --}}
        <div class="pjd-update-modal-overlay" data-allocate-modal-overlay></div>
        <div class="pjd-update-modal" data-allocate-modal>
            <div class="pjd-update-modal-head">
                <div>
                    <div class="pjd-card-title">Allocate / Reallocate Daily Task</div>
                    <div class="pjd-card-sub">Assign today's committed poster &amp; video count.</div>
                </div>
                <button type="button" class="pjd-update-modal-close" data-close-allocate-modal aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="pjd-update-modal-body">
                <form id="allocateTaskForm" method="POST" action="{{ route('projects.dashboard.allocate-task') }}" class="ps-update-form">
                    @csrf
                    <input type="hidden" name="timesheet_date" value="{{ $filters['date'] }}">

                    {{-- Team member selector --}}
                    <div class="pjd-field">
                        <label class="ps-label">Assign To (Team Member)</label>
                        @if($isTl ?? false)
                            <select name="assigned_user_id" id="allocateUserSelect" class="ps-input" required>
                                <option value="">— Select a team member —</option>
                                @foreach($teamMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }} — {{ implode(', ', $member->role_names) }}</option>
                                @endforeach
                            </select>
                        @else
                            <select id="allocateUserSelectDisplay" class="ps-input" disabled style="background-color: #f1f5f9; cursor: not-allowed;">
                                <option value="{{ auth()->id() }}" selected>{{ auth()->user()->name }}</option>
                            </select>
                            <input type="hidden" name="assigned_user_id" id="allocateUserSelect" value="{{ auth()->id() }}">
                        @endif
                    </div>

                    {{-- Multiple Projects selector & counts grid --}}
                    <div class="pjd-field">
                        <label class="ps-label">Select Accounts &amp; Enter Committed Counts</label>
                        <div style="max-height: 250px; overflow-y: auto; border: 1px solid #dbe1e8; border-radius: 14px; padding: 12px; display: grid; gap: 12px; background: #fafafa;">
                            @foreach($designProjects as $index => $proj)
                                @php
                                    $projPend = $todayPlannedTasks->firstWhere('project.id', $proj->id) ?? $overdueTasksList->firstWhere('project.id', $proj->id);
                                    $pdp = $projPend['per_day_posters'] ?? 0;
                                    $pdv = $projPend['per_day_videos'] ?? 0;
                                    $pendingP = $projPend['pending_posters'] ?? 0;
                                    $pendingV = $projPend['pending_videos'] ?? 0;
                                    $remDays = $projPend['remaining_days'] ?? 1;
                                @endphp
                                <div class="project-allocation-row" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                    <div style="display: flex; align-items: flex-start; gap: 10px; flex: 1;">
                                        <input type="checkbox" name="allocations[{{ $index }}][selected]" value="1" class="allocate-project-checkbox" style="margin-top: 4px; width: 18px; height: 18px; accent-color: #3b82f6;" data-index="{{ $index }}">
                                        <input type="hidden" name="allocations[{{ $index }}][production_initiation_id]" value="{{ $proj->id }}">
                                        <div>
                                            <span style="font-weight: 700; color: #1e293b; font-size: 13px;">{{ $proj->product_name }}</span>
                                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                                Pending: {{ $pendingP }} P / {{ $pendingV }} V &nbsp;•&nbsp; Days left: {{ $remDays }}
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <label style="font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Posters</label>
                                            <input type="number" name="allocations[{{ $index }}][committed_posters]" class="ps-input allocate-posters-input" min="0" value="0" disabled style="width: 80px; min-height: 36px; padding: 6px; font-size: 13px;" data-pdp="{{ $pdp }}">
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <label style="font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Videos</label>
                                            <input type="number" name="allocations[{{ $index }}][committed_videos]" class="ps-input allocate-videos-input" min="0" value="0" disabled style="width: 80px; min-height: 36px; padding: 6px; font-size: 13px;" data-pdv="{{ $pdv }}">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="ps-actions">
                        <button type="submit" class="ps-btn ps-btn-primary" style="background:#3b82f6; border-color:#3b82f6; color:#fff;">Allocate Task</button>
                        <button type="button" class="ps-btn" data-close-allocate-modal>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@else
    @php
        $hasUpdateErrors = $errors->has('production_initiation_id') || $errors->has('type') || $errors->has('content');
        $pageTitle = $isTlScopedView
            ? 'TL Project Dashboard'
            : ($isContributorScopedView ? 'Executive Project Dashboard' : 'Projects Dashboard');
        $pageCrumb = $isTlScopedView
            ? 'Modules > Projects > TL Dashboard'
            : ($isContributorScopedView ? 'Modules > Projects > Executive Dashboard' : 'Modules > Projects Dashboard');
        $workspaceLabel = $isTlScopedView
            ? 'TL Allocation Overview'
            : ($isContributorScopedView ? 'Executive Allocation Overview' : 'Project Allocation Overview');
        $currency = fn ($value) => 'Rs ' . number_format((float) $value, 2);
    @endphp

    <div class="pjd-page">
        <div class="pjd-topbar">
            <div>
                <div class="pjd-title">{{ $pageTitle }}</div>
                <div class="pjd-breadcrumb">{{ $pageCrumb }}</div>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                @if($isAdminLike ?? false)
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="pjd-label" style="font-weight:800; font-size:11px; color:#7c7c7c;">Dashboard View:</span>
                        <select onchange="window.location.href = '{{ route('projects.dashboard') }}?dashboard_type=' + this.value" class="pjd-select" style="min-height:36px; padding:6px 12px; border-radius:8px; width:160px; font-size:13px; font-weight:800; border:1px solid #eee7df;">
                            <option value="production" @selected(($selectedDashboard ?? 'production') === 'production')>Production</option>
                            <option value="design" @selected(($selectedDashboard ?? 'production') === 'design')>Designing</option>
                        </select>
                    </div>
                @endif
                @if($isContributorScopedView && ($canQuickAddProductionUpdate ?? false))
                    <button type="button" class="pjd-btn pjd-btn-primary" data-open-update-modal>Add Production Update</button>
                @endif
                <div class="pjd-chip">{{ $workspaceLabel }}</div>
            </div>
        </div>

        <div class="pjd-body">
            <section class="pjd-card">
                <div class="pjd-card-head">
                    <div>
                        <div class="pjd-card-title">Filters</div>
                        <div class="pjd-card-sub">Use dates, project, and team allocation filters to narrow this dashboard.</div>
                    </div>
                </div>
                <div class="pjd-card-body">
                    <form method="GET" action="{{ route('projects.dashboard') }}" class="pjd-filters">
                        <div class="pjd-field">
                            <label class="pjd-label">From Date</label>
                            <input type="date" name="date_from" value="{{ $dashboardFilters['date_from'] ?? '' }}" class="pjd-input">
                        </div>
                        <div class="pjd-field">
                            <label class="pjd-label">To Date</label>
                            <input type="date" name="date_to" value="{{ $dashboardFilters['date_to'] ?? '' }}" class="pjd-input">
                        </div>
                        <div class="pjd-field">
                            <label class="pjd-label">Project</label>
                            <select name="project_id" class="pjd-select">
                                <option value="">All Projects</option>
                                @foreach($projectOptions as $project)
                                    <option value="{{ $project->id }}" @selected(($dashboardFilters['project_id'] ?? '') === (string) $project->id)>
                                        {{ $project->product_name }} | {{ $project->company_name ?: ($project->lead?->company_name ?: 'No company') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pjd-field">
                            <label class="pjd-label">Allocation Status</label>
                            <select name="allocation_status" class="pjd-select">
                                <option value="">All Allocations</option>
                                <option value="allocation_pending" @selected(($dashboardFilters['allocation_status'] ?? '') === 'allocation_pending')>Allocation Pending</option>
                                <option value="allocated" @selected(($dashboardFilters['allocation_status'] ?? '') === 'allocated')>Allocation Completed</option>
                            </select>
                        </div>
                        @if($teamMemberOptions->isNotEmpty())
                            <div class="pjd-field">
                                <label class="pjd-label">Allocated User</label>
                                <select name="team_member_id" class="pjd-select">
                                    <option value="">All Users</option>
                                    @foreach($teamMemberOptions as $member)
                                        <option value="{{ $member->id }}" @selected(($dashboardFilters['team_member_id'] ?? '') === (string) $member->id)>
                                            {{ $member->name }} | {{ implode(', ', $member->role_names) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="pjd-field pjd-filter-actions">
                            <button type="submit" class="pjd-btn pjd-btn-primary">Apply Filter</button>
                            <a href="{{ route('projects.dashboard') }}" class="pjd-btn">Reset</a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="pjd-stats">
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #0e7490 0%, #06b6d4 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Allocated Projects</span>
                        <span class="pjd-stat-icon"><i class="bi bi-folder-fill"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>{{ number_format($stats['allocated_projects']) }}</span>
                    </div>
                    <div class="pjd-stat-sub">Projects available in the current dashboard scope.</div>
                </div>
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Project Value</span>
                        <span class="pjd-stat-icon"><i class="bi bi-currency-rupee"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>{{ $currency($stats['project_value']) }}</span>
                    </div>
                    <div class="pjd-stat-sub">Overall value of the filtered allocated projects.</div>
                </div>
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #14532d 0%, #22c55e 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Received Amount</span>
                        <span class="pjd-stat-icon"><i class="bi bi-wallet2"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>{{ $currency($stats['received_amount']) }}</span>
                    </div>
                    <div class="pjd-stat-sub">Payments already received for these projects.</div>
                </div>
                <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #7c2d12 0%, #f97316 100%);">
                    <div class="pjd-stat-header">
                        <span class="pjd-stat-label">Balance Amount</span>
                        <span class="pjd-stat-icon"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                    <div class="pjd-stat-value">
                        <span>{{ $currency($stats['balance_amount']) }}</span>
                    </div>
                    <div class="pjd-stat-sub">Outstanding amount still pending collection.</div>
                </div>

            </section>

            <section class="pjd-card">
                <div class="pjd-card-head">
                     <div>
                         <div class="pjd-card-title">{{ $deliverySectionTitle ?? 'Delivery Planned Projects' }}</div>
                         <div class="pjd-card-sub">Projects with planned delivery dates in this period.</div>
                     </div>
                     <span class="pjd-highlight">{{ $currentMonthDeliveryProjects->count() }} {{ $deliverySectionBadge ?? 'Planned' }}</span>
                </div>
                <div class="pjd-card-body" style="padding:0;">
                    @if($currentMonthDeliveryProjects->isNotEmpty())
                        <div class="pjd-table-wrap">
                            <table class="pjd-table">
                                <thead>
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Delivery Date</th>
                                        <th>Allocated Person</th>
                                        <th>Status</th>
                                        <th>Total Project Value</th>
                                        <th>Received Amount</th>
                                        <th>Pending Amount</th>
                                        <th>View</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($currentMonthDeliveryProjects as $project)
                                        <tr>
                                            <td>
                                                <div class="pjd-product">{{ $project->product_name }}</div>
                                                <div class="pjd-meta">{{ $project->company_name ?: ($project->lead?->company_name ?: 'No company') }}</div>
                                            </td>
                                            <td>{{ optional($project->project_delivery_date)->format('d M Y') ?: 'Not available' }}</td>
                                            <td>
                                                {{ $project->allocated_person_label }}
                                                <div class="pjd-meta">{{ $project->department?->name ?: 'No department' }}</div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusVal = $project->project_execution_status ?: 'ontrack';
                                                    $statusLabels = [
                                                        'ontrack' => 'Onboard',
                                                        'hold' => 'Hold',
                                                        'delivered' => 'Delivered'
                                                    ];
                                                    $statusLabel = $statusLabels[$statusVal] ?? ucfirst($statusVal);
                                                @endphp
                                                <span class="pjd-pill status-{{ $statusVal }}">{{ $statusLabel }}</span>
                                            </td>
                                            <td><span class="pjd-money">{{ $currency($project->project_value) }}</span></td>
                                            <td><span class="pjd-money received">{{ $currency($project->received_amount) }}</span></td>
                                            <td><span class="pjd-money balance">{{ $currency($project->balance_amount) }}</span></td>
                                            <td><a href="{{ route('projects.show', $project) }}" class="pjd-link">Open</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="pjd-empty">No delivery scheduled in the current month scope.</div>
                    @endif
                </div>
            </section>

            <!-- Charts Section -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; margin-top: 18px;">
                <!-- Chart 1: Development Product Delivery & Ongoing Status -->
                <div class="pjd-card" style="display: flex; flex-direction: column;">
                    <div class="pjd-card-head">
                        <div>
                            <div class="pjd-card-title">Development Product Delivery Status</div>
                            <div class="pjd-card-sub">Delivered vs Ongoing projects for the selected period.</div>
                        </div>
                    </div>
                    <div class="pjd-card-body" style="flex: 1; min-height: 280px; position: relative;">
                        @if($developmentProductWiseStats->isEmpty())
                            <div class="pjd-empty" style="padding-top: 80px;">No development projects scheduled for delivery in this period.</div>
                        @else
                            <div id="devProductChart"></div>
                        @endif
                    </div>
                </div>

                <!-- Chart 2: Payment Status -->
                <div class="pjd-card" style="display: flex; flex-direction: column;">
                    <div class="pjd-card-head">
                        <div>
                            <div class="pjd-card-title">Payment Status</div>
                            <div class="pjd-card-sub">Overview of received collections and outstanding balance.</div>
                        </div>
                    </div>
                    <div class="pjd-card-body" style="flex: 1; min-height: 280px; position: relative; display: flex; justify-content: center; align-items: center;">
                        @if($paymentStats['received'] == 0 && $paymentStats['pending'] == 0)
                            <div class="pjd-empty">No payment data recorded in this period.</div>
                        @else
                            <div style="width: 100%; height: 100%; max-height: 240px; display: flex; justify-content: center; align-items: center;">
                                <div id="paymentChart" style="width: 100%;"></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Chart 3: Last 6 Months Revenue (Full Width) -->
            <div class="pjd-card" style="margin-top: 18px; display: flex; flex-direction: column;">
                <div class="pjd-card-head">
                    <div>
                        <div class="pjd-card-title">Last 6 Months Revenue</div>
                        <div class="pjd-card-sub">Monthly trend of payment collections received.</div>
                    </div>
                </div>
                <div class="pjd-card-body" style="flex: 1; min-height: 280px; position: relative;">
                    @if(collect($sixMonthsRevenue)->sum('revenue') == 0)
                        <div class="pjd-empty" style="padding-top: 80px;">No revenue recorded over the last six months.</div>
                    @else
                        <div id="revenueChart"></div>
                    @endif
                </div>
            </div>

            <div class="pjd-split">
                <section class="pjd-card">
                    <div class="pjd-card-head">
                        <div>
                            <div class="pjd-card-title">Recent Allocated Projects</div>
                            <div class="pjd-card-sub">Active projects and their allocated teams.</div>
                        </div>
                    </div>
                    <div class="pjd-card-body" style="padding:0;">
                        @if($recentProjects->isNotEmpty())
                            <div class="pjd-table-wrap">
                                <table class="pjd-table">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Allocated To</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentProjects as $project)
                                            <tr>
                                                <td>
                                                    <div class="pjd-product">{{ $project->product_name }}</div>
                                                    <div class="pjd-meta">{{ $project->company_name ?: ($project->lead?->company_name ?: 'No company') }}</div>
                                                </td>
                                                <td>{{ $project->allocated_person_label }}</td>
                                                <td>
                                                    @php
                                                        $statusVal = $project->project_execution_status ?: 'ontrack';
                                                        $statusLabels = [
                                                            'ontrack' => 'Onboard',
                                                            'hold' => 'Hold',
                                                            'delivered' => 'Delivered'
                                                        ];
                                                        $statusLabel = $statusLabels[$statusVal] ?? ucfirst($statusVal);
                                                    @endphp
                                                    <span class="pjd-pill status-{{ $statusVal }}">{{ $statusLabel }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="pjd-empty">No active allocated projects.</div>
                        @endif
                    </div>
                </section>

                <section class="pjd-card">
                    <div class="pjd-card-head">
                        <div>
                            <div class="pjd-card-title">Timesheet & Activity Summary</div>
                            <div class="pjd-card-sub">Logs from daily timesheet entries.</div>
                        </div>
                    </div>
                    <div class="pjd-card-body" style="padding:0;">
                        @if(!empty($timesheetSummary))
                            <div class="pjd-table-wrap">
                                <table class="pjd-table">
                                    <thead>
                                        <tr>
                                            <th>Project Name</th>
                                            <th>Submissions</th>
                                            <th>Total Posters</th>
                                            <th>Total Videos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($timesheetSummary as $row)
                                            <tr>
                                                <td>
                                                    <div class="pjd-product">{{ $row['project_name'] }}</div>
                                                    <div class="pjd-meta">{{ $row['company_name'] }}</div>
                                                </td>
                                                <td>{{ $row['entries_count'] }} logs</td>
                                                <td>{{ $row['total_posters'] }}</td>
                                                <td>{{ $row['total_videos'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="pjd-empty">No active timesheet submissions found.</div>
                        @endif
                    </div>
                </section>
            </div>

            @if($isContributorScopedView && ($canQuickAddProductionUpdate ?? false))
                <div class="pjd-update-modal-overlay {{ $hasUpdateErrors ? 'is-open' : '' }}" data-update-modal-overlay></div>
                <div class="pjd-update-modal {{ $hasUpdateErrors ? 'is-open' : '' }}" data-update-modal>
                    <div class="pjd-update-modal-head">
                        <div>
                            <div class="pjd-card-title">Add Production Update</div>
                            <div class="pjd-card-sub">Post your work status or daily task update.</div>
                        </div>
                        <button type="button" class="pjd-update-modal-close" data-close-update-modal aria-label="Close modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="pjd-update-modal-body">
                        @if(($quickUpdateProjects ?? collect())->isNotEmpty())
                            @include('pages.projects.partials.update-form', [
                                'updateFormAction' => route('projects.updates.quick-store'),
                                'updateEditorId' => 'dashboardProjectUpdateEditor',
                                'showProjectSelector' => true,
                                'projectOptions' => $quickUpdateProjects,
                                'updateReturnTarget' => 'projects.dashboard',
                            ])
                        @else
                            <div class="pjd-empty">No allocated development projects are available for posting a production update.</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
@endsection

@if($isDesigningDashboard ?? false)
    @push('scripts')
    <script>
    window.userTargetsMap = @json($userTargetsMap);
    document.addEventListener('DOMContentLoaded', function () {
        const taskModal = document.querySelector('[data-task-modal]');
        const taskModalOverlay = document.querySelector('[data-task-modal-overlay]');
        const taskOpenButtons = document.querySelectorAll('[data-open-task-update-modal]');
        const taskCloseButtons = document.querySelectorAll('[data-close-task-modal]');

        function setTaskModalState(isOpen) {
            if (!taskModal || !taskModalOverlay) {
                return;
            }
            taskModal.classList.toggle('is-open', isOpen);
            taskModalOverlay.classList.toggle('is-open', isOpen);
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }

        taskOpenButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const projectId = this.getAttribute('data-project-id');
                const projectName = this.getAttribute('data-project-name');
                const committedPosters = this.getAttribute('data-committed-posters');
                const committedVideos = this.getAttribute('data-committed-videos');
                const waitingPosters = this.getAttribute('data-waiting-posters');
                const waitingVideos = this.getAttribute('data-waiting-videos');
                const completedPosters = this.getAttribute('data-completed-posters');
                const completedVideos = this.getAttribute('data-completed-videos');
                const dayClosingUpdate = this.getAttribute('data-day-closing-update');

                document.getElementById('taskModalProjectId').value = projectId;
                document.getElementById('taskModalProjectName').textContent = projectName;
                document.getElementById('taskModalCommittedPosters').value = committedPosters;
                document.getElementById('taskModalCommittedVideos').value = committedVideos;
                document.getElementById('taskModalWaitingPosters').value = waitingPosters;
                document.getElementById('taskModalWaitingVideos').value = waitingVideos;
                document.getElementById('taskModalCompletedPosters').value = completedPosters;
                document.getElementById('taskModalCompletedVideos').value = completedVideos;
                document.getElementById('taskModalDayClosingUpdate').value = dayClosingUpdate || '';

                // Run initial validation check
                validatePostersAndVideos();

                setTaskModalState(true);
            });
        });

        // Validation logic
        const taskUpdateForm = document.getElementById('taskUpdateForm');
        const committedPostersInput = document.getElementById('taskModalCommittedPosters');
        const committedVideosInput = document.getElementById('taskModalCommittedVideos');
        const waitingPostersInput = document.getElementById('taskModalWaitingPosters');
        const waitingVideosInput = document.getElementById('taskModalWaitingVideos');
        const completedPostersInput = document.getElementById('taskModalCompletedPosters');
        const completedVideosInput = document.getElementById('taskModalCompletedVideos');

        function validatePostersAndVideos() {
            const committedPosters = parseInt(committedPostersInput.value) || 0;
            const committedVideos = parseInt(committedVideosInput.value) || 0;
            const waitingPosters = parseInt(waitingPostersInput.value) || 0;
            const waitingVideos = parseInt(waitingVideosInput.value) || 0;
            const completedPosters = parseInt(completedPostersInput.value) || 0;
            const completedVideos = parseInt(completedVideosInput.value) || 0;

            let isValid = true;

            // Clear previous validity
            completedPostersInput.setCustomValidity('');
            waitingPostersInput.setCustomValidity('');
            completedVideosInput.setCustomValidity('');
            waitingVideosInput.setCustomValidity('');

            if (completedPosters + waitingPosters > committedPosters) {
                const msg = 'Completed + Waiting posters (' + (completedPosters + waitingPosters) + ') cannot exceed Committed posters (' + committedPosters + ').';
                completedPostersInput.setCustomValidity(msg);
                waitingPostersInput.setCustomValidity(msg);
                isValid = false;
            }

            if (completedVideos + waitingVideos > committedVideos) {
                const msg = 'Completed + Waiting videos (' + (completedVideos + waitingVideos) + ') cannot exceed Committed videos (' + committedVideos + ').';
                completedVideosInput.setCustomValidity(msg);
                waitingVideosInput.setCustomValidity(msg);
                isValid = false;
            }

            return isValid;
        }

        [completedPostersInput, waitingPostersInput, completedVideosInput, waitingVideosInput].forEach(function (input) {
            input.addEventListener('input', validatePostersAndVideos);
        });

        if (taskUpdateForm) {
            taskUpdateForm.addEventListener('submit', function (e) {
                if (!validatePostersAndVideos()) {
                    e.preventDefault();
                    // Focus on the first invalid field and report validation
                    const invalidInput = taskUpdateForm.querySelector(':invalid');
                    if (invalidInput) {
                        invalidInput.focus();
                        invalidInput.reportValidity();
                    }
                }
            });
        }

        taskCloseButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setTaskModalState(false);
            });
        });

        if (taskModalOverlay) {
            taskModalOverlay.addEventListener('click', function () {
                setTaskModalState(false);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setTaskModalState(false);
            }
        });

        // ── Allocation Modal (Multi-Select) ──────────────────────────────────────
        const allocateModal        = document.querySelector('[data-allocate-modal]');
        const allocateModalOverlay = document.querySelector('[data-allocate-modal-overlay]');
        const allocateOpenButtons  = document.querySelectorAll('[data-open-allocate-modal]');
        const allocateCloseButtons = document.querySelectorAll('[data-close-allocate-modal]');
        const allocateTaskForm     = document.getElementById('allocateTaskForm');
        const allocateUserSelectVal = document.getElementById('allocateUserSelect');

        function setAllocateModalState(isOpen) {
            if (!allocateModal || !allocateModalOverlay) return;
            allocateModal.classList.toggle('is-open', isOpen);
            allocateModalOverlay.classList.toggle('is-open', isOpen);
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }

        // Enable/disable and auto-fill row inputs when checkbox is toggled
        document.querySelectorAll('.allocate-project-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                const idx = this.getAttribute('data-index');
                const postInput = document.querySelector(`input[name="allocations[${idx}][committed_posters]"]`);
                const vidInput  = document.querySelector(`input[name="allocations[${idx}][committed_videos]"]`);
                if (postInput && vidInput) {
                    postInput.disabled = !this.checked;
                    vidInput.disabled  = !this.checked;
                    if (this.checked) {
                        // Pre-populate with suggested per-day rate
                        postInput.value = postInput.getAttribute('data-pdp') || '0';
                        vidInput.value  = vidInput.getAttribute('data-pdv') || '0';
                    } else {
                        postInput.value = '0';
                        vidInput.value  = '0';
                    }
                }
                validateDailyTargets();
            });
        });

        // Add input listeners for real-time validation on all committed inputs
        document.querySelectorAll('.allocate-posters-input, .allocate-videos-input').forEach(function (input) {
            input.addEventListener('input', validateDailyTargets);
        });

        allocateOpenButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const prefillProjectId = this.getAttribute('data-prefill-project-id');
                const prefillPosters   = this.getAttribute('data-prefill-committed-posters');
                const prefillVideos    = this.getAttribute('data-prefill-committed-videos');

                // Reset all rows
                document.querySelectorAll('.allocate-project-checkbox').forEach(function (cb) {
                    cb.checked = false;
                    const idx = cb.getAttribute('data-index');
                    const postInput = document.querySelector(`input[name="allocations[${idx}][committed_posters]"]`);
                    const vidInput  = document.querySelector(`input[name="allocations[${idx}][committed_videos]"]`);
                    if (postInput) { postInput.disabled = true; postInput.value = 0; }
                    if (vidInput)  { vidInput.disabled = true;  vidInput.value = 0; }
                });

                // If coming from Reallocate button, select and prefill that row
                if (prefillProjectId) {
                    const hiddenInput = document.querySelector(`input[value="${prefillProjectId}"][name$="[production_initiation_id]"]`);
                    if (hiddenInput) {
                        const row = hiddenInput.closest('.project-allocation-row');
                        const cb = row.querySelector('.allocate-project-checkbox');
                        if (cb) {
                            cb.checked = true;
                            const idx = cb.getAttribute('data-index');
                            const postInput = document.querySelector(`input[name="allocations[${idx}][committed_posters]"]`);
                            const vidInput  = document.querySelector(`input[name="allocations[${idx}][committed_videos]"]`);
                            if (postInput) {
                                postInput.disabled = false;
                                postInput.value = prefillPosters || 0;
                            }
                            if (vidInput) {
                                vidInput.disabled = false;
                                vidInput.value = prefillVideos || 0;
                            }
                        }
                    }
                }

                setAllocateModalState(true);
            });
        });

        allocateCloseButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setAllocateModalState(false);
            });
        });

        if (allocateModalOverlay) {
            allocateModalOverlay.addEventListener('click', function () {
                setAllocateModalState(false);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setAllocateModalState(false);
            }
        });

        // Target daily commitment validation
        function validateDailyTargets() {
            if (!window.userTargetsMap) return true;
            const userId = allocateUserSelectVal ? parseInt(allocateUserSelectVal.value) : 0;
            if (!userId) return true;

            const targets = window.userTargetsMap[userId] || { poster: 0, video: 0 };
            let isValid = true;

            document.querySelectorAll('.allocate-project-checkbox').forEach(function (cb) {
                if (!cb.checked) return;
                const idx = cb.getAttribute('data-index');
                const postInput = document.querySelector(`input[name="allocations[${idx}][committed_posters]"]`);
                const vidInput  = document.querySelector(`input[name="allocations[${idx}][committed_videos]"]`);

                if (postInput) postInput.setCustomValidity('');
                if (vidInput)  vidInput.setCustomValidity('');

                if (postInput) {
                    const val = parseInt(postInput.value) || 0;
                    if (val > 0 && val < targets.poster) {
                        postInput.setCustomValidity('Committed posters cannot be less than daily target (' + targets.poster + ').');
                        isValid = false;
                    }
                }

                if (vidInput) {
                    const val = parseInt(vidInput.value) || 0;
                    if (val > 0 && val < targets.video) {
                        vidInput.setCustomValidity('Committed videos cannot be less than daily target (' + targets.video + ').');
                        isValid = false;
                    }
                }
            });

            return isValid;
        }

        if (allocateUserSelectVal) {
            allocateUserSelectVal.addEventListener('change', validateDailyTargets);
        }

        if (allocateTaskForm) {
            allocateTaskForm.addEventListener('submit', function (e) {
                // Ensure at least one project checkbox is checked
                const checkedCount = document.querySelectorAll('.allocate-project-checkbox:checked').length;
                if (checkedCount === 0) {
                    e.preventDefault();
                    alert('Please select at least one account/project.');
                    return;
                }

                if (!validateDailyTargets()) {
                    e.preventDefault();
                    const invalidInput = allocateTaskForm.querySelector(':invalid');
                    if (invalidInput) {
                        invalidInput.focus();
                        invalidInput.reportValidity();
                    }
                }
            });
        }
    });
    </script>
    @endpush
@endif

@if(!($isDesigningDashboard ?? false))
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const devProductStats = @json($developmentProductWiseStats);
        const paymentStats = @json($paymentStats);
        const sixMonthsRevenue = @json($sixMonthsRevenue);

        // 1. Development Product Chart
        const devCanvas = document.getElementById('devProductChart');
        if (devCanvas && typeof ApexCharts !== 'undefined') {
            const devLabels = Object.keys(devProductStats);
            const devDeliveredData = devLabels.map(k => devProductStats[k].delivered);
            const devOngoingData = devLabels.map(k => devProductStats[k].ongoing);

            const options = {
                series: [{
                    name: 'Delivered',
                    data: devDeliveredData
                }, {
                    name: 'Ongoing',
                    data: devOngoingData
                }],
                chart: {
                    type: 'bar',
                    height: 280,
                    stacked: true,
                    toolbar: { show: false }
                },
                colors: ['#10b981', '#f59e0b'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        borderRadius: 6
                    },
                },
                xaxis: {
                    categories: devLabels,
                },
                legend: {
                    position: 'bottom'
                },
                fill: {
                    opacity: 1
                }
            };

            const chart = new ApexCharts(devCanvas, options);
            chart.render();
        }

        // 2. Payment Chart
        const paymentCanvas = document.getElementById('paymentChart');
        if (paymentCanvas && typeof ApexCharts !== 'undefined') {
            const options = {
                series: [paymentStats.received, paymentStats.pending],
                labels: ['Received', 'Pending'],
                colors: ['#10b981', '#ef4444'],
                chart: {
                    type: 'donut',
                    height: 280
                },
                legend: {
                    position: 'bottom'
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%'
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                }
            };

            const chart = new ApexCharts(paymentCanvas, options);
            chart.render();
        }

        // 3. Last 6 Months Revenue Chart
        const revCanvas = document.getElementById('revenueChart');
        if (revCanvas && typeof ApexCharts !== 'undefined') {
            const revLabels = sixMonthsRevenue.map(item => item.month_name);
            const revData = sixMonthsRevenue.map(item => item.revenue);

            const options = {
                series: [{
                    name: 'Revenue Billed',
                    data: revData
                }],
                colors: ['#ea580c'],
                chart: {
                    type: 'area',
                    height: 280,
                    toolbar: { show: false }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: revLabels,
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return 'Rs ' + value.toLocaleString();
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                }
            };

            const chart = new ApexCharts(revCanvas, options);
            chart.render();
        }
    });
    </script>
    @endpush
@endif

@if(($isContributorScopedView ?? false) && ($canQuickAddProductionUpdate ?? false))
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const updateModal = document.querySelector('[data-update-modal]');
        const updateModalOverlay = document.querySelector('[data-update-modal-overlay]');
        const updateOpenButtons = document.querySelectorAll('[data-open-update-modal]');
        const updateCloseButtons = document.querySelectorAll('[data-close-update-modal]');
        const updateEditor = document.getElementById('dashboardProjectUpdateEditor');

        function setUpdateModalState(isOpen) {
            if (!updateModal || !updateModalOverlay) {
                return;
            }

            updateModal.classList.toggle('is-open', isOpen);
            updateModalOverlay.classList.toggle('is-open', isOpen);
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }

        updateOpenButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setUpdateModalState(true);
            });
        });

        updateCloseButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setUpdateModalState(false);
            });
        });

        if (updateModalOverlay) {
            updateModalOverlay.addEventListener('click', function () {
                setUpdateModalState(false);
            });
        }

        if (window.tinymce && updateEditor) {
            window.tinymce.remove('#dashboardProjectUpdateEditor');
            window.tinymce.init({
                selector: 'textarea#dashboardProjectUpdateEditor',
                base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
                menubar: false,
                height: 280,
                plugins: 'lists link table code',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | alignleft aligncenter alignright | code',
                content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; }',
                setup: function (editor) {
                    editor.on('change keyup', function () {
                        window.tinymce.triggerSave();
                    });
                },
            });
        }

        document.querySelectorAll('form[action$="/projects-details/updates/quick"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (window.tinymce) {
                    window.tinymce.triggerSave();
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setUpdateModalState(false);
            }
        });
    });
    </script>
    @endpush
@endif
