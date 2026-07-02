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
.pjd-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:16px; }
.pjd-stat { position:relative; overflow:hidden; background:#fff; border:1px solid #eee7df; border-radius:10px; padding:18px; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.pjd-stat::before { content:''; position:absolute; inset:0 0 auto 0; height:4px; background:var(--stat-color,#fe5f04); }
.pjd-stat-label { font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#7c7c7c; }
.pjd-stat-value { margin-top:10px; font-size:25px; font-weight:900; line-height:1; color:#111827; }
.pjd-stat-sub { margin-top:10px; font-size:12px; color:#64748b; }
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
    .pjd-stats { grid-template-columns:repeat(3,minmax(0,1fr)); }
}
@media (max-width: 768px) {
    .pjd-topbar { padding:18px 16px; flex-direction:column; }
    .pjd-body { padding:18px 16px 24px; }
    .pjd-filters, .pjd-stats, .pjd-kpis { grid-template-columns:1fr; }
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
            <div class="pjd-chip" style="background:#f0fdf4; border-color:#bbf7d0; color:#166534;">Designing Team</div>
        </div>

        <div class="pjd-body">
            {{-- Metrics Cards --}}
            <div class="pjd-stats" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="pjd-stat" style="--stat-color: #3b82f6;">
                    <div class="pjd-stat-label">Daily Task Goal Count</div>
                    <div class="pjd-stat-value">{{ $stats['daily_task_goal'] }}</div>
                    <div class="pjd-stat-sub">Today's target posters/videos</div>
                </div>
                <div class="pjd-stat" style="--stat-color: #ef4444;">
                    <div class="pjd-stat-label">Overdue Count</div>
                    <div class="pjd-stat-value">{{ $stats['overdue_count'] }}</div>
                    <div class="pjd-stat-sub">Accounts past delivery date</div>
                </div>
                <div class="pjd-stat" style="--stat-color: #10b981;">
                    <div class="pjd-stat-label">Total Accounts Count</div>
                    <div class="pjd-stat-value">{{ $stats['total_accounts'] }}</div>
                    <div class="pjd-stat-sub">Allocated active accounts</div>
                </div>
                <div class="pjd-stat" style="--stat-color: #8b5cf6;">
                    <div class="pjd-stat-label">Total Posters Count</div>
                    <div class="pjd-stat-value">{{ $stats['total_posters'] }}</div>
                    <div class="pjd-stat-sub">Required across all projects</div>
                </div>
                <div class="pjd-stat" style="--stat-color: #f59e0b;">
                    <div class="pjd-stat-label">Pending Posters</div>
                    <div class="pjd-stat-value">{{ $stats['pending_posters'] }}</div>
                    <div class="pjd-stat-sub">Posters remaining to be done</div>
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
                            <input type="number" name="committed_posters" id="taskModalCommittedPosters" class="ps-input" min="0" required>
                        </div>
                        <div class="pjd-field">
                            <label class="ps-label">Committed Videos</label>
                            <input type="number" name="committed_videos" id="taskModalCommittedVideos" class="ps-input" min="0" required>
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
                <div class="pjd-stat" style="--stat-color:#fe5f04;">
                    <div class="pjd-stat-label">Allocated Projects</div>
                    <div class="pjd-stat-value">{{ number_format($stats['allocated_projects']) }}</div>
                    <div class="pjd-stat-sub">Projects available in the current dashboard scope.</div>
                </div>
                <div class="pjd-stat" style="--stat-color:#2563eb;">
                    <div class="pjd-stat-label">Project Value</div>
                    <div class="pjd-stat-value">{{ $currency($stats['project_value']) }}</div>
                    <div class="pjd-stat-sub">Overall value of the filtered allocated projects.</div>
                </div>
                <div class="pjd-stat" style="--stat-color:#16a34a;">
                    <div class="pjd-stat-label">Received Amount</div>
                    <div class="pjd-stat-value">{{ $currency($stats['received_amount']) }}</div>
                    <div class="pjd-stat-sub">Payments already received for these projects.</div>
                </div>
                <div class="pjd-stat" style="--stat-color:#f97316;">
                    <div class="pjd-stat-label">Balance Amount</div>
                    <div class="pjd-stat-value">{{ $currency($stats['balance_amount']) }}</div>
                    <div class="pjd-stat-sub">Outstanding amount still pending collection.</div>
                </div>
                <div class="pjd-stat" style="--stat-color:#ea580c;">
                    <div class="pjd-stat-label">Allocation Pending</div>
                    <div class="pjd-stat-value">{{ $allocationPendingCount ?? 0 }}</div>
                    <div class="pjd-stat-sub">Projects awaiting TL and coordinator assignment.</div>
                </div>
            </section>

            <section class="pjd-card">
                <div class="pjd-card-head">
                    <div>
                        <div class="pjd-card-title">Current Month Delivery Planned Projects</div>
                        <div class="pjd-card-sub">Projects with planned delivery dates in the current month.</div>
                    </div>
                    <span class="pjd-highlight">{{ $currentMonthDeliveryProjects->count() }} Planned This Month</span>
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

                setTaskModalState(true);
            });
        });

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
