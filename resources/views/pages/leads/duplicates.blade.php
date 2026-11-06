@extends('layouts.app')

@section('title', 'Duplicate Leads')

@push('styles')
<style>
/* ─────────────────────────────────────────
   DUPLICATE LEADS VIEW
   Theme: Clean white, orange accent, modern cards
───────────────────────────────────────── */
.ld-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }

/* ── Topbar ── */
.ld-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:64px; flex-shrink:0; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.ld-page-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; }
.ld-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.ld-topbar-right { display:flex; align-items:center; gap:10px; }
.ld-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; transition:all .15s; border:none; }
.ld-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.ld-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.ld-btn-outline { background:#fff; color:#121212; border:1px solid #e1dee3; }
.ld-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

/* Filter Bar */
.ld-filter-wrap-outer { background:#f4f5f7; padding:12px 28px 0; }
.ld-filter-accordion { background:#fff; border:1px solid #e1dee3; border-radius:12px; overflow:hidden; box-shadow:0 4px 14px rgba(18,18,18,.03); }
.ld-filter-toggle { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 18px; cursor:pointer; user-select:none; }
.ld-filter-toggle::-webkit-details-marker { display:none; }
.ld-filter-toggle-left { display:flex; align-items:center; gap:11px; min-width:0; }
.ld-filter-icon { width:32px; height:32px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; background:#fff7ed; color:#fe5f04; flex-shrink:0; }
.ld-filter-title { font-size:14px; font-weight:800; color:#121212; }
.ld-filter-sub { font-size:11px; color:#8e8e8e; margin-top:2px; }
.ld-filter-toggle-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.ld-filter-count { display:none; align-items:center; justify-content:center; min-width:22px; height:22px; padding:0 7px; border-radius:999px; background:#fe5f04; color:#fff; font-size:11px; font-weight:800; }
.ld-filter-chevron { width:30px; height:30px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e1dee3; color:#8e8e8e; transition:all .15s; }
.ld-filter-accordion[open] .ld-filter-chevron { transform:rotate(180deg); color:#fe5f04; border-color:#fed7aa; background:#fff7ed; }
.ld-filter-body { border-top:1px solid #f0eef2; }
.ld-filter-bar { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; padding:16px 18px; align-items:end; }
.ld-filter-actions { grid-column:1 / -1; display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:flex-end; padding-top:12px; border-top:1px solid #f0eef2; }
.ld-fw { display:flex; flex-direction:column; gap:6px; min-width:0; }
.ld-field-label { font-size:11px; font-weight:800; color:#7c7c7c; text-transform:uppercase; letter-spacing:.04em; }
.ld-input-wrap { position:relative; }
.ld-fi { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9e9e9e; pointer-events:none; font-size:13px; line-height:1; }
.ld-fs, .ld-fi-input { width:100%; height:38px; padding:8px 12px 8px 32px; background:#f8f8f8; border:1px solid #e1dee3; border-radius:8px; font-size:12px; font-weight:600; color:#2e2e2e; outline:none; transition:all .15s; font-family:inherit; }
.ld-fs:focus, .ld-fi-input:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.ld-reset-btn { display:inline-flex; align-items:center; justify-content:center; gap:5px; height:38px; padding:0 16px; border-radius:9px; background:#fff; color:#7c7c7c; text-decoration:none; font-weight:700; border:1px solid #e1dee3; transition:all .15s; }
.ld-reset-btn:hover { background:#fff5f5; border-color:#dc2626; color:#dc2626; }

/* ── Navtabs ── */
.ld-nav-tabs-wrap { padding:14px 28px 0; }
.ld-nav-tabs { display:flex; align-items:center; gap:8px; border-bottom:2px solid #e1dee3; }
.ld-nav-tab {
    display:inline-flex; align-items:center; gap:8px; padding:12px 22px;
    border-radius:10px 10px 0 0; font-size:13.5px; font-weight:700; color:#64748b;
    text-decoration:none; background:transparent; border-bottom:3px solid transparent;
    transition:all .15s ease;
}
.ld-nav-tab:hover { color:#fe5f04; background:rgba(254,95,4,.04); }
.ld-nav-tab.active {
    color:#fe5f04; font-weight:800; border-bottom-color:#fe5f04; background:#fff;
    box-shadow:0 -2px 10px rgba(0,0,0,.03);
}
.ld-tab-badge {
    display:inline-flex; align-items:center; justify-content:center;
    padding:2px 8px; border-radius:999px; font-size:11px; font-weight:800;
    background:#e2e8f0; color:#475569; transition:all .15s ease;
}
.ld-nav-tab.active .ld-tab-badge { background:#fe5f04; color:#fff; }

/* ── Body ── */
.ld-body { padding:18px 28px 40px; display:flex; flex-direction:column; gap:18px; }

/* ── Stats ── */
.ld-stats-row { display:grid; grid-template-columns:repeat(3, 1fr); gap:14px; }
.ld-stat-card {
    background:#fff; border:1px solid #e7e1de; border-radius:14px;
    padding:16px 18px; box-shadow:0 4px 14px rgba(18,18,18,.03);
    display:flex; align-items:center; justify-content:space-between; gap:12px;
}
.ld-stat-left { display:flex; flex-direction:column; gap:4px; }
.ld-stat-title { font-size:11px; font-weight:800; color:#9e9e9e; text-transform:uppercase; letter-spacing:.04em; }
.ld-stat-val { font-size:26px; font-weight:900; color:#121212; letter-spacing:-.02em; }
.ld-stat-icon-wrap { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }

/* ── Duplicate Phone Group Card ── */
.ld-group-card { background:#fff; border:1px solid #e1dee3; border-radius:14px; overflow:hidden; box-shadow:0 6px 18px rgba(18,18,18,.04); }
.ld-group-header {
    background:#fafafa; border-bottom:1px solid #eae7ec;
    padding:12px 20px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
}
.ld-group-title { display:flex; align-items:center; gap:10px; font-weight:800; font-size:14px; color:#121212; }
.ld-group-meta { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* ── Table Inside Group ── */
.ld-table-wrap { overflow-x:auto; }
.ld-table { width:100%; border-collapse:collapse; font-size:12.5px; text-align:left; }
.ld-table th { padding:10px 16px; background:#fbfafc; font-size:11px; font-weight:800; color:#7c7c7c; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e1dee3; white-space:nowrap; }
.ld-table td { padding:12px 16px; border-bottom:1px solid #f0eef2; color:#2e2e2e; vertical-align:middle; }
.ld-table tr:last-child td { border-bottom:none; }
.ld-table tr:hover td { background:#fffcf9; }

/* Badges */
.ld-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; white-space:nowrap; }
.ld-badge-you { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; font-weight:800; }
.ld-badge-other { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; font-weight:800; }
.ld-badge-status { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }

.ld-co-cell { display:flex; align-items:center; gap:9px; }
.ld-co-av { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; font-weight:900; flex-shrink:0; }
.ld-co-name { font-weight:800; color:#121212; line-height:1.2; }
.ld-co-sub { font-size:11px; color:#7c7c7c; margin-top:2px; }

/* Empty state */
.ld-empty { background:#fff; border:1px dashed #cbd5e1; border-radius:14px; padding:48px 24px; text-align:center; color:#64748b; }
.ld-empty-icon { font-size:42px; color:#94a3b8; margin-bottom:12px; }
.ld-empty-title { font-size:16px; font-weight:800; color:#1e293b; margin-bottom:6px; }

@media (max-width: 900px) {
    .ld-filter-bar { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    .ld-stats-row { grid-template-columns:1fr; }
    .ld-topbar { padding:0 16px; }
    .ld-filter-wrap-outer { padding:12px 16px 0; }
    .ld-nav-tabs-wrap { padding:14px 16px 0; }
    .ld-body { padding:14px 16px 30px; }
}
@media (max-width: 600px) {
    .ld-filter-bar { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')

<div class="ld-page">

    {{-- Topbar --}}
    <div class="ld-topbar">
        <div>
            <div class="ld-page-title">
                <i class="bi bi-copy text-warning" style="color:#fe5f04 !important;"></i>
                Duplicate Leads
            </div>
            <div class="ld-breadcrumb">Sales > <a href="{{ route('leads.index') }}" style="color:inherit; text-decoration:none;">Leads</a> > Duplicate Leads</div>
        </div>
        <div class="ld-topbar-right">
            <a href="{{ route('leads.index') }}" class="ld-btn ld-btn-outline">
                <i class="bi bi-arrow-left"></i>
                All Leads Sheet
            </a>
            <a href="{{ route('leads.create') }}" class="ld-btn ld-btn-primary">
                <i class="bi bi-plus-lg"></i>
                New Lead
            </a>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="ld-filter-wrap-outer">
        <details class="ld-filter-accordion" id="dupFilters" @if($filterPanelOpen) open @endif>
            <summary class="ld-filter-toggle">
                <div class="ld-filter-toggle-left">
                    <span class="ld-filter-icon"><i class="bi bi-funnel"></i></span>
                    <div>
                        <div class="ld-filter-title">Filters</div>
                        <div class="ld-filter-sub">Filter duplicate leads by keyword, date range, branch, or team member.</div>
                    </div>
                </div>
                <div class="ld-filter-toggle-right">
                    <span class="ld-filter-chevron"><i class="bi bi-chevron-down"></i></span>
                </div>
            </summary>

            <div class="ld-filter-body">
                <form method="GET" action="{{ route('leads.duplicates') }}" id="dupFilterForm">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    <div class="ld-filter-bar">
                        {{-- Search --}}
                        <div class="ld-fw">
                            <label class="ld-field-label">Search Keyword</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-search ld-fi"></i>
                                <input type="text" name="search" class="ld-fi-input" value="{{ $filters['search'] }}" placeholder="Company, contact, or mobile...">
                            </div>
                        </div>

                        {{-- Quick Date --}}
                        <div class="ld-fw">
                            <label class="ld-field-label">Quick Date</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-calendar-event ld-fi"></i>
                                <select name="quick_date" class="ld-fs" onchange="this.form.submit()">
                                    <option value="all" @selected(($filters['quick_date'] ?? 'all') === 'all')>All Time</option>
                                    <option value="today" @selected(($filters['quick_date'] ?? '') === 'today')>Today</option>
                                    <option value="this_week" @selected(($filters['quick_date'] ?? '') === 'this_week')>This Week</option>
                                    <option value="this_month" @selected(($filters['quick_date'] ?? '') === 'this_month')>This Month</option>
                                    <option value="this_quarter" @selected(($filters['quick_date'] ?? '') === 'this_quarter')>This Quarter</option>
                                    <option value="this_year" @selected(($filters['quick_date'] ?? '') === 'this_year')>This Year</option>
                                </select>
                            </div>
                        </div>

                        {{-- Date From --}}
                        <div class="ld-fw">
                            <label class="ld-field-label">From Date</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-calendar3 ld-fi"></i>
                                <input type="date" name="date_from" class="ld-fi-input" value="{{ $filters['date_from'] }}">
                            </div>
                        </div>

                        {{-- Date To --}}
                        <div class="ld-fw">
                            <label class="ld-field-label">To Date</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-calendar3 ld-fi"></i>
                                <input type="date" name="date_to" class="ld-fi-input" value="{{ $filters['date_to'] }}">
                            </div>
                        </div>

                        {{-- Branch --}}
                        @if($branches && $branches->count() > 1)
                        <div class="ld-fw">
                            <label class="ld-field-label">Branch</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-geo-alt ld-fi"></i>
                                <select name="branch_id" class="ld-fs">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" @selected($filters['branch_id'] == $b->id)>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif

                        {{-- Team Member (for Admin/Managers) --}}
                        @if(auth()->user()->hasAdminLikeRole() && $users && $users->count() > 1)
                        <div class="ld-fw">
                            <label class="ld-field-label">Team Member</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-person ld-fi"></i>
                                <select name="assigned_to" class="ld-fs">
                                    <option value="{{ auth()->id() }}" @selected($targetUserId === (int)auth()->id())>You ({{ auth()->user()->name }})</option>
                                    @foreach($users as $u)
                                        @if($u->id !== auth()->id())
                                            <option value="{{ $u->id }}" @selected($targetUserId === (int)$u->id)>{{ $u->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif

                        {{-- Filter Actions --}}
                        <div class="ld-filter-actions">
                            <a href="{{ route('leads.duplicates', ['tab' => $tab, 'reset' => 1]) }}" class="ld-reset-btn">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Reset Filters
                            </a>
                            <button type="submit" class="ld-btn ld-btn-primary" style="height:38px;">
                                <i class="bi bi-funnel-fill"></i>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </details>
    </div>

    {{-- NAVTABS: SELF vs OTHERS --}}
    <div class="ld-nav-tabs-wrap">
        <div class="ld-nav-tabs">
            <a href="{{ route('leads.duplicates', array_merge(request()->query(), ['tab' => 'self', 'page' => 1])) }}" class="ld-nav-tab {{ $tab === 'self' ? 'active' : '' }}">
                <i class="bi bi-person-badge-fill"></i>
                <span>Self Duplicates</span>
                <span class="ld-tab-badge {{ $tab === 'self' ? 'active' : '' }}">{{ $selfCount }}</span>
            </a>
            <a href="{{ route('leads.duplicates', array_merge(request()->query(), ['tab' => 'others', 'page' => 1])) }}" class="ld-nav-tab {{ $tab === 'others' ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i>
                <span>Others Duplicates</span>
                <span class="ld-tab-badge {{ $tab === 'others' ? 'active' : '' }}">{{ $othersCount }}</span>
            </a>
        </div>
    </div>

    {{-- MAIN CONTENT BODY --}}
    <div class="ld-body">

        {{-- Stats Overview Cards --}}
        <div class="ld-stats-row">
            {{-- Self Tab Stat --}}
            <div class="ld-stat-card">
                <div class="ld-stat-left">
                    <div class="ld-stat-title">Self Duplicates</div>
                    <div class="ld-stat-val" style="color:#2563eb;">{{ $selfCount }}</div>
                    <div style="font-size:11px; color:#64748b;">Phone groups within {{ $targetUser->id === auth()->id() ? 'your' : $targetUser->name . "'s" }} leads</div>
                </div>
                <div class="ld-stat-icon-wrap" style="background:#eff6ff; color:#2563eb;">
                    <i class="bi bi-person-fill"></i>
                </div>
            </div>

            {{-- Others Tab Stat --}}
            <div class="ld-stat-card">
                <div class="ld-stat-left">
                    <div class="ld-stat-title">Others Duplicates</div>
                    <div class="ld-stat-val" style="color:#fe5f04;">{{ $othersCount }}</div>
                    <div style="font-size:11px; color:#64748b;">Cross-user phone overlaps</div>
                </div>
                <div class="ld-stat-icon-wrap" style="background:#fff7ed; color:#fe5f04;">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>

            {{-- Active View Target --}}
            <div class="ld-stat-card">
                <div class="ld-stat-left">
                    <div class="ld-stat-title">Current Context</div>
                    <div style="font-size:15px; font-weight:800; color:#0f172a; margin-top:4px;">
                        {{ $targetUser->name }} {{ $targetUser->id === auth()->id() ? '(You)' : '' }}
                    </div>
                    <div style="font-size:11px; color:#64748b; margin-top:2px;">
                        Mode: <strong style="color:{{ $tab === 'self' ? '#2563eb' : '#fe5f04' }}; text-transform:uppercase;">{{ $tab }}</strong>
                    </div>
                </div>
                <div class="ld-stat-icon-wrap" style="background:#f1f5f9; color:#475569;">
                    <i class="bi bi-shield-check"></i>
                </div>
            </div>
        </div>

        {{-- Explanatory Banner --}}
        <div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid {{ $tab === 'self' ? '#2563eb' : '#fe5f04' }}; border-radius:10px; padding:12px 18px; font-size:12.5px; color:#334155; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:8px;">
                <i class="bi bi-info-circle-fill" style="color:{{ $tab === 'self' ? '#2563eb' : '#fe5f04' }}; font-size:15px;"></i>
                @if($tab === 'self')
                    <span>Showing duplicate leads where <strong>2 or more leads</strong> sharing the same mobile number are all allocated to <strong>{{ $targetUser->id === auth()->id() ? 'you (' . $targetUser->name . ')' : $targetUser->name }}</strong>.</span>
                @else
                    <span>Showing duplicate leads where <strong>{{ $targetUser->id === auth()->id() ? 'your' : $targetUser->name . "'s" }}</strong> leads share the same mobile number with <strong>other team members / users</strong> in the CRM.</span>
                @endif
            </div>
            <div style="font-size:11px; font-weight:800; color:#64748b;">
                Page {{ $paginatedGroups->currentPage() }} of {{ max(1, $paginatedGroups->lastPage()) }} (Total {{ $paginatedGroups->total() }} Duplicate Numbers)
            </div>
        </div>

        {{-- Duplicate Phone Groups List --}}
        @if($paginatedGroups->isNotEmpty())
            @php $colors = ['#fe5f04','#7c3aed','#2563eb','#16a34a','#be123c','#0284c7','#b45309','#0f766e']; @endphp
            @foreach($paginatedGroups as $g)
                @php
                    $normPhone = $g->norm_phone;
                    $groupLeads = $leadsByPhone->get($normPhone) ?? collect();
                    $selfLeads = $groupLeads->where('assigned_to', $targetUserId);
                    $otherLeads = $groupLeads->where('assigned_to', '!=', $targetUserId);
                    $firstLead = $groupLeads->first();
                    $displayPhone = $firstLead?->mobile_number ?: $normPhone;
                @endphp
                <div class="ld-group-card">
                    {{-- Group Header Banner --}}
                    <div class="ld-group-header">
                        <div class="ld-group-title">
                            <div style="width:32px; height:32px; border-radius:8px; background:#fff7ed; color:#fe5f04; display:flex; align-items:center; justify-content:center; font-size:15px;">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div>
                                <span style="font-weight:900; font-size:14px; letter-spacing:-.01em;">{{ $displayPhone }}</span>
                                <span style="font-size:11px; color:#64748b; margin-left:6px; font-weight:600;">(Normalized: {{ $normPhone }})</span>
                            </div>
                        </div>

                        <div class="ld-group-meta">
                            <span class="ld-badge" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-size:11.5px; padding:4px 10px;">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                {{ $groupLeads->count() }} Leads Found
                            </span>

                            @if($tab === 'others')
                                <span class="ld-badge ld-badge-you" style="font-size:11.5px; padding:4px 10px;">
                                    <i class="bi bi-person-check-fill"></i>
                                    {{ $selfLeads->count() }} My Lead(s)
                                </span>
                                <span class="ld-badge ld-badge-other" style="font-size:11.5px; padding:4px 10px;">
                                    <i class="bi bi-people-fill"></i>
                                    {{ $otherLeads->count() }} Other User Lead(s)
                                </span>
                            @else
                                <span class="ld-badge ld-badge-you" style="font-size:11.5px; padding:4px 10px;">
                                    <i class="bi bi-check2-all"></i>
                                    All {{ $groupLeads->count() }} Assigned to {{ $targetUser->id === auth()->id() ? 'You' : $targetUser->name }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Group Leads Table --}}
                    <div class="ld-table-wrap">
                        <table class="ld-table">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Company / Contact</th>
                                    <th>Mobile & Email</th>
                                    <th>Source</th>
                                    <th>Deal Value</th>
                                    <th>Allocated User</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th>Lead Date</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupLeads as $lead)
                                    @php
                                        $avatarColor = $colors[$lead->id % count($colors)];
                                        $isMine = ($lead->assigned_to === $targetUserId);
                                    @endphp
                                    <tr style="{{ $isMine ? 'background:#fcfdff;' : '' }}">
                                        {{-- Lead ID --}}
                                        <td>
                                            <a href="{{ route('leads.show', $lead) }}" style="font-weight:800; color:#fe5f04; text-decoration:none;">
                                                LD-{{ str_pad($lead->id, 4, '0', STR_PAD_LEFT) }}
                                            </a>
                                        </td>

                                        {{-- Company / Contact --}}
                                        <td>
                                            <div class="ld-co-cell">
                                                <div class="ld-co-av" style="background:{{ $avatarColor }}">
                                                    {{ strtoupper(substr($lead->company_name ?: 'C', 0, 2)) }}
                                                </div>
                                                <div>
                                                    <div class="ld-co-name">
                                                        <a href="{{ route('leads.show', $lead) }}" style="color:inherit; text-decoration:none;">
                                                            {{ $lead->company_name ?: 'No Company Name' }}
                                                        </a>
                                                    </div>
                                                    <div class="ld-co-sub">{{ $lead->contact_name ?: 'No Contact Name' }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Mobile & Email --}}
                                        <td>
                                            <div style="display:flex; flex-direction:column; gap:2px;">
                                                <a href="tel:{{ $lead->mobile_number }}" style="font-weight:700; color:#0f172a; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                                    <i class="bi bi-telephone-fill" style="font-size:10px; color:#64748b;"></i>
                                                    {{ $lead->mobile_number }}
                                                </a>
                                                @if($lead->email)
                                                    <a href="mailto:{{ $lead->email }}" style="font-size:11px; color:#64748b; text-decoration:none;">
                                                        {{ $lead->email }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Source --}}
                                        <td>
                                            <span class="ld-badge" style="background:#f1f5f9; color:#475569;">
                                                {{ $lead->source_label ?: ($lead->leadSource?->name ?: ($lead->lead_source ?: 'Direct')) }}
                                            </span>
                                        </td>

                                        {{-- Deal Value --}}
                                        <td>
                                            <span style="font-weight:800; color:#0f172a;">
                                                ₹ {{ number_format((float)$lead->converted_deal_value, 2) }}
                                            </span>
                                        </td>

                                        {{-- Allocated User --}}
                                        <td>
                                            <div style="display:flex; flex-direction:column; gap:4px;">
                                                <div style="display:flex; align-items:center; gap:6px;">
                                                    <span style="font-weight:800; color:#1e293b; font-size:12.5px;">
                                                        {{ $lead->assignedTo?->name ?: 'Unassigned' }}
                                                    </span>
                                                    @if($isMine)
                                                        <span class="ld-badge ld-badge-you" style="font-size:9.5px; padding:1px 6px;">You</span>
                                                    @else
                                                        <span class="ld-badge ld-badge-other" style="font-size:9.5px; padding:1px 6px;">Other User</span>
                                                    @endif
                                                </div>
                                                @if($lead->preSaleExecutive)
                                                    <span style="font-size:10.5px; color:#64748b;">
                                                        Pre-Sales: {{ $lead->preSaleExecutive->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Branch --}}
                                        <td>
                                            <span style="font-weight:700; color:#334155; font-size:12px;">
                                                {{ $lead->branch?->name ?: 'N/A' }}
                                            </span>
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            <span class="ld-badge ld-badge-status" style="font-weight:700;">
                                                {{ $lead->leadStatus?->name ?: ($lead->lead_status ?: 'Open') }}
                                            </span>
                                        </td>

                                        {{-- Date --}}
                                        <td>
                                            <span style="font-size:11.5px; font-weight:700; color:#475569;">
                                                {{ optional($lead->lead_date)->format('d M Y') ?: 'N/A' }}
                                            </span>
                                        </td>

                                        {{-- Actions --}}
                                        <td style="text-align:right; white-space:nowrap;">
                                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                                <a href="{{ route('leads.show', $lead) }}" class="ld-btn ld-btn-outline" style="padding:4px 10px; font-size:11.5px;" title="View Lead Details">
                                                    <i class="bi bi-eye"></i>
                                                    View
                                                </a>
                                                @can('leads.edit')
                                                <a href="{{ route('leads.edit', $lead) }}" class="ld-btn ld-btn-outline" style="padding:4px 10px; font-size:11.5px; color:#fe5f04; border-color:#fed7aa;" title="Edit Lead">
                                                    <i class="bi bi-pencil"></i>
                                                    Edit
                                                </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            {{-- Pagination Links --}}
            @if($paginatedGroups->hasPages())
                <div style="margin-top:10px;">
                    @include('partials.table-pagination', ['paginator' => $paginatedGroups])
                </div>
            @endif

        @else
            {{-- Empty State --}}
            <div class="ld-empty">
                <div class="ld-empty-icon">
                    <i class="bi bi-check2-circle text-success" style="color:#16a34a !important;"></i>
                </div>
                <div class="ld-empty-title">
                    No Duplicate Leads Found in {{ ucfirst($tab) }} Tab!
                </div>
                <p style="font-size:13px; max-width:480px; margin:0 auto; line-height:1.6;">
                    @if($tab === 'self')
                        There are no duplicate contact numbers among leads allocated to <strong>{{ $targetUser->name }}</strong>.
                    @else
                        There are no contact number overlaps between <strong>{{ $targetUser->name }}</strong> and other team members.
                    @endif
                </p>
                <div style="margin-top:16px;">
                    <a href="{{ route('leads.index') }}" class="ld-btn ld-btn-primary">
                        Return to All Leads Sheet
                    </a>
                </div>
            </div>
        @endif

    </div>

</div>

@endsection
