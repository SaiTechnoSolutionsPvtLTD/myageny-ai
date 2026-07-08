@extends('layouts.app')

@section('title', 'SMM Report - myAgenci.ai')

@push('styles')
<style>
/* ════════════════════════════════════════════════════════════════════
   SMM REPORT — Orange Brand Theme
   ════════════════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.smm-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(ellipse at 0% 0%, rgba(249,115,22,.10) 0%, transparent 40%),
        radial-gradient(ellipse at 100% 100%, rgba(234,88,12,.07) 0%, transparent 40%),
        #fff7ed;
    font-family: 'Inter', sans-serif;
}
.smm-shell { max-width: 1700px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }

/* ── Hero topbar ─────────────────────────────────────────────────── */
.smm-hero {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 24px;
    padding: 28px 32px;
    border-radius: 28px;
    background: linear-gradient(135deg, #fff7ed 0%, #ffffff 48%, #fef9f0 100%);
    border: 1px solid rgba(249,115,22,.2);
    box-shadow:
        0 0 0 1px rgba(255,255,255,.8) inset,
        0 20px 48px rgba(249,115,22,.08),
        0 4px 12px rgba(249,115,22,.04);
    position: relative;
    overflow: hidden;
}
.smm-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(251,146,60,.14) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.smm-hero-badge {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 6px 14px; border-radius: 999px;
    background: linear-gradient(135deg, #fed7aa, #fdba74);
    border: 1px solid rgba(249,115,22,.25);
    color: #c2410c; font-size: 10px; font-weight: 800;
    letter-spacing: .1em; text-transform: uppercase;
    margin-bottom: 14px; width: fit-content;
}
.smm-hero-badge svg { width: 13px; height: 13px; }
.smm-hero-h1 { margin: 0 0 8px; font-size: 34px; font-weight: 900; color: #7c2d12; line-height: 1.05; letter-spacing: -.5px; }
.smm-hero-sub { margin: 0; font-size: 14px; color: #64748b; line-height: 1.7; max-width: 680px; }
.smm-hero-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.smm-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 18px; border-radius: 14px;
    border: 1px solid #e2e8f0; background: #ffffff;
    color: #374151; font-size: 13px; font-weight: 700;
    text-decoration: none; white-space: nowrap;
    transition: all .18s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.smm-btn:hover {
    border-color: #fdba74; background: #fff7ed; color: #c2410c;
    box-shadow: 0 4px 12px rgba(249,115,22,.12); transform: translateY(-1px);
}
.smm-btn svg { width: 15px; height: 15px; flex-shrink: 0; }
.smm-btn-export {
    background: linear-gradient(135deg, #c2410c, #ea580c);
    border-color: transparent; color: #fff;
    box-shadow: 0 6px 20px rgba(234,88,12,.32);
}
.smm-btn-export:hover {
    background: linear-gradient(135deg, #9a3412, #c2410c);
    color: #fff; border-color: transparent;
    box-shadow: 0 8px 24px rgba(234,88,12,.38);
}

/* ── Stat cards ──────────────────────────────────────────────────── */
.smm-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.smm-stat {
    padding: 18px 20px; border-radius: 20px;
    border: 1px solid #e5e7eb; background: #fff;
    display: flex; align-items: center; gap: 14px;
    box-shadow: 0 4px 14px rgba(15,23,42,.04);
    transition: transform .16s, box-shadow .16s;
}
.smm-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,23,42,.07); }
.smm-stat-icon { width: 46px; height: 46px; border-radius: 14px; display: grid; place-items: center; flex-shrink: 0; }
.smm-stat-icon svg { width: 20px; height: 20px; }
.smm-stat-icon.s-total { background: linear-gradient(135deg, #fff7ed, #fed7aa); color: #ea580c; }
.smm-stat-icon.s-done  { background: linear-gradient(135deg, #f0fdf4, #dcfce7); color: #16a34a; }
.smm-stat-icon.s-pend  { background: linear-gradient(135deg, #fef9c3, #fef08a); color: #ca8a04; }
.smm-stat-icon.s-over  { background: linear-gradient(135deg, #fef2f2, #fee2e2); color: #dc2626; }
.smm-stat-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
.smm-stat-value { font-size: 28px; font-weight: 900; line-height: 1; }
.smm-stat-value.c-orange { color: #ea580c; }
.smm-stat-value.c-green  { color: #16a34a; }
.smm-stat-value.c-yellow { color: #ca8a04; }
.smm-stat-value.c-red    { color: #dc2626; }

/* ── Filter panel ────────────────────────────────────────────────── */
.smm-filter-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; box-shadow: 0 4px 16px rgba(15,23,42,.04); overflow: hidden; }
.smm-filter-head {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 16px 22px; border-bottom: 1px solid #fef3e2;
    background: linear-gradient(90deg, #fffbf5 0%, #fff 100%);
}
.smm-filter-head-left { display: flex; align-items: center; gap: 10px; }
.smm-filter-head-icon { width: 34px; height: 34px; border-radius: 10px; background: linear-gradient(135deg, #fff7ed, #fed7aa); display: grid; place-items: center; color: #ea580c; }
.smm-filter-head-icon svg { width: 16px; height: 16px; }
.smm-filter-title { font-size: 14px; font-weight: 800; color: #111827; }
.smm-filter-sub   { font-size: 12px; color: #9ca3af; margin-top: 1px; }
.smm-filter-body  { padding: 20px 22px; }
.smm-filter-grid  { display: grid; grid-template-columns: 1fr 1fr 2fr 1.5fr 1.2fr auto; gap: 14px; align-items: end; }
.smm-field { display: flex; flex-direction: column; gap: 6px; }
.smm-label { font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; color: #64748b; }
.smm-input, .smm-select {
    width: 100%; padding: 10px 13px; border-radius: 12px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    color: #0f172a; font-size: 13px; font-weight: 500;
    outline: none; transition: all .16s; font-family: inherit;
}
.smm-input:focus, .smm-select:focus {
    border-color: #f97316; box-shadow: 0 0 0 4px rgba(249,115,22,.10); background: #fff;
}
.smm-btn-apply {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 20px; border-radius: 12px;
    background: linear-gradient(135deg, #ea580c, #f97316);
    border: none; color: #fff; font-size: 13px; font-weight: 800;
    cursor: pointer; white-space: nowrap;
    box-shadow: 0 4px 14px rgba(234,88,12,.24); transition: all .16s; width: 100%;
}
.smm-btn-apply:hover { background: linear-gradient(135deg, #c2410c, #ea580c); box-shadow: 0 6px 18px rgba(234,88,12,.30); }
.smm-btn-reset { font-size: 12px; font-weight: 700; color: #6b7280; text-decoration: none; padding: 4px 8px; border-radius: 8px; transition: all .14s; }
.smm-btn-reset:hover { background: #f1f5f9; color: #374151; }

/* ── Tabs ────────────────────────────────────────────────────────── */
.smm-tabs-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
.smm-tabs-group { display: inline-flex; gap: 6px; padding: 6px; border-radius: 18px; background: rgba(255,255,255,.95); border: 1px solid #e2e8f0; box-shadow: 0 4px 14px rgba(15,23,42,.06); }
.smm-tab {
    padding: 10px 22px; border-radius: 13px; border: none; background: transparent;
    color: #6b7280; font-size: 13px; font-weight: 700; cursor: pointer; transition: all .18s; white-space: nowrap; font-family: inherit;
}
.smm-tab:hover { background: #fff7ed; color: #ea580c; }
.smm-tab.active { background: linear-gradient(135deg, #ea580c, #f97316); color: #fff; box-shadow: 0 6px 18px rgba(234,88,12,.28); }
.smm-records-label { font-size: 12px; color: #94a3b8; font-weight: 500; }
.smm-records-label strong { color: #374151; }

/* ── Table card ──────────────────────────────────────────────────── */
.smm-table-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; box-shadow: 0 4px 16px rgba(15,23,42,.04); overflow: hidden; }
.smm-table-card-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 16px 22px; border-bottom: 1px solid #fef3e2;
    background: linear-gradient(90deg, #fffbf5 0%, #fff 100%);
}
.smm-table-card-title { font-size: 14px; font-weight: 800; color: #111827; }
.smm-table-card-sub   { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.smm-scroll { overflow-x: auto; }
.smm-tbl { width: 100%; border-collapse: collapse; min-width: 1600px; }
.smm-tbl thead { position: sticky; top: 0; z-index: 10; }
.smm-tbl th {
    padding: 11px 14px; text-align: left;
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
.smm-tbl tbody tr:hover td { background: #fff7ed; }
.smm-tbl tbody tr:last-child td { border-bottom: none; }
.smm-tbl td.sep-design { border-left: 2px solid #bbf7d0; }
.smm-tbl td.sep-dm     { border-left: 2px solid #c4b5fd; }
.smm-tbl td.td-center  { text-align: center; }

/* Account cell */
.smm-account-name { font-weight: 800; font-size: 13px; color: #7c2d12; display: flex; align-items: center; gap: 7px; }
.smm-account-avatar {
    width: 30px; height: 30px; border-radius: 9px;
    background: linear-gradient(135deg, #fed7aa, #fdba74);
    color: #c2410c; font-size: 13px; font-weight: 900;
    display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.smm-product-pill { display: inline-block; padding: 4px 10px; border-radius: 8px; background: #fff7ed; border: 1px solid #fdba74; color: #c2410c; font-size: 11px; font-weight: 700; }
.smm-month-chip { display: inline-block; padding: 4px 10px; border-radius: 8px; background: linear-gradient(135deg, #fed7aa, #fef3c7); color: #92400e; font-size: 11px; font-weight: 800; white-space: nowrap; }
.smm-num { font-weight: 700; text-align: center; }
.smm-num.zero   { color: #d1d5db; }
.smm-num.commit { color: #ea580c; font-weight: 800; }
.smm-num.done   { color: #16a34a; font-weight: 800; }
.smm-num.pend   { color: #ca8a04; font-weight: 800; }
.smm-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 11px; border-radius: 999px; font-size: 11px; font-weight: 800; white-space: nowrap; }
.smm-badge.completed { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
.smm-badge.pending   { background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; }
.smm-badge.overdue   { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
.smm-badge-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; background: currentColor; }
.smm-prog-track { width: 100%; height: 5px; border-radius: 999px; background: #f1f5f9; margin-top: 5px; overflow: hidden; }
.smm-prog-fill { height: 5px; border-radius: 999px; background: linear-gradient(90deg, #f97316, #ea580c); transition: width .3s; }
.smm-prog-fill.success { background: linear-gradient(90deg, #22c55e, #16a34a); }
.smm-prog-fill.danger  { background: linear-gradient(90deg, #f87171, #dc2626); }
.smm-prog-label { font-size: 10px; color: #94a3b8; margin-top: 3px; }
.smm-person { font-size: 12px; color: #475569; max-width: 160px; word-break: break-word; }
.smm-person.empty { color: #d1d5db; font-style: italic; }
.smm-date { font-size: 12px; color: #64748b; white-space: nowrap; }
.smm-tenure { font-size: 13px; font-weight: 700; color: #374151; }
.smm-group-header td {
    background: linear-gradient(90deg, #fff7ed 0%, #f8fafc 100%);
    border-top: 2px solid #fed7aa; border-bottom: 1px solid #e2e8f0;
    padding: 11px 16px; font-size: 13px; font-weight: 800; color: #c2410c;
}
.smm-group-count { display: inline-flex; align-items: center; justify-content: center; padding: 2px 8px; border-radius: 999px; background: #fed7aa; color: #c2410c; font-size: 11px; font-weight: 800; margin-left: 8px; }
.smm-sub-arrow { color: #fdba74; font-size: 16px; margin-right: 4px; }
.smm-empty { padding: 80px 40px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 14px; }
.smm-empty-icon { width: 72px; height: 72px; border-radius: 24px; background: linear-gradient(135deg, #fff7ed, #fed7aa); display: grid; place-items: center; color: #ea580c; margin-bottom: 4px; }
.smm-empty-icon svg { width: 34px; height: 34px; }
.smm-empty-title { font-size: 18px; font-weight: 800; color: #374151; }
.smm-empty-sub   { font-size: 14px; color: #94a3b8; max-width: 380px; line-height: 1.6; }

@media (max-width: 1280px) {
    .smm-stats { grid-template-columns: repeat(2, 1fr); }
    .smm-filter-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .smm-page { padding: 16px; }
    .smm-hero { grid-template-columns: 1fr; }
    .smm-stats { grid-template-columns: 1fr 1fr; }
    .smm-filter-grid { grid-template-columns: 1fr; }
    .smm-tabs-row { flex-direction: column; align-items: flex-start; }
    .smm-hero-h1 { font-size: 26px; }
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
@endphp

<div class="smm-page">
<div class="smm-shell">

    {{-- ── Hero ── --}}
    <header class="smm-hero">
        <div>
            <div class="smm-hero-badge">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                CRM Reports
            </div>
            <h1 class="smm-hero-h1">SMM Report</h1>
            <p class="smm-hero-sub">Social Media Marketing deliverables tracker — monitor committed poster & video counts, Design team and DM team completions, overdue alerts, and status across all lead accounts.</p>
        </div>
        <div class="smm-hero-actions">
            <a href="{{ route('reports.crm.smm.export', request()->query()) }}" class="smm-btn smm-btn-export">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                Export Excel
            </a>
            <a href="{{ route('reports.crm.index') }}" class="smm-btn">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
        </div>
    </header>

    {{-- ── Stats ── --}}
    <div class="smm-stats">
        <div class="smm-stat">
            <span class="smm-stat-icon s-total">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg>
            </span>
            <div><div class="smm-stat-label">Total Records</div><div class="smm-stat-value c-orange">{{ $total }}</div></div>
        </div>
        <div class="smm-stat">
            <span class="smm-stat-icon s-done">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </span>
            <div><div class="smm-stat-label">Completed</div><div class="smm-stat-value c-green">{{ $completed }}</div></div>
        </div>
        <div class="smm-stat">
            <span class="smm-stat-icon s-pend">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div><div class="smm-stat-label">Pending</div><div class="smm-stat-value c-yellow">{{ $pending }}</div></div>
        </div>
        <div class="smm-stat">
            <span class="smm-stat-icon s-over">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </span>
            <div><div class="smm-stat-label">Overdue</div><div class="smm-stat-value c-red">{{ $overdue }}</div></div>
        </div>
    </div>

    {{-- ── Filter ── --}}
    <div class="smm-filter-card">
        <div class="smm-filter-head">
            <div class="smm-filter-head-left">
                <span class="smm-filter-head-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                </span>
                <div>
                    <div class="smm-filter-title">Filters</div>
                    <div class="smm-filter-sub">Narrow down by delivery date, lead, product or status</div>
                </div>
            </div>
            @if(request()->hasAny(['date_from','date_to','lead_id','product_id','status']))
                <a href="{{ route('reports.crm.smm') }}" class="smm-btn-reset">✕ Reset Filters</a>
            @endif
        </div>
        <div class="smm-filter-body">
            <form method="GET" action="{{ route('reports.crm.smm') }}" class="smm-filter-grid">
                <div class="smm-field">
                    <label class="smm-label" for="smm_date_from">Delivery From</label>
                    <input id="smm_date_from" type="date" name="date_from" class="smm-input" value="{{ $filters['date_from'] }}">
                </div>
                <div class="smm-field">
                    <label class="smm-label" for="smm_date_to">Delivery To</label>
                    <input id="smm_date_to" type="date" name="date_to" class="smm-input" value="{{ $filters['date_to'] }}">
                </div>
                <div class="smm-field">
                    <label class="smm-label" for="smm_lead_id">Account / Lead</label>
                    <select id="smm_lead_id" name="lead_id" class="smm-select">
                        <option value="">All Accounts</option>
                        @foreach($leads as $lead)
                            @php $lbl = trim($lead->company_name ?: ($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')); @endphp
                            <option value="{{ $lead->id }}" @selected((string)$filters['lead_id'] === (string)$lead->id)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="smm-field">
                    <label class="smm-label" for="smm_product_id">Product</label>
                    <select id="smm_product_id" name="product_id" class="smm-select">
                        <option value="">All SMM Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string)$filters['product_id'] === (string)$product->id)>{{ $product->package_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="smm-field">
                    <label class="smm-label" for="smm_status">Status</label>
                    <select id="smm_status" name="status" class="smm-select">
                        <option value="">All Status</option>
                        <option value="completed" @selected($filters['status'] === 'completed')>✅ Completed</option>
                        <option value="pending"   @selected($filters['status'] === 'pending')>⏳ Pending</option>
                        <option value="overdue"   @selected($filters['status'] === 'overdue')>🔴 Overdue</option>
                    </select>
                </div>
                <input type="hidden" name="view" value="{{ $activeTab }}">
                <div class="smm-field">
                    <label class="smm-label">&nbsp;</label>
                    <button type="submit" class="smm-btn-apply">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Tabs row ── --}}
    <div class="smm-tabs-row">
        <div class="smm-tabs-group" id="smmTabsGroup">
            <button type="button" class="smm-tab {{ $activeTab === 'product' ? 'active' : '' }}" data-view="product">
                📋 Product Wise
            </button>
            <button type="button" class="smm-tab {{ $activeTab === 'lead' ? 'active' : '' }}" data-view="lead">
                🏢 Lead / Account Wise
            </button>
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
    <div id="panel-product" class="smm-table-card" style="{{ $activeTab !== 'product' ? 'display:none' : '' }}">
        <div class="smm-table-card-head">
            <div>
                <div class="smm-table-card-title">Product Wise View</div>
                <div class="smm-table-card-sub">One row = one product order per lead account</div>
            </div>
            <div style="font-size:12px;color:#94a3b8;">{{ $total }} records</div>
        </div>
        <div class="smm-scroll">
            <table class="smm-tbl">
                <thead>
                    <tr>
                        <th rowspan="2" class="th-accent" style="min-width:180px;">Account Name</th>
                        <th rowspan="2" class="th-accent">Month</th>
                        <th rowspan="2" class="th-accent">Product</th>
                        <th rowspan="2" class="th-accent">Start Date</th>
                        <th rowspan="2" class="th-accent">End Date</th>
                        <th rowspan="2" class="th-accent th-center">Tenure</th>
                        <th rowspan="2" class="th-center" style="color:#ea580c;">Committed<br>Posters</th>
                        <th rowspan="2" class="th-center" style="color:#ea580c;">Committed<br>Videos</th>
                        <th colspan="5" class="th-group th-grp-design">🎨 Design Team</th>
                        <th colspan="5" class="th-group th-grp-dm">📢 Digital Marketing Team</th>
                        <th rowspan="2" class="th-center">Status</th>
                    </tr>
                    <tr>
                        <th class="th-design th-center sep-design">Done Posters</th>
                        <th class="th-design th-center">Pending Posters</th>
                        <th class="th-design th-center">Done Videos</th>
                        <th class="th-design th-center">Pending Videos</th>
                        <th class="th-design">Allocated To</th>
                        <th class="th-dm th-center sep-dm">Done Posters</th>
                        <th class="th-dm th-center">Pending Posters</th>
                        <th class="th-dm th-center">Done Videos</th>
                        <th class="th-dm th-center">Pending Videos</th>
                        <th class="th-dm">Allocated To</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $tc  = $row['committed_posters'] + $row['committed_videos'];
                            $td  = $row['design_completed_posters'] + $row['design_completed_videos']
                                 + $row['dm_completed_posters'] + $row['dm_completed_videos'];
                            $pct    = $tc > 0 ? min(100, round($td / $tc * 100)) : 0;
                            $barCls = $pct >= 100 ? 'success' : ($row['status'] === 'overdue' ? 'danger' : '');
                            $ini    = strtoupper(substr($row['account_name'], 0, 1));
                        @endphp
                        <tr>
                            <td>
                                <div class="smm-account-name">
                                    <span class="smm-account-avatar">{{ $ini }}</span>
                                    {{ $row['account_name'] }}
                                </div>
                            </td>
                            <td><span class="smm-month-chip">{{ $row['month'] }}</span></td>
                            <td><span class="smm-product-pill">{{ $row['product_name'] }}</span></td>
                            <td><span class="smm-date">{{ $row['start_date'] ? \Carbon\Carbon::parse($row['start_date'])->format('d M Y') : '—' }}</span></td>
                            <td><span class="smm-date">{{ $row['end_date'] ? \Carbon\Carbon::parse($row['end_date'])->format('d M Y') : '—' }}</span></td>
                            <td class="td-center">
                                @if($row['tenure'] !== null)
                                    <span class="smm-tenure">{{ $row['tenure'] }}<span style="font-size:10px;color:#94a3b8;font-weight:500;"> mo</span></span>
                                @else <span style="color:#d1d5db;">—</span> @endif
                            </td>
                            <td class="td-center"><span class="smm-num {{ $row['committed_posters'] ? 'commit' : 'zero' }}">{{ $row['committed_posters'] ?: '—' }}</span></td>
                            <td class="td-center"><span class="smm-num {{ $row['committed_videos'] ? 'commit' : 'zero' }}">{{ $row['committed_videos'] ?: '—' }}</span></td>
                            <td class="td-center sep-design"><span class="smm-num {{ $row['design_completed_posters'] ? 'done' : 'zero' }}">{{ $row['design_completed_posters'] ?: '—' }}</span></td>
                            <td class="td-center"><span class="smm-num {{ $row['design_pending_posters'] ? 'pend' : 'zero' }}">{{ $row['design_pending_posters'] ?: '—' }}</span></td>
                            <td class="td-center"><span class="smm-num {{ $row['design_completed_videos'] ? 'done' : 'zero' }}">{{ $row['design_completed_videos'] ?: '—' }}</span></td>
                            <td class="td-center"><span class="smm-num {{ $row['design_pending_videos'] ? 'pend' : 'zero' }}">{{ $row['design_pending_videos'] ?: '—' }}</span></td>
                            <td>@if($row['design_persons'] && $row['design_persons'] !== '-')<span class="smm-person">{{ $row['design_persons'] }}</span>@else<span class="smm-person empty">—</span>@endif</td>
                            <td class="td-center sep-dm"><span class="smm-num {{ $row['dm_completed_posters'] ? 'done' : 'zero' }}">{{ $row['dm_completed_posters'] ?: '—' }}</span></td>
                            <td class="td-center"><span class="smm-num {{ $row['dm_pending_posters'] ? 'pend' : 'zero' }}">{{ $row['dm_pending_posters'] ?: '—' }}</span></td>
                            <td class="td-center"><span class="smm-num {{ $row['dm_completed_videos'] ? 'done' : 'zero' }}">{{ $row['dm_completed_videos'] ?: '—' }}</span></td>
                            <td class="td-center"><span class="smm-num {{ $row['dm_pending_videos'] ? 'pend' : 'zero' }}">{{ $row['dm_pending_videos'] ?: '—' }}</span></td>
                            <td>@if($row['dm_persons'] && $row['dm_persons'] !== '-')<span class="smm-person">{{ $row['dm_persons'] }}</span>@else<span class="smm-person empty">—</span>@endif</td>
                            <td class="td-center">
                                <span class="smm-badge {{ $row['status'] }}">
                                    <span class="smm-badge-dot"></span> {{ ucfirst($row['status']) }}
                                </span>
                                @if($tc > 0)
                                    <div class="smm-prog-track"><div class="smm-prog-fill {{ $barCls }}" style="width:{{ $pct }}%"></div></div>
                                    <div class="smm-prog-label">{{ $pct }}% complete</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="19">
                            <div class="smm-empty">
                                <div class="smm-empty-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                                <div class="smm-empty-title">No SMM Records Found</div>
                                <div class="smm-empty-sub">No records match the current filters. Try adjusting the date range or clearing your filters.</div>
                                <a href="{{ route('reports.crm.smm') }}" class="smm-btn" style="margin-top:4px;">Clear Filters</a>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ LEAD WISE TABLE ═══ --}}
    <div id="panel-lead" class="smm-table-card" style="{{ $activeTab !== 'lead' ? 'display:none' : '' }}">
        <div class="smm-table-card-head">
            <div>
                <div class="smm-table-card-title">Lead / Account Wise View</div>
                <div class="smm-table-card-sub">Rows grouped by account — all products per lead listed together</div>
            </div>
            <div style="font-size:12px;color:#94a3b8;">{{ $byLead->count() }} account{{ $byLead->count() !== 1 ? 's' : '' }}</div>
        </div>
        <div class="smm-scroll">
            <table class="smm-tbl">
                <thead>
                    <tr>
                        <th rowspan="2" class="th-accent" style="min-width:180px;">Account Name</th>
                        <th rowspan="2" class="th-accent">Month</th>
                        <th rowspan="2" class="th-accent">Product</th>
                        <th rowspan="2" class="th-accent">Start Date</th>
                        <th rowspan="2" class="th-accent">End Date</th>
                        <th rowspan="2" class="th-accent th-center">Tenure</th>
                        <th rowspan="2" class="th-center" style="color:#ea580c;">Committed<br>Posters</th>
                        <th rowspan="2" class="th-center" style="color:#ea580c;">Committed<br>Videos</th>
                        <th colspan="5" class="th-group th-grp-design">🎨 Design Team</th>
                        <th colspan="5" class="th-group th-grp-dm">📢 Digital Marketing Team</th>
                        <th rowspan="2" class="th-center">Status</th>
                    </tr>
                    <tr>
                        <th class="th-design th-center sep-design">Done Posters</th>
                        <th class="th-design th-center">Pending Posters</th>
                        <th class="th-design th-center">Done Videos</th>
                        <th class="th-design th-center">Pending Videos</th>
                        <th class="th-design">Allocated To</th>
                        <th class="th-dm th-center sep-dm">Done Posters</th>
                        <th class="th-dm th-center">Pending Posters</th>
                        <th class="th-dm th-center">Done Videos</th>
                        <th class="th-dm th-center">Pending Videos</th>
                        <th class="th-dm">Allocated To</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byLead as $accountName => $accountRows)
                        <tr class="smm-group-header">
                            <td colspan="19">
                                <span class="smm-account-avatar" style="display:inline-flex;width:26px;height:26px;border-radius:8px;font-size:12px;vertical-align:middle;margin-right:8px;">{{ strtoupper(substr($accountName, 0, 1)) }}</span>
                                {{ $accountName }}
                                <span class="smm-group-count">{{ $accountRows->count() }} product{{ $accountRows->count() !== 1 ? 's' : '' }}</span>
                            </td>
                        </tr>

                        @foreach($accountRows as $row)
                            @php
                                $tc  = $row['committed_posters'] + $row['committed_videos'];
                                $td  = $row['design_completed_posters'] + $row['design_completed_videos']
                                     + $row['dm_completed_posters'] + $row['dm_completed_videos'];
                                $pct    = $tc > 0 ? min(100, round($td / $tc * 100)) : 0;
                                $barCls = $pct >= 100 ? 'success' : ($row['status'] === 'overdue' ? 'danger' : '');
                            @endphp
                            <tr>
                                <td style="padding-left:28px;"><span style="color:#fdba74;font-size:16px;margin-right:4px;">↳</span><span style="font-size:12px;color:#64748b;">{{ $row['account_name'] }}</span></td>
                                <td><span class="smm-month-chip">{{ $row['month'] }}</span></td>
                                <td><span class="smm-product-pill">{{ $row['product_name'] }}</span></td>
                                <td><span class="smm-date">{{ $row['start_date'] ? \Carbon\Carbon::parse($row['start_date'])->format('d M Y') : '—' }}</span></td>
                                <td><span class="smm-date">{{ $row['end_date'] ? \Carbon\Carbon::parse($row['end_date'])->format('d M Y') : '—' }}</span></td>
                                <td class="td-center">
                                    @if($row['tenure'] !== null)<span class="smm-tenure">{{ $row['tenure'] }}<span style="font-size:10px;color:#94a3b8;font-weight:500;"> mo</span></span>
                                    @else<span style="color:#d1d5db;">—</span>@endif
                                </td>
                                <td class="td-center"><span class="smm-num {{ $row['committed_posters'] ? 'commit' : 'zero' }}">{{ $row['committed_posters'] ?: '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['committed_videos'] ? 'commit' : 'zero' }}">{{ $row['committed_videos'] ?: '—' }}</span></td>
                                <td class="td-center sep-design"><span class="smm-num {{ $row['design_completed_posters'] ? 'done' : 'zero' }}">{{ $row['design_completed_posters'] ?: '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['design_pending_posters'] ? 'pend' : 'zero' }}">{{ $row['design_pending_posters'] ?: '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['design_completed_videos'] ? 'done' : 'zero' }}">{{ $row['design_completed_videos'] ?: '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['design_pending_videos'] ? 'pend' : 'zero' }}">{{ $row['design_pending_videos'] ?: '—' }}</span></td>
                                <td>@if($row['design_persons'] && $row['design_persons'] !== '-')<span class="smm-person">{{ $row['design_persons'] }}</span>@else<span class="smm-person empty">—</span>@endif</td>
                                <td class="td-center sep-dm"><span class="smm-num {{ $row['dm_completed_posters'] ? 'done' : 'zero' }}">{{ $row['dm_completed_posters'] ?: '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['dm_pending_posters'] ? 'pend' : 'zero' }}">{{ $row['dm_pending_posters'] ?: '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['dm_completed_videos'] ? 'done' : 'zero' }}">{{ $row['dm_completed_videos'] ?: '—' }}</span></td>
                                <td class="td-center"><span class="smm-num {{ $row['dm_pending_videos'] ? 'pend' : 'zero' }}">{{ $row['dm_pending_videos'] ?: '—' }}</span></td>
                                <td>@if($row['dm_persons'] && $row['dm_persons'] !== '-')<span class="smm-person">{{ $row['dm_persons'] }}</span>@else<span class="smm-person empty">—</span>@endif</td>
                                <td class="td-center">
                                    <span class="smm-badge {{ $row['status'] }}"><span class="smm-badge-dot"></span> {{ ucfirst($row['status']) }}</span>
                                    @if($tc > 0)
                                        <div class="smm-prog-track"><div class="smm-prog-fill {{ $barCls }}" style="width:{{ $pct }}%"></div></div>
                                        <div class="smm-prog-label">{{ $pct }}% complete</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="19">
                            <div class="smm-empty">
                                <div class="smm-empty-icon"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                                <div class="smm-empty-title">No SMM Records Found</div>
                                <div class="smm-empty-sub">No records match the current filters.</div>
                                <a href="{{ route('reports.crm.smm') }}" class="smm-btn" style="margin-top:4px;">Clear Filters</a>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>{{-- /smm-shell --}}
</div>{{-- /smm-page --}}
@endsection

@push('scripts')
<script>
(() => {
    const tabBtns = document.querySelectorAll('#smmTabsGroup .smm-tab');
    const panels  = { product: document.getElementById('panel-product'), lead: document.getElementById('panel-lead') };
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            const url = new URL(window.location.href);
            url.searchParams.set('view', view);
            history.replaceState({}, '', url.toString());
            const hidden = document.querySelector('input[name="view"]');
            if (hidden) hidden.value = view;
            tabBtns.forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            Object.values(panels).forEach(p => { if (p) p.style.display = 'none'; });
            if (panels[view]) panels[view].style.display = '';
        });
    });
})();
</script>
@endpush
