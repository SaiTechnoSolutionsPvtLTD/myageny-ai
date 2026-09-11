@extends('layouts.app')

@section('title', 'Attendance')

@push('styles')
<style>
.att-page {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    background: #f8fafc;
    font-family: var(--font-family, 'Inter', sans-serif);
}
.att-topbar, .att-filter-form, .att-stats, .att-meta-row {
    display: flex;
    gap: 16px;
}
.att-topbar {
    justify-content: space-between;
    align-items: center;
    padding: 16px 28px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.att-meta-row {
    justify-content: space-between;
    align-items: flex-start;
}
.att-title {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.att-breadcrumb {
    margin-top: 2px;
    color: #64748b;
    font-size: 12px;
    font-weight: 500;
}
.att-subcopy {
    margin-top: 8px;
    color: #475569;
    font-size: 13px;
    max-width: 760px;
    line-height: 1.6;
}
.att-body {
    padding: 24px 28px 36px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.att-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.att-btn:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
    transform: translateY(-1px);
}
.att-btn:active {
    transform: translateY(0);
}
.att-btn-primary {
    background: linear-gradient(135deg, #fe5f04 0%, #ff7c30 100%);
    border-color: #fe5f04;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(254, 95, 4, 0.15);
}
.att-btn-primary:hover {
    background: linear-gradient(135deg, #e05300 0%, #f26f22 100%);
    border-color: #e05300;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(254, 95, 4, 0.25);
}
.att-btn-ghost {
    background: #ffffff;
    color: #475569;
    border-color: #e2e8f0;
}
.att-btn-ghost:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.att-card, .att-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.att-card {
    padding: 24px;
}
.att-filter-wrap, .att-table-wrap {
    margin-top: 0px;
}
.att-filter-form {
    flex-wrap: wrap;
    align-items: flex-end;
}
.att-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 180px;
    flex: 1;
}
.att-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}
.att-input, .att-select {
    height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0 14px;
    background: #ffffff;
    color: #1e293b;
    font-size: 13px;
    transition: all 0.2s ease;
}
.att-input:hover, .att-select:hover {
    border-color: #94a3b8;
}
.att-input:focus, .att-select:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
    outline: none;
}
.att-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.att-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}
.att-stat-card {
    position: relative;
    padding: 20px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
    transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}
