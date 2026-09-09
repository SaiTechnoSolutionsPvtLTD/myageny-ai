@extends('layouts.app')

@section('title', 'Payroll')

@push('styles')
<style>
.payroll-page{display:flex;flex-direction:column;min-height:100%;background:#f4f5f7}
.payroll-topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:0 28px;min-height:60px;background:#fff;border-bottom:1px solid #e1dee3}
.payroll-body{padding:22px 28px 34px;display:flex;flex-direction:column;gap:16px}
.payroll-title{font-size:18px;font-weight:800;color:#121212}
.payroll-breadcrumb{margin-top:2px;color:#9e9e9e;font-size:12px}
.payroll-actions,.payroll-filter{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
.payroll-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:10px;border:1px solid transparent;background:#fff;color:#121212;text-decoration:none;font-size:13px;font-weight:700}
.payroll-btn-primary{background:linear-gradient(135deg,#fe5f04,#ff7c30);border-color:#fe5f04;color:#fff}
.payroll-btn-ghost{border-color:#e1dee3}
.payroll-card{background:#fff;border:1px solid #e1dee3;border-radius:16px;padding:20px}
.payroll-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.payroll-stat{padding:16px;border:1px solid #ece8e4;border-radius:14px;background:#faf8f6}
.payroll-stat-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#909090}
.payroll-stat-value{margin-top:8px;font-size:28px;font-weight:800;color:#121212}
.payroll-input{height:44px;border:1px solid #e1dee3;border-radius:10px;padding:0 14px;background:#fff;color:#20222a;font-size:14px}
.payroll-table{width:100%;border-collapse:collapse}
.payroll-table th,.payroll-table td{padding:14px 16px;border-bottom:1px solid #f0eef2;text-align:left}
.payroll-table th{font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#9e9e9e;background:#fafafa}
.payroll-chip{display:inline-flex;padding:6px 10px;border-radius:999px;background:#fff4ec;color:#c25a17;font-size:12px;font-weight:700}
.payroll-empty{padding:40px 20px;text-align:center;color:#7a7f8c}
.payroll-table-dropdown{position:relative;display:inline-block}
.payroll-table-dropdown[open]{z-index:60}
.payroll-table-dropdown summary{list-style:none}
.payroll-table-dropdown summary::-webkit-details-marker{display:none}
.payroll-table-dropdown-trigger{min-width:42px;height:36px;padding:0 12px;display:inline-flex;align-items:center;justify-content:center;gap:6px;border-radius:10px;border:1px solid #e1dee3;background:#fff;color:#121212;cursor:pointer;font-size:12px;font-weight:800}
.payroll-table-dropdown[open] .payroll-table-dropdown-trigger,.payroll-table-dropdown-trigger:hover{background:#fff7ed;color:#fe5f04;border-color:#fdba74}
.payroll-table-dropdown-menu{position:absolute;right:0;top:calc(100% + 8px);min-width:150px;padding:8px;border-radius:14px;border:1px solid #ece7ec;background:#fff;box-shadow:0 16px 40px rgba(18,18,18,.15),0 4px 12px rgba(0,0,0,.08);z-index:999;display:flex;flex-direction:column;gap:6px}
.payroll-table-dropdown.dropup .payroll-table-dropdown-menu{top:auto !important;bottom:calc(100% + 8px) !important;box-shadow:0 -16px 40px rgba(18,18,18,.15),0 -4px 12px rgba(0,0,0,.08) !important}
.payroll-table-dropdown-item{width:100%;display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid transparent;border-radius:10px;background:#fff;color:#121212;text-decoration:none;font-size:13px;font-weight:700}
.payroll-table-dropdown-item:hover{background:#fff7ed;color:#fe5f04;border-color:#fed7aa}
@media (max-width: 900px){
    .payroll-topbar{padding:16px 20px;flex-direction:column;align-items:flex-start}
    .payroll-body{padding:16px 20px 24px}
    .payroll-grid{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
<div class="payroll-page">
    @php($selfServiceMode = $selfServiceMode ?? false)
    <div class="payroll-topbar">
        <div>
            <div class="payroll-title">{{ $selfServiceMode ? 'My Salary Details' : 'Payroll' }}</div>
            <div class="payroll-breadcrumb">HRMS > Payroll</div>
        </div>
        <div class="payroll-actions">
            @unless($selfServiceMode)
                <a href="{{ route('payroll.create') }}" class="payroll-btn payroll-btn-primary">Run Payroll</a>
            @endunless
            <a href="{{ route('hrms.dashboard') }}" class="payroll-btn payroll-btn-ghost">Back</a>
        </div>
    </div>

    <div class="payroll-body">
        @if(session('success'))
            <div class="payroll-card" style="border-color:#bbf7d0;background:#f0fdf4;color:#166534;font-size:13px;font-weight:700;">
                {{ session('success') }}
            </div>
        @endif

        <div class="payroll-card">
            <form method="GET" action="{{ route('payroll.index') }}" class="payroll-filter">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#444;margin-bottom:8px;">Salary Month</div>
                    <input type="month" name="month" class="payroll-input" value="{{ request('month') }}">
                </div>
                <button type="submit" class="payroll-btn payroll-btn-primary">Filter</button>
                @if(request()->filled('month'))
                    <a href="{{ route('payroll.index') }}" class="payroll-btn payroll-btn-ghost">Reset</a>
                @endif
            </form>
        </div>

        <div class="payroll-grid">
            <div class="payroll-stat">
                <div class="payroll-stat-label">Payroll Runs</div>
                <div class="payroll-stat-value">{{ $payrolls->total() }}</div>
            </div>
            <div class="payroll-stat">
                <div class="payroll-stat-label">Latest Month</div>
                <div class="payroll-stat-value" style="font-size:22px;">{{ optional($payrolls->first())->salary_month?->format('M Y') ?: 'N/A' }}</div>
            </div>
            <div class="payroll-stat">
                <div class="payroll-stat-label">{{ $selfServiceMode ? 'Latest Net Salary' : 'Latest Net Total' }}</div>
                <div class="payroll-stat-value" style="font-size:22px;">Rs {{ number_format((float) ($latestNetTotal ?? optional($payrolls->first())->net_total), 2) }}</div>
            </div>
        </div>

        <div class="payroll-card">
            <div style="font-size:16px;font-weight:700;color:#121212;">{{ $selfServiceMode ? 'My Payslips' : 'Processed Payrolls' }}</div>
            <div style="margin-top:4px;color:#9e9e9e;font-size:12px;">{{ $selfServiceMode ? 'Open any month to review your salary details and download your payslip.' : 'Open any month to download employee payslips.' }}</div>

            @if($payrolls->isEmpty())
                <div class="payroll-empty">No payroll has been generated yet.</div>
            @else
                <div style="overflow-x:auto;margin-top:16px;">
                    <table class="payroll-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Employees</th>
                                <th>Working Days</th>
                                <th>Gross</th>
                                <th>Deductions</th>
                                <th>Net</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payrolls as $payroll)
                                <tr>
                                    <td>
                                        <div style="font-weight:700;color:#121212;">{{ $payroll->salary_month->format('F Y') }}</div>
                                        <div style="font-size:11px;color:#9e9e9e;">Processed {{ optional($payroll->updated_at)->format('d M Y h:i A') }}</div>
                                    </td>
                                    <td>{{ $selfServiceMode ? 1 : $payroll->employee_count }}</td>
                                    <td><span class="payroll-chip">{{ $payroll->total_working_days }} days</span></td>
                                    <td>Rs {{ number_format((float) $payroll->gross_total, 2) }}</td>
                                    <td>Rs {{ number_format((float) $payroll->deduction_total, 2) }}</td>
                                    <td><strong>Rs {{ number_format((float) $payroll->net_total, 2) }}</strong></td>
                                    <td>
                                        <details class="payroll-table-dropdown">
                                            <summary class="payroll-table-dropdown-trigger">Actions</summary>
                                            <div class="payroll-table-dropdown-menu">
                                                <a href="{{ route('payroll.show', $payroll) }}" class="payroll-table-dropdown-item">View</a>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($payrolls->hasPages())
                    @include('partials.table-pagination', ['paginator' => $payrolls])
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
