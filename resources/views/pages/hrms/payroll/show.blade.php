@extends('layouts.app')

@section('title', 'Payroll Details')

@push('styles')
<style>
.ps-page{display:flex;flex-direction:column;min-height:100%;background:#f4f5f7}
.ps-topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:0 28px;min-height:60px;background:#fff;border-bottom:1px solid #e1dee3}
.ps-body{padding:22px 28px 34px;display:flex;flex-direction:column;gap:16px}
.ps-title{font-size:18px;font-weight:800;color:#121212}
.ps-breadcrumb{margin-top:2px;color:#9e9e9e;font-size:12px}
.ps-card{background:#fff;border:1px solid #e1dee3;border-radius:16px;padding:20px}
.ps-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:10px;border:1px solid #e1dee3;background:#fff;color:#121212;text-decoration:none;font-size:13px;font-weight:700}
.ps-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.ps-stat{padding:16px;border:1px solid #ece8e4;border-radius:14px;background:#faf8f6}
.ps-stat-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#909090}
.ps-stat-value{margin-top:8px;font-size:26px;font-weight:800;color:#121212}
.ps-table{width:100%;border-collapse:collapse;min-width:1200px}
.ps-table th,.ps-table td{padding:14px 16px;border-bottom:1px solid #f0eef2;text-align:left}
.ps-table th{font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#9e9e9e;background:#fafafa}
@media (max-width: 900px){
    .ps-topbar{padding:16px 20px;flex-direction:column;align-items:flex-start}
    .ps-body{padding:16px 20px 24px}
    .ps-grid{grid-template-columns:1fr 1fr}
}
</style>
@endpush

@section('content')
<div class="ps-page">
    @php($selfServiceMode = $selfServiceMode ?? false)
    <div class="ps-topbar">
        <div>
            <div class="ps-title">{{ $selfServiceMode ? 'My Salary Details' : 'Payroll Details' }}</div>
            <div class="ps-breadcrumb">HRMS > Payroll > {{ $payroll->salary_month->format('F Y') }}</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            @unless($selfServiceMode)
                <a href="{{ route('payroll.create', ['month' => $payroll->salary_month->format('Y-m')]) }}" class="ps-btn">Rerun Month</a>
            @endunless
            <a href="{{ route('payroll.index') }}" class="ps-btn">Back</a>
        </div>
    </div>

    <div class="ps-body">
        @if(session('success'))
            <div class="ps-card" style="border-color:#bbf7d0;background:#f0fdf4;color:#166534;font-size:13px;font-weight:700;">
                {{ session('success') }}
            </div>
        @endif

        <div class="ps-grid">
            <div class="ps-stat"><div class="ps-stat-label">Month</div><div class="ps-stat-value">{{ $payroll->salary_month->format('M Y') }}</div></div>
            <div class="ps-stat"><div class="ps-stat-label">Employees</div><div class="ps-stat-value">{{ $summary['employee_count'] }}</div></div>
            <div class="ps-stat"><div class="ps-stat-label">Gross Total</div><div class="ps-stat-value">Rs {{ number_format((float) $summary['gross_total'], 2) }}</div></div>
            <div class="ps-stat"><div class="ps-stat-label">Net Total</div><div class="ps-stat-value">Rs {{ number_format((float) $summary['net_total'], 2) }}</div></div>
        </div>

        <div class="ps-card">
            <div style="font-size:16px;font-weight:700;color:#121212;">{{ $selfServiceMode ? 'Your Payslip' : 'Employee Payslips' }}</div>
            <div style="margin-top:4px;color:#9e9e9e;font-size:12px;">{{ $selfServiceMode ? 'Review your salary breakdown and download your payslip PDF.' : 'Download the payslip PDF for each employee in this payroll run.' }}</div>

            <div style="overflow-x:auto;margin-top:16px;">
                <table class="ps-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Present</th>
                            <th>Leave</th>
                            <th>LOP</th>
                            <th>Gross</th>
                            <th>Deductions</th>
                            <th>Net</th>
                            <th>PF / ESI</th>
                            <th>Payslip</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <div style="font-weight:700;color:#121212;">{{ $item->employee_name }}</div>
                                    <div style="font-size:11px;color:#8a8a8a;">{{ $item->employee_code }} | {{ $item->designation ?: 'Employee' }}</div>
                                </td>
                                <td>{{ number_format((float) $item->days_attended, 2) }}</td>
                                <td>{{ number_format((float) $item->leave_days, 2) }}</td>
                                <td>{{ number_format((float) $item->lop_days, 2) }}</td>
                                <td>Rs {{ number_format((float) $item->earned_gross_salary, 2) }}</td>
                                <td>Rs {{ number_format((float) $item->total_deductions, 2) }}</td>
                                <td><strong>Rs {{ number_format((float) $item->net_salary, 2) }}</strong></td>
                                <td>{{ $item->use_pf ? 'With PF' : 'Without PF' }} / {{ $item->use_esi ? 'With ESI' : 'Without ESI' }}</td>
                                <td><a href="{{ route('payroll.payslip', [$payroll, $item]) }}" target="_blank" class="ps-btn">PDF</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
