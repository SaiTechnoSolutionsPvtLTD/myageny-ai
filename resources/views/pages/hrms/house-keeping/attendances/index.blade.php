@extends('layouts.app')

@section('title', 'Housekeeping Attendance Management')

@push('styles')
<style>
.hk-page { min-height: 100%; padding: 28px; background: linear-gradient(180deg, #f7f3ee 0%, #f3f5f8 100%); }
.hk-shell { display: flex; flex-direction: column; gap: 20px; max-width: 1500px; margin: 0 auto; }
.hk-hero, .hk-card { background: #fff; border: 1px solid #e7e2dc; border-radius: 24px; box-shadow: 0 18px 40px rgba(18,18,18,.05); }
.hk-hero { padding: 26px 28px; background: linear-gradient(135deg, #fff8f1 0%, #ffffff 62%, #f4fbff 100%); }
.hk-kicker { display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; background: #fff1e8; color: #c2410c; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.hk-title { margin: 14px 0 8px; font-size: 30px; font-weight: 800; color: #111827; }
.hk-subtitle { margin: 0; max-width: 760px; font-size: 14px; line-height: 1.7; color: #6b7280; }
.hk-nav-tabs { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; border-bottom: 2px solid #e7e2dc; padding-bottom: 12px; }
.hk-nav-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; border: 1px solid #e5ddd6; background: #fff; color: #374151; transition: all .2s; }
.hk-nav-btn:hover { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.hk-nav-btn.active { background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; border-color: transparent; box-shadow: 0 4px 14px rgba(254, 95, 4, 0.35); }

.hk-card { padding: 24px; }
.hk-card-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.hk-card-title { font-size: 18px; font-weight: 800; color: #111827; margin: 0; }
.hk-card-sub { margin-top: 4px; font-size: 13px; color: #6b7280; }

.hk-date-filter { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; background: #fff7ed; padding: 14px 18px; border-radius: 16px; border: 1px solid #ffedd5; }
.hk-input, .hk-select { padding: 10px 14px; border-radius: 12px; border: 1px solid #d1d5db; background: #fff; color: #111827; font-size: 13px; outline: none; }
.hk-input:focus, .hk-select:focus { border-color: #fe5f04; box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.15); }

.hk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; border: 1px solid transparent; cursor: pointer; transition: all .2s; }
.hk-btn-primary { background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; box-shadow: 0 4px 12px rgba(254, 95, 4, 0.3); }
.hk-btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
.hk-btn-ghost { background: #fff; color: #374151; border-color: #e5ddd6; }
.hk-btn-ghost:hover { background: #f9fafb; border-color: #d1d5db; }
.hk-btn-danger { background: #ef4444; color: #fff; }
.hk-btn-danger:hover { background: #dc2626; }
.hk-btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 8px; }

.hk-table-wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 16px; background: #fff; margin-top: 16px; }
.hk-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
.hk-table th { background: #f9fafb; padding: 14px 16px; font-weight: 700; color: #374151; border-bottom: 1px solid #e5e7eb; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; }
.hk-table td { padding: 14px 16px; border-bottom: 1px solid #f3f4f6; color: #1f2937; vertical-align: middle; }
.hk-table tr:last-child td { border-bottom: none; }
.hk-table tr:hover td { background: #fffdfa; }

.badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
.badge-present { background: #dcfce7; color: #15803d; }
.badge-absent { background: #fee2e2; color: #b91c1c; }
.badge-halfday { background: #fef3c7; color: #92400e; }

.alert-success { padding: 14px 18px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; border-radius: 12px; margin-bottom: 16px; font-weight: 600; font-size: 13px; }
.alert-danger { padding: 14px 18px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 12px; margin-bottom: 16px; font-weight: 600; font-size: 13px; }
.empty-state { padding: 36px 18px; text-align: center; color: #9ca3af; font-size: 14px; }
</style>
@endpush

@section('content')
<main class="hk-page">
    <div class="hk-shell">
        <section class="hk-hero">
            <div class="hk-kicker">HRMS House Keeping</div>
            <h1 class="hk-title">Housekeeping Attendance</h1>
            <p class="hk-subtitle">Record and manage daily Login Time, Logout Time, and Remarks for active housekeeping staff.</p>

            <div class="hk-nav-tabs">
                <a href="{{ route('house-keeping.index') }}" class="hk-nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z"></path><path d="M3 9h18"></path><path d="M9 21V9"></path></svg>
                    Cleaning Sheet
                </a>
                <a href="{{ route('house-keeping.employees.index') }}" class="hk-nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Employee Entry
                </a>
                <a href="{{ route('house-keeping.attendances.index') }}" class="hk-nav-btn active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Attendance
                </a>
                <a href="{{ route('hrms.dashboard') }}" class="hk-nav-btn">Back to HRMS</a>
            </div>
        </section>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert-danger">
                <ul style="margin:0; padding-left: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Active Employees Daily Attendance Form -->
        <section class="hk-card">
            <div class="hk-card-header">
                <div>
                    <h3 class="hk-card-title">Daily Attendance Entry</h3>
                    <p class="hk-card-sub">Showing <strong>Active</strong> Housekeeping Employees for date: <strong>{{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</strong></p>
                </div>

                <form method="GET" action="{{ route('house-keeping.attendances.index') }}" class="hk-date-filter">
                    <label style="font-size: 12px; font-weight: 700; color: #9a3412;">Select Attendance Date:</label>
                    <input type="date" name="date" value="{{ $selectedDate }}" class="hk-input" onchange="this.form.submit()">
                    <button type="submit" class="hk-btn hk-btn-primary hk-btn-sm">Change Date</button>
                </form>
            </div>

            @if($activeEmployees->isEmpty())
                <div class="empty-state">
                    No active housekeeping employees found. 
                    <a href="{{ route('house-keeping.employees.index') }}" style="color: #fe5f04; font-weight: 700; text-decoration: underline;">
                        Click here to add or activate housekeeping employees.
                    </a>
                </div>
            @else
                <form method="POST" action="{{ route('house-keeping.attendances.store') }}">
                    @csrf
                    <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">

                    <div class="hk-table-wrap">
                        <table class="hk-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">S.No.</th>
                                    <th style="width: 220px;">Employee Name</th>
                                    <th style="width: 140px;">Status</th>
                                    <th style="width: 160px;">Login Time</th>
                                    <th style="width: 160px;">Logout Time</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeEmployees as $index => $emp)
                                    @php
                                        $att = $existingAttendances->get($emp->id);
                                        $loginVal = $att ? $att->login_time : '';
                                        $logoutVal = $att ? $att->logout_time : '';
                                        $statusVal = $att ? $att->status : 'Present';
                                        $remarksVal = $att ? $att->remarks : '';
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $emp->name }}</strong>
                                            @if($emp->mobile_number)
                                                <div style="font-size: 11px; color: #6b7280;">📱 {{ $emp->mobile_number }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <select name="attendances[{{ $emp->id }}][status]" class="hk-select" style="padding: 6px 10px;">
                                                <option value="Present" {{ $statusVal === 'Present' ? 'selected' : '' }}>Present</option>
                                                <option value="Absent" {{ $statusVal === 'Absent' ? 'selected' : '' }}>Absent</option>
                                                <option value="Half Day" {{ $statusVal === 'Half Day' ? 'selected' : '' }}>Half Day</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" name="attendances[{{ $emp->id }}][login_time]" value="{{ $loginVal }}" class="hk-input" style="padding: 6px 10px;">
                                        </td>
                                        <td>
                                            <input type="time" name="attendances[{{ $emp->id }}][logout_time]" value="{{ $logoutVal }}" class="hk-input" style="padding: 6px 10px;">
                                        </td>
                                        <td>
                                            <input type="text" name="attendances[{{ $emp->id }}][remarks]" value="{{ $remarksVal }}" placeholder="Enter remarks..." class="hk-input" style="padding: 6px 10px;">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                        <button type="submit" class="hk-btn hk-btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            Save Attendance
                        </button>
                    </div>
                </form>
            @endif
        </section>

        <!-- Attendance Logs & History Table -->
        <section class="hk-card">
            <div class="hk-card-header">
                <div>
                    <h3 class="hk-card-title">Attendance History & Logs</h3>
                    <p class="hk-card-sub">View past attendance entries and records</p>
                </div>

                <form method="GET" action="{{ route('house-keeping.attendances.index') }}" class="hk-date-filter" style="background: #f9fafb; border-color: #e5e7eb;">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <select name="filter_employee_id" class="hk-select" style="min-width: 180px;">
                        <option value="">All Employees</option>
                        @foreach($allEmployees as $emp)
                            <option value="{{ $emp->id }}" {{ request('filter_employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->status }})
                            </option>
                        @endforeach
                    </select>

                    <input type="month" name="filter_month" value="{{ request('filter_month') }}" class="hk-input">

                    <button type="submit" class="hk-btn hk-btn-primary hk-btn-sm">Filter Logs</button>
                    @if(request()->filled('filter_employee_id') || request()->filled('filter_month'))
                        <a href="{{ route('house-keeping.attendances.index', ['date' => $selectedDate]) }}" class="hk-btn hk-btn-ghost hk-btn-sm">Reset Filter</a>
                    @endif
                </form>
            </div>

            <div class="hk-table-wrap">
                <table class="hk-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">S.No.</th>
                            <th style="width: 120px;">Date</th>
                            <th>Employee Name</th>
                            <th>Status</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Remarks</th>
                            <th style="width: 80px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceLogs as $index => $log)
                            <tr>
                                <td>{{ $attendanceLogs->firstItem() + $index }}</td>
                                <td><strong>{{ $log->attendance_date ? $log->attendance_date->format('d-m-Y') : '-' }}</strong></td>
                                <td>{{ $log->employee ? $log->employee->name : 'Deleted Employee' }}</td>
                                <td>
                                    @if($log->status === 'Present')
                                        <span class="badge badge-present">Present</span>
                                    @elseif($log->status === 'Absent')
                                        <span class="badge badge-absent">Absent</span>
                                    @else
                                        <span class="badge badge-halfday">{{ $log->status }}</span>
                                    @endif
                                </td>
                                <td>{{ $log->login_time ?: '-' }}</td>
                                <td>{{ $log->logout_time ?: '-' }}</td>
                                <td>{{ $log->remarks ?: '-' }}</td>
                                <td style="text-align: right;">
                                    <form action="{{ route('house-keeping.attendances.destroy', $log) }}" method="POST" onsubmit="return confirm('Delete this attendance entry?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="hk-btn hk-btn-danger hk-btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty-state">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($attendanceLogs->hasPages())
                <div style="margin-top: 20px;">
                    {{ $attendanceLogs->links() }}
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
