@extends('layouts.app')

@section('title', 'Payroll Batch #PAY-' . str_pad($payroll->id, 5, '0', STR_PAD_LEFT) . ' (' . $payroll->salary_month->format('F Y') . ') - HRMS')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar {
    background: #ffffff;
    padding: 14px 28px;
    border-bottom: 1px solid #e1dee3;
    position: sticky;
    top: 0;
    z-index: 30;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.inv-body { padding: 22px 28px 36px; display: flex; flex-direction: column; gap: 20px; flex: 1; }
@media (max-width: 900px) {
    .inv-topbar { padding: 12px 20px; }
    .inv-body { padding: 16px 20px 24px; }
}
.inv-top-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.inv-nav-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.inv-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    text-decoration: none;
    transition: all .15s ease;
}
.inv-back-btn:hover {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #fe5f04;
    transform: translateX(-1px);
}
.inv-breadcrumbs {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}
.inv-breadcrumbs a {
    color: #64748b;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: color .15s;
}
.inv-breadcrumbs a:hover {
    color: #fe5f04;
}
.inv-main-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
}
.inv-identity-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}
.inv-icon-badge {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    border: 1px solid #fed7aa;
    color: #fe5f04;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(254,95,4,0.12);
}
.inv-identity-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}
.inv-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.inv-title {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.2;
    margin: 0;
}
.inv-num-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    padding: 3px 10px;
    border-radius: 8px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    cursor: default;
}
.inv-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #64748b;
    flex-wrap: wrap;
}
.inv-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.inv-meta-dot {
    color: #cbd5e1;
    font-size: 10px;
}

/* Header Action Buttons */
.inv-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.inv-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    text-decoration: none;
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #ffffff !important;
    border: none;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(254,95,4,0.18);
    transition: all .15s ease;
    line-height: 1.2;
}
.inv-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(254,95,4,0.28);
    color: #ffffff !important;
}
.inv-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    text-decoration: none;
    background: #ffffff;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    transition: all .15s ease;
    line-height: 1.2;
}
.inv-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a !important;
}
.inv-btn-pdf {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    text-decoration: none;
    background: #fef2f2;
    color: #dc2626 !important;
    border: 1px solid #fecaca;
    transition: all .15s ease;
}
.inv-btn-pdf:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #b91c1c !important;
    transform: translateY(-1px);
}
.inv-btn-view {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    text-decoration: none;
    background: #eff6ff;
    color: #2563eb !important;
    border: 1px solid #bfdbfe;
    transition: all .15s ease;
}
.inv-btn-view:hover {
    background: #dbeafe;
    border-color: #93c5fd;
    color: #1d4ed8 !important;
    transform: translateY(-1px);
}

/* Status Badges */
.acm-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11.5px; font-weight:800; white-space:nowrap; }
.status-paid { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-cycle { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

/* KPI Overview Grid */
.inv-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
}
.inv-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.02);
    transition: all .2s ease;
}
.inv-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    border-color: #cbd5e1;
}
.inv-kpi-card.highlight {
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
    border-color: #86efac;
}
.inv-kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.inv-kpi-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}
.inv-kpi-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.inv-kpi-val {
    font-size: 20px;
    font-weight: 900;
    color: #0f172a;
    margin-top: 2px;
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}
.inv-kpi-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 3px;
    font-weight: 600;
}

/* Card Container */
.ps-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.02);
}
.ps-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 16px;
}
.ps-search-box {
    position: relative;
    min-width: 260px;
}
.ps-search-box input {
    width: 100%;
    padding: 7px 12px 7px 32px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 12.5px;
    font-family: inherit;
    outline: none;
    transition: all .15s ease;
}
.ps-search-box input:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254,95,4,0.12);
}
.ps-search-box i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}

