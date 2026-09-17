{{-- Employee-wise Timesheet & Tasks Component --}}
@php
    $ett = $employeeTimesheetTasks ?? null;
    $ettEmployees = $ett['employees'] ?? collect();
    $ettSummary = $ett['summary'] ?? [
        'total_employees' => 0,
        'submitted_count' => 0,
        'not_submitted_count' => 0,
        'total_tasks' => 0,
        'completed_tasks' => 0,
        'pending_tasks' => 0,
    ];
    $ettDateLabel = $ett['date_range']['label'] ?? 'Current Period';
    $ettDeptLabel = $ett['department_label'] ?? 'Department';
    $ettIsTl = $ett['is_tl'] ?? false;
    $ettIsAdmin = $ett['is_admin'] ?? false;
    $ettIsContributor = $ett['is_contributor'] ?? false;

    // Theme palette adaptive to department
    $deptLower = strtolower($ettDeptLabel);
    $themeColor = match(true) {
        str_contains($deptLower, 'test') || str_contains($deptLower, 'qa') => '#0284c7',
        str_contains($deptLower, 'design') => '#7c3aed',
        str_contains($deptLower, 'digital') || str_contains($deptLower, 'marketing') || str_contains($deptLower, 'dm') => '#059669',
        default => '#ea580c',
    };
    $themeBgLight = match(true) {
        str_contains($deptLower, 'test') || str_contains($deptLower, 'qa') => '#f0f9ff',
        str_contains($deptLower, 'design') => '#f5f3ff',
        str_contains($deptLower, 'digital') || str_contains($deptLower, 'marketing') || str_contains($deptLower, 'dm') => '#ecfdf5',
        default => '#fff7ed',
    };
    $themeBorder = match(true) {
        str_contains($deptLower, 'test') || str_contains($deptLower, 'qa') => '#bae6fd',
        str_contains($deptLower, 'design') => '#ddd6fe',
        str_contains($deptLower, 'digital') || str_contains($deptLower, 'marketing') || str_contains($deptLower, 'dm') => '#a7f3d0',
        default => '#fed7aa',
    };
    $themeIconBg = match(true) {
        str_contains($deptLower, 'test') || str_contains($deptLower, 'qa') => '#e0f2fe',
        str_contains($deptLower, 'design') => '#ede9fe',
        str_contains($deptLower, 'digital') || str_contains($deptLower, 'marketing') || str_contains($deptLower, 'dm') => '#d1fae5',
        default => '#ffedd5',
    };
@endphp

<style>
/* Self-contained modern styling for Employee Timesheet & Tasks component */
#employeeTimesheetTasksSection {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    margin-top: 24px;
    font-family: inherit;
}

#employeeTimesheetTasksSection .ett-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding: 20px 26px;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
}

#employeeTimesheetTasksSection .ett-card-title {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.01em;
}

#employeeTimesheetTasksSection .ett-card-sub {
    margin-top: 4px;
    font-size: 12.5px;
    color: #64748b;
    font-weight: 500;
}

#employeeTimesheetTasksSection .ett-search-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    padding: 8px 14px;
    transition: all 0.2s ease;
}

#employeeTimesheetTasksSection .ett-search-box:focus-within {
    border-color: {{ $themeColor }};
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
}

#employeeTimesheetTasksSection .ett-search-box input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    width: 210px;
}

#employeeTimesheetTasksSection .ett-search-box input::placeholder {
    color: #94a3b8;
}

#employeeTimesheetTasksSection .ett-highlight-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    background: {{ $themeBgLight }};
    border: 1px solid {{ $themeBorder }};
    color: {{ $themeColor }};
}

/* KPI Summary Cards Grid */
#employeeTimesheetTasksSection .ett-stats-grid {
    padding: 16px 26px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
}

#employeeTimesheetTasksSection .ett-stat-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
    transition: all 0.2s ease;
}

#employeeTimesheetTasksSection .ett-stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    border-color: #cbd5e1;
}

#employeeTimesheetTasksSection .ett-stat-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #64748b;
}

