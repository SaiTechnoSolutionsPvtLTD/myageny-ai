@extends('layouts.app')

@section('title', auth()->user()->isSuperAdmin() ? 'Super Admin Dashboard' : 'Dashboard')

@push('styles')
<style>
/* ════════════════════════════════════════════════════
   SUPER ADMIN DASHBOARD — API Integrated Version
   All data loaded via fetch() from /api/v1/dashboard/admin
   Filters update without page reload
════════════════════════════════════════════════════ */
:root {
    --da-bg:      #f1f2f5;
    --da-white:   #ffffff;
    --da-border:  #e2dfe6;
    --da-text:    #111827;
    --da-muted:   #9ca3af;
    --da-orange:  #fe5f04;
    --da-orange2: #ff7c30;
    --da-green:   #16a34a;
    --da-red:     #dc2626;
    --da-blue:    #2563eb;
    --da-purple:  #7c3aed;
    --da-amber:   #b45309;
}

* { box-sizing: border-box; }

.da-page { display:flex; flex-direction:column; height:100%; overflow:hidden; background:var(--da-bg); font-family:'Inter',sans-serif; color:var(--da-text); }

/* ─── Topbar ─── */
.da-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 24px; height:58px; flex-shrink:0; background:var(--da-white); border-bottom:1px solid var(--da-border); position:sticky; top:0; z-index:50; }
.da-topbar-left { display:flex; align-items:center; gap:10px; }
.da-logo-box { width:36px; height:36px; background:linear-gradient(135deg,var(--da-orange),var(--da-orange2)); border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.da-page-title { font-size:17px; font-weight:800; }
.da-page-sub   { font-size:11px; color:var(--da-muted); margin-top:1px; }
.da-topbar-right { display:flex; align-items:center; gap:10px; }
.da-refresh-btn { display:flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; border:1px solid var(--da-border); background:var(--da-white); font-size:12px; font-weight:700; color:var(--da-muted); cursor:pointer; font-family:inherit; transition:all .15s; }
.da-refresh-btn:hover { border-color:var(--da-orange); color:var(--da-orange); }
.da-refresh-btn.spinning svg { animation:spin .8s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.da-filter-badge { font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; background:rgba(254,95,4,.1); color:var(--da-orange); border:1px solid rgba(254,95,4,.2); }
.da-last-updated { font-size:11px; color:var(--da-muted); }

/* ─── Filter Bar ─── */
.da-filter-wrap { background:var(--da-white); border-bottom:1px solid var(--da-border); flex-shrink:0; position:sticky; top:58px; z-index:40; }
.da-filter-inner { display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding:9px 24px; }
.da-quick-btns { display:flex; gap:4px; }
.da-qb { padding:5px 12px; border-radius:7px; border:1px solid var(--da-border); background:var(--da-bg); font-size:12px; font-weight:700; color:var(--da-muted); cursor:pointer; font-family:inherit; transition:all .15s; }
.da-qb:hover { border-color:var(--da-orange); color:var(--da-orange); background:#fff7ed; }
.da-qb.active { background:var(--da-orange); color:#fff; border-color:var(--da-orange); }
.da-sep { width:1px; height:24px; background:var(--da-border); flex-shrink:0; }
.da-fw { position:relative; display:inline-flex; align-items:center; }
.da-fi { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--da-muted); pointer-events:none; width:12px; height:12px; }
.da-fsel { appearance:none; -webkit-appearance:none; padding:6px 24px 6px 27px; background:var(--da-bg); border:1px solid var(--da-border); border-radius:8px; font-size:12px; font-weight:600; color:#374151; cursor:pointer; outline:none; font-family:inherit; min-width:115px; transition:all .15s; }
.da-fsel:focus { border-color:var(--da-orange); background:var(--da-white); box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.da-fsel.active { border-color:var(--da-orange); color:var(--da-orange); background:#fff7ed; }
.da-fcaret { position:absolute; right:7px; top:50%; transform:translateY(-50%); pointer-events:none; color:var(--da-muted); width:11px; height:11px; }
.da-date-pair { display:flex; align-items:center; gap:5px; }
.da-date-sep  { font-size:11px; color:var(--da-muted); font-weight:600; }
.da-date-inp  { padding:6px 10px; border:1px solid var(--da-border); border-radius:8px; font-size:12px; font-family:inherit; outline:none; background:var(--da-bg); color:var(--da-text); transition:all .15s; }
.da-date-inp:focus { border-color:var(--da-orange); background:var(--da-white); }
.da-date-inp.active { border-color:var(--da-orange); color:var(--da-orange); }
.da-apply-btn { padding:6px 16px; border-radius:8px; background:linear-gradient(135deg,var(--da-orange),var(--da-orange2)); color:#fff; border:none; font-size:12px; font-weight:700; cursor:pointer; font-family:inherit; transition:all .15s; display:flex; align-items:center; gap:5px; }
.da-apply-btn:hover { transform:translateY(-1px); }
.da-reset-btn { display:flex; align-items:center; gap:4px; padding:6px 12px; border-radius:8px; border:1px solid var(--da-border); font-size:12px; font-weight:600; color:var(--da-muted); cursor:pointer; font-family:inherit; text-decoration:none; transition:all .15s; background:var(--da-white); }
.da-reset-btn:hover { border-color:var(--da-red); color:var(--da-red); }

/* Active chips */
.da-chips { display:none; align-items:center; gap:6px; flex-wrap:wrap; padding:6px 24px 8px; }
.da-chips.show { display:flex; }
.da-chip { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; background:rgba(254,95,4,.08); border:1px solid rgba(254,95,4,.2); font-size:11px; font-weight:700; color:var(--da-orange); }
.da-chip-rm { cursor:pointer; font-size:13px; opacity:.7; line-height:1; }
.da-chip-rm:hover { opacity:1; }

/* ─── Body ─── */
.da-body { flex:1; overflow-y:auto; padding:20px 24px 40px; display:flex; flex-direction:column; gap:20px; }
.da-body::-webkit-scrollbar { width:5px; }
.da-body::-webkit-scrollbar-thumb { background:var(--da-border); border-radius:3px; }

/* ─── Loading overlay ─── */
.da-loading { position:fixed; inset:0; z-index:9999; background:rgba(241,242,245,.75); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; flex-direction:column; gap:12px; transition:opacity .3s; }
.da-loading.hidden { opacity:0; pointer-events:none; }
.da-spinner { width:40px; height:40px; border:3px solid var(--da-border); border-top-color:var(--da-orange); border-radius:50%; animation:spin .8s linear infinite; }
.da-loading-text { font-size:13px; font-weight:600; color:var(--da-muted); }

/* ─── Error banner ─── */
.da-error-bar { background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:12px 16px; font-size:13px; color:var(--da-red); display:none; align-items:center; gap:10px; }
.da-error-bar.show { display:flex; }

/* ─── Section ─── */
.da-section-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
.da-section-title { font-size:14px; font-weight:800; color:var(--da-text); display:flex; align-items:center; gap:7px; }
.da-badge { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; background:var(--da-bg); color:var(--da-muted); border:1px solid var(--da-border); }

/* ─── Card ─── */
.da-card { background:var(--da-white); border:1px solid var(--da-border); border-radius:14px; overflow:hidden; }
.da-card-head { padding:13px 18px; border-bottom:1px solid #f3f0f6; display:flex; justify-content:space-between; align-items:center; }
.da-card-title { font-size:13px; font-weight:700; color:var(--da-text); }
.da-card-body { padding:16px 18px; }

/* ─── KPI grid ─── */
.da-kpi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; }
.da-kpi { position:relative; overflow:hidden; border:none; border-radius:16px; padding:20px; box-shadow:0 10px 25px -5px rgba(15,23,42,.05), 0 8px 10px -6px rgba(15,23,42,.03); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; min-height:140px; }
.da-kpi:hover { transform:translateY(-5px); box-shadow:0 20px 25px -5px rgba(15,23,42,.15),0 10px 10px -5px rgba(15,23,42,.08); }
.da-kpi-icon { display:flex; align-items:center; justify-content:center; width:38px; height:38px; border-radius:12px; background:rgba(255,255,255,0.2); color:#fff; font-size:16px; backdrop-filter:blur(4px); margin-bottom:12px; }
.da-kpi-val  { font-size:26px; font-weight:900; color:#fff; line-height:1.2; margin-top:8px; }
.da-kpi-lbl  { font-size:11px; font-weight:800; color:rgba(255,255,255,0.95); text-transform:uppercase; letter-spacing:.06em; margin-top:4px; }
.da-kpi-sub  { font-size:12px; color:rgba(255,255,255,0.85); font-weight:500; margin-top:8px; }

/* ─── Financial grid ─── */
.da-fin-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; }
.da-fin { background:var(--da-white); border:1px solid var(--da-border); border-radius:16px; padding:20px; border-left:4px solid transparent; box-shadow:0 10px 25px -5px rgba(15,23,42,.03); transition:all 0.3s cubic-bezier(0.4,0,0.2,1); }
.da-fin:hover { transform:translateY(-5px); box-shadow:0 20px 25px -5px rgba(15,23,42,.1),0 10px 10px -5px rgba(15,23,42,.05); }
.da-fin-lbl { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:var(--da-muted); margin-bottom:8px; }
.da-fin-val { font-size:24px; font-weight:900; line-height:1.2; }
.da-fin-sub { font-size:12px; color:var(--da-muted); margin-top:8px; font-weight:500; }
.da-fin-bar-outer { height:6px; background:#f0eef2; border-radius:3px; margin-top:12px; overflow:hidden; }
.da-fin-bar-inner { height:100%; border-radius:3px; transition:width .8s ease; }

/* ─── Two/three col layouts ─── */
.da-two-col   { display:grid; grid-template-columns:8fr 4fr; gap:16px; }
.da-half      { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media (max-width: 991px) {
    .da-two-col, .da-half { grid-template-columns:1fr; }
}

/* ─── Funnel bars ─── */
.da-funnel-row { margin-bottom:12px; }
.da-funnel-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; }
.da-funnel-label { display:flex; align-items:center; gap:7px; font-size:13px; font-weight:700; }
.da-funnel-right { display:flex; align-items:center; gap:8px; }
.da-funnel-pct { font-size:11px; font-weight:700; padding:2px 7px; border-radius:20px; }
.da-funnel-count { font-size:18px; font-weight:900; }
.da-bar-outer { height:8px; background:#f3f0f6; border-radius:4px; overflow:hidden; }
.da-bar-inner { height:100%; border-radius:4px; transition:width .8s ease; }
.da-funnel-visual { margin-top:18px; padding:14px; background:#fafafa; border-radius:10px; border:1px solid #f3f0f6; }
.da-funnel-visual-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--da-muted); margin-bottom:10px; }
.da-funnel-step { margin-bottom:3px; transition:all .3s; }
.da-funnel-step-inner { padding:5px 10px; border-radius:5px; display:flex; justify-content:space-between; align-items:center; font-size:11px; font-weight:700; color:#fff; }

/* ─── Source rows ─── */
.da-source-row { margin-bottom:12px; }
.da-source-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; }
.da-source-label { display:flex; align-items:center; gap:7px; font-size:12px; font-weight:700; color:#374151; }

/* ─── Trend chart ─── */
.da-trend-cols { display:flex; align-items:flex-end; justify-content:space-between; gap:8px; height:140px; padding-top:10px; }
.da-trend-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; }
.da-trend-total { font-size:10px; font-weight:700; color:#374151; margin-bottom:4px; }
.da-trend-bars { display:flex; align-items:flex-end; gap:2px; height:90px; }
.da-trend-bar { width:18px; border-radius:3px 3px 0 0; transition:height .8s ease; min-height:2px; }
.da-trend-lbl { font-size:10px; font-weight:700; color:var(--da-muted); }
.da-trend-values { display:flex; justify-content:space-around; margin-top:10px; padding-top:10px; border-top:1px solid #f3f0f6; }
.da-trend-val-item { text-align:center; }
.da-trend-val-lbl { font-size:9px; font-weight:700; color:var(--da-muted); }
.da-trend-val-num { font-size:11px; font-weight:800; color:#059669; }

/* ─── Performance tables ─── */
.da-perf-tbl { width:100%; border-collapse:collapse; }
.da-perf-tbl th { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--da-muted); padding:9px 14px; text-align:left; background:#fafafa; white-space:nowrap; }
.da-perf-tbl td { padding:11px 14px; font-size:13px; border-top:1px solid #f7f6f9; vertical-align:middle; }
.da-rank { width:22px; height:22px; border-radius:7px; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center; }
.da-member-av { width:28px; height:28px; border-radius:8px; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; }

/* ─── Follow-up list ─── */
.da-followup-item { display:flex; align-items:flex-start; gap:11px; padding:12px 16px; border-bottom:1px solid #f7f6f9; }
.da-followup-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:5px; }
.da-followup-body { flex:1; min-width:0; }
.da-followup-company { font-size:13px; font-weight:700; color:var(--da-text); text-decoration:none; display:block; }
.da-followup-company:hover { color:var(--da-orange); }
.da-followup-meta { font-size:11px; color:var(--da-muted); margin-top:2px; }
.da-followup-tags { display:flex; align-items:center; gap:6px; margin-top:5px; }
.da-followup-time { font-size:10px; color:var(--da-muted); flex-shrink:0; }

/* ─── Reminder list ─── */
.da-rem-item { display:flex; align-items:flex-start; gap:10px; padding:11px 16px; border-bottom:1px solid #f7f6f9; }
.da-rem-ico { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
.da-rem-body { flex:1; min-width:0; }
.da-rem-title { font-size:13px; font-weight:700; color:var(--da-text); }
.da-rem-meta  { font-size:11px; color:var(--da-muted); margin-top:2px; }
.da-rem-tags  { display:flex; align-items:center; gap:6px; margin-top:5px; }

/* ─── Recent leads table ─── */
.da-leads-tbl { width:100%; border-collapse:collapse; }
.da-leads-tbl th { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:var(--da-muted); padding:9px 14px; text-align:left; background:#fafafa; white-space:nowrap; }
.da-leads-tbl td { padding:11px 14px; border-top:1px solid #f7f6f9; vertical-align:middle; font-size:12px; }
.da-leads-tbl tbody tr { cursor:pointer; transition:background .12s; }
.da-leads-tbl tbody tr:hover td { background:#fdf9f6; }
.da-lead-co-av { width:30px; height:30px; border-radius:8px; font-size:11px; font-weight:800; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.da-lead-name { font-size:12px; font-weight:700; color:var(--da-text); }
.da-lead-contact { font-size:10px; color:var(--da-muted); }

/* Skeleton loading */
.da-skel { background:linear-gradient(90deg,#f3f0f6 25%,#e9e5ee 50%,#f3f0f6 75%); background-size:200% 100%; animation:shimmer 1.4s infinite; border-radius:6px; }
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
.da-skel-card { background:var(--da-white); border:1px solid var(--da-border); border-radius:16px; padding:20px; min-height:140px; display:flex; flex-direction:column; justify-content:space-between; }

/* Utility badge */
.da-pill { display:inline-flex; align-items:center; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; border:1px solid transparent; }

/* Empty state */
.da-empty { text-align:center; padding:36px 20px; color:var(--da-muted); }
.da-empty-ico { font-size:32px; margin-bottom:8px; }
.da-empty-title { font-size:13px; font-weight:700; color:#6b7280; }

/* ─── Sales Target Tracking Card Improved UI ─── */
.da-target-card {
    background: var(--da-white);
    border: 1px solid var(--da-border);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    margin-bottom: 24px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}
.da-target-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
}
.da-target-card-head {
    padding: 16px 20px;
    border-bottom: 1px solid #f3f0f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(to right, #faf9fc, var(--da-white));
}
.da-target-card-body {
    padding: 24px;
}
.target-track-container {
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: 32px;
    align-items: center;
}
@media (max-width: 768px) {
    .target-track-container {
        grid-template-columns: 1fr;
        gap: 24px;
        text-align: center;
    }
}
.target-gauge-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
}
.target-gauge-svg-container {
    position: relative;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.target-gauge-svg {
    width: 120px;
    height: 120px;
}
.target-gauge-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.target-gauge-pct {
    font-size: 24px;
    font-weight: 800;
    line-height: 1;
    color: var(--da-text);
}
.target-gauge-lbl {
    font-size: 10px;
    font-weight: 700;
    color: var(--da-muted);
    text-transform: uppercase;
    margin-top: 4px;
    letter-spacing: 0.5px;
}
.target-status-badge {
    margin-top: 14px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-block;
    transition: all 0.3s ease;
}
.target-widgets-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
@media (max-width: 992px) {
    .target-widgets-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
.target-widget-card {
    background: #faf9fc;
    border: 1px solid #f1eef4;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.2s ease;
}
.target-widget-card:hover {
    transform: translateY(-2px);
    background: var(--da-white);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}
.target-widget-card.allocated:hover { border-color: rgba(124, 58, 237, 0.3); }
.target-widget-card.achieved:hover { border-color: rgba(22, 163, 74, 0.3); }
.target-widget-card.pending:hover { border-color: rgba(220, 38, 38, 0.3); }

.target-widget-ico {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.target-widget-card.allocated .target-widget-ico { background: rgba(124, 58, 237, 0.08); color: var(--da-purple); }
.target-widget-card.achieved .target-widget-ico { background: rgba(22, 163, 74, 0.08); color: var(--da-green); }
.target-widget-card.pending .target-widget-ico { background: rgba(220, 38, 38, 0.08); color: var(--da-red); }

.target-widget-content {
    display: flex;
    flex-direction: column;
}
.target-widget-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--da-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.target-widget-val {
    font-size: 18px;
    font-weight: 800;
    color: var(--da-text);
    margin-top: 4px;
    font-family: 'Inter', sans-serif;
}
.target-widget-card.achieved .target-widget-val { color: var(--da-green); }
.target-widget-card.pending .target-widget-val { color: var(--da-red); }

.target-linear-progress-wrap {
    margin-top: 24px;
    border-top: 1px solid #f3f0f6;
    padding-top: 20px;
}
.target-linear-progress-lbls {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 700;
    color: var(--da-muted);
    margin-bottom: 6px;
}
.target-linear-progress-bar {
    height: 8px;
    background: #f0eef2;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
    border: 1px solid #e1dee3;
}
.target-linear-progress-inner {
    height: 100%;
    width: 0%;
    border-radius: 6px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
@endpush

@section('content')

{{-- ════ LOADING OVERLAY ════ --}}
<div class="da-loading" id="daLoading">
    <div class="da-spinner"></div>
    <div class="da-loading-text">Fetching dashboard data…</div>
</div>

<div class="da-page">

    {{-- ════ TOPBAR ════ --}}
    <div class="da-topbar">
        <div class="da-topbar-left">
            <div class="da-logo-box">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div>
                <div class="da-page-title">{{ auth()->user()->isSuperAdmin() ? 'Super Admin Dashboard' : 'Dashboard' }}</div>
                <div class="da-page-sub">myAgenci.ai · {{ $today ?? '' }} · Live via API</div>
            </div>
        </div>
        <div class="da-topbar-right">
            <span class="da-last-updated" id="daLastUpdated">–</span>
            <span class="da-filter-badge" id="daFilterBadge" style="display:none">0 filters</span>
            <button class="da-refresh-btn" id="daRefreshBtn" onclick="dashboardLoad()">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.36"/></svg>
                Refresh
            </button>
            <a href="/product-dashboard/admin" class="da-refresh-btn" id="daRefreshBtn" >
                Goto Product Dashboard
            </a>
            <div style="font-size:12px;color:var(--da-muted);font-weight:600">
                {{ $userName ?? auth()->user()->name }}
                <span style="background:#f5f4f6;padding:2px 8px;border-radius:20px;margin-left:4px;font-size:11px">{{ $userRole ?? 'Admin' }}</span>
            </div>
        </div>
    </div>

    {{-- ════ FILTER BAR ════ --}}
    <div class="da-filter-wrap">
        <div class="da-filter-inner">

            {{-- Quick Dates Dropdown --}}
            <div class="da-fw">
                <svg class="da-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <select class="da-fsel" id="fQuickDate" onchange="onQuickDateSelect(this.value)">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="quarter">This Quarter</option>
                    <option value="year">This Year</option>
                    <option value="all">Show All</option>
                    <option value="custom">Custom Dates</option>
                </select>
                <svg class="da-fcaret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            {{-- Date range (Right next to Quick Dates) --}}
            <div class="da-date-pair" id="daCustomDateWrap" style="display:none">
                <input type="date" class="da-date-inp" id="fDateFrom">
                <span class="da-date-sep">→</span>
                <input type="date" class="da-date-inp" id="fDateTo">
            </div>

            <div class="da-sep"></div>

            {{-- Branch --}}
            <div class="da-fw">
                <svg class="da-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                <select class="da-fsel" id="fBranch" onchange="onFilterChange()">
                    @if(count($branches ?? []) > 1)
                    <option value="">All Branches</option>
                    @endif
                    @foreach($branches ?? [] as $b)
                    <option value="{{ $b->id }}" {{ count($branches ?? []) === 1 ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <svg class="da-fcaret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            {{-- User --}}
            <div class="da-fw">
                <svg class="da-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <select class="da-fsel" id="fUser" onchange="onFilterChange()">
                    <option value="">All Users</option>
                    @foreach($users ?? [] as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <svg class="da-fcaret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            {{-- Stage --}}
            <div class="da-fw">
                <svg class="da-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/></svg>
                <select class="da-fsel" id="fStage" onchange="onFilterChange()">
                    <option value="">All Stages</option>
                    @foreach($statuses ?? [] as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
                <svg class="da-fcaret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            {{-- Source --}}
            <div class="da-fw">
                <svg class="da-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <select class="da-fsel" id="fSource" onchange="onFilterChange()">
                    <option value="">All Sources</option>
                    @foreach($sources ?? [] as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
                <svg class="da-fcaret" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            <button type="button" class="da-apply-btn" onclick="applyFilters()">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Apply
            </button>

            <button type="button" class="da-reset-btn" id="daResetBtn" style="display:none" onclick="resetFilters()">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.36"/></svg>
                Reset
            </button>
        </div>

        {{-- Active chips --}}
        <div class="da-chips" id="daChips"></div>
    </div>

    {{-- ════ BODY ════ --}}
    <div class="da-body" id="daBody">

        {{-- Error bar --}}
        <div class="da-error-bar" id="daError">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="daErrorText">Failed to load dashboard data. Please try again.</span>
            <button onclick="dashboardLoad()" style="margin-left:auto;padding:4px 12px;border-radius:7px;border:1px solid #fecaca;background:#fff;font-size:12px;font-weight:700;color:var(--da-red);cursor:pointer">Retry</button>
        </div>

        {{-- ── KPIs ── --}}
        <div>
            <div class="da-section-head">
                <div class="da-section-title">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Key Metrics
                </div>
                <span class="da-badge" id="daKpiPeriod">–</span>
            </div>
            <div class="da-kpi-grid" id="daKpiGrid">
                @for($i = 0; $i < 8; $i++)
                <div class="da-skel-card">
                    <div class="da-skel" style="height:38px;width:38px;border-radius:12px;margin-bottom:12px"></div>
                    <div class="da-skel" style="height:26px;width:50%;margin-bottom:8px"></div>
                    <div class="da-skel" style="height:12px;width:70%;margin-bottom:8px"></div>
                    <div class="da-skel" style="height:12px;width:40%"></div>
                </div>
                @endfor
            </div>
        </div>

        {{-- ── Target Tracking Card ── --}}
        <div class="da-target-card" style="display:none;" id="daTargetCard">
            <div class="da-target-card-head">
                <div class="da-card-title" style="font-size: 15px; font-weight: 800; color: var(--da-text); display: flex; align-items: center; gap: 8px;">
                    🎯 Sales Target Tracking
                </div>
                <span class="da-badge" id="daTargetTitle" style="font-size: 11px; font-weight: 800; background: #fff1e8; color: var(--da-orange); padding: 4px 10px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.5px;">Overall Target</span>
            </div>
            <div class="da-target-card-body">
                <div class="target-track-container">
                    <!-- Progress Arc (Left Column) -->
                    <div class="target-gauge-wrapper">
                        <div class="target-gauge-svg-container">
                            <svg class="target-gauge-svg" viewBox="0 0 120 120">
                                <defs>
                                    <linearGradient id="tgtGradLow" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#ef4444" />
                                        <stop offset="100%" stop-color="#f97316" />
                                    </linearGradient>
                                    <linearGradient id="tgtGradNormal" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#f97316" />
                                        <stop offset="100%" stop-color="#16a34a" />
                                    </linearGradient>
                                    <linearGradient id="tgtGradSuccess" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#10b981" />
                                        <stop offset="100%" stop-color="#059669" />
                                    </linearGradient>
                                </defs>
                                <circle cx="60" cy="60" r="50" fill="transparent" stroke="#f3f0f6" stroke-width="8" />
                                <circle cx="60" cy="60" r="50" fill="transparent" stroke="url(#tgtGradNormal)" stroke-width="8"
                                        stroke-dasharray="314.16" stroke-dashoffset="314.16" stroke-linecap="round"
                                        style="transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1); transform: rotate(-90deg); transform-origin: 60px 60px;"
                                        id="circleProgress" />
                            </svg>
                            <div class="target-gauge-text">
                                <div class="target-gauge-pct" id="lblPercentVal">0%</div>
                                <div class="target-gauge-lbl">Achieved</div>
                            </div>
                        </div>
                        <div class="target-status-badge" id="lblStatusText">TARGET TRACKING ACTIVE</div>
                    </div>

                    <!-- Target Metrics (Right Column) -->
                    <div class="target-widgets-grid">
                        <!-- Allocated Target -->
                        <div class="target-widget-card allocated">
                            <div class="target-widget-ico">
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <circle cx="12" cy="12" r="6" />
                                    <circle cx="12" cy="12" r="2" />
                                </svg>
                            </div>
                            <div class="target-widget-content">
                                <span class="target-widget-label">Allocated Target</span>
                                <span class="target-widget-val" id="lblTargetVal">₹0.00</span>
                            </div>
                        </div>

                        <!-- Achieved Collection -->
                        <div class="target-widget-card achieved">
                            <div class="target-widget-ico">
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                </svg>
                            </div>
                            <div class="target-widget-content">
                                <span class="target-widget-label">Achieved Collection</span>
                                <span class="target-widget-val" id="lblAchievedVal">₹0.00</span>
                            </div>
                        </div>

                        <!-- Pending Target -->
                        <div class="target-widget-card pending">
                            <div class="target-widget-ico">
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                            </div>
                            <div class="target-widget-content">
                                <span class="target-widget-label">Pending Target</span>
                                <span class="target-widget-val" id="lblPendingVal">₹0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Linear Progress Bar -->
                <div class="target-linear-progress-wrap">
                    <div class="target-linear-progress-lbls">
                        <span>Progress Overview</span>
                        <span id="lblLinearProgressPct">0%</span>
                    </div>
                    <div class="target-linear-progress-bar">
                        <div class="target-linear-progress-inner" id="barProgress"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Financials ── --}}
        <div>
            <div class="da-section-head">
                <div class="da-section-title">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Payment Financials
                </div>
                <span class="da-badge">From Lead Products</span>
            </div>
            <div class="da-fin-grid" id="daFinGrid">
                @for($i = 0; $i < 4; $i++)
                <div class="da-skel-card" style="border-left: 4px solid #e2dfe6; min-height: 135px; justify-content: space-between;">
                    <div class="da-skel" style="height:11px;width:60%;"></div>
                    <div class="da-skel" style="height:24px;width:70%;margin-top:8px;"></div>
                    <div class="da-skel" style="height:12px;width:40%;margin-top:8px;"></div>
                    <div class="da-skel" style="height:6px;width:100%;margin-top:12px;border-radius:3px;"></div>
                </div>
                @endfor
            </div>
        </div>

        {{-- ── Funnel + Source ── --}}
        <div class="da-two-col">
            <div class="da-card">
                <div class="da-card-head">
                    <div class="da-card-title">📊 Pipeline Funnel (Product Wise)</div>
                    <span class="da-badge" id="daFunnelTotal">–</span>
                </div>
                <div class="da-card-body" id="daFunnelBody">
                    <div class="da-skel" style="height:280px"></div>
                </div>
            </div>
            <div class="da-card">
                <div class="da-card-head">
                    <div class="da-card-title">📡 Source Distribution</div>
                    <span class="da-badge" id="daSourceTotal">–</span>
                </div>
                <div class="da-card-body" id="daSourceBody">
                    <div class="da-skel" style="height:280px"></div>
                </div>
            </div>
        </div>

        {{-- ── 6-month Trend ── --}}
        <div class="da-card">
            <div class="da-card-head">
                <div class="da-card-title">📈 6-Month Lead Trend</div>
                <div style="display:flex;align-items:center;gap:12px;font-size:11px;font-weight:700">
                    <span style="display:flex;align-items:center;gap:4px;color:#374151"><span style="width:10px;height:10px;border-radius:50%;background:var(--da-orange);display:inline-block"></span>Total</span>
                    <span style="display:flex;align-items:center;gap:4px;color:var(--da-green)"><span style="width:10px;height:10px;border-radius:50%;background:var(--da-green);display:inline-block"></span>Convert</span>
                    <span style="display:flex;align-items:center;gap:4px;color:var(--da-red)"><span style="width:10px;height:10px;border-radius:50%;background:#fca5a5;display:inline-block"></span>Lost</span>
                </div>
            </div>
            <div class="da-card-body" id="daTrendBody">
                <div class="da-skel" style="height:160px"></div>
            </div>
        </div>

        {{-- ── Branch + Team Performance ── --}}
        <div class="da-half">
            <div class="da-card">
                <div class="da-card-head">
                    <div class="da-card-title">🏢 Branch-wise Performance</div>
                    <span class="da-badge">Highest → Lowest</span>
                </div>
                <div id="daBranchBody" style="overflow-x:auto">
                    <div style="padding:16px"><div class="da-skel" style="height:200px"></div></div>
                </div>
            </div>
            <div class="da-card">
                <div class="da-card-head">
                    <div class="da-card-title">👥 Team Performance</div>
                    <span class="da-badge">Top performers</span>
                </div>
                <div id="daTeamBody">
                    <div style="padding:16px"><div class="da-skel" style="height:200px"></div></div>
                </div>
            </div>
        </div>

        {{-- ── Call Updates (8 cols) + Reminders (4 cols) ── --}}
        <div class="da-two-col">
            <div class="da-card">
                <div class="da-card-head">
                    <div class="da-card-title">📞 Recent Call Updates</div>
                    <a href="{{ route('leads.calls.index') }}" style="font-size:12px;font-weight:700;color:var(--da-orange);text-decoration:none">Show all →</a>
                </div>
                <div id="daFollowupBody" style="max-height:400px;overflow-y:auto">
                    <div style="padding:16px"><div class="da-skel" style="height:180px"></div></div>
                </div>
            </div>
            <div class="da-card">
                <div class="da-card-head">
                    <div class="da-card-title">🔔 Reminders</div>
                    <div style="display:flex;gap:10px;align-items:center">
                        <div id="daReminderBadge" style="display:flex;gap:6px;align-items:center">
                            <span class="da-badge">–</span>
                        </div>
                        <a href="{{ route('tasks.index') }}" style="font-size:12px;font-weight:700;color:var(--da-orange);text-decoration:none">Show all →</a>
                    </div>
                </div>
                <div id="daReminderBody" style="max-height:400px;overflow-y:auto">
                    <div style="padding:16px"><div class="da-skel" style="height:180px"></div></div>
                </div>
            </div>
        </div>

        {{-- ── Recent Leads ── --}}
        <div class="da-card">
            <div class="da-card-head">
                <div class="da-card-title">📋 Recent Leads</div>
                <a href="{{ route('leads.index') }}" style="font-size:12px;font-weight:700;color:var(--da-orange);text-decoration:none">View all →</a>
            </div>
            <div style="overflow-x:auto" id="daRecentBody">
                <div style="padding:16px"><div class="da-skel" style="height:200px"></div></div>
            </div>
        </div>

    </div>{{-- /da-body --}}
</div>{{-- /da-page --}}
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
(function () {
'use strict';

/* ═══════════════════════════════════════════════════════
   CONFIG
═══════════════════════════════════════════════════════ */
var API_URL   = '{{ $apiBase ?? url("/api") }}/dashboard-data';
var API_TOKEN = '{{ $apiToken ?? "" }}';   // Server-issued Sanctum token (2h expiry)
var LEAD_BASE = '{{ $leadBase ?? url("/leads") }}';
var LEAD_PRODUCTS_BASE = '{{ route("leads.products.index") }}';
// Avatar colors
var AV_COLORS = ['#fe5f04','#7c3aed','#2563eb','#16a34a','#be123c','#0284c7','#b45309','#0f766e'];
var avColor   = function(id) { return AV_COLORS[id % AV_COLORS.length]; };

// State
var state = { quick:'month', branch:'', user:'', stage:'', source:'', dateFrom:'', dateTo:'' };
var data  = null;

function calcPresetDates(val) {
    var today = new Date();
    var fmt = function(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    };

    if (val === 'today') {
        var d = fmt(today);
        return { from: d, to: d };
    }
    if (val === 'week') {
        var mon = new Date(today);
        var dayOfWeek = today.getDay();
        var diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
        mon.setDate(today.getDate() + diff);
        var sun = new Date(mon);
        sun.setDate(mon.getDate() + 6);
        return { from: fmt(mon), to: fmt(sun) };
    }
    if (val === 'month') {
        var first = new Date(today.getFullYear(), today.getMonth(), 1);
        var last = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        return { from: fmt(first), to: fmt(last) };
    }
    if (val === 'quarter') {
        var qStartMonth = Math.floor(today.getMonth() / 3) * 3;
        var firstQ = new Date(today.getFullYear(), qStartMonth, 1);
        var lastQ = new Date(today.getFullYear(), qStartMonth + 3, 0);
        return { from: fmt(firstQ), to: fmt(lastQ) };
    }
    if (val === 'year') {
        return { from: today.getFullYear() + '-01-01', to: today.getFullYear() + '-12-31' };
    }
    return { from: '', to: '' };
}

/* ═══════════════════════════════════════════════════════
   FILTER HELPERS
═══════════════════════════════════════════════════════ */
window.onQuickDateSelect = function(val) {
    var customWrap = document.getElementById('daCustomDateWrap');
    if (val === 'custom') {
        if (customWrap) customWrap.style.display = 'inline-flex';
    } else {
        if (customWrap) customWrap.style.display = 'none';
        if (val === 'all') {
            document.getElementById('fDateFrom').value = '';
            document.getElementById('fDateTo').value   = '';
        } else {
            var dates = calcPresetDates(val);
            document.getElementById('fDateFrom').value = dates.from;
            document.getElementById('fDateTo').value   = dates.to;
        }
    }
};

window.onFilterChange = function() {
    updateFilterStyles();
};

window.applyFilters = function() {
    var qSel = document.getElementById('fQuickDate');
    state.quick = qSel ? qSel.value : 'month';
    state.branch = document.getElementById('fBranch').value;
    state.user   = document.getElementById('fUser').value;
    state.stage  = document.getElementById('fStage').value;
    state.source = document.getElementById('fSource').value;
    if (state.quick === 'custom') {
        state.dateFrom = document.getElementById('fDateFrom').value;
        state.dateTo   = document.getElementById('fDateTo').value;
    } else if (state.quick === 'all') {
        state.dateFrom = '';
        state.dateTo   = '';
    } else {
        var dates = calcPresetDates(state.quick);
        state.dateFrom = dates.from;
        state.dateTo   = dates.to;
    }
    updateFilterStyles();
    renderChips();
    dashboardLoad();
};

window.resetFilters = function() {
    var bSel = document.getElementById('fBranch');
    var defaultBranch = (bSel && bSel.options.length === 1) ? bSel.value : '';
    state = { quick:'month', branch: defaultBranch, user:'', stage:'', source:'', dateFrom:'', dateTo:'' };
    ['fBranch','fUser','fStage','fSource'].forEach(function(id) { 
        var el = document.getElementById(id);
        if (el) el.value = (id === 'fBranch' ? defaultBranch : ''); 
    });
    var qSel = document.getElementById('fQuickDate');
    if (qSel) qSel.value = 'month';
    var dates = calcPresetDates('month');
    document.getElementById('fDateFrom').value = dates.from;
    document.getElementById('fDateTo').value   = dates.to;
    var customWrap = document.getElementById('daCustomDateWrap');
    if (customWrap) customWrap.style.display = 'none';
    updateFilterStyles();
    renderChips();
    dashboardLoad();
};

// Auto-switch to custom when date input is manually picked
document.addEventListener('DOMContentLoaded', function() {
    var bSel = document.getElementById('fBranch');
    if (bSel && bSel.value) {
        state.branch = bSel.value;
    }
    var dFrom = document.getElementById('fDateFrom');
    var dTo = document.getElementById('fDateTo');
    if (dFrom) dFrom.addEventListener('change', function() {
        document.getElementById('fQuickDate').value = 'custom';
        var customWrap = document.getElementById('daCustomDateWrap');
        if (customWrap) customWrap.style.display = 'inline-flex';
    });
    if (dTo) dTo.addEventListener('change', function() {
        document.getElementById('fQuickDate').value = 'custom';
        var customWrap = document.getElementById('daCustomDateWrap');
        if (customWrap) customWrap.style.display = 'inline-flex';
    });

    // Initial dates on page load
    var initDates = calcPresetDates('month');
    if (dFrom && !dFrom.value) dFrom.value = initDates.from;
    if (dTo && !dTo.value) dTo.value = initDates.to;
});

function updateFilterStyles() {
    ['fBranch','fUser','fStage','fSource'].forEach(function(id) {
        var el = document.getElementById(id);
        el.classList.toggle('active', !!el.value);
    });
}

function buildParams() {
    var p = {};
    if (state.quick)    p.quick_date = state.quick;
    if (state.branch)   p.branch_id  = state.branch;
    if (state.user)     p.user_id    = state.user;
    if (state.stage)    p.stage      = state.stage;
    if (state.source)   p.source     = state.source;
    if (state.quick === 'custom') {
        if (state.dateFrom) p.date_from  = state.dateFrom;
        if (state.dateTo)   p.date_to    = state.dateTo;
    }
    return p;
}

function countFilters() {
    return Object.keys(buildParams()).length;
}

  function setLoading(on) {
    const el = document.getElementById('daLoading');

    if (!el) {
        console.error('daLoading element not found');
        return;
    }

    if (on) {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}

function renderChips() {
    var chips = document.getElementById('daChips');
    var labels = {
        quick_date: { label:'Period',  sel:'fQuickDate' },
        branch_id:  { label:'Branch',  sel:'fBranch' },
        user_id:    { label:'User',    sel:'fUser' },
        stage:      { label:'Stage',   sel:'fStage' },
        source:     { label:'Source',  sel:'fSource' },
        date_from:  { label:'From',    sel:'fDateFrom' },
        date_to:    { label:'To',      sel:'fDateTo' },
    };
    var params = buildParams();
    var html = '';
    Object.keys(params).forEach(function(k) {
        var cfg = labels[k]; if (!cfg) return;
        var display = params[k];
        if (cfg.sel) {
            var el = document.getElementById(cfg.sel);
            if (el && el.tagName === 'SELECT') display = el.options[el.selectedIndex]?.text || display;
        }
        html += '<span class="da-chip">' + cfg.label + ': <strong>' + display + '</strong> <span class="da-chip-rm" onclick="removeFilter(\'' + k + '\')">✕</span></span>';
    });
    chips.innerHTML = html;
    chips.classList.toggle('show', !!html);
    document.getElementById('daResetBtn').style.display = html ? 'flex' : 'none';
    var cnt = countFilters();
    var badge = document.getElementById('daFilterBadge');
    badge.textContent = cnt + ' filter' + (cnt !== 1 ? 's' : '') + ' active';
    badge.style.display = cnt > 0 ? '' : 'none';
}

window.removeFilter = function(key) {
    var map = { quick_date:'quick', branch_id:'branch', user_id:'user', stage:'stage', source:'source', date_from:'dateFrom', date_to:'dateTo' };
    var k   = map[key];
    if (k) state[k] = '';
    var selMap = { branch:'fBranch', user:'fUser', stage:'fStage', source:'fSource' };
    if (selMap[k]) document.getElementById(selMap[k]).value = '';
    if (k === 'dateFrom') document.getElementById('fDateFrom').value = '';
    if (k === 'dateTo')   document.getElementById('fDateTo').value   = '';
    if (k === 'quick') {
        var qSel = document.getElementById('fQuickDate');
        if (qSel) qSel.value = 'all';
        state.quick = 'all';
        var customWrap = document.getElementById('daCustomDateWrap');
        if (customWrap) customWrap.style.display = 'none';
    }
    updateFilterStyles();
    renderChips();
    dashboardLoad();
};

/* ═══════════════════════════════════════════════════════
   API FETCH
═══════════════════════════════════════════════════════ */
window.dashboardLoad = function() {
console.log('🔥 dashboardLoad called');
    setLoading(true);
    hideError();

    var btn = document.getElementById('daRefreshBtn');
    btn.classList.add('spinning');

    var params = buildParams();
    var qs = Object.keys(params).map(function(k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
    }).join('&');
    var url = API_URL + (qs ? '?' + qs : '');

    var headers = {
        'Accept':           'application/json',
        'Content-Type':     'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization':    'Bearer ' + API_TOKEN
    };

    console.log("Fetch URL"+url);

    fetch(url, { headers: headers, credentials: 'same-origin' })
        .then(function(res) {
            console.log('STATUS:', res.status);
            if (res.status === 401) {
                window.location.href = "{{ route('login') }}";
                throw new Error('Unauthenticated. Redirecting to login page...');
            }
            if (res.status === 403) throw new Error('Access denied. Super Admin role required.');
            if (!res.ok) throw new Error('Server error (' + res.status + ')');
            return res.json();
        })
        .then(function(json) {

            if (!json.success) throw new Error(json.message || 'API returned an error');
            data = json.data;
            renderAll(data);
            setLoading(false);
            btn.classList.remove('spinning');
            document.getElementById('daLastUpdated').textContent = 'Updated ' + new Date().toLocaleTimeString();
            renderChips();
        })
        .catch(function(err) {

            setLoading(false);
            btn.classList.remove('spinning');
            showError(err.message);
            console.error('[Dashboard API]', err);
        });
};






function showError(msg) {
    var el = document.getElementById('daError');
    document.getElementById('daErrorText').textContent = msg;
    el.classList.add('show');
}
function hideError() {
    document.getElementById('daError').classList.remove('show');
}

/* ═══════════════════════════════════════════════════════
   RENDER ALL SECTIONS
═══════════════════════════════════════════════════════ */
function renderAll(d) {
    renderKpis(d.kpis, d.filters_applied);
    renderTargetStats(d.sales_target_stats);
    renderFinancials(d.financials);
    renderFunnel(d.pipeline_funnel);
    renderSources(d.source_distribution, d.financials.payment_by_mode);
    renderTrend(d.month_trend);
    renderBranchPerf(d.branch_performance);
    renderTeamPerf(d.team_performance);
    renderFollowups(d.today_followups);
    renderReminders(d.reminders);
    renderRecentLeads(d.recent_leads);
}

/* ── Sales Target Stats ── */
function renderTargetStats(ts) {
    const cardEl = document.getElementById('daTargetCard');
    if (!cardEl) return;

    if (!ts || ts.target <= 0) {
        cardEl.style.display = 'none';
        return;
    }
    cardEl.style.display = 'block';

    const targetVal = ts.target;
    const achievedVal = ts.achieved;
    const pendingVal = ts.pending;
    const percentVal = ts.percent;
    const targetName = ts.name;

    // Update text content
    document.getElementById('daTargetTitle').textContent = ts.title || (ts.is_individual ? `${targetName}'s Target` : 'Overall Sales Target');
    document.getElementById('lblTargetVal').textContent = fmt(targetVal);
    document.getElementById('lblAchievedVal').textContent = fmt(achievedVal);
    document.getElementById('lblPendingVal').textContent = fmt(pendingVal);
    document.getElementById('lblPercentVal').textContent = `${percentVal}%`;
    document.getElementById('lblLinearProgressPct').textContent = `${percentVal}%`;

    // Update circular progress gauge
    const circle = document.getElementById('circleProgress');
    if (circle) {
        // Circumference of r=50 circle is 2 * PI * r = 314.16
        const circumference = 314.16;
        // Clamp percentage for progress visualization, but show real percentage in text
        const visualPercent = Math.min(100, Math.max(0, percentVal));
        const offset = circumference - (visualPercent / 100) * circumference;
        circle.style.strokeDashoffset = offset;

        // Change gradient / stroke color based on target levels
        if (percentVal >= 100) {
            circle.setAttribute('stroke', 'url(#tgtGradSuccess)');
        } else if (percentVal >= 50) {
            circle.setAttribute('stroke', 'url(#tgtGradNormal)');
        } else {
            circle.setAttribute('stroke', 'url(#tgtGradLow)');
        }
    }

    // Update linear progress bar width & background gradient/color
    const bar = document.getElementById('barProgress');
    if (bar) {
        bar.style.width = `${Math.min(100, percentVal)}%`;
        if (percentVal >= 100) {
            bar.style.background = 'linear-gradient(90deg, #10b981, #059669)';
            bar.style.boxShadow = '0 0 8px rgba(16, 185, 129, 0.4)';
        } else if (percentVal >= 50) {
            bar.style.background = 'linear-gradient(90deg, var(--da-orange), #16a34a)';
            bar.style.boxShadow = 'none';
        } else {
            bar.style.background = 'linear-gradient(90deg, var(--da-red), var(--da-orange))';
            bar.style.boxShadow = 'none';
        }
    }

    // Dynamic color coding and label for status text
    const statusLabel = document.getElementById('lblStatusText');
    if (statusLabel) {
        if (percentVal >= 100) {
            statusLabel.textContent = '🚀 Target Achieved!';
            statusLabel.style.color = '#065f46';
            statusLabel.style.background = '#d1fae5';
            statusLabel.style.border = '1px solid #10b981';
        } else if (percentVal >= 50) {
            statusLabel.textContent = '📈 On Track';
            statusLabel.style.color = '#9a3412';
            statusLabel.style.background = '#ffedd5';
            statusLabel.style.border = '1px solid #fed7aa';
        } else {
            statusLabel.textContent = '⚠️ Target Pending';
            statusLabel.style.color = '#991b1b';
            statusLabel.style.background = '#fee2e2';
            statusLabel.style.border = '1px solid #fecaca';
        }
    }
}

/* ── Helpers ── */
function fmt(n) { return '₹' + parseFloat(n || 0).toLocaleString('en-IN', { minimumFractionDigits:2, maximumFractionDigits:2 }); }
function fmtL(n) { var v = parseFloat(n || 0); return v >= 100000 ? '₹' + (v/100000).toFixed(1) + 'L' : fmt(v); }
function pill(text, bg, color, border) { return '<span class="da-pill" style="background:' + bg + ';color:' + color + ';border-color:' + (border||bg) + '">' + text + '</span>'; }
function empty(icon, title) { return '<div class="da-empty"><div class="da-empty-ico">' + icon + '</div><div class="da-empty-title">' + title + '</div></div>'; }

/* ── KPIs ── */
function renderKpis(k, filters) {
    var period = filters.quick_date ? ({ all:'All Time', today:'Today', week:'This Week', month:'This Month', quarter:'This Quarter', year:'This Year' })[filters.quick_date] || '' : (filters.date_from ? filters.date_from + ' → ' + (filters.date_to || '…') : 'All Time');
    document.getElementById('daKpiPeriod').textContent = period;

    var gradients = {
        orange: 'linear-gradient(135deg, #fe5f04 0%, #ff8c42 100%)',
        blue:   'linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%)',
        green:  'linear-gradient(135deg, #047857 0%, #10b981 100%)',
        red:    'linear-gradient(135deg, #b91c1c 0%, #ef4444 100%)',
        purple: 'linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%)',
        teal:   'linear-gradient(135deg, #0f766e 0%, #0d9488 100%)',
        amber:  'linear-gradient(135deg, #b45309 0%, #f59e0b 100%)',
        rose:   'linear-gradient(135deg, #be123c 0%, #f43f5e 100%)'
    };

    var kpis = [
        { accent:'orange', val:k.total_leads,    label:'Overall Leads Count', sub:'All in scope',
          clickAction: "viewAllLeads()",
          svg:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' },
        { accent:'blue',   val:k.won_leads,      label:'Active Customers', sub:'Converted leads count',
          clickAction: "viewConvertedLeads()",
          svg:'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>' },
        { accent:'green',  val:k.converted_products_count, label:'Converted Products', sub:'Total Converted Products',
          clickAction: "viewConvertedProducts()",
          svg:'<polyline points="20 6 9 17 4 12"/>' },
        { accent:'purple', val:fmtL(k.upcoming_amount), label:'Upcoming Amount', sub:'Products without converted',
          svg:'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>' },
        { accent:'teal',   val:fmtL(k.converted_value), label:'Converted Value',  sub:'Total products value',
          svg:'<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>' },
        { accent:'amber',  val:k.converted_percentage + '%', label:'Converted Percentage', sub:'Converted ÷ Total Products',
          svg:'<path d="M3 16l4-4 4 4 4-6 4 4"/>' },
        { accent:'orange', val:(k.today_reminders_count !== undefined ? k.today_reminders_count : (k.scheduled_followups_count !== undefined ? k.scheduled_followups_count : 0)), label:'Today Remainders', sub:'Reminders set for today',
          svg:'<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>', clickAction: "navigateToTasks('today')" },
        { accent:'rose',   val:(k.overdue_reminders_count !== undefined ? k.overdue_reminders_count : 0), label:'Overdue Reminders', sub:'Pending reminders past due',
          svg:'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>', clickAction: "navigateToTasks('overdue')" },
        { accent:'teal',   val:(k.today_completed_calls_count !== undefined ? k.today_completed_calls_count : 0), label:'Today Completed Calls', sub:'Call updates logged today',
          svg:'<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>', clickAction: "navigateToTodayCalls()" },
    ];

    var html = kpis.map(function(kpi) {
        var grad = gradients[kpi.accent] || gradients.orange;
        var clickAttr = kpi.clickAction ? ' onclick="' + kpi.clickAction + '" style="background:' + grad + ';cursor:pointer;" title="Click to view details"' : ' style="background:' + grad + ';"';
        return '<div class="da-kpi"' + clickAttr + '>' +
            '<div class="da-kpi-icon">' +
            '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#ffffff" stroke-width="2">' + kpi.svg + '</svg></div>' +
            '<div class="da-kpi-val">' + kpi.val + '</div>' +
            '<div class="da-kpi-lbl">' + kpi.label + '</div>' +
            '<div class="da-kpi-sub">' + kpi.sub + '</div></div>';
    }).join('');
    document.getElementById('daKpiGrid').innerHTML = html;
}

/* ── Financials ── */
function renderFinancials(f) {
    var cards = [
        { cls:'fc-total',   bc:'#fe5f04', label:'Total Product Value',  val:f.total_product_value,  sub:f.payment_percent + 'Total', bar:100 },
        { cls:'fc-paid',    bc:'#16a34a', label:'Amount Received',       val:f.amount_paid,          sub:f.payment_percent + 'Collected',  bar:f.payment_percent },
        { cls:'fc-pending', bc:'#dc2626', label:'Amount Pending',        val:f.amount_pending,       sub:'Outstanding balance',              bar:Math.max(0,100-f.payment_percent) },
        { cls:'fc-conv',    bc:'#7c3aed', label:'Converted Products',    val:f.converted_value,      sub:f.converted_count + ' Product(s)',  bar: f.total_product_value > 0 ? Math.round(f.converted_value/f.total_product_value*100) : 0, clickAction: "viewConvertedProducts()" },
    ];
    var html = cards.map(function(c) {
        var clickAttr = c.clickAction ? ' onclick="' + c.clickAction + '" style="border-left-color:' + c.bc + ';cursor:pointer;" title="Click to view Converted Products"' : ' style="border-left-color:' + c.bc + '"';
        return '<div class="da-fin"' + clickAttr + '>' +
            '<div class="da-fin-lbl">' + c.label + '</div>' +
            '<div class="da-fin-val" style="color:' + c.bc + '">' + fmt(c.val) + '</div>' +
            '<div class="da-fin-sub">' + c.sub + '</div>' +
            '<div class="da-fin-bar-outer"><div class="da-fin-bar-inner" style="width:' + Math.min(100, Math.max(0,c.bar)) + '%;background:' + c.bc + '"></div></div></div>';
    }).join('');
    document.getElementById('daFinGrid').innerHTML = html;
}

function getFilterDates() {
    var from = state.dateFrom || '';
    var to = state.dateTo || '';

    if (!from && !to && state.quick) {
        var now = new Date();
        var y = now.getFullYear();
        var m = now.getMonth();
        var pad = function(n) { return String(n).padStart(2, '0'); };
        var formatDate = function(d) {
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        };

        if (state.quick === 'today') {
            from = formatDate(now);
            to = formatDate(now);
        } else if (state.quick === 'week') {
            var day = now.getDay();
            var diffToMon = (day + 6) % 7;
            var mon = new Date(y, m, now.getDate() - diffToMon);
            var sun = new Date(y, m, now.getDate() - diffToMon + 6);
            from = formatDate(mon);
            to = formatDate(sun);
        } else if (state.quick === 'month') {
            var first = new Date(y, m, 1);
            var last = new Date(y, m + 1, 0);
            from = formatDate(first);
            to = formatDate(last);
        } else if (state.quick === 'quarter') {
            var qStartMonth = Math.floor(m / 3) * 3;
            var firstQ = new Date(y, qStartMonth, 1);
            var lastQ = new Date(y, qStartMonth + 3, 0);
            from = formatDate(firstQ);
            to = formatDate(lastQ);
        } else if (state.quick === 'year') {
            var firstY = new Date(y, 0, 1);
            var lastY = new Date(y, 11, 31);
            from = formatDate(firstY);
            to = formatDate(lastY);
        }
    }

    return { from: from, to: to };
}

window.viewAllLeads = function() {
    var dates = getFilterDates();
    var params = new URLSearchParams();
    if (state.quick) {
        params.set('quick_date', state.quick);
    }
    if (dates.from) {
        params.set('date_from', dates.from);
    }
    if (dates.to) {
        params.set('date_to', dates.to);
    }
    if (state.branch) {
        params.set('branch_id', state.branch);
    }
    if (state.user) {
        params.set('assigned_to', state.user);
    }
    if (state.source) {
        params.set('lead_source', state.source);
    }
    if (state.stage) {
        params.set('lead_status', state.stage);
    }

    window.location.href = LEAD_BASE + '?' + params.toString();
};

window.viewConvertedLeads = function() {
    var dates = getFilterDates();
    var params = new URLSearchParams();
    params.set('lead_status', 'converted');
    if (state.quick) {
        params.set('quick_date', state.quick);
    }
    if (dates.from) {
        params.set('date_from', dates.from);
    }
    if (dates.to) {
        params.set('date_to', dates.to);
    }
    if (state.branch) {
        params.set('branch_id', state.branch);
    }
    if (state.user) {
        params.set('assigned_to', state.user);
    }

    window.location.href = '{{ url("/leads") }}?' + params.toString();
};

window.viewConvertedProducts = function() {
    var dates = getFilterDates();
    var params = new URLSearchParams();
    params.set('product_status', 'converted');
    if (state.quick) {
        params.set('quick_date', state.quick);
    }
    if (dates.from) {
        params.set('date_from', dates.from);
    }
    if (dates.to) {
        params.set('date_to', dates.to);
    }
    if (state.branch) {
        params.set('branch_id', state.branch);
    }
    if (state.user) {
        params.set('assigned_to', state.user);
    }

    window.location.href = LEAD_PRODUCTS_BASE + '?' + params.toString();
};

window.navigateToTasks = function(tab) {
    var params = new URLSearchParams();
    params.set('tab', tab || 'today');
    if (state.branch) {
        params.set('branch_id', state.branch);
    }
    if (state.user) {
        params.set('user_id', state.user);
    }
    window.location.href = '{{ route("tasks.index") }}?' + params.toString();
};

window.navigateToTodayCalls = function() {
    var now = new Date();
    var pad = function(n) { return String(n).padStart(2, '0'); };
    var todayStr = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
    var params = new URLSearchParams();
    params.set('date_from', todayStr);
    params.set('date_to', todayStr);
    if (state.branch) {
        params.set('branch_id', state.branch);
    }
    if (state.user) {
        params.set('user_id', state.user);
    }
    window.location.href = '{{ route("leads.calls.index") }}?' + params.toString();
};

window.navigateToFunnelStage = function(stageVal) {
    var dates = getFilterDates();
    var params = new URLSearchParams();

    if (stageVal) {
        params.set('product_status', stageVal);
    }
    if (state.quick) {
        params.set('quick_date', state.quick);
    }
    if (dates.from) {
        params.set('date_from', dates.from);
    }
    if (dates.to) {
        params.set('date_to', dates.to);
    }
    if (state.branch) {
        params.set('branch_id', state.branch);
    }
    if (state.user) {
        params.set('assigned_to', state.user);
    }

    window.location.href = LEAD_PRODUCTS_BASE + '?' + params.toString();
};

/* ── Pipeline Funnel ── */
function renderFunnel(f) {
    if (!f || !f.stages || !Array.isArray(f.stages) || f.stages.length === 0) {
        if (document.getElementById('daFunnelTotal')) document.getElementById('daFunnelTotal').textContent = '0 total';
        if (document.getElementById('daFunnelBody')) {
            document.getElementById('daFunnelBody').innerHTML = '<div style="padding:24px;text-align:center;color:#94a3b8;font-size:13px">No pipeline funnel data available.</div>';
        }
        return;
    }

    if (document.getElementById('daFunnelTotal')) {
        document.getElementById('daFunnelTotal').textContent = (f.total || 0) + ' total';
    }

    var counts = f.stages.map(function(s) { return Number(s.count) || 0; });
    var maxCount = counts.length ? Math.max.apply(null, counts) : 1;
    if (!maxCount || maxCount <= 0 || !isFinite(maxCount)) maxCount = 1;

    var rows = f.stages.map(function(s) {
        var cnt  = Number(s.count) || 0;
        var barW = Math.min(100, Math.max(0, Math.round(cnt / maxCount * 100)));
        var c    = s.color || { bg: '#eff6ff', text: '#2563eb', border: '#bfdbfe' };
        var textColor = c.text || '#2563eb';
        var bgColor = c.bg || '#eff6ff';
        var borderColor = c.border || '#bfdbfe';

        var stageKey = s.key || s.id || s.label || '';
        var safeKey  = String(stageKey).replace(/'/g, "\\'");

        return '<div class="da-funnel-row" onclick="navigateToFunnelStage(\'' + safeKey + '\')" style="cursor:pointer;transition:transform .15s ease;" title="Click to view ' + (s.label || 'Stage') + ' leads">' +
            '<div class="da-funnel-top">' +
            '<div class="da-funnel-label" style="color:' + textColor + ';cursor:pointer;">' +
            (s.label || 'Stage') +
            '</div>' +
            '<div class="da-funnel-right">' +
            '<span class="da-funnel-pct" style="background:' + bgColor + ';color:' + textColor + ';border:1px solid ' + borderColor + '">' + (s.percent || 0) + '%</span>' +
            '<span class="da-funnel-count" style="color:' + textColor + ';">' + cnt + '</span></div></div>' +
            '<div class="da-bar-outer"><div class="da-bar-inner" style="width:' + barW + '%;background:' + textColor + '"></div></div></div>';
    }).join('');

    var widths = [100, 85, 72, 60, 50, 40];
    var visual = '<div class="da-funnel-visual"><div class="da-funnel-visual-title">Visual Funnel</div>' +
        f.stages.map(function(s, i) {
            var w  = widths[i] || 35;
            var ml = (100 - w) / 2;
            var c  = s.color || { text: '#fe5f04' };
            var textColor = c.text || '#fe5f04';
            var cnt = Number(s.count) || 0;
            var stageKey = s.key || s.id || s.label || '';
            var safeKey  = String(stageKey).replace(/'/g, "\\'");

            return '<div class="da-funnel-step" onclick="navigateToFunnelStage(\'' + safeKey + '\')" style="margin-left:' + ml + '%;width:' + w + '%;margin-bottom:3px;cursor:pointer;" title="Click to view ' + (s.label || 'Stage') + ' leads">' +
                '<div class="da-funnel-step-inner" style="background:' + textColor + ';opacity:' + (0.65 + i * 0.07) + '">' +
                '<span>' + (s.label || 'Stage') + '</span><span>' + cnt + '</span></div></div>';
        }).join('') + '</div>';

    if (document.getElementById('daFunnelBody')) {
        document.getElementById('daFunnelBody').innerHTML = rows + visual;
    }
}

window.navigateToSource = function(sourceVal) {
    var dates = getFilterDates();
    var params = new URLSearchParams();

    if (sourceVal) {
        params.set('lead_source', sourceVal);
    }
    if (state.quick) {
        params.set('quick_date', state.quick);
    }
    if (dates.from) {
        params.set('date_from', dates.from);
    }
    if (dates.to) {
        params.set('date_to', dates.to);
    }
    if (state.branch) {
        params.set('branch_id', state.branch);
    }
    if (state.user) {
        params.set('assigned_to', state.user);
    }
    if (state.stage) {
        params.set('lead_status', state.stage);
    }

    window.location.href = LEAD_BASE + '?' + params.toString();
};

/* ── Source Distribution ── */
function renderSources(sd, payModes) {
    document.getElementById('daSourceTotal').textContent = sd.total + ' leads';
    var emojiMap = { reference:'👥', ad_campaign:'📢', direct_visit:'🚶', invitation:'💌', cold_outreach:'📞', social_media:'📱', website:'🌐' };
    var colorMap = { reference:'#2563eb', ad_campaign:'#dc2626', direct_visit:'#16a34a', invitation:'#7c3aed', cold_outreach:'#b45309', social_media:'#0284c7', website:'#059669' };
    var maxSrc   = Math.max.apply(null, sd.sources.map(function(s) { return s.count; })) || 1;

    var rows = sd.sources.map(function(s) {
        var barW  = Math.round(s.count / maxSrc * 100);
        var color = colorMap[s.key] || '#7c7c7c';
        var emoji = emojiMap[s.key] || '📌';
        var sourceKey = s.key || s.id || s.label || '';
        var safeKey  = String(sourceKey).replace(/'/g, "\\'");

        return '<div class="da-source-row" onclick="navigateToSource(\'' + safeKey + '\')" style="cursor:pointer;transition:transform .15s ease;" title="Click to view ' + (s.label || 'Source') + ' leads">' +
            '<div class="da-source-top">' +
            '<div class="da-source-label" style="cursor:pointer;"><span>' + emoji + '</span>' + (s.label || 'Source') + '</div>' +
            '<div style="display:flex;align-items:center;gap:7px">' +
            '<span style="font-size:10px;color:var(--da-muted);font-weight:600">' + (s.percent || 0) + '%</span>' +
            '<span style="font-size:14px;font-weight:800;color:' + color + ';">' + s.count + '</span></div></div>' +
            '<div class="da-bar-outer" style="height:6px"><div class="da-bar-inner" style="width:' + barW + '%;background:' + color + '"></div></div></div>';
    }).join('');

    // Payment modes mini section
    var modeIcons  = { cash:'💵', bank_transfer:'🏦', cheque:'📝', upi:'📱', card:'💳' };
    var modeColors = { cash:'#16a34a', bank_transfer:'#2563eb', cheque:'#7c3aed', upi:'#ea580c', card:'#0284c7' };
    var modeHtml   = '';
    if (payModes && payModes.length) {
        modeHtml = '<div style="margin-top:18px;padding-top:14px;border-top:1px solid #f3f0f6">' +
            '<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--da-muted);margin-bottom:10px">Payments by Mode</div>' +
            payModes.map(function(pm) {
                return '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">' +
                    '<div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#374151">' +
                    '<span>' + (modeIcons[pm.mode] || '💰') + '</span>' + pm.mode_label +
                    '<span style="font-size:10px;color:var(--da-muted)">(' + pm.txn_count + ')</span></div>' +
                    '<span style="font-size:13px;font-weight:800;color:' + (modeColors[pm.mode] || '#374151') + '">' + fmt(pm.total) + '</span></div>';
            }).join('') + '</div>';
    }
    document.getElementById('daSourceBody').innerHTML = rows + modeHtml;
}

/* ── 6-Month Trend ── */
function renderTrend(months) {
    var maxVal = Math.max.apply(null, months.map(function(m) { return m.total; })) || 1;
    var cols   = months.map(function(m) {
        var bh = Math.round(m.total / maxVal * 100);
        var wh = Math.round(m.convert  / maxVal * 100);
        var lh = Math.round(m.lost / maxVal * 100);
        return '<div class="da-trend-col">' +
            '<div class="da-trend-total">' + m.total + '</div>' +
            '<div class="da-trend-bars">' +
            '<div class="da-trend-bar" style="height:' + bh + '%;background:var(--da-orange)" title="Total:' + m.total + '"></div>' +
            '<div class="da-trend-bar" style="height:' + wh + '%;background:var(--da-green)"  title="Convert:' + m.convert + '"></div>' +
            '<div class="da-trend-bar" style="height:' + lh + '%;background:#fca5a5"          title="Lost:'  + m.lost  + '"></div>' +
            '</div><div class="da-trend-lbl">' + m.month_short + '</div></div>';
    }).join('');

    var vals = months.map(function(m) {
        return '<div class="da-trend-val-item"><div class="da-trend-val-lbl">Convert ₹</div><div class="da-trend-val-num">' + (m.convert_value >= 100000 ? (m.convert_value/100000).toFixed(1)+'L' : Math.round(m.convert_value).toLocaleString('en-IN')) + '</div></div>';
    }).join('');

    document.getElementById('daTrendBody').innerHTML =
        '<div class="da-trend-cols">' + cols + '</div>' +
        '<div class="da-trend-values">' + vals + '</div>';
}

/* ── Branch Performance ── */
function renderBranchPerf(branches) {
    if (!branches.length) { document.getElementById('daBranchBody').innerHTML = empty('🏢','No branch data'); return; }
    var rankColors = ['#fe5f04','#7c3aed','#2563eb','#16a34a','#b45309'];
    var maxVal = branches.reduce(function(m, b) {
        var val = b.converted_value !== undefined ? b.converted_value : (b.won_value || 0);
        return Math.max(m, val);
    }, 1);

    var rows = branches.map(function(b, i) {
        var rc        = rankColors[i] || '#9ca3af';
        var convVal   = b.converted_value !== undefined ? b.converted_value : (b.won_value || 0);
        var convCnt   = b.converted_count !== undefined ? b.converted_count : (b.converted_leads !== undefined ? b.converted_leads : (b.won_leads || 0));
        var convPct   = b.converted_percentage !== undefined ? b.converted_percentage : (b.conversion_rate || 0);
        var bpct      = Math.round(convVal / maxVal * 100);

        return '<tr>' +
            '<td><div class="da-rank" style="background:' + rc + '20;color:' + rc + '">' + (i+1) + '</div></td>' +
            '<td><div style="font-size:13px;font-weight:700;color:var(--da-text)">' + b.branch_name + '</div>' +
            '<div style="height:3px;background:#f0eef2;border-radius:2px;margin-top:5px;width:100%"><div style="height:100%;width:' + bpct + '%;background:' + rc + ';border-radius:2px"></div></div></td>' +
            '<td style="text-align:right;font-weight:700;color:#374151">' + b.total_leads + '</td>' +
            '<td style="text-align:right;font-weight:700;color:var(--da-green)">' + convCnt + '</td>' +
            '<td style="text-align:right;font-weight:800;color:' + rc + '">' + fmtL(convVal) + '</td>' +
            '<td style="text-align:right">' +
            '<span style="font-size:11px;font-weight:700;padding:2px 7px;border-radius:20px;background:' + (convPct>=50?'#f0fdf4':'#fffbeb') + ';color:' + (convPct>=50?'#16a34a':'#b45309') + '">' + convPct + '%</span>' +
            '</td></tr>';
    }).join('');

    document.getElementById('daBranchBody').innerHTML =
        '<table class="da-perf-tbl"><thead><tr>' +
        '<th>#</th><th>Branch</th><th style="text-align:right">Leads</th><th style="text-align:right">Converted Count</th><th style="text-align:right">Converted Value</th><th style="text-align:right">Converted %</th>' +
        '</tr></thead><tbody>' + rows + '</tbody></table>';
}

/* ── Team Performance ── */
function renderTeamPerf(team) {
    if (!team.length) { document.getElementById('daTeamBody').innerHTML = empty('👥','No team data for selected filters'); return; }
    var maxVal = team.reduce(function(m, u) { return Math.max(m, u.convert_value); }, 1);

    var rows = team.map(function(u, i) {
        var mc   = avColor(u.user_id);
        var tpct = Math.round(u.convert_value / maxVal * 100);
        return '<tr>' +
            '<td><div class="da-rank" style="background:' + mc + '20;color:' + mc + '">' + (i+1) + '</div></td>' +
            '<td><div style="display:flex;align-items:center;gap:8px">' +
            '<div class="da-member-av" style="background:' + mc + '">' + (u.user_name ? u.user_name.charAt(0).toUpperCase() : '?') + '</div>' +
            '<div><div style="font-size:12px;font-weight:700;color:var(--da-text)">' + u.user_name + '</div>' +
            '<div style="font-size:10px;color:var(--da-muted)">' + (u.role || 'Staff') + '</div></div></div>' +
            '<div style="height:3px;background:#f0eef2;border-radius:2px;margin-top:6px"><div style="height:100%;width:' + tpct + '%;background:' + mc + ';border-radius:2px"></div></div></td>' +
            '<td style="text-align:right;font-weight:700;color:#374151">' + u.total_leads + '</td>' +
            '<td style="text-align:right"><span style="color:var(--da-green);font-weight:700">' + u.convert_leads + ' C</span> <span style="color:var(--da-red);font-weight:700">' + u.lost_leads + 'L</span></td>' +
            '<td style="text-align:right;font-weight:800;color:' + mc + '">' + fmtL(u.convert_value) + '</td></tr>';
    }).join('');

    document.getElementById('daTeamBody').innerHTML =
        '<table class="da-perf-tbl"><thead><tr>' +
        '<th>#</th><th>Member</th><th style="text-align:right">Leads</th><th style="text-align:right">C/L</th><th style="text-align:right">Convert Value</th>' +
        '</tr></thead><tbody>' + rows + '</tbody></table>';
}

/* ── Recent Call Updates ── */
function renderFollowups(fu) {
    if (!fu || !fu.items || !fu.items.length) {
        document.getElementById('daFollowupBody').innerHTML = empty('📭','No recent call updates');
        return;
    }

    var html = fu.items.map(function(f) {
        var oc = f.outcome_color || { bg:'#f5f4f6', text:'#7c7c7c' };
        var leadId = f.lead?.id;
        var clickAttr = leadId ? 'onclick="window.location=\'' + LEAD_BASE + '/' + leadId + '\'" style="cursor:pointer;"' : '';

        var callTimeStr = '';
        if (f.called_at_formatted) {
            callTimeStr = f.called_at_formatted;
        } else if (f.called_at) {
            var parsed = new Date(f.called_at);
            if (!isNaN(parsed.getTime())) {
                callTimeStr = parsed.toLocaleDateString([], {month:'short', day:'numeric'}) + ' ' + parsed.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
            } else {
                callTimeStr = f.called_at;
            }
        }

        return '<div class="da-followup-item" ' + clickAttr + '>' +
            '<div class="da-followup-dot" style="background:' + oc.text + '"></div>' +
            '<div class="da-followup-body">' +
            '<div class="da-followup-company" style="font-weight:700;color:var(--da-dark);font-size:14px">' + (f.lead?.company_name || f.lead?.contact_name || ('Lead #' + (f.lead?.id || ''))) + '</div>' +
            '<div class="da-followup-meta">' + (f.lead?.contact_name || '') + (f.lead?.mobile_number ? ' · ' + f.lead.mobile_number : '') + '</div>' +
            '<div class="da-followup-tags" style="margin-top:4px;display:flex;align-items:center;gap:5px;flex-wrap:wrap;">' +
            '<span class="da-pill" style="font-size:10px;background:' + oc.bg + ';color:' + oc.text + '">' + (f.outcome_label || f.outcome || 'Call Update') + '</span>' +
            (f.outcome_subcategory_label ? '<span class="da-pill" style="font-size:10px;background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe">' + f.outcome_subcategory_label + '</span>' : '') +
            (f.notes ? '<span style="font-size:11px;color:var(--da-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px">' + f.notes + '</span>' : '') +
            '</div>' +
            '<div style="font-size:11px;color:var(--da-muted);margin-top:4px">' +
            (f.lead?.assigned_to?.name ? 'Assigned to: <strong>' + f.lead.assigned_to.name + '</strong>' : '') +
            (f.logged_by?.name ? ' · By: ' + f.logged_by.name : '') +
            '</div>' +
            '</div>' +
            '<div style="margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:4px">' +
            (callTimeStr ? '<div class="da-followup-time" style="font-weight:600">' + callTimeStr + '</div>' : (f.next_follow_up ? '<div class="da-followup-time" style="font-weight:600">' + f.next_follow_up + '</div>' : '')) +
            (leadId ? '<span style="font-size:11px;color:var(--da-orange);font-weight:700">View Lead →</span>' : '') +
            '</div></div>';
    }).join('');
    document.getElementById('daFollowupBody').innerHTML = html;
}

/* ── Reminders ── */
var _remindersData = null;
var _activeReminderTab = 'today';

window.switchReminderTab = function(tab, shouldScroll) {
    _activeReminderTab = tab;
    if (_remindersData) {
        drawRemindersList(_remindersData);
    }
    if (shouldScroll) {
        var card = document.getElementById('daReminderBody');
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};

function renderReminders(r) {
    _remindersData = r || {};
    drawRemindersList(_remindersData);
}

function drawRemindersList(r) {
    r = r || {};
    var todayCnt = r.today_count !== undefined ? r.today_count : (r.items ? r.items.length : 0);
    var overdueCnt = r.overdue_count !== undefined ? r.overdue_count : (r.overdue_items ? r.overdue_items.length : 0);

    var badgeContainer = document.getElementById('daReminderBadge');
    if (badgeContainer) {
        badgeContainer.innerHTML =
            '<button type="button" onclick="switchReminderTab(\'today\')" style="cursor:pointer;border:none;border-radius:12px;padding:3px 8px;font-size:11px;font-weight:700;' +
            (_activeReminderTab === 'today' ? 'background:var(--da-orange);color:#fff;' : 'background:#f5f4f6;color:var(--da-dark);') + '">Today (' + todayCnt + ')</button>' +
            '<button type="button" onclick="switchReminderTab(\'overdue\')" style="cursor:pointer;border:none;border-radius:12px;padding:3px 8px;font-size:11px;font-weight:700;' +
            (_activeReminderTab === 'overdue' ? 'background:var(--da-red);color:#fff;' : 'background:#fef2f2;color:var(--da-red);') + '">Overdue (' + overdueCnt + ')</button>';
    }

    var items = _activeReminderTab === 'overdue' ? (r.overdue_items || []) : (r.items || []);

    if (!items.length) {
        var emptyMsg = _activeReminderTab === 'overdue' ? 'No overdue reminders' : 'No pending reminders today';
        document.getElementById('daReminderBody').innerHTML = empty(_activeReminderTab === 'overdue' ? '🎉' : '✅', emptyMsg);
        return;
    }

    var priBg  = { low:'#f0fdf4', medium:'#fffbeb', high:'#fef2f2' };
    var priClr = { low:'#16a34a', medium:'#b45309', high:'#dc2626' };

    var html = items.map(function(rem) {
        var bg  = priBg[rem.priority]  || '#f5f4f6';
        var clr = priClr[rem.priority] || '#7c7c7c';
        var overdue = rem.is_overdue || _activeReminderTab === 'overdue';
        var leadId = rem.lead?.id;
        var dateStr = '—';
        if (rem.remind_at) {
            var parsedDate = new Date(rem.remind_at);
            if (!isNaN(parsedDate.getTime())) {
                dateStr = parsedDate.toLocaleDateString([], {month:'short', day:'numeric'}) + ' ' + parsedDate.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
            } else {
                dateStr = rem.remind_at;
            }
        }

        return '<div class="da-rem-item" ' + (leadId ? 'onclick="window.location=\'' + LEAD_BASE + '/' + leadId + '\'" style="cursor:pointer;' + (overdue ? 'background:#fffafa' : '') + '"' : 'style="' + (overdue ? 'background:#fffafa' : '') + '"') + '>' +
            '<div class="da-rem-ico" style="background:' + (overdue ? '#fef2f2' : '#f5f4f6') + '">' + (rem.type_icon || '📌') + '</div>' +
            '<div class="da-rem-body">' +
            '<div class="da-rem-title" style="font-weight:700;color:var(--da-dark)">' + rem.title + '</div>' +
            '<div class="da-rem-meta">' + (rem.lead?.company_name ? '<strong>' + rem.lead.company_name + '</strong>' : '—') + (rem.user?.name ? ' · ' + rem.user.name : '') + '</div>' +
            '<div class="da-rem-tags">' +
            '<span style="font-size:10px;font-weight:700;color:' + (overdue ? 'var(--da-red)' : 'var(--da-muted)') + '">' + dateStr + (overdue ? ' (Overdue)' : '') + '</span>' +
            '<span class="da-pill" style="font-size:10px;background:' + bg + ';color:' + clr + '">' + (rem.priority ? (rem.priority.charAt(0).toUpperCase() + rem.priority.slice(1)) : '—') + '</span>' +
            '</div></div>' +
            (leadId ? '<div style="margin-left:auto;font-size:11px;color:var(--da-orange);font-weight:700;white-space:nowrap;padding-left:6px">View Lead →</div>' : '') +
            '</div>';
    }).join('');
    document.getElementById('daReminderBody').innerHTML = html;
}

/* ── Recent Leads ── */
function renderRecentLeads(leads) {
    if (!leads.length) { document.getElementById('daRecentBody').innerHTML = empty('📋','No recent leads'); return; }

    var rows = leads.map(function(l) {
        var pc  = l.priority_color || { bg:'#f5f4f6', text:'#7c7c7c' };
        var ac  = avColor(l.id);
        return '<tr onclick="window.location=\'' + LEAD_BASE + '/' + l.id + '\'">' +
            '<td><div style="display:flex;align-items:center;gap:8px">' +
            '<div class="da-lead-co-av" style="background:' + ac + '">' + (l.company_name ? l.company_name.charAt(0).toUpperCase() : '?') + '</div>' +
            '<div><div class="da-lead-name">' + l.company_name + '</div><div class="da-lead-contact">' + l.contact_name + '</div></div></div></td>' +
            '<td style="font-family:monospace">' + l.mobile_number + '</td>' +
            '<td>' + (l.source_label || l.lead_source || '—') + '</td>' +
            '<td>' + '<span class="da-pill" style="background:' + pc.bg + ';color:' + pc.text + '">' + l.priority_label + '</span>' + '</td>' +
            '<td style="font-weight:800">' + l.deal_value_formatted + '</td>' +
            '<td>' + (l.assigned_to?.name || '—') + '</td>' +
            '<td>' + (l.branch?.name || '—') + '</td>' +
            '<td style="color:var(--da-muted)">' + l.lead_date + '</td>' +
        '</tr>';
    }).join('');

    document.getElementById('daRecentBody').innerHTML =
        '<table class="da-leads-tbl"><thead><tr>' +
        '<th>Lead</th><th>Mobile</th><th>Source</th><th>Priority</th><th>Deal Value</th><th>Assigned To</th><th>Branch</th><th>Date</th>' +
        '</tr></thead><tbody>' + rows + '</tbody></table>';
}

/* ═══════════════════════════════════════════════════════
   BOOT
═══════════════════════════════════════════════════════ */
// Token is server-issued via SuperAdminDashboardWebController
// No localStorage needed — it's already set in API_TOKEN above



    if (typeof dashboardLoad === 'function') {
        dashboardLoad();
    } else {
        console.error('❌ dashboardLoad not found');
    }

    setInterval(function () {
        if (typeof dashboardLoad === 'function') {
            dashboardLoad();
        }
    }, 5 * 60 * 1000);



}());
});
</script>
@endpush
