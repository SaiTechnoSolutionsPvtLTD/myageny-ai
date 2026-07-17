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

/* ── Filter Bar ─────────────────────────────────────────── */
.filter-bar        { background:var(--cs-card-bg); border:1px solid var(--cs-border); border-radius:14px;
                     padding:20px 24px; display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); }
.filter-group      { display:flex; flex-direction:column; gap:5px; flex:1; min-width:160px; }
.filter-group label{ font-size:11px; font-weight:700; color:var(--cs-muted); text-transform:uppercase;
                     letter-spacing:.5px; }
.filter-group select,
.filter-group input { padding:9px 12px; border:1px solid var(--cs-border); border-radius:10px;
                       font-size:13px; color:var(--cs-text); background:#fafafa; outline:none;
                       transition:border-color .2s, background-color .2s; }
.filter-group select:focus,
.filter-group input:focus { border-color:var(--cs-orange); background:#fff; }
.filter-actions    { display:flex; gap:10px; align-items:flex-end; }
.btn-apply, .btn-reset { padding:10px 20px; border-radius:10px; font-size:13px; font-weight:700;
                          cursor:pointer; border:none; transition:opacity .2s, transform .1s; }
.btn-apply         { background:linear-gradient(135deg, var(--cs-orange) 0%, #ff8c42 100%); color:#fff; }
.btn-apply:hover   { opacity:.9; transform:translateY(-1px); }
.btn-reset         { background:#f3f4f6; color:var(--cs-muted); border:1px solid var(--cs-border); }
.btn-reset:hover   { background:#e5e7eb; }

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
                     box-shadow:0 10px 15px -3px rgba(0,0,0,0.05),0 4px 6px -2px rgba(0,0,0,0.02); }
.summary-card:hover { transform:translateY(-5px); box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04); }
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
.divider-card { grid-column:1/-1; height:1px; background:var(--cs-border); margin:12px 0; }

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
        {{-- Filter Bar --}}
        <div class="filter-bar">
            <div class="filter-group">
                <label>Support Agent</label>
                <select id="f_user">
                    <option value="">All Support Agents</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Branch</label>
                <select id="f_branch">
                    <option value="">All Branches</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Product</label>
                <select id="f_product">
                    <option value="">All Products</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Source</label>
                <select id="f_source">
                    <option value="">All Sources</option>
                </select>
            </div>
            <div class="filter-group">
                <label>From Date</label>
                <input type="date" id="f_from">
            </div>
            <div class="filter-group">
                <label>To Date</label>
                <input type="date" id="f_to">
            </div>
            <div class="filter-actions">
                <button class="btn-apply" onclick="loadDashboard()">Apply</button>
                <button class="btn-reset" onclick="resetFilters()">Reset</button>
            </div>
        </div>

        {{-- Summary Cards Grid --}}
        <div class="cards-grid" id="cardsGrid">
            @for($i = 0; $i < 6; $i++)
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
                <div class="table-wrap">
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

document.addEventListener('DOMContentLoaded', function() {
    // Set default dates
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
    document.getElementById('f_from').value = firstDay;
    document.getElementById('f_to').value = lastDay;

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
    
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
    document.getElementById('f_from').value = firstDay;
    document.getElementById('f_to').value = lastDay;
    
    loadDashboard();
}

async function loadDashboard() {
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
    cardsGrid.innerHTML = `
        <div class="summary-card sc-orange">
            <div class="sc-icon">📅</div>
            <div class="sc-value">${fmt(data.cmr.value)}</div>
            <div class="sc-label">Current Month Renewal (CMR)</div>
            <div class="sc-sub">${data.cmr.count} accounts renewing</div>
        </div>
        <div class="summary-card sc-emerald">
            <div class="sc-icon">⏭️</div>
            <div class="sc-value">${fmt(data.cmr_plus.value)}</div>
            <div class="sc-label">Next Month Renewal (CMR+1)</div>
            <div class="sc-sub">${data.cmr_plus.count} accounts renewing</div>
        </div>
        <div class="summary-card sc-blue">
            <div class="sc-icon">⏮️</div>
            <div class="sc-value">${fmt(data.cmr_minus.value)}</div>
            <div class="sc-label">Last Month Renewal (CMR-1)</div>
            <div class="sc-sub">${data.cmr_minus.count} accounts renewing</div>
        </div>
        <div class="summary-card sc-purple">
            <div class="sc-icon">💎</div>
            <div class="sc-value">${fmt(data.upsells.value)}</div>
            <div class="sc-label">Upsell Converted (Period)</div>
            <div class="sc-sub">${data.upsells.count} deals closed</div>
        </div>
        <div class="summary-card sc-amber">
            <div class="sc-icon">💵</div>
            <div class="sc-value">${fmt(data.today_payments)}</div>
            <div class="sc-label">Today Received Payments</div>
            <div class="sc-sub">Collections received today</div>
        </div>
        <div class="summary-card sc-teal">
            <div class="sc-icon">📊</div>
            <div class="sc-value">${fmt(data.month_payments)}</div>
            <div class="sc-label">This Month Collections</div>
            <div class="sc-sub">Total collected this month</div>
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

    // 4.1 Delivery Planned Projects Table
    const deliveryTitleEl = document.getElementById('deliverySectionTitle');
    const deliveryBadgeEl = document.getElementById('deliverySectionBadge');
    if (deliveryTitleEl) deliveryTitleEl.textContent = data.delivery_title || 'Delivery Planned Projects';
    if (deliveryBadgeEl) deliveryBadgeEl.textContent = `${data.delivery_projects ? data.delivery_projects.length : 0} ${data.delivery_badge || 'Planned'}`;

    const deliveryTbody = document.getElementById('deliveryPlannedTableBody');
    if (!data.delivery_projects || data.delivery_projects.length === 0) {
        deliveryTbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--cs-muted);">No delivery scheduled in this period.</td></tr>`;
    } else {
        deliveryTbody.innerHTML = data.delivery_projects.map(row => {
            let statusClass = 'cs-badge-onboard';
            const s = row.status.toLowerCase();
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
    }

    // 5. Renewal Deals Table tab rendering
    switchRenewalTab(activeRenewalTab);

    // 6. Chart: Daily Collections Trend
    renderCollectionsChart(data.daily_trend);

    // 7. Chart: Renewals Month Comparison
    renderRenewalsChart(data);
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
</script>
@endpush
