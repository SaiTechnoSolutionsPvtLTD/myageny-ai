@extends('layouts.app')

@section('title', 'Customer Success Dashboard – myAgenci.ai')

@push('styles')
<style>
/* ============================================================
   CUSTOMER SUCCESS & SUPPORT TEAM DASHBOARD
   ============================================================ */
:root {
    --cs-bg:         #f3f4f6;
    --cs-card-bg:    #ffffff;
    --cs-border:     #e5e7eb;
    --cs-text:       #111827;
    --cs-muted:      #6b7280;
    --cs-orange:     #fe5f04;
    --cs-purple:     #6d28d9;
    --cs-teal:       #0d9488;
    --cs-emerald:    #059669;
    --cs-rose:       #be123c;
    --cs-blue:       #1d4ed8;
    --cs-amber:      #b45309;
}

.cs-wrap          { display:flex; flex-direction:column; flex-grow:1; overflow:hidden; font-family:'Inter', sans-serif; background:var(--cs-bg); }
.cs-header        { display:flex; justify-content:space-between; align-items:center;
                     padding:20px 32px; border-bottom:1px solid var(--cs-border);
                     background:var(--cs-card-bg); position:sticky; top:0; z-index:20; }
.cs-body          { flex-grow:1; overflow-y:auto; padding:28px 32px; display:flex; flex-direction:column; gap:28px; }

