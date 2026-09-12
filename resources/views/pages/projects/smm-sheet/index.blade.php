@extends('layouts.app')

@section('title', 'SMM Sheet - myAgenci.ai')

@push('styles')
<style>
/* ════════════════════════════════════════════════════════════════════
   SMM SHEET — Modern Projects Module UI
   ════════════════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.pts-page { min-height: 100%; background: linear-gradient(180deg, #f7fbff 0%, #f8fafc 100%); font-family: 'Inter', sans-serif; }
.pts-topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 22px 28px; background: #fff; border-bottom: 1px solid #e6edf5; }
.pts-title { font-size: 24px; font-weight: 900; color: #111827; }
.pts-breadcrumb { font-size: 12px; color: #64748b; margin-top: 4px; }
.pts-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.pts-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #111827; font-size: 13px; font-weight: 800; text-decoration: none; cursor: pointer; transition: all 0.2s; }
.pts-btn:hover { border-color: #fdba74; background: #fff7ed; color: #c2410c; }
.pts-btn-primary { background: #ea580c; border-color: #ea580c; color: #fff; }
.pts-btn-primary:hover { background: #c2410c; border-color: #c2410c; color: #fff; }
.pts-btn-success { background: #16a34a; border-color: #16a34a; color: #fff; }
.pts-btn-success:hover { background: #15803d; border-color: #15803d; color: #fff; }
.pts-body { padding: 22px 28px 34px; display: grid; gap: 18px; }

/* ── KPI Stats ── */
.pjd-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
.pjd-stat { position: relative; overflow: hidden; background: var(--stat-gradient); border: none; border-radius: 16px; padding: 22px 24px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.3s cubic-bezier(0.4,0,0.2,1); color: #fff; }
.pjd-stat:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15), 0 10px 10px -5px rgba(0,0,0,0.08); }
.pjd-stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.pjd-stat-icon { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,0.22); color: #fff; font-size: 18px; backdrop-filter: blur(4px); }
.pjd-stat-label { font-size: 11px; font-weight: 800; color: rgba(255,255,255,0.92); text-transform: uppercase; letter-spacing: .06em; }
.pjd-stat-body { display: flex; flex-direction: column; gap: 2px; }
.pjd-stat-value { font-size: 30px; font-weight: 900; color: #fff; line-height: 1.1; }
.pjd-stat-footer { margin-top: 12px; padding-top: 10px; border-top: 1px dashed rgba(255,255,255,0.25); font-size: 12px; color: rgba(255,255,255,0.9); font-weight: 600; }

/* ── Filter Card & Accordion ── */
.pts-card { background: #fff; border: 1px solid #e6edf5; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(15,23,42,.04); }
.pts-card-head { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 16px 20px; border-bottom: 1px solid #edf2f7; background: #fbfdff; }
.pts-card-title { font-size: 15px; font-weight: 900; color: #111827; }
.pts-card-sub { margin-top: 2px; font-size: 12px; color: #64748b; }
.pts-card-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 14px; }

.smm-filter-accordion { border-radius: 16px; transition: all 0.2s ease; }
.smm-filter-summary {
    list-style: none;
    cursor: pointer;
    user-select: none;
    transition: background 0.15s ease;
}
.smm-filter-summary::-webkit-details-marker { display: none; }
.smm-filter-summary:hover { background: #f8fafc; }
.smm-filter-summary-left { display: flex; align-items: center; gap: 10px; }
.smm-filter-icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: #fff7ed; color: #ea580c;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.smm-filter-count-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; border-radius: 999px;
    background: #ea580c; color: #fff; font-size: 11px; font-weight: 800;
    margin-left: 6px; vertical-align: middle;
}
.smm-filter-summary-right { display: flex; align-items: center; gap: 12px; }
.smm-chevron {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #64748b; transition: transform 0.2s ease;
}
details[open] .smm-chevron { transform: rotate(180deg); }

.smm-quick-filters { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.smm-quick-label { font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; margin-right: 4px; }
.smm-qbtn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 800; cursor: pointer; transition: all .15s ease; font-family: inherit; }
.smm-qbtn:hover { border-color: #fb923c; background: #fff7ed; color: #c2410c; }
.smm-qbtn.is-active { border-color: #ea580c; background: linear-gradient(135deg, #ea580c, #f97316); color: #fff; box-shadow: 0 4px 12px rgba(234,88,12,.22); }

.smm-filter-grid { display: grid; grid-template-columns: 1.2fr 1.2fr 2fr 1.5fr auto; gap: 14px; align-items: end; }
.smm-field { display: flex; flex-direction: column; gap: 5px; }
.smm-label { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #64748b; }
.smm-input, .smm-select {
    width: 100%; padding: 9px 12px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    color: #0f172a; font-size: 13px; font-weight: 500;
    outline: none; transition: all .16s; font-family: inherit;
}
.smm-input:focus, .smm-select:focus { border-color: #ea580c; box-shadow: 0 0 0 3px rgba(234,88,12,.12); background: #fff; }
.smm-btn-apply {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 9px 18px; border-radius: 10px;
    background: #ea580c; border: none; color: #fff;
    font-size: 13px; font-weight: 800; cursor: pointer; white-space: nowrap;
    transition: all .16s; width: 100%;
}
.smm-btn-apply:hover { background: #c2410c; }
.smm-btn-reset { font-size: 12px; font-weight: 700; color: #64748b; text-decoration: none; padding: 4px 8px; border-radius: 6px; transition: all .14s; }
.smm-btn-reset:hover { background: #f1f5f9; color: #0f172a; }

/* ── Tabs & View Controls ── */
.smm-controls-bar { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
.smm-tabs-cluster { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.smm-tabs-group { display: inline-flex; gap: 4px; padding: 4px; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(15,23,42,.04); }
.smm-tab {
    padding: 8px 18px; border-radius: 9px; border: none; background: transparent;
    color: #64748b; font-size: 13px; font-weight: 700; cursor: pointer; transition: all .16s; white-space: nowrap; font-family: inherit;
}
.smm-tab:hover { background: #fff7ed; color: #ea580c; }
.smm-tab.active { background: #ea580c; color: #fff; box-shadow: 0 4px 12px rgba(234,88,12,.25); }

.smm-team-switcher { display: inline-flex; gap: 4px; padding: 4px; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; }
.smm-team-btn {
    padding: 7px 14px; border-radius: 8px; border: none; background: transparent;
    font-size: 12px; font-weight: 700; color: #64748b; cursor: pointer; transition: all .15s; font-family: inherit;
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
}
.smm-team-btn:hover { background: #f8fafc; color: #0f172a; }
.smm-team-btn.active-all    { background: #0284c7; color: #fff; }
.smm-team-btn.active-design { background: #16a34a; color: #fff; }
.smm-team-btn.active-dm     { background: #7c3aed; color: #fff; }

.smm-team-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 800; }
.smm-team-badge.is-design { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.smm-team-badge.is-dm     { background: #f5f3ff; color: #7c3aed; border: 1px solid #c4b5fd; }

.smm-records-label { font-size: 12px; color: #94a3b8; font-weight: 500; }
.smm-records-label strong { color: #374151; }

/* ── Table Styling ── */
.pts-table-wrap { overflow-x: auto; }
.smm-tbl { width: 100%; border-collapse: collapse; min-width: 1200px; }
.smm-tbl thead { position: sticky; top: 0; z-index: 10; }
.smm-tbl th {
    padding: 12px 14px; text-align: left;
    font-size: 10px; font-weight: 800; letter-spacing: .07em; text-transform: uppercase;
    white-space: nowrap; background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #64748b;
}
.smm-tbl th.th-accent { background: #fff7ed; color: #c2410c; border-bottom-color: #fdba74; }
.smm-tbl th.th-design { background: #f0fdf4; color: #15803d; border-bottom-color: #86efac; }
.smm-tbl th.th-dm     { background: #f5f3ff; color: #7c3aed; border-bottom-color: #c4b5fd; }
.smm-tbl th.th-center { text-align: center; }
.smm-tbl th.th-group  { text-align: center; font-size: 11px; font-weight: 900; padding: 9px 14px; letter-spacing: .04em; border-bottom-width: 1px; }
.smm-tbl th.th-grp-design { background: #f0fdf4; color: #15803d; border-bottom-color: #86efac; border-top: 2px solid #86efac; }
.smm-tbl th.th-grp-dm     { background: #f5f3ff; color: #7c3aed; border-bottom-color: #c4b5fd; border-top: 2px solid #c4b5fd; }
.smm-tbl th.sep-design { border-left: 2px solid #86efac; }
.smm-tbl th.sep-dm     { border-left: 2px solid #c4b5fd; }
.smm-tbl td {
    padding: 13px 14px; border-bottom: 1px solid #f1f5f9;
    font-size: 13px; color: #374151; vertical-align: middle;
}
.smm-tbl tbody tr { transition: background .12s; }
.smm-tbl tbody tr:hover td { background: #fffaf5; }
.smm-tbl tbody tr:last-child td { border-bottom: none; }
.smm-tbl td.sep-design { border-left: 2px solid #bbf7d0; }
.smm-tbl td.sep-dm     { border-left: 2px solid #c4b5fd; }
.smm-tbl td.td-center  { text-align: center; }

.smm-account-name { font-weight: 800; font-size: 13px; color: #111827; display: flex; align-items: center; gap: 7px; }
.smm-account-avatar {
    width: 28px; height: 28px; border-radius: 8px;
    background: #fed7aa; color: #c2410c; font-size: 12px; font-weight: 900;
    display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.smm-product-pill { display: inline-block; padding: 4px 10px; border-radius: 6px; background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; font-size: 11px; font-weight: 700; }
.smm-num { font-weight: 700; text-align: center; }
.smm-num.zero   { color: #d1d5db; }
.smm-num.commit { color: #ea580c; font-weight: 800; }
.smm-num.done   { color: #16a34a; font-weight: 800; }
.smm-num.pend   { color: #ca8a04; font-weight: 800; }
.smm-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; white-space: nowrap; }
.smm-badge.completed { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
.smm-badge.pending   { background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; }
.smm-badge.overdue   { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
.smm-badge-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; background: currentColor; }
.smm-person { font-size: 12px; color: #475569; max-width: 160px; word-break: break-word; }
.smm-person.empty { color: #d1d5db; font-style: italic; }
.smm-date { font-size: 12px; color: #64748b; white-space: nowrap; }

.smm-action-btns { display: flex; align-items: center; justify-content: center; gap: 6px; }
.smm-act-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    padding: 6px 10px; border-radius: 8px; font-size: 11px; font-weight: 800;
    border: 1px solid #e2e8f0; background: #fff; color: #475569;
    cursor: pointer; transition: all 0.15s ease; font-family: inherit;
}
.smm-act-btn:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
.smm-act-btn.is-entry { background: #fff7ed; border-color: #fdba74; color: #c2410c; }
.smm-act-btn.is-entry:hover { background: #ea580c; border-color: #ea580c; color: #fff; }
.smm-act-btn.is-history { background: #f1f5f9; border-color: #cbd5e1; color: #334155; }
.smm-act-btn.is-history:hover { background: #334155; border-color: #334155; color: #fff; }

.smm-group-header td {
    background: #fbfdff; border-top: 2px solid #fed7aa; border-bottom: 1px solid #e2e8f0;
    padding: 11px 16px; font-size: 13px; font-weight: 800; color: #c2410c;
}
.smm-group-count { display: inline-flex; align-items: center; justify-content: center; padding: 2px 8px; border-radius: 999px; background: #fed7aa; color: #c2410c; font-size: 11px; font-weight: 800; margin-left: 8px; }
.smm-empty { padding: 60px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.smm-empty-icon { width: 56px; height: 56px; border-radius: 16px; background: #fff7ed; display: grid; place-items: center; color: #ea580c; }
.smm-empty-title { font-size: 16px; font-weight: 800; color: #374151; }
.smm-empty-sub   { font-size: 13px; color: #94a3b8; max-width: 360px; line-height: 1.5; }

/* ── Modals & Overlays ── */
.smm-modal-overlay {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px); z-index: 9999;
    display: none; align-items: center; justify-content: center; padding: 20px;
    animation: smmFadeIn .15s ease-out;
}
.smm-modal-overlay.open { display: flex; }
@keyframes smmFadeIn { from { opacity: 0; } to { opacity: 1; } }

.smm-modal {
    background: #fff; border-radius: 20px; width: 100%; max-width: 580px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: flex; flex-direction: column; overflow: hidden; max-height: 90vh;
    animation: smmSlideUp .2s ease-out;
}
.smm-modal-lg { max-width: 720px; }
@keyframes smmSlideUp { from { transform: translateY(12px) scale(0.98); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }

.smm-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px; border-bottom: 1px solid #e2e8f0; background: #fbfdff;
}
.smm-modal-title { font-size: 17px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 8px; }
.smm-modal-close {
    background: none; border: none; font-size: 20px; line-height: 1;
    color: #94a3b8; cursor: pointer; padding: 4px 8px; border-radius: 8px; transition: all 0.15s;
}
.smm-modal-close:hover { background: #f1f5f9; color: #0f172a; }

.smm-modal-body { padding: 22px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; }
.smm-modal-footer {
    padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc;
    display: flex; align-items: center; justify-content: flex-end; gap: 10px;
}

/* Calculation & Live Preview Box */
.smm-calc-box {
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
    border: 1.5px solid #fed7aa; border-radius: 14px; padding: 14px 16px;
    display: flex; flex-direction: column; gap: 10px;
}
.smm-calc-title { font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #9a3412; }
.smm-calc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.smm-calc-card {
    background: #fff; border-radius: 10px; padding: 12px 14px;
    border: 1px solid #ffedd5; display: flex; flex-direction: column; gap: 4px;
}
.smm-calc-label { font-size: 11px; font-weight: 700; color: #64748b; }
.smm-calc-formula { font-size: 12px; font-weight: 600; color: #374151; font-family: monospace; }
.smm-calc-result { font-size: 18px; font-weight: 900; color: #ea580c; display: flex; align-items: baseline; gap: 4px; }
.smm-calc-result.zero { color: #16a34a; }

/* Timeline for History */
.smm-timeline { display: flex; flex-direction: column; gap: 14px; }
.smm-timeline-item {
    position: relative; padding-left: 28px; display: flex; flex-direction: column; gap: 4px;
}
.smm-timeline-item::before {
    content: ''; position: absolute; left: 8px; top: 12px; bottom: -14px;
    width: 2px; background: #e2e8f0;
}
.smm-timeline-item:last-child::before { display: none; }
.smm-timeline-dot {
    position: absolute; left: 2px; top: 4px; width: 14px; height: 14px;
    border-radius: 50%; background: #ea580c; border: 2px solid #fff; box-shadow: 0 0 0 2px #fdba74;
}
.smm-timeline-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.smm-timeline-user { font-size: 13px; font-weight: 800; color: #111827; display: flex; align-items: center; gap: 6px; }
.smm-timeline-time { font-size: 11px; color: #94a3b8; }
.smm-timeline-body {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;
    font-size: 12px; color: #334155; display: flex; flex-direction: column; gap: 6px;
}
.smm-timeline-badges { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.smm-tbadge { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.smm-tbadge.poster { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.smm-tbadge.video  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.smm-tbadge.team   { background: #f1f5f9; color: #475569; }

/* ── Select2 Custom Modal Styling ── */
.select2-container { z-index: 100000 !important; }
.select2-dropdown {
    z-index: 100001 !important;
    border-radius: 12px !important;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15) !important;
    border: 1.5px solid #cbd5e1 !important;
    overflow: hidden !important;
}
.select2-container--default .select2-selection--single {
    height: 42px !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    background: #f8fafc !important;
    padding: 0 10px !important;
    transition: all 0.15s ease !important;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #ea580c !important;
    box-shadow: 0 0 0 3px rgba(234,88,12,.12) !important;
    background: #fff !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    padding-left: 0 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 8px !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #ea580c !important;
    color: #fff !important;
}

@media (max-width: 1280px) {
    .pjd-stats { grid-template-columns: repeat(2, 1fr); }
    .smm-filter-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .pts-topbar { flex-direction: column; align-items: flex-start; }
    .pjd-stats { grid-template-columns: 1fr; }
    .smm-filter-grid { grid-template-columns: 1fr; }
    .smm-controls-bar { flex-direction: column; align-items: flex-start; }
    .smm-calc-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
    $total     = $rows->count();
    $completed = $rows->where('status','completed')->count();
    $pending   = $rows->where('status','pending')->count();
    $overdue   = $rows->where('status','overdue')->count();
    $byLead    = $rows->groupBy('account_name');
    $activeTab = request('view', 'product');

    $showDesignCols = in_array($teamView, ['all', 'design']);
    $showDmCols     = in_array($teamView, ['all', 'dm']);

    $totalCols = 5 + ($showDesignCols ? 5 : 0) + ($showDmCols ? 5 : 0) + 2; // +2 for status & actions

    $filterCount = 0;
    if (!empty($filters['date_from'])) $filterCount++;
    if (!empty($filters['date_to'])) $filterCount++;
    if (!empty($filters['lead_id'])) $filterCount++;
    if (!empty($filters['status'])) $filterCount++;
    $filterPanelOpen = $filterCount > 0;
@endphp

<div class="pts-page">
    {{-- ── Topbar ── --}}
    <div class="pts-topbar">
        <div>
            <div class="pts-title">SMM Sheet</div>
            <div class="pts-breadcrumb">Modules &gt; Projects &gt; SMM Sheet</div>
        </div>
        <div class="pts-actions">
            <button type="button" class="pts-btn pts-btn-success" id="btnOpenEntryModal">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                + Add SMM Entry
            </button>
            <a href="{{ route('projects.smm-sheet.export', request()->query()) }}" class="pts-btn pts-btn-primary">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                Export Excel
            </a>
            <a href="{{ route('projects.dashboard') }}" class="pts-btn">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Dashboard
            </a>
        </div>
    </div>

    <div class="pts-body">
        {{-- ── Stats Cards ── --}}
        <section class="pjd-stats">
            {{-- Total Records --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #ea580c 0%, #ff7e3b 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Total Records</span>
                    <span class="pjd-stat-icon">📋</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $total }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Count-wise renewal records
                </div>
            </div>

            {{-- Completed --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Completed</span>
                    <span class="pjd-stat-icon">✅</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $completed }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Deliverables 100% completed
                </div>
            </div>

            {{-- Pending --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Pending</span>
                    <span class="pjd-stat-icon">⏳</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $pending }}</span>
                </div>
                <div class="pjd-stat-footer">
                    In progress deliveries
                </div>
            </div>

            {{-- Overdue --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Overdue</span>
                    <span class="pjd-stat-icon">🔴</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $overdue }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Past delivery deadline
                </div>
            </div>
        </section>

        {{-- ── Filter Accordion Card ── --}}
        <details class="pts-card smm-filter-accordion" @if($filterPanelOpen) open @endif>
            <summary class="pts-card-head smm-filter-summary">
                <div class="smm-filter-summary-left">
                    <span class="smm-filter-icon">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                    </span>
                    <div>
                        <div class="pts-card-title">Filters @if($filterCount > 0) <span class="smm-filter-count-badge">{{ $filterCount }}</span> @endif</div>
                        <div class="pts-card-sub">Click to expand/collapse filter options</div>
                    </div>
                </div>
                <div class="smm-filter-summary-right">
                    @if(request()->hasAny(['date_from','date_to','lead_id','status','team_view']))
                        <a href="{{ route('projects.smm-sheet') }}" class="smm-btn-reset" onclick="event.stopPropagation();">✕ Reset Filters</a>
                    @endif
                    <span class="smm-chevron">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </span>
                </div>
            </summary>
            <div class="pts-card-body">
                <div class="smm-quick-filters">
                    <span class="smm-quick-label">Quick:</span>
                    <button type="button" class="smm-qbtn" data-preset="today">Today</button>
                    <button type="button" class="smm-qbtn" data-preset="month">This Month</button>
                    <button type="button" class="smm-qbtn" data-preset="quarter">This Quarter</button>
                    <button type="button" class="smm-qbtn" data-preset="year">This Year</button>
                </div>
                <form method="GET" action="{{ route('projects.smm-sheet') }}" class="smm-filter-grid" id="smmForm">
                    <div class="smm-field">
                        <label class="smm-label" for="smm_date_from">Delivery From</label>
                        <input id="smm_date_from" type="date" name="date_from" class="smm-input" value="{{ request('date_from', '') }}">
                    </div>
                    <div class="smm-field">
                        <label class="smm-label" for="smm_date_to">Delivery To</label>
                        <input id="smm_date_to" type="date" name="date_to" class="smm-input" value="{{ request('date_to', '') }}">
                    </div>
                    <div class="smm-field">
                        <label class="smm-label" for="smm_lead_id">Account / Lead</label>
                        <select id="smm_lead_id" name="lead_id" class="smm-select no-select2" data-no-select2="true">
                            <option value="">All Allocated Accounts</option>
                            @foreach($leads as $lead)
                                @php $lbl = trim($lead->company_name ?: ($lead->contact_name ?? '')); @endphp
                                <option value="{{ $lead->id }}" @selected((string)$filters['lead_id'] === (string)$lead->id)>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="smm-field">
                        <label class="smm-label" for="smm_status">Status</label>
                        <select id="smm_status" name="status" class="smm-select no-select2" data-no-select2="true">
                            <option value="">All Status</option>
                            <option value="completed" @selected($filters['status'] === 'completed')>✅ Completed</option>
                            <option value="pending"   @selected($filters['status'] === 'pending')>⏳ Pending</option>
                            <option value="overdue"   @selected($filters['status'] === 'overdue')>🔴 Overdue</option>
                        </select>
                    </div>
                    <input type="hidden" name="view" value="{{ $activeTab }}">
                    @if(!empty($filters['team_view']))
                        <input type="hidden" name="team_view" value="{{ $filters['team_view'] }}">
                    @endif
                    <div class="smm-field">
                        <label class="smm-label">&nbsp;</label>
                        <button type="submit" class="smm-btn-apply">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </details>

        {{-- ── Tabs & View Controls Row ── --}}
        <div class="smm-controls-bar">
            <div class="smm-tabs-cluster">
                {{-- Product vs Lead Wise Tabs --}}
                <div class="smm-tabs-group" id="smmTabsGroup">
                    <button type="button" class="smm-tab {{ $activeTab === 'product' ? 'active' : '' }}" data-view="product">
                        📋 Product Wise
                    </button>
                    <button type="button" class="smm-tab {{ $activeTab === 'lead' ? 'active' : '' }}" data-view="lead">
                        🏢 Lead / Account Wise
                    </button>
                </div>

                {{-- Team Column Switcher for Admin / Multidisciplinary Roles --}}
                @if($isAdminLike)
                    <div class="smm-team-switcher">
                        <a href="{{ request()->fullUrlWithQuery(['team_view' => 'all']) }}" class="smm-team-btn {{ $teamView === 'all' ? 'active-all' : '' }}">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20M2 12h20"/></svg>
                            All Columns
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['team_view' => 'design']) }}" class="smm-team-btn {{ $teamView === 'design' ? 'active-design' : '' }}">
                            🎨 Design Only
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['team_view' => 'dm']) }}" class="smm-team-btn {{ $teamView === 'dm' ? 'active-dm' : '' }}">
                            📢 DM Only
                        </a>
                    </div>
                @elseif($isDesignUser)
                    <span class="smm-team-badge is-design">🎨 Design Team View</span>
                @elseif($isDmUser)
                    <span class="smm-team-badge is-dm">📢 Digital Marketing Team View</span>
                @endif
            </div>

            <div class="smm-records-label">
                Showing <strong>{{ $total }}</strong> record{{ $total !== 1 ? 's' : '' }}
                @if($overdue > 0)
                    &nbsp;·&nbsp;
                    <span style="color:#dc2626;font-weight:700;">⚠ {{ $overdue }} overdue</span>
                @endif
            </div>
        </div>

        {{-- ═══ PRODUCT WISE TABLE ═══ --}}
        <div id="panel-product" class="pts-card" style="{{ $activeTab !== 'product' ? 'display:none' : '' }}">
            <div class="pts-card-head">
                <div>
                    <div class="pts-card-title">Product Wise View</div>
                    <div class="pts-card-sub">One row = one renewal product order per lead account</div>
                </div>
                <div style="font-size:12px;color:#94a3b8;">{{ $total }} records</div>
            </div>
            <div class="pts-table-wrap">
                <table class="smm-tbl">
                    <thead>
                        <tr>
                            <th rowspan="2" class="th-accent" style="min-width:200px;">Account &amp; Product</th>
                            <th rowspan="2" class="th-accent">Start Date</th>
                            <th rowspan="2" class="th-accent">End Date</th>
                            <th rowspan="2" class="th-center" style="color:#ea580c;">Committed<br>Posters</th>
                            <th rowspan="2" class="th-center" style="color:#ea580c;">Committed<br>Videos</th>

                            @if($showDesignCols)
                                <th colspan="5" class="th-group th-grp-design sep-design">🎨 Design Team</th>
                            @endif

                            @if($showDmCols)
                                <th colspan="5" class="th-group th-grp-dm sep-dm">📢 Digital Marketing Team</th>
                            @endif

                            <th rowspan="2" class="th-center">Status</th>
                            <th rowspan="2" class="th-center" style="min-width:130px;">Action</th>
                        </tr>
                        <tr>
                            @if($showDesignCols)
                                <th class="th-design th-center sep-design">Done Posters</th>
                                <th class="th-design th-center">Pending Posters</th>
                                <th class="th-design th-center">Done Videos</th>
                                <th class="th-design th-center">Pending Videos</th>
                                <th class="th-design">Allocated To</th>
                            @endif

                            @if($showDmCols)
                                <th class="th-dm th-center sep-dm">Done Posters</th>
                                <th class="th-dm th-center">Pending Posters</th>
                                <th class="th-dm th-center">Done Videos</th>
                                <th class="th-dm th-center">Pending Videos</th>
                                <th class="th-dm">Allocated To</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $ini = strtoupper(substr($row['account_name'], 0, 1));
                            @endphp
                            <tr id="smm-row-{{ $row['smm_sheet_id'] }}">
                                <td>
                                    <div style="display:flex;align-items:flex-start;gap:9px;">
                                        <span class="smm-account-avatar" style="margin-top:2px;">{{ $ini }}</span>
                                        <div style="display:flex;flex-direction:column;gap:4px;">
                                            <span class="smm-account-name" style="font-size:13px;font-weight:800;color:#111827;">{{ $row['account_name'] }}</span>
                                            <div><span class="smm-product-pill">{{ $row['product_name'] }}</span></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="smm-date">{{ $row['start_date'] ? \Carbon\Carbon::parse($row['start_date'])->format('d M Y') : '—' }}</span></td>
                                <td><span class="smm-date">{{ $row['end_date'] ? \Carbon\Carbon::parse($row['end_date'])->format('d M Y') : '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['committed_posters'] ? 'commit' : 'zero' }}">{{ $row['committed_posters'] ?: '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['committed_videos'] ? 'commit' : 'zero' }}">{{ $row['committed_videos'] ?: '—' }}</span></td>

                                @if($showDesignCols)
                                    <td class="td-center sep-design"><span class="smm-num {{ $row['design_completed_posters'] ? 'done' : 'zero' }}">{{ $row['design_completed_posters'] ?: '—' }}</span></td>
                                    <td class="td-center"><span class="smm-num {{ $row['design_pending_posters'] ? 'pend' : 'zero' }}">{{ $row['design_pending_posters'] ?: '—' }}</span></td>
                                    <td class="td-center"><span class="smm-num {{ $row['design_completed_videos'] ? 'done' : 'zero' }}">{{ $row['design_completed_videos'] ?: '—' }}</span></td>
                                    <td class="td-center"><span class="smm-num {{ $row['design_pending_videos'] ? 'pend' : 'zero' }}">{{ $row['design_pending_videos'] ?: '—' }}</span></td>
                                    <td>@if($row['design_persons'] && $row['design_persons'] !== '-')<span class="smm-person">{{ $row['design_persons'] }}</span>@else<span class="smm-person empty">—</span>@endif</td>
                                @endif

                                @if($showDmCols)
                                    <td class="td-center sep-dm"><span class="smm-num {{ $row['dm_completed_posters'] ? 'done' : 'zero' }}">{{ $row['dm_completed_posters'] ?: '—' }}</span></td>
                                    <td class="td-center"><span class="smm-num {{ $row['dm_pending_posters'] ? 'pend' : 'zero' }}">{{ $row['dm_pending_posters'] ?: '—' }}</span></td>
                                    <td class="td-center"><span class="smm-num {{ $row['dm_completed_videos'] ? 'done' : 'zero' }}">{{ $row['dm_completed_videos'] ?: '—' }}</span></td>
                                    <td class="td-center"><span class="smm-num {{ $row['dm_pending_videos'] ? 'pend' : 'zero' }}">{{ $row['dm_pending_videos'] ?: '—' }}</span></td>
                                    <td>@if($row['dm_persons'] && $row['dm_persons'] !== '-')<span class="smm-person">{{ $row['dm_persons'] }}</span>@else<span class="smm-person empty">—</span>@endif</td>
                                @endif

                                <td class="td-center">
                                    <span class="smm-badge {{ $row['status'] }}">
                                        <span class="smm-badge-dot"></span> {{ ucfirst($row['status']) }}
                                    </span>
                                </td>

                                <td class="td-center">
                                    <div class="smm-action-btns">
                                        <button type="button" class="smm-act-btn is-entry btn-row-entry"
                                            data-id="{{ $row['smm_sheet_id'] }}"
                                            data-account="{{ $row['account_name'] }}"
                                            data-product="{{ $row['product_name'] }}"
                                            data-start-date="{{ $row['start_date'] ? \Carbon\Carbon::parse($row['start_date'])->format('Y-m-d') : '' }}"
                                            data-end-date="{{ $row['end_date'] ? \Carbon\Carbon::parse($row['end_date'])->format('Y-m-d') : '' }}"
                                            data-committed-posters="{{ $row['committed_posters'] }}"
                                            data-committed-videos="{{ $row['committed_videos'] }}"
                                            data-design-done-posters="{{ $row['design_completed_posters'] }}"
                                            data-design-done-videos="{{ $row['design_completed_videos'] }}"
                                            data-dm-done-posters="{{ $row['dm_completed_posters'] }}"
                                            data-dm-done-videos="{{ $row['dm_completed_videos'] }}"
                                            title="Update Done Counts">
                                            ✏ Entry
                                        </button>
                                        <button type="button" class="smm-act-btn is-history btn-row-history"
                                            data-id="{{ $row['smm_sheet_id'] }}"
                                            title="View Activity History">
                                            📜 Logs
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $totalCols }}">
                                <div class="smm-empty">
                                    <div class="smm-empty-icon"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                                    <div class="smm-empty-title">No SMM Sheet Records Found</div>
                                    <div class="smm-empty-sub">No records match the current filters or no renewal products are allocated.</div>
                                    <a href="{{ route('projects.smm-sheet') }}" class="pts-btn" style="margin-top:4px;">Clear Filters</a>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══ LEAD WISE TABLE ═══ --}}
        <div id="panel-lead" class="pts-card" style="{{ $activeTab !== 'lead' ? 'display:none' : '' }}">
            <div class="pts-card-head">
                <div>
                    <div class="pts-card-title">Lead / Account Wise View</div>
                    <div class="pts-card-sub">Rows grouped by account — all renewal products per lead listed together</div>
                </div>
                <div style="font-size:12px;color:#94a3b8;">{{ $byLead->count() }} account{{ $byLead->count() !== 1 ? 's' : '' }}</div>
            </div>
            <div class="pts-table-wrap">
                <table class="smm-tbl">
                    <thead>
                        <tr>
                            <th rowspan="2" class="th-accent" style="min-width:200px;">Account &amp; Product</th>
                            <th rowspan="2" class="th-accent">Start Date</th>
                            <th rowspan="2" class="th-accent">End Date</th>
                            <th rowspan="2" class="th-center" style="color:#ea580c;">Committed<br>Posters</th>
                            <th rowspan="2" class="th-center" style="color:#ea580c;">Committed<br>Videos</th>

                            @if($showDesignCols)
                                <th colspan="5" class="th-group th-grp-design sep-design">🎨 Design Team</th>
                            @endif

                            @if($showDmCols)
                                <th colspan="5" class="th-group th-grp-dm sep-dm">📢 Digital Marketing Team</th>
                            @endif

                            <th rowspan="2" class="th-center">Status</th>
                            <th rowspan="2" class="th-center" style="min-width:130px;">Action</th>
                        </tr>
                        <tr>
                            @if($showDesignCols)
                                <th class="th-design th-center sep-design">Done Posters</th>
                                <th class="th-design th-center">Pending Posters</th>
                                <th class="th-design th-center">Done Videos</th>
                                <th class="th-design th-center">Pending Videos</th>
                                <th class="th-design">Allocated To</th>
                            @endif

                            @if($showDmCols)
                                <th class="th-dm th-center sep-dm">Done Posters</th>
                                <th class="th-dm th-center">Pending Posters</th>
                                <th class="th-dm th-center">Done Videos</th>
                                <th class="th-dm th-center">Pending Videos</th>
                                <th class="th-dm">Allocated To</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byLead as $accountName => $leadRows)
                            @php
                                $leadIni = strtoupper(substr($accountName, 0, 1));
                            @endphp
                            <tr class="smm-group-header">
                                <td colspan="{{ $totalCols }}">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span class="smm-account-avatar">{{ $leadIni }}</span>
                                        <span>{{ $accountName }}</span>
                                        <span class="smm-group-count">{{ $leadRows->count() }} product{{ $leadRows->count() !== 1 ? 's' : '' }}</span>
                                    </div>
                                </td>
                            </tr>
                            @foreach($leadRows as $row)
                                <tr id="smm-row-lead-{{ $row['smm_sheet_id'] }}">
                                    <td style="padding-left:36px;">
                                        <div style="display:flex;flex-direction:column;gap:3px;">
                                            <span style="font-size:13px;font-weight:800;color:#111827;">↳ {{ $row['account_name'] }}</span>
                                            <div><span class="smm-product-pill">{{ $row['product_name'] }}</span></div>
                                        </div>
                                    </td>
                                    <td><span class="smm-date">{{ $row['start_date'] ? \Carbon\Carbon::parse($row['start_date'])->format('d M Y') : '—' }}</span></td>
                                    <td><span class="smm-date">{{ $row['end_date'] ? \Carbon\Carbon::parse($row['end_date'])->format('d M Y') : '—' }}</span></td>
                                    <td class="td-center"><span class="smm-num {{ $row['committed_posters'] ? 'commit' : 'zero' }}">{{ $row['committed_posters'] ?: '—' }}</span></td>
                                    <td class="td-center"><span class="smm-num {{ $row['committed_videos'] ? 'commit' : 'zero' }}">{{ $row['committed_videos'] ?: '—' }}</span></td>

                                    @if($showDesignCols)
                                        <td class="td-center sep-design"><span class="smm-num {{ $row['design_completed_posters'] ? 'done' : 'zero' }}">{{ $row['design_completed_posters'] ?: '—' }}</span></td>
                                        <td class="td-center"><span class="smm-num {{ $row['design_pending_posters'] ? 'pend' : 'zero' }}">{{ $row['design_pending_posters'] ?: '—' }}</span></td>
                                        <td class="td-center"><span class="smm-num {{ $row['design_completed_videos'] ? 'done' : 'zero' }}">{{ $row['design_completed_videos'] ?: '—' }}</span></td>
                                        <td class="td-center"><span class="smm-num {{ $row['design_pending_videos'] ? 'pend' : 'zero' }}">{{ $row['design_pending_videos'] ?: '—' }}</span></td>
                                        <td>@if($row['design_persons'] && $row['design_persons'] !== '-')<span class="smm-person">{{ $row['design_persons'] }}</span>@else<span class="smm-person empty">—</span>@endif</td>
                                    @endif

                                    @if($showDmCols)
                                        <td class="td-center sep-dm"><span class="smm-num {{ $row['dm_completed_posters'] ? 'done' : 'zero' }}">{{ $row['dm_completed_posters'] ?: '—' }}</span></td>
                                        <td class="td-center"><span class="smm-num {{ $row['dm_pending_posters'] ? 'pend' : 'zero' }}">{{ $row['dm_pending_posters'] ?: '—' }}</span></td>
                                        <td class="td-center"><span class="smm-num {{ $row['dm_completed_videos'] ? 'done' : 'zero' }}">{{ $row['dm_completed_videos'] ?: '—' }}</span></td>
                                        <td class="td-center"><span class="smm-num {{ $row['dm_pending_videos'] ? 'pend' : 'zero' }}">{{ $row['dm_pending_videos'] ?: '—' }}</span></td>
                                        <td>@if($row['dm_persons'] && $row['dm_persons'] !== '-')<span class="smm-person">{{ $row['dm_persons'] }}</span>@else<span class="smm-person empty">—</span>@endif</td>
                                    @endif

                                    <td class="td-center">
                                        <span class="smm-badge {{ $row['status'] }}">
                                            <span class="smm-badge-dot"></span> {{ ucfirst($row['status']) }}
                                        </span>
                                    </td>

                                    <td class="td-center">
                                        <div class="smm-action-btns">
                                            <button type="button" class="smm-act-btn is-entry btn-row-entry"
                                                data-id="{{ $row['smm_sheet_id'] }}"
                                                data-account="{{ $row['account_name'] }}"
                                                data-product="{{ $row['product_name'] }}"
                                                data-start-date="{{ $row['start_date'] ? \Carbon\Carbon::parse($row['start_date'])->format('Y-m-d') : '' }}"
                                                data-end-date="{{ $row['end_date'] ? \Carbon\Carbon::parse($row['end_date'])->format('Y-m-d') : '' }}"
                                                data-committed-posters="{{ $row['committed_posters'] }}"
                                                data-committed-videos="{{ $row['committed_videos'] }}"
                                                data-design-done-posters="{{ $row['design_completed_posters'] }}"
                                                data-design-done-videos="{{ $row['design_completed_videos'] }}"
                                                data-dm-done-posters="{{ $row['dm_completed_posters'] }}"
                                                data-dm-done-videos="{{ $row['dm_completed_videos'] }}"
                                                title="Update Done Counts">
                                                ✏ Entry
                                            </button>
                                            <button type="button" class="smm-act-btn is-history btn-row-history"
                                                data-id="{{ $row['smm_sheet_id'] }}"
                                                title="View Activity History">
                                                📜 Logs
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="{{ $totalCols }}">
                                <div class="smm-empty">
                                    <div class="smm-empty-icon"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                                    <div class="smm-empty-title">No SMM Sheet Records Found</div>
                                    <div class="smm-empty-sub">No records match the current filters.</div>
                                    <a href="{{ route('projects.smm-sheet') }}" class="pts-btn" style="margin-top:4px;">Clear Filters</a>
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════
   MODAL 1: SMM Count Entry / Update Modal
   ════════════════════════════════════════════════════════════════════ --}}
<div class="smm-modal-overlay" id="entryModalOverlay">
    <div class="smm-modal">
        <div class="smm-modal-header">
            <div class="smm-modal-title">
                <span style="font-size:20px;">📝</span> SMM Done Deliverables Entry
            </div>
            <button type="button" class="smm-modal-close" onclick="closeEntryModal()">✕</button>
        </div>
        <form id="smmEntryForm" method="POST" action="{{ route('projects.smm-sheet.entry') }}">
            @csrf
            <div class="smm-modal-body">
                {{-- Account / Product Selector --}}
                <div class="smm-field">
                    <label class="smm-label" for="entry_smm_sheet_id">Select Active Account &amp; Product *</label>
                    <select id="entry_smm_sheet_id" name="smm_sheet_id" class="smm-select select2" required style="width:100%;">
                        <option value="">-- Choose Account / Product --</option>
                        @php
                            $dropdownAccounts = (!empty($allActiveAccounts) && $allActiveAccounts->isNotEmpty()) ? $allActiveAccounts : $rows;
                        @endphp
                        @foreach($dropdownAccounts as $r)
                            <option value="{{ $r['smm_sheet_id'] }}"
                                data-account="{{ $r['account_name'] }}"
                                data-product="{{ $r['product_name'] }}"
                                data-start-date="{{ !empty($r['start_date']) ? \Carbon\Carbon::parse($r['start_date'])->format('Y-m-d') : '' }}"
                                data-end-date="{{ !empty($r['end_date']) ? \Carbon\Carbon::parse($r['end_date'])->format('Y-m-d') : '' }}"
                                data-committed-posters="{{ $r['committed_posters'] }}"
                                data-committed-videos="{{ $r['committed_videos'] }}"
                                data-design-done-posters="{{ $r['design_completed_posters'] }}"
                                data-design-done-videos="{{ $r['design_completed_videos'] }}"
                                data-dm-done-posters="{{ $r['dm_completed_posters'] }}"
                                data-dm-done-videos="{{ $r['dm_completed_videos'] }}">
                                {{ $r['account_name'] }} — {{ $r['product_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Done Counts Inputs --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="smm-field">
                        <label class="smm-label" for="entry_done_posters" id="lbl_posters">Done Posters Count (To Add) *</label>
                        <input type="number" min="0" step="1" name="done_posters" id="entry_done_posters" class="smm-input" value="0" required>
                    </div>
                    <div class="smm-field">
                        <label class="smm-label" for="entry_done_videos" id="lbl_videos">Done Videos Count (To Add) *</label>
                        <input type="number" min="0" step="1" name="done_videos" id="entry_done_videos" class="smm-input" value="0" required>
                    </div>
                </div>

                {{-- Live Real-time Calculation Preview --}}
                <div class="smm-calc-box">
                    <div class="smm-calc-title">📊 Live Deliverables Calculation</div>
                    <div class="smm-calc-grid">
                        <div class="smm-calc-card">
                            <span class="smm-calc-label">Posters Pending</span>
                            <span class="smm-calc-formula" id="calc_formula_posters">Pending = Committed − Done</span>
                            <div class="smm-calc-result" id="calc_result_posters">
                                <span id="val_pending_posters">0</span>
                                <span style="font-size:11px;font-weight:600;color:#94a3b8;">left</span>
                            </div>
                        </div>
                        <div class="smm-calc-card">
                            <span class="smm-calc-label">Videos Pending</span>
                            <span class="smm-calc-formula" id="calc_formula_videos">Pending = Committed − Done</span>
                            <div class="smm-calc-result" id="calc_result_videos">
                                <span id="val_pending_videos">0</span>
                                <span style="font-size:11px;font-weight:600;color:#94a3b8;">left</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Remarks / Activity Notes --}}
                <div class="smm-field">
                    <label class="smm-label" for="entry_remarks">Remarks / Activity Note</label>
                    <textarea name="remarks" id="entry_remarks" class="smm-input" rows="2" placeholder="e.g. Created and delivered 3 promotional posters for campaign"></textarea>
                </div>
            </div>
            <div class="smm-modal-footer">
                <button type="button" class="pts-btn" onclick="closeEntryModal()">Cancel</button>
                <button type="submit" class="pts-btn pts-btn-primary" id="btnSaveEntry">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Save SMM Entry
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════
   MODAL 2: SMM Activity History / Audit Log Modal
   ════════════════════════════════════════════════════════════════════ --}}
<div class="smm-modal-overlay" id="historyModalOverlay">
    <div class="smm-modal smm-modal-lg">
        <div class="smm-modal-header">
            <div>
                <div class="smm-modal-title">
                    <span style="font-size:20px;">📜</span> SMM Activity &amp; Audit Log
                </div>
                <div class="pts-card-sub" id="historyAccountSub">Account Activity History</div>
            </div>
            <button type="button" class="smm-modal-close" onclick="closeHistoryModal()">✕</button>
        </div>
        <div class="smm-modal-body" id="historyModalBody">
            <div style="text-align:center;padding:40px 20px;color:#64748b;" id="historyLoading">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="10"/></svg>
                <div style="margin-top:8px;font-size:13px;font-weight:700;">Loading Activity Logs...</div>
            </div>
            <div class="smm-timeline" id="historyTimeline" style="display:none;"></div>
            <div class="smm-empty" id="historyEmpty" style="display:none;">
                <div class="smm-empty-icon">📝</div>
                <div class="smm-empty-title">No Activity Logs Yet</div>
                <div class="smm-empty-sub">No updates have been logged for this account yet.</div>
            </div>
        </div>
        <div class="smm-modal-footer">
            <button type="button" class="pts-btn" onclick="closeHistoryModal()">Close</button>
        </div>
    </div>
</div>

@push('scripts')
<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Tab switching ──
    const tabBtns = document.querySelectorAll('#smmTabsGroup .smm-tab');
    const panelProduct = document.getElementById('panel-product');
    const panelLead    = document.getElementById('panel-lead');
    const viewInput    = document.querySelector('input[name="view"]');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const target = this.dataset.view;
            if (viewInput) viewInput.value = target;

            if (target === 'product') {
                panelProduct.style.display = '';
                panelLead.style.display    = 'none';
            } else {
                panelProduct.style.display = 'none';
                panelLead.style.display    = '';
            }

            const url = new URL(window.location.href);
            url.searchParams.set('view', target);
            window.history.replaceState({}, '', url);
        });
    });

    // ── Quick date filters ──
    const dateFromInput = document.getElementById('smm_date_from');
    const dateToInput   = document.getElementById('smm_date_to');
    const qBtns         = document.querySelectorAll('.smm-qbtn');

    function fmt(d) {
        return d.toISOString().split('T')[0];
    }

    qBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            qBtns.forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');

            const preset = this.dataset.preset;
            const now    = new Date();
            let from, to;

            if (preset === 'today') {
                from = fmt(now);
                to   = fmt(now);
            } else if (preset === 'month') {
                from = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
                to   = fmt(new Date(now.getFullYear(), now.getMonth() + 1, 0));
            } else if (preset === 'quarter') {
                const qMonth = Math.floor(now.getMonth() / 3) * 3;
                from = fmt(new Date(now.getFullYear(), qMonth, 1));
                to   = fmt(new Date(now.getFullYear(), qMonth + 3, 0));
            } else if (preset === 'year') {
                from = fmt(new Date(now.getFullYear(), 0, 1));
                to   = fmt(new Date(now.getFullYear(), 11, 31));
            }

            if (dateFromInput && dateToInput) {
                dateFromInput.value = from;
                dateToInput.value   = to;
                document.getElementById('smmForm').submit();
            }
        });
    });

    // ── Entry Modal Logic ──
    const entryOverlay   = document.getElementById('entryModalOverlay');
    const selectSheet    = document.getElementById('entry_smm_sheet_id');
    const inputPosters   = document.getElementById('entry_done_posters');
    const inputVideos    = document.getElementById('entry_done_videos');
    const formulaPosters = document.getElementById('calc_formula_posters');
    const formulaVideos  = document.getElementById('calc_formula_videos');
    const valPosters     = document.getElementById('val_pending_posters');
    const valVideos      = document.getElementById('val_pending_videos');
    const resPosters     = document.getElementById('calc_result_posters');
    const resVideos      = document.getElementById('calc_result_videos');

    const isDesignUser = @json($isDesignUser);
    const isDmUser     = @json($isDmUser);

    function getSelectedTeam() {
        if (isDmUser && !isDesignUser) return 'dm';
        return 'design';
    }

    function updateLiveCalculation() {
        const opt = selectSheet.options[selectSheet.selectedIndex];
        if (!opt || !opt.value) {
            formulaPosters.textContent = 'Pending = Committed − Done';
            formulaVideos.textContent  = 'Pending = Committed − Done';
            valPosters.textContent = '0';
            valVideos.textContent  = '0';
            return;
        }

        const committedPosters = parseInt(opt.dataset.committedPosters || 0);
        const committedVideos  = parseInt(opt.dataset.committedVideos || 0);
        const team             = getSelectedTeam();

        const currentDonePosters = team === 'dm' 
            ? parseInt(opt.dataset.dmDonePosters || 0) 
            : parseInt(opt.dataset.designDonePosters || 0);
        const currentDoneVideos  = team === 'dm' 
            ? parseInt(opt.dataset.dmDoneVideos || 0) 
            : parseInt(opt.dataset.designDoneVideos || 0);

        const enteredPosters = parseInt(inputPosters.value || 0);
        const enteredVideos  = parseInt(inputVideos.value || 0);

        const newDonePosters = currentDonePosters + enteredPosters;
        const newDoneVideos  = currentDoneVideos + enteredVideos;

        const remainingPendingPosters = Math.max(0, committedPosters - newDonePosters);
        const remainingPendingVideos  = Math.max(0, committedVideos - newDoneVideos);

        formulaPosters.textContent = `${committedPosters} committed − (${currentDonePosters} + ${enteredPosters}) done`;
        formulaVideos.textContent  = `${committedVideos} committed − (${currentDoneVideos} + ${enteredVideos}) done`;

        valPosters.textContent = remainingPendingPosters;
        valVideos.textContent  = remainingPendingVideos;

        if (remainingPendingPosters === 0) {
            resPosters.classList.add('zero');
        } else {
            resPosters.classList.remove('zero');
        }

        if (remainingPendingVideos === 0) {
            resVideos.classList.add('zero');
        } else {
            resVideos.classList.remove('zero');
        }
    }

    function populateEntryForSheet(sheetId) {
        if (window.jQuery && window.jQuery.fn.select2) {
            $('#entry_smm_sheet_id').val(sheetId).trigger('change.select2');
        } else {
            selectSheet.value = sheetId;
        }

        inputPosters.value = 0;
        inputVideos.value  = 0;
        updateLiveCalculation();
    }

    // Initialize Select2 properly for Modal dropdown
    if (window.jQuery && window.jQuery.fn.select2) {
        $('#entry_smm_sheet_id').select2({
            dropdownParent: $('#entryModalOverlay .smm-modal'),
            placeholder: '-- Choose Account / Product --',
            width: '100%'
        }).on('change', function () {
            inputPosters.value = 0;
            inputVideos.value  = 0;
            updateLiveCalculation();
        });
    } else {
        selectSheet.addEventListener('change', function () {
            populateEntryForSheet(this.value);
        });
    }

    // Topbar + Add SMM Entry button
    const btnOpenEntry = document.getElementById('btnOpenEntryModal');
    if (btnOpenEntry) {
        btnOpenEntry.addEventListener('click', function () {
            if (selectSheet.options.length > 1) {
                const firstVal = selectSheet.options[1].value;
                populateEntryForSheet(firstVal);
            }
            entryOverlay.classList.add('open');
        });
    }

    // Row Entry button click
    document.querySelectorAll('.btn-row-entry').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            populateEntryForSheet(id);
            entryOverlay.classList.add('open');
        });
    });

    inputPosters.addEventListener('input', updateLiveCalculation);
    inputVideos.addEventListener('input', updateLiveCalculation);

    window.closeEntryModal = function () {
        entryOverlay.classList.remove('open');
    };

    // ── History Modal Logic ──
    const historyOverlay = document.getElementById('historyModalOverlay');
    const historyLoading = document.getElementById('historyLoading');
    const historyTimeline = document.getElementById('historyTimeline');
    const historyEmpty   = document.getElementById('historyEmpty');
    const historySub     = document.getElementById('historyAccountSub');

    window.closeHistoryModal = function () {
        historyOverlay.classList.remove('open');
    };

    document.querySelectorAll('.btn-row-history').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            historyOverlay.classList.add('open');
            historyLoading.style.display = 'block';
            historyTimeline.style.display = 'none';
            historyEmpty.style.display = 'none';
            historyTimeline.innerHTML = '';
            historySub.textContent = 'Loading...';

            fetch(`/projects/smm-sheet/${id}/history`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(res => {
                historyLoading.style.display = 'none';
                if (!res.success || !res.smm_sheet) {
                    historyEmpty.style.display = 'flex';
                    return;
                }

                const sheet = res.smm_sheet;
                historySub.textContent = `${sheet.account_name} — ${sheet.product_name} (Committed: ${sheet.committed_posters} Posters, ${sheet.committed_videos} Videos)`;

                const logs = res.logs || [];
                if (logs.length === 0) {
                    historyEmpty.style.display = 'flex';
                    return;
                }

                historyTimeline.style.display = 'flex';
                historyTimeline.innerHTML = logs.map(l => {
                    let changes = [];
                    if (l.posters_added !== 0) {
                        changes.push(`<span class="smm-tbadge poster">Posters: ${l.design_posters_before + l.dm_posters_before} ➔ ${l.design_posters_after + l.dm_posters_after} (${l.posters_added > 0 ? '+' : ''}${l.posters_added})</span>`);
                    }
                    if (l.videos_added !== 0) {
                        changes.push(`<span class="smm-tbadge video">Videos: ${l.design_videos_before + l.dm_videos_before} ➔ ${l.design_videos_after + l.dm_videos_after} (${l.videos_added > 0 ? '+' : ''}${l.videos_added})</span>`);
                    }
                    if (changes.length === 0 && l.action === 'production_sync') {
                        changes.push(`<span class="smm-tbadge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;">✨ Initiated &amp; Synced from Production</span>`);
                    }

                    return `
                        <div class="smm-timeline-item">
                            <div class="smm-timeline-dot"></div>
                            <div class="smm-timeline-head">
                                <div class="smm-timeline-user">
                                    <span class="smm-account-avatar" style="width:22px;height:22px;font-size:10px;">${l.user_initial}</span>
                                    <span>${l.user_name}</span>
                                    <span class="smm-tbadge team">${l.department_label}</span>
                                </div>
                                <span class="smm-timeline-time" title="${l.created_at_formatted}">${l.created_at_human}</span>
                            </div>
                            <div class="smm-timeline-body">
                                <div class="smm-timeline-badges">${changes.join(' ')}</div>
                                ${l.remarks ? `<div style="font-size:12px;color:#475569;font-style:italic;">💬 "${l.remarks}"</div>` : ''}
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;">Logged on: ${l.created_at_formatted}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            })
            .catch(err => {
                historyLoading.style.display = 'none';
                historyEmpty.style.display = 'flex';
                historyEmpty.querySelector('.smm-empty-sub').textContent = 'Error loading history: ' + err.message;
            });
        });
    });

    // Close on overlay click
    [entryOverlay, historyOverlay].forEach(ov => {
        ov.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('open');
            }
        });
    });
});
</script>
@endpush
@endsection
