@extends('layouts.app')

@section('title', 'Accounts Dashboard')

@push('styles')
<style>
.acc-dash-page { display:flex; flex-direction:column; min-height:100%; background:#f8fafc; font-family:'Inter',sans-serif; }
.acc-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:16px 28px; background:#fff; border-bottom:1px solid #e2e8f0; position:sticky; top:0; z-index:30; }
.acc-title { font-size:19px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:10px; line-height:1.2; }
.acc-breadcrumb { font-size:12px; color:#64748b; margin-top:3px; display:flex; align-items:center; gap:6px; }
.acc-breadcrumb a { color:#64748b; text-decoration:none; }
.acc-breadcrumb a:hover { color:#fe5f04; }
.acc-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.acc-body { padding:22px 28px 40px; display:flex; flex-direction:column; gap:22px; flex:1; }

@media (max-width: 991px) {
    .acc-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; }
    .acc-body { padding:16px 20px 30px; }
}

/* Alert Banner */
.acc-alert-banner { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1.5px solid #f59e0b; border-radius: 14px; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.12); flex-wrap:wrap; gap:12px; }

/* Financial KPI Grid (4 Cards) - Premium Gradient Card Style matching HRMS & Admin Dashboards */
.acc-kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:18px; }
.acc-kpi-card {
    position:relative;
    overflow:hidden;
    border-radius:20px;
    padding:22px 24px;
    border:none !important;
    color:#fff !important;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    min-height:165px;
    box-shadow:0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
    transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor:pointer;
    user-select:none;
    isolation:isolate;
}
.acc-kpi-card:hover {
    transform:translateY(-5px);
}
.acc-kpi-card::after {
    content:'';
    position:absolute;
    right:-15px;
    bottom:-20px;
    width:130px;
    height:130px;
    background:radial-gradient(circle, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0) 70%);
    border-radius:50%;
    pointer-events:none;
    z-index:1;
    transition:transform 0.4s ease;
}
.acc-kpi-card:hover::after {
    transform:scale(1.35);
}
.acc-kpi-watermark {
    position:absolute;
    right:12px;
    bottom:6px;
    font-size:82px;
    color:rgba(255, 255, 255, 0.10);
    pointer-events:none;
    z-index:0;
    line-height:1;
    transition:all 0.3s ease;
}
.acc-kpi-card:hover .acc-kpi-watermark {
    transform:scale(1.1) rotate(-6deg);
    color:rgba(255, 255, 255, 0.16);
}

.kpi-receivable {
    background:linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #3b82f6 100%);
    box-shadow:0 10px 25px -5px rgba(37, 99, 235, 0.35), 0 8px 10px -6px rgba(37, 99, 235, 0.2);
}
.kpi-receivable:hover {
    box-shadow:0 18px 32px -6px rgba(37, 99, 235, 0.45), 0 10px 14px -5px rgba(37, 99, 235, 0.25);
}

.kpi-payable {
    background:linear-gradient(135deg, #9a3412 0%, #ea580c 55%, #f97316 100%);
    box-shadow:0 10px 25px -5px rgba(234, 88, 12, 0.35), 0 8px 10px -6px rgba(234, 88, 12, 0.2);
}
.kpi-payable:hover {
    box-shadow:0 18px 32px -6px rgba(234, 88, 12, 0.45), 0 10px 14px -5px rgba(234, 88, 12, 0.25);
}

.kpi-income {
    background:linear-gradient(135deg, #064e3b 0%, #059669 55%, #10b981 100%);
    box-shadow:0 10px 25px -5px rgba(5, 150, 105, 0.35), 0 8px 10px -6px rgba(5, 150, 105, 0.2);
}
.kpi-income:hover {
    box-shadow:0 18px 32px -6px rgba(5, 150, 105, 0.45), 0 10px 14px -5px rgba(5, 150, 105, 0.25);
}

.kpi-expense {
    background:linear-gradient(135deg, #881337 0%, #dc2626 55%, #ef4444 100%);
    box-shadow:0 10px 25px -5px rgba(220, 38, 38, 0.35), 0 8px 10px -6px rgba(220, 38, 38, 0.2);
}
.kpi-expense:hover {
    box-shadow:0 18px 32px -6px rgba(220, 38, 38, 0.45), 0 10px 14px -5px rgba(220, 38, 38, 0.25);
}

.acc-kpi-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:10px;
    position:relative;
    z-index:2;
}
.acc-kpi-label {
    font-size:11.5px;
    font-weight:800;
    color:rgba(255,255,255,0.92) !important;
    text-transform:uppercase;
    letter-spacing:.08em;
    display:flex;
    align-items:center;
    gap:7px;
}
.acc-kpi-icon {
    width:42px;
    height:42px;
    border-radius:12px;
    background:rgba(255, 255, 255, 0.22);
    backdrop-filter:blur(6px);
    -webkit-backdrop-filter:blur(6px);
    border:1px solid rgba(255, 255, 255, 0.28);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:19px;
    color:#fff !important;
    flex-shrink:0;
    transition:all 0.3s ease;
}
.acc-kpi-card:hover .acc-kpi-icon {
    transform:scale(1.08) rotate(5deg);
    background:rgba(255, 255, 255, 0.32);
}
.acc-kpi-value {
    font-size:28px;
    font-weight:900;
    color:#ffffff !important;
    line-height:1.15;
    margin:6px 0 10px;
    text-shadow:0 2px 5px rgba(0,0,0,0.16);
    letter-spacing:-0.02em;
    position:relative;
    z-index:2;
}
.acc-kpi-sub {
    font-size:12px;
    color:rgba(255,255,255,0.9);
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:8px;
    margin-top:auto;
    position:relative;
    z-index:2;
}
.acc-kpi-pill {
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:3px 9px;
    border-radius:8px;
    background:rgba(255, 255, 255, 0.2);
    backdrop-filter:blur(4px);
    border:1px solid rgba(255, 255, 255, 0.25);
    font-size:11px;
    font-weight:700;
    color:#ffffff;
}
.acc-click-hint {
    font-size:11px;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    gap:4px;
    color:#ffffff;
    background:rgba(0, 0, 0, 0.16);
    padding:4px 9px;
    border-radius:7px;
    border:1px solid rgba(255, 255, 255, 0.18);
    transition:all 0.2s ease;
}
.acc-kpi-card:hover .acc-click-hint {
    background:rgba(0, 0, 0, 0.26);
    transform:translateX(2px);
    border-color:rgba(255, 255, 255, 0.35);
}

/* Sub KPI Cards for Ad Budgets */
.acc-sub-kpi-card {
    background:#ffffff;
    border-radius:14px;
    padding:16px 18px;
    border:1px solid #e2e8f0;
    display:flex;
    align-items:center;
    gap:14px;
    box-shadow:0 1px 3px rgba(0,0,0,0.02);
    transition:all 0.2s ease;
}
.acc-sub-kpi-card:hover {
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(0,0,0,0.06);
    border-color:#cbd5e1;
}
.acc-sub-kpi-icon {
    width:44px;
    height:44px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    flex-shrink:0;
}
.acc-sub-kpi-label {
    font-size:11px;
    font-weight:700;
    color:#64748b;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.acc-sub-kpi-value {
    font-size:22px;
    font-weight:800;
    color:#0f172a;
    line-height:1.2;
    margin-top:2px;
}

/* Buttons */
.acc-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:10px; font-size:12.5px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acc-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 3px 10px rgba(254,95,4,.2); }
.acc-btn-primary:hover { color:#fff; box-shadow:0 5px 14px rgba(254,95,4,.3); transform:translateY(-1px); }
.acc-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acc-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; background:#fff; }
.acc-btn-emerald { background:linear-gradient(135deg,#059669,#10b981); color:#fff; box-shadow:0 3px 10px rgba(16,185,129,.2); }
.acc-btn-emerald:hover { color:#fff; box-shadow:0 5px 14px rgba(16,185,129,.3); transform:translateY(-1px); }
.acc-btn-indigo { background:linear-gradient(135deg,#4f46e5,#6366f1); color:#fff; box-shadow:0 3px 10px rgba(79,70,229,.2); }
.acc-btn-indigo:hover { color:#fff; box-shadow:0 5px 14px rgba(79,70,229,.3); transform:translateY(-1px); }

/* Charts Layout */
.acc-charts-row { display:grid; grid-template-columns: 2fr 1.2fr; gap:20px; }
@media (max-width: 1100px) {
    .acc-charts-row { grid-template-columns: 1fr; }
}

.acc-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:22px; box-shadow:0 1px 4px rgba(0,0,0,.02); }
.acc-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid #f1f5f9; }
.acc-card-title { font-size:15px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; margin:0; }
.acc-card-subtitle { font-size:12px; color:#64748b; margin-top:2px; font-weight:500; }

/* Tables */
.acc-tbl-container { overflow-x:auto; }
.acc-tbl { width:100%; border-collapse:collapse; }
.acc-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:10px 14px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; }
.acc-tbl td { padding:12px 14px; font-size:12.5px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.acc-tbl tfoot td { padding:12px 14px; font-size:13px; font-weight:800; color:#0f172a; border-top:2px solid #e2e8f0; background:#f8fafc; }
.acc-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(5px); z-index:9999; align-items:center; justify-content:center; padding:16px; }
.modal-overlay.active { display:flex; }
.modal-box-lg { background:#fff; border-radius:18px; width:100%; max-width:1050px; max-height:92vh; display:flex; flex-direction:column; box-shadow:0 25px 60px rgba(0,0,0,.35); animation:popIn .22s ease-out; overflow:hidden; }
@keyframes popIn { from{ opacity:0; transform:scale(.96); } to{ opacity:1; transform:scale(1); } }
.modal-header-custom { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; flex-shrink:0; }
.modal-title-custom { font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:10px; }
.modal-close-btn { background:#f1f5f9; border:none; width:34px; height:34px; border-radius:10px; font-size:18px; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; }
.modal-close-btn:hover { background:#fee2e2; color:#ef4444; }
.modal-body-custom { padding:20px 24px; overflow-y:auto; flex:1; display:flex; flex-direction:column; gap:16px; }
.modal-footer-custom { padding:14px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
.modal-search-box { position:relative; width:100%; max-width:320px; }
.modal-search-box input { width:100%; padding:8px 12px 8px 34px; border:1px solid #cbd5e1; border-radius:10px; font-size:12.5px; outline:none; }
.modal-search-box input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.modal-search-box i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; }

/* Chart Filter Toolbar & Controls (Custom Styled - Strictly No Select2) */
.acc-chart-controls { display:inline-flex; align-items:center; gap:8px; flex-wrap:wrap; }
.acc-seg-control { display:inline-flex; background:#f1f5f9; border-radius:10px; padding:3px; border:1px solid #e2e8f0; gap:2px; }
.acc-seg-btn { border:none; background:transparent; padding:5px 12px; border-radius:8px; font-size:11.5px; font-weight:700; color:#64748b; cursor:pointer; transition:all 0.18s ease; display:inline-flex; align-items:center; gap:5px; line-height:1.2; user-select:none; }
.acc-seg-btn:hover { color:#0f172a; }
.acc-seg-btn.active { background:#ffffff; color:#0f172a; box-shadow:0 1px 4px rgba(0,0,0,0.08); font-weight:800; }
.acc-filter-select-wrap { position:relative; display:inline-flex; align-items:center; }
.acc-filter-select-wrap i.acc-lead-icon { position:absolute; left:10px; pointer-events:none; color:#64748b; font-size:12px; z-index:2; }
.acc-filter-select-wrap i.acc-arrow-icon { position:absolute; right:10px; pointer-events:none; color:#64748b; font-size:10px; z-index:2; }
.acc-filter-select { appearance:none !important; -webkit-appearance:none !important; -moz-appearance:none !important; background:#ffffff !important; border:1px solid #cbd5e1 !important; border-radius:9px !important; padding:5px 28px 5px 30px !important; font-size:12px !important; font-weight:700 !important; color:#0f172a !important; cursor:pointer; outline:none; transition:all 0.2s ease; height:32px !important; line-height:20px !important; min-width:130px; box-shadow:none !important; }
.acc-filter-select:hover { border-color:#94a3b8 !important; }
.acc-filter-select:focus { border-color:#fe5f04 !important; box-shadow:0 0 0 3px rgba(254,95,4,0.12) !important; }

/* Suppress any rogue Select2 containers generated inside acc-chart-controls */
.acc-chart-controls .select2-container { display:none !important; }
</style>
@endpush

@section('content')
<div class="acc-dash-page">

    {{-- Topbar --}}
    <div class="acc-topbar">
        <div>
            <div class="acc-title">
                <i class="bi bi-wallet2" style="color:#fe5f04;"></i>
                <span>Accounts & Finance Dashboard</span>
            </div>
            <div class="acc-breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span>/</span>
                <span style="color:#0f172a; font-weight:700;">Accounts Overview</span>
            </div>
        </div>
        <div class="acc-actions">
            <a href="{{ route('accounts.sales.invoices.create') }}" class="acc-btn acc-btn-primary">
                <i class="bi bi-receipt-cutoff"></i> Create Invoice
            </a>
            <a href="{{ route('accounts.purchase-orders.create') }}" class="acc-btn acc-btn-indigo">
                <i class="bi bi-bag-plus"></i> Create PO
            </a>
            <a href="{{ route('accounts.expenses.create') }}" class="acc-btn acc-btn-emerald">
                <i class="bi bi-plus-circle"></i> Add Expense
            </a>
            <a href="{{ route('accounts.ad-budget.clients') }}" class="acc-btn acc-btn-outline">
                <i class="bi bi-megaphone"></i> Ad Budget
            </a>
        </div>
    </div>

    <div class="acc-body">

        {{-- Pending Alert Banner (If Any Pending Ad Budgets) --}}
        @if(($pendingAccountsCount ?? 0) > 0 || ($pendingApprovalsCount ?? 0) > 0)
        <div class="acc-alert-banner">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:42px; height:42px; border-radius:10px; background:#f59e0b; color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <div style="font-size:14.5px; font-weight:800; color:#92400e;">⚡ Action Required: Pending Ad Budget Approvals</div>
                    <div style="font-size:12.5px; color:#b45309; margin-top:2px;">
                        You have <strong>{{ $pendingAccountsCount ?? 0 }}</strong> pending Accounts approval(s) and <strong>{{ $pendingApprovalsCount ?? 0 }}</strong> total pending request(s) awaiting review.
                    </div>
                </div>
            </div>
            <a href="{{ route('accounts.ad-budget.clients', ['status' => 'accounts_pending']) }}" class="acc-btn" style="background:#d97706; color:#fff; border:none; font-weight:800;">
                Review Requests &rarr;
            </a>
        </div>
        @endif

        {{-- Core Financial KPI Cards (4 Clickable Cards to Open Detailed Modals) --}}
        <div class="acc-kpi-grid">

            {{-- 1. Total Receivables Card --}}
            <div class="acc-kpi-card kpi-receivable" onclick="openModal('receivablesModal')" title="Click to view all outstanding receivables">
                <i class="bi bi-receipt acc-kpi-watermark"></i>
                <div>
                    <div class="acc-kpi-header">
                        <span class="acc-kpi-label">
                            <i class="bi bi-arrow-down-left-circle-fill"></i> Total Receivables
                        </span>
                        <div class="acc-kpi-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                    <div class="acc-kpi-value">₹{{ number_format($totalReceivables, 2) }}</div>
                </div>
                <div class="acc-kpi-sub">
                    <span class="acc-kpi-pill" style="background:rgba(255,255,255,0.22); border-color:rgba(255,255,255,0.3);">
                        <i class="bi bi-receipt"></i> {{ $unpaidInvoicesCount }} Open Invoices
                    </span>
                    <span class="acc-click-hint">
                        View Details <i class="bi bi-arrow-right-short" style="font-size:15px; margin-left:-2px;"></i>
                    </span>
                </div>
            </div>

            {{-- 2. Total Payables Card --}}
            <div class="acc-kpi-card kpi-payable" onclick="openModal('payablesModal')" title="Click to view all purchase order payables">
                <i class="bi bi-bag-check-fill acc-kpi-watermark"></i>
                <div>
                    <div class="acc-kpi-header">
                        <span class="acc-kpi-label">
                            <i class="bi bi-arrow-up-right-circle-fill"></i> Total Payables
                        </span>
                        <div class="acc-kpi-icon">
                            <i class="bi bi-bag-check-fill"></i>
                        </div>
                    </div>
                    <div class="acc-kpi-value">₹{{ number_format($totalPayables, 2) }}</div>
                </div>
                <div class="acc-kpi-sub">
                    <span class="acc-kpi-pill" style="background:rgba(255,255,255,0.22); border-color:rgba(255,255,255,0.3);">
                        <i class="bi bi-box-seam"></i> {{ $pendingPoCount }} Open POs
                    </span>
                    <span class="acc-click-hint">
                        View Details <i class="bi bi-arrow-right-short" style="font-size:15px; margin-left:-2px;"></i>
                    </span>
                </div>
            </div>

            {{-- 3. Income This Month Card --}}
            <div class="acc-kpi-card kpi-income" onclick="openModal('incomeModal')" title="Click to view all income collections this month">
                <i class="bi bi-wallet-fill acc-kpi-watermark"></i>
                <div>
                    <div class="acc-kpi-header">
                        <span class="acc-kpi-label">
                            <i class="bi bi-cash-stack"></i> Income (This Month)
                        </span>
                        <div class="acc-kpi-icon">
                            <i class="bi bi-wallet-fill"></i>
                        </div>
                    </div>
                    <div class="acc-kpi-value">₹{{ number_format($incomeThisMonth, 2) }}</div>
                </div>
                <div class="acc-kpi-sub">
                    <span class="acc-kpi-pill" style="background:rgba(255,255,255,0.22); border-color:rgba(255,255,255,0.3);">
                        <i class="bi bi-wallet2"></i> {{ $thisMonthPayments->count() }} Payments Received
                    </span>
                    <span class="acc-click-hint">
                        View Details <i class="bi bi-arrow-right-short" style="font-size:15px; margin-left:-2px;"></i>
                    </span>
                </div>
            </div>

            {{-- 4. Expenses This Month Card --}}
            <div class="acc-kpi-card kpi-expense" onclick="openModal('expensesModal')" title="Click to view all recorded expenses this month">
                <i class="bi bi-credit-card-fill acc-kpi-watermark"></i>
                <div>
                    <div class="acc-kpi-header">
                        <span class="acc-kpi-label">
                            <i class="bi bi-receipt"></i> Expenses (This Month)
                        </span>
                        <div class="acc-kpi-icon">
                            <i class="bi bi-credit-card-fill"></i>
                        </div>
                    </div>
                    <div class="acc-kpi-value">₹{{ number_format($expenseThisMonth, 2) }}</div>
                </div>
                <div class="acc-kpi-sub">
                    <span class="acc-kpi-pill" style="background:rgba(255,255,255,0.22); border-color:rgba(255,255,255,0.3);">
                        <i class="bi bi-credit-card"></i> {{ $thisMonthExpenses->count() }} Recorded Entries
                    </span>
                    <span class="acc-click-hint">
                        View Details <i class="bi bi-arrow-right-short" style="font-size:15px; margin-left:-2px;"></i>
                    </span>
                </div>
            </div>

        </div>

        {{-- Financial Charts Row: Income & Expense (Left) vs Top Expenses (Right) --}}
        <div class="acc-charts-row">

            {{-- Income and Expense Comparison Chart --}}
            <div class="acc-card">
                <div class="acc-card-header" style="flex-wrap:wrap; gap:12px; align-items:center;">
                    <div>
                        <h3 class="acc-card-title">
                            <i class="bi bi-bar-chart-line-fill" style="color:#10b981;"></i>
                            Income &amp; Expense Analysis
                        </h3>
                        <div class="acc-card-subtitle">Monthly cash inflow vs recorded operational expenses</div>
                    </div>
                    <div class="acc-chart-controls">
                        <!-- Segmented Toggle: Financial Year vs Calendar Year -->
                        <div class="acc-seg-control">
                            <button type="button" class="acc-seg-btn active" id="btnTypeFy" onclick="setChartType('fy')">
                                <i class="bi bi-calendar2-range"></i> Financial Year
                            </button>
                            <button type="button" class="acc-seg-btn" id="btnTypeCy" onclick="setChartType('cy')">
                                <i class="bi bi-calendar3"></i> Calendar Year
                            </button>
                        </div>

                        <!-- Compact Year Dropdown (Strictly No Select2) -->
                        <div class="acc-filter-select-wrap">
                            <i class="bi bi-clock-history acc-lead-icon"></i>
                            <select id="incomeExpenseYearSelect" class="acc-filter-select no-select2" data-no-select2="true" onchange="fetchIncomeExpenseChartData()">
                                @foreach($fyYears as $y)
                                    <option value="{{ $y }}" {{ $y == $currentFyStartYear ? 'selected' : '' }}>
                                        FY {{ $y }}-{{ substr((string)($y + 1), -2) }} {{ $y == $currentFyStartYear ? '(Current)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down acc-arrow-icon"></i>
                        </div>
                    </div>
                </div>
                <div id="chart-income-expense" style="min-height:330px; transition:opacity 0.2s;"></div>
            </div>

            {{-- Top Expenses Breakdown Chart --}}
            <div class="acc-card">
                <div class="acc-card-header" style="flex-wrap:wrap; gap:12px; align-items:center;">
                    <div>
                        <h3 class="acc-card-title">
                            <i class="bi bi-pie-chart-fill" style="color:#fe5f04;"></i>
                            Top Expenses by Category
                        </h3>
                        <div class="acc-card-subtitle">Breakdown of operational spend across expense heads</div>
                    </div>
                    <div class="acc-chart-controls">
                        <!-- Period Select (Strictly No Select2) -->
                        <div class="acc-filter-select-wrap">
                            <i class="bi bi-funnel acc-lead-icon"></i>
                            <select id="topExpensesPeriodSelect" class="acc-filter-select no-select2" data-no-select2="true" onchange="fetchTopExpensesChartData()">
                                <option value="all" selected>All Time (All Records)</option>
                                <option value="year">Current FY (FY {{ $currentFyStartYear }}-{{ substr($currentFyStartYear + 1, -2) }})</option>
                                @foreach($fyYears as $fyYr)
                                    @if($fyYr !== $currentFyStartYear)
                                        <option value="fy_{{ $fyYr }}">FY {{ $fyYr }}-{{ substr($fyYr + 1, -2) }}</option>
                                    @endif
                                @endforeach
                                <option value="quarter">This Quarter</option>
                                <option value="month">This Month</option>
                                <option value="today">Today</option>
                            </select>
                            <i class="bi bi-chevron-down acc-arrow-icon"></i>
                        </div>
                        <a href="{{ route('accounts.expenses.index') }}" class="acc-btn acc-btn-outline" style="padding:6px 10px; font-size:12px; height:32px; border-radius:9px;" title="Manage Expenses">
                            <i class="bi bi-gear-fill"></i>
                        </a>
                    </div>
                </div>
                <div id="chart-top-expenses" style="min-height:330px; display:flex; align-items:center; justify-content:center; transition:opacity 0.2s;"></div>
            </div>

        </div>

        {{-- Ad Budget Overview Secondary Grid --}}
        <div class="acc-card" style="padding:18px 22px;">
            <div class="acc-card-header" style="margin-bottom:14px;">
                <div>
                    <h3 class="acc-card-title">
                        <i class="bi bi-megaphone-fill" style="color:#3b82f6;"></i>
                        Ad Budget Management
                    </h3>
                    <div class="acc-card-subtitle">Marketing and campaign ad budgets requested by teams</div>
                </div>
                <div style="display:flex; gap:8px;">
                    <a href="{{ route('accounts.ad-budget.clients') }}" class="acc-btn acc-btn-outline" style="padding:4px 12px; font-size:12px;">Client Budgets</a>
                    <a href="{{ route('accounts.ad-budget.partners') }}" class="acc-btn acc-btn-outline" style="padding:4px 12px; font-size:12px;">Partner Budgets</a>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px;">
                <div class="acc-sub-kpi-card">
                    <div class="acc-sub-kpi-icon" style="background:#eff6ff; color:#2563eb; border:1px solid #dbeafe;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <div class="acc-sub-kpi-label">Active Ad Accounts</div>
                        <div class="acc-sub-kpi-value">{{ $activeAdAccountsCount }}</div>
                    </div>
                </div>
                <div class="acc-sub-kpi-card">
                    <div class="acc-sub-kpi-icon" style="background:#fff7ed; color:#ea580c; border:1px solid #ffedd5;">
                        <i class="bi bi-send-fill"></i>
                    </div>
                    <div>
                        <div class="acc-sub-kpi-label">Client Requests (Month)</div>
                        <div class="acc-sub-kpi-value">{{ $clientRequestsCount }}</div>
                    </div>
                </div>
                <div class="acc-sub-kpi-card">
                    <div class="acc-sub-kpi-icon" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0;">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                    <div>
                        <div class="acc-sub-kpi-label">Client Ad Spend (Month)</div>
                        <div class="acc-sub-kpi-value">₹{{ number_format($totalClientBudgetAmount, 2) }}</div>
                    </div>
                </div>
                <div class="acc-sub-kpi-card">
                    <div class="acc-sub-kpi-icon" style="background:#fef3c7; color:#d97706; border:1px solid #fde68a;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="acc-sub-kpi-label">Pending Approvals</div>
                        <div class="acc-sub-kpi-value">{{ $pendingApprovalsCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tables Row: Recent Invoices & Recent Purchase Orders --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;" class="recent-tables-grid">

            {{-- Recent Invoices --}}
            <div class="acc-card">
                <div class="acc-card-header">
                    <div>
                        <h3 class="acc-card-title">
                            <i class="bi bi-receipt" style="color:#2563eb;"></i>
                            Recent Sales Invoices
                        </h3>
                        <div class="acc-card-subtitle">Latest billed client invoices</div>
                    </div>
                    <a href="{{ route('accounts.sales.invoices.index') }}" class="acc-btn acc-btn-outline" style="padding:4px 10px; font-size:11px;">View All</a>
                </div>
                @if($recentInvoices->isEmpty())
                    <div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px;">
                        <i class="bi bi-inbox" style="font-size:28px; display:block; margin-bottom:6px;"></i>
                        No invoices recorded yet.
                    </div>
                @else
                    <div class="acc-tbl-container">
                        <table class="acc-tbl">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total (₹)</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentInvoices as $inv)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounts.sales.invoices.show', $inv->id) }}" style="font-weight:700; color:#fe5f04; text-decoration:none;">
                                            {{ $inv->invoice_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:#0f172a; max-width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            {{ $inv->customer_name ?: ($inv->customer?->display_name ?? '—') }}
                                        </div>
                                    </td>
                                    <td style="color:#64748b; font-size:12px;">{{ $inv->invoice_date?->format('d M Y') ?? '—' }}</td>
                                    <td style="font-weight:700; color:#0f172a;">₹{{ number_format($inv->total_amount, 2) }}</td>
                                    <td>
                                        @if($inv->balance_due > 0)
                                            <span style="font-weight:700; color:#dc2626;">₹{{ number_format($inv->balance_due, 2) }}</span>
                                        @else
                                            <span style="color:#059669; font-weight:700;">₹0.00</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $st = $inv->status;
                                            $stClass = match($st) {
                                                'paid' => 'background:#dcfce7; color:#15803d;',
                                                'partially_paid' => 'background:#fef3c7; color:#b45309;',
                                                'overdue' => 'background:#fee2e2; color:#b91c1c;',
                                                'draft' => 'background:#f1f5f9; color:#475569;',
                                                default => 'background:#e0f2fe; color:#0369a1;'
                                            };
                                        @endphp
                                        <span class="acc-badge" style="{{ $stClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $st)) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Recent Purchase Orders --}}
            <div class="acc-card">
                <div class="acc-card-header">
                    <div>
                        <h3 class="acc-card-title">
                            <i class="bi bi-bag-check" style="color:#ea580c;"></i>
                            Recent Purchase Orders
                        </h3>
                        <div class="acc-card-subtitle">Latest vendor & supplier procurement orders</div>
                    </div>
                    <a href="{{ route('accounts.purchase-orders.index') }}" class="acc-btn acc-btn-outline" style="padding:4px 10px; font-size:11px;">View All</a>
                </div>
                @if($recentPurchaseOrders->isEmpty())
                    <div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px;">
                        <i class="bi bi-inbox" style="font-size:28px; display:block; margin-bottom:6px;"></i>
                        No purchase orders created yet.
                    </div>
                @else
                    <div class="acc-tbl-container">
                        <table class="acc-tbl">
                            <thead>
                                <tr>
                                    <th>PO #</th>
                                    <th>Supplier</th>
                                    <th>Date</th>
                                    <th>Amount (₹)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPurchaseOrders as $po)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounts.purchase-orders.show', $po->id) }}" style="font-weight:700; color:#ea580c; text-decoration:none;">
                                            {{ $po->po_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:#0f172a; max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            {{ $po->supplier_name ?: ($po->supplier?->vendor_name ?? '—') }}
                                        </div>
                                    </td>
                                    <td style="color:#64748b; font-size:12px;">{{ $po->po_date?->format('d M Y') ?? '—' }}</td>
                                    <td style="font-weight:700; color:#0f172a;">₹{{ number_format($po->total_amount, 2) }}</td>
                                    <td>
                                        @php
                                            $poSt = $po->status;
                                            $poStClass = match($poSt) {
                                                'issued' => 'background:#dbeafe; color:#1e40af;',
                                                'delivered' => 'background:#dcfce7; color:#15803d;',
                                                'approved' => 'background:#dcfce7; color:#15803d;',
                                                'cancelled' => 'background:#fee2e2; color:#b91c1c;',
                                                default => 'background:#f1f5f9; color:#475569;'
                                            };
                                        @endphp
                                        <span class="acc-badge" style="{{ $poStClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $poSt)) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>

        {{-- Recent Ad Budget Requests Table (Preserved from existing view) --}}
        <div class="acc-card">
            <div class="acc-card-header">
                <div>
                    <h3 class="acc-card-title">
                        <i class="bi bi-clock-history" style="color:#d97706;"></i>
                        Recent Ad Budget Requests
                    </h3>
                    <div class="acc-card-subtitle">Recent budget allocations for marketing ad campaigns</div>
                </div>
                <a href="{{ route('accounts.ad-budget.clients') }}" class="acc-btn acc-btn-outline" style="padding:4px 10px; font-size:11px;">View All</a>
            </div>

            @if($recentRequests->isEmpty())
                <div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px;">
                    <i class="bi bi-inbox" style="font-size:28px; display:block; margin-bottom:6px;"></i>
                    No budget requests raised yet.
                </div>
            @else
                <div class="acc-tbl-container">
                    <table class="acc-tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Ad Account</th>
                                <th>Client / Partner</th>
                                <th>Amount</th>
                                <th>Requested By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRequests as $req)
                            <tr>
                                <td style="color:#94a3b8; font-weight:700;">#{{ $req->id }}</td>
                                <td>
                                    <span class="acc-badge" style="background:#f1f5f9; color:#334155;">{{ strtoupper($req->type) }}</span>
                                </td>
                                <td>
                                    <strong>{{ $req->adAccount?->account_name ?? '—' }}</strong>
                                    @if($req->adAccount?->account_id)
                                        <div style="font-size:11px; color:#94a3b8;">{{ $req->adAccount->account_id }}</div>
                                    @endif
                                </td>
                                <td>{{ $req->client_name ?? '—' }}</td>
                                <td style="font-weight:800; color:#0f172a;">₹{{ number_format($req->amount, 2) }}</td>
                                <td>{{ $req->requester?->name ?? '—' }}</td>
                                <td>
                                    @if($req->status === 'tl_pending')
                                        <span class="acc-badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">Pending DM TL</span>
                                    @elseif($req->status === 'accounts_pending')
                                        <span class="acc-badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">Pending Accounts</span>
                                    @elseif($req->status === 'approved')
                                        <span class="acc-badge" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">Approved</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="acc-badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;">Rejected</span>
                                    @else
                                        <span class="acc-badge" style="background:#f1f5f9; color:#475569;">{{ ucfirst($req->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 1: TOTAL RECEIVABLES DETAILS                                        --}}
{{-- ========================================================================= --}}
<div id="receivablesModal" class="modal-overlay">
    <div class="modal-box-lg">
        <div class="modal-header-custom">
            <div class="modal-title-custom">
                <div style="width:36px; height:36px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="bi bi-arrow-down-left-circle-fill"></i>
                </div>
                <div>
                    <div>Total Receivables Breakdown</div>
                    <div style="font-size:12px; color:#64748b; font-weight:500;">
                        Pending and overdue balance from client sales invoices
                    </div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('receivablesModal')">&times;</button>
        </div>

        <div style="padding:14px 24px 0; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="display:flex; gap:10px; align-items:center;">
                <span class="acc-badge" style="background:#eff6ff; color:#1d4ed8; font-size:12px; padding:5px 12px;">
                    Total Outstanding: <strong>₹{{ number_format($totalReceivables, 2) }}</strong>
                </span>
                @if($overdueReceivables > 0)
                    <span class="acc-badge" style="background:#fef2f2; color:#dc2626; font-size:12px; padding:5px 12px; border:1px solid #fee2e2;">
                        Overdue: <strong>₹{{ number_format($overdueReceivables, 2) }}</strong>
                    </span>
                @endif
                <span class="acc-badge" style="background:#f1f5f9; color:#475569; font-size:12px; padding:5px 12px;">
                    {{ $receivablesInvoices->count() }} Invoices
                </span>
            </div>
            <div class="modal-search-box">
                <i class="bi bi-search"></i>
                <input type="text" onkeyup="filterTable(this, 'receivablesTbl')" placeholder="Search invoice, customer...">
            </div>
        </div>

        <div class="modal-body-custom">
            @if($receivablesInvoices->isEmpty())
                <div style="text-align:center; padding:40px; color:#94a3b8;">
                    <i class="bi bi-check-circle" style="font-size:36px; color:#10b981; display:block; margin-bottom:8px;"></i>
                    <strong style="color:#0f172a; font-size:15px;">All Caught Up!</strong>
                    <p style="margin:4px 0 0; font-size:13px;">No open receivables at this time. All issued invoices are settled.</p>
                </div>
            @else
                <div class="acc-tbl-container">
                    <table class="acc-tbl" id="receivablesTbl">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer Name</th>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th>Total (₹)</th>
                                <th>Paid (₹)</th>
                                <th>Balance Due (₹)</th>
                                <th>Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totRecBln = 0; $totRecAmt = 0; $totRecPaid = 0; @endphp
                            @foreach($receivablesInvoices as $inv)
                            @php
                                $totRecBln += (float) $inv->balance_due;
                                $totRecAmt += (float) $inv->total_amount;
                                $totRecPaid += (float) $inv->paid_amount;
                                $isOverdue = $inv->due_date && $inv->due_date->isPast();
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('accounts.sales.invoices.show', $inv->id) }}" target="_blank" style="font-weight:700; color:#fe5f04; text-decoration:none;">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $inv->customer_name ?: ($inv->customer?->display_name ?? '—') }}</strong>
                                    @if($inv->customer_phone)
                                        <div style="font-size:11px; color:#94a3b8;">{{ $inv->customer_phone }}</div>
                                    @endif
                                </td>
                                <td style="color:#64748b; font-size:12px;">{{ $inv->invoice_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    @if($inv->due_date)
                                        <div style="font-size:12px; font-weight:{{ $isOverdue ? '800' : '600' }}; color:{{ $isOverdue ? '#dc2626' : '#334155' }};">
                                            {{ $inv->due_date->format('d M Y') }}
                                            @if($isOverdue)
                                                <span style="font-size:10px; display:block; color:#dc2626;">({{ (int) now()->diffInDays($inv->due_date) }}d overdue)</span>
                                            @endif
                                        </div>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="font-weight:700; color:#0f172a;">₹{{ number_format($inv->total_amount, 2) }}</td>
                                <td style="color:#059669; font-weight:600;">₹{{ number_format($inv->paid_amount, 2) }}</td>
                                <td style="font-weight:800; color:#dc2626; font-size:13.5px;">₹{{ number_format($inv->balance_due, 2) }}</td>
                                <td>
                                    @if($isOverdue)
                                        <span class="acc-badge" style="background:#fee2e2; color:#b91c1c;">Overdue</span>
                                    @elseif($inv->status === 'partially_paid')
                                        <span class="acc-badge" style="background:#fef3c7; color:#b45309;">Partial</span>
                                    @else
                                        <span class="acc-badge" style="background:#e0f2fe; color:#0369a1;">Unpaid</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ route('accounts.sales.invoices.show', $inv->id) }}" target="_blank" class="acc-btn acc-btn-outline" style="padding:4px 10px; font-size:11.5px;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right;">Total Outstanding:</td>
                                <td>₹{{ number_format($totRecAmt, 2) }}</td>
                                <td style="color:#059669;">₹{{ number_format($totRecPaid, 2) }}</td>
                                <td style="color:#dc2626;">₹{{ number_format($totRecBln, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        <div class="modal-footer-custom">
            <a href="{{ route('accounts.sales.invoices.index') }}" class="acc-btn acc-btn-outline">
                <i class="bi bi-box-arrow-up-right"></i> Open Invoices Page
            </a>
            <button type="button" class="acc-btn acc-btn-outline" onclick="closeModal('receivablesModal')">Close</button>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 2: TOTAL PAYABLES DETAILS                                           --}}
{{-- ========================================================================= --}}
<div id="payablesModal" class="modal-overlay">
    <div class="modal-box-lg">
        <div class="modal-header-custom">
            <div class="modal-title-custom">
                <div style="width:36px; height:36px; border-radius:10px; background:#fff7ed; color:#ea580c; display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="bi bi-arrow-up-right-circle-fill"></i>
                </div>
                <div>
                    <div>Total Payables Breakdown</div>
                    <div style="font-size:12px; color:#64748b; font-weight:500;">
                        Purchase Orders committed to suppliers & vendors
                    </div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('payablesModal')">&times;</button>
        </div>

        <div style="padding:14px 24px 0; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="display:flex; gap:10px; align-items:center;">
                <span class="acc-badge" style="background:#fff7ed; color:#c2410c; font-size:12px; padding:5px 12px; border:1px solid #ffedd5;">
                    Total PO Value: <strong>₹{{ number_format($totalPayables, 2) }}</strong>
                </span>
                <span class="acc-badge" style="background:#f1f5f9; color:#475569; font-size:12px; padding:5px 12px;">
                    {{ $payablesPurchaseOrders->count() }} Orders
                </span>
            </div>
            <div class="modal-search-box">
                <i class="bi bi-search"></i>
                <input type="text" onkeyup="filterTable(this, 'payablesTbl')" placeholder="Search PO #, supplier...">
            </div>
        </div>

        <div class="modal-body-custom">
            @if($payablesPurchaseOrders->isEmpty())
                <div style="text-align:center; padding:40px; color:#94a3b8;">
                    <i class="bi bi-inbox" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    <strong style="color:#0f172a; font-size:15px;">No Purchase Orders Found</strong>
                    <p style="margin:4px 0 0; font-size:13px;">No supplier purchase orders have been created yet.</p>
                </div>
            @else
                <div class="acc-tbl-container">
                    <table class="acc-tbl" id="payablesTbl">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Supplier Name</th>
                                <th>PO Date</th>
                                <th>Delivery Due</th>
                                <th>Payment Terms</th>
                                <th>Total (₹)</th>
                                <th>Status</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totPoAmt = 0; @endphp
                            @foreach($payablesPurchaseOrders as $po)
                            @php $totPoAmt += (float) $po->total_amount; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('accounts.purchase-orders.show', $po->id) }}" target="_blank" style="font-weight:700; color:#ea580c; text-decoration:none;">
                                        {{ $po->po_number }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $po->supplier_name ?: ($po->supplier?->vendor_name ?? '—') }}</strong>
                                    @if($po->supplier_gstin)
                                        <div style="font-size:11px; color:#94a3b8;">GST: {{ $po->supplier_gstin }}</div>
                                    @endif
                                </td>
                                <td style="color:#64748b; font-size:12px;">{{ $po->po_date?->format('d M Y') ?? '—' }}</td>
                                <td style="color:#64748b; font-size:12px;">{{ $po->delivery_date?->format('d M Y') ?? '—' }}</td>
                                <td style="font-size:12px; color:#475569;">{{ $po->payment_terms ?: 'Immediate' }}</td>
                                <td style="font-weight:800; color:#0f172a; font-size:13.5px;">₹{{ number_format($po->total_amount, 2) }}</td>
                                <td>
                                    @php
                                        $poSt = $po->status;
                                        $poStClass = match($poSt) {
                                            'issued' => 'background:#dbeafe; color:#1e40af;',
                                            'delivered' => 'background:#dcfce7; color:#15803d;',
                                            'approved' => 'background:#dcfce7; color:#15803d;',
                                            'cancelled' => 'background:#fee2e2; color:#b91c1c;',
                                            default => 'background:#f1f5f9; color:#475569;'
                                        };
                                    @endphp
                                    <span class="acc-badge" style="{{ $poStClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $poSt)) }}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:inline-flex; gap:6px;">
                                        <a href="{{ route('accounts.purchase-orders.show', $po->id) }}" target="_blank" class="acc-btn acc-btn-outline" style="padding:4px 8px; font-size:11px;" title="View PO">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="{{ route('accounts.purchase-orders.print', $po->id) }}" target="_blank" class="acc-btn acc-btn-outline" style="padding:4px 8px; font-size:11px;" title="Print PO">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" style="text-align:right;">Total Payables Value:</td>
                                <td style="color:#0f172a;">₹{{ number_format($totPoAmt, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        <div class="modal-footer-custom">
            <a href="{{ route('accounts.purchase-orders.index') }}" class="acc-btn acc-btn-outline">
                <i class="bi bi-box-arrow-up-right"></i> Open Purchase Orders Page
            </a>
            <button type="button" class="acc-btn acc-btn-outline" onclick="closeModal('payablesModal')">Close</button>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 3: INCOME (THIS MONTH) DETAILS                                      --}}
{{-- ========================================================================= --}}
<div id="incomeModal" class="modal-overlay">
    <div class="modal-box-lg">
        <div class="modal-header-custom">
            <div class="modal-title-custom">
                <div style="width:36px; height:36px; border-radius:10px; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div>Income Received This Month ({{ now()->format('F Y') }})</div>
                    <div style="font-size:12px; color:#64748b; font-weight:500;">
                        All invoice payment collections recorded in {{ now()->format('M Y') }}
                    </div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('incomeModal')">&times;</button>
        </div>

        <div style="padding:14px 24px 0; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="display:flex; gap:10px; align-items:center;">
                <span class="acc-badge" style="background:#ecfdf5; color:#047857; font-size:12px; padding:5px 12px; border:1px solid #d1fae5;">
                    Total Collected: <strong>₹{{ number_format($incomeThisMonth, 2) }}</strong>
                </span>
                <span class="acc-badge" style="background:#f1f5f9; color:#475569; font-size:12px; padding:5px 12px;">
                    {{ $thisMonthPayments->count() }} Payments
                </span>
            </div>
            <div class="modal-search-box">
                <i class="bi bi-search"></i>
                <input type="text" onkeyup="filterTable(this, 'incomeTbl')" placeholder="Search payment, customer...">
            </div>
        </div>

        <div class="modal-body-custom">
            @if($thisMonthPayments->isEmpty())
                <div style="text-align:center; padding:40px; color:#94a3b8;">
                    <i class="bi bi-inbox" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    <strong style="color:#0f172a; font-size:15px;">No Payments Received Yet This Month</strong>
                    <p style="margin:4px 0 0; font-size:13px;">No invoice payments were recorded in {{ now()->format('F Y') }}.</p>
                </div>
            @else
                <div class="acc-tbl-container">
                    <table class="acc-tbl" id="incomeTbl">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Invoice #</th>
                                <th>Customer Name</th>
                                <th>Payment Date</th>
                                <th>Payment Mode</th>
                                <th>Reference / Txn #</th>
                                <th>Amount Received (₹)</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totIncRec = 0; @endphp
                            @foreach($thisMonthPayments as $pmt)
                            @php $totIncRec += (float) $pmt->amount_received; @endphp
                            <tr>
                                <td style="font-weight:700; color:#0f172a;">{{ $pmt->payment_number }}</td>
                                <td>
                                    @if($pmt->invoice)
                                        <a href="{{ route('accounts.sales.invoices.show', $pmt->invoice_id) }}" target="_blank" style="font-weight:700; color:#fe5f04; text-decoration:none;">
                                            {{ $pmt->invoice->invoice_number }}
                                        </a>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $pmt->customer?->display_name ?: ($pmt->invoice?->customer_name ?? '—') }}</strong>
                                </td>
                                <td style="color:#64748b; font-size:12px;">{{ $pmt->payment_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <span class="acc-badge" style="background:#f1f5f9; color:#334155;">
                                        {{ $pmt->payment_mode_label }}
                                    </span>
                                </td>
                                <td style="font-size:12px; color:#64748b;">{{ $pmt->reference_number ?: '—' }}</td>
                                <td style="font-weight:800; color:#059669; font-size:13.5px;">₹{{ number_format($pmt->amount_received, 2) }}</td>
                                <td style="text-align:center;">
                                    @if($pmt->invoice_id)
                                        <a href="{{ route('accounts.sales.invoices.show', $pmt->invoice_id) }}" target="_blank" class="acc-btn acc-btn-outline" style="padding:4px 8px; font-size:11px;">
                                            <i class="bi bi-eye"></i> View Invoice
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align:right;">Total Collections (This Month):</td>
                                <td style="color:#059669;">₹{{ number_format($totIncRec, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        <div class="modal-footer-custom">
            <a href="{{ route('accounts.sales.invoices.index') }}" class="acc-btn acc-btn-outline">
                <i class="bi bi-box-arrow-up-right"></i> Open Invoices Page
            </a>
            <button type="button" class="acc-btn acc-btn-outline" onclick="closeModal('incomeModal')">Close</button>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 4: EXPENSES (THIS MONTH) DETAILS                                    --}}
{{-- ========================================================================= --}}
<div id="expensesModal" class="modal-overlay">
    <div class="modal-box-lg">
        <div class="modal-header-custom">
            <div class="modal-title-custom">
                <div style="width:36px; height:36px; border-radius:10px; background:#fef2f2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:18px;">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <div>Operational Expenses This Month ({{ now()->format('F Y') }})</div>
                    <div style="font-size:12px; color:#64748b; font-weight:500;">
                        All expenses recorded in {{ now()->format('M Y') }}
                    </div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('expensesModal')">&times;</button>
        </div>

        <div style="padding:14px 24px 0; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div style="display:flex; gap:10px; align-items:center;">
                <span class="acc-badge" style="background:#fef2f2; color:#b91c1c; font-size:12px; padding:5px 12px; border:1px solid #fee2e2;">
                    Total Expenses: <strong>₹{{ number_format($expenseThisMonth, 2) }}</strong>
                </span>
                <span class="acc-badge" style="background:#f1f5f9; color:#475569; font-size:12px; padding:5px 12px;">
                    {{ $thisMonthExpenses->count() }} Entries
                </span>
            </div>
            <div class="modal-search-box">
                <i class="bi bi-search"></i>
                <input type="text" onkeyup="filterTable(this, 'expensesTbl')" placeholder="Search expense, vendor, category...">
            </div>
        </div>

        <div class="modal-body-custom">
            @if($thisMonthExpenses->isEmpty())
                <div style="text-align:center; padding:40px; color:#94a3b8;">
                    <i class="bi bi-inbox" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    <strong style="color:#0f172a; font-size:15px;">No Expenses Recorded This Month</strong>
                    <p style="margin:4px 0 0; font-size:13px;">No expense records have been logged in {{ now()->format('F Y') }}.</p>
                </div>
            @else
                <div class="acc-tbl-container">
                    <table class="acc-tbl" id="expensesTbl">
                        <thead>
                            <tr>
                                <th>Expense #</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Vendor</th>
                                <th>Type</th>
                                <th>Bill / Ref #</th>
                                <th>Amount (₹)</th>
                                <th>Remarks</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totExpAmt = 0; @endphp
                            @foreach($thisMonthExpenses as $exp)
                            @php $totExpAmt += (float) $exp->amount; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('accounts.expenses.show', $exp->id) }}" target="_blank" style="font-weight:700; color:#dc2626; text-decoration:none;">
                                        {{ $exp->expense_number }}
                                    </a>
                                </td>
                                <td style="color:#64748b; font-size:12px;">{{ $exp->expense_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <strong>{{ $exp->category?->name ?? '—' }}</strong>
                                </td>
                                <td style="color:#64748b; font-size:12px;">{{ $exp->subcategory?->name ?? '—' }}</td>
                                <td>{{ $exp->vendor?->vendor_name ?? '—' }}</td>
                                <td>
                                    <span class="acc-badge" style="background:#f1f5f9; color:#475569;">
                                        {{ $exp->expense_type ?? 'Fixed' }}
                                    </span>
                                </td>
                                <td style="font-size:12px; color:#64748b;">{{ $exp->invoice_no ?: '—' }}</td>
                                <td style="font-weight:800; color:#dc2626; font-size:13.5px;">₹{{ number_format($exp->amount, 2) }}</td>
                                <td style="font-size:11.5px; color:#64748b; max-width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $exp->remarks ?: '—' }}
                                </td>
                                <td style="text-align:center;">
                                    <a href="{{ route('accounts.expenses.show', $exp->id) }}" target="_blank" class="acc-btn acc-btn-outline" style="padding:4px 8px; font-size:11px;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" style="text-align:right;">Total Expenses (This Month):</td>
                                <td style="color:#dc2626;">₹{{ number_format($totExpAmt, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

        <div class="modal-footer-custom">
            <a href="{{ route('accounts.expenses.index') }}" class="acc-btn acc-btn-outline">
                <i class="bi bi-box-arrow-up-right"></i> Open Expenses Page
            </a>
            <button type="button" class="acc-btn acc-btn-outline" onclick="closeModal('expensesModal')">Close</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Modal Open / Close Controls
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking backdrop or pressing Escape
window.addEventListener('click', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(function (m) {
            m.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
});

// Quick client-side search inside modals
function filterTable(input, tableId) {
    const query = input.value.toLowerCase();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(function (row) {
        const text = row.innerText.toLowerCase();
        row.style.display = text.indexOf(query) > -1 ? '' : 'none';
    });
}

// Chart instances & states
let incomeExpenseChart = null;
let donutChart = null;
let currentChartType = 'fy';

const fyYears = @json($fyYears);
const cyYears = @json($cyYears);
const currentFy = {{ $currentFyStartYear }};
const currentCy = {{ now()->year }};

function killSelect2() {
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
        const $targets = window.jQuery('#incomeExpenseYearSelect, #topExpensesPeriodSelect');
        $targets.each(function () {
            if (window.jQuery(this).hasClass('select2-hidden-accessible')) {
                window.jQuery(this).select2('destroy');
            }
        });
        window.jQuery('.acc-chart-controls .select2-container').remove();
    }
}

function setChartType(type) {
    currentChartType = type;
    const btnFy = document.getElementById('btnTypeFy');
    const btnCy = document.getElementById('btnTypeCy');

    if (type === 'fy') {
        if (btnFy) btnFy.classList.add('active');
        if (btnCy) btnCy.classList.remove('active');
    } else {
        if (btnCy) btnCy.classList.add('active');
        if (btnFy) btnFy.classList.remove('active');
    }

    populateYearDropdown(type);
    fetchIncomeExpenseChartData();
}

function populateYearDropdown(type) {
    const select = document.getElementById('incomeExpenseYearSelect');
    if (!select) return;
    select.innerHTML = '';

    if (type === 'fy') {
        fyYears.forEach(function (year) {
            const opt = document.createElement('option');
            opt.value = year;
            const nextYearShort = String(year + 1).slice(-2);
            opt.textContent = `FY ${year}-${nextYearShort}` + (year === currentFy ? ' (Current)' : '');
            if (year === currentFy) opt.selected = true;
            select.appendChild(opt);
        });
    } else {
        cyYears.forEach(function (year) {
            const opt = document.createElement('option');
            opt.value = year;
            opt.textContent = `CY ${year}` + (year === currentCy ? ' (Current)' : '');
            if (year === currentCy) opt.selected = true;
            select.appendChild(opt);
        });
    }
    killSelect2();
}

function fetchIncomeExpenseChartData() {
    const yearSelect = document.getElementById('incomeExpenseYearSelect');
    if (!yearSelect) return;

    const type = currentChartType;
    const year = yearSelect.value;
    const chartContainer = document.getElementById('chart-income-expense');
    if (chartContainer) chartContainer.style.opacity = '0.45';

    fetch(`{{ route('accounts.dashboard') }}?action=income_expense_chart&chart_type=${type}&chart_year=${year}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (chartContainer) chartContainer.style.opacity = '1';
        if (incomeExpenseChart) {
            incomeExpenseChart.updateOptions({
                xaxis: {
                    categories: data.labels
                }
            });
            incomeExpenseChart.updateSeries([
                { name: 'Income (Received)', data: data.income },
                { name: 'Expenses', data: data.expenses }
            ]);
        }
    })
    .catch(function (err) {
        console.error('Error fetching income & expense chart data:', err);
        if (chartContainer) chartContainer.style.opacity = '1';
    });
}

function fetchTopExpensesChartData() {
    const periodSelect = document.getElementById('topExpensesPeriodSelect');
    if (!periodSelect) return;

    const period = periodSelect.value;
    const chartContainer = document.getElementById('chart-top-expenses');
    if (chartContainer) chartContainer.style.opacity = '0.45';

    fetch(`{{ route('accounts.dashboard') }}?action=top_expenses_chart&expense_period=${period}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (chartContainer) chartContainer.style.opacity = '1';
        if (donutChart) {
            donutChart.updateOptions({
                labels: data.labels
            });
            donutChart.updateSeries(data.values);
        }
    })
    .catch(function (err) {
        console.error('Error fetching top expenses chart data:', err);
        if (chartContainer) chartContainer.style.opacity = '1';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Ensure Select2 does not bind to chart controls
    killSelect2();
    setTimeout(killSelect2, 60);
    setTimeout(killSelect2, 300);

    // 1. Income & Expense Comparison Chart (ApexCharts)
    const incomeExpenseEl = document.querySelector('#chart-income-expense');
    if (incomeExpenseEl) {
        const incomeLabels = @json($monthlyLabels);
        const incomeSeries = @json($incomeData);
        const expenseSeries = @json($expenseData);

        const options = {
            series: [
                {
                    name: 'Income (Received)',
                    data: incomeSeries
                },
                {
                    name: 'Expenses',
                    data: expenseSeries
                }
            ],
            chart: {
                type: 'bar',
                height: 330,
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif'
            },
            colors: ['#10b981', '#ef4444'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '45%',
                    borderRadius: 6,
                    dataLabels: { position: 'top' }
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: incomeLabels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    style: { colors: '#64748b', fontSize: '11px', fontWeight: 600 },
                    rotate: 0,
                    hideOverlappingLabels: false
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        if (val >= 100000) {
                            return '₹' + (val / 100000).toFixed(1) + 'L';
                        } else if (val >= 1000) {
                            return '₹' + (val / 1000).toFixed(0) + 'k';
                        }
                        return '₹' + val.toFixed(0);
                    },
                    style: { colors: '#64748b', fontSize: '11px' }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: '#334155' },
                markers: { radius: 12 }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (val) {
                        return '₹' + Number(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            }
        };

        incomeExpenseChart = new ApexCharts(incomeExpenseEl, options);
        incomeExpenseChart.render();
    }

    // 2. Top Expenses Donut Chart (ApexCharts)
    const topExpensesEl = document.querySelector('#chart-top-expenses');
    if (topExpensesEl) {
        const expenseLabels = @json($topExpenseLabels);
        const expenseValues = @json($topExpenseValues);

        const donutOptions = {
            series: expenseValues,
            labels: expenseLabels,
            chart: {
                type: 'donut',
                height: 330,
                fontFamily: 'Inter, sans-serif'
            },
            colors: ['#fe5f04', '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#64748b'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '12px',
                                fontWeight: 700,
                                color: '#64748b'
                            },
                            value: {
                                show: true,
                                fontSize: '18px',
                                fontWeight: 800,
                                color: '#0f172a',
                                formatter: function (val) {
                                    return '₹' + Number(val).toLocaleString('en-IN');
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Expense',
                                fontSize: '11px',
                                fontWeight: 700,
                                color: '#64748b',
                                formatter: function (w) {
                                    const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return '₹' + Number(sum).toLocaleString('en-IN');
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                labels: { colors: '#475569' },
                itemMargin: { horizontal: 6, vertical: 4 }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return '₹' + Number(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                }
            },
            noData: {
                text: 'No Expense Records',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    color: '#94a3b8',
                    fontSize: '13px'
                }
            }
        };

        donutChart = new ApexCharts(topExpensesEl, donutOptions);
        donutChart.render();
    }
});
</script>
@endpush
