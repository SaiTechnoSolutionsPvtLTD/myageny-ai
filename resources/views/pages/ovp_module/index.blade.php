@extends('layouts.app')

@section('title', 'OVP Module')

@push('styles')
<style>
.ovp-page { min-height:100%; background:linear-gradient(180deg,#f7f8fb 0%,#f3f5f9 100%); font-family:'Inter',sans-serif; }

/* Progress Bar & Processing Overlay for OVP Module */
.support-process-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(6px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
}
.support-process-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 36px 40px;
    width: 440px;
    max-width: 90vw;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.support-process-icon-wrap {
    position: relative;
    width: 72px;
    height: 72px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.support-process-spinner {
    position: absolute;
    inset: 0;
    border: 3px solid #f1f5f9;
    border-top-color: #fe5f04;
    border-radius: 50%;
    animation: processSpin 1s linear infinite;
}
@keyframes processSpin { to { transform: rotate(360deg); } }
.support-process-icon {
    font-size: 32px;
    color: #fe5f04;
    animation: pulseIcon 1.5s ease-in-out infinite alternate;
}
@keyframes pulseIcon {
    from { transform: scale(0.88); opacity: 0.85; }
    to { transform: scale(1.12); opacity: 1; }
}
.support-process-title { font-size: 19px; font-weight: 800; color: #111827; margin: 0 0 6px; }
.support-process-subtitle { font-size: 13px; color: #6b7280; margin: 0 0 24px; line-height: 1.5; }
.support-progress-wrapper { width: 100%; }
.support-progress-bar {
    width: 100%; height: 10px; background: #e2e8f0; border-radius: 999px; overflow: hidden; position: relative;
}
.support-progress-fill {
    height: 100%; width: 0%; background: linear-gradient(90deg, #059669 0%, #34d399 50%, #059669 100%);
    background-size: 200% 100%; border-radius: 999px; transition: width 0.3s ease; animation: gradientMove 2s linear infinite;
}
@keyframes gradientMove { 0% { background-position: 0% 0%; } 100% { background-position: 200% 0%; } }
.support-progress-status {
    display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 12px; font-weight: 700; color: #4b5563;
}
.ovp-topbar { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:0 28px; height:64px; background:#fff; border-bottom:1px solid #e5e7eb; }
.ovp-title { font-size:20px; font-weight:900; color:#111827; }
.ovp-breadcrumb { font-size:12px; color:#6b7280; margin-top:3px; }
.ovp-top-chip { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#f8fafc; border:1px solid #e2e8f0; color:#334155; font-size:12px; font-weight:700; }
.ovp-body { padding:22px 28px 34px; display:grid; gap:18px; }
.ovp-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:16px; }
.ovp-card { display:block; text-decoration:none; border-radius:22px; border:1px solid #e5e7eb; padding:18px; box-shadow:0 12px 34px rgba(15,23,42,.06); transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
.ovp-card:hover { transform:translateY(-2px); box-shadow:0 16px 38px rgba(15,23,42,.1); }
.ovp-card.new { background:linear-gradient(180deg,#eff6ff 0%,#ffffff 100%); border-color:#bfdbfe; }
.ovp-card.pending { background:linear-gradient(180deg,#fffaf3 0%,#ffffff 100%); border-color:#f6d7a7; }
.ovp-card.overdue { background:linear-gradient(180deg,#fff7ed 0%,#ffffff 100%); border-color:#fdba74; }
.ovp-card.approved { background:linear-gradient(180deg,#f3fcf5 0%,#ffffff 100%); border-color:#bce6c7; }
.ovp-card.reject { background:linear-gradient(180deg,#fff5f5 0%,#ffffff 100%); border-color:#f5c2c7; }
.ovp-card.is-active.new { box-shadow:0 0 0 3px rgba(59,130,246,.14), 0 16px 38px rgba(15,23,42,.1); }
.ovp-card.is-active.pending { box-shadow:0 0 0 3px rgba(245,158,11,.14), 0 16px 38px rgba(15,23,42,.1); }
.ovp-card.is-active.overdue { box-shadow:0 0 0 3px rgba(249,115,22,.14), 0 16px 38px rgba(15,23,42,.1); }
.ovp-card.is-active.approved { box-shadow:0 0 0 3px rgba(34,197,94,.14), 0 16px 38px rgba(15,23,42,.1); }
.ovp-card.is-active.reject { box-shadow:0 0 0 3px rgba(239,68,68,.12), 0 16px 38px rgba(15,23,42,.1); }
.ovp-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.ovp-card-title { font-size:16px; font-weight:900; color:#111827; }
.ovp-card-sub { margin-top:4px; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
.ovp-badge { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; border:1px solid transparent; }
.ovp-badge.new { color:#1d4ed8; background:#dbeafe; border-color:#bfdbfe; }
.ovp-badge.pending { color:#b45309; background:#fff1d6; border-color:#fcd9a2; }
.ovp-badge.overdue { color:#c2410c; background:#ffedd5; border-color:#fdba74; }
.ovp-badge.approved { color:#166534; background:#e9f9ee; border-color:#bce6c7; }
.ovp-badge.reject { color:#b91c1c; background:#fee2e2; border-color:#fecaca; }
.ovp-card-count { margin-top:18px; font-size:44px; line-height:1; font-weight:900; letter-spacing:-.05em; color:#111827; }
.ovp-table-card { background:#fff; border:1px solid #e5e7eb; border-radius:22px; overflow:hidden; box-shadow:0 12px 34px rgba(15,23,42,.06); }
.ovp-table-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid #eef2f7; background:#fcfcfd; }
.ovp-table-title { font-size:16px; font-weight:900; color:#111827; }
.ovp-table-sub { font-size:12px; color:#6b7280; margin-top:3px; }
.ovp-table-wrap { overflow-x:auto; }
.ovp-table { width:100%; border-collapse:collapse; }
.ovp-table th { padding:12px 14px; text-align:left; border-bottom:1px solid #eef2f7; background:#fafbfc; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; white-space:nowrap; }
.ovp-table td { padding:14px; border-bottom:1px solid #f3f4f6; font-size:13px; color:#111827; vertical-align:middle; }
.ovp-row { cursor: pointer; }
.ovp-table tbody tr.ovp-row:hover td { background:#f1f5f9; }
.ovp-product { font-weight:800; color:#111827; }
.ovp-meta { font-size:11px; color:#6b7280; margin-top:3px; }
.ovp-status-pill { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; border:1px solid transparent; }
.ovp-status-pill.new { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
.ovp-status-pill.pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.ovp-status-pill.overdue { color:#c2410c; background:#fff7ed; border-color:#fdba74; }
.ovp-status-pill.approved { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.ovp-status-pill.reject { color:#b91c1c; background:#fef2f2; border-color:#fecaca; }
.ovp-status-pill.allocation_pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.ovp-status-pill.allocated { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
.ovp-status-pill.submitted { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.ovp-action-btn { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; background:#eef2ff; border:1px solid #c7d2fe; color:#3730a3; font-size:12px; font-weight:800; text-decoration:none; cursor:pointer; }
.ovp-action-btn:hover { background:#e0e7ff; }
.ovp-action-btn.allocate { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.ovp-action-btn.allocate:hover { background:#ffedd5; }
.ovp-action-muted { font-size:12px; color:#9ca3af; }
.ovp-action-stack { display:grid; gap:10px; min-width:220px; }
.ovp-action-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.ovp-inline-form { display:grid; gap:8px; }
.ovp-inline-select { width:100%; min-height:40px; padding:9px 12px; border:1px solid #dbe1e8; border-radius:10px; background:#fff; font-size:12px; color:#111827; }
.ovp-inline-select:focus { outline:none; border-color:#93c5fd; box-shadow:0 0 0 4px rgba(59,130,246,.12); }
.ovp-inline-note { font-size:11px; color:#64748b; line-height:1.5; }
.ovp-assign-meta { display:grid; gap:4px; }
.ovp-assign-name { font-size:13px; font-weight:800; color:#111827; }
.ovp-assign-sub { font-size:11px; color:#64748b; line-height:1.5; }
.ovp-empty { padding:40px 20px; text-align:center; color:#6b7280; font-size:13px; }
.ovp-flash { padding:12px 14px; border-radius:14px; font-size:13px; font-weight:700; }
.ovp-flash.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.ovp-modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.5); z-index:1200; }
.ovp-modal.is-open { display:flex; }
.ovp-modal-card { width:min(100%, 1280px); max-height:94vh; display:flex; flex-direction:column; background:#fff; border-radius:24px; border:1px solid #e5e7eb; box-shadow:0 24px 80px rgba(15,23,42,.22); overflow:hidden; }
.ovp-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #eef2f7; }
.ovp-modal-title { font-size:18px; font-weight:900; color:#111827; }
.ovp-modal-sub { margin-top:4px; font-size:12px; color:#6b7280; }
.ovp-modal-close { border:none; background:#f8fafc; width:36px; height:36px; border-radius:10px; cursor:pointer; font-size:18px; color:#475569; }
.ovp-modal-form { display:flex; flex-direction:column; min-height:0; flex:1; overflow:hidden; }
.ovp-modal-body { padding:20px; display:grid; gap:20px; overflow-y:auto; flex:1; min-height:0; }
.ovp-review-layout { display:grid; gap:20px; min-height:min-content; }
.ovp-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
.ovp-field { display:grid; gap:7px; }
.ovp-field-full { grid-column:1/-1; }
.ovp-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; }
.ovp-input { width:100%; padding:11px 12px; border:1px solid #dbe1e8; border-radius:12px; background:#fff; font-size:13px; color:#111827; }
.ovp-input[readonly] { background:#f8fafc; color:#475569; }
.ovp-input:focus { outline:none; border-color:#93c5fd; box-shadow:0 0 0 4px rgba(59,130,246,.12); }
.ovp-form-shell { display:grid; gap:14px; padding:18px; border:1px solid #e5e7eb; border-radius:18px; background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%); min-height:min-content; }
.ovp-panel-title { font-size:13px; font-weight:900; color:#111827; }
.ovp-static-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
.ovp-static-card { display:grid; gap:6px; padding:14px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
.ovp-static-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; }
.ovp-static-value { font-size:13px; line-height:1.6; color:#111827; font-weight:700; word-break:break-word; }
.ovp-custom-list { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
.ovp-custom-item { display:grid; gap:6px; }
.ovp-custom-item-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; }
.ovp-custom-item-value { font-size:13px; color:#111827; line-height:1.6; word-break:break-word; }
.ovp-custom-empty { padding:16px; border:1px dashed #cbd5e1; border-radius:14px; background:#fff; color:#94a3b8; font-size:13px; text-align:center; }
.ovp-custom-link { color:#1d4ed8; font-weight:700; text-decoration:none; }
.ovp-custom-link:hover { text-decoration:underline; }
.ovp-choice-group { display:flex; flex-direction:column; gap:8px; padding:10px 12px; border:1px solid #dbe1e8; border-radius:12px; background:#fff; }
.ovp-choice { display:flex; align-items:center; gap:8px; font-size:13px; color:#111827; }
.ovp-choice input { accent-color:#2563eb; }
.ovp-help { font-size:11px; color:#94a3b8; }
.ovp-file-note { margin-top:8px; font-size:11px; color:#64748b; }
.ovp-select-wrap { position:relative; }
.ovp-select-wrap::after { content:''; position:absolute; right:14px; top:50%; width:8px; height:8px; border-right:2px solid #94a3b8; border-bottom:2px solid #94a3b8; transform:translateY(-70%) rotate(45deg); pointer-events:none; }
.ovp-input.ovp-select { appearance:none; -webkit-appearance:none; padding-right:36px; cursor:pointer; }
.ovp-modal-actions { display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:16px 20px 20px; border-top:1px solid #eef2f7; background:#fff; position:sticky; bottom:0; z-index:2; }
.ovp-btn { display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:12px; border:1px solid #d1d5db; background:#fff; color:#111827; font-size:13px; font-weight:800; cursor:pointer; }
.ovp-btn.approve { background:#166534; border-color:#166534; color:#fff; }
.ovp-btn.reject { background:#b91c1c; border-color:#b91c1c; color:#fff; }
.ovp-btn.allocate { background:#ea580c; border-color:#ea580c; color:#fff; }
@media (max-width: 1200px) {
    .ovp-static-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .ovp-custom-list { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width: 768px) {
    .ovp-form-grid { grid-template-columns:1fr; }
    .ovp-static-grid { grid-template-columns:1fr; }
    .ovp-custom-list { grid-template-columns:1fr; }
    .ovp-modal-body { padding:16px; }
    .ovp-modal-actions { padding:14px 16px 16px; }
}
@media (max-width: 1400px) {
    .ovp-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
}
@media (max-width: 1100px) {
    .ovp-grid { grid-template-columns:1fr; }
}
@media (max-width: 768px) {
    .ovp-topbar { height:auto; padding:16px 18px; align-items:flex-start; flex-direction:column; }
    .ovp-body { padding:18px 16px 24px; }
}

/* Select2 Premium Overrides */
.select2-container {
    width: 100% !important;
}
.select2-container--open {
    z-index: 9999999 !important;
}
.ovp-filter-bar .select2-container--default .select2-selection--single {
    height: 40px !important;
    border: 1px solid #dbe1e8 !important;
    border-radius: 12px !important;
    background: #fff !important;
    display: flex !important;
    align-items: center !important;
}
.ovp-filter-bar .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
    padding-left: 12px !important;
    padding-right: 32px !important;
    font-size: 13px !important;
    color: #111827 !important;
    font-weight: 500 !important;
}
.ovp-filter-bar .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px !important;
    right: 10px !important;
}
.ovp-filter-bar .select2-container--default.select2-container--focus .select2-selection--single,
.ovp-filter-bar .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 4px rgba(59,130,246,.12) !important;
    outline: none !important;
}
.select2-dropdown {
    border: 1px solid #dbe1e8 !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    box-shadow: 0 12px 34px rgba(15,23,42,.08) !important;
    background: #fff !important;
}
.select2-results__option {
    font-size: 13px !important;
    padding: 8px 12px !important;
    color: #111827 !important;
    background-color: #fff !important;
}
.select2-container--default .select2-results__option--selected {
    background-color: #f3f4f6 !important;
    color: #111827 !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #3b82f6 !important;
    color: #fff !important;
}
.select2-search--dropdown {
    padding: 8px !important;
    background-color: #fff !important;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #dbe1e8 !important;
    border-radius: 8px !important;
    padding: 6px 10px !important;
    font-size: 13px !important;
    outline: none !important;
    color: #111827 !important;
    background: #fff !important;
}
.ovp-select-wrap:has(.select2-container)::after {
    display: none !important;
}

/* Accordion Filter Bar */
.ovp-filter-accordion {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 22px;
    box-shadow: 0 12px 34px rgba(15,23,42,.04);
    margin-bottom: 16px;
    overflow: hidden;
    transition: all .2s ease;
}
.ovp-accordion-header {
    width: 100%;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border: none;
    cursor: pointer;
    outline: none;
    font-weight: 800;
    color: #111827;
    font-size: 14px;
    text-align: left;
}
.ovp-accordion-header:hover {
    background: #fafafa;
}
.ovp-accordion-title {
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: .05em;
    font-size: 13px;
    color: #374151;
}
.ovp-accordion-arrow {
    font-size: 14px;
    color: #6b7280;
    transition: transform .2s ease;
}
.ovp-filter-accordion.is-active .ovp-accordion-arrow {
    transform: rotate(180deg);
}
.ovp-accordion-body {
    padding: 0 24px 24px;
    border-top: 1px solid #f3f4f6;
}
.ovp-filter-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 11px;
    font-weight: 800;
    margin-left: 8px;
    border: 1px solid #bfdbfe;
    text-transform: uppercase;
    letter-spacing: .05em;
}
</style>
@endpush

@section('content')
<div class="ovp-page">
    <div class="ovp-topbar">
        <div>
            <div class="ovp-title">OVP Module</div>
            <div class="ovp-breadcrumb">CRM Dashboard > OVP Module</div>
        </div>
        <div class="ovp-top-chip">
            {{ $isExecutiveScopedView ? 'My OVP Queue' : ($isTlScopedView ? 'OVP TL Allocation Queue' : 'Production Status Tracker') }}
        </div>
    </div>

    <div class="ovp-body">
        @if(session('success'))
            <div class="ovp-flash success">{{ session('success') }}</div>
        @endif

        @php
            $hasActiveFilters = request()->filled('start_date') || 
                                request()->filled('end_date') || 
                                request()->filled('product_id') || 
                                request()->filled('status') || 
                                request()->filled('user_id') || 
                                request()->filled('company_id') || 
                                request()->filled('department_id');
        @endphp

        {{-- Filter Accordion --}}
        <div class="ovp-filter-accordion {{ $hasActiveFilters ? 'is-active' : '' }}">
            <button type="button" class="ovp-accordion-header">
                <span class="ovp-accordion-title">
                    <i class="bi bi-funnel-fill" style="color: #3b82f6;"></i> FILTER OPTIONS
                    @if($hasActiveFilters)
                        <span class="ovp-filter-badge">Active</span>
                    @endif
                </span>
                <span class="ovp-accordion-arrow">
                    <i class="bi bi-chevron-down"></i>
                </span>
            </button>
            <div class="ovp-accordion-body" style="{{ $hasActiveFilters ? 'display: block;' : 'display: none;' }}">
                <div class="ovp-filter-bar" style="padding-top: 20px;">
                    <form method="GET" action="{{ route('ovp-module.index') }}" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; align-items:flex-end;">
                        <input type="hidden" name="bucket" value="{{ $selectedBucket }}">

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Start Date</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="ovp-input" style="padding:9px 12px;">
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">End Date</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="ovp-input" style="padding:9px 12px;">
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Product</label>
                            <div class="ovp-select-wrap">
                                <select name="product_id" class="ovp-input ovp-select select2" style="padding:9px 12px; padding-right:32px;">
                                    <option value="">All Products</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->product_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Status</label>
                            <div class="ovp-select-wrap">
                                <select name="status" class="ovp-input ovp-select select2" style="padding:9px 12px; padding-right:32px;">
                                    <option value="">All Statuses</option>
                                    <option value="ovp_pending" {{ request('status') == 'ovp_pending' ? 'selected' : '' }}>OVP Pending</option>
                                    <option value="initiated" {{ request('status') == 'initiated' ? 'selected' : '' }}>Initiated</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">OVP Executive</label>
                            <div class="ovp-select-wrap">
                                <select name="user_id" class="ovp-input ovp-select select2" style="padding:9px 12px; padding-right:32px;">
                                    <option value="">All Executives</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Company</label>
                            <div class="ovp-select-wrap">
                                <select name="company_id" class="ovp-input ovp-select select2" style="padding:9px 12px; padding-right:32px;">
                                    <option value="">All Companies</option>
                                    @foreach($companies as $c)
                                        <option value="{{ $c->id }}" {{ request('company_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Department</label>
                            <div class="ovp-select-wrap">
                                <select name="department_id" class="ovp-input ovp-select select2" style="padding:9px 12px; padding-right:32px;">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; gap:10px; grid-column: 1 / -1; justify-content: flex-end; margin-top:4px;">
                            <button type="submit" class="ovp-btn approve" style="padding:10px 20px; font-size:13px; font-weight:800;">Apply Filters</button>
                            <a href="{{ route('ovp-module.index', ['bucket' => $selectedBucket]) }}" class="ovp-btn" style="padding:10px 20px; font-size:13px; font-weight:800; text-decoration:none; line-height:18px;">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <section class="ovp-grid">
            @foreach($cards as $key => $card)
                <a
                    href="{{ route('ovp-module.index', ['bucket' => $key]) }}"
                    class="ovp-card {{ $key }} {{ $selectedBucket === $key ? 'is-active' : '' }}"
                >
                    <div class="ovp-card-head">
                        <div>
                            <div class="ovp-card-title">{{ $card['title'] }}</div>
                            <div class="ovp-card-sub">{{ $card['status_label'] }}</div>
                        </div>
                        <span class="ovp-badge {{ $key }}">{{ $card['status_label'] }}</span>
                    </div>
                    <div class="ovp-card-count">{{ number_format($card['count']) }}</div>
                </a>
            @endforeach
        </section>

        <section class="ovp-table-card">
            @php
                $dateHeader = match ($selectedBucket) {
                    'approved' => 'Approved On',
                    'reject' => 'Rejected On',
                    default => 'Received On',
                };
            @endphp
            <div class="ovp-table-head">
                <div>
                    <div class="ovp-table-title">{{ $selectedCard['title'] }} List</div>
                    <div class="ovp-table-sub">Showing products currently in {{ strtolower($selectedCard['status_label']) }}.</div>
                </div>
                <span class="ovp-badge {{ $selectedBucket }}">{{ number_format($selectedCard['count']) }} Items</span>
            </div>

            @if($selectedCard['items']->isNotEmpty())
                <div class="ovp-table-wrap">
                    <table class="ovp-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Department</th>
                                <th>Lead / Company</th>
                                <th>Status</th>
                                <th>Assigned Executive</th>
                                <th>Allocation</th>
                                <th>{{ $dateHeader }}</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedCard['items'] as $item)
                                @php
                                    $statusBucket = match (strtolower((string) $item->status)) {
                                        'approval', 'approved' => 'approved',
                                        'rejected', 'reject' => 'reject',
                                        'pending' => 'pending',
                                        default => optional($item->created_at)->lt(now()->subDays(3)) ? 'overdue' : 'new',
                                    };
                                    $isOvpPending = in_array($selectedBucket, ['new', 'overdue', 'pending'], true);

                                    $canAllocateItem = $isOvpPending && ($isTlScopedView || auth()->user()?->hasAdminLikeRole());
                                    $canReviewItem = $isOvpPending
                                        && (bool) $item->ovpAllocatedTo
                                        && (($isExecutiveScopedView && (int) $item->ovp_allocated_to === (int) auth()->id()) || $canAllocateItem);
                                    $statusDate = in_array($statusBucket, ['approved', 'reject'], true)
                                        ? ($item->reviewed_at ?? $item->created_at)
                                        : $item->created_at;
                                    $clientName = $item->client_name ?: ($item->lead?->contact_name ?: '');
                                    $companyName = $item->company_name ?: ($item->lead?->company_name ?: '');
                                    $salesPersonName = $item->lead?->assignedTo?->name ?: ($item->lead?->createdBy?->name ?: 'Not available');
                                    $branchName = $item->lead?->branch?->name ?: 'Not available';
                                    $totalAmount = (float) ($item->leadProduct?->total_price ?? 0);
                                    $receivedAmount = (float) ($item->leadProduct?->amount_paid ?? 0);
                                    if ($receivedAmount <= 0 && $item->lead_id) {
                                        $leadPaymentsSum = (float) \App\Models\LeadProductPayment::where('lead_id', $item->lead_id)->sum('amount');
                                        if ($leadPaymentsSum > 0) {
                                            $receivedAmount = $leadPaymentsSum;
                                        }
                                    }
                                    if ($totalAmount <= 0 && $item->lead_id) {
                                        $leadTotalSum = (float) \App\Models\LeadProduct::where('lead_id', $item->lead_id)->sum('total_price');
                                        if ($leadTotalSum > 0) {
                                            $totalAmount = $leadTotalSum;
                                        }
                                    }
                                    $pendingAmount = max(0, $totalAmount - $receivedAmount);
                                    $ovpSchema = $item->product?->ovpFormFields
                                        ? $item->product->ovpFormFields
                                            ->where('is_active', true)
                                            ->where('use_in_ovp', true)
                                            ->values()
                                            ->map(fn ($field) => [
                                                'id' => $field->id,
                                                'label' => $field->label,
                                                'field_name' => $field->field_name,
                                                'field_type' => $field->field_type,
                                                'placeholder' => $field->placeholder,
                                                'help_text' => $field->help_text,
                                                'default_value' => $field->default_value,
                                                'is_required' => $field->is_required,
                                                'use_in_ovp' => $field->use_in_ovp,
                                                'options' => $field->options ?? [],
                                                'validation_rules' => $field->validation_rules ?? [],
                                            ])->all()
                                        : [];
                                    $allocationStatus = strtolower((string) ($item->ovp_allocation_status ?: ($item->ovp_allocated_to ? 'allocated' : 'allocation_pending')));
                                @endphp
                                 <tr class="ovp-row" data-href="{{ $item->lead_id ? route('leads.show', $item->lead_id) : '#' }}">
                                     <td>
                                         <div class="ovp-product">{{ $item->product_name }}</div>
                                     </td>
                                    <td>{{ $item->department?->name ?: 'No department' }}</td>
                                    <td>{{ $item->lead?->company_name ?: ($item->lead?->contact_name ?: 'No lead') }}</td>
                                    <td><span class="ovp-status-pill {{ $statusBucket }}">{{ ucfirst(str_replace('_', ' ', $statusBucket)) }}</span></td>
                                    @php
                                        $assignedExecName = $item->ovpAllocatedTo?->name
                                            ?: ($item->ovp_allocated_to ? (\App\Models\User::withTrashed()->find($item->ovp_allocated_to)?->name ?: 'Not allocated') : 'Not allocated');
                                    @endphp
                                    <td>
                                        @if($assignedExecName !== 'Not allocated')
                                            <div class="ovp-assign-meta">
                                                <div class="ovp-assign-name">{{ $assignedExecName }}</div>
                                                <div class="ovp-assign-sub">
                                                    Allocated by {{ $item->ovpAllocatedBy?->name ?: 'Not available' }}<br>
                                                    {{ optional($item->ovp_allocated_at)->format('d M Y h:i A') ?: 'Pending' }}
                                                </div>
                                            </div>
                                        @else
                                            <span class="ovp-action-muted">Not allocated</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="ovp-status-pill {{ in_array($allocationStatus, ['allocated', 'submitted'], true) ? $allocationStatus : 'allocation_pending' }}">
                                            {{ ucfirst(str_replace('_', ' ', $allocationStatus)) }}
                                        </span>
                                    </td>
                                    <td>{{ optional($statusDate)->format('d M Y h:i A') }}</td>
                                    <td>
                                        @if($canReviewItem || $canAllocateItem)
                                            <div class="ovp-action-stack">
                                                <div class="ovp-action-row">
                                                    @if($canReviewItem)
                                                        <button
                                                            type="button"
                                                            class="ovp-action-btn"
                                                            data-ovp-open
                                                            data-action="{{ route('ovp-module.review', $item) }}"
                                                            data-product="{{ $item->product_name }}"
                                                            data-department="{{ $item->department?->name ?: '' }}"
                                                            data-client="{{ $clientName }}"
                                                            data-company="{{ $companyName }}"
                                                            data-date="{{ optional($item->welcome_call_date)->format('Y-m-d') }}"
                                                            data-time="{{ $item->welcome_call_time ? substr((string) $item->welcome_call_time, 0, 5) : '' }}"
                                                            data-total-amount="{{ number_format($totalAmount, 2, '.', '') }}"
                                                            data-received-amount="{{ number_format($receivedAmount, 2, '.', '') }}"
                                                            data-pending-amount="{{ number_format($pendingAmount, 2, '.', '') }}"
                                                            data-converted-date="{{ optional($item->created_at)->format('d M Y h:i A') ?: 'Not available' }}"
                                                            data-sales-person="{{ $salesPersonName }}"
                                                            data-branch-name="{{ $branchName }}"
                                                            data-assigned-executive="{{ $assignedExecName }}"
                                                            data-schema='@json($ovpSchema)'
                                                            data-custom-form='@json($item->custom_form_data ?? [])'
                                                        >
                                                            Review
                                                        </button>
                                                    @endif

                                                    @if($canAllocateItem)
                                                        <button
                                                            type="button"
                                                            class="ovp-action-btn allocate"
                                                            data-ovp-allocate-open
                                                            data-action="{{ route('ovp-module.allocate', ['productionInitiation' => $item, 'bucket' => $selectedBucket]) }}"
                                                            data-product="{{ $item->product_name }}"
                                                            data-current-executive-id="{{ (int) $item->ovp_allocated_to }}"
                                                        >
                                                            {{ $item->ovpAllocatedTo ? 'Reallocate' : 'Allocation' }}
                                                        </button>
                                                    @endif
                                                </div>

                                                @if($canAllocateItem && $executiveUsers->isEmpty())
                                                    <div class="ovp-inline-note">Mapped OVP executives Not Available.</div>
                                                @endif
                                            </div>
                                        @elseif($item->reviewedBy)
                                            <div class="ovp-inline-note">
                                                Submitted by {{ $item->reviewedBy->name }}<br>
                                                {{ optional($item->reviewed_at)->format('d M Y h:i A') ?: 'Completed' }}
                                            </div>
                                        @else
                                            <span class="ovp-action-muted">{{ $item->ovpAllocatedTo ? 'Waiting for executive submission' : 'No action' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ovp-empty">No products are available in this status right now.</div>
            @endif
        </section>
    </div>

    <div class="ovp-modal" id="ovp-review-modal">
        <div class="ovp-modal-card">
            <div class="ovp-modal-head">
                <div>
                    <div class="ovp-modal-title">OVP Review Form</div>
                    <div class="ovp-modal-sub" id="ovp-modal-context">Mapped product customization form details fill pannunga.</div>
                </div>
                <button type="button" class="ovp-modal-close" data-ovp-close>&times;</button>
            </div>

            <form method="POST" id="ovp-review-form" class="ovp-modal-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="decision" id="ovp-decision-input">

                <div class="ovp-modal-body">
                    <div class="ovp-review-layout">
                        <div class="ovp-form-shell">
                            <div class="ovp-panel-title">Lead Conversion Summary</div>
                            <div id="ovp-static-form-wrap" class="ovp-static-grid"></div>
                            {{--  <div class="ovp-panel-title">Mapped Custom Fields</div>  --}}

                            <div id="ovp-custom-form-wrap" class="ovp-custom-list"></div>

                            <div id="ovp-remarks-field-wrap" style="margin-top: 20px; background: #fff5f5; border: 1px solid #fca5a5; border-radius: 12px; padding: 16px;">
                                <label class="ovp-label" style="color: #991b1b; font-weight: 800; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    Rejection Reason / Remarks <span style="color: #dc2626;">* (Mandatory for Rejection)</span>
                                </label>
                                <textarea name="remarks" id="ovp-remarks-input" class="ovp-input" rows="3" placeholder="Enter mandatory reason if rejecting this OVP review..." style="margin-top: 6px; border-color: #fca5a5; width: 100%; box-sizing: border-box; font-family: inherit; font-size: 13px; border-radius: 8px; padding: 10px;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ovp-modal-actions">
                    <button type="button" class="ovp-btn" data-ovp-close>Cancel</button>
                    <button type="button" class="ovp-btn reject" id="ovp-review-reject-btn" data-decision="rejected">Reject</button>
                    <button type="button" class="ovp-btn approve" id="ovp-review-approve-btn" data-decision="approval">Approve</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ovp-modal" id="ovp-allocation-modal">
        <div class="ovp-modal-card" style="width:min(100%, 560px);">
            <div class="ovp-modal-head">
                <div>
                    <div class="ovp-modal-title">OVP Allocation</div>
                    <div class="ovp-modal-sub" id="ovp-allocation-context">Select the executive for this OVP item.</div>
                </div>
                <button type="button" class="ovp-modal-close" data-ovp-allocation-close>&times;</button>
            </div>

            <form method="POST" id="ovp-allocation-form" class="ovp-modal-form">
                @csrf
                <div class="ovp-modal-body">
                    <div class="ovp-form-shell">
                        <div class="ovp-panel-title">Allocate To OVP Executive</div>
                        <div class="ovp-field">
                            <label class="ovp-label" for="ovp-allocation-user">Executive</label>
                            <select name="executive_user_id" id="ovp-allocation-user" class="ovp-input ovp-select" required>
                                <option value="">Select OVP executive</option>
                                @foreach($executiveUsers as $executiveUser)
                                    <option value="{{ $executiveUser->id }}">
                                        {{ $executiveUser->name }} | {{ $executiveUser->department_label }} | {{ $executiveUser->role_label }}
                                    </option>
                                @endforeach
                            </select>

                        </div>
                    </div>
                </div>

                <div class="ovp-modal-actions">
                    <button type="button" class="ovp-btn" data-ovp-allocation-close>Cancel</button>
                    <button type="submit" class="ovp-btn allocate">Save Allocation</button>
                </div>
            </form>
        </div>
    </div>

    {{-- OVP APPROVE CONFIRMATION MODAL --}}
    <div id="ovpApproveConfirmModal" class="support-process-overlay" style="display: none;">
        <div class="support-process-card" style="width: 480px; text-align: left; padding: 28px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                <div style="font-size:18px; font-weight:800; color:#166534; display:flex; align-items:center; gap:8px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Confirm OVP Approval
                </div>
                <button type="button" style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;" onclick="closeOvpApproveConfirmModal()">&times;</button>
            </div>

            <p style="font-size: 15px; font-weight: 700; color: #111827; margin: 0 0 8px;">Are you sure you want to APPROVE this OVP review?</p>
            <p style="font-size: 13px; color: #6b7280; margin: 0 0 16px; line-height: 1.5;">
                An approval email notification will be sent to:<br>
                <strong style="color:#0f172a;">tamilarasan@saitechnosolutions.net</strong> with all filled OVP details.
            </p>

            <div id="ovp-confirm-details-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:20px; font-size:13px; line-height:1.6;">
                <!-- Filled dynamically by JS -->
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="ovp-btn" onclick="closeOvpApproveConfirmModal()">Cancel</button>
                <button type="button" class="ovp-btn approve" id="ovp-confirm-submit-btn" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color:#fff; font-weight:700; border:none;">
                    Yes, Approve OVP
                </button>
            </div>
        </div>
    </div>

    {{-- OVP REJECT CONFIRMATION MODAL --}}
    <div id="ovpRejectConfirmModal" class="support-process-overlay" style="display: none;">
        <div class="support-process-card" style="width: 480px; text-align: left; padding: 28px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #fee2e2; padding-bottom:12px;">
                <div style="font-size:18px; font-weight:800; color:#dc2626; display:flex; align-items:center; gap:8px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    Confirm OVP Rejection
                </div>
                <button type="button" style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;" onclick="closeOvpRejectConfirmModal()">&times;</button>
            </div>

            <p style="font-size: 15px; font-weight: 700; color: #111827; margin: 0 0 8px;">Are you sure you want to REJECT this OVP review?</p>
            <p style="font-size: 13px; color: #6b7280; margin: 0 0 16px; line-height: 1.5;">
                A rejection email notification will be sent to the <strong>Lead's Allocated / Assigned Person</strong> with the specified rejection reason.
            </p>

            <div id="ovp-reject-confirm-details-box" style="background:#fef2f2; border:1px solid #fca5a5; border-radius:12px; padding:14px 16px; margin-bottom:20px; font-size:13px; line-height:1.6; color:#7f1d1d;">
                <!-- Filled dynamically by JS -->
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="ovp-btn" onclick="closeOvpRejectConfirmModal()">Cancel</button>
                <button type="button" class="ovp-btn reject" id="ovp-confirm-reject-submit-btn" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color:#fff; font-weight:700; border:none;">
                    Yes, Reject OVP
                </button>
            </div>
        </div>
    </div>

    {{-- OVP APPROVAL PROCESS OVERLAY LOADER --}}
    <div id="ovpApproveProcessOverlay" class="support-process-overlay" style="display: none;">
        <div class="support-process-card">
            <div class="support-process-icon-wrap">
                <div class="support-process-spinner"></div>
                <i class="bi bi-envelope-paper-fill support-process-icon"></i>
            </div>
            <h4 id="ovpProcessOverlayTitle" class="support-process-title">Approving OVP & Sending Email...</h4>
            <p id="ovpProcessOverlaySubtitle" class="support-process-subtitle">Please wait while OVP approval email notification is being sent to tamilarasan@saitechnosolutions.net...</p>

            <div class="support-progress-wrapper">
                <div class="support-progress-bar">
                    <div id="ovpProcessProgressFill" class="support-progress-fill"></div>
                </div>
                <div class="support-progress-status">
                    <span id="ovpProcessProgressText">Preparing OVP approval notification...</span>
                    <span id="ovpProcessProgressPercent">0%</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Accordion Toggle
    if (window.jQuery) {
        window.jQuery('.ovp-accordion-header').on('click', function() {
            const $accordion = window.jQuery(this).closest('.ovp-filter-accordion');
            const $body = $accordion.find('.ovp-accordion-body');
            $accordion.toggleClass('is-active');
            $body.slideToggle(200);
        });
    }

    const modal = document.getElementById('ovp-review-modal');
    const form = document.getElementById('ovp-review-form');
    const allocationModal = document.getElementById('ovp-allocation-modal');
    const allocationForm = document.getElementById('ovp-allocation-form');
    const allocationContext = document.getElementById('ovp-allocation-context');
    const allocationUser = document.getElementById('ovp-allocation-user');

    if (!modal || !form) {
        return;
    }

    const decisionInput = document.getElementById('ovp-decision-input');
    const modalContext = document.getElementById('ovp-modal-context');
    const staticFormWrap = document.getElementById('ovp-static-form-wrap');
    const customFormWrap = document.getElementById('ovp-custom-form-wrap');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatCurrency(value) {
        const amount = Number.parseFloat(value || 0);

        return 'Rs.' + amount.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function renderStaticFields(button) {
        if (!staticFormWrap) {
            return;
        }

        const fields = [
            { label: 'Product Name', value: button.dataset.product || 'Not available' },
            { label: 'Assigned Executive', value: button.dataset.assignedExecutive || 'Not allocated' },
            { label: 'Total Amount', value: formatCurrency(button.dataset.totalAmount) },
            { label: 'Received Amount', value: formatCurrency(button.dataset.receivedAmount) },
            { label: 'Pending Amount', value: formatCurrency(button.dataset.pendingAmount) },
            { label: 'Converted Date', value: button.dataset.convertedDate || 'Not available' },
            { label: 'Sales Person Name', value: button.dataset.salesPerson || 'Not available' },
            { label: 'Branch Name', value: button.dataset.branchName || 'Not available' },
        ];

        staticFormWrap.innerHTML = fields.map(function (field) {
            return '<div class="ovp-static-card">' +
                '<div class="ovp-static-label">' + escapeHtml(field.label) + '</div>' +
                '<div class="ovp-static-value">' + escapeHtml(field.value) + '</div>' +
            '</div>';
        }).join('');
    }

    function renderCustomFormData(schema, entries) {
        if (!customFormWrap) {
            return;
        }

        if (!Array.isArray(schema) || schema.length === 0) {
            customFormWrap.innerHTML = '<div class="ovp-custom-empty">Form Not Mapping</div>';
            return;
        }

        const existing = {};
        (Array.isArray(entries) ? entries : []).forEach(function (entry) {
            if (entry && entry.field_name) {
                existing[entry.field_name] = entry.value;
            }
        });

        customFormWrap.innerHTML = schema.map(function (field) {
            return renderField(field, existing[field.field_name]);
        }).join('');
    }

    function renderField(field, currentValue) {
        const value = currentValue !== undefined && currentValue !== null ? currentValue : (field.default_value || '');
        const required = field.is_required ? ' <span style="color:#dc2626">*</span>' : '';
        let inputHtml = '';

        if (field.field_type === 'textarea') {
            inputHtml = '<textarea class="ovp-input" name="custom_fields[' + escapeHtml(field.field_name) + ']" placeholder="' + escapeHtml(field.placeholder || '') + '">' + escapeHtml(value) + '</textarea>';
        } else if (field.field_type === 'select') {
            inputHtml = '<div class="ovp-select-wrap"><select class="ovp-input ovp-select" name="custom_fields[' + escapeHtml(field.field_name) + ']">' +
                '<option value="">Select</option>' +
                (field.options || []).map(function (option) {
                    const selected = String(value) === String(option.value) ? ' selected' : '';
                    return '<option value="' + escapeHtml(option.value) + '"' + selected + '>' + escapeHtml(option.label) + '</option>';
                }).join('') +
            '</select></div>';
        } else if (field.field_type === 'radio') {
            inputHtml = '<div class="ovp-choice-group">' +
                (field.options || []).map(function (option) {
                    const checked = String(value) === String(option.value) ? ' checked' : '';
                    return '<label class="ovp-choice"><input type="radio" name="custom_fields[' + escapeHtml(field.field_name) + ']" value="' + escapeHtml(option.value) + '"' + checked + '> <span>' + escapeHtml(option.label) + '</span></label>';
                }).join('') +
            '</div>';
        } else if (field.field_type === 'checkbox') {
            const values = Array.isArray(value) ? value.map(String) : (value ? [String(value)] : []);
            inputHtml = '<div class="ovp-choice-group">' +
                (field.options || []).map(function (option) {
                    const checked = values.includes(String(option.value)) ? ' checked' : '';
                    return '<label class="ovp-choice"><input type="checkbox" name="custom_fields[' + escapeHtml(field.field_name) + '][]" value="' + escapeHtml(option.value) + '"' + checked + '> <span>' + escapeHtml(option.label) + '</span></label>';
                }).join('') +
            '</div>';
        } else if (field.field_type === 'file') {
            const currentFile = value && typeof value === 'object' && value.name
                ? '<div class="ovp-file-note">Current file: <a class="ovp-custom-link" href="' + escapeHtml(value.url || '#') + '" target="_blank" rel="noopener">' + escapeHtml(value.name) + '</a></div>'
                : '';
            inputHtml = '<input class="ovp-input" type="file" name="custom_files[' + escapeHtml(field.field_name) + ']">' + currentFile;
        } else {
            const inputType = field.field_type === 'number' ? 'number' : (field.field_type === 'date' ? 'date' : 'text');
            const minAttr = field.validation_rules && field.validation_rules.min !== undefined ? ' min="' + escapeHtml(field.validation_rules.min) + '"' : '';
            const maxAttr = field.validation_rules && field.validation_rules.max !== undefined ? ' max="' + escapeHtml(field.validation_rules.max) + '"' : '';
            const stepAttr = field.field_type === 'number' ? ' step="0.01"' : '';
            inputHtml = '<input class="ovp-input" type="' + inputType + '" name="custom_fields[' + escapeHtml(field.field_name) + ']" value="' + escapeHtml(value) + '" placeholder="' + escapeHtml(field.placeholder || '') + '"' + minAttr + maxAttr + stepAttr + '>';
        }

        return '<div class="ovp-custom-item">' +
            '<div class="ovp-custom-item-label">' + escapeHtml(field.label || field.field_name) + required + '</div>' +
            '<div class="ovp-custom-item-value">' + inputHtml + (field.help_text ? '<div class="ovp-help" style="margin-top:8px;">' + escapeHtml(field.help_text) + '</div>' : '') + '</div>' +
        '</div>';
    }

    function openModal(button) {
        form.action = button.dataset.action || '';
        if (modalContext) {
            modalContext.textContent = (button.dataset.product || 'Selected product') + ' - ' + (button.dataset.department || 'No department');
        }
        renderStaticFields(button);
        renderCustomFormData(
            JSON.parse(button.dataset.schema || '[]'),
            JSON.parse(button.dataset.customForm || '[]')
        );
        decisionInput.value = '';
        isConfirmedApprove = false;
        isConfirmedReject = false;
        modal.classList.add('is-open');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        form.reset();
        decisionInput.value = '';
        if (modalContext) {
            modalContext.textContent = 'Mapped product customization form details fill pannunga.';
        }
        if (staticFormWrap) {
            staticFormWrap.innerHTML = '';
        }
        renderCustomFormData([], []);
    }

    function openAllocationModal(button) {
        if (!allocationModal || !allocationForm || !allocationUser) {
            return;
        }

        allocationForm.action = button.dataset.action || '';
        allocationUser.value = button.dataset.currentExecutiveId || '';

        if (allocationContext) {
            allocationContext.textContent = (button.dataset.product || 'Selected product') + ' - select the executive for allocation.';
        }

        allocationModal.classList.add('is-open');
    }

    function closeAllocationModal() {
        if (!allocationModal || !allocationForm) {
            return;
        }

        allocationModal.classList.remove('is-open');
        allocationForm.reset();
        if (allocationContext) {
            allocationContext.textContent = 'Select the executive for this OVP item.';
        }
    }

    document.querySelectorAll('[data-ovp-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button);
        });
    });

    document.querySelectorAll('[data-ovp-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.querySelectorAll('[data-ovp-allocate-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            openAllocationModal(button);
        });
    });

    document.querySelectorAll('[data-ovp-allocation-close]').forEach(function (button) {
        button.addEventListener('click', closeAllocationModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    if (allocationModal) {
        allocationModal.addEventListener('click', function (event) {
            if (event.target === allocationModal) {
                closeAllocationModal();
            }
        });
    }

    const approveConfirmModal = document.getElementById('ovpApproveConfirmModal');
    const rejectConfirmModal = document.getElementById('ovpRejectConfirmModal');
    const processOverlay = document.getElementById('ovpApproveProcessOverlay');

    window.closeOvpApproveConfirmModal = function () {
        if (approveConfirmModal) approveConfirmModal.style.display = 'none';
    };

    window.closeOvpRejectConfirmModal = function () {
        if (rejectConfirmModal) rejectConfirmModal.style.display = 'none';
    };

    const approveBtn = document.getElementById('ovp-review-approve-btn');
    const rejectBtn = document.getElementById('ovp-review-reject-btn');

    if (approveBtn) {
        approveBtn.addEventListener('click', function () {
            decisionInput.value = 'approval';
            const contextText = modalContext ? modalContext.textContent : '';
            const detailsBox = document.getElementById('ovp-confirm-details-box');
            if (detailsBox) {
                detailsBox.innerHTML = '<div><strong>OVP Context:</strong> ' + escapeHtml(contextText) + '</div>';
            }
            if (approveConfirmModal) {
                approveConfirmModal.style.display = 'flex';
            }
        });
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
            decisionInput.value = 'rejected';
            const remarksInput = document.getElementById('ovp-remarks-input');
            const remarksValue = remarksInput ? remarksInput.value.trim() : '';

            if (!remarksValue) {
                alert('Please enter a mandatory Rejection Reason / Remarks before rejecting.');
                if (remarksInput) remarksInput.focus();
                return;
            }

            const detailsBox = document.getElementById('ovp-reject-confirm-details-box');
            if (detailsBox) {
                detailsBox.innerHTML = '<div><strong>Rejection Reason:</strong> ' + escapeHtml(remarksValue) + '</div>';
            }
            if (rejectConfirmModal) {
                rejectConfirmModal.style.display = 'flex';
            }
        });
    }

    const confirmApproveSubmitBtn = document.getElementById('ovp-confirm-submit-btn');
    if (confirmApproveSubmitBtn) {
        confirmApproveSubmitBtn.onclick = function () {
            closeOvpApproveConfirmModal();
            modal.classList.remove('is-open');

            if (processOverlay) {
                processOverlay.style.display = 'flex';
                const fill = document.getElementById('ovpProcessProgressFill');
                const percentText = document.getElementById('ovpProcessProgressPercent');
                const statusText = document.getElementById('ovpProcessProgressText');

                let progress = 10;
                if (fill) fill.style.width = progress + '%';
                if (percentText) percentText.innerText = progress + '%';
                if (statusText) statusText.innerText = 'Preparing OVP approval email...';

                const interval = setInterval(function () {
                    if (progress < 40) {
                        progress += 8;
                        if (statusText) statusText.innerText = 'Building OVP details & form summary...';
                    } else if (progress < 85) {
                        progress += 6;
                        if (statusText) statusText.innerText = 'Sending email to tamilarasan@saitechnosolutions.net...';
                    } else if (progress < 95) {
                        progress += 1;
                        if (statusText) statusText.innerText = 'Updating OVP item status in database...';
                    }
                    if (progress > 95) progress = 95;
                    if (fill) fill.style.width = progress + '%';
                    if (percentText) percentText.innerText = progress + '%';
                }, 200);
            }

            setTimeout(function () {
                form.submit();
            }, 600);
        };
    }

    const confirmRejectSubmitBtn = document.getElementById('ovp-confirm-reject-submit-btn');
    if (confirmRejectSubmitBtn) {
        confirmRejectSubmitBtn.onclick = function () {
            closeOvpRejectConfirmModal();
            modal.classList.remove('is-open');

            if (processOverlay) {
                processOverlay.style.display = 'flex';
                const fill = document.getElementById('ovpProcessProgressFill');
                const percentText = document.getElementById('ovpProcessProgressPercent');
                const statusText = document.getElementById('ovpProcessProgressText');

                let progress = 10;
                if (fill) fill.style.width = progress + '%';
                if (percentText) percentText.innerText = progress + '%';
                if (statusText) statusText.innerText = 'Preparing OVP rejection notification...';

                const interval = setInterval(function () {
                    if (progress < 40) {
                        progress += 8;
                        if (statusText) statusText.innerText = 'Packaging rejection reason & lead details...';
                    } else if (progress < 85) {
                        progress += 6;
                        if (statusText) statusText.innerText = 'Sending email notification to Lead Assigned Person...';
                    } else if (progress < 95) {
                        progress += 1;
                        if (statusText) statusText.innerText = 'Updating OVP item status in database...';
                    }
                    if (progress > 95) progress = 95;
                    if (fill) fill.style.width = progress + '%';
                    if (percentText) percentText.innerText = progress + '%';
                }, 200);
            }

            setTimeout(function () {
                form.submit();
            }, 600);
        };
    }

    document.querySelectorAll('.ovp-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('button') || event.target.closest('a') || event.target.closest('select') || event.target.closest('form')) {
                return;
            }
            const href = row.dataset.href;
            if (href && href !== '#') {
                window.location.href = href;
            }
        });
    });
});
</script>
@endpush
