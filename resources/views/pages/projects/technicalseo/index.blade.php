@extends('layouts.app')

@section('title', 'Active Technical SEO Projects')

@push('styles')
<style>
.seo-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.seo-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.seo-title { font-size:22px; font-weight:900; color:#111827; }
.seo-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.seo-body { padding:22px 28px 34px; display:grid; gap:20px; }

/* Stats Mini Grid */
.seo-stats-mini { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:14px; }
.seo-stat-mini { background:#fff; border:1px solid #eee7df; border-radius:12px; padding:16px 20px; box-shadow:0 4px 12px rgba(15,23,42,.03); }
.seo-stat-mini-label { font-size:10.5px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.seo-stat-mini-val { font-size:22px; font-weight:900; color:#0f172a; margin-top:4px; }

/* Filter Section */
.seo-filter-card { background:#fff; border:1px solid #eee7df; border-radius:14px; padding:18px 22px; box-shadow:0 6px 20px rgba(15,23,42,.03); }
.seo-filter-form { display:grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap:14px; align-items:flex-end; }
.seo-field { display:flex; flex-direction:column; gap:5px; }
.seo-label { font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.seo-input, .seo-select { width:100%; min-height:40px; padding:8px 12px; border-radius:8px; border:1.5px solid #e2e8f0; font-size:13px; color:#1e293b; background:#f8fafc; outline:none; transition:all .15s; font-family:inherit; }
.seo-input:focus, .seo-select:focus { border-color:#ea580c; background:#fff; box-shadow:0 0 0 3px rgba(234,88,12,.12); }
.seo-filter-actions { display:flex; gap:8px; align-items:center; width:100%; }

@media (max-width: 1200px) {
    .seo-filter-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .seo-filter-form { grid-template-columns: repeat(1, minmax(0, 1fr)); }
}

/* Table & Actions */
.seo-card { background:#fff; border:1px solid #eee7df; border-radius:14px; box-shadow:0 10px 30px rgba(15,23,42,.04); overflow:hidden; }
.seo-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 24px; border-bottom:1px solid #f2ede8; background:#fffdfb; }
.seo-card-title { font-size:16px; font-weight:900; color:#111827; }
.seo-card-sub { font-size:12px; color:#64748b; margin-top:2px; }

.seo-btn { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:36px; padding:6px 16px; border-radius:8px; border:1px solid #d7dce2; background:#fff; color:#111827; text-decoration:none; font-size:12.5px; font-weight:700; cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.seo-btn:hover { background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
.seo-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.seo-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.seo-btn-view { background:#f0f9ff; border-color:#bae6fd; color:#0369a1; }
.seo-btn-view:hover { background:#e0f2fe; color:#0284c7; }

.seo-table-wrap { overflow-x:auto; min-height:380px; }
.seo-table { width:100%; border-collapse:collapse; min-width:1000px; }
.seo-table th { padding:14px 18px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.seo-table td { padding:15px 18px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:middle; }
.seo-table tbody tr { cursor:pointer; transition:background .15s ease; }
.seo-table tbody tr:hover td { background:#f0f9ff; }

/* Status Badges */
.seo-status-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; }
.seo-status--delivered { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.seo-status--ontrack { background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff; }
.seo-status--in_progress { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.seo-status--hold { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }

/* Pagination Footer */
.seo-pagination-footer { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; border-top:1px solid #f2ede8; background:#fffdfb; flex-wrap:wrap; gap:16px; }
.seo-pagination-info { font-size:13px; color:#64748b; font-weight:600; }
.seo-pagination-nav { display:inline-flex; align-items:center; gap:4px; }
.seo-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:32px; height:32px; padding:0 10px; border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#475569; font-size:12.5px; font-weight:700; text-decoration:none; cursor:pointer; transition:all .15s ease; }
.seo-page-btn:hover:not(.is-disabled):not(.is-active) { border-color:#fb923c; background:#fff7ed; color:#c2410c; }
.seo-page-btn.is-active { background:linear-gradient(135deg, #ea580c, #f97316); border-color:#ea580c; color:#fff; }
.seo-page-btn.is-disabled { opacity:0.4; cursor:not-allowed; background:#f8fafc; color:#94a3b8; }

@media (max-width: 1200px) {
    .seo-stats-mini { grid-template-columns:repeat(3,1fr); }
}
@media (max-width: 768px) {
    .seo-stats-mini { grid-template-columns:repeat(2,1fr); }
}
</style>
@endpush

@section('content')
<div class="seo-page">
    <div class="seo-topbar">
        <div>
            <div class="seo-title">Active Technical SEO Projects</div>
            <div class="seo-breadcrumb">
                <a href="{{ route('projects.dashboard', ['dashboard_type' => 'dm']) }}" style="color:#7c7c7c; text-decoration:none;">DM Dashboard</a>
                &gt; Technical SEO Accounts
            </div>
        </div>
        <div>
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'dm']) }}" class="seo-btn">
                &larr; Back to DM Dashboard
            </a>
        </div>
    </div>

    <div class="seo-body">
        {{-- Summary Stats Grid --}}
        <div class="seo-stats-mini">
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Total Accounts</div>
                <div class="seo-stat-mini-val" style="color:#6366f1;">{{ $stats['total_accounts'] }}</div>
            </div>
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Active Projects / Renewals</div>
                <div class="seo-stat-mini-val" style="color:#0284c7;">{{ $stats['total_projects'] }}</div>
            </div>
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Total Value</div>
                <div class="seo-stat-mini-val">₹{{ number_format($stats['total_value'], 2) }}</div>
            </div>
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Received</div>
                <div class="seo-stat-mini-val" style="color:#059669;">₹{{ number_format($stats['total_received'], 2) }}</div>
            </div>
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Balance</div>
                <div class="seo-stat-mini-val" style="color:#dc2626;">₹{{ number_format($stats['total_balance'], 2) }}</div>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="seo-filter-card">
            <form method="GET" action="{{ route('projects.technicalseo.index') }}">
                <div class="seo-filter-form">
                    <div class="seo-field">
                        <label class="seo-label" for="search">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Account, client, mobile..."
                            class="seo-input"
                        >
                    </div>

                    <div class="seo-field">
                        <label class="seo-label" for="from_date">From Date</label>
                        <input
                            type="date"
                            id="from_date"
                            name="from_date"
                            value="{{ $filters['from_date'] ?? '' }}"
                            class="seo-input"
                        >
                    </div>

                    <div class="seo-field">
                        <label class="seo-label" for="to_date">To Date</label>
                        <input
                            type="date"
                            id="to_date"
                            name="to_date"
                            value="{{ $filters['to_date'] ?? '' }}"
                            class="seo-input"
                        >
                    </div>

                    <div class="seo-field">
                        <label class="seo-label" for="lead_id">Account</label>
                        <select id="lead_id" name="lead_id" class="seo-select">
                            <option value="">All Accounts</option>
                            @foreach($allAccounts as $acc)
                                <option value="{{ $acc->lead_id }}" {{ ($filters['lead_id'] ?? '') == $acc->lead_id ? 'selected' : '' }}>
                                    {{ $acc->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="seo-field">
                        <label class="seo-label" for="status">Execution Status</label>
                        <select id="status" name="status" class="seo-select">
                            <option value="">All Statuses</option>
                            <option value="ontrack" {{ ($filters['status'] ?? '') == 'ontrack' ? 'selected' : '' }}>Onboard / On Track</option>
                            <option value="in_progress" {{ ($filters['status'] ?? '') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="hold" {{ ($filters['status'] ?? '') == 'hold' ? 'selected' : '' }}>Hold</option>
                            <option value="delivered" {{ ($filters['status'] ?? '') == 'delivered' ? 'selected' : '' }}>Delivered / Completed</option>
                        </select>
                    </div>

                    @php
                        $hasActiveFilters = !empty($filters['search']) || !empty($filters['from_date']) || !empty($filters['to_date']) || !empty($filters['lead_id']) || !empty($filters['status']);
                    @endphp

                    <div class="seo-field" style="grid-column: 1 / -1;">
                        <div class="seo-filter-actions" style="justify-content: flex-end; margin-top:4px;">
                            <button type="submit" class="seo-btn seo-btn-primary">
                                🔍 Apply Filters
                            </button>
                            @if($hasActiveFilters)
                                <a href="{{ route('projects.technicalseo.index') }}" class="seo-btn">
                                    Clear Filters
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table Card with View Switcher (Account Wise vs Project Wise) --}}
        <section class="seo-card">
            <div class="seo-card-head" style="flex-wrap:wrap; gap:12px;">
                <div>
                    <div class="seo-card-title">
                        {{ $viewMode === 'project' ? 'Technical SEO Individual Projects List' : 'Technical SEO Accounts List' }}
                    </div>
                    <div class="seo-card-sub">
                        {{ $viewMode === 'project' 
                            ? 'Individual active Technical SEO projects & renewals. Click any row to view project details.' 
                            : 'Active client accounts grouped with Technical SEO renewals breakdown. Click any row to view account renewals.' }}
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <div style="display:inline-flex; background:#f1f5f9; padding:3px; border-radius:10px; border:1px solid #e2e8f0;">
                        <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'account', 'seo_page' => 1]) }}"
                           class="seo-btn"
                           style="border:none; padding:5px 14px; font-size:12px; border-radius:8px; {{ $viewMode === 'account' ? 'background:#ea580c; color:#fff; font-weight:800; box-shadow:0 2px 6px rgba(234,88,12,.25);' : 'background:transparent; color:#64748b;' }}">
                           🏢 Account Wise ({{ $stats['total_accounts'] }})
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'project', 'seo_page' => 1]) }}"
                           class="seo-btn"
                           style="border:none; padding:5px 14px; font-size:12px; border-radius:8px; {{ $viewMode === 'project' ? 'background:#ea580c; color:#fff; font-weight:800; box-shadow:0 2px 6px rgba(234,88,12,.25);' : 'background:transparent; color:#64748b;' }}">
                           📋 Project Wise ({{ $stats['total_projects'] }})
                        </a>
                    </div>
                    <span style="font-size:12px; font-weight:800; color:#4338ca; background:#eef2ff; border:1px solid #c7d2fe; padding:5px 12px; border-radius:999px;">
                        {{ $viewMode === 'project' ? $projects->total() . ' Projects' : $accounts->total() . ' Accounts' }}
                    </span>
                </div>
            </div>

            <div class="seo-card-body" style="padding:0;">
                @if($viewMode === 'account')
                    {{-- Account Wise Table --}}
                    @if($accounts->isNotEmpty())
                        <div class="seo-table-wrap">
                            <table class="seo-table">
                                <thead>
                                    <tr>
                                        <th>Account Name / Client</th>
                                        <th>Product</th>
                                        <th>Renewals</th>
                                        <th>Allocated Team</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Total Value</th>
                                        <th style="text-align:right;">Received</th>
                                        <th style="text-align:right;">Balance</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accounts as $account)
                                        @php
                                            $statusVal = strtolower((string) ($account->status ?? 'ontrack'));
                                            $statusLabels = [
                                                'ontrack'     => 'Onboard',
                                                'onboard'     => 'Onboard',
                                                'hold'        => 'Hold',
                                                'in_progress' => 'In Progress',
                                                'in progress' => 'In Progress',
                                                'delivered'   => 'Delivered',
                                                'completed'   => 'Delivered',
                                            ];
                                            $statusLabel = $statusLabels[$statusVal] ?? ucfirst($statusVal);
                                            $statusClass = match($statusVal) {
                                                'delivered', 'completed' => 'seo-status--delivered',
                                                'in_progress', 'in progress' => 'seo-status--in_progress',
                                                'hold' => 'seo-status--hold',
                                                default => 'seo-status--ontrack',
                                            };
                                            $targetUrl = $account->campaign_url;
                                        @endphp
                                        <tr onclick="window.location.href='{{ $targetUrl }}'">
                                            <td>
                                                <div style="font-weight:800; color:#0f172a; font-size:14px;">
                                                    {{ $account->company_name ?: 'No Company' }}
                                                </div>
                                                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                                                    {{ $account->client_name }}
                                                    @if($account->mobile_number)
                                                        &bull; {{ $account->mobile_number }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight:700; color:#3730a3; background:#e0e7ff; padding:3px 10px; border-radius:6px; font-size:12px;">
                                                    {{ $account->product_name ?: 'Technical SEO' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-weight:800; color:#b45309; background:#fef3c7; border:1px solid #fde68a; padding:3px 10px; border-radius:999px; font-size:12px;">
                                                    {{ $account->project_count }} {{ Str::plural('Renewal', $account->project_count) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(!empty($account->allocated_tls) && $account->allocated_tls->isNotEmpty())
                                                    <div style="font-size:12px; font-weight:700; color:#4338ca; display:flex; align-items:center; gap:4px;">
                                                        <span style="background:#e0e7ff; color:#3730a3; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:800; text-transform:uppercase;">TL</span>
                                                        {{ $account->allocated_tls->implode(', ') }}
                                                    </div>
                                                @endif
                                                @if(!empty($account->allocated_employees) && $account->allocated_employees->isNotEmpty())
                                                    <div style="font-size:12px; font-weight:600; color:#334155; display:flex; align-items:center; gap:4px; margin-top:3px;">
                                                        <span style="background:#f1f5f9; color:#475569; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:800; text-transform:uppercase;">Team</span>
                                                        {{ $account->allocated_employees->implode(', ') }}
                                                    </div>
                                                @endif
                                                @if((empty($account->allocated_tls) || $account->allocated_tls->isEmpty()) && (empty($account->allocated_employees) || $account->allocated_employees->isEmpty()))
                                                    <span style="font-size:12px; color:#94a3b8; font-style:italic;">Not Allocated</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="seo-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                                            </td>
                                            <td style="text-align:right;"><span style="font-weight:700; color:#0f172a;">₹{{ number_format($account->project_value, 2) }}</span></td>
                                            <td style="text-align:right;"><span style="font-weight:700; color:#059669;">₹{{ number_format($account->received_amount, 2) }}</span></td>
                                            <td style="text-align:right;"><span style="font-weight:700; color:#dc2626;">₹{{ number_format($account->balance_amount, 2) }}</span></td>
                                            <td style="text-align:right;" onclick="event.stopPropagation();">
                                                <a href="{{ $targetUrl }}" class="seo-btn seo-btn-view" title="View renewals page for {{ $account->company_name }}">
                                                    View Renewals &rarr;
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($accounts->hasPages())
                            <div class="seo-pagination-footer">
                                <div class="seo-pagination-info">
                                    Showing <strong>{{ $accounts->firstItem() }}</strong> to <strong>{{ $accounts->lastItem() }}</strong> of <strong>{{ $accounts->total() }}</strong> accounts
                                </div>
                                <nav class="seo-pagination-nav">
                                    @if($accounts->onFirstPage())
                                        <span class="seo-page-btn is-disabled">&larr;</span>
                                    @else
                                        <a href="{{ $accounts->previousPageUrl() }}" class="seo-page-btn">&larr;</a>
                                    @endif

                                    @foreach(range(1, $accounts->lastPage()) as $i)
                                        @if($i == $accounts->currentPage())
                                            <span class="seo-page-btn is-active">{{ $i }}</span>
                                        @elseif($i == 1 || $i == $accounts->lastPage() || abs($i - $accounts->currentPage()) <= 2)
                                            <a href="{{ $accounts->url($i) }}" class="seo-page-btn">{{ $i }}</a>
                                        @endif
                                    @endforeach

                                    @if($accounts->hasMorePages())
                                        <a href="{{ $accounts->nextPageUrl() }}" class="seo-page-btn">&rarr;</a>
                                    @else
                                        <span class="seo-page-btn is-disabled">&rarr;</span>
                                    @endif
                                </nav>
                            </div>
                        @endif
                    @else
                        <div style="padding:48px; text-align:center; color:#64748b; font-size:14px;">
                            No active Technical SEO accounts found matching your criteria.
                        </div>
                    @endif
                @else
                    {{-- Project Wise Table --}}
                    @if($projects->isNotEmpty())
                        <div class="seo-table-wrap">
                            <table class="seo-table">
                                <thead>
                                    <tr>
                                        <th>Project / Product</th>
                                        <th>Account &amp; Client</th>
                                        <th>Delivery / Expiry Date</th>
                                        <th>Allocated Team</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Project Value</th>
                                        <th style="text-align:right;">Received</th>
                                        <th style="text-align:right;">Balance</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($projects as $proj)
                                        @php
                                            $statusVal = strtolower((string) ($proj->project_execution_status ?? 'ontrack'));
                                            $statusLabels = [
                                                'ontrack'     => 'Onboard',
                                                'onboard'     => 'Onboard',
                                                'hold'        => 'Hold',
                                                'in_progress' => 'In Progress',
                                                'in progress' => 'In Progress',
                                                'delivered'   => 'Delivered',
                                                'completed'   => 'Delivered',
                                            ];
                                            $statusLabel = $statusLabels[$statusVal] ?? ucfirst($statusVal);
                                            $statusClass = match($statusVal) {
                                                'delivered', 'completed' => 'seo-status--delivered',
                                                'in_progress', 'in progress' => 'seo-status--in_progress',
                                                'hold' => 'seo-status--hold',
                                                default => 'seo-status--ontrack',
                                            };
                                            $targetUrl = $proj->lead_id ? route('projects.technicalseo.show', $proj->lead_id) : route('projects.show', $proj->id);
                                            $delDateStr = $proj->project_delivery_date ? \Carbon\Carbon::parse($proj->project_delivery_date)->format('d/m/Y') : '—';
                                        @endphp
                                        <tr onclick="window.location.href='{{ $targetUrl }}'">
                                            <td>
                                                <div style="font-weight:800; color:#0f172a; font-size:14px;">
                                                    {{ $proj->product_name ?: 'Technical SEO' }}
                                                </div>
                                                <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                                    Project #{{ $proj->id }}
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-weight:800; color:#0f172a; font-size:13.5px;">
                                                    {{ $proj->company_name ?: ($proj->lead?->company_name ?: 'No Company') }}
                                                </div>
                                                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                                                    {{ $proj->client_name ?: ($proj->lead?->contact_name ?: '') }}
                                                    @if($proj->lead?->mobile_number)
                                                        &bull; {{ $proj->lead->mobile_number }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size:12.5px; font-weight:700; color:#0f172a;">
                                                    {{ $delDateStr }}
                                                </div>
                                            </td>
                                            <td>
                                                @if(!empty($proj->allocated_tl_names) && $proj->allocated_tl_names->isNotEmpty())
                                                    <div style="font-size:12px; font-weight:700; color:#4338ca; display:flex; align-items:center; gap:4px;">
                                                        <span style="background:#e0e7ff; color:#3730a3; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:800; text-transform:uppercase;">TL</span>
                                                        {{ $proj->allocated_tl_names->implode(', ') }}
                                                    </div>
                                                @endif
                                                @if(!empty($proj->allocated_employee_names) && $proj->allocated_employee_names->isNotEmpty())
                                                    <div style="font-size:12px; font-weight:600; color:#334155; display:flex; align-items:center; gap:4px; margin-top:3px;">
                                                        <span style="background:#f1f5f9; color:#475569; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:800; text-transform:uppercase;">Team</span>
                                                        {{ $proj->allocated_employee_names->implode(', ') }}
                                                    </div>
                                                @endif
                                                @if((empty($proj->allocated_tl_names) || $proj->allocated_tl_names->isEmpty()) && (empty($proj->allocated_employee_names) || $proj->allocated_employee_names->isEmpty()))
                                                    <span style="font-size:12px; color:#94a3b8; font-style:italic;">Not Allocated</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="seo-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                                            </td>
                                            <td style="text-align:right;"><span style="font-weight:700; color:#0f172a;">₹{{ number_format($proj->project_value, 2) }}</span></td>
                                            <td style="text-align:right;"><span style="font-weight:700; color:#059669;">₹{{ number_format($proj->received_amount, 2) }}</span></td>
                                            <td style="text-align:right;"><span style="font-weight:700; color:#dc2626;">₹{{ number_format($proj->balance_amount, 2) }}</span></td>
                                            <td style="text-align:right;" onclick="event.stopPropagation();">
                                                <a href="{{ $targetUrl }}" class="seo-btn seo-btn-view" title="View details for project #{{ $proj->id }}">
                                                    View Details &rarr;
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($projects->hasPages())
                            <div class="seo-pagination-footer">
                                <div class="seo-pagination-info">
                                    Showing <strong>{{ $projects->firstItem() }}</strong> to <strong>{{ $projects->lastItem() }}</strong> of <strong>{{ $projects->total() }}</strong> projects
                                </div>
                                <nav class="seo-pagination-nav">
                                    @if($projects->onFirstPage())
                                        <span class="seo-page-btn is-disabled">&larr;</span>
                                    @else
                                        <a href="{{ $projects->previousPageUrl() }}" class="seo-page-btn">&larr;</a>
                                    @endif

                                    @foreach(range(1, $projects->lastPage()) as $i)
                                        @if($i == $projects->currentPage())
                                            <span class="seo-page-btn is-active">{{ $i }}</span>
                                        @elseif($i == 1 || $i == $projects->lastPage() || abs($i - $projects->currentPage()) <= 2)
                                            <a href="{{ $projects->url($i) }}" class="seo-page-btn">{{ $i }}</a>
                                        @endif
                                    @endforeach

                                    @if($projects->hasMorePages())
                                        <a href="{{ $projects->nextPageUrl() }}" class="seo-page-btn">&rarr;</a>
                                    @else
                                        <span class="seo-page-btn is-disabled">&rarr;</span>
                                    @endif
                                </nav>
                            </div>
                        @endif
                    @else
                        <div style="padding:48px; text-align:center; color:#64748b; font-size:14px;">
                            No active Technical SEO projects found matching your criteria.
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
