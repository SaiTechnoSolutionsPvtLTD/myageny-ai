@extends('layouts.app')

@section('title', 'Project Timesheets')

@push('styles')
<style>
.pts-page { min-height:100%; background:linear-gradient(180deg,#f7fbff 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pts-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #e6edf5; }
.pts-title { font-size:24px; font-weight:900; color:#111827; }
.pts-breadcrumb { font-size:12px; color:#64748b; margin-top:4px; }
.pts-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pts-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#111827; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; transition:all 0.2s; }
.pts-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.pts-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.pts-btn-outline { border-color:#ea580c; color:#ea580c; background:#fff; }
.pts-btn-outline:hover { background:#fff7ed; }
.pts-body { padding:22px 28px 34px; display:grid; gap:18px; }
.pts-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; overflow:hidden; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.pts-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; padding:18px 20px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-card-title { font-size:16px; font-weight:900; color:#111827; }
.pts-card-sub { margin-top:4px; font-size:12px; color:#64748b; }
.pts-card-body { padding:20px; }
.pts-flash { padding:12px 14px; border-radius:10px; font-size:13px; font-weight:700; }
.pts-flash.success { background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; }
.pts-flash.error { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
.pts-empty { padding:40px 20px; text-align:center; color:#64748b; font-size:13px; }
.pts-table-wrap { overflow-x:auto; }
.pts-table { width:100%; border-collapse:collapse; min-width:860px; }
.pts-table th { padding:14px 16px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; background:#f8fafc; border-bottom:1px solid #edf2f7; }
.pts-table td { padding:16px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#111827; vertical-align:middle; }
.pts-meta { margin-top:4px; font-size:11px; color:#64748b; }
.pts-project { font-weight:900; color:#0f172a; }
.pts-update-text { line-height:1.65; color:#334155; }
.pts-pill { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid #fed7aa; color:#c2410c; background:#fff7ed; }

.pts-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; }
.pts-badge.pending { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.pts-badge.ongoing { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.pts-badge.completed { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.pts-badge.count { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }

.pts-status-select {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    border: 1px solid transparent;
    cursor: pointer;
    outline: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2212%22%20height%3D%2212%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22currentColor%22%20stroke-width%3D%223%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 10px;
    padding-right: 24px;
    transition: all 0.2s;
}
.pts-status-select.completed { color:#15803d; background-color:#f0fdf4; border-color:#bbf7d0; }
.pts-status-select.ongoing { color:#1d4ed8; background-color:#eff6ff; border-color:#bfdbfe; }
.pts-status-select.pending { color:#b45309; background-color:#fff7ed; border-color:#fed7aa; }
.pts-status-select:disabled {
    opacity: 0.55;
    cursor: not-allowed !important;
    background-color: #f1f5f9 !important;
    color: #94a3b8 !important;
    border-color: #e2e8f0 !important;
}

.pts-filter-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; padding:16px; box-shadow:0 10px 28px rgba(15,23,42,.04); }
.pts-filter-form { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; align-items:end; }
.pts-filter-group { display:grid; gap:7px; }
.pts-filter-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.pts-reset-btn { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:13px; font-weight:800; text-decoration:none; }

.pts-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.48); z-index:1200; display:none; }
.pts-modal-overlay.is-open { display:block; }
.pts-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(1000px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 24px 60px rgba(15,23,42,.22); z-index:1210; display:none; }
.pts-modal.is-open { display:block; }
.pts-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-modal-close { width:40px; height:40px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:16px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
.pts-modal-close:hover { background:#f1f5f9; }
.pts-modal-body { padding:20px; }

.pts-submodal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:1240; display:none; }
.pts-submodal-overlay.is-open { display:block; }
.pts-submodal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(620px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 28px 70px rgba(15,23,42,.32); z-index:1250; display:none; }
.pts-submodal.is-open { display:block; }

.modal-task-table { width:100%; border-collapse:collapse; min-width:820px; table-layout:auto; }
.modal-task-table th { padding:14px 16px; background:#f8fafc; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; border-bottom:1px solid #e2e8f0; text-align:left !important; vertical-align:middle; }
.modal-task-table th.th-center, .modal-task-table td.td-center { text-align:center !important; }
.modal-task-table th.th-right, .modal-task-table td.td-right { text-align:right !important; }
.modal-task-table td { padding:14px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:13px; text-align:left !important; color:#111827; }

.pts-form-grid { display:grid; grid-template-columns: repeat(12, 1fr); gap:16px; align-items:start; }
.pts-grid-col-12 { grid-column: span 12; }
.pts-grid-col-6 { grid-column: span 6; }
.pts-grid-col-4 { grid-column: span 4; }
.pts-grid-col-3 { grid-column: span 3; }
.pts-label { display:block; margin-bottom:8px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; }
.pts-input, .pts-select, .pts-textarea { width:100%; border:1px solid #dbe2ea; border-radius:10px; background:#fff; font-size:14px; color:#111827; }
.pts-input, .pts-select { min-height:44px; padding:10px 12px; }
.pts-textarea { min-height:170px; padding:12px; resize:vertical; line-height:1.55; }
.pts-input:focus, .pts-select:focus, .pts-textarea:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.14); }
.pts-input[readonly] { background:#f8fafc; color:#475569; cursor:not-allowed; }
.pts-help { margin-top:7px; font-size:12px; color:#64748b; line-height:1.55; }
.pts-error { margin-top:7px; font-size:12px; color:#b91c1c; font-weight:700; }
.select2-container--default .select2-selection--single.pts-select2-selection { height:44px; border:1px solid #dbe2ea; border-radius:10px; background:#fff; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__rendered { line-height:42px; padding-left:12px; padding-right:34px; font-size:14px; color:#111827; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__arrow { height:42px; right:8px; }
.select2-container--default.select2-container--focus .select2-selection--single.pts-select2-selection,
.select2-container--default.select2-container--open .select2-selection--single.pts-select2-selection { border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.14); }
.select2-dropdown { border:1px solid #dbe2ea; border-radius:10px; overflow:hidden; box-shadow:0 16px 36px rgba(15,23,42,.12); }
.select2-search--dropdown { padding:10px; }
.select2-search--dropdown .select2-search__field { border:1px solid #dbe2ea; border-radius:8px; padding:8px 10px; font-size:13px; }
.select2-results__option { font-size:13px; padding:10px 12px; }
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background:#ea580c; color:#fff; }
.select2-container--open { z-index:1220; }
@media (max-width: 900px) {
    .pts-form-grid > div { grid-column: span 12 !important; }
    .pts-filter-form { grid-template-columns:1fr 1fr; }
}
@media (max-width: 768px) {
    .pts-topbar { padding:18px 16px; flex-direction:column; }
    .pts-body { padding:18px 16px 24px; }
    .pts-card-head { flex-direction:column; }
    .pts-modal-body, .pts-modal-head { padding:16px; }
    .pts-filter-form { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
@php
    $hasTimesheetErrors = $errors->any();
    $selectedProjectId = (int) old('production_initiation_id', 0);
    $selectedLeadId = 0;
    if ($selectedProjectId > 0) {
        $selectedProj = $assignedProjects->firstWhere('id', $selectedProjectId);
        if ($selectedProj) {
            $selectedLeadId = (int) $selectedProj->lead_id;
        }
    }

    $uniqueLeads = $assignedProjects->groupBy('lead_id')->map(function ($projects) {
        $firstProj = $projects->first();
        return [
            'lead_id' => $firstProj->lead_id,
            'company_name' => $firstProj->company_name ?: ($firstProj->lead?->company_name ?: 'No Company')
        ];
    })->values();
@endphp
<div class="pts-page">
    <div class="pts-topbar">
        <div>
            <div class="pts-title">Project Timesheets</div>
            <div class="pts-breadcrumb">Projects > Timesheets</div>
        </div>
        <div class="pts-actions">
            @can('timesheets.create')
            <button type="button" class="pts-btn pts-btn-primary" data-open-timesheet-modal>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Timesheet</span>
            </button>
            @endcan
        </div>
    </div>

    <div class="pts-body">
        @if(session('success'))
            <div class="pts-flash success">{{ session('success') }}</div>
        @endif

        @if($hasTimesheetErrors)
            <div class="pts-flash error">Please check the timesheet form and try again.</div>
        @endif

        <section class="pts-filter-card">
            <form method="GET" action="{{ route('projects.timesheets') }}" class="pts-filter-form">
                <div class="pts-filter-group">
                    <label class="pts-label">From Date</label>
                    <input type="date" name="filter_date_from" value="{{ $timesheetFilters['filter_date_from'] ?? '' }}" class="pts-input">
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">To Date</label>
                    <input type="date" name="filter_date_to" value="{{ $timesheetFilters['filter_date_to'] ?? '' }}" class="pts-input">
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">Lead (Client)</label>
                    <select name="filter_lead_id" class="pts-select select2 pts-filter-lead-select" data-placeholder="All Leads">
                        <option value="">All Leads</option>
                        @foreach($uniqueLeads as $lead)
                            <option value="{{ $lead['lead_id'] }}" @selected(($timesheetFilters['filter_lead_id'] ?? '') === (string) $lead['lead_id'])>
                                LD-{{ str_pad($lead['lead_id'], 4, '0', STR_PAD_LEFT) }} | {{ $lead['company_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">Project</label>
                    <select name="filter_project_id" class="pts-select select2 pts-filter-project-select" data-placeholder="All projects">
                        <option value="">All Projects</option>
                        @foreach($assignedProjects as $project)
                            <option value="{{ $project->id }}" @selected(($timesheetFilters['filter_project_id'] ?? '') === (string) $project->id)>
                                {{ $project->product_name }} | {{ $project->company_name ?: ($project->lead?->company_name ?: 'No company') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">Status</label>
                    <select name="filter_status" class="pts-select">
                        <option value="">All Status</option>
                        <option value="pending" @selected(($timesheetFilters['filter_status'] ?? '') === 'pending')>Pending</option>
                        <option value="ongoing" @selected(($timesheetFilters['filter_status'] ?? '') === 'ongoing')>Ongoing</option>
                        <option value="completed" @selected(($timesheetFilters['filter_status'] ?? '') === 'completed')>Completed</option>
                    </select>
                </div>

                @if($departments->isNotEmpty())
                    <div class="pts-filter-group">
                        <label class="pts-label">Department</label>
                        <select name="filter_department_id" class="pts-select">
                            @if($isAdminLike ?? false)
                                <option value="">All Departments</option>
                            @endif
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(($timesheetFilters['filter_department_id'] ?? '') === (string) $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($allUsers->isNotEmpty())
                    <div class="pts-filter-group">
                        <label class="pts-label">Employee</label>
                        <select name="filter_user_id" class="pts-select select2" data-placeholder="All Employees">
                            <option value="">All Employees</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" @selected(($timesheetFilters['filter_user_id'] ?? '') === (string) $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="pts-filter-actions">
                    <button type="submit" class="pts-btn pts-btn-primary" style="min-height:44px;">Filter</button>
                    <a href="{{ route('projects.timesheets') }}" class="pts-reset-btn" style="min-height:44px; display:inline-flex; align-items:center;">Reset</a>
                </div>
            </form>
        </section>

        <!-- Saved Timesheets (Grouped by Date & Member) -->
        <section class="pts-card">
            <div class="pts-card-head">
                <div>
                    <div class="pts-card-title">Saved Timesheets (Grouped by Date)</div>
                    <div class="pts-card-sub">Your submitted day closing updates grouped date-wise.</div>
                </div>
                <span class="pts-pill">{{ $groupedTimesheets->total() }} Submissions</span>
            </div>
            <div class="pts-card-body" style="padding:0;">
                @if($groupedTimesheets->isNotEmpty())
                    <div class="pts-table-wrap">
                        <table class="pts-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    @if(($isAdminLike ?? false) || ($canViewTeamTimesheets ?? false))
                                        <th>Employee</th>
                                    @endif
                                    <th>Allocated Lead (Client)</th>
                                    <th>Timesheets</th>
                                    <th>Submitted</th>
                                    <th>Status Overview</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedTimesheets as $group)
                                    @php
                                        $firstTimesheet = $group->first();
                                        $groupDate = $firstTimesheet->timesheet_date;
                                        $timesheetUser = $firstTimesheet->user;
                                        $totalEntries = $group->count();

                                        $pendingCount = $group->where('status', 'pending')->count();
                                        $ongoingCount = $group->where('status', 'ongoing')->count();
                                        $completedCount = $group->where('status', 'completed')->count();

                                        // Prepare clean JSON array for modal
                                        $groupTimesheetsData = $group->map(function($t, $idx) {
                                            $leadId = $t->project?->lead_id;
                                            $leadFormatted = $leadId ? 'LD-' . str_pad($leadId, 4, '0', STR_PAD_LEFT) : 'N/A';
                                            $companyName = $t->project?->company_name ?: ($t->project?->lead?->company_name ?: 'No Company');
                                            $productName = $t->project?->product_name ?: 'Project removed';
                                            $deliveryDate = optional($t->project_delivery_date)->format('d M Y') ?: (optional($t->project?->timesheet_delivery_date)->format('d M Y') ?: 'Not available');

                                            $deptName = strtolower((string)($t->project?->department?->name ?? ''));
                                            $isDesignOrDm = str_contains($deptName, 'design') || str_contains($deptName, 'dm') || str_contains($deptName, 'digital marketing');

                                            return [
                                                'id' => $t->id,
                                                'sno' => $idx + 1,
                                                'lead_id' => $leadFormatted,
                                                'lead_company' => $companyName,
                                                'product_name' => $productName,
                                                'delivery_date' => $deliveryDate,
                                                'assigned_task' => $t->assigned_task_desc ?? '—',
                                                'day_closing_update' => $t->user_closing_update ?? '',
                                                'status' => strtolower($t->status ?: 'pending'),
                                                'project_type' => $t->project_type ?: 'recurring',
                                                'is_design_dm' => $isDesignOrDm,
                                                'poster_count' => (int) $t->poster_count,
                                                'video_count' => (int) $t->video_count,
                                                'committed_posters' => (int) $t->committed_posters,
                                                'committed_videos' => (int) $t->committed_videos,
                                                'waiting_posters' => (int) $t->waiting_posters,
                                                'waiting_videos' => (int) $t->waiting_videos,
                                                'update_status_url' => route('projects.timesheets.update-status', $t->id),
                                            ];
                                        })->values();

                                        $uniqueCompanies = $group->map(function ($ts) {
                                            return $ts->project?->company_name ?: ($ts->project?->lead?->company_name ?: 'No Company');
                                        })->unique()->values();

                                        $latestSubmitted = $group->sortByDesc('created_at')->first()?->created_at;
                                    @endphp
                                    <tr data-group-row>
                                        <!-- Date -->
                                        <td>
                                            <div style="font-weight:800; color:#0f172a;">
                                                {{ optional($groupDate)->format('d M Y') ?: 'No date' }}
                                            </div>
                                            <div class="pts-meta">{{ optional($groupDate)->format('l') }}</div>
                                        </td>

                                        <!-- Employee -->
                                        @if(($isAdminLike ?? false) || ($canViewTeamTimesheets ?? false))
                                            <td>
                                                <div style="display:flex; align-items:center; gap:10px;">
                                                    <div style="width:36px; height:36px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; color:#ea580c; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:13px;">
                                                        {{ strtoupper(substr($timesheetUser?->name ?: 'U', 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <div style="font-weight:800; color:#0f172a;">{{ $timesheetUser?->name ?? 'Unknown' }}</div>
                                                        <div class="pts-meta">{{ $timesheetUser?->designation ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                        @endif

                                        <!-- Allocated Lead (Client) -->
                                        <td>
                                            <div style="display:flex; flex-direction:column; gap:6px; max-width:280px;">
                                                @foreach($uniqueCompanies->take(2) as $companyName)
                                                    <div style="font-weight:800; color:#0f172a; display:flex; align-items:center; gap:7px;">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16"></path><path d="M12 7h.01"></path><path d="M12 11h.01"></path><path d="M12 15h.01"></path></svg>
                                                        <span>{{ $companyName }}</span>
                                                    </div>
                                                @endforeach
                                                @if($uniqueCompanies->count() > 2)
                                                    <div class="pts-meta" style="font-weight:800; color:#64748b; margin-left:21px;">+ {{ $uniqueCompanies->count() - 2 }} more client(s)</div>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Timesheets button -->
                                        <td>
                                            <button type="button"
                                                    class="pts-btn pts-btn-outline open-timesheets-modal-btn"
                                                    data-user-name="{{ $timesheetUser?->name ?: 'My Timesheets' }}"
                                                    data-timesheet-date="{{ optional($groupDate)->format('d M Y') }}"
                                                    data-timesheets='@json($groupTimesheetsData)'>
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                                <span>View Timesheets</span>
                                                <span class="pts-badge count" style="padding:2px 7px; font-size:10px; margin-left:2px;">{{ $totalEntries }}</span>
                                            </button>
                                        </td>

                                        <!-- Submitted -->
                                        <td>
                                            <div style="font-weight:700; color:#334155;">
                                                {{ optional($latestSubmitted)->format('d M Y, h:i A') ?: 'Not available' }}
                                            </div>
                                        </td>

                                        <!-- Status Overview -->
                                        <td>
                                            <div class="status-overview-badges" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                                @if($pendingCount > 0)
                                                    <span class="pts-badge pending">{{ $pendingCount }} Pending</span>
                                                @endif
                                                @if($ongoingCount > 0)
                                                    <span class="pts-badge ongoing">{{ $ongoingCount }} Ongoing</span>
                                                @endif
                                                @if($completedCount > 0)
                                                    <span class="pts-badge completed">{{ $completedCount }} Completed</span>
                                                @endif
                                                @if($pendingCount === 0 && $ongoingCount === 0 && $completedCount === 0)
                                                    <span class="pts-meta">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($groupedTimesheets->hasPages())
                        @include('partials.table-pagination', ['paginator' => $groupedTimesheets])
                    @endif
                @else
                    <div class="pts-empty">No timesheets submitted yet. Use Add Timesheet to enter today&apos;s day closing update.</div>
                @endif
            </div>
        </section>
    </div>
</div>

<!-- View Timesheets Details Modal Popup -->
<div class="pts-modal-overlay" data-view-timesheets-modal-overlay></div>
<div class="pts-modal" data-view-timesheets-modal style="width:min(1150px, calc(100vw - 32px));">
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div>
                <div class="pts-card-title" id="modalTimesheetMemberName" style="font-size:17px; font-weight:900; color:#111827; line-height:1.3;">Timesheet Details</div>
                <div class="pts-card-sub" id="modalTimesheetSubTitle" style="margin-top:3px; font-size:12px; color:#64748b;">Date: N/A | Total 0 Timesheet(s)</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-view-timesheets-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body" style="padding:0;">
        <div class="pts-table-wrap">
            <table class="modal-task-table">
                <thead>
                    <tr>
                        <th class="th-center" style="width:40px;">#</th>
                        <th style="width:160px;">Lead (Client)</th>
                        <th style="width:170px;">Product / Delivery</th>
                        <th style="width:230px;">Assigned Task</th>
                        <th style="min-width:270px;">Day Closing Update</th>
                        <th class="th-center" style="width:130px;">Status</th>
                    </tr>
                </thead>
                <tbody id="modalTimesheetsTableBody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Day Closing Update Entry/Edit Submodal Popup -->
<div class="pts-submodal-overlay" data-entry-closing-modal-overlay></div>
<div class="pts-submodal" data-entry-closing-modal>
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:38px; height:38px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
            </div>
            <div>
                <div class="pts-card-title" id="entryClosingModalTitle" style="font-size:16px; font-weight:900; color:#111827;">Add Day Closing Update</div>
                <div class="pts-card-sub" id="entryClosingModalSubTitle" style="margin-top:2px; font-size:12px; color:#64748b;">Project Name - Client</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-entry-closing-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body">
        <form id="entryClosingForm">
            <input type="hidden" id="entryClosingTimesheetId">
            <input type="hidden" id="entryClosingUpdateUrl">
            <div style="display:grid; gap:16px;">
                <div>
                    <label class="pts-label">Day Closing Update Details <span style="color:#ef4444;">*</span></label>
                    <textarea id="entryClosingTextarea" class="pts-textarea" rows="6" required style="min-height:140px;" placeholder="Write your completed tasks and day closing details here..."></textarea>
                </div>
            </div>
            <div class="pts-actions" style="margin-top:20px; justify-content:flex-end;">
                <button type="button" class="pts-btn" data-close-entry-closing-modal>Cancel</button>
                <button type="submit" id="entryClosingSubmitBtn" class="pts-btn pts-btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Save Closing Update</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Day Closing Update Details View Submodal Popup -->
<div class="pts-submodal-overlay" data-view-closing-modal-overlay></div>
<div class="pts-submodal" data-view-closing-modal>
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:38px; height:38px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            </div>
            <div>
                <div class="pts-card-title" id="viewClosingModalTitle" style="font-size:16px; font-weight:900; color:#111827;">Day Closing Update Details</div>
                <div class="pts-card-sub" id="viewClosingModalSubTitle" style="margin-top:2px; font-size:12px; color:#64748b;">Project - Date</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-view-closing-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body">
        <div id="viewClosingModalContent" style="white-space:pre-wrap; line-height:1.65; color:#1e293b; font-size:13.5px; background:#f8fafc; padding:18px; border-radius:10px; border:1px solid #e2e8f0; max-height:350px; overflow-y:auto;">
        </div>
        <div class="pts-actions" style="margin-top:18px; justify-content:flex-end; gap:8px;">
            <button type="button" class="pts-btn" data-close-view-closing-modal>Close</button>
            <button type="button" id="viewClosingEditShortcutBtn" class="pts-btn pts-btn-primary">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                <span>Edit Update</span>
            </button>
        </div>
    </div>
</div>

<!-- Add Timesheet Modal Popup -->
<div class="pts-modal-overlay {{ $hasTimesheetErrors ? 'is-open' : '' }}" data-timesheet-modal-overlay></div>
<div class="pts-modal {{ $hasTimesheetErrors ? 'is-open' : '' }}" data-timesheet-modal>
    <div class="pts-modal-head">
        <div>
            <div class="pts-card-title">Add Timesheet</div>
            <div class="pts-card-sub">Select one allocated project and add your day closing tasks.</div>
        </div>
        <button type="button" class="pts-modal-close" data-close-timesheet-modal aria-label="Close timesheet modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="pts-modal-body">
        <form method="POST" action="{{ route('projects.timesheets.store') }}">
            @csrf
            <div class="pts-form-grid">
                <!-- Lead Dropdown -->
                <div class="pts-grid-col-6">
                    <label class="pts-label">Lead (Client)</label>
                    <select id="leadSelect" class="pts-select select2" data-placeholder="Select Lead" required>
                        <option value="">Select Lead</option>
                        @foreach($uniqueLeads as $lead)
                            <option value="{{ $lead['lead_id'] }}" @selected($selectedLeadId === (int) $lead['lead_id'])>
                                LD-{{ str_pad($lead['lead_id'], 4, '0', STR_PAD_LEFT) }} | {{ $lead['company_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Project Dropdown -->
                <div class="pts-grid-col-6">
                    <label class="pts-label">Allocated Project</label>
                    <select name="production_initiation_id" id="projectSelect" class="pts-select select2 pts-project-select" data-placeholder="Select project" required disabled>
                        <option value="">Select project</option>
                    </select>
                    @if($assignedProjects->isEmpty())
                        <div class="pts-help">No allocated projects are available for your account right now.</div>
                    @else
                        <div class="pts-help">Select a lead first to see its allocated projects.</div>
                    @endif
                    @error('production_initiation_id')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Date -->
                <div class="pts-grid-col-4">
                    <label class="pts-label">Date</label>
                    <input type="date" name="timesheet_date" id="timesheetDateInput" min="{{ $today }}" value="{{ old('timesheet_date', $today) }}" class="pts-input" required>
                    @error('timesheet_date')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Project Delivery Date -->
                <div class="pts-grid-col-4">
                    <label class="pts-label">Project Delivery Date</label>
                    <input type="date" class="pts-input" data-project-delivery-date readonly>
                </div>

                <!-- Status -->
                <div class="pts-grid-col-4">
                    <label class="pts-label">Status</label>
                    <select name="status" class="pts-select" required>
                        <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                        <option value="ongoing" @selected(old('status') === 'ongoing')>Ongoing</option>
                        <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                    </select>
                    @error('status')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Project Type (Design/DM only) -->
                <div class="pts-grid-col-12 design-dm-only" id="projectTypeContainer" style="display: none;">
                    <label class="pts-label">Project Type</label>
                    <select name="project_type" id="projectTypeSelect" class="pts-select">
                        <option value="recurring" @selected(old('project_type', 'recurring') === 'recurring')>Recurring</option>
                        <option value="onetime" @selected(old('project_type') === 'onetime')>Onetime</option>
                    </select>
                    @error('project_type')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                @if(auth()->user()?->belongsToDesigningDepartment())
                    <div class="pts-grid-col-3 project-counts-fields" style="display: none;">
                        <label class="pts-label">Committed Posters Today</label>
                        <input type="number" name="committed_posters" value="{{ old('committed_posters', 0) }}" class="pts-input" min="0" step="1">
                        @error('committed_posters')
                            <div class="pts-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="pts-grid-col-3 project-counts-fields" style="display: none;">
                        <label class="pts-label">Committed Videos Today</label>
                        <input type="number" name="committed_videos" value="{{ old('committed_videos', 0) }}" class="pts-input" min="0" step="1">
                        @error('committed_videos')
                            <div class="pts-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="pts-grid-col-3 project-counts-fields" style="display: none;">
                        <label class="pts-label">Waiting Approval Posters</label>
                        <input type="number" name="waiting_posters" value="{{ old('waiting_posters', 0) }}" class="pts-input" min="0" step="1">
                        @error('waiting_posters')
                            <div class="pts-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="pts-grid-col-3 project-counts-fields" style="display: none;">
                        <label class="pts-label">Waiting Approval Videos</label>
                        <input type="number" name="waiting_videos" value="{{ old('waiting_videos', 0) }}" class="pts-input" min="0" step="1">
                        @error('waiting_videos')
                            <div class="pts-error">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
                <!-- Poster count (Design/DM only) -->
                <div class="pts-grid-col-6 design-dm-only project-counts-fields" style="display: none;">
                    <label class="pts-label">Poster Completed Count</label>
                    <input type="number" name="poster_count" value="{{ old('poster_count', 0) }}" class="pts-input" min="0" step="1">
                    @error('poster_count')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Video count (Design/DM only) -->
                <div class="pts-grid-col-6 design-dm-only project-counts-fields" style="display: none;">
                    <label class="pts-label">Video Completed Count</label>
                    <input type="number" name="video_count" value="{{ old('video_count', 0) }}" class="pts-input" min="0" step="1">
                    @error('video_count')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="pts-grid-col-12" id="dayClosingUpdateContainer">
                    <label class="pts-label">Day Closing Update</label>
                    <textarea name="day_closing_update" id="dayClosingUpdateTextarea" class="pts-textarea" rows="7" required placeholder="Enter your day closing update details...">{{ old('day_closing_update') }}</textarea>
                    <div class="pts-help">Enter your day closing update details.</div>
                    @error('day_closing_update')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="pts-actions" style="margin-top:18px; justify-content:flex-end;">
                <button type="button" class="pts-btn" data-close-timesheet-modal>Cancel</button>
                <button type="submit" class="pts-btn pts-btn-primary" @disabled($assignedProjects->isEmpty())>Save Timesheet</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-timesheet-modal]');
    const overlay = document.querySelector('[data-timesheet-modal-overlay]');
    const openButtons = document.querySelectorAll('[data-open-timesheet-modal]');
    const closeButtons = document.querySelectorAll('[data-close-timesheet-modal]');
    const leadSelect = document.getElementById('leadSelect');
    const projectSelect = document.getElementById('projectSelect');
    const filterProjectSelect = document.querySelector('.pts-filter-project-select');
    const deliveryDateInput = document.querySelector('[data-project-delivery-date]');
    const assignedProjects = @json($assignedProjects);
    const selectedProjectId = @json($selectedProjectId);

    function setModalState(isOpen) {
        if (!modal || !overlay) {
            return;
        }

        modal.classList.toggle('is-open', isOpen);
        overlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function syncDeliveryDate() {
        if (!projectSelect || !deliveryDateInput) {
            return;
        }

        const selectedOption = projectSelect.options[projectSelect.selectedIndex];
        deliveryDateInput.value = selectedOption ? (selectedOption.dataset.deliveryDate || '') : '';
    }

    function toggleDesignDmInputs() {
        if (!projectSelect) return;
        const selectedProjId = projectSelect.value;
        const selectedProject = assignedProjects.find(p => p.id == selectedProjId);

        if (selectedProject) {
            const deptName = selectedProject.department ? selectedProject.department.name.toLowerCase() : '';
            const isDesignOrDm = deptName.includes('design') || deptName.includes('dm') || deptName.includes('digital marketing');

            // Show or hide project type container
            const typeContainer = document.getElementById('projectTypeContainer');
            if (typeContainer) {
                typeContainer.style.display = isDesignOrDm ? '' : 'none';
            }

            // Determine if counts should be shown
            const typeSelect = document.getElementById('projectTypeSelect');
            const isRecurring = typeSelect ? (typeSelect.value === 'recurring') : true;

            // Show counts fields ONLY if it's Design/DM AND Recurring type
            const showCounts = isDesignOrDm && isRecurring;

            document.querySelectorAll('.project-counts-fields').forEach(el => {
                el.style.display = showCounts ? '' : 'none';
            });

            // Day Closing Update is always shown and required regardless of project type (Recurring / Onetime)
            const dayClosingContainer = document.getElementById('dayClosingUpdateContainer');
            const dayClosingTextarea = document.getElementById('dayClosingUpdateTextarea');
            if (dayClosingContainer && dayClosingTextarea) {
                dayClosingContainer.style.display = '';
                dayClosingTextarea.setAttribute('required', 'required');
            }
        } else {
            // Hide both type selector and counts if no project is selected
            const typeContainer = document.getElementById('projectTypeContainer');
            if (typeContainer) {
                typeContainer.style.display = 'none';
            }
            document.querySelectorAll('.project-counts-fields').forEach(el => {
                el.style.display = 'none';
            });
            const dayClosingContainer = document.getElementById('dayClosingUpdateContainer');
            const dayClosingTextarea = document.getElementById('dayClosingUpdateTextarea');
            if (dayClosingContainer && dayClosingTextarea) {
                dayClosingContainer.style.display = '';
                dayClosingTextarea.setAttribute('required', 'required');
            }
        }
    }

    function fetchTimesheetData() {
        if (!projectSelect) return;
        const projectId = projectSelect.value;
        const dateInput = document.getElementById('timesheetDateInput');
        const dateVal = dateInput ? dateInput.value : '';

        if (!projectId || !dateVal) {
            return;
        }

        fetch(`/projects/timesheets/get-data?production_initiation_id=${projectId}&timesheet_date=${dateVal}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const statusSelect = document.querySelector('select[name="status"]');
                    const typeSelect = document.getElementById('projectTypeSelect');
                    const posterCountInput = document.querySelector('input[name="poster_count"]');
                    const videoCountInput = document.querySelector('input[name="video_count"]');
                    const committedPostersInput = document.querySelector('input[name="committed_posters"]');
                    const committedVideosInput = document.querySelector('input[name="committed_videos"]');
                    const waitingPostersInput = document.querySelector('input[name="waiting_posters"]');
                    const waitingVideosInput = document.querySelector('input[name="waiting_videos"]');
                    const dayClosingTextarea = document.getElementById('dayClosingUpdateTextarea');

                    if (res.exists && res.data) {
                        const d = res.data;
                        if (statusSelect) statusSelect.value = d.status || 'pending';
                        if (typeSelect) typeSelect.value = d.project_type || 'recurring';
                        if (posterCountInput) posterCountInput.value = d.poster_count;
                        if (videoCountInput) videoCountInput.value = d.video_count;
                        if (committedPostersInput) committedPostersInput.value = d.committed_posters;
                        if (committedVideosInput) committedVideosInput.value = d.committed_videos;
                        if (waitingPostersInput) waitingPostersInput.value = d.waiting_posters;
                        if (waitingVideosInput) waitingVideosInput.value = d.waiting_videos;
                        if (dayClosingTextarea) dayClosingTextarea.value = d.day_closing_update;
                    } else {
                        if (statusSelect) statusSelect.value = 'pending';
                        if (typeSelect) typeSelect.value = 'recurring';
                        if (posterCountInput) posterCountInput.value = 0;
                        if (videoCountInput) videoCountInput.value = 0;
                        if (committedPostersInput) committedPostersInput.value = 0;
                        if (committedVideosInput) committedVideosInput.value = 0;
                        if (waitingPostersInput) waitingPostersInput.value = 0;
                        if (waitingVideosInput) waitingVideosInput.value = 0;
                        if (dayClosingTextarea) dayClosingTextarea.value = '';
                    }
                    toggleDesignDmInputs();
                }
            })
            .catch(err => console.error('Error fetching timesheet data:', err));
    }

    function handleLeadChange() {
        if (!leadSelect || !projectSelect) return;

        const leadId = leadSelect.value;

        // Clear previous options
        projectSelect.innerHTML = '<option value="">Select project</option>';

        if (!leadId) {
            projectSelect.disabled = true;
            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(projectSelect).val('').trigger('change.select2');
                window.jQuery(projectSelect).prop('disabled', true);
            }
            syncDeliveryDate();
            toggleDesignDmInputs();
            return;
        }

        // Filter projects
        const filtered = assignedProjects.filter(p => p.lead_id == leadId);

        // Populate options
        filtered.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.product_name + ' (Delivery: ' + (p.timesheet_delivery_date || 'N/A') + ')';
            opt.dataset.deliveryDate = p.timesheet_delivery_date || '';
            projectSelect.appendChild(opt);
        });

        // Enable select
        projectSelect.disabled = false;
        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery(projectSelect).prop('disabled', false);
            window.jQuery(projectSelect).trigger('change.select2');
        }

        syncDeliveryDate();
        toggleDesignDmInputs();
        fetchTimesheetData();
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setModalState(true);
            window.setTimeout(syncDeliveryDate, 0);
        });
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setModalState(false);
        });
    });

    if (overlay) {
        overlay.addEventListener('click', function () {
            setModalState(false);
        });
    }

    if (leadSelect) {
        leadSelect.addEventListener('change', handleLeadChange);
    }

    if (projectSelect) {
        projectSelect.addEventListener('change', function() {
            syncDeliveryDate();
            toggleDesignDmInputs();
            fetchTimesheetData();
        });
    }

    const dateInput = document.getElementById('timesheetDateInput');
    if (dateInput) {
        dateInput.addEventListener('change', fetchTimesheetData);
    }

    const typeSelect = document.getElementById('projectTypeSelect');
    if (typeSelect) {
        typeSelect.addEventListener('change', toggleDesignDmInputs);
    }

    // Initialize Select2
    if (window.jQuery && window.jQuery.fn.select2) {
        const $lead = window.jQuery(leadSelect);
        const $project = window.jQuery(projectSelect);
        const $filterProject = window.jQuery(filterProjectSelect);

        if ($lead.length) {
            if ($lead.hasClass('select2-hidden-accessible')) {
                $lead.select2('destroy');
            }
            $lead.select2({
                width: '100%',
                placeholder: 'Select Lead',
                dropdownParent: window.jQuery(modal),
            });
            $lead.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
            $lead.on('change select2:select', handleLeadChange);
        }

        if ($project.length) {
            if ($project.hasClass('select2-hidden-accessible')) {
                $project.select2('destroy');
            }
            $project.select2({
                width: '100%',
                placeholder: 'Select project',
                dropdownParent: window.jQuery(modal),
            });
            $project.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
            $project.on('change select2:select', function() {
                syncDeliveryDate();
                toggleDesignDmInputs();
                fetchTimesheetData();
            });
        }

        if ($filterProject.length) {
            if ($filterProject.hasClass('select2-hidden-accessible')) {
                $filterProject.select2('destroy');
            }
            $filterProject.select2({
                width: '100%',
                placeholder: 'All projects',
                allowClear: true,
            });
            $filterProject.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        }

        const filterLeadSelect = document.querySelector('.pts-filter-lead-select');
        if (filterLeadSelect) {
            const $filterLead = window.jQuery(filterLeadSelect);
            if ($filterLead.hasClass('select2-hidden-accessible')) {
                $filterLead.select2('destroy');
            }
            $filterLead.select2({
                width: '100%',
                placeholder: 'All Leads',
                allowClear: true,
            });
            $filterLead.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        }

        const filterUserSelect = document.querySelector('select[name="filter_user_id"]');
        if (filterUserSelect) {
            const $filterUser = window.jQuery(filterUserSelect);
            if ($filterUser.hasClass('select2-hidden-accessible')) {
                $filterUser.select2('destroy');
            }
            $filterUser.select2({
                width: '100%',
                placeholder: 'All Employees',
                allowClear: true,
            });
            $filterUser.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        }
    }

    // Auto-restore old values on validation failure
    if (selectedProjectId) {
        const proj = assignedProjects.find(p => p.id == selectedProjectId);
        if (proj && leadSelect) {
            leadSelect.value = proj.lead_id;
            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(leadSelect).val(proj.lead_id).trigger('change.select2');
            }
            handleLeadChange();
            if (projectSelect) {
                projectSelect.value = selectedProjectId;
                if (window.jQuery && window.jQuery.fn.select2) {
                    window.jQuery(projectSelect).val(selectedProjectId).trigger('change.select2');
                }
            }
        }
    }

    window.setTimeout(syncDeliveryDate, 0);

    // View Timesheets Modal Logic
    const viewTimesheetsModal = document.querySelector('[data-view-timesheets-modal]');
    const viewTimesheetsOverlay = document.querySelector('[data-view-timesheets-modal-overlay]');
    const openTimesheetButtons = document.querySelectorAll('.open-timesheets-modal-btn');
    const closeViewTimesheetButtons = document.querySelectorAll('[data-close-view-timesheets-modal]');
    const modalMemberName = document.getElementById('modalTimesheetMemberName');
    const modalSubTitle = document.getElementById('modalTimesheetSubTitle');
    const modalTableBody = document.getElementById('modalTimesheetsTableBody');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    function setViewTimesheetsModalState(isOpen) {
        if (!viewTimesheetsModal || !viewTimesheetsOverlay) return;
        viewTimesheetsModal.classList.toggle('is-open', isOpen);
        viewTimesheetsOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function escapeHtml(str) {
        if (!str) return '';
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }

    let currentGroupBtn = null;
    let currentGroupRow = null;
    let currentGroupTimesheets = [];

    openTimesheetButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            currentGroupBtn = this;
            currentGroupRow = this.closest('tr');
            const userName = this.getAttribute('data-user-name') || 'Team Member';
            const dateStr = this.getAttribute('data-timesheet-date') || 'N/A';
            currentGroupTimesheets = [];

            try {
                currentGroupTimesheets = JSON.parse(this.getAttribute('data-timesheets') || '[]');
            } catch (e) {
                console.error('Failed to parse timesheets JSON', e);
            }

            if (modalMemberName) modalMemberName.textContent = 'Timesheets - ' + userName;
            if (modalSubTitle) modalSubTitle.textContent = 'Date: ' + dateStr + ' | Total ' + currentGroupTimesheets.length + ' Timesheet(s)';

            if (modalTableBody) {
                modalTableBody.innerHTML = '';

                if (currentGroupTimesheets.length === 0) {
                    modalTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No timesheets found for this date.</td></tr>';
                } else {
                    currentGroupTimesheets.forEach(ts => {
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-timesheet-id', ts.id);
                        const statusClass = (ts.status === 'completed') ? 'completed' : ((ts.status === 'ongoing') ? 'ongoing' : 'pending');

                        let closingActionHtml = '';
                        const hasClosingUpdate = (ts.day_closing_update && ts.day_closing_update.trim() !== '');
                        if (hasClosingUpdate) {
                            closingActionHtml = `
                                <div class="closing-btn-group" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <button type="button" class="pts-btn pts-btn-outline open-view-closing-btn"
                                            data-project-name="${escapeHtml(ts.product_name)}"
                                            data-lead-name="${escapeHtml(ts.lead_company)}"
                                            data-delivery-date="${escapeHtml(ts.delivery_date)}"
                                            data-closing-text="${escapeHtml(ts.day_closing_update)}"
                                            data-timesheet-id="${ts.id}"
                                            data-update-url="${ts.update_status_url}"
                                            style="padding:5px 10px; font-size:12px; gap:5px;" title="View closing update details">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        <span>View</span>
                                    </button>
                                    <button type="button" class="pts-btn open-entry-closing-btn"
                                            data-project-name="${escapeHtml(ts.product_name)}"
                                            data-lead-name="${escapeHtml(ts.lead_company)}"
                                            data-delivery-date="${escapeHtml(ts.delivery_date)}"
                                            data-closing-text="${escapeHtml(ts.day_closing_update)}"
                                            data-timesheet-id="${ts.id}"
                                            data-update-url="${ts.update_status_url}"
                                            style="padding:5px 9px; font-size:12px; gap:5px; background:#f8fafc; border-color:#cbd5e1;" title="Edit closing update">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        <span>Edit</span>
                                    </button>
                                </div>
                            `;
                        } else {
                            closingActionHtml = `
                                <div class="closing-btn-group">
                                    <button type="button" class="pts-btn pts-btn-primary open-entry-closing-btn"
                                            data-project-name="${escapeHtml(ts.product_name)}"
                                            data-lead-name="${escapeHtml(ts.lead_company)}"
                                            data-delivery-date="${escapeHtml(ts.delivery_date)}"
                                            data-closing-text=""
                                            data-timesheet-id="${ts.id}"
                                            data-update-url="${ts.update_status_url}"
                                            style="padding:6px 12px; font-size:12px; gap:6px;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        <span>Add Update</span>
                                    </button>
                                </div>
                            `;
                        }

                        const isStatusDisabled = !hasClosingUpdate;
                        const statusDisabledTooltip = isStatusDisabled ? 'Add Day Closing Update first to change status' : 'Change status';

                        tr.innerHTML = `
                            <td class="td-center" style="font-weight:800; color:#64748b;">${ts.sno}</td>
                            <td>
                                <div style="font-weight:800; color:#0f172a;">${escapeHtml(ts.lead_company)}</div>
                                <div class="pts-meta" style="font-weight:700; color:#ea580c;">${escapeHtml(ts.lead_id)}</div>
                            </td>
                            <td>
                                <div class="pts-project">${escapeHtml(ts.product_name)}</div>
                                <div class="pts-meta">Delivery: ${escapeHtml(ts.delivery_date)}</div>
                            </td>
                            <td>
                                <div style="font-size:12px; color:#334155; line-height:1.55; white-space:pre-wrap; background:#f8fafc; padding:8px 10px; border-radius:8px; border:1px solid #e2e8f0; max-height:140px; overflow-y:auto;">${escapeHtml(ts.assigned_task || '—')}</div>
                            </td>
                            <td>
                                ${closingActionHtml}
                            </td>
                            <td class="td-center">
                                <select class="pts-status-select ${statusClass} modal-timesheet-status-select"
                                        data-timesheet-id="${ts.id}"
                                        data-update-url="${ts.update_status_url}"
                                        ${isStatusDisabled ? 'disabled' : ''}
                                        title="${statusDisabledTooltip}">
                                    <option value="pending" ${ts.status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="ongoing" ${ts.status === 'ongoing' ? 'selected' : ''}>Ongoing</option>
                                    <option value="completed" ${ts.status === 'completed' ? 'selected' : ''}>Completed</option>
                                </select>
                            </td>
                        `;
                        modalTableBody.appendChild(tr);
                    });

                    // Attach change listeners to status selects
                    modalTableBody.querySelectorAll('.modal-timesheet-status-select').forEach(sel => {
                        sel.addEventListener('change', function() {
                            const newStatus = this.value;
                            const timesheetId = this.getAttribute('data-timesheet-id');
                            const updateUrl = this.getAttribute('data-update-url');
                            const currentSelect = this;

                            // Visual feedback
                            currentSelect.style.opacity = '0.6';

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
                                    currentSelect.classList.remove('pending', 'ongoing', 'completed');
                                    currentSelect.classList.add(newStatus);

                                    // Update item in currentGroupTimesheets
                                    const tsItem = currentGroupTimesheets.find(t => String(t.id) === String(timesheetId));
                                    if (tsItem) {
                                        tsItem.status = newStatus;
                                    }
                                    if (currentGroupBtn) {
                                        currentGroupBtn.setAttribute('data-timesheets', JSON.stringify(currentGroupTimesheets));
                                    }

                                    // Update Status Overview in the main table row
                                    if (currentGroupRow) {
                                        const pendingCount = currentGroupTimesheets.filter(t => t.status === 'pending').length;
                                        const ongoingCount = currentGroupTimesheets.filter(t => t.status === 'ongoing').length;
                                        const completedCount = currentGroupTimesheets.filter(t => t.status === 'completed').length;

                                        let badgesHtml = '';
                                        if (pendingCount > 0) {
                                            badgesHtml += `<span class="pts-badge pending">${pendingCount} Pending</span>`;
                                        }
                                        if (ongoingCount > 0) {
                                            badgesHtml += `<span class="pts-badge ongoing">${ongoingCount} Ongoing</span>`;
                                        }
                                        if (completedCount > 0) {
                                            badgesHtml += `<span class="pts-badge completed">${completedCount} Completed</span>`;
                                        }
                                        if (badgesHtml === '') {
                                            badgesHtml = '<span class="pts-meta">—</span>';
                                        }

                                        const overviewEl = currentGroupRow.querySelector('.status-overview-badges');
                                        if (overviewEl) {
                                            overviewEl.innerHTML = badgesHtml;
                                        }
                                    }
                                } else {
                                    alert(data.message || 'Failed to update status.');
                                }
                            })
                            .catch(err => {
                                currentSelect.style.opacity = '1';
                                console.error('Error updating timesheet status:', err);
                                alert('An error occurred while updating the status.');
                            });
                        });
                    });
                }
            }

            setViewTimesheetsModalState(true);
        });
    });

    closeViewTimesheetButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            setViewTimesheetsModalState(false);
        });
    });

    if (viewTimesheetsOverlay) {
        viewTimesheetsOverlay.addEventListener('click', function() {
            setViewTimesheetsModalState(false);
        });
    }

    // Submodals Logic (Entry Modal and View Modal)
    const entryClosingModal = document.querySelector('[data-entry-closing-modal]');
    const entryClosingOverlay = document.querySelector('[data-entry-closing-modal-overlay]');
    const closeEntryClosingButtons = document.querySelectorAll('[data-close-entry-closing-modal]');
    const entryClosingTitle = document.getElementById('entryClosingModalTitle');
    const entryClosingSubTitle = document.getElementById('entryClosingModalSubTitle');
    const entryClosingTextarea = document.getElementById('entryClosingTextarea');
    const entryClosingTimesheetId = document.getElementById('entryClosingTimesheetId');
    const entryClosingUpdateUrl = document.getElementById('entryClosingUpdateUrl');
    const entryClosingForm = document.getElementById('entryClosingForm');
    const entryClosingSubmitBtn = document.getElementById('entryClosingSubmitBtn');

    const viewClosingModal = document.querySelector('[data-view-closing-modal]');
    const viewClosingOverlay = document.querySelector('[data-view-closing-modal-overlay]');
    const closeViewClosingButtons = document.querySelectorAll('[data-close-view-closing-modal]');
    const viewClosingTitle = document.getElementById('viewClosingModalTitle');
    const viewClosingSubTitle = document.getElementById('viewClosingModalSubTitle');
    const viewClosingContent = document.getElementById('viewClosingModalContent');
    const viewClosingEditShortcutBtn = document.getElementById('viewClosingEditShortcutBtn');

    let currentActiveRow = null;

    function setEntryClosingModalState(isOpen) {
        if (!entryClosingModal || !entryClosingOverlay) return;
        entryClosingModal.classList.toggle('is-open', isOpen);
        entryClosingOverlay.classList.toggle('is-open', isOpen);
    }

    function setViewClosingModalState(isOpen) {
        if (!viewClosingModal || !viewClosingOverlay) return;
        viewClosingModal.classList.toggle('is-open', isOpen);
        viewClosingOverlay.classList.toggle('is-open', isOpen);
    }

    // Delegate click for open-entry-closing-btn
    document.addEventListener('click', function(e) {
        const entryBtn = e.target.closest('.open-entry-closing-btn');
        if (entryBtn) {
            const projectName = entryBtn.getAttribute('data-project-name') || 'Project';
            const leadName = entryBtn.getAttribute('data-lead-name') || '';
            const closingText = entryBtn.getAttribute('data-closing-text') || '';
            const updateUrl = entryBtn.getAttribute('data-update-url') || '';
            const timesheetId = entryBtn.getAttribute('data-timesheet-id') || '';

            currentActiveRow = entryBtn.closest('tr');

            if (entryClosingTitle) entryClosingTitle.textContent = closingText ? 'Edit Day Closing Update' : 'Add Day Closing Update';
            if (entryClosingSubTitle) entryClosingSubTitle.textContent = projectName + (leadName ? ' | ' + leadName : '');
            if (entryClosingTextarea) entryClosingTextarea.value = closingText;
            if (entryClosingTimesheetId) entryClosingTimesheetId.value = timesheetId;
            if (entryClosingUpdateUrl) entryClosingUpdateUrl.value = updateUrl;

            setEntryClosingModalState(true);
            setTimeout(() => { if (entryClosingTextarea) entryClosingTextarea.focus(); }, 100);
            return;
        }

        const viewBtn = e.target.closest('.open-view-closing-btn');
        if (viewBtn) {
            const projectName = viewBtn.getAttribute('data-project-name') || 'Project';
            const leadName = viewBtn.getAttribute('data-lead-name') || '';
            const closingText = viewBtn.getAttribute('data-closing-text') || 'No closing update recorded.';

            if (viewClosingTitle) viewClosingTitle.textContent = 'Day Closing Update Details';
            if (viewClosingSubTitle) viewClosingSubTitle.textContent = projectName + (leadName ? ' | ' + leadName : '');
            if (viewClosingContent) viewClosingContent.textContent = closingText;

            // Store attributes on shortcut edit button
            if (viewClosingEditShortcutBtn) {
                viewClosingEditShortcutBtn.onclick = function() {
                    setViewClosingModalState(false);
                    // Trigger entry edit button on the same row
                    const tr = viewBtn.closest('tr');
                    const editBtn = tr ? tr.querySelector('.open-entry-closing-btn') : null;
                    if (editBtn) {
                        editBtn.click();
                    }
                };
            }

            setViewClosingModalState(true);
            return;
        }
    });

    closeEntryClosingButtons.forEach(btn => {
        btn.addEventListener('click', () => setEntryClosingModalState(false));
    });
    if (entryClosingOverlay) {
        entryClosingOverlay.addEventListener('click', () => setEntryClosingModalState(false));
    }

    closeViewClosingButtons.forEach(btn => {
        btn.addEventListener('click', () => setViewClosingModalState(false));
    });
    if (viewClosingOverlay) {
        viewClosingOverlay.addEventListener('click', () => setViewClosingModalState(false));
    }

    if (entryClosingForm) {
        entryClosingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const updateUrl = entryClosingUpdateUrl ? entryClosingUpdateUrl.value : '';
            const newText = entryClosingTextarea ? entryClosingTextarea.value.trim() : '';

            if (!newText) {
                alert('Please enter day closing update details.');
                return;
            }

            if (entryClosingSubmitBtn) {
                entryClosingSubmitBtn.disabled = true;
                entryClosingSubmitBtn.innerHTML = '<span>Saving...</span>';
            }

            fetch(updateUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    day_closing_update: newText
                })
            })
            .then(res => res.json())
            .then(data => {
                if (entryClosingSubmitBtn) {
                    entryClosingSubmitBtn.disabled = false;
                    entryClosingSubmitBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Save Closing Update</span>';
                }

                if (data.success) {
                    setEntryClosingModalState(false);

                    const timesheetId = entryClosingTimesheetId ? entryClosingTimesheetId.value : '';
                    if (currentGroupTimesheets && timesheetId) {
                        const tsItem = currentGroupTimesheets.find(t => String(t.id) === String(timesheetId));
                        if (tsItem) {
                            tsItem.day_closing_update = newText;
                        }
                        if (currentGroupBtn) {
                            currentGroupBtn.setAttribute('data-timesheets', JSON.stringify(currentGroupTimesheets));
                        }
                    }

                    // Update active row
                    if (currentActiveRow) {
                        const cell = currentActiveRow.querySelector('td:nth-child(5)');
                        const projectName = currentActiveRow.querySelector('.pts-project')?.textContent || '';
                        const leadName = currentActiveRow.querySelector('td:nth-child(2) > div:first-child')?.textContent || '';

                        if (cell) {
                            cell.innerHTML = `
                                <div class="closing-btn-group" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <button type="button" class="pts-btn pts-btn-outline open-view-closing-btn"
                                            data-project-name="${escapeHtml(projectName)}"
                                            data-lead-name="${escapeHtml(leadName)}"
                                            data-closing-text="${escapeHtml(newText)}"
                                            data-timesheet-id="${timesheetId}"
                                            data-update-url="${updateUrl}"
                                            style="padding:5px 10px; font-size:12px; gap:5px;" title="View closing update details">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        <span>View</span>
                                    </button>
                                    <button type="button" class="pts-btn open-entry-closing-btn"
                                            data-project-name="${escapeHtml(projectName)}"
                                            data-lead-name="${escapeHtml(leadName)}"
                                            data-closing-text="${escapeHtml(newText)}"
                                            data-timesheet-id="${timesheetId}"
                                            data-update-url="${updateUrl}"
                                            style="padding:5px 9px; font-size:12px; gap:5px; background:#f8fafc; border-color:#cbd5e1;" title="Edit closing update">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        <span>Edit</span>
                                    </button>
                                </div>
                            `;
                        }

                        // Enable status dropdown on the active row
                        const statusSelect = currentActiveRow.querySelector('.modal-timesheet-status-select');
                        if (statusSelect) {
                            if (newText.trim() !== '') {
                                statusSelect.disabled = false;
                                statusSelect.setAttribute('title', 'Change status');
                            } else {
                                statusSelect.disabled = true;
                                statusSelect.setAttribute('title', 'Add Day Closing Update first to change status');
                            }
                        }
                    }
                } else {
                    alert(data.message || 'Failed to save closing update.');
                }
            })
            .catch(err => {
                if (entryClosingSubmitBtn) {
                    entryClosingSubmitBtn.disabled = false;
                    entryClosingSubmitBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Save Closing Update</span>';
                }
                console.error('Error saving closing update:', err);
                alert('An error occurred while saving.');
            });
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (entryClosingModal && entryClosingModal.classList.contains('is-open')) {
                setEntryClosingModalState(false);
                return;
            }
            if (viewClosingModal && viewClosingModal.classList.contains('is-open')) {
                setViewClosingModalState(false);
                return;
            }
            setModalState(false);
            setViewTimesheetsModalState(false);
        }
    });
});
</script>
@endpush