#employeeTimesheetTasksSection .ett-stat-num {
    font-size: 22px;
    font-weight: 900;
    margin-top: 2px;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

#employeeTimesheetTasksSection .ett-stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}

/* Table */
#employeeTimesheetTasksSection .ett-table-wrap {
    overflow-x: auto;
    width: 100%;
}

#employeeTimesheetTasksSection .ett-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    min-width: 900px;
}

#employeeTimesheetTasksSection .ett-table th {
    padding: 15px 22px;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

#employeeTimesheetTasksSection .ett-table td {
    padding: 16px 22px;
    font-size: 13px;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    transition: background 0.15s ease;
}

#employeeTimesheetTasksSection .ett-table tbody tr:last-child td {
    border-bottom: none;
}

#employeeTimesheetTasksSection .ett-table tbody tr:hover td {
    background: #f0f9ff;
}

#employeeTimesheetTasksSection .ett-avatar {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: {{ $themeBgLight }};
    border: 1.5px solid {{ $themeBorder }};
    color: {{ $themeColor }};
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 13px;
    flex-shrink: 0;
}

#employeeTimesheetTasksSection .ett-proj-pill {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 8px;
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    max-width: 190px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

#employeeTimesheetTasksSection .ett-btn-view {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    color: #0f172a;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}

#employeeTimesheetTasksSection .ett-btn-view:hover {
    background: {{ $themeColor }};
    border-color: {{ $themeColor }};
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
}

#employeeTimesheetTasksSection .ett-empty {
    padding: 48px 24px;
    text-align: center;
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
}

/* Modal Window */
.pjd-update-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 9998;
    display: none;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.pjd-update-modal-overlay.is-open {
    display: block;
    opacity: 1;
}

.pjd-update-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.96);
    width: min(880px, calc(100vw - 32px));
    max-height: calc(100vh - 56px);
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
    z-index: 9999;
    display: none;
    flex-direction: column;
    overflow: hidden;
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
    opacity: 0;
}

.pjd-update-modal.is-open {
    display: flex;
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
}

.pjd-update-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 26px;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
}

.pjd-update-modal-body {
    padding: 24px 26px;
    display: grid;
    gap: 20px;
    overflow-y: auto;
    max-height: calc(100vh - 160px);
}

.pjd-update-modal-close {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.pjd-update-modal-close:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #ef4444;
}
</style>

