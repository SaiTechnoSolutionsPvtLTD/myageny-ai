@extends('layouts.app')

@section('title', 'Housekeeping Staff Salary & Payouts')

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

/* KPI Stats Cards */
.hk-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
.hk-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 12px rgba(18, 18, 18, 0.03); }
.hk-stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.hk-stat-icon-orange { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
.hk-stat-icon-green { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
.hk-stat-icon-blue { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
.hk-stat-icon-purple { background: #faf5ff; color: #9333ea; border: 1px solid #f3e8ff; }
.hk-stat-label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
.hk-stat-value { font-size: 24px; font-weight: 800; color: #111827; margin-top: 4px; }

.hk-card { padding: 24px; }
.hk-card-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.hk-card-title { font-size: 18px; font-weight: 800; color: #111827; margin: 0; }
.hk-card-sub { margin-top: 4px; font-size: 13px; color: #6b7280; }

.hk-filter-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; background: #fff7ed; padding: 14px 18px; border-radius: 16px; border: 1px solid #ffedd5; }
.hk-input, .hk-select, .hk-textarea { padding: 10px 14px; border-radius: 12px; border: 1px solid #d1d5db; background: #fff; color: #111827; font-size: 13px; outline: none; }
.hk-input:focus, .hk-select:focus, .hk-textarea:focus { border-color: #fe5f04; box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.15); }

.hk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 700; border: 1px solid transparent; cursor: pointer; transition: all .2s; }
.hk-btn-primary { background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #fff; box-shadow: 0 4px 12px rgba(254, 95, 4, 0.3); }
.hk-btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
.hk-btn-success { background: #10b981; color: #fff; }
.hk-btn-success:hover { background: #059669; }
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

.hk-emp-cell { display: flex; align-items: center; gap: 12px; }
.hk-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #fff1e8 0%, #fed7aa 100%); color: #ea580c; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }
.hk-emp-name { font-weight: 700; color: #0f172a; font-size: 14px; }
.hk-emp-sub { font-size: 12px; color: #64748b; margin-top: 2px; }

.badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
.badge-paid { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.badge-partial { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }

.alert-success { padding: 14px 18px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; border-radius: 12px; margin-bottom: 16px; font-weight: 600; font-size: 13px; }
.alert-danger { padding: 14px 18px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 12px; margin-bottom: 16px; font-weight: 600; font-size: 13px; }
.empty-state { padding: 36px 18px; text-align: center; color: #9ca3af; font-size: 14px; }
.hk-pagination-wrap { margin-top: 16px; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; background: #fff; box-shadow: 0 2px 6px rgba(18, 18, 18, 0.02); }

/* Modal */
.hk-modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px; opacity: 0; transition: opacity 0.2s ease; }
.hk-modal-overlay.active { display: flex; opacity: 1; }
.hk-modal { background: #fff; width: 100%; max-width: 600px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); overflow: hidden; transform: scale(0.96); transition: transform 0.2s ease; }
.hk-modal-overlay.active .hk-modal { transform: scale(1); }
.hk-modal-header { padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fff8f1; }
.hk-modal-title { font-size: 17px; font-weight: 800; color: #0f172a; margin: 0; }
.hk-modal-close { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; padding: 0; }
.hk-modal-close:hover { color: #0f172a; }
.hk-modal-body { padding: 24px; max-height: 75vh; overflow-y: auto; }
.hk-modal-footer { padding: 16px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; }
.hk-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.hk-form-group { display: flex; flex-direction: column; gap: 6px; }
.hk-label { font-size: 12px; font-weight: 700; color: #475569; }
.hk-static-box { padding: 10px 14px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 13px; font-weight: 700; color: #0f172a; }
</style>
@endpush

@section('content')
<main class="hk-page">
    <div class="hk-shell">
        <section class="hk-hero">
            <div class="hk-kicker">HRMS • House Keeping</div>
            <h1 class="hk-title">Housekeeping Salary & Payouts</h1>
            <p class="hk-subtitle">Track monthly salary disbursements, days worked/attended, bonuses, deductions, and payment status for housekeeping staff.</p>

            <div class="hk-nav-tabs">
                <a href="{{ route('house-keeping.index') }}" class="hk-nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z"></path><path d="M3 9h18"></path><path d="M9 21V9"></path></svg>
                    Cleaning Sheet
                </a>
                <a href="{{ route('house-keeping.employees.index') }}" class="hk-nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Employee Entry
                </a>
                <a href="{{ route('house-keeping.attendances.index') }}" class="hk-nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Attendance
                </a>
                <a href="{{ route('house-keeping.salaries.index') }}" class="hk-nav-btn active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                    Salary / Payouts
                </a>
                <a href="{{ route('hrms.dashboard') }}" class="hk-nav-btn">Back to HRMS</a>
            </div>
        </section>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert-danger">
                <ul style="margin:0; padding-left: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- KPI Summary Cards -->
        <div class="hk-stats-grid">
            <div class="hk-stat-card">
                <div class="hk-stat-icon hk-stat-icon-orange">👥</div>
                <div>
                    <div class="hk-stat-label">Active Staff</div>
                    <div class="hk-stat-value">{{ $totalStaff }}</div>
                </div>
            </div>
            <div class="hk-stat-card">
                <div class="hk-stat-icon hk-stat-icon-blue">💼</div>
                <div>
                    <div class="hk-stat-label">Total Monthly Base Pay</div>
                    <div class="hk-stat-value">₹{{ number_format($totalBasePay, 2) }}</div>
                </div>
            </div>
            <div class="hk-stat-card">
                <div class="hk-stat-icon hk-stat-icon-green">💰</div>
                <div>
                    <div class="hk-stat-label">Total Disbursed ({{ \Carbon\Carbon::parse($selectedMonth.'-01')->format('M Y') }})</div>
                    <div class="hk-stat-value">₹{{ number_format($totalDisbursed, 2) }}</div>
                </div>
            </div>
            <div class="hk-stat-card">
                <div class="hk-stat-icon hk-stat-icon-purple">✅</div>
                <div>
                    <div class="hk-stat-label">Paid Staff Count</div>
                    <div class="hk-stat-value">{{ $totalPaidCount }} / {{ $totalStaff }}</div>
                </div>
            </div>
        </div>

        <!-- Monthly Salary Sheet Section -->
        <section class="hk-card">
            <div class="hk-card-header">
                <div>
                    <h2 class="hk-card-title">Salary Payout Sheet - {{ \Carbon\Carbon::parse($selectedMonth.'-01')->format('F Y') }}</h2>
                    <p class="hk-card-sub">Review working days, days attended, calculated salary based on attendance, and register monthly payment</p>
                </div>

                <form method="GET" action="{{ route('house-keeping.salaries.index') }}" class="hk-filter-bar">
                    <label style="font-size: 12px; font-weight: 700; color: #9a3412;">Select Month:</label>
                    <input type="month" name="month" value="{{ $selectedMonth }}" class="hk-input">
                    <button type="submit" class="hk-btn hk-btn-primary hk-btn-sm">Apply Month</button>
                    @if(request()->filled('month'))
                        <a href="{{ route('house-keeping.salaries.index') }}" class="hk-btn hk-btn-ghost hk-btn-sm">Reset</a>
                    @endif
                </form>
            </div>

            <div class="hk-table-wrap">
                <table class="hk-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">S.No.</th>
                            <th>Staff Details</th>
                            <th>Base Salary</th>
                            <th>Month Days</th>
                            <th>Present Days</th>
                            <th>Absent Days</th>
                            <th>Calculated Pay</th>
                            <th>Actual Paid</th>
                            <th>Status</th>
                            <th style="width: 140px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employeeData as $index => $row)
                            @php
                                $rec = $row->salary_record;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="hk-emp-cell">
                                        <div class="hk-avatar">{{ strtoupper(substr($row->employee->name, 0, 1)) }}</div>
                                        <div>
                                            <div class="hk-emp-name">{{ $row->employee->name }}</div>
                                            <div class="hk-emp-sub">ID: #HK-{{ str_pad($row->employee->id, 3, '0', STR_PAD_LEFT) }} • {{ $row->employee->mobile_number ?: 'No Phone' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><strong>₹{{ number_format($row->base_salary, 2) }}</strong></td>
                                <td><span style="font-weight: 700; color: #475569;">{{ $row->working_days }} days</span></td>
                                <td>
                                    <span class="badge badge-paid">{{ $row->present_days }} Days</span>
                                </td>
                                <td>
                                    <span class="badge {{ $row->absent_days > 0 ? 'badge-pending' : '' }}">{{ $row->absent_days }} Days</span>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #0f172a;">₹{{ number_format($row->calculated_salary, 2) }}</span>
                                    <div style="font-size: 11px; color: #64748b;">(rate: ₹{{ number_format($row->working_days > 0 ? $row->base_salary / $row->working_days : 0, 1) }}/d)</div>
                                </td>
                                <td>
                                    @if($rec)
                                        <strong style="color: #059669;">₹{{ number_format($rec->paid_amount, 2) }}</strong>
                                        <div style="font-size: 11px; color: #64748b;">via {{ $rec->payment_mode }} ({{ $rec->payment_date ? $rec->payment_date->format('d-m-Y') : '-' }})</div>
                                    @else
                                        <span style="color: #94a3b8;">Not Paid</span>
                                    @endif
                                </td>
                                <td>
                                    @if($rec && $rec->payment_status === 'Paid')
                                        <span class="badge badge-paid">Paid</span>
                                    @elseif($rec && $rec->payment_status === 'Partial')
                                        <span class="badge badge-partial">Partial</span>
                                    @else
                                        <span class="badge badge-pending">Pending</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" class="hk-btn {{ $rec ? 'hk-btn-ghost' : 'hk-btn-primary' }} hk-btn-sm"
                                            onclick='openSalaryModal(@json($row))'>
                                        @if($rec)
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            Update
                                        @else
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                            Pay Salary
                                        @endif
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="empty-state">No active housekeeping employees found. Add employees in Employee Entry first.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Payout Records & History Section -->
        <section class="hk-card">
            <div class="hk-card-header">
                <div>
                    <h3 class="hk-card-title">Salary Payout History & Records</h3>
                    <p class="hk-card-sub">Past salary payments, vouchers, and audit trail</p>
                </div>

                <form method="GET" action="{{ route('house-keeping.salaries.index') }}" class="hk-filter-bar" style="background: #f9fafb; border-color: #e5e7eb;">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <select name="filter_employee_id" class="hk-select" style="min-width: 180px;">
                        <option value="">All Staff</option>
                        @foreach($allEmployees as $emp)
                            <option value="{{ $emp->id }}" {{ request('filter_employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>

                    <input type="month" name="filter_month" value="{{ request('filter_month') }}" class="hk-input">

                    <button type="submit" class="hk-btn hk-btn-primary hk-btn-sm">Filter Records</button>
                    @if(request()->filled('filter_employee_id') || request()->filled('filter_month'))
                        <a href="{{ route('house-keeping.salaries.index', ['month' => $selectedMonth]) }}" class="hk-btn hk-btn-ghost hk-btn-sm">Reset</a>
                    @endif
                </form>
            </div>

            <div class="hk-table-wrap">
                <table class="hk-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">S.No.</th>
                            <th>Month</th>
                            <th>Employee</th>
                            <th>Days Worked</th>
                            <th>Base Pay</th>
                            <th>Bonus</th>
                            <th>Deduction</th>
                            <th>Final Paid</th>
                            <th>Payment Date & Mode</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th style="width: 80px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salaryLogs as $index => $log)
                            <tr>
                                <td>{{ $salaryLogs->firstItem() + $index }}</td>
                                <td><strong>{{ \Carbon\Carbon::parse($log->salary_month.'-01')->format('M Y') }}</strong></td>
                                <td>
                                    <strong>{{ $log->employee ? $log->employee->name : 'Deleted Staff' }}</strong>
                                    <div style="font-size: 11px; color: #64748b;">#HK-{{ str_pad($log->house_keeping_employee_id, 3, '0', STR_PAD_LEFT) }}</div>
                                </td>
                                <td>{{ $log->present_days }} / {{ $log->working_days }} days</td>
                                <td>₹{{ number_format($log->base_salary, 2) }}</td>
                                <td>{{ $log->bonus > 0 ? '+₹'.number_format($log->bonus, 2) : '-' }}</td>
                                <td>{{ $log->deductions > 0 ? '-₹'.number_format($log->deductions, 2) : '-' }}</td>
                                <td><strong style="color: #059669; font-size: 14px;">₹{{ number_format($log->paid_amount, 2) }}</strong></td>
                                <td>
                                    <div><strong>{{ $log->payment_date ? $log->payment_date->format('d-m-Y') : '-' }}</strong></div>
                                    <div style="font-size: 11px; color: #64748b;">{{ $log->payment_mode }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $log->payment_status === 'Paid' ? 'badge-paid' : ($log->payment_status === 'Partial' ? 'badge-partial' : 'badge-pending') }}">
                                        {{ $log->payment_status }}
                                    </span>
                                </td>
                                <td style="max-width: 180px; font-size: 12px; color: #475569;">{{ $log->notes ?: '-' }}</td>
                                <td style="text-align: right;">
                                    <form action="{{ route('house-keeping.salaries.destroy', $log) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this salary record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="hk-btn hk-btn-danger hk-btn-sm" title="Delete record">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="empty-state">No salary payout logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($salaryLogs->hasPages())
                <div class="hk-pagination-wrap">
                    @include('partials.table-pagination', ['paginator' => $salaryLogs])
                </div>
            @endif
        </section>
    </div>
</main>

<!-- Pay / Update Salary Modal -->
<div class="hk-modal-overlay" id="salaryModal" onclick="handleOverlayClick(event, 'salaryModal')">
    <div class="hk-modal">
        <div class="hk-modal-header">
            <h3 class="hk-modal-title" id="salaryModalTitle">Record Housekeeping Salary Payout</h3>
            <button type="button" class="hk-modal-close" onclick="closeSalaryModal()">&times;</button>
        </div>
        <form id="salaryForm" method="POST" action="{{ route('house-keeping.salaries.store') }}">
            @csrf
            <input type="hidden" name="house_keeping_employee_id" id="modal_employee_id">
            <input type="hidden" name="salary_month" id="modal_salary_month">
            <input type="hidden" name="base_salary" id="modal_base_salary">
            <input type="hidden" name="working_days" id="modal_working_days">
            <input type="hidden" name="present_days" id="modal_present_days">
            <input type="hidden" name="absent_days" id="modal_absent_days">
            <input type="hidden" name="calculated_salary" id="modal_calculated_salary">

            <div class="hk-modal-body">
                <div class="hk-form-grid" style="margin-bottom: 16px;">
                    <div class="hk-form-group">
                        <label class="hk-label">Staff Name</label>
                        <div class="hk-static-box" id="modal_display_name">-</div>
                    </div>
                    <div class="hk-form-group">
                        <label class="hk-label">Salary Month</label>
                        <div class="hk-static-box" id="modal_display_month">-</div>
                    </div>
                </div>

                <div class="hk-form-grid" style="margin-bottom: 16px; background: #fff7ed; padding: 14px; border-radius: 14px; border: 1px solid #fed7aa;">
                    <div class="hk-form-group">
                        <label class="hk-label" style="color: #9a3412;">Monthly Base Salary</label>
                        <div style="font-size: 16px; font-weight: 800; color: #9a3412;" id="modal_display_base">₹0.00</div>
                    </div>
                    <div class="hk-form-group">
                        <label class="hk-label" style="color: #9a3412;">Attendance Days</label>
                        <div style="font-size: 14px; font-weight: 700; color: #111827;" id="modal_display_attendance">-</div>
                    </div>
                    <div class="hk-form-group" style="grid-column: span 2;">
                        <label class="hk-label" style="color: #9a3412;">Attendance-Calculated Salary</label>
                        <div style="font-size: 18px; font-weight: 800; color: #047857;" id="modal_display_calc">₹0.00</div>
                    </div>
                </div>

                <div class="hk-form-grid" style="margin-bottom: 16px;">
                    <div class="hk-form-group">
                        <label class="hk-label">Bonus / Incentive (₹)</label>
                        <input type="number" step="0.01" min="0" name="bonus" id="input_bonus" value="0" class="hk-input" oninput="recalcPaidAmount()">
                    </div>
                    <div class="hk-form-group">
                        <label class="hk-label">Deductions (₹)</label>
                        <input type="number" step="0.01" min="0" name="deductions" id="input_deductions" value="0" class="hk-input" oninput="recalcPaidAmount()">
                    </div>
                </div>

                <div class="hk-form-grid" style="margin-bottom: 16px;">
                    <div class="hk-form-group">
                        <label class="hk-label">Actual Paid Amount (₹) *</label>
                        <input type="number" step="0.01" min="0" name="paid_amount" id="input_paid_amount" required class="hk-input" style="font-size: 16px; font-weight: 800; color: #059669; border-color: #059669;">
                    </div>
                    <div class="hk-form-group">
                        <label class="hk-label">Payment Status *</label>
                        <select name="payment_status" id="select_payment_status" required class="hk-select">
                            <option value="Paid">Paid</option>
                            <option value="Partial">Partial</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>

                <div class="hk-form-grid" style="margin-bottom: 16px;">
                    <div class="hk-form-group">
                        <label class="hk-label">Payment Date *</label>
                        <input type="date" name="payment_date" id="input_payment_date" required value="{{ date('Y-m-d') }}" class="hk-input">
                    </div>
                    <div class="hk-form-group">
                        <label class="hk-label">Payment Mode *</label>
                        <select name="payment_mode" id="select_payment_mode" required class="hk-select">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="UPI">UPI</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="hk-form-group">
                    <label class="hk-label">Remarks / Notes</label>
                    <textarea name="notes" id="input_notes" rows="2" placeholder="e.g. Paid in cash by supervisor, voucher #123" class="hk-textarea"></textarea>
                </div>
            </div>

            <div class="hk-modal-footer">
                <button type="button" class="hk-btn hk-btn-ghost" onclick="closeSalaryModal()">Cancel</button>
                <button type="submit" class="hk-btn hk-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Save Salary Payout
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentCalculatedPay = 0;

function openSalaryModal(data) {
    document.getElementById('salaryModal').classList.add('active');
    document.getElementById('modal_employee_id').value = data.employee.id;
    document.getElementById('modal_salary_month').value = data.salary_month;
    document.getElementById('modal_base_salary').value = data.base_salary;
    document.getElementById('modal_working_days').value = data.working_days;
    document.getElementById('modal_present_days').value = data.present_days;
    document.getElementById('modal_absent_days').value = data.absent_days;
    document.getElementById('modal_calculated_salary').value = data.calculated_salary;

    currentCalculatedPay = parseFloat(data.calculated_salary) || 0;

    document.getElementById('modal_display_name').innerText = data.employee.name + ' (#HK-' + String(data.employee.id).padStart(3, '0') + ')';
    document.getElementById('modal_display_month').innerText = data.salary_month;
    document.getElementById('modal_display_base').innerText = '₹' + parseFloat(data.base_salary).toLocaleString('en-IN', { minimumFractionDigits: 2 });
    document.getElementById('modal_display_attendance').innerText = data.present_days + ' Present / ' + data.working_days + ' Days in Month (' + data.absent_days + ' Absent)';
    document.getElementById('modal_display_calc').innerText = '₹' + currentCalculatedPay.toLocaleString('en-IN', { minimumFractionDigits: 2 });

    const rec = data.salary_record;
    if (rec) {
        document.getElementById('salaryModalTitle').innerText = 'Update Housekeeping Salary Payout';
        document.getElementById('input_bonus').value = rec.bonus || 0;
        document.getElementById('input_deductions').value = rec.deductions || 0;
        document.getElementById('input_paid_amount').value = rec.paid_amount || 0;
        document.getElementById('select_payment_status').value = rec.payment_status || 'Paid';
        if (rec.payment_date) {
            document.getElementById('input_payment_date').value = String(rec.payment_date).substring(0, 10);
        }
        document.getElementById('select_payment_mode').value = rec.payment_mode || 'Cash';
        document.getElementById('input_notes').value = rec.notes || '';
    } else {
        document.getElementById('salaryModalTitle').innerText = 'Record Housekeeping Salary Payout';
        document.getElementById('input_bonus').value = 0;
        document.getElementById('input_deductions').value = 0;
        // Default paid amount to attendance-calculated salary, or base salary if full
        document.getElementById('input_paid_amount').value = (currentCalculatedPay > 0 ? currentCalculatedPay : data.base_salary);
        document.getElementById('select_payment_status').value = 'Paid';
        document.getElementById('input_payment_date').value = new Date().toISOString().substring(0, 10);
        document.getElementById('select_payment_mode').value = 'Cash';
        document.getElementById('input_notes').value = '';
    }
}

function recalcPaidAmount() {
    const bonus = parseFloat(document.getElementById('input_bonus').value) || 0;
    const deductions = parseFloat(document.getElementById('input_deductions').value) || 0;
    let net = currentCalculatedPay + bonus - deductions;
    if (net < 0) net = 0;
    document.getElementById('input_paid_amount').value = net.toFixed(2);
}

function closeSalaryModal() {
    document.getElementById('salaryModal').classList.remove('active');
}

function handleOverlayClick(event, modalId) {
    if (event.target.id === modalId) {
        document.getElementById(modalId).classList.remove('active');
    }
}
</script>
@endsection