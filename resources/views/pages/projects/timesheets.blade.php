@extends('layouts.app')

@section('title', 'Project Timesheets')

@push('styles')
<style>
.pts-page { min-height:100%; background:linear-gradient(180deg,#f7fbff 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pts-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #e6edf5; }
.pts-title { font-size:24px; font-weight:900; color:#111827; }
.pts-breadcrumb { font-size:12px; color:#64748b; margin-top:4px; }
.pts-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pts-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#111827; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; }
.pts-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
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
.pts-table th { padding:12px 14px; text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; background:#f8fafc; border-bottom:1px solid #edf2f7; }
.pts-table td { padding:14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#111827; vertical-align:top; }
.pts-meta { margin-top:4px; font-size:11px; color:#64748b; }
.pts-project { font-weight:900; color:#0f172a; }
.pts-update-text { line-height:1.65; color:#334155; max-width:520px; }
.pts-pill { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid #fed7aa; color:#c2410c; background:#fff7ed; }
.pts-status { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid transparent; }
.pts-status.completed { color:#15803d; background:#f0fdf4; border-color:#bbf7d0; }
.pts-status.pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.pts-status-select {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
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
}
.pts-status-select.completed { color:#15803d; background-color:#f0fdf4; border-color:#bbf7d0; }
.pts-status-select.pending { color:#b45309; background-color:#fff7ed; border-color:#fed7aa; }
.pts-filter-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; padding:16px; box-shadow:0 10px 28px rgba(15,23,42,.04); }
.pts-filter-form { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; align-items:end; }
.pts-filter-group { display:grid; gap:7px; }
.pts-filter-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.pts-reset-btn { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:13px; font-weight:800; text-decoration:none; }
.pts-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.42); z-index:1200; display:none; }
.pts-modal-overlay.is-open { display:block; }
.pts-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(1000px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 24px 60px rgba(15,23,42,.22); z-index:1210; display:none; }
.pts-modal.is-open { display:block; }
.pts-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-modal-close { width:40px; height:40px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:16px; cursor:pointer; }
.pts-modal-body { padding:20px; }
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
            <button type="button" class="pts-btn pts-btn-primary" data-open-timesheet-modal>Add Timesheet</button>
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
                    <label class="pts-label">Date</label>
                    <input type="date" name="filter_date" value="{{ $timesheetFilters['filter_date'] ?? '' }}" class="pts-input">
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
                        <option value="completed" @selected(($timesheetFilters['filter_status'] ?? '') === 'completed')>Completed</option>
                    </select>
                </div>

                @if(($isAdminLike ?? false) && $departments->isNotEmpty())
                    <div class="pts-filter-group">
                        <label class="pts-label">Department</label>
                        <select name="filter_department_id" class="pts-select">
                            <option value="">All Departments</option>
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

        <section class="pts-card">
            <div class="pts-card-head">
                <div>
                    <div class="pts-card-title">Saved Timesheets</div>
                    <div class="pts-card-sub">Your submitted day closing updates are listed here.</div>
                </div>
                <span class="pts-pill">{{ $timesheets->count() }} Entries</span>
            </div>
            <div class="pts-card-body" style="padding:0;">
                @if($timesheets->isNotEmpty())
                    <div class="pts-table-wrap">
                        <table class="pts-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    @if(($isAdminLike ?? false) || ($canViewTeamTimesheets ?? false))
                                        <th>Employee</th>
                                    @endif
                                    <th>Project</th>
                                    <th>Delivery Date</th>
                                    <th>Status</th>
                                    @if(auth()->user()?->belongsToDesigningDepartment())
                                        <th>Committed P/V</th>
                                        <th>Waiting P/V</th>
                                    @endif
                                    <th>Posters</th>
                                    <th>Videos</th>
                                    <th>Day Closing Update</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timesheets as $timesheet)
                                    @php
                                        $statusVal = strtolower($timesheet->status ?? 'pending');
                                        $statusClass = $statusVal === 'completed' ? 'completed' : 'pending';
                                        $statusLabel = ucfirst($statusVal);
                                    @endphp
                                    <tr>
                                        <td>{{ optional($timesheet->timesheet_date)->format('d M Y') ?: 'No date' }}</td>
                                        @if(($isAdminLike ?? false) || ($canViewTeamTimesheets ?? false))
                                            <td>
                                                <div style="font-weight:700;">{{ $timesheet->user?->name ?? 'Unknown' }}</div>
                                                <div class="pts-meta">{{ $timesheet->user?->designation ?? '' }}</div>
                                            </td>
                                        @endif
                                        <td>
                                            <div class="pts-project">{{ $timesheet->project?->product_name ?: 'Project removed' }}</div>
                                            <div class="pts-meta">{{ $timesheet->project?->company_name ?: ($timesheet->project?->lead?->company_name ?: 'No company') }}</div>
                                        </td>
                                        <td>{{ optional($timesheet->project_delivery_date)->format('d M Y') ?: 'Not available' }}</td>
                                         <td>
                                             <form method="POST" action="{{ route('projects.timesheets.update-status', $timesheet->id) }}" style="display:inline;">
                                                 @csrf
                                                 @method('PATCH')
                                                 <select name="status" onchange="this.form.submit()" class="pts-status-select {{ $statusClass }}">
                                                     <option value="pending" @selected($statusVal === 'pending')>Pending</option>
                                                     <option value="completed" @selected($statusVal === 'completed')>Completed</option>
                                                 </select>
                                             </form>
                                         </td>
                                        @if(auth()->user()?->belongsToDesigningDepartment())
                                            <td style="font-weight:600;">P: {{ (int) $timesheet->committed_posters }} / V: {{ (int) $timesheet->committed_videos }}</td>
                                            <td style="font-weight:600; color:#ea580c;">P: {{ (int) $timesheet->waiting_posters }} / V: {{ (int) $timesheet->waiting_videos }}</td>
                                        @endif
                                        @php
                                            $deptName = $timesheet->project?->department?->name ? strtolower($timesheet->project->department->name) : '';
                                            $isDesignOrDm = str_contains($deptName, 'design') || str_contains($deptName, 'dm') || str_contains($deptName, 'digital marketing');
                                        @endphp
                                        <td>{{ $isDesignOrDm ? (int) $timesheet->poster_count : '—' }}</td>
                                        <td>{{ $isDesignOrDm ? (int) $timesheet->video_count : '—' }}</td>
                                        <td>
                                            @if(strlen($timesheet->day_closing_update) > 15)
                                                @php
                                                    $previewText = str_replace(["\r\n", "\r", "\n"], ' ', $timesheet->day_closing_update);
                                                    $previewText = preg_replace('/\s+/', ' ', $previewText);
                                                @endphp
                                                <div class="pts-update-text view-closing-update"
                                                     style="display: inline-block; cursor: pointer; color: #ea580c; font-weight: 600; text-decoration: underline;"
                                                     data-project-name="{{ $timesheet->project?->product_name ?: 'Project removed' }}"
                                                     data-timesheet-date="{{ optional($timesheet->timesheet_date)->format('d M Y') }}">
                                                    {{ \Illuminate\Support\Str::limit($previewText, 15, '...') }}
                                                </div>
                                                <div class="full-update-text" style="display: none;">{{ $timesheet->day_closing_update }}</div>
                                            @else
                                                <div class="pts-update-text">{{ $timesheet->day_closing_update }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ optional($timesheet->created_at)->format('d M Y h:i A') ?: 'Not available' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pts-empty">No timesheets submitted yet. Use Add Timesheet to enter today&apos;s day closing update.</div>
                @endif
            </div>
        </section>
    </div>
</div>

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

{{-- Day Closing Update Full Text Modal --}}
<div class="pts-modal-overlay" data-update-text-modal-overlay></div>
<div class="pts-modal" data-update-text-modal style="max-width: 600px;">
    <div class="pts-modal-head">
        <div>
            <div class="pts-card-title">Day Closing Update Details</div>
            <div class="pts-card-sub" id="updateTextModalProjectDate">Project Name - Date</div>
        </div>
        <button type="button" class="pts-modal-close" data-close-update-text-modal aria-label="Close modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="pts-modal-body">
        <div id="updateTextModalContent" style="white-space: pre-wrap; line-height: 1.65; color: #1e293b; font-size: 14px; background: #f8fafc; padding: 18px; border-radius: 10px; border: 1px solid #e2e8f0; max-height: 400px; overflow-y: auto;">
        </div>
        <div class="pts-actions" style="margin-top: 18px; justify-content: flex-end;">
            <button type="button" class="pts-btn" data-close-update-text-modal>Close</button>
        </div>
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

    // Day Closing Update details modal logic
    const updateTextModal = document.querySelector('[data-update-text-modal]');
    const updateTextOverlay = document.querySelector('[data-update-text-modal-overlay]');
    const viewButtons = document.querySelectorAll('.view-closing-update');
    const closeTextButtons = document.querySelectorAll('[data-close-update-text-modal]');
    const modalProjectDate = document.getElementById('updateTextModalProjectDate');
    const modalContent = document.getElementById('updateTextModalContent');

    function setUpdateTextModalState(isOpen) {
        if (!updateTextModal || !updateTextOverlay) {
            return;
        }
        updateTextModal.classList.toggle('is-open', isOpen);
        updateTextOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    viewButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const td = this.closest('td');
            const hiddenDiv = td.querySelector('.full-update-text');
            const fullText = hiddenDiv ? hiddenDiv.textContent : '';
            const projectName = this.getAttribute('data-project-name');
            const dateStr = this.getAttribute('data-timesheet-date');

            modalProjectDate.textContent = projectName + ' - ' + dateStr;
            modalContent.textContent = fullText;

            setUpdateTextModalState(true);
        });
    });

    closeTextButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setUpdateTextModalState(false);
        });
    });

    if (updateTextOverlay) {
        updateTextOverlay.addEventListener('click', function () {
            setUpdateTextModalState(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setModalState(false);
            setUpdateTextModalState(false);
        }
    });
});
</script>
@endpush
