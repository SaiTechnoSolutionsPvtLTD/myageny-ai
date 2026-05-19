@extends('layouts.app')

@section('title', 'Attendance')

@push('styles')
<style>
.att-page{display:flex;flex-direction:column;min-height:100%;background:#f4f5f7;font-family:var(--font-family, 'Inter', sans-serif)}
.att-topbar,.att-filter-form,.att-stats,.att-meta-row{display:flex;gap:16px}
.att-topbar{justify-content:space-between;align-items:center;padding:0 28px;min-height:60px;background:#fff;border-bottom:1px solid #e1dee3}
.att-meta-row{justify-content:space-between;align-items:flex-start}
.att-title{font-size:18px;font-weight:800;color:#121212}
.att-breadcrumb{margin-top:2px;color:#9e9e9e;font-size:12px}
.att-subcopy{margin-top:8px;color:#6f6f77;font-size:12px;max-width:760px;line-height:1.6}
.att-body{padding:22px 28px 34px;display:flex;flex-direction:column;gap:16px}
.att-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:10px;border:1px solid transparent;background:#fff;color:#121212;text-decoration:none;font-size:13px;font-weight:700;transition:all .18s ease}
.att-btn:hover{transform:translateY(-1px)}
.att-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);border-color:#fe5f04;color:#fff}
.att-btn-ghost{background:#fff;color:#121212;border-color:#e1dee3}
.att-card,.att-stat-card{background:#fff;border:1px solid #e1dee3;border-radius:16px;box-shadow:none}
.att-card{padding:20px}
.att-filter-wrap,.att-table-wrap{margin-top:0px}
.att-filter-form{flex-wrap:wrap;align-items:flex-end}
.att-field{display:flex;flex-direction:column;gap:8px;min-width:180px;flex:1}
.att-label{font-size:13px;font-weight:700;color:#444}
.att-input,.att-select{height:44px;border:1px solid #e1dee3;border-radius:10px;padding:0 14px;background:#fff;color:#20222a;font-size:14px}
.att-input:focus,.att-select:focus{border-color:#fe5f04;box-shadow:0 0 0 3px rgba(254,95,4,.1);outline:none}
.att-actions{display:flex;gap:10px;align-items:center}
.att-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.att-stat-card{position:relative;padding:16px 16px 14px;min-width:180px;overflow:hidden;background:linear-gradient(180deg,#ffffff 0%,#fcfbff 100%);box-shadow:0 12px 28px rgba(18,18,18,.05);transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
.att-stat-card:hover{transform:translateY(-2px);box-shadow:0 18px 34px rgba(18,18,18,.08)}
.att-stat-card::before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;border-radius:16px 0 0 16px;background:#d7dbe3}
.att-stat-card::after{content:"";position:absolute;right:-26px;top:-26px;width:92px;height:92px;border-radius:50%;background:rgba(255,255,255,.5)}
.att-stat-card.employees{background:linear-gradient(135deg,#f8fbff 0%,#eef5ff 100%);border-color:#d7e6ff}
.att-stat-card.employees::before{background:linear-gradient(180deg,#3b82f6,#1d4ed8)}
.att-stat-card.present{background:linear-gradient(135deg,#f5fff8 0%,#ebfdf1 100%);border-color:#d7f0df}
.att-stat-card.present::before{background:linear-gradient(180deg,#22c55e,#15803d)}
.att-stat-card.absent{background:linear-gradient(135deg,#fff8f7 0%,#fff0ef 100%);border-color:#ffd9d4}
.att-stat-card.absent::before{background:linear-gradient(180deg,#ef4444,#b91c1c)}
.att-stat-card.timing{background:linear-gradient(135deg,#fffaf3 0%,#fff2df 100%);border-color:#ffe1ba}
.att-stat-card.timing::before{background:linear-gradient(180deg,#f59e0b,#ea580c)}
.att-stat-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;position:relative;z-index:1}
.att-stat-label{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#7f8794}
.att-stat-icon{width:42px;height:42px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.7);background:rgba(255,255,255,.72);backdrop-filter:blur(6px);color:#121212;box-shadow:inset 0 1px 0 rgba(255,255,255,.65)}
.att-stat-value{font-size:32px;line-height:1;font-weight:800;color:#121212;position:relative;z-index:1}
.att-stat-foot{margin-top:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;position:relative;z-index:1}
.att-stat-pill{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.02em;background:rgba(255,255,255,.8);color:#3f4754;border:1px solid rgba(255,255,255,.9)}
.att-stat-trend{font-size:11px;font-weight:700;color:#6f7784}
.att-card-title{font-size:16px;font-weight:700;color:#121212}
.att-card-sub{margin-top:4px;color:#9e9e9e;font-size:12px}
.att-chip{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;text-transform:capitalize}
.att-chip-present{background:#ecfdf3;color:#027a48}
.att-chip-absent{background:#fef3f2;color:#b42318}
.att-chip-early{background:#eff8ff;color:#175cd3}
.att-chip-late{background:#fff7ed;color:#c2410c}
.att-chip-on-time{background:#f4f4f5;color:#52525b}
.att-chip-leave{background:#f5eeff;color:#6b21a8}
.att-chip-employee{background:#eff6ff;color:#1d4ed8}
.att-chip-intern{background:#fff7ed;color:#c2410c}
.att-table{width:100%;border-collapse:collapse;min-width:1000px}
.att-table th,.att-table td{padding:14px 16px;border-bottom:1px solid #f0eef2;text-align:left;vertical-align:top}
.att-table th{font-size:10px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:#9e9e9e;background:#fafafa}
.att-row-intern td{background:#fffaf5}
.att-cell-title{font-weight:700;color:#121212;font-size:13px}
.att-cell-sub{margin-top:2px;color:#9e9e9e;font-size:11px}
.att-empty{padding:42px 20px;text-align:center;color:#7a7f8c}
.att-photo{width:42px;height:42px;border-radius:12px;object-fit:cover;border:1px solid #ece4dc}
.att-thumb-row{display:flex;align-items:center;gap:12px}
.att-note{font-size:12px;color:#8a8a97}
.att-capture-photo{width:68px;height:68px;border-radius:14px;object-fit:cover;border:1px solid #ece4dc;background:#fafafa}
.att-capture-empty{width:68px;height:68px;border-radius:14px;border:1px dashed #d7d7de;background:#fafafa;display:flex;align-items:center;justify-content:center;color:#9e9e9e;font-size:10px;font-weight:700;text-align:center;padding:6px}
@media (max-width: 900px){
    .att-topbar{padding:16px 20px}
    .att-body{padding:16px 20px 24px}
    .att-topbar,.att-meta-row{flex-direction:column}
    .att-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
    .att-filter-form{flex-direction:column;align-items:stretch}
    .att-field{min-width:100%}
    .att-actions{width:100%}
}
@media (max-width: 640px){
    .att-stats{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
<div class="att-page">
    @php($selfServiceMode = auth()->user()?->isHrmsAttendanceOnlyUser())
    <div class="att-topbar">
        <div>
            <div class="att-title">{{ $selfServiceMode ? 'My Attendance' : 'Attendance' }}</div>
            <div class="att-breadcrumb">HRMS > Attendance</div>

        </div>
        <div class="att-actions">
            @unless($selfServiceMode)
                <a href="{{ route('attendance.create') }}" class="att-btn att-btn-primary">Check In</a>
                <a href="{{ route('attendance.checkout.create') }}" class="att-btn att-btn-ghost">Checkout</a>
            @endunless
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

    <div class="att-filter-wrap att-card">
        <div class="att-card-title">Filter Attendance</div>
        <div class="att-card-sub">{{ $selfServiceMode ? 'Review your attendance by date, status, or login timing.' : 'Filter by employee, employee ID, date, status, or login timing.' }}</div>

        <form method="GET" action="{{ route('attendance.index') }}" class="att-filter-form" style="margin-top:16px;">
            @unless($selfServiceMode)
            <div class="att-field">
                <label class="att-label">Employee</label>
                <input type="text" name="employee_name" class="att-input" value="{{ request('employee_name') }}" placeholder="Search employee name">
            </div>
            <div class="att-field">
                <label class="att-label">Employee ID</label>
                <input type="text" name="employee_id" class="att-input" value="{{ request('employee_id') }}" placeholder="Search employee ID">
            </div>
            @endunless
            <div class="att-field">
                <label class="att-label">Date</label>
                <input type="date" name="attendance_date" class="att-input" value="{{ request('attendance_date', $selectedDate->format('Y-m-d')) }}">
            </div>
            <div class="att-field">
                <label class="att-label">Status</label>
                <select name="status" class="att-select">
                    <option value="">All Status</option>
                    <option value="present" @selected(request('status') === 'present')>Present</option>
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
            <div class="att-field">
                <label class="att-label">Attendee Type</label>
                <select name="attendee_type" class="att-select">
                    <option value="">All Types</option>
                    <option value="employee" @selected(request('attendee_type') === 'employee')>Employees</option>
                    <option value="intern" @selected(request('attendee_type') === 'intern')>Interns</option>
                </select>
            </div>
            <div class="att-actions">
                <button type="submit" class="att-btn att-btn-primary">Apply Filter</button>
                @unless($selfServiceMode)
                    <button type="submit" formaction="{{ route('attendance.export') }}" class="att-btn">Export Excel</button>
                @endunless
                @if(request()->hasAny(['employee_name', 'employee_id', 'attendance_date', 'status', 'login_timing', 'attendee_type']))
                    <a href="{{ route('attendance.index') }}" class="att-btn">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="att-table-wrap att-card">
        <div class="att-meta-row">
            <div>
                <div class="att-card-title">{{ $selfServiceMode ? 'Your Attendance Details' : 'Attendance Details' }}</div>
                <div class="att-card-sub">{{ $attendances->total() }} record(s) matched your filters.</div>
            </div>
            <div class="att-note">{{ $selfServiceMode ? 'Default view shows your attendance for the current day.' : 'Default view shows the current day\'s attendance.' }}</div>
        </div>

        @if($attendances->isEmpty())
            <div class="att-empty">No attendance records found for the selected filters.</div>
        @else
            <div style="overflow-x:auto; margin-top:16px;">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Login</th>
                            <th>Logout</th>
                            <th>Working Hours</th>
                            <th>Location</th>
                            <th>Attendance Captured Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendances as $attendance)
                            <tr class="{{ $attendance['attendee_type'] === 'intern' ? 'att-row-intern' : '' }}">
                                <td>
                                    <div class="att-thumb-row">
                                        @if($attendance['attendance_photo_url'])
                                            <img src="{{ $attendance['attendance_photo_url'] }}" alt="{{ $attendance['employee_name'] }}" class="att-photo">
                                        @endif
                                        <div>
                                            <div class="att-cell-title">{{ $attendance['employee_name'] }}</div>
                                            <div class="att-cell-sub">
                                                ID: {{ $attendance['employee_id'] ?: 'N/A' }}
                                                <span class="att-chip att-chip-{{ $attendance['attendee_type'] }}">{{ ucfirst($attendance['attendee_type']) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="att-cell-title">{{ \Carbon\Carbon::parse($attendance['attendance_date'])->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <span class="att-chip att-chip-{{ $attendance['attendance_status'] }}">{{ $attendance['attendance_status'] }}</span>
                                </td>
                                <td>
                                    @if($attendance['login_time'])
                                        <div class="att-cell-title">{{ \Carbon\Carbon::createFromFormat('H:i:s', $attendance['login_time'])->format('h:i A') }}</div>
                                        <div class="att-cell-sub">
                                            <span class="att-chip att-chip-{{ $attendance['login_timing'] }}">{{ str_replace('-', ' ', $attendance['login_timing']) }}</span>
                                        </div>
                                    @else
                                        <div class="att-cell-sub">No login</div>
                                    @endif
                                </td>
                                <td>
                                    @if($attendance['logout_time'])
                                        <div class="att-cell-title">{{ \Carbon\Carbon::createFromFormat('H:i:s', $attendance['logout_time'])->format('h:i A') }}</div>
                                    @else
                                        <div class="att-cell-sub">Not checked out</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="att-cell-title">{{ $attendance['overall_working_hours'] ?: 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="att-cell-title">{{ $attendance['login_location'] ?: 'N/A' }}</div>
                                    @if($attendance['logout_location'])
                                        <div class="att-cell-sub">Logout: {{ $attendance['logout_location'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($attendance['attendance_photo_url'])
                                        <img src="{{ $attendance['attendance_photo_url'] }}" alt="{{ $attendance['employee_name'] }} attendance photo" class="att-capture-photo">
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
@endsection