/* Table Styling */
.inv-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 12px;
    min-width: 1100px;
}
.inv-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 10px 14px;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}
.inv-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    color: #334155;
}
.inv-table tbody tr {
    transition: background-color .15s ease;
}
.inv-table tbody tr:hover {
    background-color: #f8fafc;
}
.emp-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #fff7ed, #fed7aa);
    color: #ea580c;
    font-weight: 800;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #ffedd5;
}
.tag-pf { display:inline-flex; padding:2px 7px; border-radius:4px; font-size:10.5px; font-weight:700; background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; }
.tag-esi { display:inline-flex; padding:2px 7px; border-radius:4px; font-size:10.5px; font-weight:700; background:#fdf2f8; color:#be185d; border:1px solid #fce7f3; }
.tag-std { display:inline-flex; padding:2px 7px; border-radius:4px; font-size:10.5px; font-weight:600; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }
</style>
@endpush

@section('content')
<div class="inv-page">
    @php($selfServiceMode = $selfServiceMode ?? false)
    @php($cycle = $payroll->salary_cycle ?? '26_to_25')
    @php($cycleLabel = in_array($cycle, ['1_to_30', '10_to_10'], true) ? '1 to 30 cycle' : '26 to 25 cycle')
    @php($cStart = $cycleStart ?? $payroll->salary_month->copy()->startOfMonth())
    @php($cEnd = $cycleEnd ?? $payroll->salary_month->copy()->endOfMonth())

    <!-- Topbar Navigation & Actions -->
    <div class="inv-topbar">
        <div class="inv-top-nav">
            <div class="inv-nav-left">
                <a href="{{ route('payroll.index') }}" class="inv-back-btn">
                    <i class="bi bi-arrow-left"></i> All Payroll Batches
                </a>
                <div class="inv-breadcrumbs">
                    <a href="{{ route('home') }}"><i class="bi bi-house-door"></i></a>
                    <span>/</span>
                    <a href="{{ route('payroll.index') }}">HRMS</a>
                    <span>/</span>
                    <a href="{{ route('payroll.index') }}">Payroll</a>
                    <span>/</span>
                    <span>{{ $payroll->salary_month->format('F Y') }}</span>
                </div>
            </div>
            <div class="inv-header-actions">
                @unless($selfServiceMode)
                    <a href="{{ route('payroll.create', ['month' => $payroll->salary_month->format('Y-m'), 'salary_cycle' => $payroll->salary_cycle ?? '26_to_25']) }}" class="inv-btn-primary">
                        <i class="bi bi-arrow-repeat"></i> Rerun Month Payroll
                    </a>
                @endunless
                <a href="{{ route('payroll.index') }}" class="inv-btn-secondary">
                    <i class="bi bi-view-list"></i> Payroll List
                </a>
            </div>
        </div>

        <div class="inv-main-header">
            <div class="inv-identity-wrap">
                <div class="inv-icon-badge">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="inv-identity-info">
                    <div class="inv-title-row">
                        <h1 class="inv-title">{{ $selfServiceMode ? 'My Salary Details' : 'Payroll Run Details' }}</h1>
                        <span class="inv-num-pill">#PAY-{{ str_pad($payroll->id, 5, '0', STR_PAD_LEFT) }}</span>
                        <span class="acm-badge status-paid">
                            <i class="bi bi-check-circle-fill"></i> PROCESSED
                        </span>
                        <span class="acm-badge status-cycle">
                            <i class="bi bi-arrow-repeat"></i> {{ $cycleLabel }}
                        </span>
                    </div>
                    <div class="inv-meta-row">
                        <span class="inv-meta-item">
                            <i class="bi bi-calendar3"></i> <strong>{{ $payroll->salary_month->format('F Y') }}</strong> ({{ $cStart->format('d M') }} - {{ $cEnd->format('d M') }})
                        </span>
                        <span class="inv-meta-dot">&bull;</span>
                        <span class="inv-meta-item">
                            <i class="bi bi-clock-history"></i> Issued: {{ optional($payroll->salary_date)->format('d M, Y') ?: $payroll->created_at->format('d M, Y') }}
                        </span>
                        <span class="inv-meta-dot">&bull;</span>
                        <span class="inv-meta-item">
                            <i class="bi bi-people"></i> {{ $summary['employee_count'] }} Employees Verified
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Body -->
    <div class="inv-body">
        @if(session('success'))
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;color:#166534;font-size:13px;font-weight:700;display:flex;align-items:center;gap:10px;">
                <i class="bi bi-check-circle-fill" style="font-size:16px;color:#16a34a;"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- KPI Metric Cards Grid -->
        <div class="inv-kpi-grid">
            <div class="inv-kpi-card">
                <div class="inv-kpi-icon" style="background:#eff6ff;color:#2563eb;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label">Verified Staff</span>
                    <span class="inv-kpi-val">{{ $summary['employee_count'] }}</span>
                    <span class="inv-kpi-sub">Active staff in batch</span>
                </div>
            </div>

            <div class="inv-kpi-card">
                <div class="inv-kpi-icon" style="background:#f5f3ff;color:#7c3aed;">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label">Working Days</span>
                    <span class="inv-kpi-val">{{ (int) ($payroll->total_working_days ?: 26) }} <small style="font-size:12px;font-weight:600;color:#64748b;">Days</small></span>
                    <span class="inv-kpi-sub">{{ $cStart->format('d M') }} to {{ $cEnd->format('d M') }}</span>
                </div>
            </div>

            <div class="inv-kpi-card">
                <div class="inv-kpi-icon" style="background:#fffbeb;color:#d97706;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label">Gross Earned Total</span>
                    <span class="inv-kpi-val">₹ {{ number_format((float) $summary['gross_total'], 2) }}</span>
                    <span class="inv-kpi-sub">Before statutory deductions</span>
                </div>
            </div>

            <div class="inv-kpi-card">
                <div class="inv-kpi-icon" style="background:#fef2f2;color:#dc2626;">
                    <i class="bi bi-dash-circle-fill"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label">Total Deductions</span>
                    <span class="inv-kpi-val" style="color:#b91c1c;">- ₹ {{ number_format((float) $summary['deduction_total'], 2) }}</span>
                    <span class="inv-kpi-sub">PF, ESI, PT, TDS &amp; LOP</span>
                </div>
            </div>

            <div class="inv-kpi-card highlight">
                <div class="inv-kpi-icon" style="background:#dcfce7;color:#16a34a;">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="inv-kpi-info">
                    <span class="inv-kpi-label" style="color:#15803d;">Net Payout Total</span>
                    <span class="inv-kpi-val" style="color:#15803d;">₹ {{ number_format((float) $summary['net_total'], 2) }}</span>
                    <span class="inv-kpi-sub" style="color:#166534;font-weight:700;">Final take-home salary</span>
                </div>
            </div>
        </div>

        <!-- Employee Salary Slips Table Card -->
        <div class="ps-card">
            <div class="ps-card-header">
                <div>
                    <div style="font-size:16px;font-weight:800;color:#0f172a;">
                        {{ $selfServiceMode ? 'Your Salary Breakdown' : 'Employee Salary Slips' }}
                    </div>
                    <div style="margin-top:2px;color:#64748b;font-size:12px;">
                        {{ $selfServiceMode ? 'Review your attendance, earnings, deductions, and inspect or download your payslip.' : 'Inspect breakdown or download individual payslip documents for each employee.' }}
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <div class="ps-search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="employeeSearchInput" placeholder="Search employee name or ID...">
                    </div>
                    <span style="font-size:12px;font-weight:700;color:#64748b;background:#f1f5f9;border:1px solid #e2e8f0;padding:6px 12px;border-radius:8px;">
                        <strong id="matchedCount">{{ $items->count() }}</strong> / {{ $items->count() }} Records
                    </span>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="inv-table" id="payrollItemsTable">
                    <thead>
                        <tr>
                            <th>Employee Details</th>
                            <th>Attended / Working</th>
                            <th>Leave &amp; LOP</th>
                            <th>Payable Days</th>
                            <th>Earned Gross</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr class="emp-row" data-search="{{ strtolower($item->employee_name . ' ' . $item->employee_code . ' ' . $item->designation . ' ' . ($item->branch_name ?? '')) }}">
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="emp-avatar">
                                            {{ strtoupper(substr($item->employee_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:800;color:#0f172a;font-size:13.5px;">{{ $item->employee_name }}</div>
                                            <div style="font-size:11px;color:#64748b;margin-top:2px;">
                                                <span style="color:#ea580c;font-weight:700;background:#fff7ed;padding:1px 5px;border-radius:4px;border:1px solid #fed7aa;">{{ $item->employee_code }}</span>
                                                &bull; {{ $item->designation ?: 'Employee' }}
                                                @if($item->branch_name && $item->branch_name !== '—')
                                                    &bull; <span style="color:#fe5f04;font-weight:600;">{{ $item->branch_name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight:800;color:#0f172a;font-size:13px;">{{ number_format((float) $item->days_attended, 1) }}</span>
                                    <span style="font-size:11px;color:#94a3b8;">/ {{ (int)$item->working_days }} Days</span>
                                </td>
                                <td>
                                    <span style="color:#166534;font-weight:700;">{{ number_format((float) $item->leave_days, 1) }}</span> Leave / 
                                    @if((float) ($item->timesheet_lop_days ?? 0) > 0)
                                        <span style="color:#ea580c;font-weight:700;" title="Timesheet LOP">{{ number_format((float) $item->timesheet_lop_days, 1) }} TS / </span>
                                    @endif
                                    <span style="color:#b91c1c;font-weight:700;">{{ number_format((float) $item->lop_days, 1) }}</span> LOP
                                </td>
                                <td>
                                    <span style="display:inline-flex;padding:3px 9px;border-radius:6px;background:#e0f2fe;color:#0284c7;font-weight:800;font-size:12px;border:1px solid #bae6fd;">
                                        {{ number_format((float) $item->payable_days, 1) }} Days
                                    </span>
                                </td>
                                <td>
                                    <strong style="color:#0f172a;font-size:13px;">₹ {{ number_format((float) $item->earned_gross_salary, 2) }}</strong>
                                </td>
                                <td>
                                    <span style="color:#b91c1c;font-weight:800;" title="PF: ₹{{ number_format((float) ($item->pf_employee_contribution + $item->pf_employer_contribution), 2) }} | ESI: ₹{{ number_format((float) $item->esi_employee_contribution, 2) }} | PT: ₹{{ number_format((float) $item->professional_tax, 2) }} | TDS: ₹{{ number_format((float) $item->tds_amount, 2) }}">
                                        - ₹ {{ number_format((float) $item->total_deductions, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size:14px;font-weight:900;color:#15803d;background:#dcfce7;border:1px solid #bbf7d0;display:inline-flex;padding:3px 9px;border-radius:6px;">
                                        ₹ {{ number_format((float) $item->net_salary, 2) }}
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                        @if($item->use_pf)
                                            <span class="tag-pf">PF</span>
                                        @endif
                                        @if($item->use_esi)
                                            <span class="tag-esi">ESI</span>
                                        @endif
                                        @if(!$item->use_pf && !$item->use_esi)
                                            <span class="tag-std">Standard</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex;align-items:center;gap:6px;">
                                        <a href="{{ route('payroll.payslip', [$payroll, $item]) }}" class="inv-btn-view" title="Inspect Interactive Payslip View">
                                            <i class="bi bi-eye"></i> View Slip
                                        </a>
                                        <a href="{{ route('payroll.payslip', [$payroll, $item]) }}?format=pdf" target="_blank" class="inv-btn-pdf" title="Download PDF">
                                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="noMatchMessage" style="display:none;text-align:center;padding:32px 16px;color:#64748b;font-size:13px;font-weight:600;">
                <i class="bi bi-search" style="font-size:24px;color:#cbd5e1;display:block;margin-bottom:8px;"></i>
                No matching employee salary records found.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const searchInput = document.getElementById('employeeSearchInput');
    const table = document.getElementById('payrollItemsTable');
    const rows = table.querySelectorAll('tbody tr.emp-row');
    const countEl = document.getElementById('matchedCount');
    const noMatch = document.getElementById('noMatchMessage');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let visible = 0;

            rows.forEach(function(row) {
                const text = row.getAttribute('data-search') || '';
                if (!query || text.includes(query)) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countEl) countEl.textContent = visible;
            if (noMatch) noMatch.style.display = visible === 0 ? 'block' : 'none';
        });
    }
})();
</script>
@endpush
@endsection
