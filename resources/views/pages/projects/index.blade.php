@extends('layouts.app')

@section('title', 'Projects Details')

@push('styles')
<style>
.prj-page { min-height:100%; background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%); font-family:'Inter',sans-serif; }
.prj-topbar { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:0 28px; height:64px; background:#fff; border-bottom:1px solid #e5e7eb; }
.prj-title { font-size:20px; font-weight:900; color:#111827; }
.prj-breadcrumb { font-size:12px; color:#6b7280; margin-top:3px; }
.prj-chip { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; font-size:12px; font-weight:800; }
.prj-body { padding:22px 28px 34px; display:grid; gap:18px; }
.prj-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.prj-card { display:block; text-decoration:none; border-radius:22px; border:1px solid #e5e7eb; padding:18px; box-shadow:0 12px 34px rgba(15,23,42,.06); transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
.prj-card:hover { transform:translateY(-2px); box-shadow:0 16px 38px rgba(15,23,42,.1); }
.prj-card.allocation_pending { background:linear-gradient(180deg,#fffaf3 0%,#ffffff 100%); border-color:#f6d7a7; }
.prj-card.allocated { background:linear-gradient(180deg,#f3fcf5 0%,#ffffff 100%); border-color:#bce6c7; }
.prj-card.is-active.allocation_pending { box-shadow:0 0 0 3px rgba(245,158,11,.14), 0 16px 38px rgba(15,23,42,.1); }
.prj-card.is-active.allocated { box-shadow:0 0 0 3px rgba(34,197,94,.14), 0 16px 38px rgba(15,23,42,.1); }
.prj-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.prj-card-title { font-size:16px; font-weight:900; color:#111827; }
.prj-card-sub { margin-top:4px; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
.prj-badge { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; border:1px solid transparent; }
.prj-badge.allocation_pending { color:#b45309; background:#fff1d6; border-color:#fcd9a2; }
.prj-badge.allocated { color:#166534; background:#e9f9ee; border-color:#bce6c7; }
.prj-card-count { margin-top:18px; font-size:44px; line-height:1; font-weight:900; letter-spacing:-.05em; color:#111827; }
.prj-table-card { background:#fff; border:1px solid #e1dee3; border-radius:18px; overflow:hidden; box-shadow:0 12px 34px rgba(15,23,42,.06); }
.prj-table-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; border-bottom:1px solid #f0eef2; background:#fcfcfd; }
.prj-table-title { font-size:15px; font-weight:800; color:#111827; }
.prj-table-sub { font-size:11px; color:#9e9e9e; margin-top:2px; }
.prj-table-wrap { overflow-x:auto; }
.prj-table { width:100%; border-collapse:collapse; }
.prj-table th { padding:10px 14px; text-align:left; border-bottom:1px solid #f0eef2; background:#fafafa; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:#9e9e9e; white-space:nowrap; }
.prj-table td { padding:13px 14px; border-bottom:1px solid #f7f6f9; font-size:13px; color:#111827; vertical-align:middle; }
.prj-table tbody tr:hover td { background:#fdf9f6; }
.prj-product { font-weight:800; color:#111827; }
.prj-meta { font-size:11px; color:#9e9e9e; margin-top:3px; }
.prj-status-pill { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid transparent; }
.prj-status-pill.allocation_pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.prj-status-pill.allocated { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.prj-action-group { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.prj-view-btn { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; font-size:12px; font-weight:800; text-decoration:none; cursor:pointer; }
.prj-link { color:#c2410c; font-weight:800; text-decoration:none; }
.prj-link:hover { text-decoration:underline; }
.prj-action-btn { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; background:#166534; border:1px solid #166534; color:#fff; font-size:12px; font-weight:800; text-decoration:none; cursor:pointer; }
.prj-action-muted { font-size:12px; color:#9ca3af; }
.prj-empty { padding:40px 20px; text-align:center; color:#6b7280; font-size:13px; }
.prj-flash { padding:12px 14px; border-radius:14px; font-size:13px; font-weight:700; }
.prj-flash.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }

/* Filter Card - Lead Products Style */
.prj-filter-card {
    background: #fff;
    border: 1px solid #e1dee3;
    border-radius: 18px;
    overflow: hidden;
    display: block;
}
.prj-filter-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 16px;
    border: none;
    background: linear-gradient(180deg, #fffaf7 0%, #fff 100%);
    cursor: pointer;
    text-align: left;
    font-family: inherit;
    list-style: none;
}
.prj-filter-toggle::-webkit-details-marker { display: none; }
.prj-filter-toggle:hover { background: linear-gradient(180deg, #fff7f1 0%, #fff 100%); }
.prj-filter-card[open] .prj-filter-toggle { border-bottom: 1px solid #f0eef2; }
.prj-filter-toggle-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.prj-filter-title { font-size: 14px; font-weight: 800; color: #111827; }
.prj-filter-sub { font-size: 11px; color: #9e9e9e; margin-top: 2px; }
.prj-filter-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 999px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
}
.prj-filter-chevron {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 1px solid #e8e3e8;
    background: #fff;
    color: #7c7c7c;
    transition: transform .18s ease, color .18s ease, border-color .18s ease;
}
.prj-filter-card[open] .prj-filter-chevron { transform: rotate(180deg); color: #fe5f04; border-color: #fed7aa; }
.prj-filter-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    padding: 20px;
    align-items: flex-start;
}
.prj-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.prj-label {
    font-size: 11px;
    font-weight: 800;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.prj-input, .prj-select {
    width: 100%;
    padding: 9px 13px;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    background: #fbfbfc;
    font-size: 13px;
    font-family: inherit;
    color: #111827;
    outline: none;
    transition: all .15s ease;
}
.prj-input:hover, .prj-select:hover { border-color: #d7d1d8; background: #fff; }
.prj-input:focus, .prj-select:focus { border-color: #fe5f04; background: #fff; box-shadow: 0 0 0 3px rgba(254,95,4,.1); }
.prj-field-wide { grid-column: span 2; }
.prj-filter-actions-row {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 14px;
    border-top: 1px solid #f0eef2;
    margin-top: 4px;
}
.prj-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 20px;
    border-radius: 10px;
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #fff;
    border: none;
    box-shadow: 0 4px 14px rgba(254,95,4,.22);
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: all .15s ease;
}
.prj-btn-primary:hover {
    color: #fff;
    background: linear-gradient(135deg, #f35700, #ff7422);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(254,95,4,.32);
}
.prj-btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #e1dee3;
    color: #6b7280;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: all .15s ease;
}
.prj-btn-ghost:hover {
    border-color: #dc2626 !important;
    color: #dc2626 !important;
}

/* Bulk Allocation Button and Modal */
.prj-btn-bulk {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 12px;
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    color: #ffffff;
    font-size: 13px;
    font-weight: 800;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
    transition: all .15s ease;
    font-family: inherit;
}
.prj-btn-bulk:hover {
    background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.38);
    color: #fff;
}
.prj-bulk-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 7px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
    font-size: 11px;
    font-weight: 900;
}

/* Modal styles */
.prj-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 999999;
    padding: 20px;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .2s ease, visibility .2s ease;
    overflow-y: auto;
}
.prj-modal-backdrop.is-open,
.prj-modal-backdrop.active {
    display: flex !important;
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
}
.prj-modal-dialog {
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 640px;
    max-height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
    border: 1px solid #e2e8f0;
    transform: scale(0.96);
    transition: transform .2s ease;
    overflow: hidden;
    margin: auto;
}
.prj-modal-backdrop.is-open .prj-modal-dialog,
.prj-modal-backdrop.active .prj-modal-dialog {
    transform: scale(1);
}
.prj-modal-dialog form,
#bulkAllocationForm {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}
.prj-modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%);
    flex-shrink: 0;
}
.prj-modal-title {
    font-size: 16px;
    font-weight: 900;
    color: #1e1b4b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.prj-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    line-height: 1;
    color: #94a3b8;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .15s ease;
}
.prj-modal-close:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.prj-modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1 1 auto;
    min-height: 0;
    max-height: calc(100vh - 200px);
    display: flex;
    flex-direction: column;
    gap: 18px;
    overscroll-behavior: contain;
}
.prj-modal-body::-webkit-scrollbar {
    width: 6px;
}
.prj-modal-body::-webkit-scrollbar-track {
    background: #f8fafc;
}
.prj-modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.prj-modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.prj-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    background: #fafafa;
    flex-shrink: 0;
}
.bulk-preview-wrap {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #f8fafc;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.bulk-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
}
.bulk-preview-list {
    max-height: 200px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px;
}
.bulk-proj-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 6px;
    background: #f8fafc;
    font-size: 12px;
    border: 1px solid #f1f5f9;
}
.bulk-proj-row:hover {
    background: #f1f5f9;
}
.bulk-proj-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.bulk-proj-client {
    font-weight: 800;
    color: #0f172a;
}
.bulk-proj-meta {
    font-size: 11px;
    color: #64748b;
}

@media (max-width: 1200px) {
    .prj-filter-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .prj-field-wide { grid-column: span 2; }
}
@media (max-width: 768px) {
    .prj-topbar { height:auto; padding:16px 20px; align-items:flex-start; flex-direction:column; gap:12px; }
    .prj-body { padding:16px 20px 24px; }
    .prj-filter-form { grid-template-columns: 1fr; }
    .prj-filter-toggle { flex-direction: column; align-items: flex-start; }
    .prj-filter-toggle-right { width: 100%; justify-content: space-between; }
    .prj-field-wide { grid-column: span 1; }
}
</style>
@endpush

@section('content')
<div class="prj-page">
    @php
        $isContributorScopedView = $isContributorScopedView ?? false;
        $canUserAllocate = $canUserAllocate ?? false;
        $pendingProductsSummary = $pendingProductsSummary ?? collect();
        $allocationUsers = $allocationUsers ?? collect();
        $pageTitle = $isContributorScopedView ? 'My Allocated Projects' : ($isTlScopedView ? 'My Projects' : 'Projects Details');
        $pageCrumb = $isContributorScopedView ? 'Projects Dashboard > Allocated Projects' : ($isTlScopedView ? 'Projects Dashboard > My Projects' : 'CRM Dashboard > Projects Details');
        $listSubText = $isContributorScopedView
            ? 'Only projects allocated to you are listed here.'
            : ($isTlScopedView
            ? 'Projects allocated to you are tracked here for employee assignment.'
            : 'Production approved items are tracked here for allocation.');
    @endphp
    <div class="prj-topbar">
        <div>
            <div class="prj-title">{{ $pageTitle }}</div>
            <div class="prj-breadcrumb">{{ $pageCrumb }}</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            @if($canUserAllocate && $pendingProductsSummary->isNotEmpty())
                <button type="button" class="prj-btn-bulk" onclick="openBulkAllocationModal()">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Bulk Allocation
                    <span class="prj-bulk-count">{{ $pendingProductsSummary->sum('count') }}</span>
                </button>
            @endif
            <div class="prj-chip">{{ $isContributorScopedView ? 'Allocated Project List' : ($isTlScopedView ? 'Employee Allocation Tracker' : 'Allocation Tracker') }}</div>
        </div>
    </div>

    <div class="prj-body">
        @if(session('success'))
            <div class="prj-flash success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="prj-flash" style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">{{ session('error') }}</div>
        @endif

        @php
            $hasActiveFilters = !empty($projectFilters['search'])
                || !empty($projectFilters['product_id'])
                || !empty($projectFilters['department_id'])
                || !empty($projectFilters['employee_id'])
                || !empty($projectFilters['project_status'])
                || !empty($projectFilters['project_category'])
                || !empty($projectFilters['date_from'])
                || !empty($projectFilters['date_to'])
                || (!empty($projectFilters['quick_date']) && $projectFilters['quick_date'] !== 'all');
        @endphp

        @if($isContributorScopedView)
            <details class="prj-filter-card" id="projectFilters" @if($hasActiveFilters) open @endif>
                <summary class="prj-filter-toggle" id="projectFiltersToggle">
                    <div>
                        <div class="prj-filter-title">Filter Allocated Projects</div>
                        <div class="prj-filter-sub">Narrow results by product, department, status, date type, or quick dates</div>
                    </div>
                    <div class="prj-filter-toggle-right">
                        <div class="prj-filter-pill">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            {{ number_format($employeeProjects->total()) }} results
                        </div>
                        <span class="prj-filter-chevron">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </span>
                    </div>
                </summary>
                <div class="prj-filter-body" id="projectFiltersBody">
                    <form method="GET" action="{{ route('projects.index') }}" class="prj-filter-form" id="prjFilterForm">
                        <div class="prj-field prj-field-wide">
                            <label class="prj-label" for="search">Search</label>
                            <input id="search" type="text" name="search" value="{{ $projectFilters['search'] ?? '' }}" class="prj-input" placeholder="Search Lead ID, company, client, mobile, project name...">
                        </div>

                        <div class="prj-field">
                            <label class="prj-label" for="product_id">Product</label>
                            <select id="product_id" name="product_id" class="prj-select">
                                <option value="">All Products</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(($projectFilters['product_id'] ?? '') === (string) $product->id)>{{ $product->product_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="prj-field">
                            <label class="prj-label" for="department_id">Department</label>
                            <select id="department_id" name="department_id" class="prj-select">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(($projectFilters['department_id'] ?? '') === (string) $dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="prj-field">
                            <label class="prj-label" for="project_status">Project Status</label>
                            <select id="project_status" name="project_status" class="prj-select">
                                <option value="">All Status</option>
                                <option value="ongoing" @selected(($projectFilters['project_status'] ?? '') === 'ongoing' || ($projectFilters['project_status'] ?? '') === 'ontrack')>Ongoing / Onboard</option>
                                <option value="hold" @selected(($projectFilters['project_status'] ?? '') === 'hold')>Hold</option>
                                <option value="delivered" @selected(($projectFilters['project_status'] ?? '') === 'delivered')>Delivered</option>
                                <option value="lost" @selected(($projectFilters['project_status'] ?? '') === 'lost')>Lost</option>
                            </select>
                        </div>

                        <input type="hidden" name="date_type" id="date_type_contrib" value="{{ $projectFilters['date_type'] ?? 'delivery' }}">

                        <div class="prj-field">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                <label class="prj-label" for="quick_date_select_contrib" style="margin-bottom:0;">Quick Dates</label>
                                <span id="prjQuickDateRange_contrib" style="font-size:11px;font-weight:700;color:#fe5f04;"></span>
                            </div>
                            <select id="quick_date_select_contrib" name="quick_date" class="prj-select" onchange="onPrjQuickDateChange(this.value, 'contrib')">
                                <option value="today" {{ ($projectFilters['quick_date'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="week" {{ in_array($projectFilters['quick_date'] ?? '', ['week', 'this_week', 'weekly'], true) ? 'selected' : '' }}>This Week</option>
                                <option value="month" {{ in_array($projectFilters['quick_date'] ?? '', ['month', 'this_month', 'monthly'], true) ? 'selected' : '' }}>This Month</option>
                                <option value="quarter" {{ in_array($projectFilters['quick_date'] ?? '', ['quarter', 'this_quarter', 'quarterly'], true) ? 'selected' : '' }}>This Quarter</option>
                                <option value="year" {{ in_array($projectFilters['quick_date'] ?? '', ['year', 'this_year', 'yearly'], true) ? 'selected' : '' }}>This Year</option>
                                <option value="all" {{ ($projectFilters['quick_date'] ?? 'all') === 'all' ? 'selected' : '' }}>Show All</option>
                                <option value="custom_onboarding" {{ in_array($projectFilters['quick_date'] ?? '', ['custom_onboarding', 'onboard', 'onboarding'], true) ? 'selected' : '' }}>Custom Dates (Onboarding)</option>
                                <option value="custom_delivery" {{ in_array($projectFilters['quick_date'] ?? '', ['custom_delivery', 'delivery'], true) || (($projectFilters['quick_date'] ?? '') === 'custom' && ($projectFilters['date_type'] ?? '') !== 'onboarding') ? 'selected' : '' }}>Custom Dates (Delivery)</option>
                            </select>
                        </div>

                        @php
                            $isCustomDateContrib = in_array($projectFilters['quick_date'] ?? '', ['custom_onboarding', 'custom_delivery', 'custom', 'onboard', 'onboarding', 'delivery'], true);
                        @endphp
                        <div class="prj-field" id="prjFromField_contrib" style="display: {{ $isCustomDateContrib ? 'flex' : 'none' }};">
                            <label class="prj-label" for="date_from_contrib">From Date</label>
                            <input id="date_from_contrib" type="date" name="date_from" class="prj-input" value="{{ $projectFilters['date_from'] ?? '' }}">
                        </div>

                        <div class="prj-field" id="prjToField_contrib" style="display: {{ $isCustomDateContrib ? 'flex' : 'none' }};">
                            <label class="prj-label" for="date_to_contrib">To Date</label>
                            <input id="date_to_contrib" type="date" name="date_to" class="prj-input" value="{{ $projectFilters['date_to'] ?? '' }}">
                        </div>

                        <div class="prj-filter-actions-row">
                            <button type="submit" class="prj-btn-primary">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                Apply Filter
                            </button>
                            <a href="{{ route('projects.index') }}" class="prj-btn-ghost">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.36"/></svg>
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </details>

            <section class="prj-table-card">
                <div class="prj-table-head">
                    <div>
                        <div class="prj-table-title">Allocated Projects</div>
                        <div class="prj-table-sub">{{ $listSubText }}</div>
                    </div>
                    <span class="prj-badge allocated">{{ number_format($employeeProjects->total()) }} Items</span>
                </div>

                @if($employeeProjects->isNotEmpty())
                    <div class="prj-table-wrap">
                        <table class="prj-table">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Delivery Date</th>
                                    <th>Client Name</th>
                                    <th>Company Name</th>
                                    <th>Mobile Number</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employeeProjects as $item)
                                    <tr>
                                        <td>
                                            <div class="prj-product">{{ $item->product_name }}</div>
                                            <div class="prj-meta">{{ $item->department?->name ?: 'No department' }}</div>
                                        </td>
                                        <td>{{ optional($item->project_delivery_date)->format('d M Y') ?: 'Not available' }}</td>
                                        <td>{{ $item->client_name ?: ($item->lead?->contact_name ?: 'No client') }}</td>
                                        <td>{{ $item->company_name ?: ($item->lead?->company_name ?: 'No company') }}</td>
                                        <td>
                                            @if($item->lead?->mobile_number)
                                                <a href="tel:{{ $item->lead->mobile_number }}" class="prj-link">{{ $item->lead->mobile_number }}</a>
                                            @else
                                                <span class="prj-action-muted">Not available</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('projects.show', $item) }}" class="prj-view-btn">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($employeeProjects->hasPages())
                        @include('partials.table-pagination', ['paginator' => $employeeProjects])
                    @endif
                @else
                    <div class="prj-empty">No allocated projects match the selected filters.</div>
                @endif
            </section>
        @else

        <details class="prj-filter-card" id="projectFilters" @if($hasActiveFilters) open @endif>
            <summary class="prj-filter-toggle" id="projectFiltersToggle">
                <div>
                    <div class="prj-filter-title">Filter Projects</div>
                    <div class="prj-filter-sub">Narrow results by product, department, employee, status, or quick dates</div>
                </div>
                <div class="prj-filter-toggle-right">
                    <div class="prj-filter-pill">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        {{ number_format($selectedCard['count']) }} results
                    </div>
                    <span class="prj-filter-chevron">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </summary>
            <div class="prj-filter-body" id="projectFiltersBody">
                <form method="GET" action="{{ route('projects.index') }}" class="prj-filter-form" id="prjFilterForm">
                    @if(request('bucket'))
                        <input type="hidden" name="bucket" value="{{ request('bucket') }}">
                    @endif

                    <div class="prj-field prj-field-wide">
                        <label class="prj-label" for="search">Lead / Company Search</label>
                        <input id="search" type="text" name="search" value="{{ $projectFilters['search'] ?? '' }}" class="prj-input" placeholder="Search Lead ID, company, client, mobile, project name...">
                    </div>

                    <div class="prj-field">
                        <label class="prj-label" for="product_id">Product</label>
                        <select id="product_id" name="product_id" class="prj-select">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected(($projectFilters['product_id'] ?? '') === (string) $product->id)>
                                    {{ $product->product_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="prj-field">
                        <label class="prj-label" for="department_id">Department</label>
                        <select id="department_id" name="department_id" class="prj-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(($projectFilters['department_id'] ?? '') === (string) $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="prj-field">
                        <label class="prj-label" for="employee_id">Employee</label>
                        <select id="employee_id" name="employee_id" class="prj-select">
                            <option value="">All Employees</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" @selected(($projectFilters['employee_id'] ?? '') === (string) $emp->id)>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="prj-field">
                        <label class="prj-label" for="project_status">Project Status</label>
                        <select id="project_status" name="project_status" class="prj-select">
                            <option value="">All Status</option>
                            <option value="ongoing" @selected(($projectFilters['project_status'] ?? '') === 'ongoing' || ($projectFilters['project_status'] ?? '') === 'ontrack')>Ongoing / Onboard</option>
                            <option value="hold" @selected(($projectFilters['project_status'] ?? '') === 'hold')>Hold</option>
                            <option value="delivered" @selected(($projectFilters['project_status'] ?? '') === 'delivered')>Delivered</option>
                            <option value="lost" @selected(($projectFilters['project_status'] ?? '') === 'lost')>Lost</option>
                        </select>
                    </div>

                    <input type="hidden" name="date_type" id="date_type" value="{{ $projectFilters['date_type'] ?? 'delivery' }}">

                    <div class="prj-field">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                            <label class="prj-label" for="quick_date_select" style="margin-bottom:0;">Quick Dates</label>
                            <span id="prjQuickDateRange" style="font-size:11px;font-weight:700;color:#fe5f04;"></span>
                        </div>
                        <select id="quick_date_select" name="quick_date" class="prj-select" onchange="onPrjQuickDateChange(this.value)">
                            <option value="today" {{ ($projectFilters['quick_date'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ in_array($projectFilters['quick_date'] ?? '', ['week', 'this_week', 'weekly'], true) ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ in_array($projectFilters['quick_date'] ?? '', ['month', 'this_month', 'monthly'], true) ? 'selected' : '' }}>This Month</option>
                            <option value="quarter" {{ in_array($projectFilters['quick_date'] ?? '', ['quarter', 'this_quarter', 'quarterly'], true) ? 'selected' : '' }}>This Quarter</option>
                            <option value="year" {{ in_array($projectFilters['quick_date'] ?? '', ['year', 'this_year', 'yearly'], true) ? 'selected' : '' }}>This Year</option>
                            <option value="all" {{ ($projectFilters['quick_date'] ?? 'all') === 'all' ? 'selected' : '' }}>Show All</option>
                            <option value="custom_onboarding" {{ in_array($projectFilters['quick_date'] ?? '', ['custom_onboarding', 'onboard', 'onboarding'], true) ? 'selected' : '' }}>Custom Dates (Onboarding)</option>
                            <option value="custom_delivery" {{ in_array($projectFilters['quick_date'] ?? '', ['custom_delivery', 'delivery'], true) || (($projectFilters['quick_date'] ?? '') === 'custom' && ($projectFilters['date_type'] ?? '') !== 'onboarding') ? 'selected' : '' }}>Custom Dates (Delivery)</option>
                        </select>
                    </div>

                    @php
                        $isCustomDate = in_array($projectFilters['quick_date'] ?? '', ['custom_onboarding', 'custom_delivery', 'custom', 'onboard', 'onboarding', 'delivery'], true);
                    @endphp
                    <div class="prj-field" id="prjFromField" style="display: {{ $isCustomDate ? 'flex' : 'none' }};">
                        <label class="prj-label" for="date_from">From Date</label>
                        <input id="date_from" type="date" name="date_from" value="{{ $projectFilters['date_from'] ?? '' }}" class="prj-input">
                    </div>

                    <div class="prj-field" id="prjToField" style="display: {{ $isCustomDate ? 'flex' : 'none' }};">
                        <label class="prj-label" for="date_to">To Date</label>
                        <input id="date_to" type="date" name="date_to" value="{{ $projectFilters['date_to'] ?? '' }}" class="prj-input">
                    </div>

                    <div class="prj-filter-actions-row">
                        <button type="submit" class="prj-btn-primary">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            Apply Filter
                        </button>
                        <a href="{{ route('projects.index', request('bucket') ? ['bucket' => request('bucket')] : []) }}" class="prj-btn-ghost">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.36"/></svg>
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </details>

        <section class="prj-grid">
            @foreach($cards as $key => $card)
                @php
                    $bucketQueryParams = array_merge(request()->query(), ['bucket' => $key]);
                @endphp
                <a href="{{ route('projects.index', $bucketQueryParams) }}" class="prj-card {{ $key }} {{ $selectedBucket === $key ? 'is-active' : '' }}">
                    <div class="prj-card-head">
                        <div>
                            <div class="prj-card-title">{{ $card['title'] }}</div>
                            <div class="prj-card-sub">{{ $card['status_label'] }}</div>
                        </div>
                        <span class="prj-badge {{ $key }}">{{ $card['status_label'] }}</span>
                    </div>
                    <div class="prj-card-count">{{ number_format($card['count']) }}</div>
                </a>
            @endforeach
        </section>

        <section class="prj-table-card">
            <div class="prj-table-head">
                <div>
                    <div class="prj-table-title">{{ $selectedCard['title'] }} List</div>
                    <div class="prj-table-sub">{{ $listSubText }}</div>
                </div>
                <span class="prj-badge {{ $selectedBucket }}">{{ number_format($selectedCard['count']) }} Items</span>
            </div>

            @if($selectedCard['items']->isNotEmpty())
                <div class="prj-table-wrap">
                    <table class="prj-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Lead / Company</th>
                                <th>Department</th>
                                <th>Prod. Approved By</th>
                                <th>Prod. Approved On</th>
                                <th>{{ $isTlScopedView ? 'Team Status' : 'Allocation Status' }}</th>
                                <th>{{ $isTlScopedView ? 'Employee Allocated By' : 'Allocated By' }}</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedCard['items'] as $item)
                                <tr>
                                    <td>
                                        <div class="prj-product">{{ $item->product_name }}</div>
                                        <div class="prj-meta">Working days: {{ $item->total_working_days }}</div>
                                    </td>
                                    <td>
                                        {{ $item->company_name ?: ($item->lead?->company_name ?: 'No company') }}
                                        <div class="prj-meta">{{ $item->client_name ?: ($item->lead?->contact_name ?: 'No client') }}</div>
                                    </td>
                                    <td>{{ $item->department?->name ?: 'No department' }}</td>
                                    <td>{{ $item->productionApprovalReviewedBy?->name ?: 'Pending' }}</td>
                                    <td>{{ optional($item->production_approval_reviewed_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                                    <td>
                                        @php
                                            $currentStatus = $isTlScopedView
                                                ? ($item->current_team_status ?? 'allocation_pending')
                                                : $item->project_allocation_status;
                                        @endphp
                                        <span class="prj-status-pill {{ $selectedBucket === 'allocated' || $currentStatus === 'allocated' ? 'allocated' : 'allocation_pending' }}">{{ str_replace('_', ' ', (string) $currentStatus) }}</span>
                                    </td>
                                    <td>{{ $isTlScopedView ? (($item->current_team_allocated_by_name ?? null) ?: 'Pending') : ($item->projectAllocatedBy?->name ?: 'Pending') }}</td>
                                    <td>
                                        <div class="prj-action-group">
                                            <a href="{{ route('projects.show', $item) }}" class="prj-view-btn">View</a>
                                            @php
                                                $canUserAllocate = auth()->user()?->hasAdminLikeRole()
                                                    || (auth()->user()?->isDesigningTl() || (auth()->user()?->belongsToDesigningDepartment() && auth()->user()?->hasTlLikeRole()))
                                                    || auth()->user()?->isDigitalMarketingTl()
                                                    || auth()->user()?->isDevelopmentProjectCoordinator()
                                                    || $isTlScopedView;
                                            @endphp
                                            @if($canUserAllocate)
                                                @if($isTlScopedView)
                                                    <a href="{{ route('projects.show', $item) }}" class="prj-action-btn">{{ $currentStatus === 'allocated' ? 'Reallocate Team' : 'Allocate Team' }}</a>
                                                @elseif($item->project_allocation_status === 'allocation_pending')
                                                    <a href="{{ route('projects.show', $item) }}" class="prj-action-btn">Allocate</a>
                                                @else
                                                    <span class="prj-action-muted">{{ optional($isTlScopedView ? ($item->current_team_allocated_at ?? null) : $item->project_allocated_at)->format('d M Y h:i A') ?: 'Completed' }}</span>
                                                @endif
                                            @else
                                                @if($item->project_allocated_at || ($item->current_team_allocated_at ?? null))
                                                    <span class="prj-action-muted">{{ optional($isTlScopedView ? ($item->current_team_allocated_at ?? null) : $item->project_allocated_at)->format('d M Y h:i A') ?: 'Completed' }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                    @if($selectedCard['items']->hasPages())
                        @include('partials.table-pagination', ['paginator' => $selectedCard['items']])
                    @endif
                @else
                    <div class="prj-empty">No project items are available in this status right now.</div>
                @endif
            </section>
        @endif
    @if($canUserAllocate)
    <div class="prj-modal-backdrop" id="bulkAllocationModal" onclick="if(event.target === this) closeBulkAllocationModal()">
        <div class="prj-modal-dialog">
            <div class="prj-modal-header">
                <div>
                    <div class="prj-modal-title">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#4f46e5;">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Bulk Project Allocation
                    </div>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Select a product and allocate all its pending client projects to an employee at once.</div>
                </div>
                <button type="button" class="prj-modal-close" onclick="closeBulkAllocationModal()">&times;</button>
            </div>

            <form method="POST" action="{{ route('projects.bulk-allocate') }}" id="bulkAllocationForm" onsubmit="return validateBulkForm()">
                @csrf
                <div class="prj-modal-body">
                    <div class="prj-field">
                        <label class="prj-label" for="bulk_product_select">1. Select Product with Pending Projects *</label>
                        <select id="bulk_product_select" class="prj-select" required onchange="onBulkProductChange(this.value)">
                            <option value="">-- Choose a Product --</option>
                            @foreach($pendingProductsSummary as $prod)
                                <option value="{{ $prod['product_id'] }}">{{ $prod['product_name'] }} ({{ $prod['count'] }} Pending Projects)</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="bulkPreviewContainer" style="display:none;" class="bulk-preview-wrap">
                        <div class="bulk-preview-header">
                            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-weight:700;">
                                <input type="checkbox" id="bulkMasterCheckbox" checked onchange="toggleSelectAllBulkProjects(this)">
                                <span>Select All (<span id="bulkSelectedCount">0</span> of <span id="bulkTotalCount">0</span> projects selected)</span>
                            </label>
                        </div>
                        <div class="bulk-preview-list" id="bulkProjectsList">
                            <!-- Dynamically populated rows -->
                        </div>
                    </div>

                    <div class="prj-field">
                        <label class="prj-label" for="bulk_assignee_select">2. Select Employee / User to Allocate *</label>
                        <select id="bulk_assignee_select" name="user_id" required class="prj-select">
                            <option value="">-- Choose Employee / Assignee --</option>
                            @foreach($allocationUsers as $assignee)
                                <option value="{{ $assignee->id }}">{{ $assignee->name }} ({{ $assignee->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="prj-field">
                        <label class="prj-label" for="bulk_notes">3. Allocation Notes (Optional)</label>
                        <input type="text" id="bulk_notes" name="allocation_notes" class="prj-input" placeholder="e.g. Bulk allocated for ongoing month delivery">
                    </div>
                </div>

                <div class="prj-modal-footer">
                    <button type="button" class="prj-btn-ghost" onclick="closeBulkAllocationModal()">Cancel</button>
                    <button type="submit" id="bulkSubmitBtn" class="prj-btn-primary" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                        Allocate Projects
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function calcPresetDates(val) {
    const today = new Date();
    const fmt = d => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (val === 'today') {
        const s = fmt(today);
        return { from: s, to: s };
    }
    if (val === 'week') {
        const day = today.getDay(); // 0 is Sun
        const diffToMon = (day === 0 ? -6 : 1) - day;
        const mon = new Date(today);
        mon.setDate(today.getDate() + diffToMon);
        const sun = new Date(mon);
        sun.setDate(mon.getDate() + 6);
        return { from: fmt(mon), to: fmt(sun) };
    }
    if (val === 'month') {
        const first = new Date(today.getFullYear(), today.getMonth(), 1);
        const last = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        return { from: fmt(first), to: fmt(last) };
    }
    if (val === 'quarter') {
        const qStartMonth = Math.floor(today.getMonth() / 3) * 3;
        const firstQ = new Date(today.getFullYear(), qStartMonth, 1);
        const lastQ = new Date(today.getFullYear(), qStartMonth + 3, 0);
        return { from: fmt(firstQ), to: fmt(lastQ) };
    }
    if (val === 'year') {
        return { from: `${today.getFullYear()}-01-01`, to: `${today.getFullYear()}-12-31` };
    }
    return { from: '', to: '' };
}

function formatDisplayDate(dStr) {
    if (!dStr) return '';
    const parts = dStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dStr;
}

function updatePrjQuickDateRangeSpan(val, suffix = '') {
    const spanId = suffix ? `prjQuickDateRange_${suffix}` : 'prjQuickDateRange';
    const fromId = suffix ? `date_from_${suffix}` : 'date_from';
    const toId = suffix ? `date_to_${suffix}` : 'date_to';

    const span = document.getElementById(spanId);
    if (!span) return;
    if (val === 'all' || !val) {
        span.textContent = '';
        return;
    }
    if (val === 'custom_onboarding' || val === 'custom_delivery' || val === 'custom') {
        const f = document.getElementById(fromId)?.value;
        const t = document.getElementById(toId)?.value;
        const prefix = (val === 'custom_onboarding') ? 'Onboard: ' : 'Delivery: ';
        span.textContent = (f && t) ? `${prefix}${formatDisplayDate(f)} - ${formatDisplayDate(t)}` : (val === 'custom_onboarding' ? 'Onboard Date' : 'Delivery Date');
        return;
    }
    const dates = calcPresetDates(val);
    if (dates.from && dates.to) {
        span.textContent = `${formatDisplayDate(dates.from)} - ${formatDisplayDate(dates.to)}`;
    } else {
        span.textContent = '';
    }
}

function onPrjQuickDateChange(val, suffix = '') {
    const fromFieldId = suffix ? `prjFromField_${suffix}` : 'prjFromField';
    const toFieldId = suffix ? `prjToField_${suffix}` : 'prjToField';
    const fromInputId = suffix ? `date_from_${suffix}` : 'date_from';
    const toInputId = suffix ? `date_to_${suffix}` : 'date_to';
    const dateTypeId = suffix ? `date_type_${suffix}` : 'date_type';

    const fromField = document.getElementById(fromFieldId);
    const toField = document.getElementById(toFieldId);
    const f = document.getElementById(fromInputId);
    const t = document.getElementById(toInputId);
    const dateTypeInput = document.getElementById(dateTypeId);

    if (val === 'custom_onboarding' || val === 'custom_delivery' || val === 'custom') {
        if (fromField) fromField.style.display = 'flex';
        if (toField) toField.style.display = 'flex';
        if (dateTypeInput) {
            dateTypeInput.value = (val === 'custom_onboarding') ? 'onboarding' : 'delivery';
        }
    } else {
        if (fromField) fromField.style.display = 'none';
        if (toField) toField.style.display = 'none';
        if (dateTypeInput) {
            dateTypeInput.value = 'delivery';
        }
        if (val === 'all') {
            if (f) f.value = '';
            if (t) t.value = '';
        } else {
            const dates = calcPresetDates(val);
            if (f) f.value = dates.from;
            if (t) t.value = dates.to;
        }
    }
    updatePrjQuickDateRangeSpan(val, suffix);
}

document.addEventListener('DOMContentLoaded', function() {
    ['', 'contrib'].forEach(suffix => {
        const fromInputId = suffix ? `date_from_${suffix}` : 'date_from';
        const toInputId = suffix ? `date_to_${suffix}` : 'date_to';
        const selectId = suffix ? `quick_date_select_${suffix}` : 'quick_date_select';
        const fromFieldId = suffix ? `prjFromField_${suffix}` : 'prjFromField';
        const toFieldId = suffix ? `prjToField_${suffix}` : 'prjToField';

        const f = document.getElementById(fromInputId);
        const t = document.getElementById(toInputId);
        const q = document.getElementById(selectId);

        function handleDateInput() {
            if (q && q.value !== 'custom_onboarding' && q.value !== 'custom_delivery') {
                q.value = 'custom_delivery';
            }
            const fromField = document.getElementById(fromFieldId);
            const toField = document.getElementById(toFieldId);
            if (fromField) fromField.style.display = 'flex';
            if (toField) toField.style.display = 'flex';
            updatePrjQuickDateRangeSpan(q?.value || 'custom_delivery', suffix);
        }

        if (f) f.addEventListener('change', handleDateInput);
        if (t) t.addEventListener('change', handleDateInput);

        if (q) {
            updatePrjQuickDateRangeSpan(q.value, suffix);
        }
    });
});

const pendingProductsData = @json($pendingProductsSummary ?? []);

function openBulkAllocationModal() {
    const modal = document.getElementById('bulkAllocationModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('is-open', 'active');
        document.body.style.overflow = 'hidden';
    }
}

function closeBulkAllocationModal() {
    const modal = document.getElementById('bulkAllocationModal');
    if (modal) {
        modal.classList.remove('is-open', 'active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBulkAllocationModal();
    }
});

function onBulkProductChange(productId) {
    const container = document.getElementById('bulkPreviewContainer');
    const list = document.getElementById('bulkProjectsList');
    const submitBtn = document.getElementById('bulkSubmitBtn');
    
    if (!productId) {
        if (container) container.style.display = 'none';
        if (list) list.innerHTML = '';
        if (submitBtn) submitBtn.textContent = 'Allocate Projects';
        return;
    }

    const prodData = pendingProductsData.find(p => String(p.product_id) === String(productId));
    if (!prodData || !prodData.projects || prodData.projects.length === 0) {
        if (container) container.style.display = 'none';
        if (list) list.innerHTML = '';
        return;
    }

    let html = '';
    prodData.projects.forEach((proj) => {
        html += `
            <div class="bulk-item-row" onclick="toggleBulkItemCheckbox(event, ${proj.id})">
                <input type="checkbox" name="project_ids[]" value="${proj.id}" class="bulk-project-cb" id="proj_cb_${proj.id}" checked onchange="updateBulkSelectionCount()">
                <div class="bulk-item-info">
                    <div class="bulk-item-title">${escapeHtml(proj.company_name)} <span class="bulk-item-client">(${escapeHtml(proj.client_name)})</span></div>
                    <div class="bulk-item-meta">Lead: ${escapeHtml(proj.lead_id)} &bull; Date: ${escapeHtml(proj.date)}</div>
                </div>
            </div>
        `;
    });

    list.innerHTML = html;
    container.style.display = 'block';
    
    const masterCb = document.getElementById('bulkMasterCheckbox');
    if (masterCb) masterCb.checked = true;

    updateBulkSelectionCount();
}

function toggleBulkItemCheckbox(e, projId) {
    if (e.target.tagName.toLowerCase() === 'input') return;
    const cb = document.getElementById(`proj_cb_${projId}`);
    if (cb) {
        cb.checked = !cb.checked;
        updateBulkSelectionCount();
    }
}

function toggleSelectAllBulkProjects(masterCb) {
    const checkboxes = document.querySelectorAll('.bulk-project-cb');
    checkboxes.forEach(cb => {
        cb.checked = masterCb.checked;
    });
    updateBulkSelectionCount();
}

function updateBulkSelectionCount() {
    const checkboxes = document.querySelectorAll('.bulk-project-cb');
    const checkedBoxes = document.querySelectorAll('.bulk-project-cb:checked');
    
    const selectedSpan = document.getElementById('bulkSelectedCount');
    const totalSpan = document.getElementById('bulkTotalCount');
    const masterCb = document.getElementById('bulkMasterCheckbox');
    const submitBtn = document.getElementById('bulkSubmitBtn');

    if (selectedSpan) selectedSpan.textContent = checkedBoxes.length;
    if (totalSpan) totalSpan.textContent = checkboxes.length;
    
    if (masterCb) {
        masterCb.checked = checkboxes.length > 0 && checkedBoxes.length === checkboxes.length;
    }

    if (submitBtn) {
        submitBtn.textContent = checkedBoxes.length > 0 ? `Allocate ${checkedBoxes.length} Project${checkedBoxes.length > 1 ? 's' : ''}` : 'Allocate Projects';
        submitBtn.disabled = checkedBoxes.length === 0;
        submitBtn.style.opacity = checkedBoxes.length === 0 ? '0.6' : '1';
        submitBtn.style.cursor = checkedBoxes.length === 0 ? 'not-allowed' : 'pointer';
    }
}

function validateBulkForm() {
    const checkedBoxes = document.querySelectorAll('.bulk-project-cb:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one project to allocate.');
        return false;
    }
    const assignee = document.getElementById('bulk_assignee_select');
    if (!assignee || !assignee.value) {
        alert('Please select an employee to allocate the projects to.');
        return false;
    }
    return true;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endpush

