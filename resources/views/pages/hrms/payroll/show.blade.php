@extends('layouts.app')

@section('title', 'Payroll Details - ' . $payroll->salary_month->format('F Y'))

@push('styles')
<style>
.ps-page{display:flex;flex-direction:column;min-height:100%;background:#f8fafc}
.ps-topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:16px 28px;min-height:64px;background:#fff;border-bottom:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,0.02)}
.ps-body{padding:24px 28px 36px;display:flex;flex-direction:column;gap:20px}
.ps-title{font-size:20px;font-weight:800;color:#0f172a;letter-spacing:-0.02em}
.ps-breadcrumb{margin-top:2px;color:#64748b;font-size:12px;font-weight:500}
.ps-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.02)}
.ps-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:9px 18px;border-radius:12px;border:1px solid #cbd5e1;background:#fff;color:#334155;text-decoration:none;font-size:13px;font-weight:700;transition:all .18s ease;cursor:pointer}
.ps-btn:hover{background:#f8fafc;border-color:#94a3b8;color:#0f172a;transform:translateY(-1px)}
.ps-btn-primary{background:linear-gradient(135deg,#fe5f04 0%,#ff7c30 100%);border-color:#fe5f04;color:#fff;box-shadow:0 2px 6px rgba(254,95,4,.18)}
.ps-btn-primary:hover{background:linear-gradient(135deg,#e05300 0%,#f26f22 100%);border-color:#e05300;color:#fff;box-shadow:0 4px 12px rgba(254,95,4,.25)}
.ps-btn-pdf{background:#fff;color:#dc2626;border-color:#fca5a5;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:800}
.ps-btn-pdf:hover{background:#fef2f2;border-color:#ef4444;color:#991b1b;transform:translateY(-1px)}
.ps-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.ps-stat{padding:20px;border-radius:18px;position:relative;overflow:hidden;color:#fff;border:none;box-shadow:0 10px 25px -5px rgba(0,0,0,0.08)}
.ps-stat-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,0.85)}
.ps-stat-value{margin-top:8px;font-size:26px;font-weight:900;line-height:1.1;color:#fff}
.ps-stat-sub{margin-top:6px;font-size:12px;color:rgba(255,255,255,0.75)}
.ps-stat-icon{position:absolute;top:18px;right:18px;width:38px;height:38px;border-radius:12px;background:rgba(255,255,255,0.22);display:flex;align-items:center;justify-content:center;font-size:18px;backdrop-filter:blur(4px)}
.ps-table{width:100%;border-collapse:collapse;min-width:1150px}
.ps-table th,.ps-table td{padding:14px 16px;border-bottom:1px solid #f1f5f9;text-align:left;vertical-align:middle}
.ps-table th{font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#64748b;background:#f8fafc}
.ps-table tbody tr:hover{background:#f8fafc}
.ps-badge-pf{display:inline-flex;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}
.ps-badge-esi{display:inline-flex;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;background:#fdf2f8;color:#be185d;border:1px solid #fbcfe8}
.ps-badge-none{display:inline-flex;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600;background:#f1f5f9;color:#64748b}
.ps-card-header{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:16px}
@media (max-width: 900px){
    .ps-topbar{padding:16px 20px;flex-direction:column;align-items:flex-start}
    .ps-body{padding:16px 20px 24px}
    .ps-grid{grid-template-columns:1fr 1fr}
}
@media (max-width: 600px){
    .ps-grid{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
<div class="ps-page">
    @php($selfServiceMode = $selfServiceMode ?? false)
    <div class="ps-topbar">
        <div>
            <div class="ps-title">{{ $selfServiceMode ? 'My Salary Details' : 'Payroll Run Details' }}</div>
            <div class="ps-breadcrumb">HRMS > Payroll > {{ $payroll->salary_month->format('F Y') }}</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @unless($selfServiceMode)
                <a href="{{ route('payroll.create', ['month' => $payroll->salary_month->format('Y-m')]) }}" class="ps-btn ps-btn-primary">
                    <i class="bi bi-arrow-repeat"></i> Rerun Month
                </a>
            @endunless
            <a href="{{ route('payroll.index') }}" class="ps-btn">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="ps-body">
        @if(session('success'))
            <div class="ps-card" style="border-color:#bbf7d0;background:#f0fdf4;color:#166534;font-size:13px;font-weight:700;padding:14px 18px;">
                {{ session('success') }}
            </div>
        @endif

        <div class="ps-grid">
            <div class="ps-stat" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                <div class="ps-stat-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="ps-stat-label">Salary Month</div>
                <div class="ps-stat-value">{{ $payroll->salary_month->format('M Y') }}</div>
                <div class="ps-stat-sub">Processed on {{ optional($payroll->salary_date)->format('d M Y') ?: 'Schedule' }}</div>
            </div>
            <div class="ps-stat" style="background: linear-gradient(135deg, #4c1d95 0%, #8b5cf6 100%);">
                <div class="ps-stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="ps-stat-label">Total Employees</div>
                <div class="ps-stat-value">{{ $summary['employee_count'] }}</div>
                <div class="ps-stat-sub">Active staff on sheet</div>
            </div>
            <div class="ps-stat" style="background: linear-gradient(135deg, #78350f 0%, #f59e0b 100%);">
                <div class="ps-stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="ps-stat-label">Earned Gross Total</div>
                <div class="ps-stat-value">₹ {{ number_format((float) $summary['gross_total'], 2) }}</div>
                <div class="ps-stat-sub">Ded: ₹ {{ number_format((float) $summary['deduction_total'], 2) }}</div>
            </div>
            <div class="ps-stat" style="background: linear-gradient(135deg, #064e3b 0%, #10b981 100%);">
                <div class="ps-stat-icon"><i class="bi bi-wallet2"></i></div>
                <div class="ps-stat-label">Net Payable Total</div>
                <div class="ps-stat-value">₹ {{ number_format((float) $summary['net_total'], 2) }}</div>
                <div class="ps-stat-sub">Final take-home payout</div>
            </div>
        </div>

        <div class="ps-card">
            <div class="ps-card-header">
                <div>
                    <div style="font-size:17px;font-weight:800;color:#0f172a;">{{ $selfServiceMode ? 'Your Salary Breakdown' : 'Employee Salary Slips' }}</div>
                    <div style="margin-top:3px;color:#64748b;font-size:12px;">{{ $selfServiceMode ? 'Review your attendance, earnings, deductions, and download your payslip PDF.' : 'Download individual payslip PDFs for each employee in this payroll batch.' }}</div>
                </div>
                <span style="font-size:12px;font-weight:700;color:#64748b;background:#f1f5f9;padding:6px 12px;border-radius:999px;">
                    {{ $items->count() }} Records
                </span>
            </div>

            <div style="overflow-x:auto;">
                <table class="ps-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Days Attended</th>
                            <th>Leave / LOP</th>
                            <th>Payable Days</th>
                            <th>Earned Gross</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>PF / ESI Status</th>
                            <th style="text-align:right;">Payslip PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <div style="font-weight:800;color:#0f172a;font-size:14px;">{{ $item->employee_name }}</div>
                                    <div style="font-size:11px;color:#64748b;margin-top:2px;">
                                        <span style="color:#fe5f04;font-weight:700;">{{ $item->employee_code }}</span> • {{ $item->designation ?: 'Employee' }}
                                    </div>
                                </td>
                                <td><span style="font-weight:700;color:#0f172a;">{{ number_format((float) $item->days_attended, 1) }}</span> <span style="font-size:11px;color:#94a3b8;">/ {{ (int)$item->working_days }}</span></td>
                                <td>
                                    <span style="color:#166534;font-weight:700;">{{ number_format((float) $item->leave_days, 1) }}</span> Leave / 
                                    <span style="color:#b91c1c;font-weight:700;">{{ number_format((float) $item->lop_days, 1) }}</span> LOP
                                </td>
                                <td><span style="font-weight:800;color:#0284c7;">{{ number_format((float) $item->payable_days, 1) }}</span> Days</td>
                                <td><strong style="color:#0f172a;">₹ {{ number_format((float) $item->earned_gross_salary, 2) }}</strong></td>
                                <td><span style="color:#b91c1c;font-weight:700;">- ₹ {{ number_format((float) $item->total_deductions, 2) }}</span></td>
                                <td>
                                    <div style="font-size:14px;font-weight:800;color:#047857;">₹ {{ number_format((float) $item->net_salary, 2) }}</div>
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                        @if($item->use_pf)
                                            <span class="ps-badge-pf">PF</span>
                                        @endif
                                        @if($item->use_esi)
                                            <span class="ps-badge-esi">ESI</span>
                                        @endif
                                        @if(!$item->use_pf && !$item->use_esi)
                                            <span class="ps-badge-none">Standard</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="text-align:right;">
                                    <a href="{{ route('payroll.payslip', [$payroll, $item]) }}" target="_blank" class="ps-btn ps-btn-pdf">
                                        <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