/* ── Filter Card (Lead Products Style) ────────────────────── */
.lpd-filter-card { background:#fff; border:1px solid var(--cs-border); border-radius:18px; overflow:hidden; box-shadow:0 4px 6px -1px rgba(0,0,0,0.03); display:block; }
.lpd-filter-toggle {
    width:100%; display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 20px;
    border:none; background:linear-gradient(180deg,#fffaf7 0%, #fff 100%); cursor:pointer; text-align:left; font-family:inherit;
    list-style:none;
}
.lpd-filter-toggle::-webkit-details-marker { display:none; }
.lpd-filter-toggle:hover { background:linear-gradient(180deg,#fff7f1 0%, #fff 100%); }
.lpd-filter-card[open] .lpd-filter-toggle { border-bottom:1px solid var(--cs-border); }
.lpd-filter-toggle-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.lpd-filter-title { font-size:14px; font-weight:800; color:#111827; }
.lpd-filter-sub { font-size:11px; color:#9e9e9e; margin-top:2px; }
.lpd-filter-pill {
    display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:999px;
    background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:11px; font-weight:800;
}
.lpd-filter-chevron {
    width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px;
    border:1px solid #e8e3e8; background:#fff; color:#7c7c7c; transition:transform .18s ease, color .18s ease, border-color .18s ease;
}
.lpd-filter-card[open] .lpd-filter-chevron { transform:rotate(180deg); color:#fe5f04; border-color:#fed7aa; }
.lpd-filter-form { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:16px; padding:20px; align-items:flex-start; }
.lpd-field { display:flex; flex-direction:column; gap:6px; }
.lpd-label { font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
.lpd-input, .lpd-select {
    width:100%; padding:9px 13px; border:1px solid #e1dee3; border-radius:10px;
    background:#fbfbfc; font-size:13px; font-family:inherit; color:#111827; outline:none; transition:all .15s ease;
}
.lpd-input:hover, .lpd-select:hover { border-color:#d7d1d8; background:#fff; }
.lpd-input:focus, .lpd-select:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.lpd-filter-actions-row { grid-column:1 / -1; display:flex; align-items:center; justify-content:flex-end; gap:10px; padding-top:14px; border-top:1px solid #f0eef2; margin-top:4px; }
.lpd-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; font-family:inherit; text-decoration:none; cursor:pointer; }
.lpd-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; border:none; box-shadow:0 4px 14px rgba(254,95,4,.22); }
.lpd-btn-primary:hover { color:#fff; background:linear-gradient(135deg,#f35700,#ff7422); transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.32); }
.lpd-btn-ghost { background:#fff; border:1px solid #e1dee3; color:#6b7280; }
.lpd-btn-ghost:hover { border-color:#dc2626 !important; color:#dc2626 !important; }

@media (max-width: 1200px) {
    .lpd-filter-form { grid-template-columns:repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .lpd-filter-form { grid-template-columns:1fr; }
    .lpd-filter-toggle { flex-direction:column; align-items:flex-start; }
    .lpd-filter-toggle-right { width:100%; justify-content:space-between; }
}

/* ── Loading Overlay ────────────────────────────────────── */
#loadingOverlay    { display:none; position:fixed; inset:0; background:rgba(243,244,246,.7);
                     z-index:999; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.spinner           { width:44px; height:44px; border:4px solid var(--cs-border);
                     border-top-color:var(--cs-orange); border-radius:50%; animation:spin .8s linear infinite; }
@keyframes spin    { to { transform:rotate(360deg); } }

/* ── Summary Cards ──────────────────────────────────────── */
.cards-grid        { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
                     gap:16px; }
.summary-card      { position:relative; overflow:hidden; border:none; border-radius:16px;
                     padding:20px; display:flex; flex-direction:column; justify-content:space-between;
                     transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; min-height:140px;
                     box-shadow:0 10px 15px -3px rgba(0,0,0,0.05),0 4px 6px -2px rgba(0,0,0,0.02);
                     cursor:pointer; user-select:none; }
.summary-card:hover { transform:translateY(-5px); box-shadow:0 20px 25px -5px rgba(0,0,0,0.15),0 10px 10px -5px rgba(0,0,0,0.08); filter:brightness(1.04); }
.summary-card .sc-label  { font-size:11px; font-weight:800; color:rgba(255,255,255,0.9); text-transform:uppercase; letter-spacing:.06em; margin-top:4px; }
.summary-card .sc-value  { font-size:26px; font-weight:900; color:#fff; line-height:1.2; margin-top:8px; }
.summary-card .sc-sub    { font-size:12px; color:rgba(255,255,255,0.8); font-weight:500; margin-top:8px; }
.sc-icon           { width:38px; height:38px; border-radius:12px; display:flex;
                     align-items:center; justify-content:center; font-size:18px; margin-bottom:12px;
                     background:rgba(255,255,255,0.2) !important; backdrop-filter:blur(4px); }

/* Gradients */
.sc-orange  { background:linear-gradient(135deg, #fe5f04 0%, #ff8c42 100%) !important; }
.sc-emerald { background:linear-gradient(135deg, #059669 0%, #10b981 100%) !important; }
.sc-blue    { background:linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%) !important; }
.sc-purple  { background:linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%) !important; }
.sc-amber   { background:linear-gradient(135deg, #d97706 0%, #f59e0b 100%) !important; }
.sc-teal    { background:linear-gradient(135deg, #0d9488 0%, #14b8a6 100%) !important; }
.sc-rose    { background:linear-gradient(135deg, #e11d48 0%, #f43f5e 100%) !important; }
.sc-indigo  { background:linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important; }
.sc-cyan    { background:linear-gradient(135deg, #0284c7 0%, #06b6d4 100%) !important; }
.divider-card { grid-column:1/-1; height:1px; background:var(--cs-border); margin:12px 0; }

/* Tab buttons */
.cs-tab-btn {
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--cs-border);
    background: #fff;
    color: var(--cs-muted);
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.cs-tab-btn:hover {
    background: #f9fafb;
    color: var(--cs-text);
    border-color: #d1d5db;
}
.cs-tab-btn.active {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(254, 95, 4, 0.25);
}

/* Skeletons */
.cs-skel { background:linear-gradient(90deg,#f3f0f6 25%,#e9e5ee 50%,#f3f0f6 75%); background-size:200% 100%; animation:shimmer 1.4s infinite; border-radius:6px; }
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
.cs-skel-card { background:#ffffff; border:1px solid var(--cs-border); border-radius:16px; padding:20px; min-height:140px; display:flex; flex-direction:column; justify-content:space-between; }

/* ── Layout Panels ──────────────────────────────────────── */
.dashboard-grid    { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
.dashboard-grid-full { grid-column:1/-1; }
.dashboard-panel   { background:var(--cs-card-bg); border:1px solid var(--cs-border); border-radius:16px;
                     padding:24px; display:flex; flex-direction:column; gap:16px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.03); }
.dashboard-panel h4 { font-size:15px; font-weight:800; color:var(--cs-text); margin:0; display:flex; align-items:center; gap:8px; }

/* ── Tables ─────────────────────────────────────────────── */
.table-wrap        { overflow-x:auto; width:100%; }
.cs-table          { width:100%; border-collapse:collapse; text-align:left; font-size:13px; }
.cs-table th       { background:#f9fafb; padding:12px 16px; font-size:11px; font-weight:700;
                     color:var(--cs-muted); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--cs-border); }
.cs-table td       { padding:12px 16px; border-bottom:1px solid var(--cs-border); color:var(--cs-text); vertical-align:middle; }
.cs-table tr:hover td { background:#faf5ff; }
.cs-badge          { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:700; text-transform:capitalize; }
.cs-badge-paid     { background:#ecfdf5; color:#065f46; }
.cs-badge-partial  { background:#fffbeb; color:#92400e; }
.cs-badge-pending  { background:#fef2f2; color:#991b1b; }
.cs-badge-onboard  { background:#eff6ff; color:#1d4ed8; text-transform:uppercase; }
.cs-badge-ontrack  { background:#eff6ff; color:#1d4ed8; text-transform:uppercase; }
.cs-badge-hold     { background:#fffbeb; color:#92400e; text-transform:uppercase; }
.cs-badge-delivered{ background:#ecfdf5; color:#065f46; text-transform:uppercase; }

/* ── Dept Pending Table Scroll (10 records height) ── */
.dept-pending-scroll-wrap {
    max-height: 480px;
    overflow-y: auto;
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid var(--cs-border);
}
.dept-pending-scroll-wrap thead th {
    position: sticky;
    top: 0;
    background: #f9fafb;
    z-index: 2;
    box-shadow: 0 1px 0 var(--cs-border);
}
.dept-pending-scroll-wrap::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.dept-pending-scroll-wrap::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}
.dept-pending-scroll-wrap::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.dept-pending-scroll-wrap::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

@media(max-width:1024px) {
    .dashboard-grid   { grid-template-columns:1fr; }
}
@media(max-width:640px) {
    .cs-body          { padding:16px; }
    .filter-bar       { padding:16px; }
    .cards-grid       { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
<div id="loadingOverlay">
    <div class="spinner"></div>
</div>

<div class="cs-wrap">
    {{-- Header --}}
    <header class="cs-header">
        <div class="breadcrumbs">
            <span class="crumb-item">Dashboard</span>
            <span class="crumb-item active" style="color:var(--cs-text);font-weight:700;">Customer Success Dashboard</span>
        </div>
        <div style="font-size:12px;color:var(--cs-muted);font-weight:500;">
            🏢 Support & Success Team Scope Only
        </div>
    </header>

    <div class="cs-body">
        {{-- Filter Card (Lead Products Style) --}}
        <details class="lpd-filter-card" id="customerSuccessFilters" open>
            <summary class="lpd-filter-toggle" id="customerSuccessFiltersToggle">
                <div>
                    <div class="lpd-filter-title">Filter Customer Success Dashboard</div>
                    <div class="lpd-filter-sub">Narrow results by support agent, branch, product, lead source, or date period</div>
                </div>
                <div class="lpd-filter-toggle-right">
                    <div class="lpd-filter-pill">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        Support Filters
                    </div>
                    <span class="lpd-filter-chevron">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </summary>
            <div class="lpd-filter-body">
                <div class="lpd-filter-form" id="csFilterForm">
                    <div class="lpd-field">
                        <label class="lpd-label" for="f_user">Support Agent</label>
                        <select id="f_user" class="lpd-select">
                            <option value="">All Support Agents</option>
                        </select>
                    </div>

                    <div class="lpd-field">
                        <label class="lpd-label" for="f_branch">Branch</label>
                        <select id="f_branch" class="lpd-select">
                            <option value="">All Branches</option>
                        </select>
                    </div>

                    <div class="lpd-field">
                        <label class="lpd-label" for="f_product">Product</label>
                        <select id="f_product" class="lpd-select">
                            <option value="">All Products</option>
                        </select>
                    </div>

                    <div class="lpd-field">
                        <label class="lpd-label" for="f_source">Source</label>
                        <select id="f_source" class="lpd-select">
                            <option value="">All Sources</option>
                        </select>
                    </div>

                    <div class="lpd-field">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                            <label class="lpd-label" for="quick_date_select" style="margin-bottom:0;">Quick Dates</label>
                            <span id="lpdQuickDateRange" style="font-size:11px;font-weight:700;color:var(--cs-orange, #fe5f04);"></span>
                        </div>
                        <select id="quick_date_select" class="lpd-select" onchange="onQuickDateChange(this.value)">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                            <option value="all">Show All</option>
                            <option value="custom">Custom Dates</option>
                        </select>
                    </div>

                    <div class="lpd-field" id="fromField" style="display:none;">
                        <label class="lpd-label" for="f_from">From Date</label>
                        <input id="f_from" type="date" class="lpd-input">
                    </div>

                    <div class="lpd-field" id="toField" style="display:none;">
                        <label class="lpd-label" for="f_to">To Date</label>
                        <input id="f_to" type="date" class="lpd-input">
                    </div>

                    <div class="lpd-filter-actions-row">
                        <button type="button" class="lpd-btn lpd-btn-primary" onclick="loadDashboard()">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            Apply Filter
                        </button>
                        <button type="button" class="lpd-btn lpd-btn-ghost" onclick="resetFilters()">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.36"/></svg>
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </details>

        {{-- Summary Cards Grid --}}
        <div class="cards-grid" id="cardsGrid">
            @for($i = 0; $i < 9; $i++)
            <div class="cs-skel-card">
                <div class="cs-skel" style="height:38px;width:38px;border-radius:12px;margin-bottom:12px"></div>
                <div class="cs-skel" style="height:26px;width:60%;margin-bottom:8px"></div>
                <div class="cs-skel" style="height:12px;width:80%"></div>
            </div>
            @endfor
        </div>

        {{-- Main Dashboard Visualizations --}}
        <div class="dashboard-grid">
            {{-- Daily Collections Trend --}}
            <div class="dashboard-panel">
                <h4>📈 Daily Collections Trend</h4>
                <div style="height:260px;position:relative;">
                    <canvas id="collectionsChart"></canvas>
                </div>
            </div>

            {{-- Monthly Renewal Comparison --}}
            <div class="dashboard-panel">
                <h4>🔄 Renewals Period Comparison</h4>
                <div style="height:260px;position:relative;">
                    <canvas id="renewalsChart"></canvas>
                </div>
            </div>

            {{-- Department Product-Wise Payment Pending --}}
            <div class="dashboard-panel">
                <h4>🏢 Department Product-Wise Pending Payments</h4>
                <div class="table-wrap dept-pending-scroll-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Product</th>
                                <th style="text-align:right;">Pending Amount</th>
                                <th style="text-align:center;">Count</th>
                            </tr>
                        </thead>
                        <tbody id="deptPendingTableBody">
                            <tr><td colspan="4" style="text-align:center;color:var(--cs-muted);">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- User-Wise Performance --}}
            <div class="dashboard-panel">
                <h4>👤 User-Wise Support Performance</h4>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Agent Name</th>
                                <th style="text-align:center;">Leads Handled</th>
                                <th style="text-align:center;">Converted</th>
                                <th style="text-align:right;">Collections</th>
                                <th style="text-align:right;">CMR Target Value</th>
                            </tr>
                        </thead>
                        <tbody id="userPerformanceTableBody">
                            <tr><td colspan="5" style="text-align:center;color:var(--cs-muted);">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Converted Upsell Deals --}}
            <div class="dashboard-panel dashboard-grid-full">
                <h4>💎 Converted Upsell Deals (Non-Recurring Contracts)</h4>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Lead Account</th>
                                <th>Product Name</th>
                                <th style="text-align:right;">Deal Value</th>
                                <th style="text-align:center;">Converted Date</th>
                            </tr>
                        </thead>
                        <tbody id="upsellDealsTableBody">
                            <tr><td colspan="4" style="text-align:center;color:var(--cs-muted);">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Customer Campaigns Tracking --}}
            <div class="dashboard-panel dashboard-grid-full">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                    <div>
                        <h4 style="margin:0;">🎯 Customer Campaigns Tracking</h4>
                        <p style="font-size:12px;color:var(--cs-muted);margin:3px 0 0 0;">Monitor expired campaigns, renewals, and pending renewals for customer ad accounts.</p>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="cs-tab-btn active" id="btn_camp_cm_not_renewed" onclick="switchCampaignTab('cm_not_renewed')">
                            <span>⚠️ CM Not Renewed</span> (<span id="campCountNotRenewed">0</span>)
                        </button>
                        <button type="button" class="cs-tab-btn" id="btn_camp_cm_renewed" onclick="switchCampaignTab('cm_renewed')">
                            <span>✅ CM Renewed</span> (<span id="campCountRenewed">0</span>)
                        </button>
                        <button type="button" class="cs-tab-btn" id="btn_camp_expired" onclick="switchCampaignTab('expired')">
                            <span>⏳ Expired Campaigns</span> (<span id="campCountExpired">0</span>)
                        </button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Campaign & Client</th>
                                <th>Platform / Ad Account</th>
                                <th>Duration</th>
                                <th style="text-align:right;">Budget</th>
                                <th style="text-align:center;">Renewal Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="campaignsTableBody">
                            <tr><td colspan="6" style="text-align:center;color:var(--cs-muted);padding:20px;">Loading campaigns...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="campaignPagination" class="app-pagination" style="display:none; border-top:1px solid var(--cs-border); padding:12px 16px; border-radius:0 0 12px 12px; margin-top:4px;"></div>
            </div>

            {{-- Delivery Planned Projects --}}
            <div class="dashboard-panel dashboard-grid-full">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h4 id="deliverySectionTitle" style="color:var(--cs-text);font-weight:800;font-size:15px;margin:0;">Delivery Planned Projects</h4>
                    <span class="cs-badge cs-badge-paid" id="deliverySectionBadge" style="border-radius:12px; padding:4px 12px; font-weight:700;">Planned</span>
                </div>
                <p style="font-size:12px;color:var(--cs-muted);margin:0 0 10px 0;">Projects with planned delivery dates in this period.</p>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Delivery Date</th>
                                <th>Allocated Person</th>
                                <th>Status</th>
                                <th style="text-align:right;">Total Project Value</th>
                                <th style="text-align:right;">Received Amount</th>
                                <th style="text-align:right;">Pending Amount</th>
                                <th style="text-align:center;">View</th>
                            </tr>
                        </thead>
                        <tbody id="deliveryPlannedTableBody">
                            <tr><td colspan="8" style="text-align:center;color:var(--cs-muted);">Loading delivery planned projects...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="deliveryPagination" class="app-pagination" style="display:none; border-top:1px solid var(--cs-border); padding:12px 16px; border-radius:0 0 12px 12px; margin-top:4px;"></div>
            </div>

            {{-- Detailed Renewal Deals Lists --}}
            <div class="dashboard-panel dashboard-grid-full">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h4>📋 Active Month Renewal list</h4>
                    <div style="display:flex;gap:6px;">
                        <button class="btn-apply" style="padding:6px 12px;font-size:11px;" onclick="switchRenewalTab('cmr')">Current Month</button>
                        <button class="btn-reset" style="padding:6px 12px;font-size:11px;" id="btn_nmr" onclick="switchRenewalTab('nmr')">Next Month</button>
                        <button class="btn-reset" style="padding:6px 12px;font-size:11px;" id="btn_lmr" onclick="switchRenewalTab('lmr')">Last Month</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Product Package</th>
                                <th>Renewal Date</th>
                                <th style="text-align:right;">Contract Value</th>
                                <th style="text-align:right;">Collected</th>
                                <th style="text-align:right;">Pending Collection</th>
                            </tr>
                        </thead>
                        <tbody id="renewalDealsTableBody">
                            <tr><td colspan="6" style="text-align:center;color:var(--cs-muted);">Select a tab or apply filters to view renewal listings.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let collectionChartInst = null;
let renewalChartInst = null;
let dashboardDataRaw = null;
let activeRenewalTab = 'cmr';

const fmt = n => '₹' + Number(n ?? 0).toLocaleString('en-IN', {maximumFractionDigits:2});
const num = n => Number(n ?? 0).toLocaleString('en-IN');

function calcPresetDates(val) {
    const today = new Date();
    const fmt = d => {
        const yr = d.getFullYear();
        const mo = String(d.getMonth() + 1).padStart(2, '0');
        const da = String(d.getDate()).padStart(2, '0');
        return `${yr}-${mo}-${da}`;
    };

    if (val === 'today') {
        return { from: fmt(today), to: fmt(today) };
    }
    if (val === 'week') {
        const day = today.getDay();
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

function updateQuickDateRangeSpan(val) {
    const span = document.getElementById('lpdQuickDateRange');
    if (!span) return;
    if (val === 'all') {
        span.textContent = 'All Time';
        return;
    }
    if (val === 'custom') {
        const f = document.getElementById('f_from')?.value;
        const t = document.getElementById('f_to')?.value;
        span.textContent = (f && t) ? `${formatDisplayDate(f)} - ${formatDisplayDate(t)}` : 'Custom';
        return;
    }
    const dates = calcPresetDates(val);
    if (dates.from && dates.to) {
        span.textContent = `${formatDisplayDate(dates.from)} - ${formatDisplayDate(dates.to)}`;
    } else {
        span.textContent = '';
    }
}

function onQuickDateChange(val) {
    const fromField = document.getElementById('fromField');
    const toField = document.getElementById('toField');
    const f = document.getElementById('f_from');
    const t = document.getElementById('f_to');

    if (val === 'custom') {
        if (fromField) fromField.style.display = 'flex';
        if (toField) toField.style.display = 'flex';
    } else {
        if (fromField) fromField.style.display = 'none';
        if (toField) toField.style.display = 'none';
        if (val === 'all') {
            if (f) f.value = '';
            if (t) t.value = '';
        } else {
            const dates = calcPresetDates(val);
            if (f) f.value = dates.from;
            if (t) t.value = dates.to;
        }
    }
    updateQuickDateRangeSpan(val);
}

document.addEventListener('DOMContentLoaded', function() {
    const dates = calcPresetDates('month');
    document.getElementById('f_from').value = dates.from;
    document.getElementById('f_to').value = dates.to;
    updateQuickDateRangeSpan('month');

    const f = document.getElementById('f_from');
    const t = document.getElementById('f_to');
    const q = document.getElementById('quick_date_select');

    if (f) f.addEventListener('change', () => {
        if (q) q.value = 'custom';
        const fromField = document.getElementById('fromField');
        const toField = document.getElementById('toField');
        if (fromField) fromField.style.display = 'flex';
        if (toField) toField.style.display = 'flex';
        updateQuickDateRangeSpan('custom');
    });
    if (t) t.addEventListener('change', () => {
        if (q) q.value = 'custom';
        const fromField = document.getElementById('fromField');
        const toField = document.getElementById('toField');
        if (fromField) fromField.style.display = 'flex';
        if (toField) toField.style.display = 'flex';
        updateQuickDateRangeSpan('custom');
    });

    // Load filter options first
    loadFilters().then(() => {
        loadDashboard();
    });
});

async function loadFilters() {
    try {
        const res = await fetch('/api/customer-success/filters');
        if (res.status === 401) {
            window.location.href = "{{ route('login') }}";
            return;
        }
        const json = await res.json();
        if (!json.status) throw new Error(json.message);

        const d = json.data;
        
        // Hydrate dropdowns
        const userSelect = document.getElementById('f_user');
        d.users.forEach(u => {
            const opt = new Option(u.name, u.id);
            userSelect.add(opt);
        });

        const branchSelect = document.getElementById('f_branch');
        d.branches.forEach(b => {
            const opt = new Option(b.name, b.id);
            branchSelect.add(opt);
        });

        const productSelect = document.getElementById('f_product');
        d.products.forEach(p => {
            const opt = new Option(p.product_name, p.id);
            productSelect.add(opt);
        });

        const sourceSelect = document.getElementById('f_source');
        d.sources.forEach(s => {
            const opt = new Option(s, s);
            sourceSelect.add(opt);
        });

    } catch (err) {
        console.error('Failed to load filter options:', err);
    }
}

function getFilterValues() {
    return {
        user_id: document.getElementById('f_user').value,
        branch_id: document.getElementById('f_branch').value,
        product_id: document.getElementById('f_product').value,
        source: document.getElementById('f_source').value,
        from_date: document.getElementById('f_from').value,
        to_date: document.getElementById('f_to').value,
    };
}

function resetFilters() {
    document.getElementById('f_user').value = '';
    document.getElementById('f_branch').value = '';
    document.getElementById('f_product').value = '';
    document.getElementById('f_source').value = '';
    
    const q = document.getElementById('quick_date_select');
    if (q) q.value = 'month';
    
    const dates = calcPresetDates('month');
    document.getElementById('f_from').value = dates.from;
    document.getElementById('f_to').value = dates.to;
    
    const fromField = document.getElementById('fromField');
    const toField = document.getElementById('toField');
    if (fromField) fromField.style.display = 'none';
    if (toField) toField.style.display = 'none';
    
    updateQuickDateRangeSpan('month');
    loadDashboard();
}

async function loadDashboard() {
    deliveryCurrentPage = 1;
    document.getElementById('loadingOverlay').style.display = 'flex';
    const filters = getFilterValues();
    const qs = Object.entries(filters)
        .filter(([, v]) => v)
        .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
        .join('&');

    try {
        const res = await fetch(`/api/customer-success/data?${qs}`);
        if (res.status === 401) {
            window.location.href = "{{ route('login') }}";
            return;
        }
        const json = await res.json();
        if (!json.status) throw new Error(json.message);

        dashboardDataRaw = json.data;
        renderDashboard(json.data);
    } catch (err) {
        console.error('Failed to load dashboard:', err);
        alert('Could not retrieve dashboard data. See console logs.');
    } finally {
        document.getElementById('loadingOverlay').style.display = 'none';
    }
}

function renderDashboard(data) {
    // 1. KPI Cards
    const cardsGrid = document.getElementById('cardsGrid');
    const campExpired = data.campaigns?.expired || { count: 0, value: 0 };
    const campRenewed = data.campaigns?.cm_renewed || { count: 0, value: 0 };
    const campNotRenewed = data.campaigns?.cm_not_renewed || { count: 0, value: 0 };

    cardsGrid.innerHTML = `
        <div class="summary-card sc-orange" onclick="navigateCard('lead_products', { is_renewal: true, renewal_period: 'cmr' })" title="Click to view Current Month Renewals in Lead Products">
            <div class="sc-icon">📅</div>
            <div class="sc-value">${fmt(data.cmr.value)}</div>
            <div class="sc-label">Current Month Renewal (CMR)</div>
            <div class="sc-sub">${data.cmr.count} accounts renewing</div>
        </div>
        <div class="summary-card sc-emerald" onclick="navigateCard('lead_products', { is_renewal: true, renewal_period: 'cmr_plus' })" title="Click to view Next Month Renewals in Lead Products">
            <div class="sc-icon">⏭️</div>
            <div class="sc-value">${fmt(data.cmr_plus.value)}</div>
            <div class="sc-label">Next Month Renewal (CMR+1)</div>
            <div class="sc-sub">${data.cmr_plus.count} accounts renewing</div>
        </div>
        <div class="summary-card sc-blue" onclick="navigateCard('lead_products', { is_renewal: true, renewal_period: 'cmr_minus' })" title="Click to view Last Month Renewals in Lead Products">
            <div class="sc-icon">⏮️</div>
            <div class="sc-value">${fmt(data.cmr_minus.value)}</div>
            <div class="sc-label">Last Month Renewal (CMR-1)</div>
            <div class="sc-sub">${data.cmr_minus.count} accounts renewing</div>
        </div>
        <div class="summary-card sc-purple" onclick="navigateCard('lead_products', { product_status: 'converted' })" title="Click to view Converted Upsell Deals in Lead Products">
            <div class="sc-icon">💎</div>
            <div class="sc-value">${fmt(data.upsells.value)}</div>
            <div class="sc-label">Upsell Converted (Period)</div>
            <div class="sc-sub">${data.upsells.count} deals closed</div>
        </div>
        <div class="summary-card sc-amber" onclick="navigateCard('payment_collection', { quick_date: 'today' })" title="Click to view Today Payments in Payment Collection Report">
            <div class="sc-icon">💵</div>
            <div class="sc-value">${fmt(data.today_payments)}</div>
            <div class="sc-label">Today Received Payments</div>
            <div class="sc-sub">Collections received today</div>
        </div>
        <div class="summary-card sc-teal" onclick="navigateCard('payment_collection', { quick_date: 'month' })" title="Click to view This Month Collections in Payment Collection Report">
            <div class="sc-icon">📊</div>
            <div class="sc-value">${fmt(data.month_payments)}</div>
            <div class="sc-label">This Month Collections</div>
            <div class="sc-sub">Total collected this month</div>
        </div>
        <div class="summary-card sc-indigo" onclick="navigateCard('campaigns', { renewal_filter: 'cm_not_renewed', period: 'cm' })" title="Click to view CM Not Renewed Campaigns in Digital Marketing Campaigns">
            <div class="sc-icon">⚠️</div>
            <div class="sc-value">${fmt(campNotRenewed.value)}</div>
            <div class="sc-label">CM Not Renewed Campaigns</div>
            <div class="sc-sub">${campNotRenewed.count} campaigns pending renewal</div>
        </div>
        <div class="summary-card sc-cyan" onclick="navigateCard('campaigns', { renewal_filter: 'cm_renewed', period: 'cm' })" title="Click to view CM Renewed Campaigns in Digital Marketing Campaigns">
            <div class="sc-icon">✅</div>
            <div class="sc-value">${fmt(campRenewed.value)}</div>
            <div class="sc-label">CM Renewed Campaigns</div>
            <div class="sc-sub">${campRenewed.count} campaigns renewed this month</div>
        </div>
        <div class="summary-card sc-rose" onclick="navigateCard('campaigns', { renewal_filter: 'expired', status: 'expired' })" title="Click to view Expired Campaigns in Digital Marketing Campaigns">
            <div class="sc-icon">⏳</div>
            <div class="sc-value">${fmt(campExpired.value)}</div>
            <div class="sc-label">Expired Campaigns</div>
            <div class="sc-sub">${campExpired.count} campaigns expired</div>
        </div>
    `;

    // 2. Department Pending Table
    const deptTbody = document.getElementById('deptPendingTableBody');
    if (!data.dept_product_pending || data.dept_product_pending.length === 0) {
        deptTbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--cs-muted);">No pending payments.</td></tr>`;
    } else {
        deptTbody.innerHTML = data.dept_product_pending.map(row => `
            <tr>
                <td><strong>${row.department}</strong></td>
                <td>${row.product}</td>
                <td style="text-align:right;font-weight:700;color:var(--cs-rose);">${fmt(row.pending_amount)}</td>
                <td style="text-align:center;font-weight:600;">${row.count}</td>
            </tr>
        `).join('');
    }

    // 3. User Performance Table
    const userTbody = document.getElementById('userPerformanceTableBody');
    if (!data.user_performance || data.user_performance.length === 0) {
        userTbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:var(--cs-muted);">No performance records.</td></tr>`;
    } else {
        userTbody.innerHTML = data.user_performance.map(row => `
            <tr>
                <td><strong>${row.name}</strong></td>
                <td style="text-align:center;">${row.handled_leads}</td>
                <td style="text-align:center;">${row.converted_leads}</td>
                <td style="text-align:right;font-weight:700;color:var(--cs-emerald);">${fmt(row.total_collected)}</td>
                <td style="text-align:right;font-weight:600;color:var(--cs-blue);">${fmt(row.cmr_value)}</td>
            </tr>
        `).join('');
    }

    // 4. Converted Upsell Table
    const upsellTbody = document.getElementById('upsellDealsTableBody');
    if (!data.upsells.items || data.upsells.items.length === 0) {
        upsellTbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--cs-muted);">No upsell deals in the selected range.</td></tr>`;
    } else {
        upsellTbody.innerHTML = data.upsells.items.map(row => `
            <tr>
                <td><strong><a href="/leads/${row.id}" style="color:var(--cs-purple);text-decoration:none;">${row.company_name}</a></strong></td>
                <td>${row.product_name}</td>
                <td style="text-align:right;font-weight:700;color:var(--cs-purple);">${fmt(row.value)}</td>
                <td style="text-align:center;">${row.created_at}</td>
            </tr>
        `).join('');
    }

    // 4.1 Customer Campaigns Tracking
    const elCampExp = document.getElementById('campCountExpired');
    const elCampRen = document.getElementById('campCountRenewed');
    const elCampNotRen = document.getElementById('campCountNotRenewed');
    if (elCampExp) elCampExp.textContent = campExpired.count;
    if (elCampRen) elCampRen.textContent = campRenewed.count;
    if (elCampNotRen) elCampNotRen.textContent = campNotRenewed.count;
    renderCampaignsTable();

    // 4.2 Delivery Planned Projects Table
    const deliveryTitleEl = document.getElementById('deliverySectionTitle');
    const deliveryBadgeEl = document.getElementById('deliverySectionBadge');
    if (deliveryTitleEl) deliveryTitleEl.textContent = data.delivery_title || 'Delivery Planned Projects';
    if (deliveryBadgeEl) deliveryBadgeEl.textContent = `${data.delivery_projects ? data.delivery_projects.length : 0} ${data.delivery_badge || 'Planned'}`;

    renderDeliveryPlannedProjects(data.delivery_projects);

    // 5. Renewal Deals Table tab rendering
    switchRenewalTab(activeRenewalTab);

    // 6. Chart: Daily Collections Trend
    renderCollectionsChart(data.daily_trend);

    // 7. Chart: Renewals Month Comparison
    renderRenewalsChart(data);
}

let activeCampaignTab = 'cm_not_renewed';
let campaignCurrentPage = 1;
const campaignPageSize = 10;

function switchCampaignTab(tab) {
    activeCampaignTab = tab;
    campaignCurrentPage = 1;

    ['cm_not_renewed', 'cm_renewed', 'expired'].forEach(k => {
        const btn = document.getElementById('btn_camp_' + k);
        if (btn) {
            if (k === tab) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    });

    renderCampaignsTable();
}

function renderCampaignsTable() {
    if (!dashboardDataRaw || !dashboardDataRaw.campaigns) return;

    let items = [];
    if (activeCampaignTab === 'cm_not_renewed') {
        items = dashboardDataRaw.campaigns.cm_not_renewed?.items || [];
    } else if (activeCampaignTab === 'cm_renewed') {
        items = dashboardDataRaw.campaigns.cm_renewed?.items || [];
    } else if (activeCampaignTab === 'expired') {
        items = dashboardDataRaw.campaigns.expired?.items || [];
    }

    const total = items.length;
    const totalPages = Math.max(1, Math.ceil(total / campaignPageSize));
    if (campaignCurrentPage > totalPages) campaignCurrentPage = totalPages;
    if (campaignCurrentPage < 1) campaignCurrentPage = 1;

    const startIdx = (campaignCurrentPage - 1) * campaignPageSize;
    const endIdx = Math.min(startIdx + campaignPageSize, total);
    const pageItems = items.slice(startIdx, endIdx);

    const tbody = document.getElementById('campaignsTableBody');
    const paginationEl = document.getElementById('campaignPagination');

    if (total === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--cs-muted);padding:24px;">No campaigns found in this category.</td></tr>`;
        if (paginationEl) paginationEl.style.display = 'none';
        return;
    }

    tbody.innerHTML = pageItems.map(row => {
        let renewalBadge = '';
        if (row.is_renewed) {
            renewalBadge = `<span class="cs-badge cs-badge-paid">Renewed</span>`;
        } else {
            renewalBadge = `<span class="cs-badge cs-badge-pending">Not Renewed</span>`;
        }

        return `
            <tr>
                <td>
                    <strong style="color:var(--cs-text);">${row.campaign_name || 'Campaign'}</strong>
                    <div style="font-size:11px;color:var(--cs-muted);margin-top:2px;">
                        🏢 ${row.company_name} · 📞 ${row.mobile_number}
                    </div>
                </td>
                <td>
                    <span class="cs-badge cs-badge-onboard">${row.platform}</span>
                    <div style="font-size:11px;color:var(--cs-muted);margin-top:2px;">
                        ${row.ad_account_name || '—'}
                    </div>
                </td>
                <td>
                    <div style="font-size:12px;font-weight:600;">${row.start_date} → ${row.end_date}</div>
                    <div style="font-size:11px;color:var(--cs-muted);">${row.budget_type || 'Monthly'}</div>
                </td>
                <td style="text-align:right;font-weight:700;color:var(--cs-purple);">
                    ${fmt(row.budget)}
                </td>
                <td style="text-align:center;">
                    ${renewalBadge}
                </td>
                <td style="text-align:center;">
                    <a href="/projects/campaigns/${row.lead_id}" class="lpd-btn lpd-btn-ghost" style="padding:4px 10px;font-size:11px;text-decoration:none;display:inline-flex;">
                        View Campaigns
                    </a>
                </td>
            </tr>
        `;
    }).join('');

    if (paginationEl) {
        if (totalPages <= 1 && total <= campaignPageSize) {
            paginationEl.style.display = total > 0 ? 'flex' : 'none';
            paginationEl.innerHTML = `
                <div class="app-pagination__info">
                    Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${total}</strong> campaigns
                </div>
                <div class="app-pagination__links">
                    <span class="app-pagination__link is-active">1</span>
                </div>
            `;
            return;
        }

        paginationEl.style.display = 'flex';
        let linksHtml = '';

        // Prev button
        if (campaignCurrentPage > 1) {
            linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeCampaignPage(${campaignCurrentPage - 1})">Prev</a>`;
        } else {
            linksHtml += `<span class="app-pagination__link is-disabled">Prev</span>`;
        }

        // Page numbers
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= campaignCurrentPage - 1 && p <= campaignCurrentPage + 1)) {
                if (p === campaignCurrentPage) {
                    linksHtml += `<span class="app-pagination__link is-active">${p}</span>`;
                } else {
                    linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeCampaignPage(${p})">${p}</a>`;
                }
            } else if (p === campaignCurrentPage - 2 || p === campaignCurrentPage + 2) {
                linksHtml += `<span class="app-pagination__ellipsis">...</span>`;
            }
        }

        // Next button
        if (campaignCurrentPage < totalPages) {
            linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeCampaignPage(${campaignCurrentPage + 1})">Next</a>`;
        } else {
            linksHtml += `<span class="app-pagination__link is-disabled">Next</span>`;
        }

        paginationEl.innerHTML = `
            <div class="app-pagination__info">
                Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${total}</strong> campaigns
            </div>
            <div class="app-pagination__links">
                ${linksHtml}
            </div>
        `;
    }
}

function changeCampaignPage(page) {
    campaignCurrentPage = page;
    renderCampaignsTable();
}

let deliveryCurrentPage = 1;
const deliveryPageSize = 10;

function renderDeliveryPlannedProjects(projects) {
    projects = projects || [];
    const total = projects.length;
    const totalPages = Math.max(1, Math.ceil(total / deliveryPageSize));
    if (deliveryCurrentPage > totalPages) deliveryCurrentPage = totalPages;
    if (deliveryCurrentPage < 1) deliveryCurrentPage = 1;

    const startIdx = (deliveryCurrentPage - 1) * deliveryPageSize;
    const endIdx = Math.min(startIdx + deliveryPageSize, total);
    const pageItems = projects.slice(startIdx, endIdx);

    const deliveryTbody = document.getElementById('deliveryPlannedTableBody');
    const paginationEl = document.getElementById('deliveryPagination');

    if (total === 0) {
        deliveryTbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--cs-muted);">No delivery scheduled in this period.</td></tr>`;
        if (paginationEl) paginationEl.style.display = 'none';
        return;
    }

    deliveryTbody.innerHTML = pageItems.map(row => {
        let statusClass = 'cs-badge-onboard';
        const s = (row.status || '').toLowerCase();
        if (s === 'hold') statusClass = 'cs-badge-hold';
        else if (s === 'delivered') statusClass = 'cs-badge-delivered';
        else if (s === 'ontrack') statusClass = 'cs-badge-ontrack';

        return `
            <tr>
                <td>
                    <strong>${row.product_name}</strong>
                    <div style="font-size:11px;color:var(--cs-muted);margin-top:2px;">${row.company_name}</div>
                </td>
                <td>${row.delivery_date}</td>
                <td>
                    ${row.allocated_person}
                    <div style="font-size:11px;color:var(--cs-muted);margin-top:2px;">${row.allocated_department}</div>
                </td>
                <td><span class="cs-badge ${statusClass}">${row.status}</span></td>
                <td style="text-align:right;font-weight:600;">${fmt(row.total_value)}</td>
                <td style="text-align:right;color:var(--cs-emerald);font-weight:600;">${fmt(row.received_amount)}</td>
                <td style="text-align:right;color:var(--cs-rose);font-weight:600;">${fmt(row.pending_amount)}</td>
                <td style="text-align:center;"><a href="/projects-details/${row.id}" style="color:var(--cs-orange);text-decoration:none;font-weight:700;">Open</a></td>
            </tr>
        `;
    }).join('');

    if (paginationEl) {
        if (totalPages <= 1 && total <= deliveryPageSize) {
            paginationEl.style.display = total > 0 ? 'flex' : 'none';
            paginationEl.innerHTML = `
                <div class="app-pagination__info">
                    Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${total}</strong> projects
                </div>
                <div class="app-pagination__links">
                    <span class="app-pagination__link is-active">1</span>
                </div>
            `;
            return;
        }

        paginationEl.style.display = 'flex';
        
        let linksHtml = '';
        // Prev button
        if (deliveryCurrentPage > 1) {
            linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeDeliveryPage(${deliveryCurrentPage - 1})">Prev</a>`;
        } else {
            linksHtml += `<span class="app-pagination__link is-disabled">Prev</span>`;
        }

        // Page numbers
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= deliveryCurrentPage - 1 && p <= deliveryCurrentPage + 1)) {
                if (p === deliveryCurrentPage) {
                    linksHtml += `<span class="app-pagination__link is-active">${p}</span>`;
                } else {
                    linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeDeliveryPage(${p})">${p}</a>`;
                }
            } else if (p === deliveryCurrentPage - 2 || p === deliveryCurrentPage + 2) {
                linksHtml += `<span class="app-pagination__ellipsis">...</span>`;
            }
        }

        // Next button
        if (deliveryCurrentPage < totalPages) {
            linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeDeliveryPage(${deliveryCurrentPage + 1})">Next</a>`;
        } else {
            linksHtml += `<span class="app-pagination__link is-disabled">Next</span>`;
        }

        paginationEl.innerHTML = `
            <div class="app-pagination__info">
                Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${total}</strong> projects
            </div>
            <div class="app-pagination__links">
                ${linksHtml}
            </div>
        `;
    }
}

function changeDeliveryPage(page) {
    deliveryCurrentPage = page;
    if (dashboardDataRaw && dashboardDataRaw.delivery_projects) {
        renderDeliveryPlannedProjects(dashboardDataRaw.delivery_projects);
    }
}

function switchRenewalTab(tab) {
    activeRenewalTab = tab;
    
    // Toggle active tab buttons UI
    const btns = {
        cmr: document.querySelector("button[onclick=\"switchRenewalTab('cmr')\"]"),
        nmr: document.getElementById("btn_nmr"),
        lmr: document.getElementById("btn_lmr")
    };
    
    Object.keys(btns).forEach(k => {
        if (k === tab) {
            btns[k].className = 'btn-apply';
        } else {
            btns[k].className = 'btn-reset';
        }
    });

    const tb = document.getElementById('renewalDealsTableBody');
    let items = [];
    if (tab === 'cmr') items = dashboardDataRaw.cmr.items;
    else if (tab === 'nmr') items = dashboardDataRaw.cmr_plus.items;
    else if (tab === 'lmr') items = dashboardDataRaw.cmr_minus.items;

    if (items.length === 0) {
        tb.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--cs-muted);">No renewals in this category.</td></tr>`;
        return;
    }

    tb.innerHTML = items.map(row => {
        const pctPaid = row.value > 0 ? Math.round((row.paid / row.value) * 100) : 100;
        let badgeClass = 'cs-badge-pending';
        if (pctPaid === 100) badgeClass = 'cs-badge-paid';
        else if (pctPaid > 0) badgeClass = 'cs-badge-partial';

        return `
            <tr>
                <td><strong>${row.company_name}</strong></td>
                <td>${row.product_name}</td>
                <td>${row.renewal_date}</td>
                <td style="text-align:right;font-weight:700;">${fmt(row.value)}</td>
                <td style="text-align:right;color:var(--cs-emerald);">${fmt(row.paid)}</td>
                <td style="text-align:right;font-weight:700;color:var(--cs-rose);">
                    <span class="cs-badge ${badgeClass}">${fmt(row.pending)} (${pctPaid}%)</span>
                </td>
            </tr>
        `;
    }).join('');
}

function renderCollectionsChart(trendData) {
    if (collectionChartInst) {
        collectionChartInst.destroy();
    }

    const ctx = document.getElementById('collectionsChart').getContext('2d');
    
    // Sort trendData chronologically just in case
    trendData.sort((a,b) => new Date(a.date) - new Date(b.date));
    
    const labels = trendData.map(d => {
        const parts = d.date.split('-');
        return `${parts[2]}/${parts[1]}`;
    });
    const values = trendData.map(d => d.amount);

    collectionChartInst = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Payments Collected',
                data: values,
                borderColor: '#fe5f04',
                backgroundColor: 'rgba(254, 95, 4, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => '₹' + num(v)
                    }
                }
            }
        }
    });
}

function renderRenewalsChart(data) {
    if (renewalChartInst) {
        renewalChartInst.destroy();
    }

    const ctx = document.getElementById('renewalsChart').getContext('2d');
    
    renewalChartInst = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Last Month (CMR-1)', 'Current Month (CMR)', 'Next Month (CMR+1)'],
            datasets: [{
                label: 'Renewal Target Value',
                data: [data.cmr_minus.value, data.cmr.value, data.cmr_plus.value],
                backgroundColor: [
                    'rgba(29, 78, 216, 0.85)',
                    'rgba(254, 95, 4, 0.85)',
                    'rgba(5, 150, 105, 0.85)'
                ],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => '₹' + num(v)
                    }
                }
            }
        }
    });
}

function getCmrDates(type) {
    const today = new Date();
    const pad = n => String(n).padStart(2, '0');
    const toYmd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    if (type === 'cmr') {
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        return { from: toYmd(start), to: toYmd(end) };
    }
    if (type === 'cmr_plus') {
        const start = new Date(today.getFullYear(), today.getMonth() + 1, 1);
        const end = new Date(today.getFullYear(), today.getMonth() + 2, 0);
        return { from: toYmd(start), to: toYmd(end) };
    }
    if (type === 'cmr_minus') {
        const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const end = new Date(today.getFullYear(), today.getMonth(), 0);
        return { from: toYmd(start), to: toYmd(end) };
    }
    return { from: '', to: '' };
}

function navigateCard(target, extra = {}) {
    const filters = getFilterValues();
    const qSelect = document.getElementById('quick_date_select')?.value;
    const params = new URLSearchParams();

    if (target === 'payment_collection') {
        if (extra.quick_date === 'today') {
            const today = new Date();
            const pad = n => String(n).padStart(2, '0');
            const todayStr = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`;
            params.set('quick_date', 'today');
            params.set('date_from', todayStr);
            params.set('date_to', todayStr);
        } else {
            if (qSelect && qSelect !== 'custom') {
                params.set('quick_date', qSelect);
            }
            if (filters.from_date) {
                params.set('date_from', filters.from_date);
            }
            if (filters.to_date) {
                params.set('date_to', filters.to_date);
            }
        }
        if (filters.branch_id) params.set('branch_id', filters.branch_id);
        if (filters.user_id) {
            params.set('sales_executive_id', filters.user_id);
            params.set('user_id', filters.user_id);
        }
        window.location.href = '{{ route("reports.crm.payment-collection") }}?' + params.toString();
        return;
    }

    if (target === 'lead_products') {
        if (extra.is_renewal) {
            params.set('is_renewal', '1');
            params.set('product_status', 'converted');
            if (extra.renewal_period) {
                const dates = getCmrDates(extra.renewal_period);
                if (dates.from) params.set('date_from', dates.from);
                if (dates.to) params.set('date_to', dates.to);
                if (extra.renewal_period === 'cmr') {
                    params.set('quick_date', 'month');
                }
            }
        } else {
            if (extra.product_status) {
                params.set('product_status', extra.product_status);
            }
            if (qSelect && qSelect !== 'custom') {
                params.set('quick_date', qSelect);
            }
            if (filters.from_date) {
                params.set('date_from', filters.from_date);
            }
            if (filters.to_date) {
                params.set('date_to', filters.to_date);
            }
        }

        if (filters.branch_id) params.set('branch_id', filters.branch_id);
        if (filters.user_id) params.set('assigned_to', filters.user_id);
        if (filters.product_id) params.set('product_id', filters.product_id);
        if (filters.source) params.set('source', filters.source);

        window.location.href = '{{ route("leads.products.index") }}?' + params.toString();
        return;
    }

    if (target === 'campaigns') {
        params.set('view', 'campaign');
        if (extra.renewal_filter) params.set('renewal_filter', extra.renewal_filter);
        if (extra.status) params.set('status', extra.status);
        if (extra.period === 'cm') {
            const cmDates = getCmrDates('cmr');
            if (cmDates.from) params.set('start_date', cmDates.from);
            if (cmDates.to) params.set('end_date', cmDates.to);
        } else {
            if (filters.from_date) params.set('start_date', filters.from_date);
            if (filters.to_date) params.set('end_date', filters.to_date);
        }
        if (filters.branch_id) params.set('branch_id', filters.branch_id);
        if (filters.user_id) params.set('employee_id', filters.user_id);
        if (filters.source) params.set('source', filters.source);

        window.location.href = '{{ route("projects.campaigns.index") }}?' + params.toString();
        return;
    }
}
</script>
@endpush
