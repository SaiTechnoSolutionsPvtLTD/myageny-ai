@extends('layouts.app')

@section('title', 'Digital Marketing Campaigns')

@push('styles')
<style>
.cmp-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.cmp-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.cmp-title { font-size:24px; font-weight:900; color:#111827; }
.cmp-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.cmp-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:999px; background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:12px; font-weight:800; }
.cmp-body { padding:22px 28px 34px; display:grid; gap:20px; }

/* Stats Grid */
.cmp-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; }
.cmp-stat { position:relative; overflow:hidden; background:var(--stat-gradient); border:none; border-radius:16px; padding:22px 24px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.08),0 8px 10px -6px rgba(0,0,0,0.04); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; }
.cmp-stat:hover { transform:translateY(-4px); box-shadow:0 18px 25px -5px rgba(0,0,0,0.12),0 10px 10px -5px rgba(0,0,0,0.06); }
.cmp-stat-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.cmp-stat-icon { display:flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:12px; background:rgba(255,255,255,0.22); color:#fff; font-size:18px; backdrop-filter:blur(4px); }
.cmp-stat-label { font-size:11px; font-weight:800; color:rgba(255,255,255,0.92); text-transform:uppercase; letter-spacing:.06em; text-shadow:0 1px 2px rgba(0,0,0,0.1); }
.cmp-stat-value { font-size:30px; font-weight:900; color:#fff; line-height:1.2; text-shadow:0 2px 4px rgba(0,0,0,0.1); }
.cmp-stat-footer { margin-top:12px; padding-top:12px; border-top:1px dashed rgba(255,255,255,0.25); font-size:12px; color:rgba(255,255,255,0.92); font-weight:600; text-shadow:0 1px 2px rgba(0,0,0,0.1); }

/* Filter Card */
.cmp-filter-card { background:#fff; border:1px solid #eee7df; border-radius:14px; overflow:hidden; box-shadow:0 6px 20px rgba(15,23,42,.03); }
.cmp-filter-head { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #f2ede8; background:#fffdfb; }
.cmp-filter-title { font-size:14px; font-weight:800; color:#1e293b; display:flex; align-items:center; gap:8px; }
.cmp-filter-badge { display:inline-flex; align-items:center; justify-content:center; padding:2px 8px; border-radius:999px; background:#ea580c; color:#fff; font-size:11px; font-weight:800; }
.cmp-filter-body { padding:18px 20px; }
.cmp-filter-grid { display:grid; grid-template-columns:1.5fr 1.3fr 1.1fr 1fr 1fr auto; gap:12px; align-items:end; }
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

/* Card & Table */
.cmp-card { background:#fff; border:1px solid #eee7df; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,.04); }
.cmp-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 24px; border-bottom:1px solid #f2ede8; background:#fffdfb; flex-wrap:wrap; }
.cmp-card-title { font-size:16px; font-weight:900; color:#111827; display:flex; align-items:center; gap:8px; }
.cmp-card-sub { margin-top:3px; font-size:12px; color:#7c7c7c; }

.cmp-table-wrap { overflow-x:auto; }
.cmp-table { width:100%; border-collapse:collapse; min-width:980px; }
.cmp-table th { padding:14px 16px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.cmp-table td { padding:16px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:middle; }
.cmp-table tbody tr:hover td { background:#fffaf5; }

/* Company & Customer */
.cmp-company-name { font-weight:800; font-size:14px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.cmp-avatar { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg, #ea580c, #fb923c); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; flex-shrink:0; }
.cmp-customer-name { font-weight:700; color:#1e293b; font-size:13px; }
.cmp-contact-meta { font-size:11px; color:#64748b; margin-top:2px; display:flex; flex-direction:column; gap:2px; }

/* Team Allocation Badges */
.cmp-team-wrap { display:flex; flex-wrap:wrap; gap:6px; max-width:260px; }
.cmp-team-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; }
.cmp-team-badge--tl { background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; }
.cmp-team-badge--emp { background:#f8fafc; color:#475569; border:1px solid #e2e8f0; }

/* Pill Counts */
.cmp-count-badge { display:inline-flex; align-items:center; justify-content:center; padding:4px 12px; border-radius:999px; font-size:13px; font-weight:800; }
.cmp-count-total { background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; }
.cmp-count-active { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.cmp-pulse-dot { width:7px; height:7px; border-radius:50%; background:#10b981; display:inline-block; margin-right:5px; box-shadow:0 0 0 0 rgba(16,185,129,0.7); animation:cmpPulse 2s infinite; }

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

@media (max-width: 1200px) {
    .cmp-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .cmp-filter-actions { grid-column: span 3; justify-content: flex-end; }
}
@media (max-width: 1024px) {
    .cmp-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .cmp-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cmp-filter-actions { grid-column: span 2; }
}
@media (max-width: 640px) {
    .cmp-stats { grid-template-columns:1fr; }
    .cmp-filter-grid { grid-template-columns: 1fr; }
    .cmp-filter-actions { grid-column: 1; }
    .cmp-topbar, .cmp-body { padding:16px; }
    .cmp-pagination-footer { flex-direction:column; align-items:flex-start; }
}
</style>
@endpush

@section('content')
<div class="cmp-page">
    <div class="cmp-topbar">
        <div>
            <div class="cmp-title">Digital Marketing Campaigns</div>
            <div class="cmp-breadcrumb">Modules &gt; Production &gt; Campaigns</div>
        </div>
        <div class="cmp-chip">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                <line x1="4" y1="22" x2="4" y2="15"></line>
            </svg>
            Digital Marketing Budget Approved Leads
        </div>
    </div>

    <div class="cmp-body">
        {{-- KPI Cards --}}
        <section class="cmp-stats">
            {{-- Total Leads --}}
            <div class="cmp-stat" style="--stat-gradient: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Total DM Leads</span>
                    <span class="cmp-stat-icon">🏢</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_leads'] }}</div>
                <div class="cmp-stat-footer">
                    Leads with Budget Approval = Yes
                </div>
            </div>

            {{-- Total Campaigns --}}
            <div class="cmp-stat" style="--stat-gradient: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Total Campaigns</span>
                    <span class="cmp-stat-icon">📢</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_campaigns'] }}</div>
                <div class="cmp-stat-footer">
                    Across all customer accounts
                </div>
            </div>

            {{-- Active Campaigns --}}
            <div class="cmp-stat" style="--stat-gradient: linear-gradient(135deg, #059669 0%, #34d399 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Active Campaigns</span>
                    <span class="cmp-stat-icon">⚡</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_active_campaigns'] }}</div>
                <div class="cmp-stat-footer">
                    Currently running campaigns
                </div>
            </div>
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
                    <a href="{{ route('projects.campaigns.index') }}" class="cmp-btn cmp-btn-outline" style="min-height:32px; padding:4px 12px; font-size:12px;">
                        ✕ Clear All Filters
                    </a>
                @endif
            </div>

            <div class="cmp-filter-body">
                <form method="GET" action="{{ route('projects.campaigns.index') }}">
                    <div class="cmp-filter-grid">
                        {{-- 1. Company Name Textbox --}}
                        <div class="cmp-field">
                            <label class="cmp-label" for="filter_company_name">Company / Client Name</label>
                            <input
                                type="text"
                                id="filter_company_name"
                                name="company_name"
                                value="{{ $filters['company_name'] ?? '' }}"
                                placeholder="Search company or client..."
                                class="cmp-input"
                            >
                        </div>

                        {{-- 2. Employee Wise Dropdown --}}
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

                        {{-- 3. Status (Active / Inactive) Dropdown --}}
                        <div class="cmp-field">
                            <label class="cmp-label" for="filter_status">Status</label>
                            <select id="filter_status" name="status" class="cmp-select">
                                <option value="">All Statuses</option>
                                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>🟢 Active Campaigns</option>
                                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>⚪ Inactive / No Active</option>
                            </select>
                        </div>

                        {{-- 4. Start Date --}}
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

                        {{-- 5. End Date --}}
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
                                <a href="{{ route('projects.campaigns.index') }}" class="cmp-btn cmp-btn-outline" title="Reset Filters">
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
                    <div class="cmp-card-title">
                        <span>Campaigns by Customer / Lead</span>
                        <span style="font-size:12px; font-weight:700; color:#ea580c; background:#fff7ed; padding:3px 10px; border-radius:999px; border:1px solid #fed7aa;">
                            {{ $leads->total() }} {{ \Illuminate\Support\Str::plural('Lead', $leads->total()) }}
                        </span>
                    </div>
                    <div class="cmp-card-sub">
                        Showing leads whose Digital Marketing products are moved to production with budget approval required.
                    </div>
                </div>
            </div>

            <div class="cmp-card-body" style="padding:0;">
                <div class="cmp-table-wrap">
                    <table class="cmp-table">
                        <thead>
                            <tr>
                                <th style="width: 24%;">Company Name</th>
                                <th style="width: 18%;">Customer Name</th>
                                <th style="width: 22%;">Team Allocation</th>
                                <th style="width: 12%; text-align: center;">No Of Campaigns</th>
                                <th style="width: 14%; text-align: center;">No of Active Campaigns</th>
                                <th style="width: 10%; text-align: right;">Action</th>
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

                                    {{-- No Of Campaigns --}}
                                    <td style="text-align: center;">
                                        <span class="cmp-count-badge cmp-count-total">
                                            {{ $lead->no_of_campaigns }}
                                        </span>
                                    </td>

                                    {{-- No of Active Campaigns --}}
                                    <td style="text-align: center;">
                                        <span class="cmp-count-badge cmp-count-active">
                                            @if($lead->no_of_active_campaigns > 0)
                                                <span class="cmp-pulse-dot"></span>
                                            @endif
                                            {{ $lead->no_of_active_campaigns }} Active
                                        </span>
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

                {{-- Pagination Footer --}}
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
            </div>
        </section>
    </div>
</div>
@endsection

