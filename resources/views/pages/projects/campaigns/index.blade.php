@extends('layouts.app')

@section('title', 'Digital Marketing Campaigns')

@push('styles')
<style>
.cmp-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.cmp-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.cmp-title { font-size:24px; font-weight:900; color:#111827; }
.cmp-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.cmp-topbar-actions { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.cmp-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:999px; background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:12px; font-weight:800; }

/* View Switcher Tabs */
.cmp-view-tabs { display:inline-flex; background:#f1f5f9; border-radius:10px; padding:3px; gap:3px; border:1px solid #e2e8f0; }
.cmp-view-tab { padding:7px 16px; border-radius:8px; font-size:12.5px; font-weight:800; text-decoration:none; color:#64748b; transition:all 0.15s; display:inline-flex; align-items:center; gap:6px; user-select:none; }
.cmp-view-tab:hover { color:#0f172a; }
.cmp-view-tab.is-active { background:#fff; color:#ea580c; box-shadow:0 2px 6px rgba(0,0,0,0.08); }

.cmp-body { padding:22px 28px 34px; display:grid; gap:20px; }

/* Stats Grid - 6 cards */
.cmp-stats { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:14px; }
.cmp-stat { position:relative; overflow:hidden; background:var(--stat-gradient); border:none; border-radius:16px; padding:16px 18px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.08),0 8px 10px -6px rgba(0,0,0,0.04); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; text-decoration:none; cursor:pointer; }
.cmp-stat:hover { transform:translateY(-4px); box-shadow:0 18px 25px -5px rgba(0,0,0,0.12),0 10px 10px -5px rgba(0,0,0,0.06); color:#fff; }
.cmp-stat.is-active-card { box-shadow: 0 0 0 3px #ea580c, 0 18px 25px -5px rgba(0,0,0,0.12); }
.cmp-stat-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.cmp-stat-icon { display:flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:10px; background:rgba(255,255,255,0.22); color:#fff; font-size:15px; backdrop-filter:blur(4px); }
.cmp-stat-label { font-size:10px; font-weight:800; color:rgba(255,255,255,0.92); text-transform:uppercase; letter-spacing:.06em; text-shadow:0 1px 2px rgba(0,0,0,0.1); }
.cmp-stat-value { font-size:24px; font-weight:900; color:#fff; line-height:1.2; text-shadow:0 2px 4px rgba(0,0,0,0.1); }
.cmp-stat-footer { margin-top:8px; padding-top:8px; border-top:1px dashed rgba(255,255,255,0.25); font-size:10.5px; color:rgba(255,255,255,0.92); font-weight:600; text-shadow:0 1px 2px rgba(0,0,0,0.1); }

/* Filter Card */
.cmp-filter-card { background:#fff; border:1px solid #eee7df; border-radius:14px; overflow:hidden; box-shadow:0 6px 20px rgba(15,23,42,.03); }
.cmp-filter-head { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f2ede8; background:#fffdfb; }
.cmp-filter-title { font-size:14px; font-weight:800; color:#1e293b; display:flex; align-items:center; gap:8px; }
.cmp-filter-badge { display:inline-flex; align-items:center; justify-content:center; padding:2px 8px; border-radius:999px; background:#ea580c; color:#fff; font-size:11px; font-weight:800; }
.cmp-filter-body { padding:18px 20px; }
.cmp-filter-grid { display:grid; grid-template-columns:1.5fr 1fr 1.3fr 1.2fr 1fr 1fr auto; gap:12px; align-items:end; }
.cmp-field { display:flex; flex-direction:column; gap:5px; }
.cmp-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#64748b; }
.cmp-input, .cmp-select { width:100%; min-height:40px; padding:8px 12px; border-radius:8px; border:1.5px solid #e2e8f0; font-size:13px; color:#1e293b; background:#f8fafc; outline:none; transition:all .15s; font-family:inherit; }
.cmp-input:focus, .cmp-select:focus { border-color:#ea580c; background:#fff; box-shadow:0 0 0 3px rgba(234,88,12,.12); }
.cmp-filter-actions { display:flex; align-items:center; gap:8px; }

/* Buttons */
.cmp-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:40px; padding:8px 16px; border-radius:8px; border:1px solid #d7dce2; background:#fff; color:#111827; text-decoration:none; font-size:13px; font-weight:700; cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.cmp-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.cmp-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.cmp-btn-outline { border:1px solid #cbd5e1; background:#fff; color:#64748b; }
.cmp-btn-outline:hover { background:#f1f5f9; color:#0f172a; border-color:#94a3b8; }
.cmp-btn-sm { min-height:32px; padding:4px 12px; font-size:12px; }

/* Card & Table */
.cmp-card { background:#fff; border:1px solid #eee7df; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,.04); }
.cmp-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 24px; border-bottom:1px solid #f2ede8; background:#fffdfb; flex-wrap:wrap; }
.cmp-card-title { font-size:16px; font-weight:900; color:#111827; display:flex; align-items:center; gap:8px; }
.cmp-card-sub { margin-top:3px; font-size:12px; color:#7c7c7c; }

.cmp-table-wrap { overflow-x:auto; }
.cmp-table { width:100%; border-collapse:collapse; min-width:1050px; }
.cmp-table th { padding:13px 16px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.cmp-table td { padding:15px 16px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:middle; }
.cmp-table tbody tr:hover td { background:#fffaf5; }

/* Company & Customer */
.cmp-company-name { font-weight:800; font-size:13.5px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.cmp-avatar { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg, #ea580c, #fb923c); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; flex-shrink:0; }
.cmp-customer-name { font-weight:700; color:#1e293b; font-size:12.5px; }
.cmp-contact-meta { font-size:11px; color:#64748b; margin-top:2px; display:flex; flex-direction:column; gap:2px; }

/* Campaign Title & Meta */
.cmp-campaign-title { font-weight:800; font-size:14px; color:#0f172a; line-height:1.3; }
.cmp-campaign-sub { font-size:11.5px; color:#64748b; margin-top:3px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.cmp-platform-tag { display:inline-flex; align-items:center; gap:4px; padding:2px 7px; border-radius:6px; font-size:10.5px; font-weight:800; background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
.cmp-extended-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 7px; border-radius:6px; font-size:10.5px; font-weight:800; background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; }

/* Team Allocation Badges */
.cmp-team-wrap { display:flex; flex-wrap:wrap; gap:6px; max-width:260px; }
.cmp-team-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; }
.cmp-team-badge--tl { background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; }
.cmp-team-badge--emp { background:#f8fafc; color:#475569; border:1px solid #e2e8f0; }

/* Status Badges */
.cmp-status-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:800; white-space:nowrap; }
.cmp-status-badge--active { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.cmp-status-badge--paused { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.cmp-status-badge--expired { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.cmp-status-badge--stopped { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
.cmp-status-badge--inactive { background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; }
.cmp-status-badge--none { background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; font-style:italic; font-weight:600; }
.cmp-pulse-dot { width:7px; height:7px; border-radius:50%; background:#10b981; display:inline-block; margin-right:3px; box-shadow:0 0 0 0 rgba(16,185,129,0.7); animation:cmpPulse 2s infinite; }

/* Renewal Badges */
.cmp-renewal-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:800; white-space:nowrap; }
.cmp-renewal-badge--warning { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.cmp-renewal-badge--success { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.cmp-renewal-badge--danger { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.cmp-renewal-badge--info { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.cmp-renewal-badge--neutral { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }

/* Pill Counts */
.cmp-count-badge { display:inline-flex; align-items:center; justify-content:center; padding:4px 12px; border-radius:999px; font-size:13px; font-weight:800; }
.cmp-count-total { background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; }

/* Pagination Footer */
.cmp-pagination-footer { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; border-top:1px solid #f2ede8; background:#fffdfb; flex-wrap:wrap; gap:16px; }
.cmp-pagination-left { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.cmp-pagination-info { font-size:13px; color:#64748b; font-weight:600; display:flex; align-items:center; gap:6px; }
.cmp-pagination-info strong { color:#0f172a; font-weight:800; }
.cmp-pagination-per-page { display:flex; align-items:center; gap:8px; font-size:12px; color:#64748b; font-weight:700; }
.cmp-pagination-select { padding:4px 8px; border-radius:6px; border:1.5px solid #e2e8f0; font-size:12px; font-weight:700; color:#1e293b; background:#fff; outline:none; cursor:pointer; transition:border-color .15s; }
.cmp-pagination-select:focus { border-color:#ea580c; }

.cmp-pagination-nav { display:inline-flex; align-items:center; gap:4px; list-style:none; margin:0; padding:0; }
.cmp-page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 10px; border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#475569; font-size:13px; font-weight:700; text-decoration:none; cursor:pointer; transition:all .15s ease; user-select:none; }
.cmp-page-btn:hover:not(.is-disabled):not(.is-active) { border-color:#fb923c; background:#fff7ed; color:#c2410c; }
.cmp-page-btn.is-active { background:linear-gradient(135deg, #ea580c, #f97316); border-color:#ea580c; color:#fff; box-shadow:0 3px 10px rgba(234,88,12,0.28); cursor:default; }
.cmp-page-btn.is-disabled { opacity:0.4; cursor:not-allowed; background:#f8fafc; color:#94a3b8; border-color:#e2e8f0; }
.cmp-page-ellipsis { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:34px; color:#94a3b8; font-weight:800; font-size:13px; }

@keyframes cmpPulse {
    0% { transform:scale(0.95); box-shadow:0 0 0 0 rgba(16,185,129,0.7); }
    70% { transform:scale(1); box-shadow:0 0 0 6px rgba(16,185,129,0); }
    100% { transform:scale(0.95); box-shadow:0 0 0 0 rgba(16,185,129,0); }
}

@media (max-width: 1400px) {
    .cmp-stats { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .cmp-filter-grid { grid-template-columns:repeat(3, minmax(0, 1fr)); }
    .cmp-filter-actions { grid-column:span 3; justify-content:flex-end; }
}
@media (max-width: 900px) {
    .cmp-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .cmp-filter-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    .cmp-filter-actions { grid-column:span 2; }
}
@media (max-width: 640px) {
    .cmp-stats { grid-template-columns:1fr; }
    .cmp-filter-grid { grid-template-columns:1fr; }
    .cmp-filter-actions { grid-column:1; }
    .cmp-topbar, .cmp-body { padding:16px; }
    .cmp-pagination-footer { flex-direction:column; align-items:flex-start; }
}
</style>
@endpush

@section('content')
<div class="cmp-page">
    {{-- Topbar --}}
    <div class="cmp-topbar">
        <div>
            <div class="cmp-title">Digital Marketing Campaigns</div>
            <div class="cmp-breadcrumb">Modules &gt; Production &gt; Campaigns</div>
        </div>
        <div class="cmp-topbar-actions">
            {{-- View Switcher --}}
            <div class="cmp-view-tabs">
                <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['page']), ['view' => 'campaign'])) }}"
                   class="cmp-view-tab {{ $viewMode === 'campaign' ? 'is-active' : '' }}">
                    📢 Campaign-Wise View
                </a>
                <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['page']), ['view' => 'account'])) }}"
                   class="cmp-view-tab {{ $viewMode === 'account' ? 'is-active' : '' }}">
                    🏢 Account-Wise View
                </a>
            </div>

            <div class="cmp-chip">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                    <line x1="4" y1="22" x2="4" y2="15"></line>
                </svg>
                Digital Marketing Production
            </div>
        </div>
    </div>

    <div class="cmp-body">
        {{-- KPI Cards (Clickable quick filters) --}}
        <section class="cmp-stats">
            {{-- 1. Total Campaigns --}}
            <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['status', 'renewal_filter', 'page']), ['view' => 'campaign'])) }}"
               class="cmp-stat {{ empty($filters['status']) && empty($filters['renewal_filter']) && $viewMode === 'campaign' ? 'is-active-card' : '' }}"
               style="--stat-gradient: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Total Campaigns</span>
                    <span class="cmp-stat-icon">📢</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_campaigns'] }}</div>
                <div class="cmp-stat-footer">
                    All customer campaigns
                </div>
            </a>

            {{-- 2. Active Campaigns --}}
            <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['status', 'renewal_filter', 'page']), ['view' => 'campaign', 'status' => 'active'])) }}"
               class="cmp-stat {{ ($filters['status'] ?? '') === 'active' && empty($filters['renewal_filter']) ? 'is-active-card' : '' }}"
               style="--stat-gradient: linear-gradient(135deg, #059669 0%, #34d399 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Active</span>
                    <span class="cmp-stat-icon">⚡</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_active_campaigns'] }}</div>
                <div class="cmp-stat-footer">
                    Currently running
                </div>
            </a>

            {{-- 3. Current Month Not Renewed --}}
            <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['status', 'renewal_filter', 'page']), ['view' => 'campaign', 'renewal_filter' => 'cm_not_renewed'])) }}"
               class="cmp-stat {{ ($filters['renewal_filter'] ?? '') === 'cm_not_renewed' ? 'is-active-card' : '' }}"
               style="--stat-gradient: linear-gradient(135deg, #d97706 0%, #fbbf24 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">CM Not Renewed</span>
                    <span class="cmp-stat-icon">⚠️</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_cm_not_renewed'] ?? 0 }}</div>
                <div class="cmp-stat-footer">
                    Ending this month (Pending)
                </div>
            </a>

            {{-- 4. Current Month Renewed --}}
            <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['status', 'renewal_filter', 'page']), ['view' => 'campaign', 'renewal_filter' => 'cm_renewed'])) }}"
               class="cmp-stat {{ ($filters['renewal_filter'] ?? '') === 'cm_renewed' ? 'is-active-card' : '' }}"
               style="--stat-gradient: linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">CM Renewed</span>
                    <span class="cmp-stat-icon">✅</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_cm_renewed'] ?? 0 }}</div>
                <div class="cmp-stat-footer">
                    Renewed this month
                </div>
            </a>

            {{-- 5. Expired Campaigns --}}
            <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['status', 'renewal_filter', 'page']), ['view' => 'campaign', 'renewal_filter' => 'expired'])) }}"
               class="cmp-stat {{ ($filters['renewal_filter'] ?? '') === 'expired' || ($filters['status'] ?? '') === 'expired' ? 'is-active-card' : '' }}"
               style="--stat-gradient: linear-gradient(135deg, #dc2626 0%, #f87171 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Expired</span>
                    <span class="cmp-stat-icon">⏳</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_expired_campaigns'] }}</div>
                <div class="cmp-stat-footer">
                    Past end date
                </div>
            </a>

            {{-- 6. Total Leads --}}
            <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['status', 'renewal_filter', 'page']), ['view' => 'account'])) }}"
               class="cmp-stat {{ $viewMode === 'account' ? 'is-active-card' : '' }}"
               style="--stat-gradient: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Total DM Leads</span>
                    <span class="cmp-stat-icon">🏢</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_leads'] }}</div>
                <div class="cmp-stat-footer">
                    Account-wise view
                </div>
            </a>
        </section>

        {{-- Filters Section --}}
        <section class="cmp-filter-card">
            <div class="cmp-filter-head">
                <div class="cmp-filter-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:#ea580c;">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    <span>Filter Campaigns</span>
                    @if($hasActiveFilters)
                        <span class="cmp-filter-badge">Active Filters</span>
                    @endif
                </div>
                @if($hasActiveFilters)
                    <a href="{{ route('projects.campaigns.index', ['view' => $viewMode]) }}" class="cmp-btn cmp-btn-outline cmp-btn-sm">
                        ✕ Clear All Filters
                    </a>
                @endif
            </div>

            <div class="cmp-filter-body">
                <form method="GET" action="{{ route('projects.campaigns.index') }}">
                    <input type="hidden" name="view" value="{{ $viewMode }}">

                    <div class="cmp-filter-grid">
                        {{-- 1. Search Textbox --}}
                        <div class="cmp-field">
                            <label class="cmp-label" for="filter_company_name">Search Campaign / Client</label>
                            <input
                                type="text"
                                id="filter_company_name"
                                name="company_name"
                                value="{{ $filters['company_name'] ?? '' }}"
                                placeholder="Search campaign, platform, client..."
                                class="cmp-input"
                            >
                        </div>

                        {{-- 2. Branch Dropdown --}}
                        <div class="cmp-field">
                            <label class="cmp-label" for="filter_branch_id">Branch</label>
                            <select id="filter_branch_id" name="branch_id" class="cmp-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (string)($filters['branch_id'] ?? '') === (string)$branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 3. Status & Renewal Dropdown --}}
                        <div class="cmp-field">
                            <label class="cmp-label" for="filter_status">Status / Renewal</label>
                            <select id="filter_status" name="status" class="cmp-select">
                                <option value="">All Statuses & Renewals</option>
                                <option value="active" {{ ($filters['status'] ?? '') === 'active' && empty($filters['renewal_filter']) ? 'selected' : '' }}>🟢 Active</option>
                                <option value="paused" {{ ($filters['status'] ?? '') === 'paused' ? 'selected' : '' }}>🟡 Paused</option>
                                <option value="expired" {{ ($filters['status'] ?? '') === 'expired' || ($filters['renewal_filter'] ?? '') === 'expired' ? 'selected' : '' }}>🔴 Expired</option>
                                <option value="cm_not_renewed" {{ ($filters['renewal_filter'] ?? '') === 'cm_not_renewed' || ($filters['status'] ?? '') === 'cm_not_renewed' ? 'selected' : '' }}>⚠️ Current Month Not Renewed</option>
                                <option value="cm_renewed" {{ ($filters['renewal_filter'] ?? '') === 'cm_renewed' || ($filters['status'] ?? '') === 'cm_renewed' ? 'selected' : '' }}>✅ Current Month Renewed</option>
                                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>⚪ Inactive / Stopped</option>
                            </select>
                        </div>

                        {{-- 4. Employee Wise Dropdown --}}
                        <div class="cmp-field">
                            <label class="cmp-label" for="filter_employee_id">Employee Wise</label>
                            <select id="filter_employee_id" name="employee_id" class="cmp-select">
                                <option value="">All Employees / TLs</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ (string)($filters['employee_id'] ?? '') === (string)$emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 5. Start Date --}}
                        <div class="cmp-field">
                            <label class="cmp-label" for="filter_start_date">Start Date</label>
                            <input
                                type="date"
                                id="filter_start_date"
                                name="start_date"
                                value="{{ $filters['start_date'] ?? '' }}"
                                class="cmp-input"
                            >
                        </div>

                        {{-- 6. End Date --}}
                        <div class="cmp-field">
                            <label class="cmp-label" for="filter_end_date">End Date</label>
                            <input
                                type="date"
                                id="filter_end_date"
                                name="end_date"
                                value="{{ $filters['end_date'] ?? '' }}"
                                class="cmp-input"
                            >
                        </div>

                        {{-- Action Buttons --}}
                        <div class="cmp-filter-actions">
                            <button type="submit" class="cmp-btn cmp-btn-primary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                Filter
                            </button>
                            @if($hasActiveFilters)
                                <a href="{{ route('projects.campaigns.index', ['view' => $viewMode]) }}" class="cmp-btn cmp-btn-outline" title="Reset Filters">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </section>

        {{-- Main Table Card --}}
        <section class="cmp-card">
            <div class="cmp-card-head">
                <div>
                    @if($viewMode === 'campaign')
                        <div class="cmp-card-title">
                            <span>Campaigns List</span>
                            <span style="font-size:12px; font-weight:700; color:#0284c7; background:#f0f9ff; padding:3px 10px; border-radius:999px; border:1px solid #bae6fd;">
                                {{ $campaigns->total() }} {{ \Illuminate\Support\Str::plural('Campaign', $campaigns->total()) }}
                            </span>
                            @if(!empty($renewalFilter))
                                <span style="font-size:11.5px; font-weight:800; color:#ea580c; background:#fff7ed; padding:3px 10px; border-radius:999px; border:1px solid #fed7aa;">
                                    Filter: {{ str_replace('_', ' ', strtoupper($renewalFilter)) }}
                                </span>
                            @endif
                        </div>
                        <div class="cmp-card-sub">
                            Showing individual digital marketing campaigns with renewal & performance tracking.
                        </div>
                    @else
                        <div class="cmp-card-title">
                            <span>Campaigns by Customer / Lead</span>
                            <span style="font-size:12px; font-weight:700; color:#ea580c; background:#fff7ed; padding:3px 10px; border-radius:999px; border:1px solid #fed7aa;">
                                {{ $leads->total() }} {{ \Illuminate\Support\Str::plural('Lead', $leads->total()) }}
                            </span>
                        </div>
                        <div class="cmp-card-sub">
                            Showing leads whose Digital Marketing products are moved to production with budget approval required.
                        </div>
                    @endif
                </div>

                {{-- View Toggle inside Card Header as well --}}
                <div class="cmp-view-tabs">
                    <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['page']), ['view' => 'campaign'])) }}"
                       class="cmp-view-tab {{ $viewMode === 'campaign' ? 'is-active' : '' }}">
                        📢 Campaign-Wise
                    </a>
                    <a href="{{ route('projects.campaigns.index', array_merge(request()->except(['page']), ['view' => 'account'])) }}"
                       class="cmp-view-tab {{ $viewMode === 'account' ? 'is-active' : '' }}">
                        🏢 Account-Wise
                    </a>
                </div>
            </div>

            <div class="cmp-card-body" style="padding:0;">
                @if($viewMode === 'campaign')
                    {{-- ── CAMPAIGN-WISE TABLE ── --}}
                    <div class="cmp-table-wrap">
                        <table class="cmp-table">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Campaign & Platform</th>
                                    <th style="width: 22%;">Client / Company</th>
                                    <th style="width: 14%;">Budget</th>
                                    <th style="width: 14%;">Schedule</th>
                                    <th style="width: 10%; text-align: center;">Status</th>
                                    <th style="width: 11%; text-align: center;">Renewal</th>
                                    <th style="width: 4%; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($campaigns as $campaign)
                                    @php
                                        $endDateStr = $campaign->end_date ? \Carbon\Carbon::parse($campaign->end_date)->toDateString() : null;
                                        $createdAtStr = \Carbon\Carbon::parse($campaign->created_at)->toDateString();

                                        $isCmNotRenewed = $endDateStr && $endDateStr >= $cmStart && $endDateStr <= $cmEnd && !in_array($campaign->id, $extendedParentIds, true);
                                        $isCmRenewed = (in_array($campaign->id, $extendedParentIds, true) && $endDateStr && $endDateStr >= $cmStart && $endDateStr <= $cmEnd)
                                            || (!empty($campaign->extended_from_id) && $createdAtStr >= $cmStart && $createdAtStr <= $cmEnd);
                                    @endphp
                                    <tr>
                                        {{-- 1. Campaign Name & Platform --}}
                                        <td>
                                            <div class="cmp-campaign-title">{{ $campaign->campaign_name }}</div>
                                            <div class="cmp-campaign-sub">
                                                @if($campaign->platform)
                                                    <span class="cmp-platform-tag">
                                                        🌐 {{ $campaign->platform }}
                                                    </span>
                                                @endif
                                                @if($campaign->ad_account_name)
                                                    <span style="color:#64748b; font-size:11px;">
                                                        Acct: <strong>{{ $campaign->ad_account_name }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                            @if($campaign->extended_from_id)
                                                <div style="margin-top: 4px;">
                                                    <span class="cmp-extended-badge" title="Renewed extension from {{ $campaign->extendedFrom?->campaign_name }}">
                                                        🔄 Extension of: {{ \Illuminate\Support\Str::limit($campaign->extendedFrom?->campaign_name ?? 'Previous Campaign', 24) }}
                                                    </span>
                                                </div>
                                            @elseif($campaign->extensions && $campaign->extensions->isNotEmpty())
                                                <div style="margin-top: 4px;">
                                                    <span class="cmp-extended-badge" style="background:#eff6ff; color:#1d4ed8; border-color:#dbeafe;" title="Has {{ $campaign->extensions->count() }} renewals">
                                                        ✨ Renewed ({{ $campaign->extensions->count() }} {{ \Illuminate\Support\Str::plural('time', $campaign->extensions->count()) }})
                                                    </span>
                                                </div>
                                            @endif
                                        </td>

                                        {{-- 2. Client / Company --}}
                                        <td>
                                            <div class="cmp-company-name">
                                                <div class="cmp-avatar">
                                                    {{ strtoupper(substr($campaign->lead?->company_name ?: ($campaign->lead?->contact_name ?: 'C'), 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div>{{ $campaign->lead?->company_name ?: '—' }}</div>
                                                </div>
                                            </div>
                                            @if($campaign->lead?->contact_name)
                                                <div class="cmp-customer-name" style="margin-top:3px; margin-left:40px;">
                                                    👤 {{ $campaign->lead->contact_name }}
                                                </div>
                                            @endif
                                            <div class="cmp-contact-meta" style="margin-left:40px;">
                                                @if($campaign->lead?->mobile_number)
                                                    <span>📞 {{ $campaign->lead->mobile_number }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- 3. Budget --}}
                                        <td>
                                            <div style="font-weight:800; color:#0f172a; font-size:13.5px;">
                                                ₹{{ number_format((float) ($campaign->budget_amount ?? 0), 2) }}
                                            </div>
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                                <span>{{ ucfirst($campaign->budget_type ?? 'Monthly') }}</span>
                                                @if($campaign->calculateDailyBudget() > 0)
                                                    <span style="color:#059669; font-weight:700;">(₹{{ number_format($campaign->calculateDailyBudget(), 2) }}/d)</span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- 4. Schedule --}}
                                        <td>
                                            <div style="font-size:12px; font-weight:700; color:#1e293b;">
                                                {{ $campaign->start_date ? \Carbon\Carbon::parse($campaign->start_date)->format('d M, Y') : '—' }}
                                            </div>
                                            <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                                to {{ $campaign->end_date ? \Carbon\Carbon::parse($campaign->end_date)->format('d M, Y') : 'Ongoing' }}
                                            </div>
                                            <div style="font-size:10.5px; color:#94a3b8; margin-top:1px;">
                                                Duration: {{ $campaign->calculateRunDays() }} days run
                                            </div>
                                        </td>

                                        {{-- 5. Campaign Status --}}
                                        <td style="text-align: center;">
                                            @if($campaign->status === 'active' && !$campaign->isExpired() && !$campaign->isStopped())
                                                <span class="cmp-status-badge cmp-status-badge--active">
                                                    <span class="cmp-pulse-dot"></span> Active
                                                </span>
                                            @elseif($campaign->status === 'paused')
                                                <span class="cmp-status-badge cmp-status-badge--paused">
                                                    🟡 Paused
                                                </span>
                                            @elseif($campaign->isExpired())
                                                <span class="cmp-status-badge cmp-status-badge--expired">
                                                    🔴 Expired
                                                </span>
                                            @elseif($campaign->isStopped())
                                                <span class="cmp-status-badge cmp-status-badge--stopped">
                                                    🛑 Stopped
                                                </span>
                                            @else
                                                <span class="cmp-status-badge cmp-status-badge--inactive">
                                                    ⚪ {{ ucfirst($campaign->status ?? 'Inactive') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- 6. Renewal Status --}}
                                        <td style="text-align: center;">
                                            @if($isCmNotRenewed)
                                                <span class="cmp-renewal-badge cmp-renewal-badge--warning" title="Ending in current month, not yet renewed">
                                                    ⚠️ CM Not Renewed
                                                </span>
                                            @elseif($isCmRenewed)
                                                <span class="cmp-renewal-badge cmp-renewal-badge--success" title="Renewed in current month">
                                                    ✅ CM Renewed
                                                </span>
                                            @elseif($campaign->isExpired())
                                                <span class="cmp-renewal-badge cmp-renewal-badge--danger" title="Campaign period ended">
                                                    🔴 Expired
                                                </span>
                                            @elseif($campaign->extended_from_id)
                                                <span class="cmp-renewal-badge cmp-renewal-badge--info" title="Active renewal extension">
                                                    🔄 Extension
                                                </span>
                                            @elseif(in_array($campaign->id, $extendedParentIds, true))
                                                <span class="cmp-renewal-badge cmp-renewal-badge--info" title="Renewed previously">
                                                    ✨ Extended
                                                </span>
                                            @else
                                                <span class="cmp-renewal-badge cmp-renewal-badge--neutral">
                                                    Ongoing
                                                </span>
                                            @endif
                                        </td>

                                        {{-- 7. Action --}}
                                        <td style="text-align: right;">
                                            <a href="{{ route('projects.campaigns.show', $campaign->lead_id) }}" class="cmp-btn cmp-btn-primary cmp-btn-sm" title="Manage Customer Campaigns">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                Manage
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 48px 20px; color: #64748b;">
                                            <div style="font-size: 36px; margin-bottom: 8px;">📢</div>
                                            <div style="font-size: 16px; font-weight: 700; color: #1e293b;">No Campaigns Found</div>
                                            <div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">
                                                @if($hasActiveFilters)
                                                    No campaigns match your active filter criteria. Try clearing or modifying the filters above.
                                                @else
                                                    There are currently no customer campaigns recorded.
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Campaign Pagination Footer --}}
                    @if($campaigns->hasPages() || $campaigns->total() > 0)
                        <div class="cmp-pagination-footer">
                            <div class="cmp-pagination-left">
                                <div class="cmp-pagination-info">
                                    <span>Showing</span>
                                    <strong>{{ $campaigns->firstItem() ?? 0 }}</strong>
                                    <span>to</span>
                                    <strong>{{ $campaigns->lastItem() ?? 0 }}</strong>
                                    <span>of</span>
                                    <strong>{{ $campaigns->total() }}</strong>
                                    <span>campaigns</span>
                                </div>

                                <div class="cmp-pagination-per-page">
                                    <span>Rows:</span>
                                    <select class="cmp-pagination-select" onchange="const url = new URL(window.location.href); url.searchParams.set('per_page', this.value); url.searchParams.set('page', 1); window.location.href = url.toString();">
                                        @foreach([10, 25, 50, 100] as $size)
                                            <option value="{{ $size }}" {{ (int)request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if($campaigns->hasPages())
                                @php
                                    $currentPage = $campaigns->currentPage();
                                    $lastPage = $campaigns->lastPage();
                                    $start = max(1, $currentPage - 2);
                                    $end = min($lastPage, $currentPage + 2);
                                @endphp

                                <nav class="cmp-pagination-nav">
                                    {{-- Previous Button --}}
                                    @if($campaigns->onFirstPage())
                                        <span class="cmp-page-btn is-disabled" title="Previous Page">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                        </span>
                                    @else
                                        <a href="{{ $campaigns->previousPageUrl() }}" class="cmp-page-btn" title="Previous Page">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                        </a>
                                    @endif

                                    {{-- First Page if out of range --}}
                                    @if($start > 1)
                                        <a href="{{ $campaigns->url(1) }}" class="cmp-page-btn {{ $currentPage == 1 ? 'is-active' : '' }}">1</a>
                                        @if($start > 2)
                                            <span class="cmp-page-ellipsis">...</span>
                                        @endif
                                    @endif

                                    {{-- Page Numbers --}}
                                    @for($page = $start; $page <= $end; $page++)
                                        @if($page == $currentPage)
                                            <span class="cmp-page-btn is-active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $campaigns->url($page) }}" class="cmp-page-btn">{{ $page }}</a>
                                        @endif
                                    @endfor

                                    {{-- Last Page if out of range --}}
                                    @if($end < $lastPage)
                                        @if($end < $lastPage - 1)
                                            <span class="cmp-page-ellipsis">...</span>
                                        @endif
                                        <a href="{{ $campaigns->url($lastPage) }}" class="cmp-page-btn {{ $currentPage == $lastPage ? 'is-active' : '' }}">{{ $lastPage }}</a>
                                    @endif

                                    {{-- Next Button --}}
                                    @if($campaigns->hasMorePages())
                                        <a href="{{ $campaigns->nextPageUrl() }}" class="cmp-page-btn" title="Next Page">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                        </a>
                                    @else
                                        <span class="cmp-page-btn is-disabled" title="Next Page">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                        </span>
                                    @endif
                                </nav>
                            @endif
                        </div>
                    @endif

                @else
                    {{-- ── ACCOUNT-WISE TABLE ── --}}
                    <div class="cmp-table-wrap">
                        <table class="cmp-table">
                            <thead>
                                <tr>
                                    <th style="width: 22%;">Company Name</th>
                                    <th style="width: 17%;">Customer Name</th>
                                    <th style="width: 20%;">Team Allocation</th>
                                    <th style="width: 11%; text-align: center;">Total Campaigns</th>
                                    <th style="width: 22%; text-align: center;">Campaign Status</th>
                                    <th style="width: 8%; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leads as $lead)
                                    <tr>
                                        {{-- Company Name --}}
                                        <td>
                                            <div class="cmp-company-name">
                                                <div class="cmp-avatar">
                                                    {{ strtoupper(substr($lead->company_name ?: ($lead->contact_name ?: 'C'), 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div>{{ $lead->company_name ?: '—' }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Customer Name --}}
                                        <td>
                                            <div class="cmp-customer-name">{{ $lead->contact_name ?: '—' }}</div>
                                            <div class="cmp-contact-meta">
                                                @if($lead->mobile_number)
                                                    <span>📞 {{ $lead->mobile_number }}</span>
                                                @endif
                                                @if($lead->email)
                                                    <span>✉️ {{ $lead->email }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Team Allocation --}}
                                        <td>
                                            <div class="cmp-team-wrap">
                                                @if($lead->allocated_tls && $lead->allocated_tls->isNotEmpty())
                                                    @foreach($lead->allocated_tls as $tl)
                                                        <span class="cmp-team-badge cmp-team-badge--tl" title="Team Leader">
                                                            👑 TL: {{ $tl->name }}
                                                        </span>
                                                    @endforeach
                                                @endif

                                                @if($lead->allocated_employees && $lead->allocated_employees->isNotEmpty())
                                                    @foreach($lead->allocated_employees as $emp)
                                                        <span class="cmp-team-badge cmp-team-badge--emp" title="Team Member">
                                                            👤 {{ $emp->name }}
                                                        </span>
                                                    @endforeach
                                                @endif

                                                @if((!$lead->allocated_tls || $lead->allocated_tls->isEmpty()) && (!$lead->allocated_employees || $lead->allocated_employees->isEmpty()))
                                                    <span style="color:#94a3b8; font-size:12px; font-style:italic;">Not allocated yet</span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Total Campaigns --}}
                                        <td style="text-align: center;">
                                            <span class="cmp-count-badge cmp-count-total">
                                                {{ $lead->no_of_campaigns }}
                                            </span>
                                        </td>

                                        {{-- Campaign Statuses (Active, Paused, Inactive, Expired) --}}
                                        <td style="text-align: center;">
                                            <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; align-items: center;">
                                                @if($lead->no_of_active_campaigns > 0)
                                                    <span class="cmp-status-badge cmp-status-badge--active" title="Active Campaigns">
                                                        <span class="cmp-pulse-dot"></span>
                                                        {{ $lead->no_of_active_campaigns }} Active
                                                    </span>
                                                @endif
                                                @if($lead->no_of_paused_campaigns > 0)
                                                    <span class="cmp-status-badge cmp-status-badge--paused" title="Paused Campaigns">
                                                        🟡 {{ $lead->no_of_paused_campaigns }} Paused
                                                    </span>
                                                @endif
                                                @if($lead->no_of_expired_campaigns > 0)
                                                    <span class="cmp-status-badge cmp-status-badge--expired" title="Expired Campaigns">
                                                        🔴 {{ $lead->no_of_expired_campaigns }} Expired
                                                    </span>
                                                @endif
                                                @if($lead->no_of_inactive_campaigns > 0)
                                                    <span class="cmp-status-badge cmp-status-badge--inactive" title="Inactive Campaigns">
                                                        ⚪ {{ $lead->no_of_inactive_campaigns }} Inactive
                                                    </span>
                                                @endif
                                                @if($lead->no_of_campaigns == 0)
                                                    <span class="cmp-status-badge cmp-status-badge--none">
                                                        No Campaigns
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- View Button --}}
                                        <td style="text-align: right;">
                                            <a href="{{ route('projects.campaigns.show', $lead->id) }}" class="cmp-btn cmp-btn-primary">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 48px 20px; color: #64748b;">
                                            <div style="font-size: 36px; margin-bottom: 8px;">📢</div>
                                            <div style="font-size: 16px; font-weight: 700; color: #1e293b;">No Digital Marketing Products Found</div>
                                            <div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">
                                                @if($hasActiveFilters)
                                                    No leads match your active filter criteria. Try clearing or modifying the filters above.
                                                @else
                                                    There are currently no products moved to production in the Digital Marketing department with Budget Approval needed.
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Account Pagination Footer --}}
                    @if($leads->hasPages() || $leads->total() > 0)
                        <div class="cmp-pagination-footer">
                            <div class="cmp-pagination-left">
                                <div class="cmp-pagination-info">
                                    <span>Showing</span>
                                    <strong>{{ $leads->firstItem() ?? 0 }}</strong>
                                    <span>to</span>
                                    <strong>{{ $leads->lastItem() ?? 0 }}</strong>
                                    <span>of</span>
                                    <strong>{{ $leads->total() }}</strong>
                                    <span>leads</span>
                                </div>

                                <div class="cmp-pagination-per-page">
                                    <span>Rows:</span>
                                    <select class="cmp-pagination-select" onchange="const url = new URL(window.location.href); url.searchParams.set('per_page', this.value); url.searchParams.set('page', 1); window.location.href = url.toString();">
                                        @foreach([10, 25, 50, 100] as $size)
                                            <option value="{{ $size }}" {{ (int)request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @if($leads->hasPages())
                                @php
                                    $currentPage = $leads->currentPage();
                                    $lastPage = $leads->lastPage();
                                    $start = max(1, $currentPage - 2);
                                    $end = min($lastPage, $currentPage + 2);
                                @endphp

                                <nav class="cmp-pagination-nav">
                                    {{-- Previous Button --}}
                                    @if($leads->onFirstPage())
                                        <span class="cmp-page-btn is-disabled" title="Previous Page">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                        </span>
                                    @else
                                        <a href="{{ $leads->previousPageUrl() }}" class="cmp-page-btn" title="Previous Page">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                        </a>
                                    @endif

                                    {{-- First Page if out of range --}}
                                    @if($start > 1)
                                        <a href="{{ $leads->url(1) }}" class="cmp-page-btn {{ $currentPage == 1 ? 'is-active' : '' }}">1</a>
                                        @if($start > 2)
                                            <span class="cmp-page-ellipsis">...</span>
                                        @endif
                                    @endif

                                    {{-- Page Numbers --}}
                                    @for($page = $start; $page <= $end; $page++)
                                        @if($page == $currentPage)
                                            <span class="cmp-page-btn is-active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $leads->url($page) }}" class="cmp-page-btn">{{ $page }}</a>
                                        @endif
                                    @endfor

                                    {{-- Last Page if out of range --}}
                                    @if($end < $lastPage)
                                        @if($end < $lastPage - 1)
                                            <span class="cmp-page-ellipsis">...</span>
                                        @endif
                                        <a href="{{ $leads->url($lastPage) }}" class="cmp-page-btn {{ $currentPage == $lastPage ? 'is-active' : '' }}">{{ $lastPage }}</a>
                                    @endif

                                    {{-- Next Button --}}
                                    @if($leads->hasMorePages())
                                        <a href="{{ $leads->nextPageUrl() }}" class="cmp-page-btn" title="Next Page">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                        </a>
                                    @else
                                        <span class="cmp-page-btn is-disabled" title="Next Page">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                        </span>
                                    @endif
                                </nav>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
@endsection

