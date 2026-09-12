@extends('layouts.app')

@section('title', 'Production Tasks')

@push('styles')
<style>
.pts-page { min-height:100%; background:linear-gradient(180deg,#f7fbff 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pts-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #e6edf5; }
.pts-title { font-size:24px; font-weight:900; color:#111827; }
.pts-breadcrumb { font-size:12px; color:#64748b; margin-top:4px; }
.pts-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pts-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 16px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#111827; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; transition:all 0.2s; }
.pts-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.pts-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.pts-btn-outline { border-color:#ea580c; color:#ea580c; background:#fff; }
.pts-btn-outline:hover { background:#fff7ed; }
.pts-btn-danger { background:#ef4444; border-color:#ef4444; color:#fff; }
.pts-btn-danger:hover { background:#dc2626; border-color:#dc2626; color:#fff; }
.pts-body { padding:22px 28px 34px; display:grid; gap:18px; }
.pts-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; overflow:hidden; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.pts-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; padding:18px 20px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-card-title { font-size:16px; font-weight:900; color:#111827; }
.pts-card-sub { margin-top:4px; font-size:12px; color:#64748b; }
.pts-card-body { padding:20px; }
.pts-flash { padding:12px 14px; border-radius:10px; font-size:13px; font-weight:700; }
.pts-flash.success { background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; }
.pts-flash.error { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
.pts-empty { padding:50px 20px; text-align:center; color:#64748b; font-size:13px; }
.pts-table-wrap { overflow-x:auto; }
.pts-table { width:100%; border-collapse:collapse; min-width:980px; }
.pts-table th { padding:14px 16px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; background:#f8fafc; border-bottom:1px solid #edf2f7; }
.pts-table td { padding:16px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#111827; vertical-align:middle; }
.pts-meta { margin-top:4px; font-size:11px; color:#64748b; }
.pts-project { font-weight:900; color:#0f172a; }

.pts-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; }
.pts-badge.pending { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.pts-badge.in_progress { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
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
.pts-status-select.in_progress { color:#1d4ed8; background-color:#eff6ff; border-color:#bfdbfe; }
.pts-status-select.pending { color:#b45309; background-color:#fff7ed; border-color:#fed7aa; }

.pts-filter-card { background:#fff; border:1px solid #e6edf5; border-radius:16px; overflow:hidden; display:block; box-shadow:0 10px 28px rgba(15,23,42,.04); }
.pts-filter-toggle { width:100%; display:flex; align-items:center; justify-content:space-between; gap:14px; padding:16px 20px; border:none; background:linear-gradient(180deg,#fffdfb 0%,#fff 100%); cursor:pointer; text-align:left; font-family:inherit; list-style:none; }
.pts-filter-toggle::-webkit-details-marker { display:none; }
.pts-filter-toggle:hover { background:linear-gradient(180deg,#fff7f1 0%,#fff 100%); }
.pts-filter-card[open] .pts-filter-toggle { border-bottom:1px solid #f2ede8; }
.pts-filter-toggle-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.pts-filter-title { font-size:15px; font-weight:900; color:#111827; }
.pts-filter-sub { font-size:12px; color:#7c7c7c; margin-top:2px; }
.pts-filter-pill { display:inline-flex; align-items:center; gap:6px; padding:7px 11px; border-radius:999px; background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:11px; font-weight:800; }
.pts-filter-chevron { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; border:1px solid #eee7df; background:#fff; color:#7c7c7c; transition:transform .18s ease, color .18s ease, border-color .18s ease; }
.pts-filter-card[open] .pts-filter-chevron { transform:rotate(180deg); color:#ea580c; border-color:#fed7aa; }
.pts-filter-body { padding:20px; }
.pts-filter-form { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:14px; align-items:start; }
.pts-filter-actions-row { grid-column: 1 / -1; display:flex; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #f2ede8; margin-top:4px; }
.pts-filter-group { display:grid; gap:6px; }
.pts-label { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; }
.pts-input, .pts-select { width:100%; min-height:44px; padding:10px 12px; border:1px solid #dbe2ea; border-radius:10px; background:#fff; font-size:13px; color:#111827; }
.pts-input:focus, .pts-select:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.12); }
.pts-reset-btn { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:13px; font-weight:800; text-decoration:none; }
.pts-reset-btn:hover { background:#f1f5f9; color:#0f172a; }

.pts-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.48); z-index:1200; display:none; }
.pts-modal-overlay.is-open { display:block; }
.pts-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(900px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 24px 60px rgba(15,23,42,.25); z-index:1210; display:none; }
.pts-modal.is-open { display:block; }
.pts-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:20px 24px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-modal-close { width:38px; height:38px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:16px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
.pts-modal-close:hover { background:#f1f5f9; }
.pts-modal-body { padding:24px; }

.modal-task-table { width:100%; border-collapse:collapse; min-width:760px; table-layout:auto; }
.modal-task-table th { padding:14px 16px; background:#f8fafc; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; border-bottom:1px solid #e2e8f0; text-align:left !important; vertical-align:middle; }
.modal-task-table th.th-center, .modal-task-table td.td-center { text-align:center !important; }
.modal-task-table th.th-right, .modal-task-table td.td-right { text-align:right !important; }
.modal-task-table td { padding:14px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:13px; text-align:left !important; color:#111827; }

.select2-container--default .select2-selection--single.pts-select2-selection { height:44px; border:1px solid #dbe2ea; border-radius:10px; background:#fff; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__rendered { line-height:42px; padding-left:12px; padding-right:34px; font-size:13px; color:#111827; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__arrow { height:42px; right:8px; }
.select2-dropdown { border:1px solid #dbe2ea; border-radius:10px; overflow:hidden; box-shadow:0 16px 36px rgba(15,23,42,.12); }
.select2-results__option { font-size:13px; padding:8px 12px; }

@media (max-width: 1200px) {
    .pts-filter-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .pts-topbar { padding:18px 16px; flex-direction:column; }
    .pts-body { padding:16px 12px 24px; }
    .pts-filter-form { grid-template-columns:1fr; }
    .pts-modal { width:calc(100vw - 20px); }
}
</style>
@endpush

@section('content')
<div class="pts-page">
    <div class="pts-topbar">
        <div>
            <div class="pts-title">Production Tasks</div>
            <div class="pts-breadcrumb">Projects &gt; Tasks (Assigned Directory)</div>
        </div>
        <div class="pts-actions">
            @can('tasks.create')
            <a href="{{ route('projects.tasks.create') }}" class="pts-btn pts-btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Create Task</span>
            </a>
            @endcan
        </div>
    </div>

    <div class="pts-body">
        @if(session('success'))
            <div class="pts-flash success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="pts-flash error">{{ session('error') }}</div>
        @endif

        <!-- Filter Accordion Card -->
        <details class="pts-filter-card" id="taskFiltersAccordion" @if($hasActiveFilters ?? false) open @endif>
            <summary class="pts-filter-toggle">
                <div>
                    <div class="pts-filter-title">Filter Tasks</div>
                    <div class="pts-filter-sub">Narrow results by quick dates, date range, client, project, member, or status.</div>
                </div>
                <div class="pts-filter-toggle-right">
                    <div class="pts-filter-pill">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        <span>Filter Options</span>
                        @if($hasActiveFilters ?? false)
                            <span class="pts-badge count" style="background:#ea580c; color:#fff; border:none; margin-left:4px;">Active</span>
                        @endif
                    </div>
                    <span class="pts-filter-chevron">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </summary>

            <div class="pts-filter-body">
                <form method="GET" action="{{ route('projects.tasks.index') }}" class="pts-filter-form" id="ptsTaskFilterForm">
                    {{-- 1. Quick Dates Select --}}
                    <div class="pts-filter-group">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;">
                            <label class="pts-label" for="quick_date_select" style="margin-bottom:0;">Quick Dates</label>
                            <span id="ptsQuickDateRange" style="font-size:11px;font-weight:700;color:#ea580c;"></span>
                        </div>
                        <select id="quick_date_select" name="quick_date" class="pts-select" onchange="onPtsQuickDateChange(this.value)">
                            <option value="today" {{ ($filters['quick_date'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ in_array($filters['quick_date'] ?? '', ['week', 'this_week', 'weekly'], true) ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ in_array($filters['quick_date'] ?? '', ['month', 'this_month', 'monthly'], true) ? 'selected' : '' }}>This Month</option>
                            <option value="quarter" {{ in_array($filters['quick_date'] ?? '', ['quarter', 'this_quarter', 'quarterly'], true) ? 'selected' : '' }}>This Quarter</option>
                            <option value="year" {{ in_array($filters['quick_date'] ?? '', ['year', 'this_year', 'yearly'], true) ? 'selected' : '' }}>This Year</option>
                            <option value="all" {{ ($filters['quick_date'] ?? '') === 'all' ? 'selected' : '' }}>Show All</option>
                            <option value="custom" {{ in_array($filters['quick_date'] ?? '', ['custom'], true) ? 'selected' : '' }}>Custom Dates</option>
                        </select>
                    </div>

                    {{-- 2. From Date & To Date --}}
                    @php
                        $isCustomDate = in_array($filters['quick_date'] ?? '', ['custom'], true);
                    @endphp
                    <div class="pts-filter-group" id="ptsFromField" style="display: {{ $isCustomDate ? 'grid' : 'none' }};">
                        <label class="pts-label" for="date_from">From Date</label>
                        <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="pts-input">
                    </div>

                    <div class="pts-filter-group" id="ptsToField" style="display: {{ $isCustomDate ? 'grid' : 'none' }};">
                        <label class="pts-label" for="date_to">To Date</label>
                        <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="pts-input">
                    </div>

                    {{-- 3. Lead (Client) --}}
                    <div class="pts-filter-group">
                        <label class="pts-label">Lead (Client)</label>
                        <select name="filter_lead_id" class="pts-select select2 filter-lead-select" data-placeholder="All Leads">
                            <option value="">All Leads</option>
                            @foreach($uniqueLeads as $lead)
                                <option value="{{ $lead['lead_id'] }}" @selected($filters['filter_lead_id'] === (string) $lead['lead_id'])>
                                    LD-{{ str_pad($lead['lead_id'], 4, '0', STR_PAD_LEFT) }} | {{ $lead['company_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 4. Product / Project --}}
                    <div class="pts-filter-group">
                        <label class="pts-label">Product / Project</label>
                        <select name="filter_project_id" class="pts-select select2 filter-project-select" data-placeholder="All Projects">
                            <option value="">All Projects</option>
                            @foreach($assignedProjects as $project)
                                <option value="{{ $project->id }}" @selected($filters['filter_project_id'] === (string) $project->id)>
                                    {{ $project->product_name }} | {{ $project->company_name ?: ($project->lead?->company_name ?: 'No company') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 5. Assigned Member --}}
                    <div class="pts-filter-group">
                        <label class="pts-label">Assigned Member</label>
                        <select name="filter_user_id" class="pts-select select2 filter-user-select" data-placeholder="All Members">
                            <option value="">All Members</option>
                            @foreach($mappedTeamMembers as $member)
                                <option value="{{ $member->id }}" @selected($filters['filter_user_id'] === (string) $member->id)>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 6. Status --}}
                    <div class="pts-filter-group">
                        <label class="pts-label">Status</label>
                        <select name="filter_status" class="pts-select">
                            <option value="">All Statuses</option>
                            <option value="pending" @selected($filters['filter_status'] === 'pending')>Pending</option>
                            <option value="in_progress" @selected($filters['filter_status'] === 'in_progress')>In Progress</option>
                            <option value="completed" @selected($filters['filter_status'] === 'completed')>Completed</option>
                        </select>
                    </div>

                    {{-- 7. Action buttons row --}}
                    <div class="pts-filter-actions-row">
                        <button type="submit" class="pts-btn pts-btn-primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <span>Apply Filter</span>
                        </button>
                        <a href="{{ route('projects.tasks.index') }}" class="pts-reset-btn">Reset</a>
                    </div>
                </form>
            </div>
        </details>

        <!-- Tasks Grouped Table Section -->
        <section class="pts-card">
            <div class="pts-card-head">
                <div>
                    <div class="pts-card-title">Assigned Tasks (Grouped by Member &amp; Date)</div>
                    <div class="pts-card-sub">Each row displays the allocated task list for a team member on a specific date.</div>
                </div>
                <div style="font-size:12px; font-weight:800; color:#64748b;">
                    Total: {{ $groupedTasks->total() }} Member Allocations
                </div>
            </div>

            <div class="pts-card-body" style="padding:0;">
                @if($groupedTasks->isNotEmpty())
                    <div class="pts-table-wrap">
                        <table class="pts-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Assigned To</th>
                                    <th>Allocated Lead (Client)</th>
                                    <th>Task Description</th>
                                    <th>Assigned By</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedTasks as $group)
                                    @php
                                        $firstTask = $group->first();
                                        $assignedUser = $firstTask->assignedUser;
                                        $taskDate = $firstTask->task_date;
                                        $creator = $firstTask->creator;
                                        $totalTasks = $group->count();

                                        // Prepare clean JSON representation for modal
                                        $tasksData = $group->map(function($t, $idx) {
                                            return [
                                                'id' => $t->id,
                                                'sno' => $idx + 1,
                                                'lead_id' => $t->lead_id ? 'LD-' . str_pad($t->lead_id, 4, '0', STR_PAD_LEFT) : 'N/A',
                                                'lead_company' => $t->lead?->company_name ?: ($t->project?->company_name ?: 'No Company'),
                                                'product_name' => $t->product_name ?: ($t->project?->product_name ?: 'General Task'),
                                                'task_description' => $t->task_description,
                                                'status' => $t->status,
                                                'can_delete' => auth()->user()?->hasAdminLikeRole() || auth()->user()?->can('tasks.delete') || $t->created_by === auth()->id(),
                                                'delete_url' => route('projects.tasks.destroy', $t->id),
                                            ];
                                        })->values();

                                        $uniqueCompanies = $group->map(function ($task) {
                                            return $task->lead?->company_name ?: ($task->project?->company_name ?: 'No Company');
                                        })->unique()->values();
                                    @endphp
                                    <tr data-group-row>
                                        <!-- Date -->
                                        <td>
                                            <div style="font-weight:800; color:#0f172a;">
                                                {{ optional($taskDate)->format('d M Y') ?: 'N/A' }}
                                            </div>
                                            <div class="pts-meta">{{ optional($taskDate)->format('l') }}</div>
                                        </td>

                                        <!-- Assigned To -->
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="width:36px; height:36px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; color:#ea580c; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:13px;">
                                                    {{ strtoupper(substr($assignedUser?->name ?: 'U', 0, 2)) }}
                                                </div>
                                                <div>
                                                    <div style="font-weight:800; color:#0f172a;">
                                                        {{ $assignedUser?->name ?: 'Unassigned' }}
                                                    </div>
                                                    <div class="pts-meta">{{ $assignedUser?->email }}</div>
                                                </div>
                                            </div>
                                        </td>

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

                                        <!-- Task Description with View Tasks Button -->
                                        <td>
                                            <button type="button" 
                                                    class="pts-btn pts-btn-outline open-tasks-modal-btn" 
                                                    data-user-name="{{ $assignedUser?->name ?: 'Team Member' }}"
                                                    data-task-date="{{ optional($taskDate)->format('d M Y') }}"
                                                    data-tasks='@json($tasksData)'>
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                <span>View Tasks</span>
                                                <span class="pts-badge count" style="padding:2px 7px; font-size:10px; margin-left:2px;">{{ $totalTasks }}</span>
                                            </button>
                                        </td>

                                        <!-- Assigned By -->
                                        <td>
                                            <div style="font-size:12px; font-weight:700; color:#334155;">
                                                {{ $creator?->name ?: 'System' }}
                                            </div>
                                            <div class="pts-meta">{{ optional($firstTask->created_at)->format('d M, h:i A') }}</div>
                                        </td>

                                        <!-- Actions -->
                                        <td style="text-align:right;">
                                            <button type="button" 
                                                    class="pts-btn pts-btn-primary open-tasks-modal-btn" 
                                                    style="padding:7px 12px; font-size:12px;"
                                                    data-user-name="{{ $assignedUser?->name ?: 'Team Member' }}"
                                                    data-task-date="{{ optional($taskDate)->format('d M Y') }}"
                                                    data-tasks='@json($tasksData)'
                                                    title="View task items">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                                                <span>Details</span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($groupedTasks->hasPages())
                        @include('partials.table-pagination', ['paginator' => $groupedTasks])
                    @endif
                @else
                    <div class="pts-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:12px;"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        <div style="font-weight:800; font-size:15px; color:#1e293b; margin-bottom:4px;">No Tasks Found</div>
                        <div>No tasks match your filter criteria. Click below to create a new task.</div>
                        @can('tasks.create')
                        <div style="margin-top:16px;">
                            <a href="{{ route('projects.tasks.create') }}" class="pts-btn pts-btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                <span>Create First Task</span>
                            </a>
                        </div>
                        @endcan
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>

<!-- Task List Details Modal Popup -->
<div class="pts-modal-overlay" data-tasks-modal-overlay></div>
<div class="pts-modal" data-tasks-modal>
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            </div>
            <div>
                <div class="pts-card-title" id="modalMemberName" style="font-size:17px; font-weight:900; color:#111827; line-height:1.3;">Allocated Tasks</div>
                <div class="pts-card-sub" id="modalSubTitle" style="margin-top:3px; font-size:12px; color:#64748b;">Date: N/A | Total 0 Tasks</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-tasks-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body" style="padding:0;">
        <div class="pts-table-wrap">
            <table class="modal-task-table">
                <thead>
                    <tr>
                        <th class="th-center" style="width:50px;">#</th>
                        <th style="width:220px;">Lead (Client)</th>
                        <th style="width:200px;">Product / Project</th>
                        <th>Task Description</th>
                    </tr>
                </thead>
                <tbody id="modalTasksTableBody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function calcPtsPresetDates(val) {
    const today = new Date();
    const fmt = d => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (val === 'today') {
        const str = fmt(today);
        return { from: str, to: str };
    }
    if (val === 'week') {
        const d = new Date(today);
        const day = d.getDay();
        const diffToMon = d.getDate() - day + (day === 0 ? -6 : 1);
        const monday = new Date(d.setDate(diffToMon));
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        return { from: fmt(monday), to: fmt(sunday) };
    }
    if (val === 'month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        return { from: fmt(firstDay), to: fmt(lastDay) };
    }
    if (val === 'quarter') {
        const qMonth = Math.floor(today.getMonth() / 3) * 3;
        const firstQ = new Date(today.getFullYear(), qMonth, 1);
        const lastQ = new Date(today.getFullYear(), qMonth + 3, 0);
        return { from: fmt(firstQ), to: fmt(lastQ) };
    }
    if (val === 'year') {
        return { from: `${today.getFullYear()}-01-01`, to: `${today.getFullYear()}-12-31` };
    }
    return { from: '', to: '' };
}

function formatPtsDisplayDate(dStr) {
    if (!dStr) return '';
    const parts = dStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dStr;
}

function updatePtsQuickDateRangeSpan(val) {
    const span = document.getElementById('ptsQuickDateRange');
    if (!span) return;
    if (val === 'all' || !val) {
        span.textContent = '';
        return;
    }
    if (val === 'custom') {
        const f = document.getElementById('date_from')?.value;
        const t = document.getElementById('date_to')?.value;
        span.textContent = (f && t) ? `${formatPtsDisplayDate(f)} - ${formatPtsDisplayDate(t)}` : 'Custom Range';
        return;
    }
    const dates = calcPtsPresetDates(val);
    if (dates.from && dates.to) {
        span.textContent = `${formatPtsDisplayDate(dates.from)} - ${formatPtsDisplayDate(dates.to)}`;
    } else {
        span.textContent = '';
    }
}

function onPtsQuickDateChange(val) {
    const fromField = document.getElementById('ptsFromField');
    const toField = document.getElementById('ptsToField');
    const f = document.getElementById('date_from');
    const t = document.getElementById('date_to');

    if (val === 'custom') {
        if (fromField) fromField.style.display = 'grid';
        if (toField) toField.style.display = 'grid';
    } else {
        if (fromField) fromField.style.display = 'none';
        if (toField) toField.style.display = 'none';
        if (val === 'all') {
            if (f) f.value = '';
            if (t) t.value = '';
        } else {
            const dates = calcPtsPresetDates(val);
            if (f) f.value = dates.from;
            if (t) t.value = dates.to;
        }
    }
    updatePtsQuickDateRangeSpan(val);
}

document.addEventListener('DOMContentLoaded', function () {
    const qSelect = document.getElementById('quick_date_select');
    const f = document.getElementById('date_from');
    const t = document.getElementById('date_to');
    const fromField = document.getElementById('ptsFromField');
    const toField = document.getElementById('ptsToField');

    function handleCustomDateInput() {
        if (qSelect && qSelect.value !== 'custom') {
            qSelect.value = 'custom';
        }
        if (fromField) fromField.style.display = 'grid';
        if (toField) toField.style.display = 'grid';
        updatePtsQuickDateRangeSpan('custom');
    }

    if (f) f.addEventListener('change', handleCustomDateInput);
    if (t) t.addEventListener('change', handleCustomDateInput);

    if (qSelect) {
        updatePtsQuickDateRangeSpan(qSelect.value);
    }
    // Select2 Init
    if (window.jQuery && window.jQuery.fn.select2) {
        window.jQuery('.select2').each(function () {
            const $this = window.jQuery(this);
            $this.select2({
                width: '100%',
                placeholder: $this.data('placeholder') || 'Select option',
                allowClear: true
            });
            $this.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        });
    }

    // Tasks Modal Logic
    const modal = document.querySelector('[data-tasks-modal]');
    const overlay = document.querySelector('[data-tasks-modal-overlay]');
    const closeBtn = document.querySelector('[data-close-tasks-modal]');
    const modalMemberName = document.getElementById('modalMemberName');
    const modalSubTitle = document.getElementById('modalSubTitle');
    const tableBody = document.getElementById('modalTasksTableBody');

    function setModalOpen(open) {
        if (!modal || !overlay) return;
        modal.classList.toggle('is-open', open);
        overlay.classList.toggle('is-open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }

    function renderTasksInModal(userName, dateStr, tasks) {
        if (modalMemberName) {
            modalMemberName.textContent = `Allocated Tasks: ${userName}`;
        }
        if (modalSubTitle) {
            modalSubTitle.textContent = `Date: ${dateStr} | Total ${tasks.length} Task(s)`;
        }

        tableBody.innerHTML = '';
        if (!tasks || tasks.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px; color:#64748b;">No tasks found.</td></tr>';
            return;
        }

        tasks.forEach((task, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="td-center">
                    <span style="font-weight:800; color:#64748b;">${idx + 1}</span>
                </td>
                <td>
                    <div style="font-weight:800; color:#0f172a;">${escapeHtml(task.lead_company)}</div>
                    <div class="pts-meta">${escapeHtml(task.lead_id)}</div>
                </td>
                <td>
                    <div style="font-weight:800; color:#ea580c;">${escapeHtml(task.product_name)}</div>
                </td>
                <td>
                    <div style="white-space:pre-wrap; line-height:1.55; color:#334155; font-size:13px;">${escapeHtml(task.task_description)}</div>
                </td>
            `;
            tableBody.appendChild(tr);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.querySelectorAll('.open-tasks-modal-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const userName = this.getAttribute('data-user-name');
            const taskDate = this.getAttribute('data-task-date');
            let tasks = [];
            try {
                tasks = JSON.parse(this.getAttribute('data-tasks') || '[]');
            } catch (e) {
                console.error('Failed to parse tasks JSON', e);
            }

            renderTasksInModal(userName, taskDate, tasks);
            setModalOpen(true);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', () => setModalOpen(false));
    if (overlay) overlay.addEventListener('click', () => setModalOpen(false));

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setModalOpen(false);
    });
});
</script>
@endpush