.att-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
}
.att-stat-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #cbd5e1;
}
.att-stat-card.employees {
    background: linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);
    border-color: #bfdbfe;
}
.att-stat-card.employees::before {
    background: #3b82f6;
}
.att-stat-card.present {
    background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
    border-color: #bbf7d0;
}
.att-stat-card.present::before {
    background: #22c55e;
}
.att-stat-card.absent {
    background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
    border-color: #fecaca;
}
.att-stat-card.absent::before {
    background: #ef4444;
}
.att-stat-card.timing {
    background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
    border-color: #fde68a;
}
.att-stat-card.timing::before {
    background: #f59e0b;
}
.att-stat-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    position: relative;
    z-index: 1;
}
.att-stat-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
}
.att-stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    color: #475569;
    border: 1px solid #e2e8f0;
}
.att-stat-card.employees .att-stat-icon { color: #3b82f6; border-color: #bfdbfe; }
.att-stat-card.present .att-stat-icon { color: #22c55e; border-color: #bbf7d0; }
.att-stat-card.absent .att-stat-icon { color: #ef4444; border-color: #fecaca; }
.att-stat-card.timing .att-stat-icon { color: #f59e0b; border-color: #fde68a; }

.att-stat-value {
    font-size: 30px;
    line-height: 1;
    font-weight: 800;
    color: #0f172a;
    position: relative;
    z-index: 1;
    margin-top: 10px;
    letter-spacing: -0.02em;
}
.att-stat-foot {
    margin-top: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    position: relative;
    z-index: 1;
}
.att-stat-pill {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid #e2e8f0;
    color: #334155;
}
.att-stat-trend {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
}
.att-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}
.att-card-sub {
    margin-top: 4px;
    color: #64748b;
    font-size: 12px;
}
.att-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    text-transform: capitalize;
}
.att-chip-present {
    background: #dcfce7;
    color: #15803d;
}
.att-chip-absent {
    background: #fee2e2;
    color: #b91c1c;
}
.att-chip-early {
    background: #dbeafe;
    color: #1d4ed8;
}
.att-chip-late {
    background: #fef3c7;
    color: #d97706;
}
.att-chip-on-time {
    background: #f1f5f9;
    color: #475569;
}
.att-chip-leave {
    background: #faf5ff;
    color: #701a75;
}
.att-chip-od {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
    font-weight: 800;
}
.att-chip-employee {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}
.att-chip-intern {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}
.att-table-wrap {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
    padding: 24px;
    margin-top: 8px;
}
.att-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 1000px;
}
.att-table th, .att-table td {
    padding: 14px 16px;
    text-align: left;
    vertical-align: middle;
}
.att-table th {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #475569;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}
.att-table td {
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}
.att-table tbody tr:hover td {
    background-color: #f8fafc;
}
.att-th-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: inherit;
    text-decoration: none;
    transition: color 0.15s ease;
}
.att-th-link:hover {
    color: #fe5f04;
}
.att-th-sort {
    font-size: 11px;
    line-height: 1;
    color: #94a3b8;
}
.att-th-sort.is-active {
    color: #fe5f04;
}
.att-row-intern td {
    background: #fffdfa;
}
.att-cell-title {
    font-weight: 600;
    color: #0f172a;
    font-size: 13px;
}
.att-cell-sub {
    margin-top: 2px;
    color: #64748b;
    font-size: 11px;
}
.att-empty {
    padding: 48px 20px;
    text-align: center;
    color: #64748b;
    font-size: 14px;
}
.att-photo {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ffffff;
    box-shadow: 0 0 0 1px #e2e8f0;
}
.att-thumb-row {
    display: flex;
    align-items: center;
    gap: 12px;
}
.att-note {
    font-size: 12px;
    color: #64748b;
}
.att-capture-photo {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.att-capture-photo.is-clickable {
    cursor: pointer;
}
.att-capture-photo.is-clickable:hover {
    transform: translateY(-1px) scale(1.04);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
}
.att-capture-empty {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 10px;
    font-weight: 600;
    text-align: center;
    padding: 4px;
}
.att-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1200;
}
.att-modal.is-open {
    display: flex;
}
.att-modal-dialog {
    position: relative;
    max-width: min(92vw, 960px);
    max-height: 92vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.att-modal-image {
    max-width: 100%;
    max-height: 92vh;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
}
.att-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.8);
    color: #ffffff;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s ease;
}
.att-modal-close:hover {
    background: #fe5f04;
}

