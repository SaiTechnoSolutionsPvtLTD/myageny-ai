@extends('layouts.app')

@section('title', 'Timesheet LOP - HRMS')

@push('styles')
<style>
.tlop-page {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    background: #f8fafc;
    font-family: var(--font-family, 'Inter', sans-serif);
}
.tlop-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 28px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}
.tlop-title {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.tlop-breadcrumb {
    margin-top: 2px;
    color: #64748b;
    font-size: 12px;
    font-weight: 500;
}
.tlop-body {
    padding: 24px 28px 36px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.tlop-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
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
.tlop-btn:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
    transform: translateY(-1px);
}
.tlop-btn-primary {
    background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
    border-color: #ea580c;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(234, 88, 12, 0.15);
}
.tlop-btn-primary:hover {
    background: linear-gradient(135deg, #c2410c 0%, #ea580c 100%);
    border-color: #c2410c;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);
}
.tlop-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.tlop-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    display: flex;
    flex-direction: column;
    gap: 8px;
    position: relative;
    overflow: hidden;
}
.tlop-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}
.tlop-stat-card.primary::before { background: #3b82f6; }
.tlop-stat-card.success::before { background: #10b981; }
.tlop-stat-card.warning::before { background: #f59e0b; }
.tlop-stat-card.danger::before { background: #ef4444; }
.tlop-stat-card.purple::before { background: #8b5cf6; }

.tlop-stat-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}
.tlop-stat-val {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
}
.tlop-stat-sub {
    font-size: 12px;
    color: #94a3b8;
}

.tlop-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}
.tlop-card-head {
    padding: 18px 22px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fbfdff;
}
.tlop-card-title {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}
.tlop-filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    padding: 20px;
    align-items: end;
}
.tlop-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.tlop-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
}
.tlop-input, .tlop-select {
    width: 100%;
    height: 42px;
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    color: #0f172a;
    font-size: 13px;
    font-weight: 500;
    outline: none;
    transition: all 0.2s ease;
}
.tlop-input:focus, .tlop-select:focus {
    border-color: #ea580c;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}
.tlop-table-wrap {
    overflow-x: auto;
}
.tlop-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}
.tlop-table th {
    padding: 12px 18px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.tlop-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
    color: #1e293b;
    vertical-align: middle;
}
.tlop-table tbody tr:hover {
    background: #fbfdff;
}
.tlop-user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.tlop-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-transform: uppercase;
}
.tlop-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
}
.tlop-badge-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.tlop-badge-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.tlop-badge-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.tlop-badge-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.tlop-badge-neutral { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

.tlop-lop-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 800;
}
.tlop-lop-badge.has-lop {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #f87171;
}
.tlop-lop-badge.no-lop {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #86efac;
}