<section class="pjd-card" id="employeeTimesheetTasksSection">
    {{-- Card Header --}}
    <div class="ett-card-head pjd-card-head">
        <div>
            <div class="ett-card-title pjd-card-title">
                <div style="width:34px; height:34px; border-radius:10px; background:{{ $themeIconBg }}; color:{{ $themeColor }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <span>Timesheet &amp; Tasks (Employee-Wise)</span>
            </div>
            <div class="ett-card-sub pjd-card-sub">
                @if($ettIsContributor)
                    Your assigned tasks and submitted day closing updates for {{ $ettDateLabel }}.
                @elseif($ettIsTl)
                    Team members' task status and submitted timesheet updates for {{ $ettDeptLabel }} ({{ $ettDateLabel }}).
                @else
                    All {{ $ettDeptLabel }} employees' task allocations and timesheet logs ({{ $ettDateLabel }}).
                @endif
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            @if($ettEmployees->isNotEmpty() && !$ettIsContributor)
                <div class="ett-search-box">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text"
                           placeholder="Search team member or project..."
                           onkeyup="pjdFilterEmpRows(this.value)">
                </div>
            @endif
            <span class="ett-highlight-pill pjd-highlight">
                <span>👥</span>
                <span>{{ $ettSummary['total_employees'] }} {{ $ettIsContributor ? 'You' : ($ettSummary['total_employees'] === 1 ? 'Member' : 'Team Members') }}</span>
            </span>
        </div>
    </div>

    {{-- KPI Summary Stats --}}
    <div class="ett-stats-grid">
        {{-- Card 1: Total Members --}}
        <div class="ett-stat-box">
            <div>
                <div class="ett-stat-label">
                    {{ $ettIsContributor ? 'Active Member' : 'Total Members' }}
                </div>
                <div class="ett-stat-num" style="color: #0f172a;">{{ $ettSummary['total_employees'] }}</div>
            </div>
            <div class="ett-stat-icon" style="background:#f1f5f9; color:#475569;">
                👥
            </div>
        </div>

        {{-- Card 2: Timesheets Submitted --}}
        <div class="ett-stat-box">
            <div>
                <div class="ett-stat-label">Timesheets Submitted</div>
                <div class="ett-stat-num" style="color: #15803d;">
                    {{ $ettSummary['submitted_count'] }}
                    <span style="font-size: 13px; font-weight: 600; color: #64748b;">/ {{ $ettSummary['total_employees'] }}</span>
                </div>
            </div>
            <div class="ett-stat-icon" style="background:#ecfdf5; border:1px solid #bbf7d0; color:#15803d;">
                ✓
            </div>
        </div>

        {{-- Card 3: Pending Submissions --}}
        <div class="ett-stat-box">
            <div>
                <div class="ett-stat-label">Pending Submissions</div>
                <div class="ett-stat-num" style="color: {{ $ettSummary['not_submitted_count'] > 0 ? '#ea580c' : '#64748b' }};">
                    {{ $ettSummary['not_submitted_count'] }}
                </div>
            </div>
            <div class="ett-stat-icon" style="background:{{ $ettSummary['not_submitted_count'] > 0 ? '#fff7ed' : '#f8fafc' }}; border:1px solid {{ $ettSummary['not_submitted_count'] > 0 ? '#fed7aa' : '#e2e8f0' }}; color:{{ $ettSummary['not_submitted_count'] > 0 ? '#ea580c' : '#64748b' }};">
                ⏳
            </div>
        </div>

        {{-- Card 4: Assigned Tasks --}}
        <div class="ett-stat-box">
            <div>
                <div class="ett-stat-label">Assigned Tasks</div>
                <div class="ett-stat-num" style="color: #0f172a;">
                    {{ $ettSummary['total_tasks'] }}
                    <span style="font-size: 12px; font-weight: 700; color: #15803d; margin-left: 4px;">({{ $ettSummary['completed_tasks'] }} Done)</span>
                </div>
            </div>
            <div class="ett-stat-icon" style="background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8;">
                📋
            </div>
        </div>
    </div>

    {{-- Employee-wise Table --}}
    <div class="pjd-card-body" style="padding:0;">
        @if($ettEmployees->isNotEmpty())
            <div class="ett-table-wrap pjd-table-wrap">
                <table class="ett-table pjd-table" id="pjdEmpTasksTable">
                    <thead>
                        <tr>
                            <th style="width:23%;">Employee</th>
                            <th style="width:21%;">Allocated Projects</th>
                            <th style="width:23%;">Assigned Tasks</th>
                            <th style="width:23%;">Timesheet (Day Closing)</th>
                            <th style="width:10%; text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ettEmployees as $emp)
                            @php
                                $statusClass = match($emp['status']) {
                                    'completed' => 'background:#ecfdf5; color:#15803d; border-color:#a7f3d0;',
                                    'ongoing'   => 'background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;',
                                    'pending'   => 'background:#fff7ed; color:#b45309; border-color:#fed7aa;',
                                    default     => 'background:#f8fafc; color:#64748b; border-color:#e2e8f0;',
                                };
                                $statusDot = match($emp['status']) {
                                    'completed' => '●',
                                    'ongoing'   => '●',
                                    'pending'   => '●',
                                    default     => '○',
                                };
                                $statusLabel = match($emp['status']) {
                                    'completed' => 'Completed',
                                    'ongoing'   => 'Ongoing',
                                    'pending'   => 'Pending',
                                    default     => 'Not Submitted',
                                };
                            @endphp
                            <tr class="pjd-emp-row ett-emp-row" data-emp-name="{{ strtolower($emp['name']) }}" data-emp-projects="{{ strtolower(implode(' ', $emp['projects'])) }}">
                                {{-- 1. Employee Column --}}
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div class="ett-avatar">
                                            {{ $emp['initials'] }}
                                        </div>
                                        <div>
                                            <div style="font-weight:800; color:#0f172a; font-size:13.5px; display:flex; align-items:center; gap:6px;">
                                                <span>{{ $emp['name'] }}</span>
                                                @if($emp['is_current_user'])
                                                    <span style="font-size:10px; font-weight:800; padding:1px 7px; border-radius:6px; background:{{ $themeColor }}; color:#ffffff;">You</span>
                                                @endif
                                            </div>
                                            <div class="pjd-meta" style="margin-top:2px; font-size:11.5px; color:#64748b; font-weight:500;">
                                                {{ $emp['designation'] }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- 2. Allocated Projects Column --}}
                                <td>
                                    @if(!empty($emp['projects']))
                                        <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                            @foreach(array_slice($emp['projects'], 0, 3) as $projName)
                                                <span class="ett-proj-pill" title="{{ $projName }}">
                                                    {{ $projName }}
                                                </span>
                                            @endforeach
                                            @if(count($emp['projects']) > 3)
                                                <span style="font-size:11px; font-weight:800; color:#64748b; padding:2px 4px;">
                                                    +{{ count($emp['projects']) - 3 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span style="font-size:12.5px; color:#94a3b8; font-style:italic;">No allocated project</span>
                                    @endif
                                </td>

                                {{-- 3. Assigned Tasks Column --}}
                                <td>
                                    @if($emp['total_tasks'] > 0)
                                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:5px;">
                                            <span style="font-size:11px; font-weight:800; padding:3px 9px; border-radius:999px; background:#f8fafc; border:1px solid #cbd5e1; color:#334155;">
                                                {{ $emp['total_tasks'] }} {{ Str::plural('Task', $emp['total_tasks']) }}
                                            </span>
                                            @if($emp['completed_tasks'] > 0)
                                                <span style="font-size:11px; font-weight:700; color:#15803d;">
                                                    ✓ {{ $emp['completed_tasks'] }} Done
                                                </span>
                                            @endif
                                            @if($emp['pending_tasks'] > 0)
                                                <span style="font-size:11px; font-weight:700; color:#ea580c;">
                                                    ⏳ {{ $emp['pending_tasks'] }} Pending
                                                </span>
                                            @endif
                                        </div>
                                        <div style="display:flex; flex-direction:column; gap:3px;">
                                            @foreach(array_slice($emp['tasks'], 0, 2) as $taskItem)
                                                <div style="font-size:12px; color:#475569; display:flex; align-items:center; gap:6px;" title="{{ $taskItem['task_description'] }}">
                                                    @if($taskItem['status'] === 'completed')
                                                        <span style="display:inline-flex; align-items:center; justify-content:center; width:15px; height:15px; border-radius:50%; background:#dcfce7; color:#15803d; font-size:10px; font-weight:900; flex-shrink:0;">✓</span>
                                                    @else
                                                        <span style="display:inline-flex; align-items:center; justify-content:center; width:15px; height:15px; border-radius:50%; background:#ffedd5; color:#ea580c; font-size:9px; font-weight:900; flex-shrink:0;">•</span>
                                                    @endif
                                                    <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:210px; font-weight:500;">
                                                        {{ $taskItem['task_description'] }}
                                                    </span>
                                                </div>
                                            @endforeach
                                            @if(count($emp['tasks']) > 2)
                                                <span style="font-size:10.5px; color:#64748b; font-weight:600; margin-left:21px;">
                                                    +{{ count($emp['tasks']) - 2 }} more task(s)
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span style="font-size:12.5px; color:#94a3b8; font-style:italic;">No tasks assigned</span>
                                    @endif
                                </td>

                                {{-- 4. Timesheet / Day Closing Column --}}
                                <td>
                                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:5px; flex-wrap:wrap;">
                                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid; {{ $statusClass }}">
                                            <span>{{ $statusDot }}</span>
                                            <span>{{ $statusLabel }}</span>
                                        </span>
                                        @if($emp['total_timesheets'] > 0)
                                            <span style="font-size:11px; color:#64748b; font-weight:600;">
                                                ({{ $emp['total_timesheets'] }} {{ Str::plural('log', $emp['total_timesheets']) }})
                                            </span>
                                        @endif
                                        @if($emp['total_posters'] > 0 || $emp['total_videos'] > 0)
                                            <span style="font-size:10.5px; font-weight:700; padding:1px 6px; border-radius:6px; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa;">
                                                P: {{ $emp['total_posters'] }} | V: {{ $emp['total_videos'] }}
                                            </span>
                                        @endif
                                    </div>
                                    @if(!empty($emp['latest_update']))
                                        <div style="font-size:12px; color:#334155; line-height:1.45; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:6px 10px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;" title="{{ $emp['latest_update'] }}">
                                            {{ $emp['latest_update'] }}
                                        </div>
                                        @if($emp['latest_date'])
                                            <div style="font-size:10.5px; color:#94a3b8; margin-top:3px; display:flex; align-items:center; gap:4px;">
                                                <span>🕒</span>
                                                <span>Updated: {{ $emp['latest_date'] }}</span>
                                            </div>
                                        @endif
                                    @else
                                        <div style="font-size:12px; color:#94a3b8; font-style:italic;">
                                            No day closing update recorded
                                        </div>
                                    @endif
                                </td>

                                {{-- 5. Action Column --}}
                                <td style="text-align:center;">
                                    <button type="button"
                                            class="ett-btn-view pjd-btn"
                                            onclick='openPjdEmpDetailsModal(@json($emp))'>
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span>Details</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($ettEmployees->count() > 8)
                <div id="ettEmpPaginationFooter" class="tjd-pagination-footer" style="padding:14px 22px; border-top:1px solid #e2e8f0; background:#ffffff; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                    <div id="ettEmpPaginationInfo" style="font-size:12.5px; font-weight:600; color:#64748b;">
                        Showing <strong id="ettEmpStart">1</strong> to <strong id="ettEmpEnd">8</strong> of <strong id="ettEmpTotal">{{ $ettEmployees->count() }}</strong> members
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <button type="button" id="ettEmpPrevBtn" class="tjd-page-btn" onclick="pjdChangeEmpPage(-1)" style="border:1px solid #e2e8f0; background:#f8fafc; cursor:pointer; width:34px; height:34px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <span id="ettEmpPageIndicator" style="font-size:12px; font-weight:700; color:#1e293b; padding:0 8px;">Page 1</span>
                        <button type="button" id="ettEmpNextBtn" class="tjd-page-btn" onclick="pjdChangeEmpPage(1)" style="border:1px solid #e2e8f0; background:#f8fafc; cursor:pointer; width:34px; height:34px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                </div>
            @endif
        @else
            <div class="ett-empty pjd-empty">
                <div style="font-size:32px; margin-bottom:8px;">👥</div>
                <div style="font-weight:700; color:#1e293b;">No employees found for current dashboard scope</div>
                <div style="font-size:12.5px; color:#94a3b8; margin-top:2px;">Allocated team members will be displayed here.</div>
            </div>
        @endif
    </div>
</section>

{{-- Employee Details Modal --}}
<div id="pjdEmpDetailsModalOverlay" class="pjd-update-modal-overlay" onclick="closePjdEmpDetailsModal()"></div>
<div id="pjdEmpDetailsModal" class="pjd-update-modal" role="dialog" aria-modal="true">
    <div class="pjd-update-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div id="mEmpAvatar" class="ett-avatar" style="width:44px; height:44px; font-size:15px;">
                EM
            </div>
            <div>
                <div id="mEmpName" style="font-size:17px; font-weight:800; color:#0f172a;">Employee Name</div>
                <div id="mEmpRole" style="font-size:12.5px; color:#64748b; font-weight:500;">Designation</div>
            </div>
        </div>
        <button type="button" class="pjd-update-modal-close" onclick="closePjdEmpDetailsModal()" aria-label="Close modal">
            ✕
        </button>
    </div>

    <div class="pjd-update-modal-body">
        {{-- Subsection 1: Assigned Tasks --}}
        <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <div style="font-size:13px; font-weight:800; color:#1e293b; text-transform:uppercase; letter-spacing:.05em; display:flex; align-items:center; gap:6px;">
                    <span>📋 Assigned Tasks</span>
                    <span id="mTaskBadge" style="font-size:11px; font-weight:800; padding:2px 8px; border-radius:999px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;">0</span>
                </div>
            </div>
            <div id="mTasksContainer" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; max-height:240px; overflow-y:auto; overflow-x:auto;">
                {{-- Injected dynamically --}}
            </div>
        </div>

        {{-- Subsection 2: Submitted Timesheet Entries --}}
        <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <div style="font-size:13px; font-weight:800; color:#1e293b; text-transform:uppercase; letter-spacing:.05em; display:flex; align-items:center; gap:6px;">
                    <span>⏱️ Timesheet &amp; Day Closing Logs</span>
                    <span id="mTimesheetBadge" style="font-size:11px; font-weight:800; padding:2px 8px; border-radius:999px; background:#ecfdf5; color:#15803d; border:1px solid #bbf7d0;">0</span>
                </div>
            </div>
            <div id="mTimesheetsContainer" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; max-height:280px; overflow-y:auto; overflow-x:auto;">
                {{-- Injected dynamically --}}
            </div>
        </div>
    </div>
</div>

<script>
let ettCurrentPage = 1;
const ettPageSize = 8;

function pjdInitEmpPagination() {
    const rows = Array.from(document.querySelectorAll('.pjd-emp-row, .ett-emp-row'));
    if (!rows.length) return;

    const visibleRows = rows.filter(r => r.getAttribute('data-filtered-out') !== 'true');
    const total = visibleRows.length;
    const totalPages = Math.max(1, Math.ceil(total / ettPageSize));
    if (ettCurrentPage > totalPages) ettCurrentPage = totalPages;
    if (ettCurrentPage < 1) ettCurrentPage = 1;

    const startIdx = (ettCurrentPage - 1) * ettPageSize;
    const endIdx = startIdx + ettPageSize;

    visibleRows.forEach((r, idx) => {
        if (idx >= startIdx && idx < endIdx) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });

    const startEl = document.getElementById('ettEmpStart');
    const endEl = document.getElementById('ettEmpEnd');
    const totalEl = document.getElementById('ettEmpTotal');
    const pageInd = document.getElementById('ettEmpPageIndicator');
    const prevBtn = document.getElementById('ettEmpPrevBtn');
    const nextBtn = document.getElementById('ettEmpNextBtn');

    if (startEl) startEl.textContent = total > 0 ? (startIdx + 1) : 0;
    if (endEl) endEl.textContent = Math.min(endIdx, total);
    if (totalEl) totalEl.textContent = total;
    if (pageInd) pageInd.textContent = `Page ${ettCurrentPage} of ${totalPages}`;

    if (prevBtn) {
        if (ettCurrentPage <= 1) {
            prevBtn.classList.add('is-disabled');
            prevBtn.disabled = true;
            prevBtn.style.opacity = '0.45';
        } else {
            prevBtn.classList.remove('is-disabled');
            prevBtn.disabled = false;
            prevBtn.style.opacity = '1';
        }
    }
    if (nextBtn) {
        if (ettCurrentPage >= totalPages) {
            nextBtn.classList.add('is-disabled');
            nextBtn.disabled = true;
            nextBtn.style.opacity = '0.45';
        } else {
            nextBtn.classList.remove('is-disabled');
            nextBtn.disabled = false;
            nextBtn.style.opacity = '1';
        }
    }
}

function pjdChangeEmpPage(dir) {
    ettCurrentPage += dir;
    pjdInitEmpPagination();
}

function pjdFilterEmpRows(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.pjd-emp-row, .ett-emp-row');
    rows.forEach(row => {
        const name = row.getAttribute('data-emp-name') || '';
        const projs = row.getAttribute('data-emp-projects') || '';
        if (!q || name.includes(q) || projs.includes(q)) {
            row.removeAttribute('data-filtered-out');
        } else {
            row.setAttribute('data-filtered-out', 'true');
            row.style.display = 'none';
        }
    });
    ettCurrentPage = 1;
    pjdInitEmpPagination();
}

document.addEventListener('DOMContentLoaded', function() {
    pjdInitEmpPagination();
});

function openPjdEmpDetailsModal(emp) {
    if (!emp) return;

    document.getElementById('mEmpAvatar').textContent = emp.initials || 'EM';
    document.getElementById('mEmpName').textContent = emp.name || 'Employee';
    document.getElementById('mEmpRole').textContent = emp.designation || 'Team Member';

    // Tasks
    const tasksCount = emp.tasks ? emp.tasks.length : 0;
    document.getElementById('mTaskBadge').textContent = tasksCount + ' ' + (tasksCount === 1 ? 'Task' : 'Tasks');
    const tasksBox = document.getElementById('mTasksContainer');

    if (tasksCount > 0) {
        let tasksHtml = '<table style="width:100%; border-collapse:separate; border-spacing:0; font-size:12.5px; text-align:left;">';
        tasksHtml += '<thead><tr style="background:#ffffff; color:#64748b; font-weight:800; font-size:11px; text-transform:uppercase; letter-spacing:.05em;">';
        tasksHtml += '<th style="padding:10px 14px; border-bottom:1px solid #e2e8f0;">Date</th><th style="padding:10px 14px; border-bottom:1px solid #e2e8f0;">Project / Client</th><th style="padding:10px 14px; border-bottom:1px solid #e2e8f0;">Task Description</th><th style="padding:10px 14px; border-bottom:1px solid #e2e8f0; text-align:center;">Status</th></tr></thead><tbody>';

        emp.tasks.forEach(t => {
            const isDone = t.status === 'completed';
            const badgeBg = isDone ? '#ecfdf5' : (t.status === 'in_progress' ? '#eff6ff' : '#fff7ed');
            const badgeColor = isDone ? '#15803d' : (t.status === 'in_progress' ? '#1d4ed8' : '#b45309');
            const badgeBorder = isDone ? '#a7f3d0' : (t.status === 'in_progress' ? '#bfdbfe' : '#fed7aa');

            tasksHtml += '<tr style="border-bottom:1px solid #f1f5f9; background:#fff;">';
            tasksHtml += '<td style="padding:10px 14px; color:#64748b; font-weight:600; white-space:nowrap; border-bottom:1px solid #f1f5f9;">' + (t.task_date || '—') + '</td>';
            tasksHtml += '<td style="padding:10px 14px; border-bottom:1px solid #f1f5f9;"><div style="font-weight:700; color:#0f172a;">' + (t.project_name || 'General') + '</div><div style="font-size:11px; color:#64748b;">' + (t.company_name || '') + '</div></td>';
            tasksHtml += '<td style="padding:10px 14px; color:#334155; line-height:1.5; border-bottom:1px solid #f1f5f9;">' + (t.task_description || '—') + '</td>';
            tasksHtml += '<td style="padding:10px 14px; text-align:center; border-bottom:1px solid #f1f5f9;"><span style="display:inline-block; padding:3px 9px; border-radius:999px; font-size:10.5px; font-weight:800; text-transform:capitalize; background:' + badgeBg + '; color:' + badgeColor + '; border:1px solid ' + badgeBorder + ';">' + (t.status || 'pending') + '</span></td>';
            tasksHtml += '</tr>';
        });

        tasksHtml += '</tbody></table>';
        tasksBox.innerHTML = tasksHtml;
    } else {
        tasksBox.innerHTML = '<div style="padding:28px; text-align:center; color:#94a3b8; font-size:12.5px; font-style:italic;">No tasks recorded for this period.</div>';
    }

    // Timesheets
    const tsCount = emp.timesheets ? emp.timesheets.length : 0;
    document.getElementById('mTimesheetBadge').textContent = tsCount + ' ' + (tsCount === 1 ? 'Log' : 'Logs');
    const tsBox = document.getElementById('mTimesheetsContainer');

    if (tsCount > 0) {
        let tsHtml = '<table style="width:100%; border-collapse:separate; border-spacing:0; font-size:12.5px; text-align:left;">';
        tsHtml += '<thead><tr style="background:#ffffff; color:#64748b; font-weight:800; font-size:11px; text-transform:uppercase; letter-spacing:.05em;">';
        tsHtml += '<th style="padding:10px 14px; border-bottom:1px solid #e2e8f0;">Date</th><th style="padding:10px 14px; border-bottom:1px solid #e2e8f0;">Project</th><th style="padding:10px 14px; border-bottom:1px solid #e2e8f0;">Day Closing Update</th><th style="padding:10px 14px; border-bottom:1px solid #e2e8f0; text-align:center;">Status</th></tr></thead><tbody>';

        emp.timesheets.forEach(ts => {
            const isCompleted = ts.status === 'completed';
            const badgeBg = isCompleted ? '#ecfdf5' : (ts.status === 'ongoing' ? '#eff6ff' : '#fff7ed');
            const badgeColor = isCompleted ? '#15803d' : (ts.status === 'ongoing' ? '#1d4ed8' : '#b45309');
            const badgeBorder = isCompleted ? '#a7f3d0' : (ts.status === 'ongoing' ? '#bfdbfe' : '#fed7aa');

            let deliverablePill = '';
            if (ts.poster_count > 0 || ts.video_count > 0) {
                deliverablePill = '<div style="margin-top:4px;"><span style="font-size:10px; font-weight:700; padding:1px 6px; border-radius:6px; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa;">Posters: ' + ts.poster_count + ' | Videos: ' + ts.video_count + '</span></div>';
            }

            tsHtml += '<tr style="border-bottom:1px solid #f1f5f9; background:#fff;">';
            tsHtml += '<td style="padding:10px 14px; color:#64748b; font-weight:600; white-space:nowrap; border-bottom:1px solid #f1f5f9;">' + (ts.timesheet_date || '—') + '</td>';
            tsHtml += '<td style="padding:10px 14px; border-bottom:1px solid #f1f5f9;"><div style="font-weight:700; color:#0f172a;">' + (ts.project_name || 'General') + '</div><div style="font-size:11px; color:#64748b;">' + (ts.company_name || '') + '</div>' + deliverablePill + '</td>';
            tsHtml += '<td style="padding:10px 14px; color:#334155; line-height:1.5; white-space:pre-wrap; border-bottom:1px solid #f1f5f9;">' + (ts.day_closing_update || '—') + '</td>';
            tsHtml += '<td style="padding:10px 14px; text-align:center; border-bottom:1px solid #f1f5f9;"><span style="display:inline-block; padding:3px 9px; border-radius:999px; font-size:10.5px; font-weight:800; text-transform:capitalize; background:' + badgeBg + '; color:' + badgeColor + '; border:1px solid ' + badgeBorder + ';">' + (ts.status || 'pending') + '</span></td>';
            tsHtml += '</tr>';
        });

        tsHtml += '</tbody></table>';
        tsBox.innerHTML = tsHtml;
    } else {
        tsBox.innerHTML = '<div style="padding:28px; text-align:center; color:#94a3b8; font-size:12.5px; font-style:italic;">No timesheet entries recorded for this period.</div>';
    }

    const modal = document.getElementById('pjdEmpDetailsModal');
    const overlay = document.getElementById('pjdEmpDetailsModalOverlay');
    if (modal && overlay) {
        modal.classList.add('is-open');
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
}

function closePjdEmpDetailsModal() {
    const modal = document.getElementById('pjdEmpDetailsModal');
    const overlay = document.getElementById('pjdEmpDetailsModalOverlay');
    if (modal && overlay) {
        modal.classList.remove('is-open');
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePjdEmpDetailsModal();
    }
});
</script>