details.att-accordion {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
    overflow: hidden;
    margin-top: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
details.att-accordion summary::-webkit-details-marker {
    display: none;
}
details.att-accordion summary {
    list-style: none;
}
summary.att-accordion-header {
    padding: 16px 24px;
    font-weight: 700;
    font-size: 15px;
    color: #0f172a;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
    user-select: none;
    transition: background-color 0.15s ease, color 0.15s ease;
}
summary.att-accordion-header:hover {
    background: #f8fafc;
    color: #fe5f04;
}
summary.att-accordion-header::after {
    content: "";
    display: inline-block;
    width: 7px;
    height: 7px;
    border-right: 2px solid #64748b;
    border-bottom: 2px solid #64748b;
    transform: rotate(45deg);
    transition: transform 0.2s ease, border-color 0.15s ease;
    margin-right: 4px;
}
summary.att-accordion-header:hover::after {
    border-color: #fe5f04;
}
details[open] summary.att-accordion-header::after {
    transform: rotate(-135deg);
}
details[open] summary.att-accordion-header {
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}
.att-accordion-content {
    padding: 24px;
    background: #ffffff;
}

@media (max-width: 900px) {
    .att-topbar { padding: 16px 20px; }
    .att-body { padding: 16px 20px 24px; }
    .att-topbar, .att-meta-row { flex-direction: column; align-items: stretch; gap: 12px; }
    .att-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .att-filter-form { flex-direction: column; align-items: stretch; }
    .att-field { min-width: 100%; }
    .att-actions { width: 100%; justify-content: flex-start; }
}
@media (max-width: 640px) {
    .att-stats { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="att-page">
    @php($selfServiceMode = auth()->user()?->isHrmsAttendanceOnlyUser())
    @php($managerAttendanceView = ($canViewAllAttendance ?? false) || ($hasTeamMembers ?? false))
    @php($sortIcon = fn (string $column) => $sortBy === $column ? ($sortDir === 'asc' ? '↑' : '↓') : '↕')
    <div class="att-topbar">
        <div>
            <div class="att-title">{{ $managerAttendanceView ? 'Attendance' : 'My Attendance' }}</div>
            <div class="att-breadcrumb">HRMS > Attendance</div>

        </div>
        <div class="att-actions">
            @if($managerAttendanceView)
                <a href="{{ route('attendance.create') }}" class="att-btn att-btn-primary">Check In</a>
                <a href="{{ route('attendance.create', ['attendance_status' => 'leave']) }}" class="att-btn att-btn-ghost">Mark Leave</a>
                <a href="{{ route('attendance.checkout.create') }}" class="att-btn att-btn-ghost">Checkout</a>
            @endif
            <a href="{{ route('hrms.dashboard') }}" class="att-btn att-btn-ghost">Back</a>
        </div>
    </div>

    <div class="att-body">
    @if(session('success'))
        <div class="att-card" style="border-color:#bbf7d0;background:#f0fdf4;color:#166534;font-size:13px;font-weight:700;">
            {!! session('success') !!}
        </div>
    @endif
    <div class="att-stats">
        <div class="att-stat-card employees">
            <div class="att-stat-head">
                <div class="att-stat-label">People</div>
                <div class="att-stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="10" cy="7" r="4"></circle>
                        <path d="M20 8v6"></path>
                        <path d="M23 11h-6"></path>
                    </svg>
                </div>
            </div>
            <div class="att-stat-value">{{ $stats['total_employees'] }}</div>
            <div class="att-stat-foot">
                <span class="att-stat-pill">{{ $stats['employee_count'] }} Emp / {{ $stats['intern_count'] }} Int</span>
                <span class="att-stat-trend">Workforce</span>
            </div>
        </div>
        <div class="att-stat-card present">
            <div class="att-stat-head">
                <div class="att-stat-label">Present</div>
                <div class="att-stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
            </div>
            <div class="att-stat-value">{{ $stats['present_count'] }}</div>
            <div class="att-stat-foot">
                <span class="att-stat-pill">Checked In</span>
                <span class="att-stat-trend">Active today</span>
            </div>
        </div>
        <div class="att-stat-card absent">
            <div class="att-stat-head">
                <div class="att-stat-label">Absent</div>
                <div class="att-stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </div>
            </div>
            <div class="att-stat-value">{{ $stats['absent_count'] }}</div>
            <div class="att-stat-foot">
                <span class="att-stat-pill">Needs follow-up</span>
                <span class="att-stat-trend">Missing today</span>
            </div>
        </div>
        <div class="att-stat-card timing">
            <div class="att-stat-head">
                <div class="att-stat-label">Late / Early</div>
                <div class="att-stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 3"></path>
                    </svg>
                </div>
            </div>
            <div class="att-stat-value">{{ $stats['late_count'] }} / {{ $stats['early_count'] }}</div>
            <div class="att-stat-foot">
                <span class="att-stat-pill">Late vs Early</span>
                <span class="att-stat-trend">Timing view</span>
            </div>
        </div>
    </div>

<details class="att-accordion">
    <summary class="att-accordion-header">Filter Attendance</summary>
    <div class="att-accordion-content">
        <div class="att-card-sub">{{ $managerAttendanceView ? 'Filter by employee, department, date range, status, or login timing.' : 'Review your attendance by date range, status, or login timing.' }}</div>
        <form method="GET" action="{{ route('attendance.index') }}" class="att-filter-form" style="margin-top:16px;">
            @if($managerAttendanceView)
            <div class="att-field">
                <label class="att-label">Branch</label>
                <select name="branch_id" class="att-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="att-field">
                <label class="att-label">Employee</label>
                <input type="text" name="employee_name" class="att-input" value="{{ request('employee_name') }}" placeholder="Search employee name">
            </div>
            <div class="att-field">
                <label class="att-label">Department</label>
                <select name="department_id" class="att-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="att-field">
                <label class="att-label">From Date</label>
                <input type="date" name="from_date" class="att-input" value="{{ request('from_date', $selectedFromDate->format('Y-m-d')) }}">
            </div>
            <div class="att-field">
                <label class="att-label">To Date</label>
                <input type="date" name="to_date" class="att-input" value="{{ request('to_date', $selectedToDate->format('Y-m-d')) }}">
            </div>
            <div class="att-field">
                <label class="att-label">Status</label>
                <select name="status" class="att-select">
                    <option value="">All Status</option>
                    <option value="present" @selected(request('status') === 'present')>Present</option>
                    <option value="od" @selected(request('status') === 'od')>OD (On Duty)</option>
                    <option value="absent" @selected(request('status') === 'absent')>Absent</option>
                    <option value="leave" @selected(request('status') === 'leave')>Leave</option>
                </select>
            </div>
            <div class="att-field">
                <label class="att-label">Login Timing</label>
                <select name="login_timing" class="att-select">
                    <option value="">All Timing</option>
                    <option value="late" @selected(request('login_timing') === 'late')>Late Login</option>
                    <option value="early" @selected(request('login_timing') === 'early')>Early Login</option>
                </select>
            </div>
            @if($managerAttendanceView)
            <div class="att-field">
                <label class="att-label">Attendee Type</label>
                <select name="attendee_type" class="att-select">
                    <option value="">All Types</option>
                    <option value="employee" @selected(request('attendee_type') === 'employee')>Employees</option>
                    <option value="intern" @selected(request('attendee_type') === 'intern')>Interns</option>
                </select>
            </div>
            @endif
            <div class="att-actions">
                <button type="submit" class="att-btn att-btn-primary">Apply Filter</button>
                @if($managerAttendanceView)
                    <button type="submit" formaction="{{ route('attendance.export') }}" class="att-btn">Export Excel</button>
                @endif
                @if(request()->hasAny(['employee_name', 'branch_id', 'department_id', 'from_date', 'to_date', 'status', 'login_timing', 'attendee_type']))
                    <a href="{{ route('attendance.index') }}" class="att-btn">Reset</a>
                @endif
            </div>
        </form>
    </div>
</details>

    <div class="att-table-wrap att-card">
        <div class="att-meta-row">
            <div>
                <div class="att-card-title">{{ $managerAttendanceView ? 'Attendance Details' : 'Your Attendance Details' }}</div>
                <div class="att-card-sub">{{ $attendances->total() }} record(s) matched your filters.</div>
            </div>
            <div class="att-note">{{ $managerAttendanceView ? 'Default view loads current date in both From Date and To Date.' : 'Default view loads your current date attendance automatically.' }}</div>
        </div>

        @if($attendances->isEmpty())
            <div class="att-empty">No attendance records found for the selected filters.</div>
        @else
            <div style="overflow-x:auto; margin-top:16px;">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'employee_name', 'sort_dir' => $sortBy === 'employee_name' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Employee <span class="att-th-sort {{ $sortBy === 'employee_name' ? 'is-active' : '' }}">{{ $sortIcon('employee_name') }}</span></a></th>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'employee_id', 'sort_dir' => $sortBy === 'employee_id' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Employee ID <span class="att-th-sort {{ $sortBy === 'employee_id' ? 'is-active' : '' }}">{{ $sortIcon('employee_id') }}</span></a></th>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'attendance_date', 'sort_dir' => $sortBy === 'attendance_date' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Date <span class="att-th-sort {{ $sortBy === 'attendance_date' ? 'is-active' : '' }}">{{ $sortIcon('attendance_date') }}</span></a></th>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'attendance_status', 'sort_dir' => $sortBy === 'attendance_status' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Status <span class="att-th-sort {{ $sortBy === 'attendance_status' ? 'is-active' : '' }}">{{ $sortIcon('attendance_status') }}</span></a></th>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'login_time', 'sort_dir' => $sortBy === 'login_time' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Login <span class="att-th-sort {{ $sortBy === 'login_time' ? 'is-active' : '' }}">{{ $sortIcon('login_time') }}</span></a></th>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'logout_time', 'sort_dir' => $sortBy === 'logout_time' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Logout <span class="att-th-sort {{ $sortBy === 'logout_time' ? 'is-active' : '' }}">{{ $sortIcon('logout_time') }}</span></a></th>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'overall_working_hours', 'sort_dir' => $sortBy === 'overall_working_hours' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Working Hours <span class="att-th-sort {{ $sortBy === 'overall_working_hours' ? 'is-active' : '' }}">{{ $sortIcon('overall_working_hours') }}</span></a></th>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'login_location', 'sort_dir' => $sortBy === 'login_location' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Location <span class="att-th-sort {{ $sortBy === 'login_location' ? 'is-active' : '' }}">{{ $sortIcon('login_location') }}</span></a></th>
                            <th><a href="{{ route('attendance.index', array_merge(request()->query(), ['sort_by' => 'attendance_photo', 'sort_dir' => $sortBy === 'attendance_photo' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="att-th-link">Attendance Captured Photo <span class="att-th-sort {{ $sortBy === 'attendance_photo' ? 'is-active' : '' }}">{{ $sortIcon('attendance_photo') }}</span></a></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendances as $attendance)
                            <tr class="{{ $attendance['attendee_type'] === 'intern' ? 'att-row-intern' : '' }}">
                                <td>
                                    <div class="att-thumb-row">
                                        @if($attendance['profile_photo_url'] ?? null)
                                            <img src="{{ $attendance['profile_photo_url'] }}" alt="{{ $attendance['employee_name'] }}" class="att-photo">
                                        @endif
                                        <div>
                                            <div class="att-cell-title">{{ $attendance['employee_name'] }}</div>
                                            <div class="att-cell-sub">
                                                <span class="att-chip att-chip-{{ $attendance['attendee_type'] }}">{{ ucfirst($attendance['attendee_type']) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="att-cell-title">{{ $attendance['employee_id'] ?: 'N/A' }}</div>
                                </td>
                                <td>
                                        <div class="att-cell-title">{{ \Carbon\Carbon::parse($attendance['attendance_date'])->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <span class="att-chip att-chip-{{ $attendance['attendance_status'] }}">{{ $attendance['attendance_status'] === 'od' ? 'OD' : ucfirst($attendance['attendance_status']) }}</span>
                                    @if($attendance['attendance_status'] === 'leave' && $attendance['leave_label'])
                                        <div class="att-cell-sub">{{ $attendance['leave_label'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($attendance['attendance_status'] === 'leave')
                                        <div class="att-cell-sub">Leave day</div>
                                    @elseif($attendance['login_time'])
                                        <div class="att-cell-title">{{ \Carbon\Carbon::createFromFormat('H:i:s', $attendance['login_time'])->format('h:i A') }}</div>
                                        <div class="att-cell-sub">
                                            <span class="att-chip att-chip-{{ $attendance['login_timing'] }}">{{ str_replace('-', ' ', $attendance['login_timing']) }}</span>
                                        </div>
                                    @else
                                        <div class="att-cell-sub">No login</div>
                                    @endif
                                </td>
                                <td>
                                    @if($attendance['attendance_status'] === 'leave')
                                        <div class="att-cell-sub">No checkout</div>
                                    @elseif($attendance['logout_time'])
                                        <div class="att-cell-title">{{ \Carbon\Carbon::createFromFormat('H:i:s', $attendance['logout_time'])->format('h:i A') }}</div>
                                    @else
                                        <div class="att-cell-sub">Not checked out</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="att-cell-title">{{ $attendance['attendance_status'] === 'leave' ? 'N/A' : ($attendance['overall_working_hours'] ?: 'N/A') }}</div>
                                </td>
                                <td>
                                    <div class="att-cell-title">{{ $attendance['login_location'] ?: 'N/A' }}</div>
                                    @if($attendance['logout_location'])
                                        <div class="att-cell-sub">Logout: {{ $attendance['logout_location'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($attendance['attendance_photo_url'])
                                        <img src="{{ $attendance['attendance_photo_url'] }}" alt="{{ $attendance['employee_name'] }} check-in photo" class="att-capture-photo is-clickable" data-att-preview="{{ $attendance['attendance_photo_url'] }}">
                                    @else
                                        <div class="att-capture-empty">No Photo</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($attendances->hasPages())
                @include('partials.table-pagination', ['paginator' => $attendances])
            @endif
        @endif
    </div>
    </div>
</div>

<div class="att-modal" id="attendanceImageModal" aria-hidden="true">
    <div class="att-modal-dialog">
        <button type="button" class="att-modal-close" id="attendanceImageModalClose" aria-label="Close image preview">&times;</button>
        <img src="" alt="Attendance photo preview" class="att-modal-image" id="attendanceImageModalPreview">
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('attendanceImageModal');
    const modalImage = document.getElementById('attendanceImageModalPreview');
    const modalClose = document.getElementById('attendanceImageModalClose');
    const previewImages = document.querySelectorAll('[data-att-preview]');

    if (!modal || !modalImage || !modalClose || previewImages.length === 0) {
        return;
    }

    const closeModal = function () {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modalImage.src = '';
    };

    previewImages.forEach(function (image) {
        image.addEventListener('click', function () {
            const src = image.getAttribute('data-att-preview');

            if (!src) {
                return;
            }

            modalImage.src = src;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
});
</script>
@endpush
