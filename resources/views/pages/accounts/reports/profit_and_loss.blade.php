@extends('layouts.app')

@section('title', 'Profit & Loss (P & L) Report - Accounts')

@push('styles')
<style>
.pnl-page { display:flex; flex-direction:column; min-height:100%; background:#f8fafc; font-family:'Inter',sans-serif; }
.pnl-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:64px; background:#fff; border-bottom:1px solid #e2e8f0; position:sticky; top:0; z-index:30; }
.pnl-title { font-size:18px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:10px; line-height:1.2; }
.pnl-breadcrumbs { font-size:12px; color:#94a3b8; margin-top:2px; display:flex; align-items:center; gap:6px; }
.pnl-breadcrumbs a { color:#64748b; text-decoration:none; font-weight:500; }
.pnl-breadcrumbs a:hover { color:#059669; }
.pnl-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pnl-body { padding:22px 28px 38px; display:flex; flex-direction:column; gap:22px; flex:1; }

@media (max-width: 900px) {
    .pnl-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .pnl-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#059669,#10b981); color:#fff; box-shadow:0 4px 14px rgba(5,150,105,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(5,150,105,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-excel { background:#15803d; color:#fff; box-shadow:0 3px 10px rgba(21,128,61,0.25); }
.acm-btn-excel:hover { background:#166534; color:#fff; }
.acm-btn-pdf { background:#dc2626; color:#fff; box-shadow:0 3px 10px rgba(220,38,38,0.25); }
.acm-btn-pdf:hover { background:#b91c1c; color:#fff; }
.acm-btn-print { background:#0284c7; color:#fff; box-shadow:0 3px 10px rgba(2,132,199,0.25); }
.acm-btn-print:hover { background:#0369a1; color:#fff; }

/* Filter Section */
.filter-card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:18px 22px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.filter-grid { display:grid; grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) auto auto; gap:14px; align-items:flex-end; }
@media (max-width: 768px) {
    .filter-grid { grid-template-columns: 1fr; }
}
.f-label { display:block; font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px; }
.f-input { width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:13px; color:#1e293b; background:#fff; outline:none; transition:border-color .15s; }
.f-input:focus { border-color:#059669; box-shadow:0 0 0 3px rgba(5,150,105,.1); }

/* Quick Presets Pills */
.presets-wrap { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:12px; border-top:1px dashed #e2e8f0; }
.presets-title { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-right:4px; }
.preset-pill { font-size:11.5px; font-weight:600; padding:4px 11px; border-radius:6px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; cursor:pointer; text-decoration:none; transition:all .15s; }
.preset-pill:hover, .preset-pill.active { background:#ecfdf5; border-color:#a7f3d0; color:#047857; font-weight:700; }

/* KPI Cards */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(230px, 1fr)); gap:16px; }
.kpi-card { background:#fff; border-radius:14px; padding:18px 22px; border:1px solid #e2e8f0; display:flex; align-items:flex-start; justify-content:space-between; box-shadow:0 2px 6px rgba(0,0,0,.02); position:relative; overflow:hidden; }
.kpi-card::before { content:''; position:absolute; top:0; left:0; bottom:0; width:4px; }
.kpi-card.rev::before { background:#10b981; }
.kpi-card.exp::before { background:#f97316; }
.kpi-card.profit::before { background:#059669; }
.kpi-card.loss::before { background:#ef4444; }
.kpi-card.cash::before { background:#3b82f6; }

.kpi-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; display:flex; align-items:center; gap:6px; }
.kpi-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:4px; line-height:1.15; }
.kpi-sub { font-size:12px; color:#64748b; margin-top:4px; font-weight:500; }
.kpi-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:20px; }
.kpi-icon.rev { background:#ecfdf5; color:#059669; }
.kpi-icon.exp { background:#fff7ed; color:#ea580c; }
.kpi-icon.profit { background:#ecfdf5; color:#047857; }
.kpi-icon.loss { background:#fef2f2; color:#dc2626; }
.kpi-icon.cash { background:#eff6ff; color:#2563eb; }

/* Financial Statement Card & Table */
.statement-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 4px 14px rgba(15,23,42,0.03); }
.statement-head { padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; background:#ffffff; }
.statement-title { font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.statement-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.statement-badge.profit { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
.statement-badge.loss { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }

.tbl-statement { width:100%; border-collapse:collapse; font-size:13px; }
.tbl-statement th { background:#f8fafc; color:#475569; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding:12px 20px; border-bottom:1px solid #e2e8f0; }
.tbl-statement td { padding:12px 20px; border-bottom:1px solid #f1f5f9; color:#1e293b; vertical-align:middle; }

/* Statement Section Headers */
.stmt-section-row td { background:#f8fafc; font-weight:800; color:#0f172a; font-size:12px; text-transform:uppercase; letter-spacing:.04em; padding:14px 20px 8px; border-top:1px solid #e2e8f0; border-bottom:1px solid #e2e8f0; }
.stmt-subtotal-row td { background:#fafbfc; font-weight:800; color:#0f172a; border-top:1.5px solid #cbd5e1; border-bottom:1.5px solid #cbd5e1; }
.stmt-grand-row td { background:#f0fdf4; font-weight:900; font-size:15px; color:#064e3b; border-top:2px solid #059669; border-bottom:3px double #059669; padding:16px 20px; }
.stmt-grand-row.loss td { background:#fef2f2; color:#991b1b; border-top:2px solid #dc2626; border-bottom:3px double #dc2626; }

.text-right { text-align:right !important; }
.text-center { text-align:center !important; }
.pl-indent { padding-left:36px !important; }

/* Secondary Tables */
.sub-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media (max-width: 1024px) {
    .sub-grid { grid-template-columns:1fr; }
}
.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.card-top { padding:16px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; background:#fff; }
.card-title { font-size:14px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.sub-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.sub-tbl th { background:#f8fafc; color:#475569; font-weight:700; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; padding:10px 14px; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.sub-tbl td { padding:10px 14px; border-bottom:1px solid #f1f5f9; color:#334155; }
.sub-tbl tbody tr:hover td { background:#fafbfc; }

.badge-code { background:#f1f5f9; color:#475569; padding:2px 7px; border-radius:5px; font-size:10.5px; font-weight:700; font-family:monospace; }
.badge-fixed { background:#e0f2fe; color:#0369a1; padding:2px 7px; border-radius:5px; font-size:10.5px; font-weight:700; }
.badge-var { background:#fef3c7; color:#b45309; padding:2px 7px; border-radius:5px; font-size:10.5px; font-weight:700; }

.progress-pill { width:70px; height:6px; background:#e2e8f0; border-radius:999px; overflow:hidden; display:inline-block; vertical-align:middle; margin-left:6px; }
.progress-fill-pnl { height:100%; background:#059669; border-radius:999px; }

@media print {
    .pnl-topbar, .filter-card, .pnl-actions { display:none !important; }
    .pnl-page { background:#fff; }
    .pnl-body { padding:0; }
    .statement-card, .acm-card { border:none; box-shadow:none; }
}
</style>
@endpush

@section('content')
<div class="pnl-page">

    {{-- Topbar --}}
    <div class="pnl-topbar">
        <div>
            <div class="pnl-title">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 6l-9.5 9.5-5-5L1 18"/>
                    <path d="M17 6h6v6"/>
                </svg>
                <span>Profit & Loss (P & L) Statement</span>
            </div>
            <div class="pnl-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.reports.index') }}">Reports</a>
                <span>/</span>
                <span style="color:#059669; font-weight:600;">Profit & Loss Report</span>
            </div>
        </div>

        <div class="pnl-actions">
            <a href="{{ route('accounts.reports.index') }}" class="acm-btn acm-btn-secondary" title="Back to Reports Hub">
                <i class="bi bi-grid-fill"></i>
                <span>All Reports</span>
            </a>
            <a href="{{ route('accounts.reports.profit-and-loss.export-excel', request()->query()) }}" class="acm-btn acm-btn-excel" title="Download Excel Sheet">
                <i class="bi bi-file-earmark-excel-fill"></i>
                <span>Export Excel</span>
            </a>
            <a href="{{ route('accounts.reports.profit-and-loss.export-pdf', request()->query()) }}" class="acm-btn acm-btn-pdf" title="Download PDF Report">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span>Export PDF</span>
            </a>
            <button type="button" onclick="window.print()" class="acm-btn acm-btn-print" title="Print this view">
                <i class="bi bi-printer-fill"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div class="pnl-body">

        {{-- Filter Card --}}
        <div class="filter-card">
            <form method="GET" action="{{ route('accounts.reports.profit-and-loss') }}" id="pnlFilterForm">
                <div class="filter-grid">
                    <div>
                        <label class="f-label" for="date_from">Date From</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" class="f-input" required>
                    </div>

                    <div>
                        <label class="f-label" for="date_to">Date To</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" class="f-input" required>
                    </div>

                    <div>
                        <button type="submit" class="acm-btn acm-btn-primary" style="height:40px; width:100%; justify-content:center;">
                            <i class="bi bi-funnel-fill"></i>
                            <span>Generate Statement</span>
                        </button>
                    </div>

                    <div>
                        <a href="{{ route('accounts.reports.profit-and-loss') }}" class="acm-btn acm-btn-secondary" style="height:40px; justify-content:center;">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </div>

                {{-- Quick Presets --}}
                <div class="presets-wrap">
                    <span class="presets-title"><i class="bi bi-clock-history me-1"></i> Quick Presets:</span>
                    <button type="button" class="preset-pill" onclick="applyPreset('this_month')">This Month</button>
                    <button type="button" class="preset-pill" onclick="applyPreset('last_month')">Last Month</button>
                    <button type="button" class="preset-pill" onclick="applyPreset('this_quarter')">This Quarter</button>
                    <button type="button" class="preset-pill" onclick="applyPreset('this_fy')">This FY (Current)</button>
                    <button type="button" class="preset-pill" onclick="applyPreset('last_fy')">Last FY</button>
                    <button type="button" class="preset-pill" onclick="applyPreset('all_time')">All Time</button>
                </div>
            </form>
        </div>

        {{-- Executive KPI Summary --}}
        <div class="kpi-grid">
            {{-- Gross Invoiced Revenue --}}
            <div class="kpi-card rev">
                <div>
                    <div class="kpi-label">
                        <i class="bi bi-receipt" style="color:#10b981;"></i>
                        <span>Operating Revenue</span>
                    </div>
                    <div class="kpi-val" style="color:#065f46;">₹{{ number_format($netOperatingRevenue, 2) }}</div>
                    <div class="kpi-sub">
                        Gross Billed: ₹{{ number_format($grossSales, 2) }}
                        @if($totalCreditNotes > 0)
                            <span class="text-danger">(-₹{{ number_format($totalCreditNotes, 2) }} CN)</span>
                        @endif
                    </div>
                </div>
                <div class="kpi-icon rev">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>

            {{-- Total Operating Expenses --}}
            <div class="kpi-card exp">
                <div>
                    <div class="kpi-label">
                        <i class="bi bi-wallet2" style="color:#ea580c;"></i>
                        <span>Operating Expenses</span>
                    </div>
                    <div class="kpi-val" style="color:#9a3412;">₹{{ number_format($totalExpenses, 2) }}</div>
                    <div class="kpi-sub">
                        Fixed: ₹{{ number_format($totalFixedExpenses, 2) }} | Var: ₹{{ number_format($totalVariableExpenses, 2) }}
                    </div>
                </div>
                <div class="kpi-icon exp">
                    <i class="bi bi-graph-down-arrow"></i>
                </div>
            </div>

            {{-- Net Profit / Loss --}}
            <div class="kpi-card {{ $netProfit >= 0 ? 'profit' : 'loss' }}">
                <div>
                    <div class="kpi-label">
                        @if($netProfit >= 0)
                            <i class="bi bi-arrow-up-right-circle-fill" style="color:#059669;"></i>
                            <span>Net Profit</span>
                        @else
                            <i class="bi bi-arrow-down-right-circle-fill" style="color:#dc2626;"></i>
                            <span>Net Loss</span>
                        @endif
                    </div>
                    <div class="kpi-val" style="color:{{ $netProfit >= 0 ? '#047857' : '#b91c1c' }};">
                        {{ $netProfit < 0 ? '-' : '' }}₹{{ number_format(abs($netProfit), 2) }}
                    </div>
                    <div class="kpi-sub">
                        Margin: <strong style="color:{{ $netProfit >= 0 ? '#047857' : '#b91c1c' }};">{{ $profitMargin }}%</strong> of operating revenue
                    </div>
                </div>
                <div class="kpi-icon {{ $netProfit >= 0 ? 'profit' : 'loss' }}">
                    <i class="bi {{ $netProfit >= 0 ? 'bi-trophy-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                </div>
            </div>

            {{-- Cash Realized --}}
            <div class="kpi-card cash">
                <div>
                    <div class="kpi-label">
                        <i class="bi bi-bank" style="color:#2563eb;"></i>
                        <span>Realized Cash Collected</span>
                    </div>
                    <div class="kpi-val" style="color:#1e40af;">₹{{ number_format($cashRealized, 2) }}</div>
                    <div class="kpi-sub">
                        Receivables Due: ₹{{ number_format($totalBalanceDue, 2) }}
                    </div>
                </div>
                <div class="kpi-icon cash">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>

        {{-- Formal Profit & Loss Statement Table --}}
        <div class="statement-card">
            <div class="statement-head">
                <div>
                    <div class="statement-title">
                        <i class="bi bi-file-earmark-spreadsheet-fill" style="color:#059669;"></i>
                        <span>Statement of Profit & Loss</span>
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                        Period: <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</strong>
                        @if(!empty($setting->company_name))
                            &bull; {{ $setting->company_name }}
                        @endif
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="statement-badge {{ $netProfit >= 0 ? 'profit' : 'loss' }}">
                        <i class="bi {{ $netProfit >= 0 ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                        {{ $netProfit >= 0 ? 'Operating Profit' : 'Operating Loss' }}: {{ $profitMargin }}%
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="tbl-statement">
                    <thead>
                        <tr>
                            <th style="width:50%;">Particulars / Account Head</th>
                            <th class="text-center" style="width:12%;">Type / Code</th>
                            <th class="text-right" style="width:18%;">Subtotal (₹)</th>
                            <th class="text-right" style="width:20%;">Net Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- PART 1: OPERATING REVENUE --}}
                        <tr class="stmt-section-row">
                            <td colspan="4">
                                <i class="bi bi-plus-circle-fill text-success me-1"></i> I. Operating Income / Revenue
                            </td>
                        </tr>
                        <tr>
                            <td class="pl-indent">
                                <strong>Gross Invoiced Sales</strong>
                                <div style="font-size:11px; color:#64748b;">Total billed sales across {{ $invoicesCount }} finalized invoices</div>
                            </td>
                            <td class="text-center"><span class="badge-code">SALES</span></td>
                            <td class="text-right">₹{{ number_format($netSalesSubtotal, 2) }}</td>
                            <td class="text-right">-</td>
                        </tr>
                        @if($totalCreditNotesSubtotal > 0)
                        <tr>
                            <td class="pl-indent">
                                <span class="text-danger">Less:</span> Credit Notes & Sales Returns
                                <div style="font-size:11px; color:#64748b;">Issued against invoices ({{ $creditNotesCount }} notes)</div>
                            </td>
                            <td class="text-center"><span class="badge-code text-danger">CN</span></td>
                            <td class="text-right text-danger">-₹{{ number_format($totalCreditNotesSubtotal, 2) }}</td>
                            <td class="text-right">-</td>
                        </tr>
                        @endif
                        @if($totalSalesTax > 0)
                        <tr>
                            <td class="pl-indent">
                                <span style="color:#64748b;">GST Collected (Output Tax Liability)</span>
                                <div style="font-size:11px; color:#94a3b8;">CGST/SGST/IGST billed on invoices (Pass-through tax)</div>
                            </td>
                            <td class="text-center"><span class="badge-code">GST</span></td>
                            <td class="text-right" style="color:#64748b;">₹{{ number_format($totalSalesTax, 2) }}</td>
                            <td class="text-right">-</td>
                        </tr>
                        @endif
                        <tr class="stmt-subtotal-row">
                            <td>
                                <strong>TOTAL NET OPERATING REVENUE (A)</strong>
                            </td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">REVENUE</span></td>
                            <td class="text-right">-</td>
                            <td class="text-right" style="color:#047857; font-size:14px;">
                                <strong>₹{{ number_format($netOperatingRevenue, 2) }}</strong>
                            </td>
                        </tr>

                        {{-- PART 2: OPERATING EXPENSES --}}
                        <tr class="stmt-section-row">
                            <td colspan="4">
                                <i class="bi bi-dash-circle-fill text-warning me-1"></i> II. Operating Expenses (Expenditure)
                            </td>
                        </tr>
                        @forelse($categoriesBreakdown as $cat)
                        <tr>
                            <td class="pl-indent">
                                <strong>{{ $cat['name'] }}</strong>
                                <div style="font-size:11px; color:#64748b;">
                                    {{ $cat['count'] }} entries &bull;
                                    Fixed: ₹{{ number_format($cat['fixed'], 2) }} | Var: ₹{{ number_format($cat['variable'], 2) }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge-code">{{ $cat['code'] }}</span>
                            </td>
                            <td class="text-right">
                                ₹{{ number_format($cat['total'], 2) }}
                                <div style="font-size:10.5px; color:#94a3b8;">{{ $cat['pct_of_expenses'] }}% of exp</div>
                            </td>
                            <td class="text-right">-</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No operating expenses recorded in this period.</td>
                        </tr>
                        @endforelse
                        <tr class="stmt-subtotal-row">
                            <td>
                                <strong>TOTAL OPERATING EXPENSES (B)</strong>
                                <div style="font-size:11px; color:#64748b;">Fixed: ₹{{ number_format($totalFixedExpenses, 2) }} | Variable: ₹{{ number_format($totalVariableExpenses, 2) }}</div>
                            </td>
                            <td class="text-center"><span class="badge bg-danger-subtle text-danger">EXPENSE</span></td>
                            <td class="text-right">-</td>
                            <td class="text-right" style="color:#b91c1c; font-size:14px;">
                                <strong>₹{{ number_format($totalExpenses, 2) }}</strong>
                            </td>
                        </tr>

                        {{-- PART 3: NET PROFIT / (LOSS) --}}
                        <tr class="stmt-grand-row {{ $netProfit >= 0 ? '' : 'loss' }}">
                            <td colspan="2">
                                <div style="font-size:16px;">
                                    @if($netProfit >= 0)
                                        <i class="bi bi-check2-circle me-1"></i> NET OPERATING PROFIT (A - B)
                                    @else
                                        <i class="bi bi-dash-circle me-1"></i> NET OPERATING LOSS (A - B)
                                    @endif
                                </div>
                                <div style="font-size:12px; font-weight:600; opacity:.85; margin-top:2px;">
                                    Profit Margin: {{ $profitMargin }}% &bull; Expense-to-Revenue: {{ $expenseRatio }}%
                                </div>
                            </td>
                            <td class="text-right" style="font-size:12px;">
                                Net Margin: {{ $profitMargin }}%
                            </td>
                            <td class="text-right" style="font-size:18px;">
                                <strong>{{ $netProfit < 0 ? '-' : '' }}₹{{ number_format(abs($netProfit), 2) }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Secondary Drilldowns: Categories Breakdown & Top Client Revenue --}}
        <div class="sub-grid">
            {{-- Category Wise Breakdown --}}
            <div class="acm-card">
                <div class="card-top">
                    <div class="card-title">
                        <i class="bi bi-pie-chart-fill" style="color:#f97316;"></i>
                        <span>Expense Category Distribution</span>
                    </div>
                    <span style="font-size:11px; font-weight:700; color:#64748b;">{{ count($categoriesBreakdown) }} Categories</span>
                </div>
                <div class="table-responsive">
                    <table class="sub-tbl">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-center">Fixed</th>
                                <th class="text-center">Variable</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">% Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoriesBreakdown as $cat)
                            <tr>
                                <td>
                                    <strong>{{ $cat['name'] }}</strong>
                                    <div style="font-size:10.5px; color:#94a3b8;">{{ $cat['count'] }} transactions</div>
                                </td>
                                <td class="text-center">₹{{ number_format($cat['fixed'], 2) }}</td>
                                <td class="text-center">₹{{ number_format($cat['variable'], 2) }}</td>
                                <td class="text-right"><strong>₹{{ number_format($cat['total'], 2) }}</strong></td>
                                <td class="text-right">
                                    {{ $cat['pct_of_expenses'] }}%
                                    <div class="progress-pill">
                                        <div class="progress-fill-pnl" style="width: {{ min(100, $cat['pct_of_expenses']) }}%;"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No expenses available.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Top Client Revenue Contributions --}}
            <div class="acm-card">
                <div class="card-top">
                    <div class="card-title">
                        <i class="bi bi-people-fill" style="color:#059669;"></i>
                        <span>Revenue by Client</span>
                    </div>
                    <span style="font-size:11px; font-weight:700; color:#64748b;">{{ count($clientRevenue) }} Clients</span>
                </div>
                <div class="table-responsive">
                    <table class="sub-tbl">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">Gross Total</th>
                                <th class="text-right">% Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(array_slice($clientRevenue, 0, 8) as $client)
                            <tr>
                                <td>
                                    <strong>{{ $client['name'] }}</strong>
                                </td>
                                <td class="text-center"><span class="badge-code">{{ $client['count'] }}</span></td>
                                <td class="text-right">₹{{ number_format($client['subtotal'], 2) }}</td>
                                <td class="text-right"><strong>₹{{ number_format($client['gross'], 2) }}</strong></td>
                                <td class="text-right">
                                    {{ $client['pct_of_revenue'] }}%
                                    <div class="progress-pill">
                                        <div class="progress-fill-pnl" style="width: {{ min(100, $client['pct_of_revenue']) }}%;"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No client sales recorded in this period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Monthly Timeline / Trend Analysis --}}
        @if(count($monthlyTrend) > 1)
        <div class="acm-card">
            <div class="card-top">
                <div class="card-title">
                    <i class="bi bi-calendar3" style="color:#2563eb;"></i>
                    <span>Monthly Financial Performance Trend</span>
                </div>
                <span style="font-size:11px; font-weight:700; color:#64748b;">{{ count($monthlyTrend) }} Months</span>
            </div>
            <div class="table-responsive">
                <table class="sub-tbl">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Invoiced Revenue (₹)</th>
                            <th class="text-right">Operating Expenses (₹)</th>
                            <th class="text-right">Net Profit / (Loss) (₹)</th>
                            <th class="text-right">Profit Margin %</th>
                            <th class="text-center">Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyTrend as $m)
                        <tr>
                            <td><strong>{{ $m['month_label'] }}</strong></td>
                            <td class="text-right" style="color:#047857; font-weight:700;">₹{{ number_format($m['revenue'], 2) }}</td>
                            <td class="text-right" style="color:#b91c1c; font-weight:700;">₹{{ number_format($m['expense'], 2) }}</td>
                            <td class="text-right" style="font-weight:800; color:{{ $m['net_profit'] >= 0 ? '#047857' : '#b91c1c' }};">
                                {{ $m['net_profit'] < 0 ? '-' : '' }}₹{{ number_format(abs($m['net_profit']), 2) }}
                            </td>
                            <td class="text-right">
                                <span class="badge {{ $m['net_profit'] >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $m['margin'] }}%
                                </span>
                            </td>
                            <td class="text-center" style="font-size:11px; color:#64748b;">
                                {{ $m['invoice_cnt'] }} Inv / {{ $m['expense_cnt'] }} Exp
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>

<script>
function applyPreset(preset) {
    const today = new Date();
    const pad = num => String(num).padStart(2, '0');
    const toYMD = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    let fromDate, toDate;

    if (preset === 'this_month') {
        fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
        toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (preset === 'last_month') {
        fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        toDate = new Date(today.getFullYear(), today.getMonth(), 0);
    } else if (preset === 'this_quarter') {
        const qMonth = Math.floor(today.getMonth() / 3) * 3;
        fromDate = new Date(today.getFullYear(), qMonth, 1);
        toDate = new Date(today.getFullYear(), qMonth + 3, 0);
    } else if (preset === 'this_fy') {
        const fyYear = today.getMonth() >= 3 ? today.getFullYear() : today.getFullYear() - 1;
        fromDate = new Date(fyYear, 3, 1); // April 1st
        toDate = toYMD(today);
    } else if (preset === 'last_fy') {
        const fyYear = today.getMonth() >= 3 ? today.getFullYear() - 1 : today.getFullYear() - 2;
        fromDate = new Date(fyYear, 3, 1);
        toDate = new Date(fyYear + 1, 2, 31); // March 31st
    } else if (preset === 'all_time') {
        fromDate = new Date(2020, 0, 1);
        toDate = toYMD(today);
    }

    if (fromDate instanceof Date) fromDate = toYMD(fromDate);
    if (toDate instanceof Date) toDate = toYMD(toDate);

    document.getElementById('date_from').value = fromDate;
    document.getElementById('date_to').value = toDate;
    document.getElementById('pnlFilterForm').submit();
}
</script>
@endsection
