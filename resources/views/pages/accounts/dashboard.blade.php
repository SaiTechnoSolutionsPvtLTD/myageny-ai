@extends('layouts.app')

@section('title', 'Accounts Dashboard')

@push('styles')
<style>
.acc-dash-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.acc-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.acc-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.acc-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.acc-breadcrumb a { color:#9e9e9e; text-decoration:none; }
.acc-breadcrumb a:hover { color:#fe5f04; }
.acc-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.acc-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .acc-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .acc-body { padding:16px 20px 24px; }
}

.acc-alert-banner { background: linear-gradient(135deg, #fff3cd, #fef3c7); border: 2px solid #f59e0b; border-radius: 14px; padding: 16px 22px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.15); animation: pulseHighlight 2s infinite ease-in-out; }
@keyframes pulseHighlight {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

.acc-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
.acc-stat-card { background:#fff; border-radius:14px; padding:18px 20px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,.02); display:flex; align-items:center; gap:16px; }
.acc-stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acc-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acc-stat-val { font-size:24px; font-weight:800; color:#0f172a; margin-top:4px; line-height:1; }

.acc-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.acc-card-title { font-size:16px; font-weight:800; color:#0f172a; margin-bottom:14px; display:flex; align-items:center; justify-content:space-between; }

.acc-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acc-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acc-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acc-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acc-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

.acc-tbl { width:100%; border-collapse:collapse; }
.acc-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; }
.acc-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.acc-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.ab-tl_pending { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.ab-accounts_pending { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
.ab-approved { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.ab-rejected { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
</style>
@endpush

@section('content')
<div class="acc-dash-page">
    {{-- Topbar --}}
    <div class="acc-topbar">
        <div>
            <div class="acc-title">
                <i class="bi bi-wallet2" style="color:#fe5f04;"></i>
                <span>Accounts Dashboard</span>
            </div>
            <div class="acc-breadcrumb">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Accounts</span>
            </div>
        </div>
        <div class="acc-actions">
            <a href="{{ route('accounts.expenses.create') }}" class="acc-btn acc-btn-primary" style="background:linear-gradient(135deg,#059669,#10b981); box-shadow:0 4px 14px rgba(16,185,129,.25);">
                <i class="bi bi-plus-lg"></i> Add Expense
            </a>
            <a href="{{ route('accounts.ad-budget.clients') }}" class="acc-btn acc-btn-primary">
                <i class="bi bi-plus-lg"></i> Raise Client Ad Budget
            </a>
        </div>
    </div>

    <div class="acc-body">

    {{-- Highlight Pulsing Banner for Pending Accounts Approvals --}}
    @if(($pendingAccountsCount ?? 0) > 0 || ($pendingApprovalsCount ?? 0) > 0)
    <div class="acc-alert-banner">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:12px; background:#f59e0b; color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <div style="font-size:15px; font-weight:800; color:#92400e;">⚡ Action Required: Pending Ad Budget Approvals</div>
                <div style="font-size:13px; color:#b45309; margin-top:2px;">
                    You have <strong>{{ $pendingAccountsCount ?? 0 }}</strong> pending HR/Accounts approval(s) and <strong>{{ $pendingApprovalsCount ?? 0 }}</strong> total pending request(s) waiting for review.
                </div>
            </div>
        </div>
        <a href="{{ route('accounts.ad-budget.clients', ['status' => 'accounts_pending']) }}" class="acc-btn" style="background:#d97706; color:#fff; border:none; font-weight:800;">
            Review Requests &rarr;
        </a>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="acc-stats-grid">
        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <div class="acc-stat-label">Active Ad Accounts</div>
                <div class="acc-stat-val">{{ $activeAdAccountsCount }}</div>
            </div>
        </div>
        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#fff7ed; color:#ea580c;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="acc-stat-label">Client Budget Requests (This Month)</div>
                <div class="acc-stat-val">{{ $clientRequestsCount }}</div>
            </div>
        </div>
        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-currency-rupee"></i>
            </div>
            <div>
                <div class="acc-stat-label">Total Client Budget (This Month)</div>
                <div class="acc-stat-val">₹{{ number_format($totalClientBudgetAmount, 2) }}</div>
            </div>
        </div>
        <div class="acc-stat-card">
            <div class="acc-stat-icon" style="background:#fef3c7; color:#d97706;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="acc-stat-label">Pending Approvals</div>
                <div class="acc-stat-val">{{ $pendingApprovalsCount }}</div>
            </div>
        </div>
    </div>

    {{-- Recent Requests Table --}}
    <div class="acc-card">
        <div class="acc-card-title">
            <span>Recent Ad Budget Requests</span>
            <a href="{{ route('accounts.ad-budget.clients') }}" class="acc-btn acc-btn-outline" style="padding:5px 12px; font-size:12px;">View All</a>
        </div>

        @if($recentRequests->isEmpty())
            <div style="text-align:center; padding:30px; color:#94a3b8;">
                <i class="bi bi-inbox" style="font-size:32px; display:block; margin-bottom:8px;"></i>
                No budget requests raised yet.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="acc-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Type</th>
                            <th>Ad Account</th>
                            <th>Client / Partner</th>
                            <th>Amount</th>
                            <th>Selected Dates</th>
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
                            <td>
                                @if(!empty($req->selected_dates))
                                    @foreach((array)$req->selected_dates as $dt)
                                        <span style="display:inline-block; padding:1px 6px; background:#eff6ff; color:#1d4ed8; border-radius:4px; font-size:10.5px; font-weight:700; margin:1px;">{{ $dt }}</span>
                                    @endforeach
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>{{ $req->requester?->name ?? '—' }}</td>
                            <td>
                                @if($req->status === 'tl_pending')
                                    <span class="acc-badge ab-tl_pending">Pending DM TL</span>
                                @elseif($req->status === 'accounts_pending')
                                    <span class="acc-badge ab-accounts_pending">Pending Accounts</span>
                                @elseif($req->status === 'approved')
                                    <span class="acc-badge ab-approved">Approved</span>
                                @elseif($req->status === 'rejected')
                                    <span class="acc-badge ab-rejected">Rejected</span>
                                @else
                                    <span class="acc-badge ab-{{ $req->status }}">{{ ucfirst($req->status) }}</span>
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
@endsection