/* Modal styles */
.tlop-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(2px);
    z-index: 1200;
    display: none;
}
.tlop-modal-overlay.is-open {
    display: block;
}
.tlop-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: min(880px, calc(100vw - 32px));
    max-height: calc(100vh - 60px);
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
    z-index: 1210;
    display: none;
    flex-direction: column;
    overflow: hidden;
}
.tlop-modal.is-open {
    display: flex;
}
.tlop-modal-head {
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fbfdff;
}
.tlop-modal-title {
    font-size: 17px;
    font-weight: 800;
    color: #0f172a;
}
.tlop-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.tlop-modal-close:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.tlop-modal-body {
    padding: 20px 24px;
    overflow-y: auto;
    max-height: calc(100vh - 200px);
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.tlop-timeline-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid #f1f5f9;
    background: #ffffff;
    transition: all 0.15s ease;
}
.tlop-timeline-item:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.tlop-timeline-item.missing {
    background: #fff5f5;
    border-color: #fecaca;
}
.tlop-timeline-item.submitted {
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.tlop-timeline-item.leave {
    background: #f0f9ff;
    border-color: #bae6fd;
}
</style>
@endpush

@section('content')
<div class="tlop-page">
    <div class="tlop-topbar">
        <div>
            <div class="tlop-title">Timesheet LOP (Loss of Pay)</div>
            <div class="tlop-breadcrumb">HRMS > Timesheet LOP Management</div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('hrms.timesheet-lop.export', request()->query()) }}" class="tlop-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export Excel
            </a>
        </div>
    </div>

    <div class="tlop-body">
        {{-- Policy Notice Banner --}}
        <div style="padding: 14px 18px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; font-size: 13px; line-height: 1.5; display: flex; align-items: center; gap: 12px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                <strong>Timesheet LOP Rule:</strong> Applicable to <strong>Development, Designing, and Digital Marketing</strong> departments. Every month <strong>1st &amp; 3rd Saturday</strong> are Holidays, and <strong>2nd, 4th &amp; 5th Saturday</strong> are Working Days. For every <strong>2 days</strong> marked Present without submitting a Timesheet, <strong>0.5 Day LOP</strong> is marked (4 days = 1.0 Day LOP, 6 days = 1.5 Days LOP). Employees on approved Leave are exempt.
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="tlop-stats-grid">
            <div class="tlop-stat-card primary">
                <div class="tlop-stat-title">Target Employees</div>
                <div class="tlop-stat-val">{{ $stats['total_employees'] }}</div>
                <div class="tlop-stat-sub">Dev, Design &amp; DM Departments</div>
            </div>
            <div class="tlop-stat-card info">
                <div class="tlop-stat-title">Total Present Days</div>
                <div class="tlop-stat-val">{{ $stats['total_present_days'] }}</div>
                <div class="tlop-stat-sub">Across selected period</div>
            </div>
            <div class="tlop-stat-card success">
                <div class="tlop-stat-title">Timesheets Submitted</div>
                <div class="tlop-stat-val">{{ $stats['total_submitted_timesheets'] }}</div>
                <div class="tlop-stat-sub">Days timesheet recorded</div>
            </div>
            <div class="tlop-stat-card warning">
                <div class="tlop-stat-title">Missing Timesheet Days</div>
                <div class="tlop-stat-val">{{ $stats['total_missing_days'] }}</div>
                <div class="tlop-stat-sub">Present but no timesheet</div>
            </div>
            <div class="tlop-stat-card danger">
                <div class="tlop-stat-title">Total Timesheet LOP Days</div>
                <div class="tlop-stat-val" style="color: #dc2626;">{{ $stats['total_lop_days'] }} <span style="font-size: 15px; font-weight: 600;">Days</span></div>
                <div class="tlop-stat-sub">Calculated salary penalty days</div>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="tlop-card">
            <div class="tlop-card-head">
                <div class="tlop-card-title">Filter Records</div>
                <div style="font-size: 12px; color: #64748b;">
                    Period: <strong>{{ $selectedFromDate->format('d M Y') }}</strong> to <strong>{{ $selectedToDate->format('d M Y') }}</strong>
                </div>
            </div>
            <form method="GET" action="{{ route('hrms.timesheet-lop.index') }}" class="tlop-filter-form">
                <div class="tlop-field">
                    <label class="tlop-label">Month</label>
                    <input type="month" name="month" value="{{ $filters['month'] }}" class="tlop-input">
                </div>
                <div class="tlop-field">
                    <label class="tlop-label">From Date</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="tlop-input">
                </div>
                <div class="tlop-field">
                    <label class="tlop-label">To Date</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="tlop-input">
                </div>
                <div class="tlop-field">
                    <label class="tlop-label">Department</label>
                    <select name="department_id" class="tlop-select">
                        <option value="">All Target Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected($filters['department_id'] === (string) $dept->id)>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tlop-field">
                    <label class="tlop-label">Employee</label>
                    <select name="employee_id" class="tlop-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected($filters['employee_id'] === (string) $emp->id)>
                                {{ $emp->name }} ({{ $emp->employee_id ?: 'No ID' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tlop-field">
                    <label class="tlop-label">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search name / ID..." class="tlop-input">
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="tlop-btn tlop-btn-primary" style="height: 42px; min-width: 90px;">Filter</button>
                    <a href="{{ route('hrms.timesheet-lop.index') }}" class="tlop-btn" style="height: 42px;">Reset</a>
                </div>
            </form>
        </div>

        {{-- Employee-wise Table Card --}}
        <div class="tlop-card">
            <div class="tlop-card-head">
                <div class="tlop-card-title">Employee Timesheet &amp; LOP Summary</div>
                <span class="tlop-badge tlop-badge-neutral">{{ $rows->count() }} Employees</span>
            </div>
            <div class="tlop-table-wrap">
                <table class="tlop-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department &amp; Designation</th>
                            <th style="text-align: center;">Present Days</th>
                            <th style="text-align: center;">Timesheet Submitted</th>
                            <th style="text-align: center;">Missing Days</th>
                            <th style="text-align: center;">Leave Exempted</th>
                            <th style="text-align: center;">Timesheet LOP</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $emp = $row['employee'];
                                $initials = collect(explode(' ', $emp->name))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->join('');
                                $hasLop = $row['timesheet_lop_days'] > 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="tlop-user-cell">
                                        <div class="tlop-avatar">{{ $initials }}</div>
                                        <div>
                                            <div style="font-weight: 700; color: #0f172a;">{{ $emp->name }}</div>
                                            <div style="font-size: 11px; color: #64748b;">{{ $emp->employee_id ?: 'ID: N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #334155;">{{ $emp->department?->name ?: '—' }}</div>
                                    <div style="font-size: 11px; color: #64748b;">{{ $emp->role?->display_name ?: ($emp->role?->name ?: 'Employee') }}</div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="tlop-badge tlop-badge-info">{{ $row['present_days'] }} Days</span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="tlop-badge tlop-badge-success">{{ $row['timesheet_submitted_days'] }} Days</span>
                                </td>
                                <td style="text-align: center;">
                                    @if($row['missing_days'] > 0)
                                        <span class="tlop-badge tlop-badge-danger">{{ $row['missing_days'] }} Days</span>
                                    @else
                                        <span class="tlop-badge tlop-badge-neutral">0 Days</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="tlop-badge tlop-badge-neutral">{{ $row['leave_exempted_days'] }} Days</span>
                                </td>
                                <td style="text-align: center;">
                                    @if($hasLop)
                                        <div class="tlop-lop-badge has-lop">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            {{ $row['timesheet_lop_days'] }} Days LOP
                                        </div>
                                    @else
                                        <div class="tlop-lop-badge no-lop">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            0.0 LOP
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" 
                                            class="tlop-btn" 
                                            style="padding: 6px 12px; font-size: 12px;"
                                            data-open-breakdown
                                            data-employee-id="{{ $emp->id }}"
                                            data-employee-name="{{ $emp->name }}">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">
                                    No records found for the selected criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Daily Breakdown Modal --}}
<div class="tlop-modal-overlay" data-breakdown-overlay></div>
<div class="tlop-modal" data-breakdown-modal>
    <div class="tlop-modal-head">
        <div>
            <div class="tlop-modal-title" id="modalEmployeeName">Employee Daily Breakdown</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;" id="modalPeriodText">Period</div>
        </div>
        <button type="button" class="tlop-modal-close" data-close-breakdown>
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="tlop-modal-body">
        {{-- Summary bar inside modal --}}
        <div id="modalSummaryBar" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; background: #f8fafc; padding: 14px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Present</div>
                <div style="font-size: 18px; font-weight: 800; color: #0f172a;" id="modalPresentCount">0</div>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Submitted</div>
                <div style="font-size: 18px; font-weight: 800; color: #16a34a;" id="modalSubmittedCount">0</div>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Missing</div>
                <div style="font-size: 18px; font-weight: 800; color: #dc2626;" id="modalMissingCount">0</div>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Timesheet LOP</div>
                <div style="font-size: 18px; font-weight: 800; color: #b91c1c;" id="modalLopCount">0.0 Days</div>
            </div>
        </div>

        <div style="font-size: 13px; font-weight: 700; color: #334155; margin-top: 6px;">Daily Timeline:</div>

        <div id="modalTimelineContainer" style="display: flex; flex-direction: column; gap: 8px;">
            {{-- Injected dynamically --}}
            <div style="text-align: center; padding: 30px; color: #64748b;">Loading details...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.querySelector('[data-breakdown-overlay]');
    const modal = document.querySelector('[data-breakdown-modal]');
    const closeButtons = document.querySelectorAll('[data-close-breakdown]');
    const openButtons = document.querySelectorAll('[data-open-breakdown]');

    const currentFromDate = @json($filters['from_date']);
    const currentToDate = @json($filters['to_date']);

    function toggleModal(isOpen) {
        if (!overlay || !modal) return;
        overlay.classList.toggle('is-open', isOpen);
        modal.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => toggleModal(false));
    });

    if (overlay) {
        overlay.addEventListener('click', () => toggleModal(false));
    }

    openButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const empId = this.dataset.employeeId;
            const empName = this.dataset.employeeName;

            document.getElementById('modalEmployeeName').textContent = empName + ' - Timesheet & Attendance Breakdown';
            document.getElementById('modalTimelineContainer').innerHTML = '<div style="text-align:center; padding:30px; color:#64748b;">Loading daily records...</div>';

            toggleModal(true);

            fetch(`/hrms/timesheet-lop/details/${empId}?from_date=${currentFromDate}&to_date=${currentToDate}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('modalTimelineContainer').innerHTML = '<div style="text-align:center; padding:20px; color:#ef4444;">Failed to load details.</div>';
                        return;
                    }

                    document.getElementById('modalPeriodText').textContent = 'Period: ' + data.period.from + ' to ' + data.period.to + ' | ' + data.employee.department + ' (' + data.employee.designation + ')';
                    document.getElementById('modalPresentCount').textContent = data.summary.present_days + ' Days';
                    document.getElementById('modalSubmittedCount').textContent = data.summary.timesheet_submitted_days + ' Days';
                    document.getElementById('modalMissingCount').textContent = data.summary.missing_days + ' Days';
                    document.getElementById('modalLopCount').textContent = data.summary.timesheet_lop_days + ' Days';

                    let html = '';
                    if (data.daily_records.length === 0) {
                        html = '<div style="text-align:center; padding:20px; color:#64748b;">No records for this period.</div>';
                    } else {
                        data.daily_records.forEach(rec => {
                            let itemClass = '';
                            let badgeHtml = '';

                            if (rec.is_leave) {
                                itemClass = 'leave';
                                badgeHtml = '<span class="tlop-badge tlop-badge-info">Leave Exempted</span>';
                            } else if (rec.is_present) {
                                if (rec.has_timesheet) {
                                    itemClass = 'submitted';
                                    badgeHtml = '<span class="tlop-badge tlop-badge-success">Timesheet Submitted</span>';
                                } else {
                                    itemClass = 'missing';
                                    badgeHtml = '<span class="tlop-badge tlop-badge-danger">Missing Timesheet (LOP candidate)</span>';
                                }
                            } else {
                                badgeHtml = `<span class="tlop-badge tlop-badge-${rec.badge_class}">${rec.status_label}</span>`;
                            }

                            let timesheetDetailsHtml = '';
                            if (rec.timesheet_entries && rec.timesheet_entries.length > 0) {
                                timesheetDetailsHtml = '<div style="font-size:11px; color:#059669; margin-top:4px;">';
                                rec.timesheet_entries.forEach(ts => {
                                    timesheetDetailsHtml += `<div>✓ ${ts.project_name} (${ts.status})</div>`;
                                });
                                timesheetDetailsHtml += '</div>';
                            }

                            html += `
                                <div class="tlop-timeline-item ${itemClass}">
                                    <div>
                                        <div style="font-weight: 700; font-size: 13px; color: #0f172a;">
                                            ${rec.date} <span style="font-size: 11px; font-weight: 500; color: #64748b;">(${rec.day_name})</span>
                                        </div>
                                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                            Check-in: <strong>${rec.login_time}</strong> | Check-out: <strong>${rec.logout_time}</strong>
                                        </div>
                                        ${timesheetDetailsHtml}
                                    </div>
                                    <div>
                                        ${badgeHtml}
                                    </div>
                                </div>
                            `;
                        });
                    }

                    document.getElementById('modalTimelineContainer').innerHTML = html;
                })
                .catch(err => {
                    console.error('Error fetching breakdown:', err);
                    document.getElementById('modalTimelineContainer').innerHTML = '<div style="text-align:center; padding:20px; color:#ef4444;">Error loading details.</div>';
                });
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            toggleModal(false);
        }
    });
});
</script>
@endpush
