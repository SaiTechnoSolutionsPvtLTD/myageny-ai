@extends('layouts.app')

@section('title', 'Project Timesheets')

@push('styles')
<style>
.pts-page { min-height:100%; background:linear-gradient(180deg,#f7fbff 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pts-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #e6edf5; }
.pts-title { font-size:24px; font-weight:900; color:#111827; }
.pts-breadcrumb { font-size:12px; color:#64748b; margin-top:4px; }
.pts-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.pts-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#111827; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; transition:all 0.2s; }
.pts-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.pts-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.pts-btn-outline { border-color:#ea580c; color:#ea580c; background:#fff; }
.pts-btn-outline:hover { background:#fff7ed; }
.pts-body { padding:22px 28px 34px; display:grid; gap:18px; }
.pts-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; overflow:hidden; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.pts-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; padding:18px 20px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-card-title { font-size:16px; font-weight:900; color:#111827; }
.pts-card-sub { margin-top:4px; font-size:12px; color:#64748b; }
.pts-card-body { padding:20px; }
.pts-flash { padding:12px 14px; border-radius:10px; font-size:13px; font-weight:700; }
.pts-flash.success { background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; }
.pts-flash.error { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
.pts-empty { padding:40px 20px; text-align:center; color:#64748b; font-size:13px; }
.pts-table-wrap { overflow-x:auto; }
.pts-table { width:100%; border-collapse:collapse; min-width:860px; }
.pts-table th { padding:14px 16px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; background:#f8fafc; border-bottom:1px solid #edf2f7; }
.pts-table td { padding:16px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#111827; vertical-align:middle; }
.pts-meta { margin-top:4px; font-size:11px; color:#64748b; }
.pts-project { font-weight:900; color:#0f172a; }
.pts-update-text { line-height:1.65; color:#334155; }
.pts-pill { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid #fed7aa; color:#c2410c; background:#fff7ed; }

.pts-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; }
.pts-badge.pending { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.pts-badge.ongoing { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.pts-badge.completed { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.pts-badge.count { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }

.pts-status-select {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    border: 1px solid transparent;
    cursor: pointer;
    outline: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2212%22%20height%3D%2212%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22currentColor%22%20stroke-width%3D%223%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 10px;
    padding-right: 24px;
    transition: all 0.2s;
}
.pts-status-select.completed { color:#15803d; background-color:#f0fdf4; border-color:#bbf7d0; }
.pts-status-select.ongoing { color:#1d4ed8; background-color:#eff6ff; border-color:#bfdbfe; }
.pts-status-select.pending { color:#b45309; background-color:#fff7ed; border-color:#fed7aa; }
.pts-status-select:disabled {
    opacity: 0.55;
    cursor: not-allowed !important;
    background-color: #f1f5f9 !important;
    color: #94a3b8 !important;
    border-color: #e2e8f0 !important;
}

.pts-filter-card { background:#fff; border:1px solid #e6edf5; border-radius:14px; padding:16px; box-shadow:0 10px 28px rgba(15,23,42,.04); }
.pts-filter-form { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; align-items:end; }
.pts-filter-group { display:grid; gap:7px; }
.pts-filter-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.pts-reset-btn { display:inline-flex; align-items:center; justify-content:center; min-height:44px; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:13px; font-weight:800; text-decoration:none; }

.pts-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.48); z-index:1200; display:none; }
.pts-modal-overlay.is-open { display:block; }
.pts-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(1000px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 24px 60px rgba(15,23,42,.22); z-index:1210; display:none; }
.pts-modal.is-open { display:block; }
.pts-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #edf2f7; background:#fbfdff; }
.pts-modal-close { width:40px; height:40px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; color:#334155; font-size:16px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
.pts-modal-close:hover { background:#f1f5f9; }
.pts-modal-body { padding:20px; }

.pts-submodal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:1240; display:none; }
.pts-submodal-overlay.is-open { display:block; }
.pts-submodal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(620px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 28px 70px rgba(15,23,42,.32); z-index:1250; display:none; }
.pts-submodal.is-open { display:block; }

.modal-task-table { width:100%; border-collapse:collapse; min-width:820px; table-layout:auto; }
.modal-task-table th { padding:14px 16px; background:#f8fafc; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; border-bottom:1px solid #e2e8f0; text-align:left !important; vertical-align:middle; }
.modal-task-table th.th-center, .modal-task-table td.td-center { text-align:center !important; }
.modal-task-table th.th-right, .modal-task-table td.td-right { text-align:right !important; }
.modal-task-table td { padding:14px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:13px; text-align:left !important; color:#111827; }

.pts-form-grid { display:grid; grid-template-columns: repeat(12, 1fr); gap:16px; align-items:start; }
.pts-grid-col-12 { grid-column: span 12; }
.pts-grid-col-6 { grid-column: span 6; }
.pts-grid-col-4 { grid-column: span 4; }
.pts-grid-col-3 { grid-column: span 3; }
.pts-label { display:block; margin-bottom:8px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; }
.pts-input, .pts-select, .pts-textarea { width:100%; border:1px solid #dbe2ea; border-radius:10px; background:#fff; font-size:14px; color:#111827; }
.pts-input, .pts-select { min-height:44px; padding:10px 12px; }
.pts-textarea { min-height:170px; padding:12px; resize:vertical; line-height:1.55; }
.pts-input:focus, .pts-select:focus, .pts-textarea:focus { outline:none; border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.14); }
.pts-input[readonly] { background:#f8fafc; color:#475569; cursor:not-allowed; }
.pts-help { margin-top:7px; font-size:12px; color:#64748b; line-height:1.55; }
.pts-error { margin-top:7px; font-size:12px; color:#b91c1c; font-weight:700; }
.select2-container--default .select2-selection--single.pts-select2-selection { height:44px; border:1px solid #dbe2ea; border-radius:10px; background:#fff; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__rendered { line-height:42px; padding-left:12px; padding-right:34px; font-size:14px; color:#111827; }
.select2-container--default .select2-selection--single.pts-select2-selection .select2-selection__arrow { height:42px; right:8px; }
.select2-container--default.select2-container--focus .select2-selection--single.pts-select2-selection,
.select2-container--default.select2-container--open .select2-selection--single.pts-select2-selection { border-color:#ea580c; box-shadow:0 0 0 4px rgba(234,88,12,.14); }
.select2-dropdown { border:1px solid #dbe2ea; border-radius:10px; overflow:hidden; box-shadow:0 16px 36px rgba(15,23,42,.12); }
.select2-search--dropdown { padding:10px; }
.select2-search--dropdown .select2-search__field { border:1px solid #dbe2ea; border-radius:8px; padding:8px 10px; font-size:13px; }
.select2-results__option { font-size:13px; padding:10px 12px; }
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background:#ea580c; color:#fff; }
.select2-container--open { z-index:1220; }
@media (max-width: 900px) {
    .pts-form-grid > div { grid-column: span 12 !important; }
    .pts-filter-form { grid-template-columns:1fr 1fr; }
}
@media (max-width: 768px) {
    .pts-topbar { padding:18px 16px; flex-direction:column; }
    .pts-body { padding:18px 16px 24px; }
    .pts-card-head { flex-direction:column; }
    .pts-modal-body, .pts-modal-head { padding:16px; }
    .pts-filter-form { grid-template-columns:1fr; }
}

/* Department Filter Cards (Company Admin) */
.pts-dept-section { display:grid; gap:12px; }
.pts-dept-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.pts-dept-section-title { font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#475569; display:flex; align-items:center; gap:8px; }
.pts-dept-clear-btn { display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:#64748b; text-decoration:none; padding:5px 12px; border-radius:999px; background:#f1f5f9; border:1px solid #cbd5e1; transition:all 0.2s; }
.pts-dept-clear-btn:hover { background:#e2e8f0; color:#0f172a; }
.pts-dept-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
.pts-dept-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:16px 18px; display:flex; align-items:center; justify-content:space-between; gap:14px; cursor:pointer; text-decoration:none; color:inherit; transition:all 0.22s ease-in-out; box-shadow:0 4px 12px rgba(15,23,42,.03); position:relative; overflow:hidden; }
.pts-dept-card:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,.07); border-color:#cbd5e1; }
.pts-dept-card-left { display:flex; align-items:center; gap:14px; min-width:0; }
.pts-dept-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all 0.2s ease; }
.pts-dept-info { min-width:0; }
.pts-dept-name { font-size:15px; font-weight:800; color:#0f172a; line-height:1.2; }
.pts-dept-meta { font-size:11px; font-weight:600; color:#64748b; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pts-dept-count-badge { display:inline-flex; align-items:center; justify-content:center; min-width:32px; height:32px; padding:0 10px; border-radius:999px; font-size:13px; font-weight:800; flex-shrink:0; transition:all 0.2s; }

/* Development Theme */
.pts-dept-card.dept-dev .pts-dept-icon { background:#eff6ff; color:#2563eb; }
.pts-dept-card.dept-dev .pts-dept-count-badge { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.pts-dept-card.dept-dev:hover { border-color:#93c5fd; }
.pts-dept-card.dept-dev.active { background:linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%); border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15), 0 8px 24px rgba(37,99,235,.12); }
.pts-dept-card.dept-dev.active .pts-dept-icon { background:#2563eb; color:#ffffff; }
.pts-dept-card.dept-dev.active .pts-dept-count-badge { background:#2563eb; color:#ffffff; border-color:#2563eb; }

/* Designing Theme */
.pts-dept-card.dept-design .pts-dept-icon { background:#f5f3ff; color:#7c3aed; }
.pts-dept-card.dept-design .pts-dept-count-badge { background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; }
.pts-dept-card.dept-design:hover { border-color:#c4b5fd; }
.pts-dept-card.dept-design.active { background:linear-gradient(135deg, #ffffff 0%, #f7f4ff 100%); border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.15), 0 8px 24px rgba(124,58,237,.12); }
.pts-dept-card.dept-design.active .pts-dept-icon { background:#7c3aed; color:#ffffff; }
.pts-dept-card.dept-design.active .pts-dept-count-badge { background:#7c3aed; color:#ffffff; border-color:#7c3aed; }

/* Digital Marketing Theme */
.pts-dept-card.dept-dm .pts-dept-icon { background:#fff7ed; color:#ea580c; }
.pts-dept-card.dept-dm .pts-dept-count-badge { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.pts-dept-card.dept-dm:hover { border-color:#fdba74; }
.pts-dept-card.dept-dm.active { background:linear-gradient(135deg, #ffffff 0%, #fff8f0 100%); border-color:#ea580c; box-shadow:0 0 0 3px rgba(234,88,12,.15), 0 8px 24px rgba(234,88,12,.12); }
.pts-dept-card.dept-dm.active .pts-dept-icon { background:#ea580c; color:#ffffff; }
.pts-dept-card.dept-dm.active .pts-dept-count-badge { background:#ea580c; color:#ffffff; border-color:#ea580c; }

@media (max-width: 900px) {
    .pts-dept-grid { grid-template-columns:1fr; }
}

/* File dropzone & file preview items */
.task-file-dropzone:hover {
    border-color: #ea580c !important;
    background: #fffaf5 !important;
}
.task-file-dropzone.dragover {
    border-color: #ea580c !important;
    background: #fff7ed !important;
    box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.12) !important;
}
.task-file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12.5px;
    color: #1e293b;
    transition: all 0.15s ease;
}
.task-file-item:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

/* Modern Tabs Navigation */
.pts-tabs-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.pts-tabs-cluster {
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 4px;
    gap: 4px;
    box-shadow: 0 4px 14px rgba(15,23,42,0.03);
}
.pts-nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 9px;
    border: none;
    background: transparent;
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    text-decoration: none;
}
.pts-nav-tab:hover {
    background: #fff7ed;
    color: #c2410c;
}
.pts-nav-tab.is-active {
    background: #ea580c;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(234, 88, 12, 0.25);
}
.pts-nav-tab.is-active .pts-tab-badge {
    background: #ffffff !important;
    color: #ea580c !important;
}
.pts-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 900;
}
.pts-tab-badge.pulse {
    animation: tabPulse 1.8s infinite;
}
@keyframes tabPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.5); }
    70% { transform: scale(1.06); box-shadow: 0 0 0 6px rgba(234, 88, 12, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(234, 88, 12, 0); }
}

/* Poster Cards Grid */
.approved-posters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 18px;
}
.poster-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
}
.poster-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    border-color: #cbd5e1;
}
.poster-preview-box {
    position: relative;
    width: 100%;
    height: 210px;
    background: #f1f5f9;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.poster-preview-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
    cursor: pointer;
}
.poster-card:hover .poster-preview-box img {
    transform: scale(1.04);
}
.poster-zoom-btn {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(15, 23, 42, 0.7);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.2s;
}
.poster-card:hover .poster-zoom-btn {
    opacity: 1;
}

.poster-deliverable-thumb {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    object-fit: cover;
    border: 1.5px solid #fed7aa;
    cursor: pointer;
    transition: all 0.2s;
    background: #fff;
}
.poster-deliverable-thumb:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(234,88,12,0.25);
    border-color: #ea580c;
}

.pts-tab-panel { display: none !important; }
.pts-tab-panel.is-active { display: block !important; }
#tabTimesheetsPanel.is-active { display: grid !important; gap: 18px; }

.review-decision-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
@media (max-width: 600px) {
    .review-decision-group { grid-template-columns: 1fr; }
}
.review-decision-label {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 14px 16px;
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
}
.review-decision-label:hover {
    border-color: #cbd5e1;
    transform: translateY(-1px);
}
.review-decision-label.is-approve {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.review-decision-label.is-reject {
    border-color: #fecaca;
    background: #fef2f2;
}
.review-decision-label input[type="radio"] {
    display: none;
}
.review-decision-label.selected.is-approve {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.2);
}
.review-decision-label.selected.is-reject {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.2);
}
.poster-deliverables-filter-btn {
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}
.poster-deliverables-filter-btn:hover {
    background: #f1f5f9;
}
.poster-deliverables-filter-btn.is-active {
    background: #ea580c;
    color: #fff;
    border-color: #ea580c;
}
</style>
@endpush

@section('content')
@php
    $hasTimesheetErrors = $errors->any();
    $selectedProjectId = (int) old('production_initiation_id', 0);
    $selectedLeadId = 0;
    if ($selectedProjectId > 0) {
        $selectedProj = $assignedProjects->firstWhere('id', $selectedProjectId);
        if ($selectedProj) {
            $selectedLeadId = (int) $selectedProj->lead_id;
        }
    }

    $uniqueLeads = $assignedProjects->groupBy('lead_id')->map(function ($projects) {
        $firstProj = $projects->first();
        return [
            'lead_id' => $firstProj->lead_id,
            'company_name' => $firstProj->company_name ?: ($firstProj->lead?->company_name ?: 'No Company')
        ];
    })->values();
@endphp
<div class="pts-page">
    <div class="pts-topbar">
        <div>
            <div class="pts-title">Project Timesheets</div>
            <div class="pts-breadcrumb">Projects > Timesheets</div>
        </div>
        <div class="pts-actions">
            @can('timesheets.create')
            <button type="button" class="pts-btn pts-btn-primary" data-open-timesheet-modal>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Add Timesheet</span>
            </button>
            @endcan
        </div>
    </div>

    <div class="pts-body">
        @if(session('success'))
            <div class="pts-flash success">{{ session('success') }}</div>
        @endif

        @if($hasTimesheetErrors)
            <div class="pts-flash error">Please check the timesheet form and try again.</div>
        @endif

        @if($isCompanyAdmin ?? false)
            <!-- Department Filter Cards (Company Admin) -->
            <div class="pts-dept-section">
                <div class="pts-dept-header">
                    <div class="pts-dept-section-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span>Departments</span>
                    </div>
                    @if(!empty($timesheetFilters['filter_department']))
                        <a href="{{ request()->fullUrlWithQuery(['filter_department' => null, 'page' => 1]) }}" class="pts-dept-clear-btn" title="View all departments">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            <span>Clear Filter (Showing {{ ucfirst(str_replace('_', ' ', $timesheetFilters['filter_department'])) }})</span>
                        </a>
                    @else
                        <span style="font-size:12px; font-weight:700; color:#64748b;">
                            Total: {{ $deptCounts['all'] ?? 0 }} Timesheets
                        </span>
                    @endif
                </div>

                <div class="pts-dept-grid">
                    {{-- Development Card --}}
                    @php
                        $isDevActive = ($timesheetFilters['filter_department'] ?? '') === 'development';
                        $devUrl = request()->fullUrlWithQuery([
                            'filter_department' => $isDevActive ? null : 'development',
                            'page' => 1,
                        ]);
                    @endphp
                    <a href="{{ $devUrl }}" class="pts-dept-card dept-dev {{ $isDevActive ? 'active' : '' }}" title="{{ $isDevActive ? 'Click to show all timesheets' : 'Click to filter Development timesheets' }}">
                        <div class="pts-dept-card-left">
                            <div class="pts-dept-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                            </div>
                            <div class="pts-dept-info">
                                <div class="pts-dept-name">Development</div>
                                <div class="pts-dept-meta">Web, App &amp; Software Timesheets</div>
                            </div>
                        </div>
                        <div class="pts-dept-count-badge">
                            {{ $deptCounts['development'] ?? 0 }}
                        </div>
                    </a>

                    {{-- Designing Card --}}
                    @php
                        $isDesignActive = ($timesheetFilters['filter_department'] ?? '') === 'designing';
                        $designUrl = request()->fullUrlWithQuery([
                            'filter_department' => $isDesignActive ? null : 'designing',
                            'page' => 1,
                        ]);
                    @endphp
                    <a href="{{ $designUrl }}" class="pts-dept-card dept-design {{ $isDesignActive ? 'active' : '' }}" title="{{ $isDesignActive ? 'Click to show all timesheets' : 'Click to filter Designing timesheets' }}">
                        <div class="pts-dept-card-left">
                            <div class="pts-dept-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C22 6.5 17.5 2 12 2z"></path></svg>
                            </div>
                            <div class="pts-dept-info">
                                <div class="pts-dept-name">Designing</div>
                                <div class="pts-dept-meta">UI/UX, Graphics &amp; Video Timesheets</div>
                            </div>
                        </div>
                        <div class="pts-dept-count-badge">
                            {{ $deptCounts['designing'] ?? 0 }}
                        </div>
                    </a>

                    {{-- Digital Marketing Card --}}
                    @php
                        $isDmActive = ($timesheetFilters['filter_department'] ?? '') === 'digital_marketing';
                        $dmUrl = request()->fullUrlWithQuery([
                            'filter_department' => $isDmActive ? null : 'digital_marketing',
                            'page' => 1,
                        ]);
                    @endphp
                    <a href="{{ $dmUrl }}" class="pts-dept-card dept-dm {{ $isDmActive ? 'active' : '' }}" title="{{ $isDmActive ? 'Click to show all timesheets' : 'Click to filter Digital Marketing timesheets' }}">
                        <div class="pts-dept-card-left">
                            <div class="pts-dept-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path><line x1="2" y1="8" x2="4" y2="8"></line><line x1="20" y1="8" x2="22" y2="8"></line></svg>
                            </div>
                            <div class="pts-dept-info">
                                <div class="pts-dept-name">Digital Marketing</div>
                                <div class="pts-dept-meta">SEO, Ads &amp; SMM Timesheets</div>
                            </div>
                        </div>
                        <div class="pts-dept-count-badge">
                            {{ $deptCounts['digital_marketing'] ?? 0 }}
                        </div>
                    </a>
                </div>
            </div>
        @endif

        <!-- Modern Navigation Tabs: Timesheets, Approvals, Approved Posters -->
        <div class="pts-tabs-bar">
            <div class="pts-tabs-cluster">
                <button type="button" class="pts-nav-tab is-active" data-pts-tab="timesheets">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Timesheets Log</span>
                    <span class="pts-tab-badge" style="background:#f1f5f9; color:#475569;">{{ $deptCounts['all'] ?? $groupedTimesheets->total() }}</span>
                </button>
                <button type="button" class="pts-nav-tab" data-pts-tab="approvals">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    <span>Design Poster Approvals</span>
                    @php $pendingCount = $pendingPosterApprovals->count(); @endphp
                    <span class="pts-tab-badge {{ $pendingCount > 0 ? 'pulse' : '' }}" style="background:{{ $pendingCount > 0 ? '#ea580c' : '#f1f5f9' }}; color:{{ $pendingCount > 0 ? '#fff' : '#64748b' }};">
                        {{ $pendingCount }}
                    </span>
                </button>
                <button type="button" class="pts-nav-tab" data-pts-tab="approved-posters">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                    <span>Approved Posters (DM Publishing)</span>
                    <span class="pts-tab-badge" style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0;">{{ $approvedPosters->count() }}</span>
                </button>
            </div>
        </div>

        <div id="tabTimesheetsPanel" class="pts-tab-panel is-active">
        <section class="pts-filter-card">
            <form method="GET" action="{{ route('projects.timesheets') }}" class="pts-filter-form">
                <input type="hidden" name="filter_department" value="{{ $timesheetFilters['filter_department'] ?? '' }}">
                <div class="pts-filter-group">
                    <label class="pts-label">From Date</label>
                    <input type="date" name="filter_date_from" value="{{ $timesheetFilters['filter_date_from'] ?? '' }}" class="pts-input">
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">To Date</label>
                    <input type="date" name="filter_date_to" value="{{ $timesheetFilters['filter_date_to'] ?? '' }}" class="pts-input">
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">Lead (Client)</label>
                    <select name="filter_lead_id" class="pts-select select2 pts-filter-lead-select" data-placeholder="All Leads">
                        <option value="">All Leads</option>
                        @foreach($uniqueLeads as $lead)
                            <option value="{{ $lead['lead_id'] }}" @selected(($timesheetFilters['filter_lead_id'] ?? '') === (string) $lead['lead_id'])>
                                LD-{{ str_pad($lead['lead_id'], 4, '0', STR_PAD_LEFT) }} | {{ $lead['company_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">Project</label>
                    <select name="filter_project_id" class="pts-select select2 pts-filter-project-select" data-placeholder="All projects">
                        <option value="">All Projects</option>
                        @foreach($assignedProjects as $project)
                            <option value="{{ $project->id }}" @selected(($timesheetFilters['filter_project_id'] ?? '') === (string) $project->id)>
                                {{ $project->product_name }} | {{ $project->company_name ?: ($project->lead?->company_name ?: 'No company') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pts-filter-group">
                    <label class="pts-label">Status</label>
                    <select name="filter_status" class="pts-select">
                        <option value="">All Status</option>
                        <option value="pending" @selected(($timesheetFilters['filter_status'] ?? '') === 'pending')>Pending</option>
                        <option value="ongoing" @selected(($timesheetFilters['filter_status'] ?? '') === 'ongoing')>Ongoing</option>
                        <option value="completed" @selected(($timesheetFilters['filter_status'] ?? '') === 'completed')>Completed</option>
                    </select>
                </div>

                @if($departments->isNotEmpty())
                    <div class="pts-filter-group">
                        <label class="pts-label">Department</label>
                        <select name="filter_department_id" class="pts-select">
                            @if($isAdminLike ?? false)
                                <option value="">All Departments</option>
                            @endif
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(($timesheetFilters['filter_department_id'] ?? '') === (string) $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($allUsers->isNotEmpty())
                    <div class="pts-filter-group">
                        <label class="pts-label">Employee</label>
                        <select name="filter_user_id" class="pts-select select2" data-placeholder="All Employees">
                            <option value="">All Employees</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" @selected(($timesheetFilters['filter_user_id'] ?? '') === (string) $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="pts-filter-actions">
                    <button type="submit" class="pts-btn pts-btn-primary" style="min-height:44px;">Filter</button>
                    <a href="{{ route('projects.timesheets') }}" class="pts-reset-btn" style="min-height:44px; display:inline-flex; align-items:center;">Reset</a>
                </div>
            </form>
        </section>

        <!-- Saved Timesheets (Grouped by Date & Member) -->
        <section class="pts-card">
            <div class="pts-card-head">
                <div>
                    <div class="pts-card-title" style="display:flex; align-items:center; gap:8px;">
                        <span>Saved Timesheets (Grouped by Date)</span>
                        @if(!empty($timesheetFilters['filter_department']))
                            @php
                                $badgeStyle = match($timesheetFilters['filter_department']) {
                                    'development' => 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;',
                                    'designing' => 'background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe;',
                                    'digital_marketing' => 'background:#fff7ed; color:#c2410c; border:1px solid #fed7aa;',
                                    default => 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;',
                                };
                            @endphp
                            <span class="pts-badge" style="{{ $badgeStyle }}">
                                {{ ucfirst(str_replace('_', ' ', $timesheetFilters['filter_department'])) }}
                            </span>
                        @endif
                    </div>
                    <div class="pts-card-sub">Your submitted day closing updates grouped date-wise.</div>
                </div>
                <span class="pts-pill">{{ $groupedTimesheets->total() }} Submissions</span>
            </div>
            <div class="pts-card-body" style="padding:0;">
                @if($groupedTimesheets->isNotEmpty())
                    <div class="pts-table-wrap">
                        <table class="pts-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    @if(($isAdminLike ?? false) || ($canViewTeamTimesheets ?? false))
                                        <th>Employee</th>
                                    @endif
                                    <th>Allocated Lead (Client)</th>
                                    <th>Timesheets</th>
                                    <th>Submitted</th>
                                    <th>Status Overview</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedTimesheets as $group)
                                    @php
                                        $firstTimesheet = $group->first();
                                        $groupDate = $firstTimesheet->timesheet_date;
                                        $timesheetUser = $firstTimesheet->user;
                                        $totalEntries = $group->count();

                                        $pendingCount = $group->where('status', 'pending')->count();
                                        $ongoingCount = $group->where('status', 'ongoing')->count();
                                        $completedCount = $group->where('status', 'completed')->count();

                                        // Prepare clean JSON array for modal
                                        $groupTimesheetsData = $group->map(function($t, $idx) {
                                            $leadId = $t->project?->lead_id;
                                            $leadFormatted = $leadId ? 'LD-' . str_pad($leadId, 4, '0', STR_PAD_LEFT) : 'N/A';
                                            $companyName = $t->project?->company_name ?: ($t->project?->lead?->company_name ?: 'No Company');
                                            $productName = $t->project?->product_name ?: 'Project removed';
                                            $deliveryDate = optional($t->project_delivery_date)->format('d M Y') ?: (optional($t->project?->timesheet_delivery_date)->format('d M Y') ?: 'Not available');

                                            $deptName = strtolower((string)($t->project?->department?->name ?? ''));
                                            $isDesignOrDm = str_contains($deptName, 'design') || str_contains($deptName, 'dm') || str_contains($deptName, 'digital marketing');

                                            return [
                                                'id' => $t->id,
                                                'sno' => $idx + 1,
                                                'lead_id' => $leadFormatted,
                                                'lead_company' => $companyName,
                                                'product_name' => $productName,
                                                'delivery_date' => $deliveryDate,
                                                'assigned_task' => $t->assigned_task_desc ?? '—',
                                                'day_closing_update' => $t->user_closing_update ?? '',
                                                'status' => strtolower($t->status ?: 'pending'),
                                                'project_type' => $t->project_type ?: 'recurring',
                                                'is_design_dm' => $isDesignOrDm,
                                                'poster_count' => (int) $t->poster_count,
                                                'video_count' => (int) $t->video_count,
                                                'committed_posters' => (int) $t->committed_posters,
                                                'committed_videos' => (int) $t->committed_videos,
                                                'waiting_posters' => (int) $t->waiting_posters,
                                                'waiting_videos' => (int) $t->waiting_videos,
                                                'update_status_url' => route('projects.timesheets.update-status', $t->id),
                                                'attachments' => $t->attachment_list,
                                                'poster_approval_status' => $t->poster_approval_status,
                                                'poster_approval_remarks' => $t->poster_approval_remarks,
                                                'poster_approved_by' => $t->posterApprovedBy?->name,
                                                'poster_attachments' => $t->poster_attachments,
                                                'review_poster_url' => route('projects.timesheets.review-poster', $t->id),
                                                'dm_proofs' => $t->dm_post_proof_list,
                                                'user_name' => $timesheetUser?->name ?? 'Designer',
                                            ];
                                        })->values();

                                        $uniqueCompanies = $group->map(function ($ts) {
                                            return $ts->project?->company_name ?: ($ts->project?->lead?->company_name ?: 'No Company');
                                        })->unique()->values();

                                        $latestSubmitted = $group->sortByDesc('created_at')->first()?->created_at;
                                    @endphp
                                    <tr data-group-row>
                                        <!-- Date -->
                                        <td>
                                            <div style="font-weight:800; color:#0f172a;">
                                                {{ optional($groupDate)->format('d M Y') ?: 'No date' }}
                                            </div>
                                            <div class="pts-meta">{{ optional($groupDate)->format('l') }}</div>
                                        </td>

                                        <!-- Employee -->
                                        @if(($isAdminLike ?? false) || ($canViewTeamTimesheets ?? false))
                                            <td>
                                                <div style="display:flex; align-items:center; gap:10px;">
                                                    <div style="width:36px; height:36px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; color:#ea580c; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:13px;">
                                                        {{ strtoupper(substr($timesheetUser?->name ?: 'U', 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <div style="font-weight:800; color:#0f172a;">{{ $timesheetUser?->name ?? 'Unknown' }}</div>
                                                        <div class="pts-meta">{{ $timesheetUser?->designation ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                        @endif

                                        <!-- Allocated Lead (Client) -->
                                        <td>
                                            <div style="display:flex; flex-direction:column; gap:6px; max-width:280px;">
                                                @foreach($uniqueCompanies->take(2) as $companyName)
                                                    <div style="font-weight:800; color:#0f172a; display:flex; align-items:center; gap:7px;">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16"></path><path d="M12 7h.01"></path><path d="M12 11h.01"></path><path d="M12 15h.01"></path></svg>
                                                        <span>{{ $companyName }}</span>
                                                    </div>
                                                @endforeach
                                                @if($uniqueCompanies->count() > 2)
                                                    <div class="pts-meta" style="font-weight:800; color:#64748b; margin-left:21px;">+ {{ $uniqueCompanies->count() - 2 }} more client(s)</div>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Timesheets button -->
                                        <td>
                                            <button type="button"
                                                    class="pts-btn pts-btn-outline open-timesheets-modal-btn"
                                                    data-user-name="{{ $timesheetUser?->name ?: 'My Timesheets' }}"
                                                    data-timesheet-date="{{ optional($groupDate)->format('d M Y') }}"
                                                    data-timesheets="{{ json_encode($groupTimesheetsData) }}">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                                <span>View Timesheets</span>
                                                <span class="pts-badge count" style="padding:2px 7px; font-size:10px; margin-left:2px;">{{ $totalEntries }}</span>
                                            </button>
                                        </td>

                                        <!-- Submitted -->
                                        <td>
                                            <div style="font-weight:700; color:#334155;">
                                                {{ optional($latestSubmitted)->format('d M Y, h:i A') ?: 'Not available' }}
                                            </div>
                                        </td>

                                        <!-- Status Overview -->
                                        <td>
                                            <div class="status-overview-badges" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                                @if($pendingCount > 0)
                                                    <span class="pts-badge pending">{{ $pendingCount }} Pending</span>
                                                @endif
                                                @if($ongoingCount > 0)
                                                    <span class="pts-badge ongoing">{{ $ongoingCount }} Ongoing</span>
                                                @endif
                                                @if($completedCount > 0)
                                                    <span class="pts-badge completed">{{ $completedCount }} Completed</span>
                                                @endif
                                                @if($pendingCount === 0 && $ongoingCount === 0 && $completedCount === 0)
                                                    <span class="pts-meta">—</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($groupedTimesheets->hasPages())
                        @include('partials.table-pagination', ['paginator' => $groupedTimesheets])
                    @endif
                @else
                    <div class="pts-empty">No timesheets submitted yet. Use Add Timesheet to enter today&apos;s day closing update.</div>
                @endif
            </div>
        </section>
        </div> <!-- #tabTimesheetsPanel -->

        <!-- Tab 2: Design Poster Approvals -->
        <div id="tabApprovalsPanel" class="pts-tab-panel">
            <section class="pts-card">
                <div class="pts-card-head" style="flex-wrap:wrap; align-items:center;">
                    <div>
                        <div class="pts-card-title" style="display:flex; align-items:center; gap:8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            <span>Design Poster Approvals</span>
                            @if($pendingPosterApprovals->count() > 0)
                                <span class="pts-badge pending" style="font-size:11px;">{{ $pendingPosterApprovals->count() }} Pending Review</span>
                            @endif
                        </div>
                        <div class="pts-card-sub">Posters uploaded by Design team executives awaiting Design TL verification. Approved counts automatically sync to SMM Sheet Done Count.</div>
                    </div>
                    <!-- Sub-filters: All, Pending, Approved, Rejected -->
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="poster-deliverables-filter-btn is-active" data-deliverable-filter="all">All ({{ $allPosterDeliverables->count() }})</button>
                        <button type="button" class="poster-deliverables-filter-btn" data-deliverable-filter="pending">Pending ({{ $allPosterDeliverables->where('poster_approval_status', 'pending')->count() }})</button>
                        <button type="button" class="poster-deliverables-filter-btn" data-deliverable-filter="approved">Approved ({{ $allPosterDeliverables->where('poster_approval_status', 'approved')->count() }})</button>
                        <button type="button" class="poster-deliverables-filter-btn" data-deliverable-filter="rejected">Rejected ({{ $allPosterDeliverables->where('poster_approval_status', 'rejected')->count() }})</button>
                    </div>
                </div>
                <div class="pts-card-body" style="padding:0;">
                    @if($allPosterDeliverables->isNotEmpty())
                        <div class="pts-table-wrap">
                            <table class="pts-table">
                                <thead>
                                    <tr>
                                        <th style="width:70px;">Poster</th>
                                        <th>Client (Lead)</th>
                                        <th>Project / Deliverables</th>
                                        <th>Designer</th>
                                        <th>Submitted Date</th>
                                        <th style="text-align:center;">Count</th>
                                        <th>TL Approval Status</th>
                                        <th>TL Remarks</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allPosterDeliverables as $deliv)
                                        @php
                                            $posters = $deliv->poster_attachments;
                                            $firstPoster = !empty($posters) ? $posters[0] : null;
                                            $status = $deliv->poster_approval_status ?: 'pending';
                                            $pCount = (int) $deliv->poster_count ?: (count($posters) ?: 1);
                                            $clientName = $deliv->project?->company_name ?: ($deliv->project?->lead?->company_name ?: 'No Company');
                                            $leadCode = $deliv->project?->lead_id ? 'LD-' . str_pad($deliv->project->lead_id, 4, '0', STR_PAD_LEFT) : 'N/A';
                                        @endphp
                                        <tr data-deliverable-row data-deliverable-status="{{ $status }}">
                                            <!-- Poster Thumbnail -->
                                            <td>
                                                @if($firstPoster)
                                                    <div style="position:relative; width:52px; height:52px;">
                                                        <img src="{{ $firstPoster['url'] }}" alt="{{ $firstPoster['name'] }}" class="poster-deliverable-thumb open-lightbox-trigger" data-lightbox-src="{{ $firstPoster['url'] }}" data-lightbox-title="{{ $deliv->project?->product_name }} - {{ $clientName }}" title="Click to view full image">
                                                        @if(count($posters) > 1)
                                                            <span style="position:absolute; bottom:-4px; right:-4px; background:#111827; color:#fff; font-size:10px; font-weight:800; border-radius:999px; padding:1px 5px; border:1px solid #fff;">
                                                                +{{ count($posters) - 1 }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div style="width:52px; height:52px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:20px;">
                                                        🖼️
                                                    </div>
                                                @endif
                                            </td>
                                            <!-- Client -->
                                            <td>
                                                <div style="font-weight:800; color:#0f172a;">{{ $clientName }}</div>
                                                <div class="pts-meta" style="font-weight:700; color:#ea580c;">{{ $leadCode }}</div>
                                            </td>
                                            <!-- Project -->
                                            <td>
                                                <div class="pts-project">{{ $deliv->project?->product_name ?: 'Project' }}</div>
                                                <div class="pts-meta">{{ \Illuminate\Support\Str::limit($deliv->user_closing_update ?: $deliv->day_closing_update, 40) }}</div>
                                            </td>
                                            <!-- Designer -->
                                            <td>
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <div style="width:30px; height:30px; border-radius:8px; background:#f5f3ff; color:#7c3aed; font-weight:800; font-size:11px; display:flex; align-items:center; justify-content:center; border:1px solid #ddd6fe;">
                                                        {{ strtoupper(substr($deliv->user?->name ?: 'D', 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <div style="font-weight:800; color:#0f172a; font-size:12.5px;">{{ $deliv->user?->name ?? 'Designer' }}</div>
                                                        <div class="pts-meta" style="font-size:10.5px;">{{ $deliv->user?->designation ?: 'Designing Team' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <!-- Date -->
                                            <td>
                                                <div style="font-weight:700; color:#334155; font-size:12.5px;">
                                                    {{ optional($deliv->timesheet_date)->format('d M Y') }}
                                                </div>
                                                <div class="pts-meta">{{ optional($deliv->created_at)->format('h:i A') }}</div>
                                            </td>
                                            <!-- Count -->
                                            <td style="text-align:center;">
                                                <span class="pts-badge count" style="font-size:12px; font-weight:800; padding:4px 10px; background:#fff7ed; color:#ea580c; border-color:#fed7aa;">
                                                    {{ $pCount }} Poster{{ $pCount > 1 ? 's' : '' }}
                                                </span>
                                            </td>
                                            <!-- Status -->
                                            <td>
                                                @if($status === 'approved')
                                                    <span class="pts-badge completed" style="font-size:11.5px; padding:5px 10px;">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                        <span>Approved</span>
                                                    </span>
                                                    <div class="pts-meta" style="margin-top:3px; font-size:10.5px; color:#15803d; font-weight:700;">
                                                        ✓ Synced to SMM Sheet Done Count
                                                    </div>
                                                @elseif($status === 'rejected')
                                                    <span class="pts-badge" style="font-size:11.5px; padding:5px 10px; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                                        <span>Rejected</span>
                                                    </span>
                                                    <div class="pts-meta" style="margin-top:3px; font-size:10.5px; color:#b91c1c; font-weight:700;">
                                                        ✕ Not added to SMM Sheet
                                                    </div>
                                                @else
                                                    <span class="pts-badge pending" style="font-size:11.5px; padding:5px 10px;">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                                        <span>Pending TL Review</span>
                                                    </span>
                                                @endif
                                            </td>
                                            <!-- Remarks -->
                                            <td>
                                                @if(!empty($deliv->poster_approval_remarks))
                                                    <div style="font-size:12px; color:#334155; line-height:1.45; background:#f8fafc; padding:6px 10px; border-radius:8px; border:1px solid #e2e8f0; max-width:220px;" title="{{ $deliv->poster_approval_remarks }}">
                                                        <strong style="color:#0f172a; font-size:11px;">TL Note:</strong> {{ \Illuminate\Support\Str::limit($deliv->poster_approval_remarks, 60) }}
                                                    </div>
                                                @else
                                                    <span class="pts-meta">—</span>
                                                @endif
                                            </td>
                                            <!-- Actions -->
                                            <td style="text-align:right;">
                                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                                    @if($canApprovePosters)
                                                        <button type="button" class="pts-btn pts-btn-primary open-review-poster-btn"
                                                                data-timesheet-id="{{ $deliv->id }}"
                                                                data-designer-name="{{ $deliv->user?->name }}"
                                                                data-project-name="{{ $deliv->project?->product_name }}"
                                                                data-client-name="{{ $clientName }}"
                                                                data-poster-count="{{ $pCount }}"
                                                                data-status="{{ $status }}"
                                                                data-remarks="{{ $deliv->poster_approval_remarks }}"
                                                                data-posters="{{ json_encode($posters) }}"
                                                                data-review-url="{{ route('projects.timesheets.review-poster', $deliv->id) }}"
                                                                style="padding:6px 12px; font-size:12px;">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                                                            <span>{{ $status === 'pending' ? 'Review Poster' : 'Change Decision' }}</span>
                                                        </button>
                                                    @endif
                                                    @if($firstPoster)
                                                        <a href="{{ $firstPoster['url'] }}" download="{{ $firstPoster['name'] }}" class="pts-btn pts-btn-outline" style="padding:6px 10px; font-size:12px;" title="Download Poster">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="pts-empty">No design poster deliverables submitted yet.</div>
                    @endif
                </div>
            </section>
        </div>

        <!-- Tab 3: Approved Posters (DM Publishing) -->
        <div id="tabApprovedPostersPanel" class="pts-tab-panel">
            <div class="pts-card" style="margin-bottom:18px;">
                <div class="pts-card-head" style="flex-wrap:wrap; align-items:center;">
                    <div>
                        <div class="pts-card-title" style="display:flex; align-items:center; gap:8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            <span>Approved Posters for DM Publishing</span>
                        </div>
                        <div class="pts-card-sub">Posters approved by Design Team Leader, ready for Digital Marketing publishing to Facebook and other social media accounts.</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="pts-pill" style="background:#f0fdf4; border-color:#bbf7d0; color:#15803d;">
                            {{ $approvedPosters->count() }} Ready for Publishing
                        </span>
                    </div>
                </div>
            </div>

            @if($approvedPosters->isNotEmpty())
                <div class="approved-posters-grid">
                    @foreach($approvedPosters as $appTs)
                        @php
                            $posters = $appTs->poster_attachments;
                            $firstPoster = !empty($posters) ? $posters[0] : null;
                            $clientName = $appTs->project?->company_name ?: ($appTs->project?->lead?->company_name ?: 'No Company');
                            $leadCode = $appTs->project?->lead_id ? 'LD-' . str_pad($appTs->project->lead_id, 4, '0', STR_PAD_LEFT) : 'N/A';
                            $pCount = (int) $appTs->poster_count ?: (count($posters) ?: 1);
                            $proofs = $appTs->dm_post_proof_list;
                            $proofCount = count($proofs);
                            $publishedCount = (int) ($appTs->dm_published_count ?? 0);
                            $smmSheet = $appTs->project?->smmSheet;
                            $smmDesignCount = $smmSheet ? (int) $smmSheet->design_completed_posters : $pCount;
                            $smmDmCount = $smmSheet ? (int) $smmSheet->dm_completed_posters : $publishedCount;
                        @endphp
                        <div class="poster-card" data-approved-poster-card>
                            <!-- Preview Box -->
                            <div class="poster-preview-box">
                                @if($firstPoster)
                                    <img src="{{ $firstPoster['url'] }}" alt="{{ $firstPoster['name'] }}" class="open-lightbox-trigger" data-lightbox-src="{{ $firstPoster['url'] }}" data-lightbox-title="{{ $appTs->project?->product_name }} - {{ $clientName }}" loading="lazy">
                                    <button type="button" class="poster-zoom-btn open-lightbox-trigger" data-lightbox-src="{{ $firstPoster['url'] }}" data-lightbox-title="{{ $appTs->project?->product_name }} - {{ $clientName }}" title="Zoom in">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                                    </button>
                                @else
                                    <div style="color:#94a3b8; font-size:36px;">🖼️</div>
                                @endif
                                <div style="position:absolute; top:10px; left:10px; display:flex; gap:6px; flex-wrap:wrap;">
                                    <span class="pts-badge completed" style="box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                                        Approved by TL
                                    </span>
                                    @if($publishedCount > 0 || $proofCount > 0)
                                        <span class="pts-badge" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                                            ✓ Published ({{ $publishedCount ?: $proofCount }} / {{ $pCount }})
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Details Body -->
                            <div style="padding:16px; flex:1; display:flex; flex-direction:column; justify-content:space-between; gap:12px;">
                                <div>
                                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
                                        <div>
                                            <div style="font-size:15px; font-weight:900; color:#0f172a; line-height:1.25;">{{ $clientName }}</div>
                                            <div class="pts-meta" style="font-weight:700; color:#ea580c; margin-top:2px;">{{ $leadCode }} | {{ $appTs->project?->product_name }}</div>
                                        </div>
                                        <span class="pts-badge count" style="font-size:11px; padding:3px 8px; flex-shrink:0;">
                                            {{ $pCount }} Poster{{ $pCount > 1 ? 's' : '' }}
                                        </span>
                                    </div>

                                    <div style="margin-top:10px; font-size:12px; color:#475569; display:grid; gap:4px;">
                                        <div><strong>Designer:</strong> {{ $appTs->user?->name ?? 'Designer' }}</div>
                                        <div><strong>Approved by:</strong> {{ $appTs->posterApprovedBy?->name ?? 'TL' }} on {{ optional($appTs->poster_approved_at)->format('d M Y') }}</div>
                                        @if($publishedCount > 0 && ($appTs->dmPublishedBy || $appTs->dm_published_at))
                                            <div><strong>Published by:</strong> {{ $appTs->dmPublishedBy?->name ?? 'DM Team' }} on {{ optional($appTs->dm_published_at)->format('d M Y') }}</div>
                                        @endif
                                        @if(!empty($appTs->poster_approval_remarks))
                                            <div style="background:#f8fafc; padding:6px 10px; border-radius:8px; border:1px solid #e2e8f0; font-size:11.5px; color:#334155; margin-top:4px;">
                                                <strong>TL Note:</strong> &ldquo;{{ $appTs->poster_approval_remarks }}&rdquo;
                                            </div>
                                        @endif
                                    </div>

                                    @if($proofCount > 0)
                                        <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #e2e8f0;">
                                            <div style="font-size:11.5px; font-weight:800; color:#1e293b; margin-bottom:6px; display:flex; align-items:center; gap:6px;">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                                                <span>FB Proof Uploaded ({{ $proofCount }})</span>
                                            </div>
                                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                                @foreach($proofs as $pr)
                                                    <a href="{{ $pr['url'] }}" target="_blank" class="open-lightbox-trigger" data-lightbox-src="{{ $pr['url'] }}" data-lightbox-title="Proof: {{ $clientName }}" style="display:inline-block; width:38px; height:38px; border-radius:6px; overflow:hidden; border:1.5px solid #bfdbfe;">
                                                        <img src="{{ $pr['url'] }}" alt="proof" style="width:100%; height:100%; object-fit:cover;">
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Card Action Buttons -->
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:8px;">
                                    @if($firstPoster)
                                        <a href="{{ $firstPoster['url'] }}" download="{{ $firstPoster['name'] }}" class="pts-btn pts-btn-primary" style="padding:8px 12px; font-size:12px; text-decoration:none;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                            <span>Download</span>
                                        </a>
                                    @else
                                        <div></div>
                                    @endif

                                    <button type="button" class="pts-btn pts-btn-outline open-dm-proof-modal-btn"
                                            data-timesheet-id="{{ $appTs->id }}"
                                            data-client-name="{{ $clientName }}"
                                            data-project-name="{{ $appTs->project?->product_name }}"
                                            data-poster-count="{{ $pCount }}"
                                            data-published-count="{{ $publishedCount }}"
                                            data-smm-design="{{ $smmDesignCount }}"
                                            data-smm-dm="{{ $smmDmCount }}"
                                            data-proofs="{{ json_encode($proofs) }}"
                                            data-upload-url="{{ route('projects.timesheets.dm-post-proof', $appTs->id) }}"
                                            style="padding:8px 12px; font-size:12px;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                        <span>{{ ($publishedCount >= $pCount && $pCount > 0) ? 'Manage Proofs' : 'Upload Proof & Publish' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="pts-card">
                    <div class="pts-empty">No approved posters ready for publishing yet. Approved posters will appear here.</div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- View Timesheets Details Modal Popup -->
<div class="pts-modal-overlay" data-view-timesheets-modal-overlay></div>
<div class="pts-modal" data-view-timesheets-modal style="width:min(1150px, calc(100vw - 32px));">
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div>
                <div class="pts-card-title" id="modalTimesheetMemberName" style="font-size:17px; font-weight:900; color:#111827; line-height:1.3;">Timesheet Details</div>
                <div class="pts-card-sub" id="modalTimesheetSubTitle" style="margin-top:3px; font-size:12px; color:#64748b;">Date: N/A | Total 0 Timesheet(s)</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-view-timesheets-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body" style="padding:0;">
        <div class="pts-table-wrap">
            <table class="modal-task-table">
                <thead>
                    <tr>
                        <th class="th-center" style="width:40px;">#</th>
                        <th style="width:160px;">Lead (Client)</th>
                        <th style="width:170px;">Product / Delivery</th>
                        <th style="width:230px;">Assigned Task</th>
                        <th style="min-width:270px;">Day Closing Update</th>
                        <th class="th-center" style="width:130px;">Status</th>
                    </tr>
                </thead>
                <tbody id="modalTimesheetsTableBody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Day Closing Update Entry/Edit Submodal Popup -->
<div class="pts-submodal-overlay" data-entry-closing-modal-overlay></div>
<div class="pts-submodal" data-entry-closing-modal>
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:38px; height:38px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
            </div>
            <div>
                <div class="pts-card-title" id="entryClosingModalTitle" style="font-size:16px; font-weight:900; color:#111827;">Add Day Closing Update</div>
                <div class="pts-card-sub" id="entryClosingModalSubTitle" style="margin-top:2px; font-size:12px; color:#64748b;">Project Name - Client</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-entry-closing-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body">
        <form id="entryClosingForm">
            <input type="hidden" id="entryClosingTimesheetId">
            <input type="hidden" id="entryClosingUpdateUrl">
            <div style="display:grid; gap:16px;">
                <div>
                    <label class="pts-label">Day Closing Update Details <span style="color:#ef4444;">*</span></label>
                    <textarea id="entryClosingTextarea" class="pts-textarea" rows="6" required style="min-height:140px;" placeholder="Write your completed tasks and day closing details here..."></textarea>
                </div>

                <!-- Multiple File Attachments -->
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; flex-wrap:wrap; gap:6px;">
                        <label class="pts-label" style="margin-bottom:0; display:inline-flex; align-items:center; gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                            <span>File Attachments (Posters, Deliverables, Screenshots)</span>
                            <span id="entryAttachmentMandatoryBadge" style="font-weight:800; text-transform:none; color:#ef4444; font-size:11.5px; display:none;">* (Mandatory for Designing &amp; Digital Marketing)</span>
                            <span id="entryAttachmentOptionalBadge" style="font-weight:600; text-transform:none; color:#64748b; font-size:11px;">(Optional, Multiple files)</span>
                        </label>
                        <span style="font-size:11px; color:#64748b; font-weight:600;">Max 25MB each</span>
                    </div>

                    <!-- Existing Attachments List -->
                    <div id="entryExistingAttachmentsContainer" style="display:none; margin-bottom:10px;">
                        <div style="font-size:11.5px; font-weight:700; color:#475569; margin-bottom:6px; display:flex; align-items:center; gap:5px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                            <span>Current Attachments</span>
                        </div>
                        <div id="entryExistingAttachmentsList" style="display:flex; flex-direction:column; gap:6px;"></div>
                    </div>

                    <!-- File Dropzone -->
                    <div id="entryFileDropzone" class="task-file-dropzone" style="background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:8px; padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px; cursor:pointer; transition:all 0.2s;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:34px; height:34px; border-radius:8px; background:#fff7ed; border:1px solid #fed7aa; color:#ea580c; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </div>
                            <div>
                                <div style="font-size:12.5px; font-weight:700; color:#1e293b;">Click to upload or drag &amp; drop files here</div>
                                <div style="font-size:11px; color:#64748b;">Images, PDFs, Docs, Sheets, Zips (Multiple files allowed)</div>
                            </div>
                        </div>
                        <button type="button" class="pts-btn" style="padding:6px 12px; font-size:11.5px; pointer-events:none; background:#ffffff; flex-shrink:0;">Browse Files</button>
                        <input type="file" id="entryClosingAttachments" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.rar,.txt" style="display:none;">
                    </div>
                    <!-- Selected Files Preview -->
                    <div id="entrySelectedFilesList" style="margin-top:8px; display:flex; flex-direction:column; gap:6px;"></div>
                </div>
            </div>
            <div class="pts-actions" style="margin-top:20px; justify-content:flex-end;">
                <button type="button" class="pts-btn" data-close-entry-closing-modal>Cancel</button>
                <button type="submit" id="entryClosingSubmitBtn" class="pts-btn pts-btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Save Closing Update</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Day Closing Update Details View Submodal Popup -->
<div class="pts-submodal-overlay" data-view-closing-modal-overlay></div>
<div class="pts-submodal" data-view-closing-modal>
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:38px; height:38px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            </div>
            <div>
                <div class="pts-card-title" id="viewClosingModalTitle" style="font-size:16px; font-weight:900; color:#111827;">Day Closing Update Details</div>
                <div class="pts-card-sub" id="viewClosingModalSubTitle" style="margin-top:2px; font-size:12px; color:#64748b;">Project - Date</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-view-closing-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body">
        <div id="viewClosingModalContent" style="white-space:pre-wrap; line-height:1.65; color:#1e293b; font-size:13.5px; background:#f8fafc; padding:18px; border-radius:10px; border:1px solid #e2e8f0; max-height:350px; overflow-y:auto;">
        </div>
        <!-- View Attachments Container -->
        <div id="viewClosingModalAttachmentsContainer" style="margin-top:14px; display:none;">
            <div style="font-size:12px; font-weight:800; color:#475569; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                <span>Attached Files (<span id="viewClosingModalAttachmentsCount">0</span>)</span>
            </div>
            <div id="viewClosingModalAttachmentsList" style="display:flex; flex-direction:column; gap:6px;"></div>
        </div>
        <div class="pts-actions" style="margin-top:18px; justify-content:flex-end; gap:8px;">
            <button type="button" class="pts-btn" data-close-view-closing-modal>Close</button>
            <button type="button" id="viewClosingEditShortcutBtn" class="pts-btn pts-btn-primary">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                <span>Edit Update</span>
            </button>
        </div>
    </div>
</div>

<!-- Add Timesheet Modal Popup -->
<div class="pts-modal-overlay {{ $hasTimesheetErrors ? 'is-open' : '' }}" data-timesheet-modal-overlay></div>
<div class="pts-modal {{ $hasTimesheetErrors ? 'is-open' : '' }}" data-timesheet-modal>
    <div class="pts-modal-head">
        <div>
            <div class="pts-card-title">Add Timesheet</div>
            <div class="pts-card-sub">Select one allocated project and add your day closing tasks.</div>
        </div>
        <button type="button" class="pts-modal-close" data-close-timesheet-modal aria-label="Close timesheet modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="pts-modal-body">
        <form id="addTimesheetForm" method="POST" action="{{ route('projects.timesheets.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="pts-form-grid">
                <!-- Lead Dropdown -->
                <div class="pts-grid-col-6">
                    <label class="pts-label">Lead (Client)</label>
                    <select id="leadSelect" class="pts-select select2" data-placeholder="Select Lead" required>
                        <option value="">Select Lead</option>
                        @foreach($uniqueLeads as $lead)
                            <option value="{{ $lead['lead_id'] }}" @selected($selectedLeadId === (int) $lead['lead_id'])>
                                LD-{{ str_pad($lead['lead_id'], 4, '0', STR_PAD_LEFT) }} | {{ $lead['company_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Project Dropdown -->
                <div class="pts-grid-col-6">
                    <label class="pts-label">Allocated Project</label>
                    <select name="production_initiation_id" id="projectSelect" class="pts-select select2 pts-project-select" data-placeholder="Select project" required disabled>
                        <option value="">Select project</option>
                    </select>
                    @if($assignedProjects->isEmpty())
                        <div class="pts-help">No allocated projects are available for your account right now.</div>
                    @else
                        <div class="pts-help">Select a lead first to see its allocated projects.</div>
                    @endif
                    @error('production_initiation_id')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Date -->
                <div class="pts-grid-col-4">
                    <label class="pts-label">Date</label>
                    <input type="date" name="timesheet_date" id="timesheetDateInput" min="{{ $today }}" value="{{ old('timesheet_date', $today) }}" class="pts-input" required>
                    @error('timesheet_date')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Project Delivery Date -->
                <div class="pts-grid-col-4">
                    <label class="pts-label">Project Delivery Date</label>
                    <input type="date" class="pts-input" data-project-delivery-date readonly>
                </div>

                <!-- Status -->
                <div class="pts-grid-col-4">
                    <label class="pts-label">Status</label>
                    <select name="status" class="pts-select" required>
                        <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                        <option value="ongoing" @selected(old('status') === 'ongoing')>Ongoing</option>
                        <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                    </select>
                    @error('status')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Project Type (Design/DM only) -->
                <div class="pts-grid-col-12 design-dm-only" id="projectTypeContainer" style="display: none;">
                    <label class="pts-label">Project Type</label>
                    <select name="project_type" id="projectTypeSelect" class="pts-select">
                        <option value="recurring" @selected(old('project_type', 'recurring') === 'recurring')>Recurring</option>
                        <option value="onetime" @selected(old('project_type') === 'onetime')>Onetime</option>
                    </select>
                    @error('project_type')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                @if(auth()->user()?->belongsToDesigningDepartment())
                    <div class="pts-grid-col-3 project-counts-fields" style="display: none;">
                        <label class="pts-label">Committed Posters Today</label>
                        <input type="number" name="committed_posters" value="{{ old('committed_posters', 0) }}" class="pts-input" min="0" step="1">
                        @error('committed_posters')
                            <div class="pts-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="pts-grid-col-3 project-counts-fields" style="display: none;">
                        <label class="pts-label">Committed Videos Today</label>
                        <input type="number" name="committed_videos" value="{{ old('committed_videos', 0) }}" class="pts-input" min="0" step="1">
                        @error('committed_videos')
                            <div class="pts-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="pts-grid-col-3 project-counts-fields" style="display: none;">
                        <label class="pts-label">Waiting Approval Posters</label>
                        <input type="number" name="waiting_posters" value="{{ old('waiting_posters', 0) }}" class="pts-input" min="0" step="1">
                        @error('waiting_posters')
                            <div class="pts-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="pts-grid-col-3 project-counts-fields" style="display: none;">
                        <label class="pts-label">Waiting Approval Videos</label>
                        <input type="number" name="waiting_videos" value="{{ old('waiting_videos', 0) }}" class="pts-input" min="0" step="1">
                        @error('waiting_videos')
                            <div class="pts-error">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
                <!-- Poster count (Design/DM only) -->
                <div class="pts-grid-col-6 design-dm-only project-counts-fields" style="display: none;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                        <label class="pts-label" style="margin-bottom:0;">Poster Completed Count</label>
                        @if($isDmUser)
                            <span id="timesheetDmDesignCapBadge" class="pts-pill" style="font-size:11px; padding:2px 8px; background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; display:none;">
                                Design Done: 0
                            </span>
                        @endif
                    </div>
                    <input type="number" name="poster_count" id="timesheetPosterCountInput" value="{{ old('poster_count', 0) }}" class="pts-input" min="0" step="1">
                    @if($isDmUser)
                        <div id="timesheetDmDesignCapNote" class="pts-help" style="color:#ea580c; font-weight:700; display:none;">
                            ⚠️ Cannot exceed Design completed count (<span id="timesheetDmDesignCapVal">0</span>).
                        </div>
                    @endif
                    @error('poster_count')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Video count (Design/DM only) -->
                <div class="pts-grid-col-6 design-dm-only project-counts-fields" style="display: none;">
                    <label class="pts-label">Video Completed Count</label>
                    <input type="number" name="video_count" value="{{ old('video_count', 0) }}" class="pts-input" min="0" step="1">
                    @error('video_count')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="pts-grid-col-12" id="dayClosingUpdateContainer">
                    <label class="pts-label">Day Closing Update</label>
                    <textarea name="day_closing_update" id="dayClosingUpdateTextarea" class="pts-textarea" rows="7" required placeholder="Enter your day closing update details...">{{ old('day_closing_update') }}</textarea>
                    <div class="pts-help">Enter your day closing update details.</div>
                    @error('day_closing_update')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>

                <!-- File Attachments (Multiple Allowed) -->
                <div class="pts-grid-col-12" id="timesheetAttachmentsContainer">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; flex-wrap:wrap; gap:6px;">
                        <label class="pts-label" style="margin-bottom:0; display:inline-flex; align-items:center; gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                            <span>File Attachments (Posters, Deliverables, Proofs)</span>
                            <span id="timesheetAttachmentMandatoryBadge" style="font-weight:800; text-transform:none; color:#ef4444; font-size:11.5px; {{ ($isDesignUser || $isDmUser) ? '' : 'display:none;' }}">* (Mandatory for Designing &amp; Digital Marketing)</span>
                            <span id="timesheetAttachmentOptionalBadge" style="font-weight:600; text-transform:none; color:#64748b; font-size:11px; {{ ($isDesignUser || $isDmUser) ? 'display:none;' : '' }}">(Optional, Multiple files)</span>
                        </label>
                        <span style="font-size:11px; color:#64748b; font-weight:600;">Images, PDFs, Docs, Sheets, Zips (Max 25MB each)</span>
                    </div>

                    <!-- Existing Attachments List in Add/Edit Timesheet modal -->
                    <div id="timesheetExistingAttachmentsContainer" style="display:none; margin-bottom:10px;">
                        <div style="font-size:11.5px; font-weight:700; color:#475569; margin-bottom:6px; display:flex; align-items:center; gap:5px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                            <span>Current Attachments</span>
                        </div>
                        <div id="timesheetExistingAttachmentsList" style="display:flex; flex-direction:column; gap:6px;"></div>
                    </div>

                    <div id="timesheetFileDropzone" class="task-file-dropzone" style="background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:8px; padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px; cursor:pointer; transition:all 0.2s;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:34px; height:34px; border-radius:8px; background:#fff7ed; border:1px solid #fed7aa; color:#ea580c; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </div>
                            <div>
                                <div style="font-size:12.5px; font-weight:700; color:#1e293b;">Click to upload or drag &amp; drop files here</div>
                                <div style="font-size:11px; color:#64748b;">Images, PDFs, Docs, Sheets, Zips (Multiple files allowed)</div>
                            </div>
                        </div>
                        <button type="button" class="pts-btn" style="padding:6px 12px; font-size:11.5px; pointer-events:none; background:#ffffff; flex-shrink:0;">Browse Files</button>
                        <input type="file" name="attachments[]" id="timesheetAttachmentsInput" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.zip,.rar,.txt" style="display:none;">
                    </div>
                    <div id="timesheetSelectedFilesList" style="margin-top:8px; display:flex; flex-direction:column; gap:6px;"></div>
                    @error('attachments')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                    @error('attachments.*')
                        <div class="pts-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="pts-actions" style="margin-top:18px; justify-content:flex-end;">
                <button type="button" class="pts-btn" data-close-timesheet-modal>Cancel</button>
                <button type="submit" class="pts-btn pts-btn-primary" @disabled($assignedProjects->isEmpty())>Save Timesheet</button>
            </div>
        </form>
    </div>
</div>

<!-- Review Poster Deliverable Modal Popup -->
<div class="pts-modal-overlay" data-review-poster-modal-overlay></div>
<div class="pts-modal" data-review-poster-modal style="width:min(680px, calc(100vw - 32px));">
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:#fff7ed; border:1px solid #fed7aa; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            </div>
            <div>
                <div class="pts-card-title" style="font-size:17px; font-weight:900; color:#111827;">Review Poster Deliverables</div>
                <div class="pts-card-sub" id="reviewModalSubTitle">Verify designer poster submissions</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-review-poster-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body">
        <form id="reviewPosterForm" method="POST" action="">
            @csrf
            <!-- Deliverable Preview Row -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:18px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:10px;">
                    <div>
                        <div style="font-weight:900; color:#0f172a; font-size:15px;" id="reviewClientProjectTitle">—</div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;" id="reviewDesignerInfo">—</div>
                    </div>
                    <span class="pts-badge count" id="reviewPosterCountBadge" style="font-size:12px; padding:4px 10px; background:#fff7ed; color:#ea580c; border-color:#fed7aa;">
                        1 Poster
                    </span>
                </div>
                <!-- Posters preview list -->
                <div id="reviewPostersThumbnails" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:8px;"></div>
            </div>

            <!-- Decision Selection (Approve / Reject) -->
            <div style="margin-bottom:18px;">
                <label class="pts-label">Verification Decision <span style="color:#ef4444;">*</span></label>
                <div class="review-decision-group">
                    <label class="review-decision-label is-approve" id="labelApprove">
                        <input type="radio" name="status" value="approved" required>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:24px; height:24px; border-radius:50%; background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:900;">✓</div>
                            <span style="font-weight:900; color:#15803d; font-size:14px;">Approve Deliverable</span>
                        </div>
                        <span style="font-size:11.5px; color:#166534; line-height:1.4;">
                            Auto-syncs poster count to <strong>SMM Sheet Done Count</strong> for this client.
                        </span>
                    </label>

                    <label class="review-decision-label is-reject" id="labelReject">
                        <input type="radio" name="status" value="rejected" required>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:24px; height:24px; border-radius:50%; background:#fee2e2; color:#b91c1c; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:900;">✕</div>
                            <span style="font-weight:900; color:#b91c1c; font-size:14px;">Reject Deliverable</span>
                        </div>
                        <span style="font-size:11.5px; color:#991b1b; line-height:1.4;">
                            Count will <strong>NOT</strong> be added to SMM Sheet. Remarks sent to designer.
                        </span>
                    </label>
                </div>
            </div>

            <!-- Mandatory Remarks -->
            <div style="margin-bottom:18px;">
                <label class="pts-label">TL Review Remarks <span style="color:#ef4444;">* (Mandatory for both Approve and Reject)</span></label>
                <textarea name="remarks" id="reviewRemarksTextarea" class="pts-textarea" rows="4" style="min-height:95px;" required minlength="3" placeholder="Provide verification feedback, quality checks, or revision instructions..."></textarea>
                <div class="pts-help">Remarks are mandatory to ensure an audit trail and feedback for the designer. Minimum 3 characters.</div>
            </div>

            <!-- Confirmation Notice -->
            <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; padding:12px; margin-bottom:18px; display:flex; align-items:center; gap:10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <div style="font-size:12px; color:#9a3412; font-weight:700;">
                    Please confirm your review decision. Once approved, the poster count will immediately update the account's SMM Sheet Done Count.
                </div>
            </div>

            <div class="pts-actions" style="justify-content:flex-end;">
                <button type="button" class="pts-btn" data-close-review-poster-modal>Cancel</button>
                <button type="submit" id="submitReviewBtn" class="pts-btn pts-btn-primary" style="min-width:140px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Submit Review</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Upload DM Post Proof Modal Popup -->
<div class="pts-modal-overlay" data-dm-proof-modal-overlay></div>
<div class="pts-modal" data-dm-proof-modal style="width:min(640px, calc(100vw - 32px));">
    <div class="pts-modal-head">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:40px; height:40px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
            </div>
            <div>
                <div class="pts-card-title" style="font-size:17px; font-weight:900; color:#111827;">Upload Facebook Post Proof</div>
                <div class="pts-card-sub" id="dmProofModalSubTitle">Digital Marketing Publishing Proof</div>
            </div>
        </div>
        <button type="button" class="pts-modal-close" data-close-dm-proof-modal aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="pts-modal-body">
        <form id="dmProofForm" method="POST" action="" enctype="multipart/form-data">
            @csrf
            <!-- Deliverable Info Header -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:16px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                    <div>
                        <div style="font-weight:900; color:#0f172a; font-size:14.5px;" id="dmProofClientTitle">—</div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;" id="dmProofProjectTitle">—</div>
                    </div>
                    <div style="text-align:right;">
                        <span class="pts-badge completed" id="dmProofApprovedBadge" style="font-size:11.5px; padding:3px 8px;">Approved: 0</span>
                        <div style="font-size:11px; color:#64748b; margin-top:4px;" id="dmProofSmmStatus">SMM Design: 0 | Published: 0</div>
                    </div>
                </div>
            </div>

            <!-- Existing Proofs if any -->
            <div id="dmExistingProofsContainer" style="display:none; margin-bottom:18px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px;">
                <div style="font-size:12px; font-weight:800; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Already Uploaded Proofs</span>
                </div>
                <div id="dmExistingProofsList" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:10px;"></div>
            </div>

            <div style="display:grid; gap:16px;">
                <!-- Published Poster Count with Design Approval Cap -->
                <div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                        <label class="pts-label" style="margin-bottom:0;">Published Posters Count <span style="color:#ef4444;">*</span></label>
                        <span id="dmProofMaxAllowedBadge" class="pts-pill" style="font-size:11px; padding:3px 8px; background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe;">
                            Max Allowed: 0
                        </span>
                    </div>
                    <input type="number" name="poster_count" id="dmProofPosterCountInput" class="pts-input" min="1" step="1" required placeholder="Number of posters published">
                    <div class="pts-help" id="dmProofCountHelp" style="color:#ea580c; font-weight:700;">
                        ⚠️ Note: DM published count cannot exceed Design team's completed/approved count (<span id="dmProofMaxCountText">0</span>).
                    </div>
                </div>

                <!-- Screenshot file upload -->
                <div>
                    <label class="pts-label">Facebook / Social Media Screenshot Proof <span style="color:#ef4444;">*</span></label>
                    <div id="dmProofDropzone" class="task-file-dropzone" style="background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:10px; padding:16px; text-align:center; cursor:pointer;">
                        <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                            <div style="width:40px; height:40px; border-radius:10px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            </div>
                            <div style="font-size:13px; font-weight:800; color:#1e293b;">Click to upload screenshot proof</div>
                            <div style="font-size:11px; color:#64748b;">PNG, JPG, WEBP (Max 25MB)</div>
                        </div>
                        <input type="file" name="proof_image" id="dmProofFileInput" accept="image/*" style="display:none;" required>
                    </div>
                    <div id="dmProofFilePreview" style="margin-top:8px;"></div>
                </div>

                <!-- Platform & Post URL -->
                <div style="display:grid; grid-template-columns:1fr 2fr; gap:12px;">
                    <div>
                        <label class="pts-label">Platform</label>
                        <select name="platform" class="pts-select">
                            <option value="Facebook" selected>Facebook</option>
                            <option value="Instagram">Instagram</option>
                            <option value="LinkedIn">LinkedIn</option>
                            <option value="Twitter/X">Twitter / X</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="pts-label">Post URL / Link</label>
                        <input type="url" name="post_url" class="pts-input" placeholder="https://www.facebook.com/...">
                    </div>
                </div>

                <!-- Post Date -->
                <div>
                    <label class="pts-label">Posted Date</label>
                    <input type="date" name="posted_date" value="{{ $today }}" class="pts-input" max="{{ $today }}">
                </div>

                <!-- Remarks -->
                <div>
                    <label class="pts-label">Remarks / Caption Notes</label>
                    <textarea name="remarks" class="pts-textarea" rows="2" style="min-height:75px;" placeholder="Optional notes about the post..."></textarea>
                </div>
            </div>

            <div class="pts-actions" style="margin-top:20px; justify-content:flex-end;">
                <button type="button" class="pts-btn" data-close-dm-proof-modal>Cancel</button>
                <button type="submit" id="submitDmProofBtn" class="pts-btn pts-btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>Upload Proof</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Fullscreen Poster Lightbox Modal -->
<div class="pts-modal-overlay" data-poster-lightbox-overlay style="z-index:1300; background:rgba(15,23,42,0.88); backdrop-filter:blur(6px);"></div>
<div class="pts-modal" data-poster-lightbox-modal style="z-index:1310; width:min(900px, calc(100vw - 32px)); background:transparent; border:none; box-shadow:none; text-align:center; padding:0;">
    <div style="position:relative; display:inline-block; max-width:100%;">
        <div style="position:absolute; top:-44px; right:0; display:flex; gap:10px; z-index:10;">
            <a id="lightboxDownloadBtn" href="" download class="pts-btn" style="background:#ffffff; color:#111827; padding:6px 12px; font-size:12px; text-decoration:none;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <span>Download</span>
            </a>
            <button type="button" class="pts-btn" data-close-poster-lightbox style="background:#ffffff; color:#111827; padding:6px 12px; font-size:12px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <span>Close</span>
            </button>
        </div>
        <img id="lightboxImage" src="" alt="Poster" style="max-width:100%; max-height:82vh; border-radius:14px; box-shadow:0 24px 60px rgba(0,0,0,0.5); object-fit:contain; background:#111827;">
        <div id="lightboxCaption" style="margin-top:10px; font-size:13px; font-weight:800; color:#ffffff; text-shadow:0 1px 3px rgba(0,0,0,0.8);"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-timesheet-modal]');
    const overlay = document.querySelector('[data-timesheet-modal-overlay]');
    const openButtons = document.querySelectorAll('[data-open-timesheet-modal]');
    const closeButtons = document.querySelectorAll('[data-close-timesheet-modal]');
    const leadSelect = document.getElementById('leadSelect');
    const projectSelect = document.getElementById('projectSelect');
    const filterProjectSelect = document.querySelector('.pts-filter-project-select');
    const deliveryDateInput = document.querySelector('[data-project-delivery-date]');
    const assignedProjects = @json($assignedProjects);
    const selectedProjectId = @json($selectedProjectId);

    function setModalState(isOpen) {
        if (!modal || !overlay) {
            return;
        }

        modal.classList.toggle('is-open', isOpen);
        overlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    function syncDeliveryDate() {
        if (!projectSelect || !deliveryDateInput) {
            return;
        }

        const selectedOption = projectSelect.options[projectSelect.selectedIndex];
        deliveryDateInput.value = selectedOption ? (selectedOption.dataset.deliveryDate || '') : '';
    }

    function toggleDesignDmInputs() {
        if (!projectSelect) return;
        const selectedProjId = projectSelect.value;
        const selectedProject = assignedProjects.find(p => p.id == selectedProjId);

        if (selectedProject) {
            const deptName = selectedProject.department ? selectedProject.department.name.toLowerCase() : '';
            const isDesignOrDm = deptName.includes('design') || deptName.includes('dm') || deptName.includes('digital marketing');

            // Show or hide project type container
            const typeContainer = document.getElementById('projectTypeContainer');
            if (typeContainer) {
                typeContainer.style.display = isDesignOrDm ? '' : 'none';
            }

            // Determine if counts should be shown
            const typeSelect = document.getElementById('projectTypeSelect');
            const isRecurring = typeSelect ? (typeSelect.value === 'recurring') : true;

            // Show counts fields ONLY if it's Design/DM AND Recurring type
            const showCounts = isDesignOrDm && isRecurring;

            document.querySelectorAll('.project-counts-fields').forEach(el => {
                el.style.display = showCounts ? '' : 'none';
            });

            // Day Closing Update is always shown and required regardless of project type (Recurring / Onetime)
            const dayClosingContainer = document.getElementById('dayClosingUpdateContainer');
            const dayClosingTextarea = document.getElementById('dayClosingUpdateTextarea');
            if (dayClosingContainer && dayClosingTextarea) {
                dayClosingContainer.style.display = '';
                dayClosingTextarea.setAttribute('required', 'required');
            }

            const tsMandBadge = document.getElementById('timesheetAttachmentMandatoryBadge');
            const tsOptBadge = document.getElementById('timesheetAttachmentOptionalBadge');
            if (tsMandBadge && tsOptBadge) {
                const isMand = @json($isDesignUser) || @json($isDmUser) || isDesignOrDm;
                tsMandBadge.style.display = isMand ? '' : 'none';
                tsOptBadge.style.display = isMand ? 'none' : '';
            }
        } else {
            // Hide both type selector and counts if no project is selected
            const typeContainer = document.getElementById('projectTypeContainer');
            if (typeContainer) {
                typeContainer.style.display = 'none';
            }
            document.querySelectorAll('.project-counts-fields').forEach(el => {
                el.style.display = 'none';
            });
            const dayClosingContainer = document.getElementById('dayClosingUpdateContainer');
            const dayClosingTextarea = document.getElementById('dayClosingUpdateTextarea');
            if (dayClosingContainer && dayClosingTextarea) {
                dayClosingContainer.style.display = '';
                dayClosingTextarea.setAttribute('required', 'required');
            }

            const tsMandBadge = document.getElementById('timesheetAttachmentMandatoryBadge');
            const tsOptBadge = document.getElementById('timesheetAttachmentOptionalBadge');
            if (tsMandBadge && tsOptBadge) {
                const isMand = @json($isDesignUser) || @json($isDmUser);
                tsMandBadge.style.display = isMand ? '' : 'none';
                tsOptBadge.style.display = isMand ? 'none' : '';
            }
        }
    }

    function fetchTimesheetData() {
        if (!projectSelect) return;
        const projectId = projectSelect.value;
        const dateInput = document.getElementById('timesheetDateInput');
        const dateVal = dateInput ? dateInput.value : '';

        if (!projectId || !dateVal) {
            return;
        }

        fetch(`/projects/timesheets/get-data?production_initiation_id=${projectId}&timesheet_date=${dateVal}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const statusSelect = document.querySelector('select[name="status"]');
                    const typeSelect = document.getElementById('projectTypeSelect');
                    const posterCountInput = document.querySelector('input[name="poster_count"]');
                    const videoCountInput = document.querySelector('input[name="video_count"]');
                    const committedPostersInput = document.querySelector('input[name="committed_posters"]');
                    const committedVideosInput = document.querySelector('input[name="committed_videos"]');
                    const waitingPostersInput = document.querySelector('input[name="waiting_posters"]');
                    const waitingVideosInput = document.querySelector('input[name="waiting_videos"]');
                    const dayClosingTextarea = document.getElementById('dayClosingUpdateTextarea');

                    const designCompleted = (res.data && res.data.design_completed_posters !== undefined) ? res.data.design_completed_posters : 0;
                    if (posterCountInput) {
                        posterCountInput.setAttribute('data-design-completed', designCompleted);
                    }
                    const capBadge = document.getElementById('timesheetDmDesignCapBadge');
                    const capNote = document.getElementById('timesheetDmDesignCapNote');
                    const capVal = document.getElementById('timesheetDmDesignCapVal');
                    if (capBadge && capNote && capVal) {
                        capBadge.textContent = 'Design Done: ' + designCompleted;
                        capBadge.style.display = 'inline-flex';
                        capVal.textContent = designCompleted;
                        capNote.style.display = 'block';
                    }

                    const timesheetExistingContainer = document.getElementById('timesheetExistingAttachmentsContainer');
                    const timesheetExistingList = document.getElementById('timesheetExistingAttachmentsList');
                    const timesheetForm = document.querySelector('[data-timesheet-modal] form');

                    if (timesheetForm) {
                        timesheetForm.querySelectorAll('input[name="removed_attachments[]"]').forEach(inp => inp.remove());
                    }

                    if (res.exists && res.data) {
                        const d = res.data;
                        if (statusSelect) statusSelect.value = d.status || 'pending';
                        if (typeSelect) typeSelect.value = d.project_type || 'recurring';
                        if (posterCountInput) posterCountInput.value = d.poster_count;
                        if (videoCountInput) videoCountInput.value = d.video_count;
                        if (committedPostersInput) committedPostersInput.value = d.committed_posters;
                        if (committedVideosInput) committedVideosInput.value = d.committed_videos;
                        if (waitingPostersInput) waitingPostersInput.value = d.waiting_posters;
                        if (waitingVideosInput) waitingVideosInput.value = d.waiting_videos;
                        if (dayClosingTextarea) dayClosingTextarea.value = d.day_closing_update;

                        if (timesheetExistingContainer && timesheetExistingList) {
                            timesheetExistingList.innerHTML = '';
                            const atts = Array.isArray(d.attachments) ? d.attachments : [];
                            if (atts.length > 0) {
                                timesheetExistingContainer.style.display = 'block';
                                atts.forEach(att => {
                                    const item = document.createElement('div');
                                    item.className = 'task-file-item';
                                    item.innerHTML = `
                                        <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                            <span style="font-size:16px;">${getFileIcon(att.name, att.mime_type)}</span>
                                            <div style="min-width:0;">
                                                <a href="${escapeHtml(att.url)}" target="_blank" style="font-weight:700; color:#0f172a; text-decoration:none; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px;" title="${escapeHtml(att.name)}">
                                                    ${escapeHtml(att.name)}
                                                </a>
                                                <div style="font-size:11px; color:#64748b;">${escapeHtml(att.formatted_size || formatBytes(att.size))}</div>
                                            </div>
                                        </div>
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <a href="${escapeHtml(att.url)}" target="_blank" download class="pts-btn" style="padding:3px 8px; font-size:11px; background:#f8fafc; border-color:#cbd5e1; text-decoration:none;" title="Download file">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                            </a>
                                            <button type="button" class="pts-btn remove-timesheet-att-btn" style="padding:3px 8px; font-size:11px; color:#ef4444; border-color:#fecaca; background:#fff;" title="Remove this attachment">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </div>
                                    `;
                                    item.querySelector('.remove-timesheet-att-btn').addEventListener('click', function(ev) {
                                        ev.preventDefault();
                                        if (timesheetForm && att.path) {
                                            const hiddenInput = document.createElement('input');
                                            hiddenInput.type = 'hidden';
                                            hiddenInput.name = 'removed_attachments[]';
                                            hiddenInput.value = att.path;
                                            timesheetForm.appendChild(hiddenInput);
                                        }
                                        item.remove();
                                        if (timesheetExistingList.children.length === 0) {
                                            timesheetExistingContainer.style.display = 'none';
                                        }
                                    });
                                    timesheetExistingList.appendChild(item);
                                });
                            } else {
                                timesheetExistingContainer.style.display = 'none';
                            }
                        }
                    } else {
                        if (statusSelect) statusSelect.value = 'pending';
                        if (typeSelect) typeSelect.value = 'recurring';
                        if (posterCountInput) posterCountInput.value = 0;
                        if (videoCountInput) videoCountInput.value = 0;
                        if (committedPostersInput) committedPostersInput.value = 0;
                        if (committedVideosInput) committedVideosInput.value = 0;
                        if (waitingPostersInput) waitingPostersInput.value = 0;
                        if (waitingVideosInput) waitingVideosInput.value = 0;
                        if (dayClosingTextarea) dayClosingTextarea.value = '';
                        if (timesheetExistingContainer && timesheetExistingList) {
                            timesheetExistingList.innerHTML = '';
                            timesheetExistingContainer.style.display = 'none';
                        }
                    }
                    toggleDesignDmInputs();
                }
            })
            .catch(err => console.error('Error fetching timesheet data:', err));
    }

    function handleLeadChange() {
        if (!leadSelect || !projectSelect) return;

        const leadId = leadSelect.value;

        // Clear previous options
        projectSelect.innerHTML = '<option value="">Select project</option>';

        const existingContainer = document.getElementById('timesheetExistingAttachmentsContainer');
        const existingList = document.getElementById('timesheetExistingAttachmentsList');
        if (existingContainer && existingList) {
            existingList.innerHTML = '';
            existingContainer.style.display = 'none';
        }

        if (!leadId) {
            projectSelect.disabled = true;
            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(projectSelect).val('').trigger('change.select2');
                window.jQuery(projectSelect).prop('disabled', true);
            }
            syncDeliveryDate();
            toggleDesignDmInputs();
            return;
        }

        // Filter projects
        const filtered = assignedProjects.filter(p => p.lead_id == leadId);

        // Populate options
        filtered.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.product_name + ' (Delivery: ' + (p.timesheet_delivery_date || 'N/A') + ')';
            opt.dataset.deliveryDate = p.timesheet_delivery_date || '';
            projectSelect.appendChild(opt);
        });

        // Enable select
        projectSelect.disabled = false;
        if (window.jQuery && window.jQuery.fn.select2) {
            window.jQuery(projectSelect).prop('disabled', false);
            window.jQuery(projectSelect).trigger('change.select2');
        }

        syncDeliveryDate();
        toggleDesignDmInputs();
        fetchTimesheetData();
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setModalState(true);
            const tsFileInput = document.getElementById('timesheetAttachmentsInput');
            const tsPreviewList = document.getElementById('timesheetSelectedFilesList');
            if (tsFileInput) tsFileInput.value = '';
            if (tsPreviewList) tsPreviewList.innerHTML = '';
            window.setTimeout(syncDeliveryDate, 0);
        });
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setModalState(false);
        });
    });

    if (overlay) {
        overlay.addEventListener('click', function () {
            setModalState(false);
        });
    }

    if (leadSelect) {
        leadSelect.addEventListener('change', handleLeadChange);
    }

    if (projectSelect) {
        projectSelect.addEventListener('change', function() {
            syncDeliveryDate();
            toggleDesignDmInputs();
            fetchTimesheetData();
        });
    }

    const dateInput = document.getElementById('timesheetDateInput');
    if (dateInput) {
        dateInput.addEventListener('change', fetchTimesheetData);
    }

    const typeSelect = document.getElementById('projectTypeSelect');
    if (typeSelect) {
        typeSelect.addEventListener('change', toggleDesignDmInputs);
    }

    // Initialize Select2
    if (window.jQuery && window.jQuery.fn.select2) {
        const $lead = window.jQuery(leadSelect);
        const $project = window.jQuery(projectSelect);
        const $filterProject = window.jQuery(filterProjectSelect);

        if ($lead.length) {
            if ($lead.hasClass('select2-hidden-accessible')) {
                $lead.select2('destroy');
            }
            $lead.select2({
                width: '100%',
                placeholder: 'Select Lead',
                dropdownParent: window.jQuery(modal),
            });
            $lead.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
            $lead.on('change select2:select', handleLeadChange);
        }

        if ($project.length) {
            if ($project.hasClass('select2-hidden-accessible')) {
                $project.select2('destroy');
            }
            $project.select2({
                width: '100%',
                placeholder: 'Select project',
                dropdownParent: window.jQuery(modal),
            });
            $project.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
            $project.on('change select2:select', function() {
                syncDeliveryDate();
                toggleDesignDmInputs();
                fetchTimesheetData();
            });
        }

        if ($filterProject.length) {
            if ($filterProject.hasClass('select2-hidden-accessible')) {
                $filterProject.select2('destroy');
            }
            $filterProject.select2({
                width: '100%',
                placeholder: 'All projects',
                allowClear: true,
            });
            $filterProject.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        }

        const filterLeadSelect = document.querySelector('.pts-filter-lead-select');
        if (filterLeadSelect) {
            const $filterLead = window.jQuery(filterLeadSelect);
            if ($filterLead.hasClass('select2-hidden-accessible')) {
                $filterLead.select2('destroy');
            }
            $filterLead.select2({
                width: '100%',
                placeholder: 'All Leads',
                allowClear: true,
            });
            $filterLead.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        }

        const filterUserSelect = document.querySelector('select[name="filter_user_id"]');
        if (filterUserSelect) {
            const $filterUser = window.jQuery(filterUserSelect);
            if ($filterUser.hasClass('select2-hidden-accessible')) {
                $filterUser.select2('destroy');
            }
            $filterUser.select2({
                width: '100%',
                placeholder: 'All Employees',
                allowClear: true,
            });
            $filterUser.next('.select2-container').find('.select2-selection--single').addClass('pts-select2-selection');
        }
    }

    // Auto-restore old values on validation failure
    if (selectedProjectId) {
        const proj = assignedProjects.find(p => p.id == selectedProjectId);
        if (proj && leadSelect) {
            leadSelect.value = proj.lead_id;
            if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery(leadSelect).val(proj.lead_id).trigger('change.select2');
            }
            handleLeadChange();
            if (projectSelect) {
                projectSelect.value = selectedProjectId;
                if (window.jQuery && window.jQuery.fn.select2) {
                    window.jQuery(projectSelect).val(selectedProjectId).trigger('change.select2');
                }
            }
        }
    }

    window.setTimeout(syncDeliveryDate, 0);

    // View Timesheets Modal Logic
    const viewTimesheetsModal = document.querySelector('[data-view-timesheets-modal]');
    const viewTimesheetsOverlay = document.querySelector('[data-view-timesheets-modal-overlay]');
    const openTimesheetButtons = document.querySelectorAll('.open-timesheets-modal-btn');
    const closeViewTimesheetButtons = document.querySelectorAll('[data-close-view-timesheets-modal]');
    const modalMemberName = document.getElementById('modalTimesheetMemberName');
    const modalSubTitle = document.getElementById('modalTimesheetSubTitle');
    const modalTableBody = document.getElementById('modalTimesheetsTableBody');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    function setViewTimesheetsModalState(isOpen) {
        if (!viewTimesheetsModal || !viewTimesheetsOverlay) return;
        viewTimesheetsModal.classList.toggle('is-open', isOpen);
        viewTimesheetsOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
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

    let currentGroupBtn = null;
    let currentGroupRow = null;
    let currentGroupTimesheets = [];

    function openGroupTimesheetsModal(btn) {
        currentGroupBtn = btn;
        currentGroupRow = btn.closest('tr');
        const userName = btn.getAttribute('data-user-name') || 'Team Member';
        const dateStr = btn.getAttribute('data-timesheet-date') || 'N/A';
        currentGroupTimesheets = [];

        try {
            currentGroupTimesheets = JSON.parse(btn.getAttribute('data-timesheets') || '[]');
        } catch (e) {
            console.error('Failed to parse timesheets JSON', e);
        }

        if (modalMemberName) modalMemberName.textContent = 'Timesheets - ' + userName;
        if (modalSubTitle) modalSubTitle.textContent = 'Date: ' + dateStr + ' | Total ' + currentGroupTimesheets.length + ' Timesheet(s)';

        if (modalTableBody) {
            modalTableBody.innerHTML = '';

            if (currentGroupTimesheets.length === 0) {
                modalTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No timesheets found for this date.</td></tr>';
            } else {
                currentGroupTimesheets.forEach(ts => {
                    const tr = document.createElement('tr');
                    tr.setAttribute('data-timesheet-id', ts.id);
                    const statusClass = (ts.status === 'completed') ? 'completed' : ((ts.status === 'ongoing') ? 'ongoing' : 'pending');

                    let closingActionHtml = '';
                    const hasClosingUpdate = (ts.day_closing_update && ts.day_closing_update.trim() !== '');
                    const attsJson = escapeHtml(JSON.stringify(ts.attachments || []));
                    const hasAttachments = (ts.attachments && ts.attachments.length > 0);
                    const attBadgeHtml = hasAttachments ? `
                        <span class="pts-badge count" style="padding:2px 7px; font-size:11px; display:inline-flex; align-items:center; gap:4px; background:#fff7ed; color:#ea580c; border:1px solid #fed7aa;" title="${ts.attachments.length} file attachment(s)">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                            <span>${ts.attachments.length}</span>
                        </span>
                    ` : '';

                    if (hasClosingUpdate) {
                        closingActionHtml = `
                            <div class="closing-btn-group" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <button type="button" class="pts-btn pts-btn-outline open-view-closing-btn"
                                        data-project-name="${escapeHtml(ts.product_name)}"
                                        data-lead-name="${escapeHtml(ts.lead_company)}"
                                        data-delivery-date="${escapeHtml(ts.delivery_date)}"
                                        data-closing-text="${escapeHtml(ts.day_closing_update)}"
                                        data-attachments="${attsJson}"
                                        data-timesheet-id="${ts.id}"
                                        data-update-url="${ts.update_status_url}"
                                        style="padding:5px 10px; font-size:12px; gap:5px;" title="View closing update details">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    <span>View</span>
                                </button>
                                <button type="button" class="pts-btn open-entry-closing-btn"
                                        data-project-name="${escapeHtml(ts.product_name)}"
                                        data-lead-name="${escapeHtml(ts.lead_company)}"
                                        data-delivery-date="${escapeHtml(ts.delivery_date)}"
                                        data-closing-text="${escapeHtml(ts.day_closing_update)}"
                                        data-attachments="${attsJson}"
                                        data-timesheet-id="${ts.id}"
                                        data-update-url="${ts.update_status_url}"
                                        style="padding:5px 9px; font-size:12px; gap:5px; background:#f8fafc; border-color:#cbd5e1;" title="Edit closing update">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    <span>Edit</span>
                                </button>
                                ${attBadgeHtml}
                            </div>
                        `;
                    } else {
                        closingActionHtml = `
                            <div class="closing-btn-group" style="display:flex; align-items:center; gap:6px;">
                                <button type="button" class="pts-btn pts-btn-primary open-entry-closing-btn"
                                        data-project-name="${escapeHtml(ts.product_name)}"
                                        data-lead-name="${escapeHtml(ts.lead_company)}"
                                        data-delivery-date="${escapeHtml(ts.delivery_date)}"
                                        data-closing-text=""
                                        data-attachments="${attsJson}"
                                        data-timesheet-id="${ts.id}"
                                        data-update-url="${ts.update_status_url}"
                                        style="padding:6px 12px; font-size:12px; gap:6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    <span>Add Update</span>
                                </button>
                            </div>
                        `;
                    }

                    let posterDeliverableBadge = '';
                    const hasPosterAtts = (ts.poster_attachments && ts.poster_attachments.length > 0) || (ts.attachments && ts.attachments.length > 0 && ts.is_design_dm);
                    if (ts.is_design_dm || hasPosterAtts || ts.poster_approval_status) {
                        const pStatus = ts.poster_approval_status || (hasPosterAtts ? 'pending' : '');
                        if (pStatus === 'approved') {
                            posterDeliverableBadge = `<div style="margin-top:5px;"><span class="pts-badge completed" style="font-size:10px; padding:2px 7px;">✓ Poster Approved</span></div>`;
                        } else if (pStatus === 'rejected') {
                            posterDeliverableBadge = `<div style="margin-top:5px;"><span class="pts-badge" style="font-size:10px; padding:2px 7px; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;">✕ Poster Rejected</span></div>`;
                        } else if (pStatus === 'pending') {
                            posterDeliverableBadge = `<div style="margin-top:5px;"><span class="pts-badge pending" style="font-size:10px; padding:2px 7px;">⏳ Awaiting TL Review</span></div>`;
                        }
                    }

                    let tlReviewBtnHtml = '';
                    if (@json($canApprovePosters) && (hasPosterAtts || ts.poster_approval_status)) {
                        const postersJson = escapeHtml(JSON.stringify(ts.poster_attachments && ts.poster_attachments.length ? ts.poster_attachments : (ts.attachments || [])));
                        tlReviewBtnHtml = `
                            <div style="margin-top:5px;">
                                <button type="button" class="pts-btn pts-btn-primary open-review-from-table-btn"
                                        data-timesheet-id="${ts.id}"
                                        data-designer-name="${escapeHtml(ts.user_name || '')}"
                                        data-project-name="${escapeHtml(ts.product_name)}"
                                        data-client-name="${escapeHtml(ts.lead_company)}"
                                        data-poster-count="${ts.poster_count || (ts.poster_attachments ? ts.poster_attachments.length : 1)}"
                                        data-status="${ts.poster_approval_status || 'pending'}"
                                        data-remarks="${escapeHtml(ts.poster_approval_remarks || '')}"
                                        data-posters="${postersJson}"
                                        data-review-url="${ts.review_poster_url || ('/projects/timesheets/' + ts.id + '/review-poster')}"
                                        style="padding:3px 8px; font-size:11px; gap:4px;" title="Review poster deliverable">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                                    <span>Review</span>
                                </button>
                            </div>
                        `;
                    }

                    const isStatusDisabled = !hasClosingUpdate;
                    const statusDisabledTooltip = isStatusDisabled ? 'Add Day Closing Update first to change status' : 'Change status';

                    tr.innerHTML = `
                        <td class="td-center" style="font-weight:800; color:#64748b;">${ts.sno}</td>
                        <td>
                            <div style="font-weight:800; color:#0f172a;">${escapeHtml(ts.lead_company)}</div>
                            <div class="pts-meta" style="font-weight:700; color:#ea580c;">${escapeHtml(ts.lead_id)}</div>
                        </td>
                        <td>
                            <div class="pts-project">${escapeHtml(ts.product_name)}</div>
                            <div class="pts-meta">Delivery: ${escapeHtml(ts.delivery_date)}</div>
                        </td>
                        <td>
                            <div style="font-size:12px; color:#334155; line-height:1.55; white-space:pre-wrap; background:#f8fafc; padding:8px 10px; border-radius:8px; border:1px solid #e2e8f0; max-height:140px; overflow-y:auto;">${escapeHtml(ts.assigned_task || '—')}</div>
                        </td>
                        <td>
                            ${closingActionHtml}
                            ${tlReviewBtnHtml}
                            ${posterDeliverableBadge}
                        </td>
                        <td class="td-center">
                            <select class="pts-status-select ${statusClass} modal-timesheet-status-select"
                                    data-timesheet-id="${ts.id}"
                                    data-update-url="${ts.update_status_url}"
                                    ${isStatusDisabled ? 'disabled' : ''}
                                    title="${statusDisabledTooltip}">
                                <option value="pending" ${ts.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="ongoing" ${ts.status === 'ongoing' ? 'selected' : ''}>Ongoing</option>
                                <option value="completed" ${ts.status === 'completed' ? 'selected' : ''}>Completed</option>
                            </select>
                        </td>
                    `;
                    modalTableBody.appendChild(tr);
                });

                // Attach change listeners to status selects
                modalTableBody.querySelectorAll('.modal-timesheet-status-select').forEach(sel => {
                    sel.addEventListener('change', function() {
                        const newStatus = this.value;
                        const timesheetId = this.getAttribute('data-timesheet-id');
                        const updateUrl = this.getAttribute('data-update-url');
                        const currentSelect = this;

                        // Visual feedback
                        currentSelect.style.opacity = '0.6';

                        fetch(updateUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ status: newStatus })
                        })
                        .then(res => res.json())
                        .then(data => {
                            currentSelect.style.opacity = '1';
                            if (data.success) {
                                currentSelect.classList.remove('pending', 'ongoing', 'completed');
                                currentSelect.classList.add(newStatus);

                                // Update item in currentGroupTimesheets
                                const tsItem = currentGroupTimesheets.find(t => String(t.id) === String(timesheetId));
                                if (tsItem) {
                                    tsItem.status = newStatus;
                                }
                                if (currentGroupBtn) {
                                    currentGroupBtn.setAttribute('data-timesheets', JSON.stringify(currentGroupTimesheets));
                                }

                                // Update Status Overview in the main table row
                                if (currentGroupRow) {
                                    const pendingCount = currentGroupTimesheets.filter(t => t.status === 'pending').length;
                                    const ongoingCount = currentGroupTimesheets.filter(t => t.status === 'ongoing').length;
                                    const completedCount = currentGroupTimesheets.filter(t => t.status === 'completed').length;

                                    let badgesHtml = '';
                                    if (pendingCount > 0) {
                                        badgesHtml += `<span class="pts-badge pending">${pendingCount} Pending</span>`;
                                    }
                                    if (ongoingCount > 0) {
                                        badgesHtml += `<span class="pts-badge ongoing">${ongoingCount} Ongoing</span>`;
                                    }
                                    if (completedCount > 0) {
                                        badgesHtml += `<span class="pts-badge completed">${completedCount} Completed</span>`;
                                    }
                                    if (badgesHtml === '') {
                                        badgesHtml = '<span class="pts-meta">—</span>';
                                    }

                                    const overviewEl = currentGroupRow.querySelector('.status-overview-badges');
                                    if (overviewEl) {
                                        overviewEl.innerHTML = badgesHtml;
                                    }
                                }
                            } else {
                                alert(data.message || 'Failed to update status.');
                            }
                        })
                        .catch(err => {
                            currentSelect.style.opacity = '1';
                            console.error('Error updating timesheet status:', err);
                            alert('An error occurred while updating the status.');
                        });
                    });
                });
            }
        }

        setViewTimesheetsModalState(true);
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.open-timesheets-modal-btn');
        if (btn) {
            e.preventDefault();
            openGroupTimesheetsModal(btn);
        }
    });

    closeViewTimesheetButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            setViewTimesheetsModalState(false);
        });
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-close-view-timesheets-modal]')) {
            setViewTimesheetsModalState(false);
        }
    });

    if (viewTimesheetsOverlay) {
        viewTimesheetsOverlay.addEventListener('click', function() {
            setViewTimesheetsModalState(false);
        });
    }

    // Submodals Logic (Entry Modal and View Modal)
    const entryClosingModal = document.querySelector('[data-entry-closing-modal]');
    const entryClosingOverlay = document.querySelector('[data-entry-closing-modal-overlay]');
    const closeEntryClosingButtons = document.querySelectorAll('[data-close-entry-closing-modal]');
    const entryClosingTitle = document.getElementById('entryClosingModalTitle');
    const entryClosingSubTitle = document.getElementById('entryClosingModalSubTitle');
    const entryClosingTextarea = document.getElementById('entryClosingTextarea');
    const entryClosingTimesheetId = document.getElementById('entryClosingTimesheetId');
    const entryClosingUpdateUrl = document.getElementById('entryClosingUpdateUrl');
    const entryClosingForm = document.getElementById('entryClosingForm');
    const entryClosingSubmitBtn = document.getElementById('entryClosingSubmitBtn');

    const viewClosingModal = document.querySelector('[data-view-closing-modal]');
    const viewClosingOverlay = document.querySelector('[data-view-closing-modal-overlay]');
    const closeViewClosingButtons = document.querySelectorAll('[data-close-view-closing-modal]');
    const viewClosingTitle = document.getElementById('viewClosingModalTitle');
    const viewClosingSubTitle = document.getElementById('viewClosingModalSubTitle');
    const viewClosingContent = document.getElementById('viewClosingModalContent');
    const viewClosingEditShortcutBtn = document.getElementById('viewClosingEditShortcutBtn');

    let currentActiveRow = null;

    function setEntryClosingModalState(isOpen) {
        if (!entryClosingModal || !entryClosingOverlay) return;
        entryClosingModal.classList.toggle('is-open', isOpen);
        entryClosingOverlay.classList.toggle('is-open', isOpen);
    }

    function setViewClosingModalState(isOpen) {
        if (!viewClosingModal || !viewClosingOverlay) return;
        viewClosingModal.classList.toggle('is-open', isOpen);
        viewClosingOverlay.classList.toggle('is-open', isOpen);
    }

    function formatBytes(bytes) {
        bytes = Number(bytes) || 0;
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return bytes + ' B';
    }

    function getFileIcon(name, mime) {
        name = (name || '').toLowerCase();
        mime = (mime || '').toLowerCase();
        if (mime.startsWith('image/') || /\.(png|jpe?g|gif|webp|svg)$/i.test(name)) return '🖼️';
        if (mime.includes('pdf') || /\.pdf$/i.test(name)) return '📕';
        if (mime.includes('sheet') || mime.includes('excel') || mime.includes('csv') || /\.(xls|xlsx|csv)$/i.test(name)) return '📊';
        if (mime.includes('word') || /\.(doc|docx)$/i.test(name)) return '📝';
        if (mime.includes('zip') || mime.includes('tar') || mime.includes('compressed') || /\.(zip|rar|7z|gz)$/i.test(name)) return '🗜️';
        return '📄';
    }

    let removedAttachmentsList = [];

    // Delegate click for open-entry-closing-btn
    document.addEventListener('click', function(e) {
        const entryBtn = e.target.closest('.open-entry-closing-btn');
        if (entryBtn) {
            const projectName = entryBtn.getAttribute('data-project-name') || 'Project';
            const leadName = entryBtn.getAttribute('data-lead-name') || '';
            const closingText = entryBtn.getAttribute('data-closing-text') || '';
            const updateUrl = entryBtn.getAttribute('data-update-url') || '';
            const timesheetId = entryBtn.getAttribute('data-timesheet-id') || '';

            currentActiveRow = entryBtn.closest('tr');
            removedAttachmentsList = [];

            if (entryClosingTitle) entryClosingTitle.textContent = closingText ? 'Edit Day Closing Update' : 'Add Day Closing Update';
            if (entryClosingSubTitle) entryClosingSubTitle.textContent = projectName + (leadName ? ' | ' + leadName : '');
            if (entryClosingTextarea) entryClosingTextarea.value = closingText;
            if (entryClosingTimesheetId) entryClosingTimesheetId.value = timesheetId;
            if (entryClosingUpdateUrl) entryClosingUpdateUrl.value = updateUrl;

            // Reset new files input & preview
            const entryFileInput = document.getElementById('entryClosingAttachments');
            const entryPreviewList = document.getElementById('entrySelectedFilesList');
            if (entryFileInput) entryFileInput.value = '';
            if (entryPreviewList) entryPreviewList.innerHTML = '';

            // Render existing attachments if any
            let attachments = [];
            if (currentGroupTimesheets && timesheetId) {
                const tsItem = currentGroupTimesheets.find(t => String(t.id) === String(timesheetId));
                if (tsItem && Array.isArray(tsItem.attachments)) {
                    attachments = tsItem.attachments;
                }
            }
            if (!attachments || attachments.length === 0) {
                try {
                    attachments = JSON.parse(entryBtn.getAttribute('data-attachments') || '[]');
                } catch(err) {
                    attachments = [];
                }
            }

            const existingContainer = document.getElementById('entryExistingAttachmentsContainer');
            const existingList = document.getElementById('entryExistingAttachmentsList');
            if (existingContainer && existingList) {
                existingList.innerHTML = '';
                if (attachments && attachments.length > 0) {
                    existingContainer.style.display = 'block';
                    attachments.forEach(att => {
                        const item = document.createElement('div');
                        item.className = 'task-file-item';
                        item.innerHTML = `
                            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                <span style="font-size:16px;">${getFileIcon(att.name, att.mime_type)}</span>
                                <div style="min-width:0;">
                                    <a href="${escapeHtml(att.url)}" target="_blank" style="font-weight:700; color:#0f172a; text-decoration:none; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px;" title="${escapeHtml(att.name)}">
                                        ${escapeHtml(att.name)}
                                    </a>
                                    <div style="font-size:11px; color:#64748b;">${escapeHtml(att.formatted_size || formatBytes(att.size))}</div>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <a href="${escapeHtml(att.url)}" target="_blank" download class="pts-btn" style="padding:3px 8px; font-size:11px; background:#f8fafc; border-color:#cbd5e1; text-decoration:none;" title="Download file">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                </a>
                                <button type="button" class="pts-btn remove-existing-att-btn" style="padding:3px 8px; font-size:11px; color:#ef4444; border-color:#fecaca; background:#fff;" title="Remove this attachment">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        `;
                        item.querySelector('.remove-existing-att-btn').addEventListener('click', function(ev) {
                            ev.preventDefault();
                            if (att.path) {
                                removedAttachmentsList.push(att.path);
                            }
                            item.remove();
                            if (existingList.children.length === 0) {
                                existingContainer.style.display = 'none';
                            }
                        });
                        existingList.appendChild(item);
                    });
                } else {
                    existingContainer.style.display = 'none';
                }
            }

            const isTsDesignOrDm = (currentGroupTimesheets && timesheetId && currentGroupTimesheets.find(t => String(t.id) === String(timesheetId))?.is_design_dm) || @json($isDesignUser) || @json($isDmUser);
            const entryMandBadge = document.getElementById('entryAttachmentMandatoryBadge');
            const entryOptBadge = document.getElementById('entryAttachmentOptionalBadge');
            if (entryMandBadge && entryOptBadge) {
                entryMandBadge.style.display = isTsDesignOrDm ? '' : 'none';
                entryOptBadge.style.display = isTsDesignOrDm ? 'none' : '';
            }

            setEntryClosingModalState(true);
            setTimeout(() => { if (entryClosingTextarea) entryClosingTextarea.focus(); }, 100);
            return;
        }

        const viewBtn = e.target.closest('.open-view-closing-btn');
        if (viewBtn) {
            const projectName = viewBtn.getAttribute('data-project-name') || 'Project';
            const leadName = viewBtn.getAttribute('data-lead-name') || '';
            const closingText = viewBtn.getAttribute('data-closing-text') || 'No closing update recorded.';
            const timesheetId = viewBtn.getAttribute('data-timesheet-id') || '';

            if (viewClosingTitle) viewClosingTitle.textContent = 'Day Closing Update Details';
            if (viewClosingSubTitle) viewClosingSubTitle.textContent = projectName + (leadName ? ' | ' + leadName : '');
            if (viewClosingContent) viewClosingContent.textContent = closingText;

            // Render view modal attachments
            let attachments = [];
            if (currentGroupTimesheets && timesheetId) {
                const tsItem = currentGroupTimesheets.find(t => String(t.id) === String(timesheetId));
                if (tsItem && Array.isArray(tsItem.attachments)) {
                    attachments = tsItem.attachments;
                }
            }
            if (!attachments || attachments.length === 0) {
                try {
                    attachments = JSON.parse(viewBtn.getAttribute('data-attachments') || '[]');
                } catch(err) {
                    attachments = [];
                }
            }

            const viewAttsContainer = document.getElementById('viewClosingModalAttachmentsContainer');
            const viewAttsList = document.getElementById('viewClosingModalAttachmentsList');
            const viewAttsCount = document.getElementById('viewClosingModalAttachmentsCount');

            if (viewAttsContainer && viewAttsList) {
                viewAttsList.innerHTML = '';
                if (attachments && attachments.length > 0) {
                    if (viewAttsCount) viewAttsCount.textContent = attachments.length;
                    attachments.forEach(att => {
                        const item = document.createElement('div');
                        item.className = 'task-file-item';
                        item.innerHTML = `
                            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                <span style="font-size:16px;">${getFileIcon(att.name, att.mime_type)}</span>
                                <div style="min-width:0;">
                                    <div style="font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:300px;" title="${escapeHtml(att.name)}">
                                        ${escapeHtml(att.name)}
                                    </div>
                                    <div style="font-size:11px; color:#64748b;">${escapeHtml(att.formatted_size || formatBytes(att.size))}</div>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:6px;">
                                <a href="${escapeHtml(att.url)}" target="_blank" class="pts-btn pts-btn-primary" style="padding:4px 10px; font-size:11px; gap:4px; text-decoration:none;" title="View file">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    <span>View</span>
                                </a>
                                <a href="${escapeHtml(att.url)}" download="${escapeHtml(att.name)}" class="pts-btn pts-btn-outline" style="padding:4px 10px; font-size:11px; gap:4px; text-decoration:none;" title="Download file">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    <span>Download</span>
                                </a>
                            </div>
                        `;
                        viewAttsList.appendChild(item);
                    });
                    viewAttsContainer.style.display = 'block';
                } else {
                    viewAttsContainer.style.display = 'none';
                }
            }

            // Store attributes on shortcut edit button
            if (viewClosingEditShortcutBtn) {
                viewClosingEditShortcutBtn.onclick = function() {
                    setViewClosingModalState(false);
                    // Trigger entry edit button on the same row
                    const tr = viewBtn.closest('tr');
                    const editBtn = tr ? tr.querySelector('.open-entry-closing-btn') : null;
                    if (editBtn) {
                        editBtn.click();
                    }
                };
            }

            setViewClosingModalState(true);
            return;
        }
    });

    closeEntryClosingButtons.forEach(btn => {
        btn.addEventListener('click', () => setEntryClosingModalState(false));
    });
    if (entryClosingOverlay) {
        entryClosingOverlay.addEventListener('click', () => setEntryClosingModalState(false));
    }

    closeViewClosingButtons.forEach(btn => {
        btn.addEventListener('click', () => setViewClosingModalState(false));
    });
    if (viewClosingOverlay) {
        viewClosingOverlay.addEventListener('click', () => setViewClosingModalState(false));
    }

    if (entryClosingForm) {
        entryClosingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const updateUrl = entryClosingUpdateUrl ? entryClosingUpdateUrl.value : '';
            const newText = entryClosingTextarea ? entryClosingTextarea.value.trim() : '';

            if (!newText) {
                alert('Please enter day closing update details.');
                return;
            }

            const tsItem = (currentGroupTimesheets && entryClosingTimesheetId) ? currentGroupTimesheets.find(t => String(t.id) === String(entryClosingTimesheetId.value)) : null;
            const isTsDesignOrDm = (tsItem && tsItem.is_design_dm) || @json($isDesignUser) || @json($isDmUser);
            const entryFileInput = document.getElementById('entryClosingAttachments');
            const hasNewClosingFiles = entryFileInput && entryFileInput.files && entryFileInput.files.length > 0;
            const entryExistingList = document.getElementById('entryExistingAttachmentsList');
            const hasExistingClosingFiles = entryExistingList && entryExistingList.children.length > 0;

            if (isTsDesignOrDm && !hasNewClosingFiles && !hasExistingClosingFiles) {
                alert('Attachments are mandatory for Designing and Digital Marketing team members. Please attach your poster or deliverable files.');
                return;
            }

            if (entryClosingSubmitBtn) {
                entryClosingSubmitBtn.disabled = true;
                entryClosingSubmitBtn.innerHTML = '<span>Saving...</span>';
            }

            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('day_closing_update', newText);

            if (entryFileInput && entryFileInput.files) {
                for (let i = 0; i < entryFileInput.files.length; i++) {
                    formData.append('attachments[]', entryFileInput.files[i]);
                }
            }
            removedAttachmentsList.forEach(path => {
                formData.append('removed_attachments[]', path);
            });

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (entryClosingSubmitBtn) {
                    entryClosingSubmitBtn.disabled = false;
                    entryClosingSubmitBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Save Closing Update</span>';
                }

                if (data.success) {
                    setEntryClosingModalState(false);

                    const timesheetId = entryClosingTimesheetId ? entryClosingTimesheetId.value : '';
                    const updatedAttachments = data.attachments || [];

                    if (currentGroupTimesheets && timesheetId) {
                        const tsItem = currentGroupTimesheets.find(t => String(t.id) === String(timesheetId));
                        if (tsItem) {
                            tsItem.day_closing_update = newText;
                            tsItem.attachments = updatedAttachments;
                        }
                        if (currentGroupBtn) {
                            currentGroupBtn.setAttribute('data-timesheets', JSON.stringify(currentGroupTimesheets));
                        }
                    }

                    // Update active row
                    if (currentActiveRow) {
                        const cell = currentActiveRow.querySelector('td:nth-child(5)');
                        const projectName = currentActiveRow.querySelector('.pts-project')?.textContent || '';
                        const leadName = currentActiveRow.querySelector('td:nth-child(2) > div:first-child')?.textContent || '';
                        const attsJson = escapeHtml(JSON.stringify(updatedAttachments));
                        const hasAtts = updatedAttachments.length > 0;
                        const attBadge = hasAtts ? `
                            <span class="pts-badge count" style="padding:2px 7px; font-size:11px; display:inline-flex; align-items:center; gap:4px; background:#fff7ed; color:#ea580c; border:1px solid #fed7aa;" title="${updatedAttachments.length} file attachment(s)">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                <span>${updatedAttachments.length}</span>
                            </span>
                        ` : '';

                        if (cell) {
                            cell.innerHTML = `
                                <div class="closing-btn-group" style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <button type="button" class="pts-btn pts-btn-outline open-view-closing-btn"
                                            data-project-name="${escapeHtml(projectName)}"
                                            data-lead-name="${escapeHtml(leadName)}"
                                            data-closing-text="${escapeHtml(newText)}"
                                            data-attachments="${attsJson}"
                                            data-timesheet-id="${timesheetId}"
                                            data-update-url="${updateUrl}"
                                            style="padding:5px 10px; font-size:12px; gap:5px;" title="View closing update details">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        <span>View</span>
                                    </button>
                                    <button type="button" class="pts-btn open-entry-closing-btn"
                                            data-project-name="${escapeHtml(projectName)}"
                                            data-lead-name="${escapeHtml(leadName)}"
                                            data-closing-text="${escapeHtml(newText)}"
                                            data-attachments="${attsJson}"
                                            data-timesheet-id="${timesheetId}"
                                            data-update-url="${updateUrl}"
                                            style="padding:5px 9px; font-size:12px; gap:5px; background:#f8fafc; border-color:#cbd5e1;" title="Edit closing update">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        <span>Edit</span>
                                    </button>
                                    ${attBadge}
                                </div>
                            `;
                        }

                        // Enable status dropdown on the active row
                        const statusSelect = currentActiveRow.querySelector('.modal-timesheet-status-select');
                        if (statusSelect) {
                            if (newText.trim() !== '') {
                                statusSelect.disabled = false;
                                statusSelect.setAttribute('title', 'Change status');
                            } else {
                                statusSelect.disabled = true;
                                statusSelect.setAttribute('title', 'Add Day Closing Update first to change status');
                            }
                        }
                    }
                } else {
                    alert(data.message || 'Failed to save closing update.');
                }
            })
            .catch(err => {
                if (entryClosingSubmitBtn) {
                    entryClosingSubmitBtn.disabled = false;
                    entryClosingSubmitBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Save Closing Update</span>';
                }
                console.error('Error saving closing update:', err);
                alert('An error occurred while saving.');
            });
        });
    }

    // Initialize Dropzones
    function initFileDropzone(dropzoneEl, inputEl, previewListEl) {
        if (!dropzoneEl || !inputEl || !previewListEl) return;

        function updatePreview() {
            previewListEl.innerHTML = '';
            const files = Array.from(inputEl.files || []);
            files.forEach(file => {
                const item = document.createElement('div');
                item.className = 'task-file-item';
                item.innerHTML = `
                    <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                        <span style="font-size:16px;">${getFileIcon(file.name, file.type)}</span>
                        <div style="min-width:0;">
                            <div style="font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px;" title="${escapeHtml(file.name)}">
                                ${escapeHtml(file.name)}
                            </div>
                            <div style="font-size:11px; color:#64748b;">${formatBytes(file.size)}</div>
                        </div>
                    </div>
                    <span style="font-size:11px; font-weight:800; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px;">
                        Ready to upload
                    </span>
                `;
                previewListEl.appendChild(item);
            });

            if (files.length > 0) {
                const clearRow = document.createElement('div');
                clearRow.style.cssText = 'display:flex; justify-content:flex-end; margin-top:2px;';
                clearRow.innerHTML = `
                    <button type="button" class="pts-btn" style="padding:3px 10px; font-size:11px; color:#ef4444; border-color:#fecaca; background:#fff;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        <span>Clear Selected Files</span>
                    </button>
                `;
                clearRow.querySelector('button').addEventListener('click', function(e) {
                    e.stopPropagation();
                    inputEl.value = '';
                    previewListEl.innerHTML = '';
                });
                previewListEl.appendChild(clearRow);
            }
        }

        dropzoneEl.addEventListener('click', () => inputEl.click());
        inputEl.addEventListener('change', updatePreview);

        dropzoneEl.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneEl.classList.add('dragover');
        });
        dropzoneEl.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneEl.classList.remove('dragover');
        });
        dropzoneEl.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneEl.classList.remove('dragover');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                inputEl.files = e.dataTransfer.files;
                updatePreview();
            }
        });
    }

    initFileDropzone(
        document.getElementById('entryFileDropzone'),
        document.getElementById('entryClosingAttachments'),
        document.getElementById('entrySelectedFilesList')
    );

    initFileDropzone(
        document.getElementById('timesheetFileDropzone'),
        document.getElementById('timesheetAttachmentsInput'),
        document.getElementById('timesheetSelectedFilesList')
    );

    // ==========================================
    // Modern Navigation Tabs & Sub-filters
    // ==========================================
    const navTabs = document.querySelectorAll('[data-pts-tab]');
    const tabPanels = {
        'timesheets': document.getElementById('tabTimesheetsPanel'),
        'approvals': document.getElementById('tabApprovalsPanel'),
        'approved-posters': document.getElementById('tabApprovedPostersPanel'),
    };

    function switchTab(tabName) {
        if (!tabPanels[tabName]) return;
        navTabs.forEach(t => {
            t.classList.toggle('is-active', t.getAttribute('data-pts-tab') === tabName);
        });
        Object.keys(tabPanels).forEach(key => {
            const panel = tabPanels[key];
            if (panel) {
                const isActive = (key === tabName);
                panel.classList.toggle('is-active', isActive);
                panel.style.display = isActive ? (key === 'timesheets' ? 'grid' : 'block') : 'none';
            }
        });
    }

    document.addEventListener('click', function(e) {
        const tab = e.target.closest('[data-pts-tab]');
        if (tab) {
            e.preventDefault();
            const tabName = tab.getAttribute('data-pts-tab');
            switchTab(tabName);
            if (history.replaceState) {
                history.replaceState(null, null, '#' + tabName);
            }
        }
    });

    // Auto-activate tab from hash or query param
    const currentUrlParams = new URLSearchParams(window.location.search);
    const initialTabName = currentUrlParams.get('tab') || window.location.hash.replace('#', '');
    if (initialTabName && tabPanels[initialTabName]) {
        switchTab(initialTabName);
    }

    // Deliverables Sub-filters in Tab 2
    const deliverableFilterBtns = document.querySelectorAll('[data-deliverable-filter]');
    const deliverableRows = document.querySelectorAll('[data-deliverable-row]');

    deliverableFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            deliverableFilterBtns.forEach(b => b.classList.remove('is-active'));
            this.classList.add('is-active');
            const filter = this.getAttribute('data-deliverable-filter');
            deliverableRows.forEach(row => {
                const rowStatus = row.getAttribute('data-deliverable-status');
                if (filter === 'all' || rowStatus === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // ==========================================
    // Add Timesheet Form Validation (Mandatory Attachments)
    // ==========================================
    const addTimesheetForm = document.getElementById('addTimesheetForm');
    if (addTimesheetForm) {
        addTimesheetForm.addEventListener('submit', function(e) {
            const selectedProjId = projectSelect ? projectSelect.value : null;
            const proj = assignedProjects.find(p => p.id == selectedProjId);
            const deptName = (proj && proj.department) ? (proj.department.name || '').toLowerCase() : '';
            const isDesignOrDmProject = deptName.includes('design') || deptName.includes('dm') || deptName.includes('digital marketing');
            const isMandatory = @json($isDesignUser) || @json($isDmUser) || isDesignOrDmProject;

            const fileInput = document.getElementById('timesheetAttachmentsInput');
            const hasNewFiles = fileInput && fileInput.files && fileInput.files.length > 0;
            const existingList = document.getElementById('timesheetExistingAttachmentsList');
            const hasExistingFiles = existingList && existingList.children.length > 0;

            if (isMandatory && !hasNewFiles && !hasExistingFiles) {
                e.preventDefault();
                alert('Attachments are mandatory for Designing and Digital Marketing team members. Please attach your poster or deliverable files.');
                return false;
            }

            // Validation for DM: Poster count cannot exceed Design completed posters
            const isDmUserFlag = @json($isDmUser);
            const typeSelect = document.getElementById('projectTypeSelect');
            const isOnetime = typeSelect && typeSelect.value === 'onetime';
            if (isDmUserFlag && !isOnetime) {
                const posterInput = document.getElementById('timesheetPosterCountInput') || document.querySelector('input[name="poster_count"]');
                if (posterInput) {
                    const countVal = parseInt(posterInput.value || '0', 10);
                    const designCompleted = parseInt(posterInput.getAttribute('data-design-completed') || '0', 10);
                    if (countVal > designCompleted) {
                        e.preventDefault();
                        alert('Cannot set Poster Completed Count to ' + countVal + '. Design team has only completed/approved ' + designCompleted + ' poster(s) for this project.');
                        return false;
                    }
                }
            }
        });
    }

    // ==========================================
    // Review Poster Modal Logic
    // ==========================================
    const reviewPosterModal = document.querySelector('[data-review-poster-modal]');
    const reviewPosterOverlay = document.querySelector('[data-review-poster-modal-overlay]');
    const closeReviewButtons = document.querySelectorAll('[data-close-review-poster-modal]');
    const reviewPosterForm = document.getElementById('reviewPosterForm');
    const reviewModalSubTitle = document.getElementById('reviewModalSubTitle');
    const reviewClientProjectTitle = document.getElementById('reviewClientProjectTitle');
    const reviewDesignerInfo = document.getElementById('reviewDesignerInfo');
    const reviewPosterCountBadge = document.getElementById('reviewPosterCountBadge');
    const reviewPostersThumbnails = document.getElementById('reviewPostersThumbnails');
    const reviewRemarksTextarea = document.getElementById('reviewRemarksTextarea');
    const labelApprove = document.getElementById('labelApprove');
    const labelReject = document.getElementById('labelReject');
    const submitReviewBtn = document.getElementById('submitReviewBtn');

    function setReviewPosterModalState(isOpen) {
        if (!reviewPosterModal || !reviewPosterOverlay) return;
        reviewPosterModal.classList.toggle('is-open', isOpen);
        reviewPosterOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    if (labelApprove && labelReject) {
        labelApprove.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            this.classList.add('selected');
            labelReject.classList.remove('selected');
        });
        labelReject.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            this.classList.add('selected');
            labelApprove.classList.remove('selected');
        });
    }

    function openReviewModalFromData(btn) {
        const timesheetId = btn.getAttribute('data-timesheet-id');
        const designerName = btn.getAttribute('data-designer-name') || 'Designer';
        const projectName = btn.getAttribute('data-project-name') || 'Project';
        const clientName = btn.getAttribute('data-client-name') || 'Client';
        const posterCount = btn.getAttribute('data-poster-count') || '1';
        const status = btn.getAttribute('data-status') || '';
        const remarks = btn.getAttribute('data-remarks') || '';
        const reviewUrl = btn.getAttribute('data-review-url') || '';
        let posters = [];

        try {
            posters = JSON.parse(btn.getAttribute('data-posters') || '[]');
        } catch (e) {
            posters = [];
        }

        if (reviewPosterForm) reviewPosterForm.action = reviewUrl;
        if (reviewModalSubTitle) reviewModalSubTitle.textContent = 'Timesheet #' + timesheetId + ' | ' + designerName;
        if (reviewClientProjectTitle) reviewClientProjectTitle.textContent = clientName + ' — ' + projectName;
        if (reviewDesignerInfo) reviewDesignerInfo.textContent = 'Uploaded by ' + designerName;
        if (reviewPosterCountBadge) reviewPosterCountBadge.textContent = posterCount + ' Poster(s)';
        if (reviewRemarksTextarea) reviewRemarksTextarea.value = remarks;

        // Reset radio states
        if (labelApprove) labelApprove.classList.remove('selected');
        if (labelReject) labelReject.classList.remove('selected');
        const approveRadio = labelApprove ? labelApprove.querySelector('input[type="radio"]') : null;
        const rejectRadio = labelReject ? labelReject.querySelector('input[type="radio"]') : null;
        if (approveRadio) approveRadio.checked = false;
        if (rejectRadio) rejectRadio.checked = false;

        if (status === 'approved' && approveRadio && labelApprove) {
            approveRadio.checked = true;
            labelApprove.classList.add('selected');
        } else if (status === 'rejected' && rejectRadio && labelReject) {
            rejectRadio.checked = true;
            labelReject.classList.add('selected');
        }

        // Render thumbnails
        if (reviewPostersThumbnails) {
            reviewPostersThumbnails.innerHTML = '';
            if (posters && posters.length > 0) {
                posters.forEach(p => {
                    const imgThumb = document.createElement('img');
                    imgThumb.src = p.url;
                    imgThumb.alt = p.name || 'Poster';
                    imgThumb.className = 'poster-deliverable-thumb open-lightbox-trigger';
                    imgThumb.setAttribute('data-lightbox-src', p.url);
                    imgThumb.setAttribute('data-lightbox-title', clientName + ' - ' + projectName);
                    imgThumb.title = 'Click to zoom';
                    reviewPostersThumbnails.appendChild(imgThumb);
                });
            } else {
                reviewPostersThumbnails.innerHTML = '<span style="font-size:12px; color:#64748b;">No image preview available</span>';
            }
        }

        setReviewPosterModalState(true);
    }

    document.addEventListener('click', function(e) {
        const revBtn = e.target.closest('.open-review-poster-btn, .open-review-from-table-btn');
        if (revBtn) {
            openReviewModalFromData(revBtn);
        }
    });

    closeReviewButtons.forEach(btn => {
        btn.addEventListener('click', () => setReviewPosterModalState(false));
    });
    if (reviewPosterOverlay) {
        reviewPosterOverlay.addEventListener('click', () => setReviewPosterModalState(false));
    }

    if (reviewPosterForm) {
        reviewPosterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const actionUrl = this.action;
            const checkedRadio = this.querySelector('input[name="status"]:checked');
            const remarksVal = reviewRemarksTextarea ? reviewRemarksTextarea.value.trim() : '';

            if (!checkedRadio) {
                alert('Please select a Verification Decision (Approve or Reject).');
                return;
            }
            if (remarksVal.length < 3) {
                alert('Remarks are mandatory for both approval and rejection. Please enter at least 3 characters.');
                if (reviewRemarksTextarea) reviewRemarksTextarea.focus();
                return;
            }

            if (submitReviewBtn) {
                submitReviewBtn.disabled = true;
                submitReviewBtn.innerHTML = '<span>Submitting Review...</span>';
            }

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    status: checkedRadio.value,
                    remarks: remarksVal
                })
            })
            .then(res => res.json())
            .then(data => {
                if (submitReviewBtn) {
                    submitReviewBtn.disabled = false;
                    submitReviewBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Submit Review</span>';
                }
                if (data.success) {
                    setReviewPosterModalState(false);
                    alert(data.message || 'Poster review saved successfully!');
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to submit review.');
                }
            })
            .catch(err => {
                if (submitReviewBtn) {
                    submitReviewBtn.disabled = false;
                    submitReviewBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Submit Review</span>';
                }
                console.error('Error submitting poster review:', err);
                alert('An error occurred while submitting the review.');
            });
        });
    }

    // ==========================================
    // DM Proof Modal Logic
    // ==========================================
    const dmProofModal = document.querySelector('[data-dm-proof-modal]');
    const dmProofOverlay = document.querySelector('[data-dm-proof-modal-overlay]');
    const closeDmProofButtons = document.querySelectorAll('[data-close-dm-proof-modal]');
    const dmProofForm = document.getElementById('dmProofForm');
    const dmProofModalSubTitle = document.getElementById('dmProofModalSubTitle');
    const dmExistingProofsContainer = document.getElementById('dmExistingProofsContainer');
    const dmExistingProofsList = document.getElementById('dmExistingProofsList');
    const dmProofDropzone = document.getElementById('dmProofDropzone');
    const dmProofFileInput = document.getElementById('dmProofFileInput');
    const dmProofFilePreview = document.getElementById('dmProofFilePreview');
    const submitDmProofBtn = document.getElementById('submitDmProofBtn');

    function setDmProofModalState(isOpen) {
        if (!dmProofModal || !dmProofOverlay) return;
        dmProofModal.classList.toggle('is-open', isOpen);
        dmProofOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    if (dmProofDropzone && dmProofFileInput) {
        dmProofDropzone.addEventListener('click', () => dmProofFileInput.click());
        dmProofFileInput.addEventListener('change', function() {
            if (dmProofFilePreview) {
                dmProofFilePreview.innerHTML = '';
                const file = this.files && this.files[0];
                if (file) {
                    const item = document.createElement('div');
                    item.className = 'task-file-item';
                    item.innerHTML = `
                        <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                            <span style="font-size:16px;">🖼️</span>
                            <div style="min-width:0;">
                                <div style="font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px;">${escapeHtml(file.name)}</div>
                                <div style="font-size:11px; color:#64748b;">${formatBytes(file.size)}</div>
                            </div>
                        </div>
                        <span style="font-size:11px; font-weight:800; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px;">Ready</span>
                    `;
                    dmProofFilePreview.appendChild(item);
                }
            }
        });
    }

    document.addEventListener('click', function(e) {
        const dmBtn = e.target.closest('.open-dm-proof-modal-btn');
        if (dmBtn) {
            const timesheetId = dmBtn.getAttribute('data-timesheet-id');
            const clientName = dmBtn.getAttribute('data-client-name') || 'Client';
            const projectName = dmBtn.getAttribute('data-project-name') || 'Project';
            const posterCount = parseInt(dmBtn.getAttribute('data-poster-count') || '1', 10);
            const publishedCount = parseInt(dmBtn.getAttribute('data-published-count') || '0', 10);
            const smmDesign = parseInt(dmBtn.getAttribute('data-smm-design') || posterCount, 10);
            const smmDm = parseInt(dmBtn.getAttribute('data-smm-dm') || '0', 10);
            const uploadUrl = dmBtn.getAttribute('data-upload-url') || '';
            let proofs = [];

            try {
                proofs = JSON.parse(dmBtn.getAttribute('data-proofs') || '[]');
            } catch (err) {
                proofs = [];
            }

            if (dmProofForm) {
                dmProofForm.action = uploadUrl;
                dmProofForm.reset();
            }
            if (dmProofModalSubTitle) {
                dmProofModalSubTitle.textContent = clientName + ' — ' + projectName;
            }
            if (document.getElementById('dmProofClientTitle')) {
                document.getElementById('dmProofClientTitle').textContent = clientName;
            }
            if (document.getElementById('dmProofProjectTitle')) {
                document.getElementById('dmProofProjectTitle').textContent = projectName;
            }
            if (document.getElementById('dmProofApprovedBadge')) {
                document.getElementById('dmProofApprovedBadge').textContent = 'Approved: ' + posterCount + ' Poster' + (posterCount > 1 ? 's' : '');
            }
            if (document.getElementById('dmProofSmmStatus')) {
                document.getElementById('dmProofSmmStatus').textContent = 'SMM Design Done: ' + smmDesign + ' | DM Published: ' + smmDm;
            }

            const remainingForTimesheet = Math.max(0, posterCount - publishedCount);
            const remainingForSmm = Math.max(0, smmDesign - smmDm);
            const maxAllowed = Math.min(posterCount, smmDesign);
            const defaultVal = remainingForTimesheet > 0 ? remainingForTimesheet : (remainingForSmm > 0 ? remainingForSmm : 1);

            const posterCountInput = document.getElementById('dmProofPosterCountInput');
            if (posterCountInput) {
                posterCountInput.value = defaultVal;
                posterCountInput.max = maxAllowed;
                posterCountInput.setAttribute('data-max-allowed', maxAllowed);
                posterCountInput.setAttribute('data-smm-design', smmDesign);
                posterCountInput.setAttribute('data-smm-dm', smmDm);
            }
            if (document.getElementById('dmProofMaxAllowedBadge')) {
                document.getElementById('dmProofMaxAllowedBadge').textContent = 'Max Allowed: ' + maxAllowed;
            }
            if (document.getElementById('dmProofMaxCountText')) {
                document.getElementById('dmProofMaxCountText').textContent = maxAllowed;
            }
            if (dmProofFilePreview) {
                dmProofFilePreview.innerHTML = '';
            }

            if (dmExistingProofsContainer && dmExistingProofsList) {
                dmExistingProofsList.innerHTML = '';
                if (proofs && proofs.length > 0) {
                    dmExistingProofsContainer.style.display = 'block';
                    proofs.forEach(pr => {
                        const prCard = document.createElement('div');
                        prCard.style.cssText = 'border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; background:#fff; text-align:center; padding:6px;';
                        prCard.innerHTML = `
                            <a href="${escapeHtml(pr.url)}" target="_blank" class="open-lightbox-trigger" data-lightbox-src="${escapeHtml(pr.url)}" data-lightbox-title="Proof" style="display:block; height:75px; overflow:hidden; border-radius:6px; margin-bottom:4px;">
                                <img src="${escapeHtml(pr.url)}" alt="proof" style="width:100%; height:100%; object-fit:cover;">
                            </a>
                            <div style="font-size:10px; font-weight:700; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(pr.posted_date || 'Proof')}</div>
                            ${pr.post_url ? `<a href="${escapeHtml(pr.post_url)}" target="_blank" style="font-size:10px; color:#2563eb; text-decoration:none; display:block; margin-top:2px;">View Post ↗</a>` : ''}
                        `;
                        dmExistingProofsList.appendChild(prCard);
                    });
                } else {
                    dmExistingProofsContainer.style.display = 'none';
                }
            }

            setDmProofModalState(true);
        }
    });

    closeDmProofButtons.forEach(btn => {
        btn.addEventListener('click', () => setDmProofModalState(false));
    });
    if (dmProofOverlay) {
        dmProofOverlay.addEventListener('click', () => setDmProofModalState(false));
    }

    if (dmProofForm) {
        dmProofForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const posterCountInput = document.getElementById('dmProofPosterCountInput');
            const countVal = parseInt(posterCountInput ? posterCountInput.value : '1', 10);
            const maxAllowed = parseInt(posterCountInput ? posterCountInput.getAttribute('data-max-allowed') : '999', 10);

            if (countVal > maxAllowed) {
                alert('Design team has only completed/approved ' + maxAllowed + ' poster(s). Digital Marketing cannot publish ' + countVal + ' posters.');
                return;
            }

            if (!dmProofFileInput || !dmProofFileInput.files || dmProofFileInput.files.length === 0) {
                alert('Please upload a screenshot proof of the Facebook or social media post.');
                return;
            }

            if (submitDmProofBtn) {
                submitDmProofBtn.disabled = true;
                submitDmProofBtn.innerHTML = '<span>Uploading...</span>';
            }

            const formData = new FormData(this);
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (submitDmProofBtn) {
                    submitDmProofBtn.disabled = false;
                    submitDmProofBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Upload Proof</span>';
                }
                if (status === 422 || !body.success) {
                    alert(body.message || (body.errors ? Object.values(body.errors).flat().join('\n') : 'Validation failed.'));
                    return;
                }
                setDmProofModalState(false);
                alert(body.message || 'Facebook post proof uploaded successfully!');
                window.location.reload();
            })
            .catch(err => {
                if (submitDmProofBtn) {
                    submitDmProofBtn.disabled = false;
                    submitDmProofBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Upload Proof</span>';
                }
                console.error('Error uploading DM proof:', err);
                alert('An error occurred while uploading proof.');
            });
        });
    }

    // ==========================================
    // Poster Lightbox Modal Logic
    // ==========================================
    const lightboxModal = document.querySelector('[data-poster-lightbox-modal]');
    const lightboxOverlay = document.querySelector('[data-poster-lightbox-overlay]');
    const lightboxCloseBtn = document.querySelector('[data-close-poster-lightbox]');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxDownloadBtn = document.getElementById('lightboxDownloadBtn');

    function setLightboxModalState(isOpen) {
        if (!lightboxModal || !lightboxOverlay) return;
        lightboxModal.classList.toggle('is-open', isOpen);
        lightboxOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.open-lightbox-trigger');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            const src = trigger.getAttribute('data-lightbox-src') || trigger.src || trigger.href;
            const title = trigger.getAttribute('data-lightbox-title') || trigger.alt || 'Poster Preview';

            if (src && lightboxImage) {
                lightboxImage.src = src;
                if (lightboxCaption) lightboxCaption.textContent = title;
                if (lightboxDownloadBtn) {
                    lightboxDownloadBtn.href = src;
                    lightboxDownloadBtn.download = (title ? title.replace(/[^a-z0-9]/gi, '_') : 'poster') + '.jpg';
                }
                setLightboxModalState(true);
            }
        }
    });

    if (lightboxCloseBtn) {
        lightboxCloseBtn.addEventListener('click', () => setLightboxModalState(false));
    }
    if (lightboxOverlay) {
        lightboxOverlay.addEventListener('click', () => setLightboxModalState(false));
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (lightboxModal && lightboxModal.classList.contains('is-open')) {
                setLightboxModalState(false);
                return;
            }
            if (reviewPosterModal && reviewPosterModal.classList.contains('is-open')) {
                setReviewPosterModalState(false);
                return;
            }
            if (dmProofModal && dmProofModal.classList.contains('is-open')) {
                setDmProofModalState(false);
                return;
            }
            if (entryClosingModal && entryClosingModal.classList.contains('is-open')) {
                setEntryClosingModalState(false);
                return;
            }
            if (viewClosingModal && viewClosingModal.classList.contains('is-open')) {
                setViewClosingModalState(false);
                return;
            }
            setModalState(false);
            setViewTimesheetsModalState(false);
        }
    });
});
</script>
@endpush
