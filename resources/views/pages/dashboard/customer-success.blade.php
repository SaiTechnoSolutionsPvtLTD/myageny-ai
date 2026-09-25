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

/* ── Renewal Details Modal ────────────────────────────── */
.rn-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    animation: rnFadeIn 0.2s ease-out;
}
@keyframes rnFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.rn-modal-card {
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 1280px;
    max-height: 90vh;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
    overflow: hidden;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    animation: rnZoomIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes rnZoomIn {
    from { opacity: 0; transform: scale(0.96) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.rn-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px 24px 16px;
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    gap: 16px;
}
.rn-header-left {
    display: flex;
    flex-direction: column;
    gap: 12px;
    flex: 1;
}
.rn-title-row {
    display: flex;
    align-items: center;
    gap: 12px;
}
.rn-period-icon {
    font-size: 26px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.rn-modal-title {
    margin: 0;
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.rn-modal-subtitle {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}
.rn-header-stats {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}
.rn-stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
}
.rn-stat-chip strong {
    font-weight: 800;
    font-size: 13px;
}
.rn-stat-chip.rn-stat-total strong { color: #0f172a; }
.rn-stat-chip.rn-stat-value strong { color: #fe5f04; }
.rn-stat-chip.rn-stat-collected strong { color: #059669; }
.rn-stat-chip.rn-stat-pending strong { color: #dc2626; }
.rn-modal-close {
    background: #f1f5f9;
    border: none;
    font-size: 22px;
    line-height: 1;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    color: #64748b;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    flex-shrink: 0;
}
.rn-modal-close:hover {
    background: #fee2e2;
    color: #dc2626;
    transform: rotate(90deg);
}
.rn-modal-toolbar {
    padding: 12px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.rn-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 480px;
}
.rn-search-box .rn-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    color: #94a3b8;
    pointer-events: none;
}
.rn-search-input {
    width: 100%;
    padding: 8px 32px 8px 34px;
    font-size: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    color: #0f172a;
    outline: none;
    transition: all 0.15s;
}
.rn-search-input:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.rn-search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 16px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.rn-search-clear:hover { color: #475569; }
.rn-filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.rn-status-select {
    padding: 7px 12px;
    border-radius: 9px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 12.5px;
    color: #334155;
    font-weight: 600;
    outline: none;
    cursor: pointer;
}
.rn-status-select:focus {
    border-color: #fe5f04;
}
.rn-export-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 9px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s;
}
.rn-export-btn:hover {
    background: #059669;
    color: #ffffff;
    border-color: #059669;
}
.rn-modal-body {
    flex: 1 1 auto;
    overflow-y: auto;
    background: #ffffff;
    min-height: 250px;
    max-height: calc(90vh - 210px);
}
.rn-table-wrap {
    overflow-x: auto;
    width: 100%;
}
.rn-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.rn-table thead th {
    position: sticky;
    top: 0;
    background: #f8fafc;
    z-index: 10;
    border-bottom: 2px solid #e2e8f0;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    padding: 10px 14px;
}
.rn-table tbody td {
    padding: 12px 14px;
    font-size: 12.5px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.rn-table tbody tr:hover {
    background: #fefaf8;
}
.rn-badge-future {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.rn-badge-today {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}
.rn-badge-overdue {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.rn-contact-phone {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    font-size: 12px;
}
.rn-contact-phone:hover {
    text-decoration: underline;
    color: #1d4ed8;
}
.rn-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #334155;
    transition: all 0.15s;
    white-space: nowrap;
}
.rn-action-btn:hover {
    background: #fe5f04;
    color: #ffffff;
    border-color: #fe5f04;
}
.rn-action-lead {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}
.rn-action-lead:hover {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}
.rn-modal-footer {
    padding: 12px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.rn-footer-info {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
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
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('cst.day-closing.index') }}" class="cs-tab-btn active" style="text-decoration:none; padding:8px 16px; font-weight:800; font-size:12.5px; display:inline-flex; align-items:center; gap:8px; border-radius:10px; background:linear-gradient(135deg, #fe5f04, #ff7c30); color:#fff; box-shadow:0 3px 12px rgba(254,95,4,0.3); transition:all 0.2s;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <polyline points="9 16 12 19 16 14"></polyline>
                </svg>
                <span>Day Closing</span>
            </a>
            <div style="font-size:12px;color:var(--cs-muted);font-weight:500;">
                🏢 Support &amp; Success Team Scope Only
            </div>
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
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h4>💎 Converted Upsell Deals (Non-Recurring Contracts)</h4>
                    <button type="button" class="lpd-btn lpd-btn-ghost" style="padding:5px 11px;font-size:11px;" onclick="openUpsellModal()" title="View all upsell deals in popup modal">
                        <span>🔍 View All in Modal</span>
                    </button>
                </div>
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
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button class="btn-apply" style="padding:6px 12px;font-size:11px;" onclick="switchRenewalTab('cmr')">Current Month</button>
                        <button class="btn-reset" style="padding:6px 12px;font-size:11px;" id="btn_nmr" onclick="switchRenewalTab('nmr')">Next Month</button>
                        <button class="btn-reset" style="padding:6px 12px;font-size:11px;" id="btn_lmr" onclick="switchRenewalTab('lmr')">Last Month</button>
                        <button type="button" class="lpd-btn lpd-btn-ghost" style="padding:5px 11px;font-size:11px;margin-left:6px;" onclick="openRenewalModal(activeRenewalTab)" title="View all renewals in popup modal">
                            <span>🔍 View in Modal</span>
                        </button>
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

            {{-- Pending Welcome Call Updates (All Departments) --}}
            <div class="dashboard-panel dashboard-grid-full" id="pendingWelcomeCallsPanel">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                    <div>
                        <h4 style="margin:0;">📞 Pending Welcome Call Updates (All Departments)</h4>
                        <div style="font-size:12px;color:var(--cs-muted);margin-top:2px;">Approved production projects across all departments awaiting Welcome Call Update.</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="text" id="pendingWcSearch" class="lpd-input" placeholder="Search company, client, product..." style="padding:6px 12px;font-size:12px;width:220px;" oninput="onPendingWcSearch(this.value)">
                        <span id="pendingWcCountBadge" class="cs-badge cs-badge-pending" style="font-size:12px;padding:4px 10px;">0 Projects</span>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Account / Client</th>
                                <th>Product</th>
                                <th>Department</th>
                                <th style="text-align:right;">Project Value</th>
                                <th style="text-align:right;">Collected</th>
                                <th style="text-align:right;">Pending</th>
                                <th style="text-align:center;">Approved Date</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="pendingWcTableBody">
                            <tr><td colspan="8" style="text-align:center;color:var(--cs-muted);">Loading pending welcome calls...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="pendingWcPagination" class="app-pagination" style="display:none; border-top:1px solid var(--cs-border); padding:12px 16px; border-radius:0 0 12px 12px; margin-top:4px;"></div>
            </div>

            {{-- SMM Sheet Expiry Details (Current Month, Last Month, Next Month) --}}
            <div class="dashboard-panel dashboard-grid-full" id="smmSheetExpiryPanel">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                    <div>
                        <h4 style="margin:0;">📱 SMM Sheet Expiry Details</h4>
                        <div style="font-size:12px;color:var(--cs-muted);margin-top:2px;">Track Social Media Marketing (SMM) contracts expiring in current month, last month, and next month.</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <div style="display:flex;gap:6px;">
                            <button type="button" class="cs-tab-btn active" id="btn_smm_current_month" onclick="switchSmmTab('current_month')">
                                <span>📅 Current Month Expire</span> (<span id="smmCountCurrentMonth">0</span>)
                            </button>
                            <button type="button" class="cs-tab-btn" id="btn_smm_last_month" onclick="switchSmmTab('last_month')">
                                <span>⏮️ Last Month Expired</span> (<span id="smmCountLastMonth">0</span>)
                            </button>
                            <button type="button" class="cs-tab-btn" id="btn_smm_next_month" onclick="switchSmmTab('next_month')">
                                <span>⏭️ Next Month Expire</span> (<span id="smmCountNextMonth">0</span>)
                            </button>
                        </div>
                        <input type="text" id="smmSearch" class="lpd-input" placeholder="Search account, product..." style="padding:6px 12px;font-size:12px;width:180px;" oninput="onSmmSearch(this.value)">
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="cs-table">
                        <thead>
                            <tr>
                                <th>Account / Client</th>
                                <th>Product / Department</th>
                                <th style="text-align:center;">Campaign Period</th>
                                <th style="text-align:center;">Posters (Done/Committed)</th>
                                <th style="text-align:center;">Videos (Done/Committed)</th>
                                <th style="text-align:right;">Contract Value</th>
                                <th style="text-align:right;">Collected</th>
                                <th style="text-align:right;">Pending</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="smmTableBody">
                            <tr><td colspan="10" style="text-align:center;color:var(--cs-muted);padding:20px;">Loading SMM Sheet expiry data...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="smmPagination" class="app-pagination" style="display:none; border-top:1px solid var(--cs-border); padding:12px 16px; border-radius:0 0 12px 12px; margin-top:4px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Renewal Details Modal Popup --}}
<div id="renewalModal" class="rn-modal-overlay" style="display:none;" onclick="if(event.target === this) closeRenewalModal()">
    <div class="rn-modal-card">
        <!-- Header -->
        <div class="rn-modal-header">
            <div class="rn-header-left">
                <div class="rn-title-row">
                    <span id="rnModalIcon" class="rn-period-icon">📅</span>
                    <div>
                        <h3 id="rnModalTitle" class="rn-modal-title">Current Month Renewal (CMR)</h3>
                        <div id="rnModalSubtitle" class="rn-modal-subtitle">Renewal accounts &amp; products scheduled for the current month</div>
                    </div>
                </div>
                <!-- Stat Badges -->
                <div class="rn-header-stats">
                    <div class="rn-stat-chip rn-stat-total">
                        <span style="font-weight:600;color:#64748b;">Total Accounts:</span>
                        <strong id="rnStatCount">0</strong>
                    </div>
                    <div class="rn-stat-chip rn-stat-value">
                        <span style="font-weight:600;color:#64748b;">Total Value:</span>
                        <strong id="rnStatValue">₹0</strong>
                    </div>
                    <div class="rn-stat-chip rn-stat-collected">
                        <span style="font-weight:600;color:#64748b;">Collected:</span>
                        <strong id="rnStatPaid">₹0</strong>
                    </div>
                    <div class="rn-stat-chip rn-stat-pending">
                        <span style="font-weight:600;color:#64748b;">Pending:</span>
                        <strong id="rnStatPending">₹0</strong>
                    </div>
                </div>
            </div>
            <button type="button" class="rn-modal-close" onclick="closeRenewalModal()" title="Close (Esc)">&times;</button>
        </div>

        <!-- Search and Filter Bar -->
        <div class="rn-modal-toolbar">
            <div class="rn-search-box">
                <span class="rn-search-icon">🔍</span>
                <input type="text" id="rnSearchInput" class="rn-search-input" placeholder="Search account, client, mobile, product, assigned CST executive, branch..." oninput="onRenewalModalSearch(this.value)">
                <button type="button" id="rnSearchClear" class="rn-search-clear" onclick="clearRenewalSearch()" style="display:none;">&times;</button>
            </div>
            <div class="rn-filter-group">
                <select id="rnStatusFilter" class="rn-status-select" onchange="onRenewalFilterChange()">
                    <option value="all">All Payment Statuses</option>
                    <option value="pending">Pending Payment</option>
                    <option value="partial">Partial Payment</option>
                    <option value="paid">Fully Paid</option>
                </select>
                <button type="button" class="rn-export-btn" onclick="exportRenewalToCsv()" title="Export current list to CSV">
                    <span>📥 Export CSV</span>
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="rn-modal-body">
            <div class="rn-table-wrap">
                <table class="rn-table">
                    <thead>
                        <tr>
                            <th style="width:40px;text-align:center;">#</th>
                            <th style="min-width:180px;">Account / Company</th>
                            <th style="min-width:150px;">Contact &amp; Mobile</th>
                            <th style="min-width:160px;">Product Package</th>
                            <th style="min-width:130px;text-align:center;">Renewal Date</th>
                            <th style="min-width:110px;text-align:right;">Contract Value</th>
                            <th style="min-width:110px;text-align:right;">Collected</th>
                            <th style="min-width:120px;text-align:right;">Pending</th>
                            <th style="min-width:100px;text-align:center;">Payment Status</th>
                            <th style="min-width:140px;">Assigned CST</th>
                            <th style="min-width:120px;text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="rnTableBody">
                        <tr><td colspan="11" style="text-align:center;color:var(--cs-muted);padding:30px;">Loading renewal data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="rn-modal-footer">
            <div class="rn-footer-info" id="rnFooterInfo">
                Showing 0 of 0 accounts
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <button type="button" class="lpd-btn lpd-btn-ghost" onclick="closeRenewalModal()" style="padding:7px 18px;font-size:12px;">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Upsell Deals Modal Popup --}}
<div id="upsellModal" class="rn-modal-overlay" style="display:none;" onclick="if(event.target === this) closeUpsellModal()">
    <div class="rn-modal-card">
        <!-- Header -->
        <div class="rn-modal-header">
            <div class="rn-header-left">
                <div class="rn-title-row">
                    <span class="rn-period-icon" style="color:#6d28d9;background:#f5f3ff;border-color:#ddd6fe;">💎</span>
                    <div>
                        <h3 class="rn-modal-title">Converted Upsell Deals</h3>
                        <div class="rn-modal-subtitle">Non-recurring product deals successfully closed by Customer Success Team</div>
                    </div>
                </div>
                <!-- Stat Badges -->
                <div class="rn-header-stats">
                    <div class="rn-stat-chip rn-stat-total">
                        <span style="font-weight:600;color:#64748b;">Total Deals:</span>
                        <strong id="upsellStatCount">0</strong>
                    </div>
                    <div class="rn-stat-chip" style="background:#f5f3ff;border-color:#ddd6fe;">
                        <span style="font-weight:600;color:#6b21a8;">Total Upsell Value:</span>
                        <strong id="upsellStatValue" style="color:#6d28d9;">₹0</strong>
                    </div>
                    <div class="rn-stat-chip rn-stat-collected">
                        <span style="font-weight:600;color:#64748b;">Collected:</span>
                        <strong id="upsellStatPaid">₹0</strong>
                    </div>
                    <div class="rn-stat-chip rn-stat-pending">
                        <span style="font-weight:600;color:#64748b;">Pending:</span>
                        <strong id="upsellStatPending">₹0</strong>
                    </div>
                </div>
            </div>
            <button type="button" class="rn-modal-close" onclick="closeUpsellModal()" title="Close (Esc)">&times;</button>
        </div>

        <!-- Search and Filter Bar -->
        <div class="rn-modal-toolbar">
            <div class="rn-search-box">
                <span class="rn-search-icon">🔍</span>
                <input type="text" id="upsellSearchInput" class="rn-search-input" placeholder="Search account, client, mobile, product, branch, closed by..." oninput="onUpsellModalSearch(this.value)">
                <button type="button" id="upsellSearchClear" class="rn-search-clear" onclick="clearUpsellSearch()" style="display:none;">&times;</button>
            </div>
            <div class="rn-filter-group">
                <select id="upsellStatusFilter" class="rn-status-select" onchange="onUpsellFilterChange()">
                    <option value="all">All Payment Statuses</option>
                    <option value="paid">Fully Paid</option>
                    <option value="partial">Partial Payment</option>
                    <option value="unpaid">Unpaid</option>
                </select>
                <button type="button" class="rn-export-btn" onclick="exportUpsellToCsv()" title="Export current upsell list to CSV">
                    <span>📥 Export CSV</span>
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="rn-modal-body">
            <div class="rn-table-wrap">
                <table class="rn-table">
                    <thead>
                        <tr>
                            <th style="width:40px;text-align:center;">#</th>
                            <th style="min-width:180px;">Account / Company</th>
                            <th style="min-width:150px;">Contact &amp; Mobile</th>
                            <th style="min-width:160px;">Product Name</th>
                            <th style="min-width:110px;text-align:right;">Deal Value</th>
                            <th style="min-width:110px;text-align:right;">Collected</th>
                            <th style="min-width:120px;text-align:right;">Pending</th>
                            <th style="min-width:100px;text-align:center;">Payment Status</th>
                            <th style="min-width:120px;text-align:center;">Converted Date</th>
                            <th style="min-width:140px;">Closed By</th>
                            <th style="min-width:110px;text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="upsellTableBody">
                        <tr><td colspan="11" style="text-align:center;color:var(--cs-muted);padding:30px;">Loading upsell data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="rn-modal-footer">
            <div class="rn-footer-info" id="upsellFooterInfo">
                Showing 0 of 0 deals
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <button type="button" class="lpd-btn lpd-btn-ghost" onclick="closeUpsellModal()" style="padding:7px 18px;font-size:12px;">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Received Payments Modal Popup (Today / This Month) --}}
<div id="paymentsModal" class="rn-modal-overlay" style="display:none;" onclick="if(event.target === this) closePaymentsModal()">
    <div class="rn-modal-card">
        <!-- Header -->
        <div class="rn-modal-header">
            <div class="rn-header-left">
                <div class="rn-title-row">
                    <span id="payModalIcon" class="rn-period-icon" style="color:#059669;background:#ecfdf5;border-color:#a7f3d0;">💵</span>
                    <div>
                        <h3 id="payModalTitle" class="rn-modal-title">Today Received Payments</h3>
                        <div id="payModalSubtitle" class="rn-modal-subtitle">Collections and payments recorded by Customer Success Team</div>
                    </div>
                </div>
                <!-- Stat Badges -->
                <div class="rn-header-stats">
                    <div class="rn-stat-chip rn-stat-total">
                        <span style="font-weight:600;color:#64748b;">Total Collections:</span>
                        <strong id="payStatCount">0</strong>
                    </div>
                    <div class="rn-stat-chip rn-stat-collected">
                        <span style="font-weight:600;color:#047857;">Total Received:</span>
                        <strong id="payStatValue">₹0</strong>
                    </div>
                    <div class="rn-stat-chip" style="background:#f8fafc;border-color:#e2e8f0;">
                        <span style="font-weight:600;color:#64748b;">Unique Accounts:</span>
                        <strong id="payStatAccounts">0</strong>
                    </div>
                </div>
            </div>
            <button type="button" class="rn-modal-close" onclick="closePaymentsModal()" title="Close (Esc)">&times;</button>
        </div>

        <!-- Search and Filter Bar -->
        <div class="rn-modal-toolbar">
            <div class="rn-search-box">
                <span class="rn-search-icon">🔍</span>
                <input type="text" id="paySearchInput" class="rn-search-input" placeholder="Search account, client, mobile, product, ref no, recorded by, branch..." oninput="onPaymentsModalSearch(this.value)">
                <button type="button" id="paySearchClear" class="rn-search-clear" onclick="clearPaymentsSearch()" style="display:none;">&times;</button>
            </div>
            <div class="rn-filter-group">
                <select id="payModeFilter" class="rn-status-select" onchange="onPaymentsFilterChange()">
                    <option value="all">All Payment Modes</option>
                    <option value="upi">UPI</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="cheque">Cheque</option>
                    <option value="card">Card</option>
                </select>
                <button type="button" class="rn-export-btn" onclick="exportPaymentsToCsv()" title="Export current payments list to CSV">
                    <span>📥 Export CSV</span>
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="rn-modal-body">
            <div class="rn-table-wrap">
                <table class="rn-table">
                    <thead>
                        <tr>
                            <th style="width:40px;text-align:center;">#</th>
                            <th style="min-width:180px;">Account / Company</th>
                            <th style="min-width:150px;">Contact &amp; Mobile</th>
                            <th style="min-width:150px;">Product Name</th>
                            <th style="min-width:120px;text-align:right;">Amount Received</th>
                            <th style="min-width:120px;text-align:center;">Payment Mode</th>
                            <th style="min-width:120px;">Ref / Cheque No</th>
                            <th style="min-width:110px;text-align:center;">Payment Date</th>
                            <th style="min-width:130px;">Recorded By</th>
                            <th style="min-width:130px;">Notes</th>
                            <th style="min-width:90px;text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="payTableBody">
                        <tr><td colspan="11" style="text-align:center;color:var(--cs-muted);padding:30px;">Loading payment collections...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="rn-modal-footer">
            <div class="rn-footer-info" id="payFooterInfo">
                Showing 0 of 0 collections
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <button type="button" class="lpd-btn lpd-btn-ghost" onclick="closePaymentsModal()" style="padding:7px 18px;font-size:12px;">Close</button>
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
        <div class="summary-card sc-orange" onclick="openRenewalModal('cmr')" title="Click to view Current Month Renewal accounts in popup modal">
            <div class="sc-icon">📅</div>
            <div class="sc-value">${fmt(data.cmr.value)}</div>
            <div class="sc-label">Current Month Renewal (CMR)</div>
            <div class="sc-sub">${data.cmr.count} accounts renewing &bull; Click to view ↗</div>
        </div>
        <div class="summary-card sc-emerald" onclick="openRenewalModal('cmr_plus')" title="Click to view Next Month Renewal accounts in popup modal">
            <div class="sc-icon">⏭️</div>
            <div class="sc-value">${fmt(data.cmr_plus.value)}</div>
            <div class="sc-label">Next Month Renewal (CMR+1)</div>
            <div class="sc-sub">${data.cmr_plus.count} accounts renewing &bull; Click to view ↗</div>
        </div>
        <div class="summary-card sc-blue" onclick="openRenewalModal('cmr_minus')" title="Click to view Last Month Renewal accounts in popup modal">
            <div class="sc-icon">⏮️</div>
            <div class="sc-value">${fmt(data.cmr_minus.value)}</div>
            <div class="sc-label">Last Month Renewal (CMR-1)</div>
            <div class="sc-sub">${data.cmr_minus.count} accounts renewing &bull; Click to view ↗</div>
        </div>
        <div class="summary-card sc-purple" onclick="openUpsellModal()" title="Click to view Converted Upsell Deals in modal popup">
            <div class="sc-icon">💎</div>
            <div class="sc-value">${fmt(data.upsells.value)}</div>
            <div class="sc-label">Upsell Converted (Period)</div>
            <div class="sc-sub">${data.upsells.count} deals closed &bull; Click to view ↗</div>
        </div>
        <div class="summary-card sc-amber" onclick="openPaymentsModal('today')" title="Click to view Today Received Payments in modal popup">
            <div class="sc-icon">💵</div>
            <div class="sc-value">${fmt(data.today_payments?.value ?? data.today_payments)}</div>
            <div class="sc-label">Today Received Payments</div>
            <div class="sc-sub">${data.today_payments?.count ?? 0} collections &bull; Click to view ↗</div>
        </div>
        <div class="summary-card sc-teal" onclick="openPaymentsModal('month')" title="Click to view This Month Collections in modal popup">
            <div class="sc-icon">📊</div>
            <div class="sc-value">${fmt(data.month_payments?.value ?? data.month_payments)}</div>
            <div class="sc-label">This Month Collections</div>
            <div class="sc-sub">${data.month_payments?.count ?? 0} collections &bull; Click to view ↗</div>
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
        <div class="summary-card sc-orange" style="background: linear-gradient(135deg, #c2410c 0%, #ea580c 100%) !important;" onclick="document.getElementById('pendingWelcomeCallsPanel')?.scrollIntoView({behavior: 'smooth'})" title="Click to view Approved Projects Pending Welcome Call Update">
            <div class="sc-icon">📞</div>
            <div class="sc-value">${data.pending_welcome_calls?.count ?? 0}</div>
            <div class="sc-label">Pending Welcome Calls</div>
            <div class="sc-sub">Approved accounts (All Departments)</div>
        </div>
        <div class="summary-card sc-amber" style="background: linear-gradient(135deg, #b45309 0%, #f59e0b 100%) !important;" onclick="switchSmmTab('current_month', true)" title="Click to view SMM Sheet Current Month Expiring Details">
            <div class="sc-icon">📱</div>
            <div class="sc-value">${data.smm_sheet?.current_month?.count ?? 0}</div>
            <div class="sc-label">SMM Expiring (This Month)</div>
            <div class="sc-sub">Accounts expiring this month</div>
        </div>
        <div class="summary-card sc-rose" style="background: linear-gradient(135deg, #be123c 0%, #fb7185 100%) !important;" onclick="switchSmmTab('last_month', true)" title="Click to view SMM Sheet Last Month Expired Details">
            <div class="sc-icon">⏮️</div>
            <div class="sc-value">${data.smm_sheet?.last_month?.count ?? 0}</div>
            <div class="sc-label">SMM Expired (Last Month)</div>
            <div class="sc-sub">Accounts expired last month</div>
        </div>
        <div class="summary-card sc-indigo" style="background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%) !important;" onclick="switchSmmTab('next_month', true)" title="Click to view SMM Sheet Next Month Expiring Details">
            <div class="sc-icon">⏭️</div>
            <div class="sc-value">${data.smm_sheet?.next_month?.count ?? 0}</div>
            <div class="sc-label">SMM Expiring (Next Month)</div>
            <div class="sc-sub">Accounts expiring next month</div>
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

    // 8. Pending Welcome Calls Table
    renderPendingWelcomeCallsTable();

    // 9. SMM Sheet Expiry Details
    const elSmmCur = document.getElementById('smmCountCurrentMonth');
    const elSmmLast = document.getElementById('smmCountLastMonth');
    const elSmmNext = document.getElementById('smmCountNextMonth');
    if (elSmmCur) elSmmCur.textContent = data.smm_sheet?.current_month?.count ?? 0;
    if (elSmmLast) elSmmLast.textContent = data.smm_sheet?.last_month?.count ?? 0;
    if (elSmmNext) elSmmNext.textContent = data.smm_sheet?.next_month?.count ?? 0;
    renderSmmSheetTable();
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

let pendingWcCurrentPage = 1;
const pendingWcPageSize = 10;
let pendingWcSearchTerm = '';

function onPendingWcSearch(val) {
    pendingWcSearchTerm = (val || '').toLowerCase().trim();
    pendingWcCurrentPage = 1;
    renderPendingWelcomeCallsTable();
}

function renderPendingWelcomeCallsTable() {
    if (!dashboardDataRaw) return;
    const allItems = dashboardDataRaw.pending_welcome_calls?.items || [];
    
    let items = allItems;
    if (pendingWcSearchTerm) {
        items = allItems.filter(item => {
            const str = `${item.company_name} ${item.mobile_number} ${item.product_name} ${item.department_name} ${item.lead_id}`.toLowerCase();
            return str.includes(pendingWcSearchTerm);
        });
    }

    const badge = document.getElementById('pendingWcCountBadge');
    if (badge) {
        badge.textContent = `${items.length} Projects`;
    }

    const total = items.length;
    const totalPages = Math.max(1, Math.ceil(total / pendingWcPageSize));
    if (pendingWcCurrentPage > totalPages) pendingWcCurrentPage = totalPages;
    if (pendingWcCurrentPage < 1) pendingWcCurrentPage = 1;

    const startIdx = (pendingWcCurrentPage - 1) * pendingWcPageSize;
    const endIdx = Math.min(startIdx + pendingWcPageSize, total);
    const pageItems = items.slice(startIdx, endIdx);

    const tbody = document.getElementById('pendingWcTableBody');
    const paginationEl = document.getElementById('pendingWcPagination');

    if (total === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--cs-muted);padding:24px;">No approved projects pending welcome call update.</td></tr>`;
        if (paginationEl) paginationEl.style.display = 'none';
        return;
    }

    tbody.innerHTML = pageItems.map(row => `
        <tr>
            <td>
                <strong style="color:var(--cs-text);">${row.company_name}</strong>
                <div style="font-size:11px;color:var(--cs-muted);margin-top:2px;">
                    Lead #${row.lead_id} · 📞 ${row.mobile_number}
                </div>
            </td>
            <td>
                <span style="font-weight:600;">${row.product_name}</span>
            </td>
            <td>
                <span class="cs-badge cs-badge-onboard">${row.department_name}</span>
            </td>
            <td style="text-align:right;font-weight:700;">${fmt(row.total_value)}</td>
            <td style="text-align:right;color:var(--cs-emerald);font-weight:700;">${fmt(row.received_amount)}</td>
            <td style="text-align:right;color:var(--cs-rose);font-weight:700;">${fmt(row.pending_amount)}</td>
            <td style="text-align:center;font-size:12px;color:var(--cs-muted);font-weight:600;">${row.approved_date}</td>
            <td style="text-align:center;">
                <a href="${row.action_url}" class="lpd-btn lpd-btn-primary" style="padding:4px 10px;font-size:11px;text-decoration:none;display:inline-flex;">
                    View Project
                </a>
            </td>
        </tr>
    `).join('');

    if (paginationEl) {
        if (totalPages <= 1 && total <= pendingWcPageSize) {
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

        if (pendingWcCurrentPage > 1) {
            linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changePendingWcPage(${pendingWcCurrentPage - 1})">Prev</a>`;
        } else {
            linksHtml += `<span class="app-pagination__link is-disabled">Prev</span>`;
        }

        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= pendingWcCurrentPage - 1 && p <= pendingWcCurrentPage + 1)) {
                if (p === pendingWcCurrentPage) {
                    linksHtml += `<span class="app-pagination__link is-active">${p}</span>`;
                } else {
                    linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changePendingWcPage(${p})">${p}</a>`;
                }
            } else if (p === pendingWcCurrentPage - 2 || p === pendingWcCurrentPage + 2) {
                linksHtml += `<span class="app-pagination__ellipsis">...</span>`;
            }
        }

        if (pendingWcCurrentPage < totalPages) {
            linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changePendingWcPage(${pendingWcCurrentPage + 1})">Next</a>`;
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

function changePendingWcPage(page) {
    pendingWcCurrentPage = page;
    renderPendingWelcomeCallsTable();
}

let activeSmmTab = 'current_month';
let smmCurrentPage = 1;
const smmPageSize = 10;
let smmSearchTerm = '';

function switchSmmTab(tab, scrollToPanel = false) {
    activeSmmTab = tab;
    smmCurrentPage = 1;

    ['current_month', 'last_month', 'next_month'].forEach(k => {
        const btn = document.getElementById('btn_smm_' + k);
        if (btn) {
            if (k === tab) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    });

    renderSmmSheetTable();

    if (scrollToPanel) {
        document.getElementById('smmSheetExpiryPanel')?.scrollIntoView({ behavior: 'smooth' });
    }
}

function onSmmSearch(val) {
    smmSearchTerm = (val || '').toLowerCase().trim();
    smmCurrentPage = 1;
    renderSmmSheetTable();
}

function renderSmmSheetTable() {
    if (!dashboardDataRaw || !dashboardDataRaw.smm_sheet) return;

    let allItems = [];
    if (activeSmmTab === 'current_month') {
        allItems = dashboardDataRaw.smm_sheet.current_month?.items || [];
    } else if (activeSmmTab === 'last_month') {
        allItems = dashboardDataRaw.smm_sheet.last_month?.items || [];
    } else if (activeSmmTab === 'next_month') {
        allItems = dashboardDataRaw.smm_sheet.next_month?.items || [];
    }

    let items = allItems;
    if (smmSearchTerm) {
        items = allItems.filter(item => {
            const str = `${item.company_name} ${item.mobile_number} ${item.product_name} ${item.department_name} ${item.status}`.toLowerCase();
            return str.includes(smmSearchTerm);
        });
    }

    const total = items.length;
    const totalPages = Math.max(1, Math.ceil(total / smmPageSize));
    if (smmCurrentPage > totalPages) smmCurrentPage = totalPages;
    if (smmCurrentPage < 1) smmCurrentPage = 1;

    const startIdx = (smmCurrentPage - 1) * smmPageSize;
    const endIdx = Math.min(startIdx + smmPageSize, total);
    const pageItems = items.slice(startIdx, endIdx);

    const tbody = document.getElementById('smmTableBody');
    const paginationEl = document.getElementById('smmPagination');

    if (total === 0) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;color:var(--cs-muted);padding:24px;">No SMM sheets found for this period.</td></tr>`;
        if (paginationEl) paginationEl.style.display = 'none';
        return;
    }

    tbody.innerHTML = pageItems.map(row => {
        let statusBadge = '';
        const s = (row.status || '').toLowerCase();
        if (s === 'completed') {
            statusBadge = `<span class="cs-badge cs-badge-paid">COMPLETED</span>`;
        } else if (s === 'overdue') {
            statusBadge = `<span class="cs-badge cs-badge-pending">OVERDUE</span>`;
        } else {
            statusBadge = `<span class="cs-badge cs-badge-onboard">PENDING</span>`;
        }

        return `
            <tr>
                <td>
                    <strong style="color:var(--cs-text);">${row.company_name}</strong>
                    <div style="font-size:11px;color:var(--cs-muted);margin-top:2px;">
                        Lead #${row.lead_id} · 📞 ${row.mobile_number}
                    </div>
                </td>
                <td>
                    <span style="font-weight:600;">${row.product_name}</span>
                    <div style="font-size:11px;color:var(--cs-muted);margin-top:2px;">${row.department_name}</div>
                </td>
                <td style="text-align:center;">
                    <div style="font-size:12px;font-weight:600;color:var(--cs-text);">${row.end_date}</div>
                    <div style="font-size:11px;color:var(--cs-muted);">From ${row.start_date}</div>
                </td>
                <td style="text-align:center;">
                    <span style="font-weight:700;color:var(--cs-emerald);">${row.completed_posters}</span>
                    <span style="color:var(--cs-muted);"> / ${row.committed_posters}</span>
                </td>
                <td style="text-align:center;">
                    <span style="font-weight:700;color:var(--cs-purple);">${row.completed_videos}</span>
                    <span style="color:var(--cs-muted);"> / ${row.committed_videos}</span>
                </td>
                <td style="text-align:right;font-weight:700;">${fmt(row.total_value)}</td>
                <td style="text-align:right;color:var(--cs-emerald);font-weight:700;">${fmt(row.received_amount)}</td>
                <td style="text-align:right;color:var(--cs-rose);font-weight:700;">${fmt(row.pending_amount)}</td>
                <td style="text-align:center;">${statusBadge}</td>
                <td style="text-align:center;">
                    <a href="${row.action_url}" class="lpd-btn lpd-btn-primary" style="padding:4px 10px;font-size:11px;text-decoration:none;display:inline-flex;">
                        View
                    </a>
                </td>
            </tr>
        `;
    }).join('');

    if (paginationEl) {
        if (totalPages <= 1 && total <= smmPageSize) {
            paginationEl.style.display = total > 0 ? 'flex' : 'none';
            paginationEl.innerHTML = `
                <div class="app-pagination__info">
                    Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${total}</strong> records
                </div>
                <div class="app-pagination__links">
                    <span class="app-pagination__link is-active">1</span>
                </div>
            `;
            return;
        }

        paginationEl.style.display = 'flex';
        let linksHtml = '';

        if (smmCurrentPage > 1) {
            linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeSmmPage(${smmCurrentPage - 1})">Prev</a>`;
        } else {
            linksHtml += `<span class="app-pagination__link is-disabled">Prev</span>`;
        }

        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= smmCurrentPage - 1 && p <= smmCurrentPage + 1)) {
                if (p === smmCurrentPage) {
                    linksHtml += `<span class="app-pagination__link is-active">${p}</span>`;
                } else {
                    linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeSmmPage(${p})">${p}</a>`;
                }
            } else if (p === smmCurrentPage - 2 || p === smmCurrentPage + 2) {
                linksHtml += `<span class="app-pagination__ellipsis">...</span>`;
            }
        }

        if (smmCurrentPage < totalPages) {
            linksHtml += `<a href="javascript:void(0)" class="app-pagination__link" onclick="changeSmmPage(${smmCurrentPage + 1})">Next</a>`;
        } else {
            linksHtml += `<span class="app-pagination__link is-disabled">Next</span>`;
        }

        paginationEl.innerHTML = `
            <div class="app-pagination__info">
                Showing <strong>${startIdx + 1}</strong> to <strong>${endIdx}</strong> of <strong>${total}</strong> records
            </div>
            <div class="app-pagination__links">
                ${linksHtml}
            </div>
        `;
    }
}

function changeSmmPage(page) {
    smmCurrentPage = page;
    renderSmmSheetTable();
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

/* ============================================================
   RENEWAL DETAILS MODAL POPUP LOGIC
   ============================================================ */
let currentRenewalPeriod = 'cmr';
let rawRenewalModalItems = [];
let filteredRenewalItems = [];

function openRenewalModal(periodKey) {
    if (!dashboardDataRaw) {
        alert('Please wait for dashboard data to load.');
        return;
    }

    // Normalize periodKey
    let target = 'cmr';
    if (periodKey === 'cmr_plus' || periodKey === 'nmr') {
        target = 'cmr_plus';
    } else if (periodKey === 'cmr_minus' || periodKey === 'lmr') {
        target = 'cmr_minus';
    } else {
        target = 'cmr';
    }

    currentRenewalPeriod = target;

    const modal = document.getElementById('renewalModal');
    const titleEl = document.getElementById('rnModalTitle');
    const iconEl = document.getElementById('rnModalIcon');
    const subEl = document.getElementById('rnModalSubtitle');

    let meta = {
        title: 'Current Month Renewal (CMR)',
        icon: '📅',
        sub: 'Renewal accounts & products scheduled for the current month'
    };

    if (target === 'cmr_plus') {
        meta = {
            title: 'Next Month Renewal (CMR+1)',
            icon: '⏭️',
            sub: 'Upcoming renewal contracts scheduled for next month'
        };
    } else if (target === 'cmr_minus') {
        meta = {
            title: 'Last Month Renewal (CMR-1)',
            icon: '⏮️',
            sub: 'Renewal accounts from the previous month (renewed or pending)'
        };
    }

    if (titleEl) titleEl.textContent = meta.title;
    if (iconEl) iconEl.textContent = meta.icon;
    if (subEl) subEl.textContent = meta.sub;

    const dataset = dashboardDataRaw[target] || { count: 0, value: 0, items: [] };
    rawRenewalModalItems = Array.isArray(dataset.items) ? dataset.items : [];

    // Reset toolbar filters
    const searchInput = document.getElementById('rnSearchInput');
    if (searchInput) searchInput.value = '';
    const statusSelect = document.getElementById('rnStatusFilter');
    if (statusSelect) statusSelect.value = 'all';
    const clearBtn = document.getElementById('rnSearchClear');
    if (clearBtn) clearBtn.style.display = 'none';

    // Calculate & render summary chips
    let totalVal = 0;
    let totalPaid = 0;
    let totalPending = 0;
    rawRenewalModalItems.forEach(item => {
        totalVal += Number(item.value || 0);
        totalPaid += Number(item.paid || 0);
        totalPending += Number(item.pending || 0);
    });

    const cntEl = document.getElementById('rnStatCount');
    const valEl = document.getElementById('rnStatValue');
    const paidEl = document.getElementById('rnStatPaid');
    const pendEl = document.getElementById('rnStatPending');

    if (cntEl) cntEl.textContent = num(rawRenewalModalItems.length);
    if (valEl) valEl.textContent = fmt(totalVal);
    if (paidEl) paidEl.textContent = fmt(totalPaid);
    if (pendEl) pendEl.textContent = fmt(totalPending);

    filterAndRenderRenewalModal();

    if (modal) modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeRenewalModal() {
    const modal = document.getElementById('renewalModal');
    if (modal) {
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
}

function onRenewalModalSearch(val) {
    const clearBtn = document.getElementById('rnSearchClear');
    if (clearBtn) {
        clearBtn.style.display = val.trim() ? 'block' : 'none';
    }
    filterAndRenderRenewalModal();
}

function clearRenewalSearch() {
    const searchInput = document.getElementById('rnSearchInput');
    if (searchInput) searchInput.value = '';
    const clearBtn = document.getElementById('rnSearchClear');
    if (clearBtn) clearBtn.style.display = 'none';
    filterAndRenderRenewalModal();
    if (searchInput) searchInput.focus();
}

function onRenewalFilterChange() {
    filterAndRenderRenewalModal();
}

function filterAndRenderRenewalModal() {
    const searchVal = (document.getElementById('rnSearchInput')?.value || '').trim().toLowerCase();
    const statusVal = document.getElementById('rnStatusFilter')?.value || 'all';

    filteredRenewalItems = rawRenewalModalItems.filter(item => {
        const paid = Number(item.paid || 0);
        const val = Number(item.value || 0);
        const pending = Number(item.pending || 0);

        // Status filter
        if (statusVal === 'paid') {
            if (pending > 0 || (val > 0 && paid < val)) return false;
        } else if (statusVal === 'pending') {
            if (pending <= 0 || paid > 0) return false;
        } else if (statusVal === 'partial') {
            if (paid <= 0 || pending <= 0) return false;
        }

        // Search text
        if (searchVal) {
            const company = (item.company_name || '').toLowerCase();
            const contact = (item.contact_name || '').toLowerCase();
            const mobile = (item.mobile_number || '').toLowerCase();
            const product = (item.product_name || '').toLowerCase();
            const assigned = (item.assigned_to_name || '').toLowerCase();
            const branch = (item.branch_name || '').toLowerCase();

            return company.includes(searchVal) ||
                   contact.includes(searchVal) ||
                   mobile.includes(searchVal) ||
                   product.includes(searchVal) ||
                   assigned.includes(searchVal) ||
                   branch.includes(searchVal);
        }

        return true;
    });

    renderRenewalTableRows(filteredRenewalItems);
}

function renderRenewalTableRows(items) {
    const tb = document.getElementById('rnTableBody');
    const footerInfo = document.getElementById('rnFooterInfo');

    if (!tb) return;

    if (items.length === 0) {
        tb.innerHTML = `
            <tr>
                <td colspan="11" style="text-align:center;padding:40px 20px;color:var(--cs-muted);">
                    <div style="font-size:32px;margin-bottom:8px;">🔍</div>
                    <div style="font-weight:700;font-size:14px;color:#334155;">No renewal accounts found</div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Try adjusting your search query or status filter.</div>
                </td>
            </tr>
        `;
        if (footerInfo) footerInfo.innerHTML = `Showing <strong>0</strong> of <strong>${rawRenewalModalItems.length}</strong> accounts`;
        return;
    }

    let runningValue = 0;
    let runningPending = 0;

    tb.innerHTML = items.map((row, idx) => {
        runningValue += Number(row.value || 0);
        runningPending += Number(row.pending || 0);

        // Days diff badge
        let daysBadge = '';
        if (row.days_diff === 0) {
            daysBadge = '<span class="rn-badge-today">Today</span>';
        } else if (row.days_diff > 0) {
            daysBadge = `<span class="rn-badge-future">In ${row.days_diff}d</span>`;
        } else if (row.days_diff < 0) {
            daysBadge = `<span class="rn-badge-overdue">${Math.abs(row.days_diff)}d ago</span>`;
        }

        // Payment status badge
        const pctPaid = row.value > 0 ? Math.round((row.paid / row.value) * 100) : 100;
        let pStatusBadge = '';
        if (row.pending <= 0 || pctPaid >= 100) {
            pStatusBadge = '<span class="cs-badge cs-badge-paid">Paid</span>';
        } else if (row.paid > 0) {
            pStatusBadge = `<span class="cs-badge cs-badge-partial">Partial (${pctPaid}%)</span>`;
        } else {
            pStatusBadge = '<span class="cs-badge cs-badge-pending">Unpaid</span>';
        }

        // Branch pill
        const branchPill = row.branch_name ? `<span style="font-size:10px;background:#f1f5f9;color:#475569;padding:2px 6px;border-radius:4px;font-weight:600;margin-left:6px;">${row.branch_name}</span>` : '';

        // Contact info
        let contactHtml = '—';
        if (row.contact_name || row.mobile_number) {
            const cName = row.contact_name ? `<div style="font-weight:600;color:#1e293b;">${row.contact_name}</div>` : '';
            const cPhone = row.mobile_number && row.mobile_number !== '—' 
                ? `<a href="tel:${row.mobile_number}" class="rn-contact-phone">📞 ${row.mobile_number}</a>` 
                : `<span style="color:#94a3b8;font-size:11px;">No Mobile</span>`;
            contactHtml = `${cName}${cPhone}`;
        }

        return `
            <tr>
                <td style="text-align:center;color:#64748b;font-weight:600;">${idx + 1}</td>
                <td>
                    <div style="font-weight:700;color:#0f172a;display:flex;align-items:center;flex-wrap:wrap;gap:4px;">
                        <span>${row.company_name}</span>
                        ${branchPill}
                    </div>
                </td>
                <td>${contactHtml}</td>
                <td>
                    <span style="font-weight:600;color:#334155;">${row.product_name}</span>
                </td>
                <td style="text-align:center;">
                    <div style="font-weight:700;color:#0f172a;">${row.renewal_date_formatted || row.renewal_date}</div>
                    <div style="margin-top:2px;">${daysBadge}</div>
                </td>
                <td style="text-align:right;font-weight:800;color:#0f172a;">${fmt(row.value)}</td>
                <td style="text-align:right;font-weight:700;color:var(--cs-emerald);">${fmt(row.paid)}</td>
                <td style="text-align:right;font-weight:700;color:var(--cs-rose);">
                    <div>${fmt(row.pending)}</div>
                </td>
                <td style="text-align:center;">${pStatusBadge}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:13px;">👤</span>
                        <span style="font-weight:600;color:#334155;">${row.assigned_to_name || 'Unassigned'}</span>
                    </div>
                </td>
                <td style="text-align:center;">
                    <div style="display:inline-flex;gap:4px;align-items:center;">
                        <a href="${row.project_url}" target="_blank" class="rn-action-btn" title="View Project Details">
                            <span>👁️ Project</span>
                        </a>
                        ${row.lead_url && row.lead_url !== '#' ? `
                            <a href="${row.lead_url}" target="_blank" class="rn-action-btn rn-action-lead" title="View Lead Details">
                                <span>👤 Lead</span>
                            </a>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    if (footerInfo) {
        footerInfo.innerHTML = `Showing <strong>${items.length}</strong> of <strong>${rawRenewalModalItems.length}</strong> accounts &bull; Total Value: <strong>${fmt(runningValue)}</strong> (Pending: <span style="color:#dc2626;font-weight:700;">${fmt(runningPending)}</span>)`;
    }
}

function exportRenewalToCsv() {
    if (!filteredRenewalItems || filteredRenewalItems.length === 0) {
        alert('No data to export.');
        return;
    }

    const headers = [
        '#',
        'Account / Company Name',
        'Contact Name',
        'Mobile Number',
        'Branch',
        'Product Package',
        'Renewal Date',
        'Days Remaining/Overdue',
        'Contract Value',
        'Amount Paid',
        'Amount Pending',
        'Payment Status',
        'Assigned CST Executive'
    ];

    const escapeCsv = val => {
        if (val === null || val === undefined) return '""';
        const str = String(val).replace(/"/g, '""');
        return `"${str}"`;
    };

    const rows = filteredRenewalItems.map((item, index) => [
        index + 1,
        escapeCsv(item.company_name),
        escapeCsv(item.contact_name),
        escapeCsv(item.mobile_number),
        escapeCsv(item.branch_name),
        escapeCsv(item.product_name),
        escapeCsv(item.renewal_date_formatted || item.renewal_date),
        escapeCsv(item.days_diff),
        item.value || 0,
        item.paid || 0,
        item.pending || 0,
        escapeCsv(item.payment_status),
        escapeCsv(item.assigned_to_name)
    ].join(','));

    const csvContent = '\uFEFF' + [headers.join(','), ...rows].join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `Renewal_Deals_${currentRenewalPeriod}_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

/* ============================================================
   UPSELL DEALS MODAL POPUP LOGIC
   ============================================================ */
let rawUpsellModalItems = [];
let filteredUpsellItems = [];

function openUpsellModal() {
    if (!dashboardDataRaw) {
        alert('Please wait for dashboard data to load.');
        return;
    }

    const modal = document.getElementById('upsellModal');
    const dataset = dashboardDataRaw.upsells || { count: 0, value: 0, items: [] };
    rawUpsellModalItems = Array.isArray(dataset.items) ? dataset.items : [];

    // Reset toolbar filters
    const searchInput = document.getElementById('upsellSearchInput');
    if (searchInput) searchInput.value = '';
    const statusSelect = document.getElementById('upsellStatusFilter');
    if (statusSelect) statusSelect.value = 'all';
    const clearBtn = document.getElementById('upsellSearchClear');
    if (clearBtn) clearBtn.style.display = 'none';

    // Summary stats
    let totalVal = 0;
    let totalPaid = 0;
    let totalPending = 0;
    rawUpsellModalItems.forEach(item => {
        totalVal += Number(item.value || 0);
        totalPaid += Number(item.paid || 0);
        totalPending += Number(item.pending || 0);
    });

    const cntEl = document.getElementById('upsellStatCount');
    const valEl = document.getElementById('upsellStatValue');
    const paidEl = document.getElementById('upsellStatPaid');
    const pendEl = document.getElementById('upsellStatPending');

    if (cntEl) cntEl.textContent = num(rawUpsellModalItems.length);
    if (valEl) valEl.textContent = fmt(totalVal);
    if (paidEl) paidEl.textContent = fmt(totalPaid);
    if (pendEl) pendEl.textContent = fmt(totalPending);

    filterAndRenderUpsellModal();

    if (modal) modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeUpsellModal() {
    const modal = document.getElementById('upsellModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

function onUpsellModalSearch(val) {
    const clearBtn = document.getElementById('upsellSearchClear');
    if (clearBtn) clearBtn.style.display = val.trim() ? 'block' : 'none';
    filterAndRenderUpsellModal();
}

function clearUpsellSearch() {
    const searchInput = document.getElementById('upsellSearchInput');
    if (searchInput) searchInput.value = '';
    const clearBtn = document.getElementById('upsellSearchClear');
    if (clearBtn) clearBtn.style.display = 'none';
    filterAndRenderUpsellModal();
    if (searchInput) searchInput.focus();
}

function onUpsellFilterChange() {
    filterAndRenderUpsellModal();
}

function filterAndRenderUpsellModal() {
    const searchVal = (document.getElementById('upsellSearchInput')?.value || '').trim().toLowerCase();
    const statusVal = document.getElementById('upsellStatusFilter')?.value || 'all';

    filteredUpsellItems = rawUpsellModalItems.filter(item => {
        const paid = Number(item.paid || 0);
        const val = Number(item.value || 0);
        const pending = Number(item.pending || 0);

        if (statusVal === 'paid') {
            if (pending > 0 || (val > 0 && paid < val)) return false;
        } else if (statusVal === 'unpaid') {
            if (paid > 0) return false;
        } else if (statusVal === 'partial') {
            if (paid <= 0 || pending <= 0) return false;
        }

        if (searchVal) {
            const company = (item.company_name || '').toLowerCase();
            const contact = (item.contact_name || '').toLowerCase();
            const mobile = (item.mobile_number || '').toLowerCase();
            const product = (item.product_name || '').toLowerCase();
            const closedBy = (item.created_by_name || '').toLowerCase();
            const branch = (item.branch_name || '').toLowerCase();

            return company.includes(searchVal) ||
                   contact.includes(searchVal) ||
                   mobile.includes(searchVal) ||
                   product.includes(searchVal) ||
                   closedBy.includes(searchVal) ||
                   branch.includes(searchVal);
        }

        return true;
    });

    renderUpsellTableRows(filteredUpsellItems);
}

function renderUpsellTableRows(items) {
    const tb = document.getElementById('upsellTableBody');
    const footerInfo = document.getElementById('upsellFooterInfo');
    if (!tb) return;

    if (items.length === 0) {
        tb.innerHTML = `
            <tr>
                <td colspan="11" style="text-align:center;padding:40px 20px;color:var(--cs-muted);">
                    <div style="font-size:32px;margin-bottom:8px;">🔍</div>
                    <div style="font-weight:700;font-size:14px;color:#334155;">No upsell deals found</div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Try adjusting your search query or status filter.</div>
                </td>
            </tr>
        `;
        if (footerInfo) footerInfo.innerHTML = `Showing <strong>0</strong> of <strong>${rawUpsellModalItems.length}</strong> deals`;
        return;
    }

    let runningValue = 0;
    let runningPending = 0;

    tb.innerHTML = items.map((row, idx) => {
        runningValue += Number(row.value || 0);
        runningPending += Number(row.pending || 0);

        const pctPaid = row.value > 0 ? Math.round((row.paid / row.value) * 100) : 100;
        let pStatusBadge = '';
        if (row.pending <= 0 || pctPaid >= 100) {
            pStatusBadge = '<span class="cs-badge cs-badge-paid">Paid</span>';
        } else if (row.paid > 0) {
            pStatusBadge = `<span class="cs-badge cs-badge-partial">Partial (${pctPaid}%)</span>`;
        } else {
            pStatusBadge = '<span class="cs-badge cs-badge-pending">Unpaid</span>';
        }

        const branchPill = row.branch_name ? `<span style="font-size:10px;background:#f1f5f9;color:#475569;padding:2px 6px;border-radius:4px;font-weight:600;margin-left:6px;">${row.branch_name}</span>` : '';

        let contactHtml = '—';
        if (row.contact_name || row.mobile_number) {
            const cName = row.contact_name ? `<div style="font-weight:600;color:#1e293b;">${row.contact_name}</div>` : '';
            const cPhone = row.mobile_number && row.mobile_number !== '—' 
                ? `<a href="tel:${row.mobile_number}" class="rn-contact-phone">📞 ${row.mobile_number}</a>` 
                : `<span style="color:#94a3b8;font-size:11px;">No Mobile</span>`;
            contactHtml = `${cName}${cPhone}`;
        }

        return `
            <tr>
                <td style="text-align:center;color:#64748b;font-weight:600;">${idx + 1}</td>
                <td>
                    <div style="font-weight:700;color:#0f172a;display:flex;align-items:center;flex-wrap:wrap;gap:4px;">
                        <span>${row.company_name}</span>
                        ${branchPill}
                    </div>
                </td>
                <td>${contactHtml}</td>
                <td><span style="font-weight:600;color:#334155;">${row.product_name}</span></td>
                <td style="text-align:right;font-weight:800;color:#6d28d9;">${fmt(row.value)}</td>
                <td style="text-align:right;font-weight:700;color:var(--cs-emerald);">${fmt(row.paid)}</td>
                <td style="text-align:right;font-weight:700;color:var(--cs-rose);">${fmt(row.pending)}</td>
                <td style="text-align:center;">${pStatusBadge}</td>
                <td style="text-align:center;font-weight:600;color:#334155;">${row.converted_at || row.created_at}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:13px;">👤</span>
                        <span style="font-weight:600;color:#334155;">${row.created_by_name || 'CST Team'}</span>
                    </div>
                </td>
                <td style="text-align:center;">
                    <a href="${row.lead_url}" target="_blank" class="rn-action-btn rn-action-lead" title="View Lead Profile">
                        <span>👤 View Lead</span>
                    </a>
                </td>
            </tr>
        `;
    }).join('');

    if (footerInfo) {
        footerInfo.innerHTML = `Showing <strong>${items.length}</strong> of <strong>${rawUpsellModalItems.length}</strong> deals &bull; Total Value: <strong style="color:#6d28d9;">${fmt(runningValue)}</strong> (Pending: <span style="color:#dc2626;font-weight:700;">${fmt(runningPending)}</span>)`;
    }
}

function exportUpsellToCsv() {
    if (!filteredUpsellItems || filteredUpsellItems.length === 0) {
        alert('No data to export.');
        return;
    }
    const headers = ['#', 'Account / Company Name', 'Contact Name', 'Mobile Number', 'Branch', 'Product Name', 'Deal Value', 'Amount Paid', 'Amount Pending', 'Payment Status', 'Converted Date', 'Closed By'];
    const escapeCsv = val => {
        if (val === null || val === undefined) return '""';
        return `"${String(val).replace(/"/g, '""')}"`;
    };
    const rows = filteredUpsellItems.map((item, index) => [
        index + 1,
        escapeCsv(item.company_name),
        escapeCsv(item.contact_name),
        escapeCsv(item.mobile_number),
        escapeCsv(item.branch_name),
        escapeCsv(item.product_name),
        item.value || 0,
        item.paid || 0,
        item.pending || 0,
        escapeCsv(item.payment_status),
        escapeCsv(item.converted_at || item.created_at),
        escapeCsv(item.created_by_name)
    ].join(','));
    const csvContent = '\uFEFF' + [headers.join(','), ...rows].join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `Upsell_Deals_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

/* ============================================================
   PAYMENT COLLECTIONS MODAL POPUP LOGIC (Today / This Month)
   ============================================================ */
let currentPaymentsPeriod = 'today';
let rawPaymentsModalItems = [];
let filteredPaymentsItems = [];

function openPaymentsModal(periodType) {
    if (!dashboardDataRaw) {
        alert('Please wait for dashboard data to load.');
        return;
    }

    currentPaymentsPeriod = periodType === 'today' ? 'today' : 'month';

    const modal = document.getElementById('paymentsModal');
    const titleEl = document.getElementById('payModalTitle');
    const iconEl = document.getElementById('payModalIcon');
    const subEl = document.getElementById('payModalSubtitle');

    if (currentPaymentsPeriod === 'today') {
        if (titleEl) titleEl.textContent = "Today Received Payments";
        if (iconEl) {
            iconEl.textContent = "💵";
            iconEl.style.color = "#b45309";
            iconEl.style.background = "#fffbeb";
            iconEl.style.borderColor = "#fde68a";
        }
        if (subEl) subEl.textContent = "All collections and customer payments recorded today";
        const dataset = dashboardDataRaw.today_payments || { count: 0, value: 0, items: [] };
        rawPaymentsModalItems = Array.isArray(dataset.items) ? dataset.items : [];
    } else {
        if (titleEl) titleEl.textContent = "This Month Collections";
        if (iconEl) {
            iconEl.textContent = "📊";
            iconEl.style.color = "#0d9488";
            iconEl.style.background = "#f0fdfa";
            iconEl.style.borderColor = "#99f6e4";
        }
        if (subEl) subEl.textContent = "All collections and customer payments recorded in the current month";
        const dataset = dashboardDataRaw.month_payments || { count: 0, value: 0, items: [] };
        rawPaymentsModalItems = Array.isArray(dataset.items) ? dataset.items : [];
    }

    // Reset toolbar filters
    const searchInput = document.getElementById('paySearchInput');
    if (searchInput) searchInput.value = '';
    const modeSelect = document.getElementById('payModeFilter');
    if (modeSelect) modeSelect.value = 'all';
    const clearBtn = document.getElementById('paySearchClear');
    if (clearBtn) clearBtn.style.display = 'none';

    // Summary stats
    let totalVal = 0;
    const uniqueAccounts = new Set();
    rawPaymentsModalItems.forEach(item => {
        totalVal += Number(item.amount || 0);
        if (item.lead_id) uniqueAccounts.add(item.lead_id);
    });

    const cntEl = document.getElementById('payStatCount');
    const valEl = document.getElementById('payStatValue');
    const accEl = document.getElementById('payStatAccounts');

    if (cntEl) cntEl.textContent = num(rawPaymentsModalItems.length);
    if (valEl) valEl.textContent = fmt(totalVal);
    if (accEl) accEl.textContent = num(uniqueAccounts.size);

    filterAndRenderPaymentsModal();

    if (modal) modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closePaymentsModal() {
    const modal = document.getElementById('paymentsModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

function onPaymentsModalSearch(val) {
    const clearBtn = document.getElementById('paySearchClear');
    if (clearBtn) clearBtn.style.display = val.trim() ? 'block' : 'none';
    filterAndRenderPaymentsModal();
}

function clearPaymentsSearch() {
    const searchInput = document.getElementById('paySearchInput');
    if (searchInput) searchInput.value = '';
    const clearBtn = document.getElementById('paySearchClear');
    if (clearBtn) clearBtn.style.display = 'none';
    filterAndRenderPaymentsModal();
    if (searchInput) searchInput.focus();
}

function onPaymentsFilterChange() {
    filterAndRenderPaymentsModal();
}

function filterAndRenderPaymentsModal() {
    const searchVal = (document.getElementById('paySearchInput')?.value || '').trim().toLowerCase();
    const modeVal = document.getElementById('payModeFilter')?.value || 'all';

    filteredPaymentsItems = rawPaymentsModalItems.filter(item => {
        if (modeVal !== 'all' && (item.payment_mode || '').toLowerCase() !== modeVal.toLowerCase()) {
            return false;
        }

        if (searchVal) {
            const company = (item.company_name || '').toLowerCase();
            const contact = (item.contact_name || '').toLowerCase();
            const mobile = (item.mobile_number || '').toLowerCase();
            const product = (item.product_name || '').toLowerCase();
            const recorded = (item.recorded_by_name || '').toLowerCase();
            const ref = (item.reference_number || '').toLowerCase();
            const mode = (item.payment_mode_label || item.payment_mode || '').toLowerCase();
            const branch = (item.branch_name || '').toLowerCase();

            return company.includes(searchVal) ||
                   contact.includes(searchVal) ||
                   mobile.includes(searchVal) ||
                   product.includes(searchVal) ||
                   recorded.includes(searchVal) ||
                   ref.includes(searchVal) ||
                   mode.includes(searchVal) ||
                   branch.includes(searchVal);
        }

        return true;
    });

    renderPaymentsTableRows(filteredPaymentsItems);
}

function renderPaymentsTableRows(items) {
    const tb = document.getElementById('payTableBody');
    const footerInfo = document.getElementById('payFooterInfo');
    if (!tb) return;

    if (items.length === 0) {
        tb.innerHTML = `
            <tr>
                <td colspan="11" style="text-align:center;padding:40px 20px;color:var(--cs-muted);">
                    <div style="font-size:32px;margin-bottom:8px;">🔍</div>
                    <div style="font-weight:700;font-size:14px;color:#334155;">No payment records found</div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">Try adjusting your search query or payment mode filter.</div>
                </td>
            </tr>
        `;
        if (footerInfo) footerInfo.innerHTML = `Showing <strong>0</strong> of <strong>${rawPaymentsModalItems.length}</strong> payments`;
        return;
    }

    let runningTotal = 0;

    tb.innerHTML = items.map((row, idx) => {
        runningTotal += Number(row.amount || 0);

        const branchPill = row.branch_name ? `<span style="font-size:10px;background:#f1f5f9;color:#475569;padding:2px 6px;border-radius:4px;font-weight:600;margin-left:6px;">${row.branch_name}</span>` : '';

        let contactHtml = '—';
        if (row.contact_name || row.mobile_number) {
            const cName = row.contact_name ? `<div style="font-weight:600;color:#1e293b;">${row.contact_name}</div>` : '';
            const cPhone = row.mobile_number && row.mobile_number !== '—' 
                ? `<a href="tel:${row.mobile_number}" class="rn-contact-phone">📞 ${row.mobile_number}</a>` 
                : `<span style="color:#94a3b8;font-size:11px;">No Mobile</span>`;
            contactHtml = `${cName}${cPhone}`;
        }

        // Mode badge style
        let modeBg = '#f1f5f9';
        let modeColor = '#334155';
        if (row.payment_mode === 'upi') { modeBg = '#fff7ed'; modeColor = '#c2410c'; }
        else if (row.payment_mode === 'bank_transfer') { modeBg = '#eff6ff'; modeColor = '#1d4ed8'; }
        else if (row.payment_mode === 'cash') { modeBg = '#ecfdf5'; modeColor = '#047857'; }
        else if (row.payment_mode === 'cheque') { modeBg = '#f5f3ff'; modeColor = '#6d28d9'; }

        return `
            <tr>
                <td style="text-align:center;color:#64748b;font-weight:600;">${idx + 1}</td>
                <td>
                    <div style="font-weight:700;color:#0f172a;display:flex;align-items:center;flex-wrap:wrap;gap:4px;">
                        <span>${row.company_name}</span>
                        ${branchPill}
                    </div>
                </td>
                <td>${contactHtml}</td>
                <td><span style="font-weight:600;color:#334155;">${row.product_name}</span></td>
                <td style="text-align:right;font-weight:800;color:var(--cs-emerald);font-size:13.5px;">${fmt(row.amount)}</td>
                <td style="text-align:center;">
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;background:${modeBg};color:${modeColor};">
                        <span>${row.payment_mode_icon || '💰'}</span>
                        <span>${row.payment_mode_label || row.payment_mode}</span>
                    </span>
                </td>
                <td><span style="font-family:monospace;font-size:11px;color:#475569;">${row.reference_number || '—'}</span></td>
                <td style="text-align:center;font-weight:600;color:#334155;">${row.payment_date_formatted || row.payment_date}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:13px;">👤</span>
                        <span style="font-weight:600;color:#334155;">${row.recorded_by_name || 'Unknown'}</span>
                    </div>
                </td>
                <td><span style="font-size:11px;color:#64748b;max-width:140px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${row.notes || ''}">${row.notes || '—'}</span></td>
                <td style="text-align:center;">
                    <a href="${row.lead_url}" target="_blank" class="rn-action-btn rn-action-lead" title="View Lead Profile">
                        <span>👤 Lead</span>
                    </a>
                </td>
            </tr>
        `;
    }).join('');

    if (footerInfo) {
        footerInfo.innerHTML = `Showing <strong>${items.length}</strong> of <strong>${rawPaymentsModalItems.length}</strong> payments &bull; Total Amount: <strong style="color:#059669;">${fmt(runningTotal)}</strong>`;
    }
}

function exportPaymentsToCsv() {
    if (!filteredPaymentsItems || filteredPaymentsItems.length === 0) {
        alert('No data to export.');
        return;
    }
    const headers = ['#', 'Account / Company Name', 'Contact Name', 'Mobile Number', 'Branch', 'Product Name', 'Amount Received', 'Payment Mode', 'Reference / Cheque No', 'Payment Date', 'Recorded By', 'Notes'];
    const escapeCsv = val => {
        if (val === null || val === undefined) return '""';
        return `"${String(val).replace(/"/g, '""')}"`;
    };
    const rows = filteredPaymentsItems.map((item, index) => [
        index + 1,
        escapeCsv(item.company_name),
        escapeCsv(item.contact_name),
        escapeCsv(item.mobile_number),
        escapeCsv(item.branch_name),
        escapeCsv(item.product_name),
        item.amount || 0,
        escapeCsv(item.payment_mode_label || item.payment_mode),
        escapeCsv(item.reference_number),
        escapeCsv(item.payment_date_formatted || item.payment_date),
        escapeCsv(item.recorded_by_name),
        escapeCsv(item.notes)
    ].join(','));
    const csvContent = '\uFEFF' + [headers.join(','), ...rows].join('\r\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `Payment_Collections_${currentPaymentsPeriod}_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Close modals when pressing Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRenewalModal();
        closeUpsellModal();
        closePaymentsModal();
    }
});
</script>
@endpush
