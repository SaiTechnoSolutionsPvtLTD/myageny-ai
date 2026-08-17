@extends('layouts.app')

@section('title', 'Admin Dashboard – myAgenci.ai')

@push('styles')
<style>
/* ============================================================
   ADMIN DASHBOARD STYLES
   ============================================================ */
:root {
    --orange:   #fe5f04;
    --purple:   #60308c;
    --green:    #469d89;
    --red:      #ff5a55;
    --blue:     #46479d;
    --border:   #e1dee3;
    --bg:       #fcfcfc;
    --bg2:      #f8f8f8;
    --text:     #121212;
    --muted:    #9e9e9e;
}

/* ── Layout ─────────────────────────────────────────────── */
.adm-wrap          { display:flex; flex-direction:column; flex-grow:1; overflow:hidden; }
.adm-header        { display:flex; justify-content:space-between; align-items:center;
                     padding:20px 32px; border-bottom:1px solid var(--border);
                     background:var(--bg); position:sticky; top:0; z-index:20; }
.adm-body          { flex-grow:1; overflow-y:auto; padding:28px 32px; background:#f4f4f6; }

/* ── Filter Bar ─────────────────────────────────────────── */
.filter-bar        { background:var(--bg); border:1px solid var(--border); border-radius:14px;
                     padding:20px 24px; margin-bottom:28px;
                     display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; }
.filter-group      { display:flex; flex-direction:column; gap:5px; flex:1; min-width:150px; }
.filter-group label{ font-size:11px; font-weight:600; color:var(--muted); text-transform:uppercase;
                     letter-spacing:.5px; }
.filter-group select,
.filter-group input { padding:8px 12px; border:1px solid var(--border); border-radius:10px;
                      font-size:13px; color:var(--text); background:var(--bg2); outline:none;
                      transition:border-color .2s; }
.filter-group select:focus,
.filter-group input:focus { border-color:var(--orange); }
.filter-actions    { display:flex; gap:10px; align-items:flex-end; }
.btn-apply, .btn-reset { padding:9px 20px; border-radius:10px; font-size:13px; font-weight:600;
                         cursor:pointer; border:none; transition:opacity .2s; }
.btn-apply         { background:var(--orange); color:#fff; }
.btn-apply:hover   { opacity:.88; }
.btn-reset         { background:var(--bg2); color:var(--muted); border:1px solid var(--border); }

/* ── Loading Overlay ────────────────────────────────────── */
#loadingOverlay    { display:none; position:fixed; inset:0; background:rgba(252,252,252,.6);
                     z-index:999; align-items:center; justify-content:center; }
.spinner           { width:44px; height:44px; border:4px solid var(--border);
                     border-top-color:var(--orange); border-radius:50%; animation:spin .8s linear infinite; }
@keyframes spin    { to { transform:rotate(360deg); } }

/* ── Summary Cards ──────────────────────────────────────── */
.cards-grid        { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
                     gap:16px; margin-bottom:28px; }
.summary-card      { position:relative; overflow:hidden; border:none; border-radius:16px;
                     padding:20px; display:flex; flex-direction:column; justify-content:space-between;
                     transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; min-height:140px; }
.summary-card:hover { transform:translateY(-5px); box-shadow:0 20px 25px -5px rgba(15,23,42,.15),0 10px 10px -5px rgba(15,23,42,.08); }
.summary-card .sc-label  { font-size:11px; font-weight:800; color:rgba(255,255,255,0.95); text-transform:uppercase; letter-spacing:.06em; margin-top:4px; }
.summary-card .sc-value  { font-size:26px; font-weight:900; color:#fff; line-height:1.2; margin-top:8px; }
.summary-card .sc-sub    { font-size:12px; color:rgba(255,255,255,0.85); font-weight:500; margin-top:8px; }
.sc-icon           { width:38px; height:38px; border-radius:12px; display:flex;
                     align-items:center; justify-content:center; font-size:18px; margin-bottom:12px;
                     background:rgba(255,255,255,0.2) !important; backdrop-filter:blur(4px); }
.sc-orange  { background:linear-gradient(135deg, #fe5f04 0%, #ff8c42 100%) !important; }
.sc-green   { background:linear-gradient(135deg, #047857 0%, #10b981 100%) !important; }
.sc-red     { background:linear-gradient(135deg, #b91c1c 0%, #ef4444 100%) !important; }
.sc-blue    { background:linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%) !important; }
.sc-purple  { background:linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%) !important; }
.sc-teal    { background:linear-gradient(135deg, #0f766e 0%, #0d9488 100%) !important; }
.sc-warning { background:linear-gradient(135deg, #b45309 0%, #f59e0b 100%) !important; }
.sc-pink    { background:linear-gradient(135deg, #be123c 0%, #f43f5e 100%) !important; }
.divider-card { grid-column:1/-1; height:1px; background:var(--border); margin:12px 0; }

/* Skeleton loaders */
.da-skel { background:linear-gradient(90deg,#f3f0f6 25%,#e9e5ee 50%,#f3f0f6 75%); background-size:200% 100%; animation:shimmer 1.4s infinite; border-radius:6px; }
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
.da-skel-card { background:#ffffff; border:1px solid var(--border); border-radius:16px; padding:20px; min-height:140px; display:flex; flex-direction:column; justify-content:space-between; }

/* ── Charts Grid ────────────────────────────────────────── */
.charts-grid       { display:grid; grid-template-columns:repeat(2,1fr); gap:20px; margin-bottom:28px; }
.chart-card        { background:var(--bg); border:1px solid var(--border); border-radius:14px;
                     padding:22px; }
.chart-card.full   { grid-column:1/-1; }
.chart-card h4     { font-size:14px; font-weight:700; color:var(--text); margin-bottom:16px; }

/* ── Top User Card ──────────────────────────────────────── */
.top-user-grid     { display:grid; grid-template-columns:320px 1fr; gap:20px; margin-bottom:28px; }
.top-user-card     { background:linear-gradient(145deg,#f0e8f8,#fde9f8);
                     border:1px solid #d9b6f0; border-radius:14px;
                     padding:28px; display:flex; flex-direction:column; align-items:center; text-align:center; }
.top-user-photo    { width:90px; height:90px; border-radius:50%; object-fit:cover;
                     border:3px solid var(--purple); margin-bottom:14px; }
.top-user-avatar   { width:90px; height:90px; border-radius:50%; background:var(--orange);
                     color:#fff; font-size:32px; font-weight:700; display:flex;
                     align-items:center; justify-content:center; margin-bottom:14px; }
.top-user-badge    { display:inline-block; background:#fff; border-radius:20px;
                     padding:4px 14px; font-size:11px; font-weight:600; color:var(--purple);
                     border:1px solid var(--purple); margin-bottom:10px; }
.top-user-name     { font-size:20px; font-weight:700; color:#a30000; margin-bottom:4px; }
.top-user-role     { font-size:12px; color:#555; margin-bottom:4px; }
.top-user-branch   { font-size:12px; color:var(--muted); }
.top-user-amount   { font-size:22px; font-weight:700; color:var(--green); margin-top:10px; }
.top-user-amount small { font-size:12px; font-weight:400; color:var(--muted); display:block; }

/* ── Pipeline Funnel Table ──────────────────────────────── */
.pipeline-table    { width:100%; border-collapse:collapse; font-size:13px; }
.pipeline-table th { background:var(--bg2); padding:10px 14px; text-align:left;
                     font-size:11px; color:var(--muted); font-weight:600;
                     text-transform:uppercase; letter-spacing:.4px; }
.pipeline-table td { padding:11px 14px; border-bottom:1px solid var(--border); color:var(--text); }
.pipeline-table tr:hover td { background:#f9f3ff; }
.funnel-bar-wrap   { display:flex; align-items:center; gap:8px; }
.funnel-bar        { height:8px; border-radius:4px; transition:width .4s; }
.bar-received      { background:var(--green); }
.bar-pending       { background:#ffcbad; }

/* ── Responsive ─────────────────────────────────────────── */
@media(max-width:900px) {
    .charts-grid      { grid-template-columns:1fr; }
    .top-user-grid    { grid-template-columns:1fr; }
    .cards-grid       { grid-template-columns:repeat(2,1fr); }
}
@media(max-width:600px) {
    .adm-body         { padding:16px; }
    .filter-bar       { padding:16px; }
    .cards-grid       { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
{{-- Loading overlay --}}
<div id="loadingOverlay" style="display:none;">
    <div class="spinner"></div>
</div>

<div class="adm-wrap">

    {{-- ── Header ──────────────────────────────────────────── --}}
    <header class="adm-header">
        <div class="breadcrumbs">
            <span class="crumb-item">Overview</span>
            {{--  <img src="{{ asset('images/48_651.svg') }}" alt="/" style="width:16px;margin:0 4px;">  --}}
            <span class="crumb-item active" style="color:#121212;font-weight:600;">Admin Dashboard</span>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="/dashboard">
            <div class="ai-insight-btn" style="display:flex;align-items:center;gap:6px;padding:6px 14px;
                border-radius:20px;background:#fff;border:1.5px solid #fa6203;cursor:pointer;">
                <span style="color:#fa6203;font-size:14px;font-weight:600;">Goto Lead Dashboard</span>
            </div>
            </a>
        </div>
    </header>

    <div class="adm-body">

        {{-- ── Filter Bar ───────────────────────────────────── --}}
        <div class="filter-bar" id="filterBar">

            <div class="filter-group">
                <label>Product</label>
                <select id="f_product">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->product_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label>Branch</label>
                <select id="f_branch">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label>User / Agent</label>
                <select id="f_user">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label>Source</label>
                <select id="f_source">
                    <option value="">All Sources</option>
                    @foreach($sources as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label>Status</label>
                <select id="f_status">
                    <option value="">All Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                    @endforeach
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
                <button class="btn-apply" id="applyBtn">Apply</button>
                <button class="btn-reset" id="resetBtn">Reset</button>
            </div>
        </div>

        {{-- ── Product Summary Cards ────────────────────────── --}}
        <div class="cards-grid" id="cardsGrid">
            @for($i = 0; $i < 8; $i++)
            <div class="da-skel-card">
                <div class="da-skel" style="height:38px;width:38px;border-radius:12px;margin-bottom:12px"></div>
                <div class="da-skel" style="height:26px;width:50%;margin-bottom:8px"></div>
                <div class="da-skel" style="height:12px;width:70%;margin-bottom:8px"></div>
                <div class="da-skel" style="height:12px;width:40%"></div>
            </div>
            @if($i == 3)
            <div class="divider-card"></div>
            @endif
            @endfor
        </div>

        {{-- ── Top User + Pipeline ──────────────────────────── --}}
        <div class="top-user-grid">
            <div class="top-user-card" id="topUserCard">
                <div class="top-user-avatar" id="topUserAvatar">—</div>
                <div class="top-user-badge" id="topUserBranch">Branch</div>
                <div class="top-user-name" id="topUserName">Loading…</div>
                <div class="top-user-role" id="topUserRole">—</div>
                <div class="top-user-amount" id="topUserAmount">
                    ₹0
                    <small>Total Collected</small>
                </div>
                <div style="font-size:12px;color:#888;margin-top:6px;">🏅 Top Performer of the Period</div>
            </div>

            <div class="chart-card" style="padding:22px;">
                <h4>🔺 Pipeline Funnel</h4>
                <div id="pipelineTableWrap">
                    <table class="pipeline-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Total Cost</th>
                                <th>Received</th>
                                <th>Pending</th>
                                <th style="min-width:180px">Progress</th>
                            </tr>
                        </thead>
                        <tbody id="pipelineTbody">
                            <tr><td colspan="5" style="text-align:center;color:#ccc;padding:30px;">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Charts Grid ──────────────────────────────────── --}}
        <div class="charts-grid">

            <div class="chart-card full">
                <h4>📈 Last 6 Months Revenue Trend</h4>
                <canvas id="trendChart" height="80"></canvas>
            </div>

            <div class="chart-card">
                <h4>📦 Product-wise Sales (6 Months)</h4>
                <canvas id="productSalesChart" height="220"></canvas>
            </div>

            <div class="chart-card">
                <h4>👤 User-wise Collection (6 Months)</h4>
                <canvas id="userCollChart" height="220"></canvas>
            </div>

            <div class="chart-card full">
                <h4>🏢 Branch-wise Payments (6 Months)</h4>
                <canvas id="branchChart" height="100"></canvas>
            </div>

        </div>

    </div>{{-- .adm-body --}}
</div>{{-- .adm-wrap --}}
@endsection


@push('scripts')
{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
/* ============================================================
   ADMIN DASHBOARD – AJAX + CHART.JS SCRIPT
   ============================================================ */
'use strict';

window._apiToken = @json($apiToken ?? '');
const defaultFromDate = @json(now()->startOfMonth()->toDateString());
const defaultToDate = @json(now()->endOfMonth()->toDateString());

// ── Utilities ──────────────────────────────────────────────
const fmt = n => '₹' + Number(n ?? 0).toLocaleString('en-IN', {maximumFractionDigits:2});
const num = n => Number(n ?? 0).toLocaleString('en-IN');
const pct = (a, b) => b > 0 ? Math.min(100, (a / b) * 100).toFixed(1) : 0;

// ── Chart instances (store to destroy before re-render) ─────
let chartInstances = {};

function destroyChart(id) {
    if (chartInstances[id]) {
        chartInstances[id].destroy();
        delete chartInstances[id];
    }
}

// ── Read current filters ────────────────────────────────────
function getFilters() {
    return {
        product_id : document.getElementById('f_product').value,
        branch_id  : document.getElementById('f_branch').value,
        user_id    : document.getElementById('f_user').value,
        source     : document.getElementById('f_source').value,
        status     : document.getElementById('f_status').value,
        from_date  : document.getElementById('f_from').value,
        to_date    : document.getElementById('f_to').value,
    };
}

// ── Build query string ──────────────────────────────────────
function toQueryString(obj) {
    return Object.entries(obj)
        .filter(([, v]) => v)
        .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
        .join('&');
}

// ── Fetch dashboard data ────────────────────────────────────
async function loadDashboard() {

    document.getElementById('loadingOverlay').style.display = 'flex';

    const qs  = toQueryString(getFilters());
    const url = `/api/product-dashboard-data${qs ? '?' + qs : ''}`;
    console.log(url);
    try {
        const res  = await fetch(url, {
            headers: {
                'Accept'       : 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': `Bearer ${window._apiToken || ''}`,
            },
            credentials: 'same-origin',
        });

        if (res.status === 401) {
            window.location.href = "{{ route('login') }}";
            throw new Error('Unauthenticated. Redirecting to login page...');
        }

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const json = await res.json();

        if (!json.status) throw new Error(json.message || 'API error');

        const d = json.data;
        renderCards(d.product_summary, d.value_summary);
        renderTopUser(d.top_user);
        renderPipeline(d.pipeline_funnel);
        renderTrendChart(d.six_month_trend);
        renderProductSales(d.product_sales);
        renderUserCollections(d.user_collections);
        renderBranchPayments(d.branch_payments);

    } catch (err) {
        console.error('Dashboard error:', err);
        alert('Failed to load dashboard. Check console for details.');
    } finally {
        document.getElementById('loadingOverlay').style.display = 'none';
    }
}

// ── 1. Summary Cards ────────────────────────────────────────
function renderCards(ps, vs) {
    const grid = document.getElementById('cardsGrid');
    grid.innerHTML = `
        <!-- Product summary -->
        <div class="summary-card sc-orange">
            <div class="sc-icon">📦</div>
            <div class="sc-value">${num(ps.total_products)}</div>
            <div class="sc-label">Total Products</div>
        </div>
        <div class="summary-card sc-green">
            <div class="sc-icon">✅</div>
            <div class="sc-value">${num(ps.converted_products)}</div>
            <div class="sc-label">Converted</div>
            <div class="sc-sub">${pct(ps.converted_products, ps.total_products)}% conversion</div>
        </div>
        <div class="summary-card sc-red">
            <div class="sc-icon">🔥</div>
            <div class="sc-value">${num(ps.hot_products)}</div>
            <div class="sc-label">Hot Leads</div>
        </div>
        <div class="summary-card sc-blue">
            <div class="sc-icon">❄️</div>
            <div class="sc-value">${num(ps.cold_products)}</div>
            <div class="sc-label">Cold Leads</div>
        </div>

        <div class="divider-card"></div>

        <!-- Value summary -->
        <div class="summary-card sc-purple">
            <div class="sc-icon">💰</div>
            <div class="sc-value">${fmt(vs.total_products_value)}</div>
            <div class="sc-label">Total Value</div>
        </div>
        <div class="summary-card sc-teal">
            <div class="sc-icon">🏆</div>
            <div class="sc-value">${fmt(vs.converted_products_value)}</div>
            <div class="sc-label">Converted Value</div>
        </div>
        <div class="summary-card sc-warning">
            <div class="sc-icon">💵</div>
            <div class="sc-value">${fmt(vs.received_value)}</div>
            <div class="sc-label">Received</div>
        </div>
        <div class="summary-card sc-pink">
            <div class="sc-icon">⏳</div>
            <div class="sc-value">${fmt(vs.pending_value)}</div>
            <div class="sc-label">Pending</div>
        </div>
    `;
}

// ── 2. Top User ─────────────────────────────────────────────
function renderTopUser(u) {
    if (!u || !u.user_name) {
        document.getElementById('topUserName').textContent = 'No data';
        return;
    }
    const avatarEl = document.getElementById('topUserAvatar');
    if (u.user_photo) {
        avatarEl.outerHTML = `<img src="${u.user_photo}" class="top-user-photo" id="topUserAvatar" alt="${u.user_name}">`;
    } else {
        avatarEl.textContent = u.user_name.charAt(0).toUpperCase();
    }
    document.getElementById('topUserName').textContent   = u.user_name;
    document.getElementById('topUserRole').textContent   = u.designation || '—';
    document.getElementById('topUserBranch').textContent = u.branch_name || '—';
    document.getElementById('topUserAmount').innerHTML   =
        `${fmt(u.total_collected_amount)}<small>Total Collected</small>`;
}

// ── 3. Pipeline Funnel Table ─────────────────────────────────
function renderPipeline(funnel) {
    const tbody = document.getElementById('pipelineTbody');
    if (!funnel || funnel.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#ccc;padding:30px;">No data</td></tr>';
        return;
    }
    tbody.innerHTML = funnel.map(row => {
        const recPct = pct(row.received_amount, row.total_cost);
        const penPct = pct(row.pending_amount, row.total_cost);
        return `
            <tr>
                <td><strong>${row.product_name}</strong></td>
                <td>${fmt(row.total_cost)}</td>
                <td style="color:#469d89;">${fmt(row.received_amount)}</td>
                <td style="color:#ff5a55;">${fmt(row.pending_amount)}</td>
                <td>
                    <div class="funnel-bar-wrap">
                        <div class="funnel-bar bar-received" style="width:${recPct}%"></div>
                        <div class="funnel-bar bar-pending"  style="width:${penPct}%"></div>
                        <span style="font-size:11px;color:#999;">${recPct}%</span>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// ── 4. 6-Month Trend Line Chart ─────────────────────────────
function renderTrendChart(trend) {
    destroyChart('trendChart');
    const ctx    = document.getElementById('trendChart').getContext('2d');
    const labels = trend.map(t => t.month);
    const values = trend.map(t => t.total_value);

    chartInstances['trendChart'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label      : 'Revenue (₹)',
                data       : values,
                borderColor: '#60308c',
                backgroundColor: 'rgba(96,48,140,.08)',
                borderWidth: 2,
                pointBackgroundColor: '#fe5f04',
                pointRadius: 5,
                fill       : true,
                tension    : 0.4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => ' ₹' + ctx.parsed.y.toLocaleString('en-IN') }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '₹' + v.toLocaleString('en-IN') }
                }
            }
        }
    });
}

// ── 5 & 6. Product Sales + User Collection Bar Charts ────────
function renderBarChart(canvasId, labels, values, color) {
    destroyChart(canvasId);
    const ctx = document.getElementById(canvasId).getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label          : 'Amount (₹)',
                data           : values,
                backgroundColor: color,
                borderRadius   : 6,
                borderSkipped  : false,
            }]
        },
        options: {
            responsive: true,
            indexAxis  : 'y',
            plugins    : { legend: { display: false } },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { callback: v => '₹' + v.toLocaleString('en-IN') }
                }
            }
        }
    });
}

function renderProductSales(data) {
    const labels = data.map(d => d.product_name);
    const values = data.map(d => d.total_sales);
    renderBarChart('productSalesChart', labels, values, 'rgba(96,48,140,.75)');
}

function renderUserCollections(data) {
    const labels = data.map(d => d.user_name);
    const values = data.map(d => d.total_collection);
    renderBarChart('userCollChart', labels, values, 'rgba(254,95,4,.75)');
}

// ── 7. Branch-wise Payments Grouped Bar ─────────────────────
function renderBranchPayments(data) {
    destroyChart('branchChart');
    const ctx = document.getElementById('branchChart').getContext('2d');

    // Group by branch, then by month
    const months   = [...new Set(data.map(d => d.month))].sort();
    const branches = [...new Set(data.map(d => d.branch_name))];

    const palette = [
        'rgba(96,48,140,.8)', 'rgba(254,95,4,.8)',  'rgba(70,157,137,.8)',
        'rgba(70,71,157,.8)', 'rgba(255,90,85,.8)', 'rgba(201,148,17,.8)',
    ];

    const datasets = branches.map((branch, i) => ({
        label          : branch,
        data           : months.map(m => {
            const row = data.find(d => d.branch_name === branch && d.month === m);
            return row ? row.total_payment : 0;
        }),
        backgroundColor: palette[i % palette.length],
        borderRadius   : 4,
    }));

    chartInstances['branchChart'] = new Chart(ctx, {
        type: 'bar',
        data: { labels: months, datasets },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: c => ` ${c.dataset.label}: ₹${c.parsed.y.toLocaleString('en-IN')}`
                    }
                }
            },
            scales: {
                x: { stacked: false },
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '₹' + v.toLocaleString('en-IN') }
                }
            }
        }
    });
}

// ── Event Listeners ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('f_from').value = defaultFromDate;
    document.getElementById('f_to').value = defaultToDate;

    const btn = document.getElementById('applyBtn');
    console.log(btn); // check again

    btn?.addEventListener('click', function () {
        console.log('clicked');
        loadDashboard();
    });

});

document.getElementById('resetBtn')?.addEventListener('click', () => {
    ['f_product','f_branch','f_user','f_source','f_status'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('f_from').value = defaultFromDate;
    document.getElementById('f_to').value   = defaultToDate;
    loadDashboard();
});

// ── Initial Load ─────────────────────────────────────────────


document.addEventListener('DOMContentLoaded', loadDashboard);
</script>
@endpush
