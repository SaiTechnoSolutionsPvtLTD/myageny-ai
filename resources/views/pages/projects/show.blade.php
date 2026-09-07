@extends('layouts.app')

@section('title', 'Project Details')

@push('styles')
<style>
.ps-page { min-height:100%; background:linear-gradient(180deg,#f8fafc 0%,#eef2ff 100%); font-family:'Inter',sans-serif; }
.ps-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:20px 28px; background:#fff; border-bottom:1px solid #e5e7eb; }
.ps-title { font-size:22px; font-weight:900; color:#111827; }
.ps-breadcrumb { font-size:12px; color:#6b7280; margin-top:4px; }
.ps-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.ps-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:10px 14px; border-radius:12px; border:1px solid #d1d5db; background:#fff; color:#111827; font-size:13px; font-weight:800; text-decoration:none; cursor:pointer; }
.ps-btn-primary { background:#166534; border-color:#166534; color:#fff; }
.ps-body { padding:22px 28px 34px; display:grid; gap:18px; }
.ps-tabbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:18px 22px; background:#fff; border:1px solid #e5e7eb; border-radius:22px; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.ps-tab-btn { border:none; background:#fff7ed; color:#c2410c; padding:11px 16px; border-radius:999px; font-size:13px; font-weight:800; cursor:pointer; transition:all .18s ease; border:1px solid #fed7aa; }
.ps-tab-btn:hover { background:#ffedd5; color:#ea580c; border-color:#fdba74; }
.ps-tab-btn.is-active { background:linear-gradient(135deg,#fe5f04,#ff8a3d); color:#fff; border-color:transparent; box-shadow:0 10px 22px rgba(254,95,4,.24); }
.ps-tab-panel { display:none; }
.ps-tab-panel.is-active { display:grid; gap:18px; }
.ps-grid { display:grid; grid-template-columns:1.2fr .8fr; gap:18px; align-items:start; }
.ps-stack { display:grid; gap:18px; }
.ps-card { background:#fff; border:1px solid #e5e7eb; border-radius:24px; box-shadow:0 16px 38px rgba(15,23,42,.06); overflow:hidden; }
.ps-card-head { padding:18px 20px; border-bottom:1px solid #eef2f7; background:#fcfcfd; }
.ps-card-title { font-size:16px; font-weight:900; color:#111827; }
.ps-card-sub { margin-top:4px; font-size:12px; color:#6b7280; }
.ps-card-body { padding:20px; }
.ps-detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.ps-detail { display:grid; gap:6px; padding:14px; border:1px solid #eef2f7; border-radius:16px; background:#fafcff; }
.ps-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; }
.ps-value { font-size:14px; color:#111827; font-weight:700; word-break:break-word; }
.ps-value-soft { font-weight:600; color:#475569; }
.ps-full { grid-column:1/-1; }
.ps-pill { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid transparent; text-transform:uppercase; letter-spacing:.06em; }
.ps-pill.pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.ps-pill.done { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.ps-pill.ontrack { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
.ps-pill.hold { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.ps-pill.delivered { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.ps-text-block { padding:14px; border:1px solid #eef2f7; border-radius:16px; background:#fafcff; color:#1f2937; font-size:14px; line-height:1.65; white-space:pre-wrap; }
.ps-link { color:#1d4ed8; font-weight:800; text-decoration:none; }
.ps-link:hover { text-decoration:underline; }
.ps-timeline { display:grid; gap:14px; }
.ps-timeline-item { position:relative; padding:16px 16px 16px 18px; border:1px solid #eef2f7; border-radius:18px; background:#fff; }
.ps-timeline-item::before { content:''; position:absolute; left:0; top:16px; bottom:16px; width:4px; border-radius:999px; background:var(--timeline-color,#2563eb); }
.ps-timeline-title { font-size:14px; font-weight:900; color:#111827; }
.ps-timeline-sub { margin-top:4px; font-size:12px; color:#6b7280; }
.ps-timeline-meta { margin-top:10px; font-size:13px; color:#334155; line-height:1.7; }
.ps-update-form { display:grid; gap:16px; }
.ps-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; align-items:start; }
.ps-form-grid-single { grid-template-columns:1fr; }
.ps-form-editor { display:grid; gap:8px; }
.ps-input, .ps-textarea { width:100%; border:1px solid #dbe1e8; border-radius:14px; background:#fff; font-size:14px; color:#111827; }
.ps-input { padding:12px 14px; min-height:48px; }
.ps-textarea { min-height:240px; padding:14px; resize:vertical; }
.ps-input:focus, .ps-textarea:focus { outline:none; border-color:#fdba74; box-shadow:0 0 0 4px rgba(254,95,4,.12); }
.ps-update-list { display:grid; gap:14px; }
.ps-update-overview { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
.ps-update-overview-card { position:relative; overflow:hidden; border:1px solid #e5e7eb; border-radius:20px; background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%); padding:18px; box-shadow:0 12px 28px rgba(15,23,42,.05); }
.ps-update-overview-card::after { content:''; position:absolute; inset:auto 16px 0 16px; height:4px; border-radius:999px; background:var(--update-accent,#2563eb); }
.ps-update-overview-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#64748b; }
.ps-update-overview-value { margin-top:8px; font-size:24px; line-height:1; font-weight:900; color:#111827; }
.ps-update-filter-shell { margin-bottom:18px; padding:16px; border:1px solid #e5e7eb; border-radius:20px; background:linear-gradient(135deg,#fffdf8 0%,#f8fbff 100%); }
.ps-update-item { position:relative; border:1px solid #eef2f7; border-radius:22px; background:linear-gradient(180deg,#fff 0%,#fcfdff 100%); padding:18px; box-shadow:0 14px 32px rgba(15,23,42,.05); overflow:hidden; }
.ps-update-item::before { content:''; position:absolute; left:0; top:0; bottom:0; width:5px; background:var(--update-accent,#2563eb); }
.ps-update-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.ps-update-type { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; background:var(--type-soft-bg,#fff7ed); border:1px solid var(--type-soft-border,#fed7aa); color:var(--type-soft-text,#c2410c); font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
.ps-update-title { margin-top:10px; font-size:17px; font-weight:900; color:#0f172a; }
.ps-update-meta { font-size:12px; color:#64748b; }
.ps-update-content { margin-top:14px; padding-top:14px; border-top:1px solid #edf2f7; color:#1f2937; font-size:14px; line-height:1.8; }
.ps-update-empty { padding:26px 18px; border:1px dashed #dbe1e8; border-radius:18px; background:#fafcff; color:#64748b; font-size:13px; text-align:center; }
.ps-update-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.ps-update-filters { display:flex; align-items:end; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.ps-filter-group { display:grid; gap:6px; min-width:180px; }
.ps-filter-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.ps-update-composer { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.ps-update-composer-copy { font-size:12px; color:#64748b; line-height:1.6; }
.ps-modal-note { margin-top:14px; padding:14px 16px; border:1px solid #e5e7eb; border-radius:16px; background:#fafcff; display:grid; gap:8px; }
.ps-modal-note strong { font-size:13px; color:#0f172a; }
.ps-modal-note span { font-size:12px; color:#64748b; line-height:1.6; }
.ps-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,.42); z-index:1200; display:none; }
.ps-modal-overlay.is-open { display:block; }
.ps-modal { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:min(860px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border:1px solid #e5e7eb; border-radius:24px; box-shadow:0 24px 60px rgba(15,23,42,.22); z-index:1210; display:none; }
.ps-modal.is-open { display:block; }
.ps-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:20px 22px; border-bottom:1px solid #eef2f7; background:#fffdfb; }
.ps-modal-close { width:42px; height:42px; border-radius:14px; border:1px solid #e5e7eb; background:#fff; color:#334155; font-size:16px; cursor:pointer; }
.ps-modal-body { padding:22px; }
.tox-tinymce { border-radius:16px !important; border-color:#dbe1e8 !important; }
.ps-flash { padding:12px 14px; border-radius:14px; font-size:13px; font-weight:700; }
.ps-flash.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.ps-flash.error { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.ps-allocate-list { display:grid; gap:12px; margin-top:16px; }
.ps-allocate-item { display:flex; align-items:flex-start; gap:12px; padding:14px; border:1px solid #e5e7eb; border-radius:16px; background:#fff; }
.ps-allocate-item input { margin-top:3px; }
.ps-allocate-name { font-size:14px; font-weight:800; color:#111827; }
.ps-allocate-sub { margin-top:4px; font-size:12px; color:#64748b; line-height:1.55; }
.ps-allocate-note { margin-top:10px; font-size:12px; color:#475569; }
.ps-inline-form { display:grid; gap:16px; }
.ps-inline-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.ps-selected-list { display:grid; gap:10px; }
.ps-selected-chip { padding:12px 14px; border:1px solid #dbeafe; border-radius:14px; background:#eff6ff; }
.ps-selected-chip.team { border-color:#fed7aa; background:#fff7ed; }
.ps-allocation-group { display:grid; gap:14px; }
.ps-allocation-card { border:1px solid #e5e7eb; border-radius:18px; background:linear-gradient(180deg,#fff 0%,#fafcff 100%); padding:16px; box-shadow:0 10px 24px rgba(15,23,42,.04); }
.ps-allocation-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.ps-allocation-meta { margin-top:10px; font-size:12px; color:#64748b; line-height:1.7; }
.ps-allocation-team { display:grid; gap:10px; margin-top:14px; }
.ps-select { width:100%; min-height:44px; padding:10px 12px; border:1px solid #dbe1e8; border-radius:12px; background:#fff; font-size:13px; color:#111827; }
.ps-select:focus { outline:none; border-color:#93c5fd; box-shadow:0 0 0 4px rgba(59,130,246,.12); }
.ps-select-help { margin-top:8px; font-size:12px; color:#64748b; line-height:1.55; }
.select2-container--default .select2-selection--multiple.ps-select2-selection {
    min-height: 44px;
    border: 1px solid #dbe1e8;
    border-radius: 12px;
    background: #fff;
    padding: 6px 10px;
    display: flex;
    align-items: center;
}
.select2-container--default.select2-container--focus .select2-selection--multiple.ps-select2-selection,
.select2-container--default.select2-container--open .select2-selection--multiple.ps-select2-selection {
    border-color: #93c5fd;
    box-shadow: 0 0 0 4px rgba(59,130,246,.12);
}
.select2-container--default .select2-selection--multiple.ps-select2-selection .select2-selection__rendered {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    padding: 0;
    width: 100%;
}
.select2-container--default .select2-selection--multiple.ps-select2-selection .select2-selection__choice {
    margin: 2px 0;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 700;
}
.select2-container--default .select2-selection--multiple.ps-select2-selection .select2-selection__choice__remove {
    color: #1d4ed8;
    margin-right: 6px;
    border-right: none;
}
.select2-container--default .select2-selection--multiple.ps-select2-selection .select2-search--inline {
    margin: 2px 0;
}
.select2-container--default .select2-selection--multiple.ps-select2-selection .select2-search--inline .select2-search__field {
    margin-top: 0;
    font-size: 13px;
    height: 28px;
    line-height: 28px;
}
.select2-dropdown {
    border: 1px solid #dbe1e8;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 16px 36px rgba(15,23,42,.12);
}
.select2-search--dropdown {
    padding: 10px;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #dbe1e8;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 13px;
}
.select2-results__option {
    font-size: 13px;
    padding: 10px 12px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #2563eb;
    color: #fff;
}
@media (max-width: 1024px) {
    .ps-grid { grid-template-columns:1fr; }
    .ps-form-grid { grid-template-columns:1fr; }
    .ps-inline-grid { grid-template-columns:1fr; }
    .ps-update-overview { grid-template-columns:1fr; }
}
@media (max-width: 768px) {
    .ps-topbar { padding:18px 16px; flex-direction:column; }
    .ps-body { padding:18px 16px 24px; }
    .ps-tabbar { padding:14px; gap:10px; }
    .ps-detail-grid { grid-template-columns:1fr; }
    .ps-modal-body, .ps-modal-head { padding:16px; }
    .ps-update-filters { align-items:stretch; }
    .ps-filter-group { min-width:100%; }
}
/* ── Content Calendar ─────────────────────────────────── */
.cc-header { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:18px; }
.cc-title-group { display:grid; gap:4px; }
.cc-title { font-size:17px; font-weight:900; color:#0f172a; }
.cc-subtitle { font-size:12px; color:#64748b; }
.cc-sheet-form { background:linear-gradient(135deg,#f0fdf4 0%,#eff6ff 100%); border:1px solid #d1fae5; border-radius:20px; padding:20px; margin-bottom:22px; }
.cc-sheet-form-title { font-size:13px; font-weight:800; color:#065f46; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.cc-sheet-input-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.cc-sheet-input { flex:1; min-width:220px; border:1px solid #a7f3d0; border-radius:12px; padding:10px 14px; font-size:13px; color:#0f172a; background:#fff; }
.cc-sheet-input:focus { outline:none; border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.14); }
.cc-sheet-btn { padding:10px 18px; border-radius:12px; border:none; background:linear-gradient(135deg,#059669,#10b981); color:#fff; font-size:13px; font-weight:800; cursor:pointer; white-space:nowrap; }
.cc-sheet-btn:hover { background:linear-gradient(135deg,#047857,#059669); }
.cc-sheet-hint { font-size:11px; color:#6b7280; margin-top:10px; line-height:1.6; }
.cc-sync-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
.cc-sync-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:999px; background:#f0fdf4; border:1px solid #a7f3d0; font-size:11px; font-weight:700; color:#065f46; }
.cc-sync-dot { width:7px; height:7px; border-radius:50%; background:#10b981; animation:cc-pulse 1.8s ease-in-out infinite; }
@keyframes cc-pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.45;transform:scale(1.3)} }
.cc-refresh-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:10px; border:1px solid #e5e7eb; background:#fff; color:#334155; font-size:12px; font-weight:700; cursor:pointer; }
.cc-refresh-btn:hover { background:#f8fafc; }
.cc-table-wrap { overflow-x:auto; border-radius:16px; border:1px solid #e5e7eb; box-shadow:0 8px 22px rgba(15,23,42,.05); }
.cc-table { width:100%; border-collapse:collapse; font-size:13px; min-width:520px; }
.cc-table thead tr { background:linear-gradient(135deg,#1e3a5f 0%,#1e40af 100%); }
.cc-table thead th { padding:13px 14px; text-align:left; font-size:11px; font-weight:800; color:#e0f2fe; letter-spacing:.07em; text-transform:uppercase; white-space:nowrap; border-right:1px solid rgba(255,255,255,.08); }
.cc-table thead th:last-child { border-right:none; }
.cc-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background .12s; }
.cc-table tbody tr:hover { background:#f8faff; }
.cc-table tbody tr:nth-child(even) { background:#f9fafb; }
.cc-table tbody tr:nth-child(even):hover { background:#f0f4ff; }
.cc-table td { padding:11px 14px; color:#1e293b; vertical-align:top; border-right:1px solid #f1f5f9; line-height:1.55; }
.cc-table td:last-child { border-right:none; }
.cc-empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; padding:52px 24px; text-align:center; }
.cc-empty-icon { font-size:44px; opacity:.35; }
.cc-empty-title { font-size:15px; font-weight:800; color:#374151; }
.cc-empty-sub { font-size:13px; color:#6b7280; max-width:340px; line-height:1.6; }
.cc-loading { display:flex; align-items:center; gap:10px; padding:30px 16px; color:#64748b; font-size:13px; }
.cc-spinner { width:18px; height:18px; border:2px solid #e5e7eb; border-top-color:#2563eb; border-radius:50%; animation:cc-spin .7s linear infinite; }
@keyframes cc-spin { to{transform:rotate(360deg)} }
.cc-error { padding:18px 16px; border:1px solid #fca5a5; border-radius:14px; background:#fff5f5; color:#b91c1c; font-size:13px; line-height:1.7; }
.cc-cell-link { color:#2563eb; font-weight:700; text-decoration:none; border-bottom:1px dashed #bfdbfe; transition:color .12s,border-color .12s; }
.cc-cell-link:hover { color:#1d4ed8; border-bottom-color:#1d4ed8; }
</style>
@endpush

@section('content')
@php
    $lead = $projectItem->lead;
    $leadProduct = $projectItem->leadProduct;
    $salesPerson = $lead?->assignedTo ?: $lead?->createdBy;
    $salesPersonRole = trim((string) ($salesPerson?->designation ?? ''));
    $leadDisplayId = $lead?->id ? 'LD-' . str_pad((string) $lead->id, 4, '0', STR_PAD_LEFT) : 'Not available';
    $productValue = (float) ($leadProduct?->total_price ?? 0);
    $receivedAmount = (float) ($leadProduct?->payments?->sum('amount') ?? $leadProduct?->amount_paid ?? 0);
    $pendingAmount = max(0, $productValue - $receivedAmount);
    $customFormEntries = collect($projectItem->custom_form_data ?? [])
        ->filter(fn ($entry) => filled(data_get($entry, 'label')) || filled(data_get($entry, 'field_name')))
        ->values();
    $currentTeamStatus = $isTlScopedView
        ? (string) ($projectItem->current_team_status ?? 'allocation_pending')
        : (string) $projectItem->employee_allocation_status;
    $currentTeamAllocatedAt = $isTlScopedView
        ? ($projectItem->current_team_allocated_at ?? null)
        : $projectItem->employee_allocated_at;
    $currentTeamAllocatedByName = $isTlScopedView
        ? (($projectItem->current_team_allocated_by_name ?? null) ?: 'Pending')
        : ($projectItem->employeeAllocatedBy?->name ?: 'Pending');
    $isEmployeeAllocated = strtolower($currentTeamStatus) === 'allocated';
    $updateTypeMeta = [
        'production_update' => [
            'label' => 'Production Update',
            'title' => '',
            'description' => '',
            'accent' => '#2563eb',
            'soft_bg' => '#eff6ff',
            'soft_border' => '#bfdbfe',
            'soft_text' => '#1d4ed8',
        ],
        'meeting_update' => [
            'label' => 'Meeting Update',
            'title' => '',
            'description' => '',
            'accent' => '#7c3aed',
            'soft_bg' => '#f5f3ff',
            'soft_border' => '#ddd6fe',
            'soft_text' => '#6d28d9',
        ],
        'weekly_update' => [
            'label' => 'Weekly Update',
            'title' => '',
            'description' => '',
            'accent' => '#ea580c',
            'soft_bg' => '#fff7ed',
            'soft_border' => '#fed7aa',
            'soft_text' => '#c2410c',
        ],
        'timesheet' => [
            'label' => 'Timesheet',
            'title' => '',
            'description' => '',
            'accent' => '#10b981',
            'soft_bg' => '#f0fdf4',
            'soft_border' => '#bbf7d0',
            'soft_text' => '#15803d',
        ],
        'schedule_history' => [
            'label' => 'Schedule & Status Changed',
            'title' => '',
            'description' => '',
            'accent' => '#b91c1c',
            'soft_bg' => '#fef2f2',
            'soft_border' => '#fecaca',
            'soft_text' => '#991b1b',
        ],
    ];
    $allocationStatusTone = $isTlScopedView
        ? ($isEmployeeAllocated ? 'done' : 'pending')
        : (strtolower((string) $projectItem->project_allocation_status) === 'allocated' ? 'done' : 'pending');
    $projectExecutionStatus = strtolower((string) ($projectItem->project_execution_status ?: 'ontrack'));
    $projectExecutionLabel = match ($projectExecutionStatus) {
        'hold' => 'Hold',
        'delivered' => 'Delivered',
        default => 'Ontrack',
    };
    $pageTitle = $isTlScopedView ? 'My Project Details' : 'Project Full Details';
    $pageCrumb = $isTlScopedView ? 'Projects Dashboard > My Projects > View' : 'CRM Dashboard > Projects Details > View';
    $hasUpdateErrors = $errors->has('type') || $errors->has('content');
    $hasScheduleErrors = $errors->has('project_delivery_date') || $errors->has('project_execution_status');
    $activeTab = $hasUpdateErrors
        ? 'updates'
        : ($hasScheduleErrors ? 'overview' : request('tab', 'overview'));
    // Content Calendar: show only for Designing / Digital Marketing departments
    $deptName = strtolower(trim((string) ($projectItem->department?->name ?? '')));
    $isContentCalendarDept = in_array($deptName, ['designing', 'digital marketing'], true);
    $contentCalendarSheetUrl = (string) ($projectItem->content_calendar_sheet_url ?? '');

    // Testing Tab: show ONLY for Development department / Development team
    $currentUser = auth()->user();
    $isDevUser = $currentUser && (
        $currentUser->belongsToDevelopmentDepartment() ||
        $currentUser->hasDevelopmentLikeRole() ||
        $currentUser->isDevelopmentTeam() ||
        $currentUser->isSuperAdmin() ||
        $currentUser->isCompanyAdmin()
    );

    $isDevDept = str_contains($deptName, 'development') || str_contains($deptName, 'dev') || str_contains($deptName, 'software') || str_contains($deptName, 'web') || str_contains($deptName, 'app');
    $isNonDevDept = str_contains($deptName, 'digital') || str_contains($deptName, 'marketing') || str_contains($deptName, 'design') || str_contains($deptName, 'dm');

    $canSeeTestingTab = ($isDevDept || $isDevUser) && ! $isNonDevDept;

    if ($activeTab === 'testing' && ! $canSeeTestingTab) {
        $activeTab = 'overview';
    }
@endphp
<div class="ps-page">
    <div class="ps-topbar">
        <div>
            <div class="ps-title">{{ $pageTitle }}</div>
            <div class="ps-breadcrumb">{{ $pageCrumb }}</div>
        </div>
        <div class="ps-actions">
            <a href="{{ route('projects.index') }}" class="ps-btn">Back</a>
        </div>
    </div>

    <div class="ps-body">
        @if(session('success'))
            <div class="ps-flash success" style="margin-bottom: 20px;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="ps-flash error" style="margin-bottom: 20px;">{{ session('error') }}</div>
        @endif

        <div class="ps-tabbar" role="tablist" aria-label="Project details sections">
            <button type="button" class="ps-tab-btn {{ $activeTab === 'overview' ? 'is-active' : '' }}" data-tab-target="overview">Overview</button>
            <button type="button" class="ps-tab-btn {{ $activeTab === 'approvals' ? 'is-active' : '' }}" data-tab-target="approvals">Approvals</button>
            <button type="button" class="ps-tab-btn {{ $activeTab === 'allocation' ? 'is-active' : '' }}" data-tab-target="allocation">{{ $isTlScopedView ? 'Team Allocation' : 'TL Allocation' }}</button>
            @if(auth()->user()?->allowsProductionUpdates())
            <button type="button" class="ps-tab-btn {{ $activeTab === 'updates' ? 'is-active' : '' }}" data-tab-target="updates">Production Update</button>
            @endif
            <button type="button" class="ps-tab-btn {{ $activeTab === 'timeline' ? 'is-active' : '' }}" data-tab-target="timeline">Timeline</button>
            @if($canSeeTestingTab)
            <button type="button" class="ps-tab-btn {{ $activeTab === 'testing' ? 'is-active' : '' }}" data-tab-target="testing">
                <i class="bi bi-bug" style="margin-right:5px;"></i>Testing
            </button>
            @endif
            @if($isContentCalendarDept)
            <button type="button" class="ps-tab-btn {{ $activeTab === 'content_calendar' ? 'is-active' : '' }}" data-tab-target="content_calendar" id="cc-tab-btn">
                <i class="bi bi-calendar2-week" style="margin-right:5px;"></i>Content Calendar
            </button>
            @endif
        </div>

        <section class="ps-tab-panel {{ $activeTab === 'overview' ? 'is-active' : '' }}" data-tab-panel="overview">
            <div class="ps-grid">
                <div class="ps-stack">
                    <section class="ps-card">
                        <div class="ps-card-head">
                            <div class="ps-card-title">Customer And Project</div>
                            <div class="ps-card-sub">Customer details and project initiation snapshot</div>
                        </div>
                        <div class="ps-card-body">
                            <div class="ps-detail-grid">
                                <div class="ps-detail">
                                    <div class="ps-label">Lead ID</div>
                                    <div class="ps-value">{{ $leadDisplayId }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Customer Name</div>
                                    <div class="ps-value">{{ $projectItem->client_name ?: ($lead?->contact_name ?: 'Not available') }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Company Name</div>
                                    <div class="ps-value">{{ $projectItem->company_name ?: ($lead?->company_name ?: 'Not available') }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Email</div>
                                    <div class="ps-value ps-value-soft">{{ $lead?->email ?: 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Mobile Number</div>
                                    <div class="ps-value ps-value-soft">{{ $lead?->mobile_number ?: 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Project Name</div>
                                    <div class="ps-value">{{ $projectItem->product_name }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Department</div>
                                    <div class="ps-value">{{ $projectItem->department?->name ?: 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Product Value</div>
                                    <div class="ps-value">₹{{ number_format($productValue, 2) }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Received Amount</div>
                                    <div class="ps-value">₹{{ number_format($receivedAmount, 2) }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Pending Amount</div>
                                    <div class="ps-value">₹{{ number_format($pendingAmount, 2) }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Working Days</div>
                                    <div class="ps-value">{{ $projectItem->total_working_days }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">UI Available</div>
                                    <div class="ps-value">{{ $projectItem->ui_available ? 'Yes' : 'No' }}</div>
                                </div>
                                <div class="ps-detail ps-full">
                                    <div class="ps-label">Current Allocation Status</div>
                                    <div class="ps-value">
                                        <span class="ps-pill {{ $allocationStatusTone }}">{{ str_replace('_', ' ', $isTlScopedView ? $currentTeamStatus : (string) $projectItem->project_allocation_status) }}</span>
                                    </div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Delivery Date</div>
                                    <div class="ps-value">{{ optional($projectDeliveryDate ?? null)->format('d M Y') ?: 'Not set' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Project Status</div>
                                    <div class="ps-value">
                                        <span class="ps-pill {{ $projectExecutionStatus }}">{{ $projectExecutionLabel }}</span>
                                    </div>
                                </div>
                                <div class="ps-detail ps-full">
                                    <div class="ps-label">Initiation Remarks</div>
                                    <div class="ps-text-block">{{ $projectItem->requirements ?: 'No remarks provided.' }}</div>
                                </div>
                                <div class="ps-detail ps-full">
                                    <div class="ps-label">Attachment</div>
                                    <div class="ps-value">
                                        @if($projectItem->attachment_path)
                                            @php
                                                $attUrl = (str_starts_with($projectItem->attachment_path, 'uploads/') || str_starts_with($projectItem->attachment_path, 'http'))
                                                    ? asset($projectItem->attachment_path)
                                                    : asset('storage/' . $projectItem->attachment_path);
                                            @endphp
                                            <a class="ps-link" href="{{ $attUrl }}" target="_blank" download="{{ $projectItem->attachment_name ?: basename($projectItem->attachment_path) }}">{{ $projectItem->attachment_name ?: 'View / Download attachment' }}</a>
                                        @else
                                            <span class="ps-value-soft">No attachment available</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="ps-card">
                    <div class="ps-card-head">
                        <div class="ps-card-title">Quick Summary</div>
                        <div class="ps-card-sub">Current project state at a glance</div>
                    </div>
                    <div class="ps-card-body">
                            <div class="ps-detail-grid">
                                <div class="ps-detail">
                                    <div class="ps-label">Sales Person</div>
                                    <div class="ps-value">{{ $salesPerson?->name ?: 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Sales Person Email</div>
                                    <div class="ps-value ps-value-soft">{{ $salesPerson?->email ?: 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Sales Person Role</div>
                                    <div class="ps-value">{{ $salesPersonRole !== '' ? $salesPersonRole : 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Initiated By</div>
                                    <div class="ps-value">{{ $projectItem->initiatedBy?->name ?: 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Initiated On</div>
                                    <div class="ps-value">{{ optional($projectItem->created_at)->format('d M Y h:i A') ?: 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Project Approval</div>
                                    <div class="ps-value">{{ $projectItem->productionApprovalReviewedBy?->name ?: 'Pending' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Delivery Date</div>
                                    <div class="ps-value">{{ optional($projectDeliveryDate ?? null)->format('d M Y') ?: 'Not set' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Project Status</div>
                                    <div class="ps-value"><span class="ps-pill {{ $projectExecutionStatus }}">{{ $projectExecutionLabel }}</span></div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">{{ $isTlScopedView ? 'Employee Allocated By' : 'Allocated By' }}</div>
                                    <div class="ps-value">{{ $isTlScopedView ? ($projectItem->employeeAllocatedBy?->name ?: 'Pending') : ($projectItem->projectAllocatedBy?->name ?: 'Pending') }}</div>
                                </div>
                            </div>
                        </div>
                </section>

                @if($canManageProjectSchedule ?? false)
                    <section class="ps-card">
                        <div class="ps-card-head">
                            <div class="ps-card-title">Delivery And Status Update</div>
                            <div class="ps-card-sub">TL and Project Coordinator can update delivery date and current project status here.</div>
                        </div>
                        <div class="ps-card-body">
                            <form method="POST" action="{{ route('projects.schedule.update', $projectItem) }}" class="ps-inline-form">
                                @csrf
                                <div class="ps-inline-grid">
                                    <div>
                                        <label class="ps-label" style="margin-bottom:8px;">Delivery Date</label>
                                        <input
                                            type="date"
                                            name="project_delivery_date"
                                            class="ps-input"
                                            value="{{ old('project_delivery_date', optional($projectDeliveryDate ?? null)->toDateString()) }}"
                                        >
                                        @error('project_delivery_date')
                                            <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="ps-label" style="margin-bottom:8px;">Project Status</label>
                                        <select name="project_execution_status" class="ps-input" required>
                                            <option value="ontrack" @selected(old('project_execution_status', $projectExecutionStatus) === 'ontrack')>Ontrack</option>
                                            <option value="hold" @selected(old('project_execution_status', $projectExecutionStatus) === 'hold')>Hold</option>
                                            <option value="delivered" @selected(old('project_execution_status', $projectExecutionStatus) === 'delivered')>Delivered</option>
                                            <option value="lost" @selected(old('project_execution_status', $projectExecutionStatus) === 'lost')>Lost</option>
                                        </select>
                                        @error('project_execution_status')
                                            <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="ps-actions">
                                    <button type="submit" class="ps-btn ps-btn-primary">Update Delivery & Status</button>
                                </div>
                            </form>
                        </div>
                    </section>
                @endif
            </div>
        </section>

        <section class="ps-tab-panel {{ $activeTab === 'approvals' ? 'is-active' : '' }}" data-tab-panel="approvals">
            <div class="ps-grid">
                <div class="ps-stack">
                    <section class="ps-card">
                        <div class="ps-card-head">
                            <div class="ps-card-title">OVP And Approval Inputs</div>
                            <div class="ps-card-sub">Details added during OVP and production approval stages</div>
                        </div>
                        <div class="ps-card-body">
                            <div class="ps-detail-grid">
                                {{--  <div class="ps-detail">
                                    <div class="ps-label">Welcome Call Date</div>
                                    <div class="ps-value">{{ optional($projectItem->welcome_call_date)->format('d M Y') ?: 'Not available' }}</div>
                                </div>
                                <div class="ps-detail">
                                    <div class="ps-label">Welcome Call Time</div>
                                    <div class="ps-value">{{ $projectItem->welcome_call_time ? \Illuminate\Support\Str::of((string) $projectItem->welcome_call_time)->substr(0, 5) : 'Not available' }}</div>
                                </div>  --}}
                                @forelse($customFormEntries as $entry)
                                    @php
                                        $entryLabel = data_get($entry, 'label') ?: \Illuminate\Support\Str::of((string) data_get($entry, 'field_name'))->replace('_', ' ')->title()->value();
                                        $entryType = strtolower((string) data_get($entry, 'type'));
                                        $entryValue = data_get($entry, 'value');
                                    @endphp
                                    <div class="ps-detail {{ $entryType === 'textarea' ? 'ps-full' : '' }}">
                                        <div class="ps-label">{{ $entryLabel }}</div>
                                        <div class="ps-value ps-value-soft">
                                            @if($entryType === 'file' && is_array($entryValue))
                                                @if(! empty($entryValue['url']))
                                                    <a class="ps-link" href="{{ $entryValue['url'] }}" target="_blank">{{ $entryValue['name'] ?? 'View attachment' }}</a>
                                                @else
                                                    {{ $entryValue['name'] ?? 'File uploaded' }}
                                                @endif
                                            @elseif(is_array($entryValue))
                                                {{ collect($entryValue)->filter(fn ($item) => filled($item))->implode(', ') ?: 'Not available' }}
                                            @elseif(filled($entryValue))
                                                {{ $entryValue }}
                                            @else
                                                Not available
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="ps-detail ps-full">
                                        <div class="ps-label">OVP Form Details</div>
                                        <div class="ps-text-block">No dynamic OVP form details available for this project.</div>
                                    </div>
                                @endforelse
                                @if(auth()->user()->canViewBudgetApprovalDetails() && $projectItem->lead_budget_amount)
                                    <div class="ps-detail">
                                        <div class="ps-label">Lead Budget Amount</div>
                                        <div class="ps-value" style="color: #166534;">₹{{ number_format($projectItem->lead_budget_amount, 2) }}</div>
                                    </div>
                                    <div class="ps-detail">
                                        <div class="ps-label">Budget Amount Type</div>
                                        <div class="ps-value">{{ $projectItem->budget_amount_type }}</div>
                                    </div>
                                @endif
                                <div class="ps-detail ps-full">
                                    <div class="ps-label">Production Approval Remarks</div>
                                    <div class="ps-text-block">{{ $projectItem->production_approval_remarks ?: 'No production approval remarks provided.' }}</div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="ps-card">
                    <div class="ps-card-head">
                        <div class="ps-card-title">Approval Owners</div>
                        <div class="ps-card-sub">Reviewers and timestamps for approval stages</div>
                    </div>
                    <div class="ps-card-body">
                        <div class="ps-detail-grid">
                            <div class="ps-detail">
                                <div class="ps-label">OVP Approved By</div>
                                <div class="ps-value">{{ $projectItem->reviewedBy?->name ?: 'Not available' }}</div>
                            </div>
                            <div class="ps-detail">
                                <div class="ps-label">OVP Approved On</div>
                                <div class="ps-value">{{ optional($projectItem->reviewed_at)->format('d M Y h:i A') ?: 'Not available' }}</div>
                            </div>
                            <div class="ps-detail">
                                <div class="ps-label">Project Approved By</div>
                                <div class="ps-value">{{ $projectItem->productionApprovalReviewedBy?->name ?: 'Not available' }}</div>
                            </div>
                            <div class="ps-detail">
                                <div class="ps-label">Project Approved On</div>
                                <div class="ps-value">{{ optional($projectItem->production_approval_reviewed_at)->format('d M Y h:i A') ?: 'Not available' }}</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <section class="ps-tab-panel {{ $activeTab === 'allocation' ? 'is-active' : '' }}" data-tab-panel="allocation">
            <div class="ps-stack">
                <section class="ps-card">
                    <div class="ps-card-head">
                        <div class="ps-card-title">TL Allocation</div>
                        <div class="ps-card-sub">All department TLs are shown here. You can allocate this project to multiple TLs.</div>
                    </div>
                    <div class="ps-card-body">

                        @if($allocatedTlUsers->isNotEmpty())
                            <div class="ps-selected-list">
                                @foreach($allocatedTlUsers as $tlUser)
                                    <div class="ps-selected-chip">
                                        <div class="ps-allocate-name">{{ $tlUser->name }}</div>
                                        <div class="ps-allocate-sub">{{ $tlUser->department_label }} | {{ implode(', ', $tlUser->role_names) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="ps-allocate-note">TL allocation has not been completed yet. You can select TLs from the dropdown below and allocate them directly.</div>
                        @endif
                            
                        @if($canAllocate)
                            <form method="POST" action="{{ route('projects.allocate', $projectItem) }}" id="tl-allocation-form">
                                @csrf
                                @if($tlUsers->isNotEmpty())
                                    <div class="ps-label" style="margin-bottom:8px;">Available TLs</div>
                                    <select name="tl_user_ids[]" class="ps-select select2 ps-tl-select" data-placeholder="Select TLs for allocation" multiple required>
                                        @foreach($tlUsers as $tlUser)
                                            <option
                                                value="{{ $tlUser->id }}"
                                                @selected(in_array($tlUser->id, old('tl_user_ids', []), true))
                                            >
                                                {{ $tlUser->name }} | {{ $tlUser->department_label }} | {{ implode(', ', $tlUser->role_names) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="ps-select-help">
                                        You can search and select multiple TLs from all departments here.
                                    </div>
                                @else
                                    <div class="ps-allocate-note">No TL users found right now. Create or assign TL role users to make them available for allocation.</div>
                                @endif

                                @error('tl_user_ids')
                                    <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
                                @enderror
                                @error('tl_user_ids.*')
                                    <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
                                @enderror

                                @if($tlUsers->isNotEmpty())
                                    <div class="ps-actions" style="margin-top:16px;">
                                        <button type="submit" class="ps-btn ps-btn-primary">Allocate Selected TLs</button>
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </section>

                @if($canAllocateEmployees || $isAssignedTl || $allocatedEmployees->isNotEmpty() || auth()->user()?->isDesigningTl() || auth()->user()?->hasAdminLikeRole())
                    <section class="ps-card">
                        <div class="ps-card-head">
                            <div class="ps-card-title">Employee Allocation</div>
                            <div class="ps-card-sub">Each allocated TL's team status and selected employees are shown here.</div>
                        </div>
                        <div class="ps-card-body">
                            @if($tlAllocationSummaries->isNotEmpty())
                                <div class="ps-allocation-group">
                                    @foreach($tlAllocationSummaries as $tlAllocation)
                                        @php
                                            $allocationDone = strtolower($tlAllocation->status) === 'allocated';
                                        @endphp
                                        <div class="ps-allocation-card">
                                            <div class="ps-allocation-head">
                                                <div>
                                                    <div class="ps-allocate-name">{{ $tlAllocation->tl_user?->name ?: 'Unknown TL' }}</div>
                                                    <div class="ps-allocate-sub">
                                                        {{ $tlAllocation->tl_user?->department_label ?: 'Department not available' }}
                                                        @if(! empty($tlAllocation->tl_user?->role_names))
                                                            | {{ implode(', ', $tlAllocation->tl_user->role_names) }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <span class="ps-pill {{ $allocationDone ? 'done' : 'pending' }}">{{ str_replace('_', ' ', $tlAllocation->status) }}</span>
                                            </div>
                                            <div class="ps-allocation-meta">
                                                Allocated By: {{ $tlAllocation->allocated_by_name }}<br>
                                                Allocated On: {{ optional($tlAllocation->allocated_at)->format('d M Y h:i A') ?: 'Pending' }}
                                            </div>

                                            @if($tlAllocation->employees->isNotEmpty())
                                                <div class="ps-allocation-team">
                                                    @foreach($tlAllocation->employees as $employee)
                                                        <div class="ps-selected-chip team">
                                                            <div class="ps-allocate-name">{{ $employee->name }}</div>
                                                            <div class="ps-allocate-sub">{{ $employee->department_label }} | {{ implode(', ', $employee->role_names) }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="ps-allocate-note">This TL has not allocated team members yet.</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="ps-allocate-note">Employee allocation is pending for this project. Select one or more mapped employees below and allocate them directly.</div>
                            @endif

                            @if($canAllocateEmployees)
                                <form method="POST" action="{{ route('projects.employee-allocate', $projectItem) }}" id="employee-allocation-form">
                                    @csrf
                                    @if($teamMembers->isNotEmpty())
                                        <div class="ps-label" style="margin:16px 0 8px;">Mapped Employees</div>
                                        <select name="employee_user_ids[]" class="ps-select select2 ps-employee-select" data-placeholder="Select employees for this project" multiple required>
                                            @foreach($teamMembers as $teamMember)
                                                <option
                                                    value="{{ $teamMember->id }}"
                                                    @selected(in_array($teamMember->id, old('employee_user_ids', $allocatedEmployees->pluck('id')->all()), true))
                                                >
                                                    {{ $teamMember->name }} | {{ $teamMember->department_label }} | {{ implode(', ', $teamMember->role_names) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="ps-select-help">
                                            Mapped employees and the logged-in TL are shown here. You can search, allocate, or reallocate multiple users at once.
                                        </div>
                                    @else
                                        <div class="ps-allocate-note">No mapped employees were found under this TL right now.</div>
                                    @endif

                                    @error('employee_user_ids')
                                        <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
                                    @enderror
                                    @error('employee_user_ids.*')
                                        <div class="ps-allocate-note" style="color:#b91c1c;">{{ $message }}</div>
                                    @enderror

                                    @if($teamMembers->isNotEmpty())
                                        <div class="ps-actions" style="margin-top:16px;">
                                            <button type="submit" class="ps-btn ps-btn-primary">{{ $allocatedEmployees->isNotEmpty() ? 'Reallocate Employees' : 'Allocate Selected Employees' }}</button>
                                        </div>
                                    @endif
                                </form>
                            @endif
                        </div>
                    </section>
                @endif
            </div>
        </section>

        @if(auth()->user()?->allowsProductionUpdates())
        <section class="ps-tab-panel {{ $activeTab === 'updates' ? 'is-active' : '' }}" data-tab-panel="updates">
            <section class="ps-card">
                <div class="ps-card-head">
                    <div class="ps-card-title">Production Updates</div>
                    <div class="ps-card-sub">Track production movement, weekly summaries, and meeting decisions in one clean timeline.</div>
                </div>
                <div class="ps-card-body">
                    <div class="ps-update-toolbar">
                        <div class="ps-update-composer">
                            <div class="ps-update-composer-copy">Latest update appears first. Use filters to quickly jump between production, meeting, and weekly notes.</div>
                        </div>
                        <button type="button" class="ps-btn ps-btn-primary" data-open-update-modal>Add New Update</button>
                    </div>

                    <div class="ps-update-overview">
                        @foreach($updateTypeMeta as $typeKey => $typeMeta)
                            <div class="ps-update-overview-card" style="--update-accent:{{ $typeMeta['accent'] }};">
                                <div class="ps-update-overview-label">{{ $typeMeta['label'] }}</div>
                                <div class="ps-update-overview-value">{{ (int) ($projectUpdateCounts[$typeKey] ?? 0) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="ps-update-filter-shell">
                        <form method="GET" action="{{ route('projects.show', $projectItem) }}" class="ps-update-filters">
                            <input type="hidden" name="tab" value="updates">
                            <div class="ps-filter-group">
                                <label class="ps-label">Filter By Type</label>
                                <select name="update_type" class="ps-input">
                                    <option value="">All Types</option>
                                    <option value="production_update" @selected($selectedUpdateType === 'production_update')>Production Update</option>
                                    <option value="meeting_update" @selected($selectedUpdateType === 'meeting_update')>Meeting Update</option>
                                    <option value="weekly_update" @selected($selectedUpdateType === 'weekly_update')>Weekly Update</option>
                                    <option value="timesheet" @selected($selectedUpdateType === 'timesheet')>Timesheet</option>
                                </select>
                            </div>
                            <div class="ps-filter-group">
                                <label class="ps-label">Filter By Date</label>
                                <input type="date" name="update_date" value="{{ $selectedUpdateDate }}" class="ps-input">
                            </div>
                            <div class="ps-filter-actions">
                                <button type="submit" class="ps-btn ps-btn-primary">Apply Filter</button>
                                <a href="{{ route('projects.show', ['productionInitiation' => $projectItem, 'tab' => 'updates']) }}" class="ps-btn">Reset</a>
                            </div>
                        </form>
                    </div>

                    @if($projectUpdates->isNotEmpty())
                        <div class="ps-update-list">
                            @foreach($projectUpdates as $update)
                                @php
                                    $typeMeta = $updateTypeMeta[$update->type] ?? [
                                        'label' => str_replace('_', ' ', (string) $update->type),
                                        'title' => 'Project Update',
                                        'accent' => '#2563eb',
                                        'soft_bg' => '#eff6ff',
                                        'soft_border' => '#bfdbfe',
                                        'soft_text' => '#1d4ed8',
                                    ];
                                    $isLifecycleUpdate = str_contains((string)$update->content, 'Production Initiation')
                                        || str_contains((string)$update->content, 'OVP Review')
                                        || str_contains((string)$update->content, 'Production Approval')
                                        || str_contains((string)$update->content, 'Team Lead Allocation')
                                        || str_contains((string)$update->content, 'Team Member Allocation')
                                        || str_contains((string)$update->content, 'OVP Executive Allocation');
                                @endphp
                                <article class="ps-update-item" style="--update-accent:{{ $typeMeta['accent'] }};">
                                    <div class="ps-update-head">
                                        <div>
                                            <span
                                                class="ps-update-type"
                                                style="--type-soft-bg:{{ $typeMeta['soft_bg'] }}; --type-soft-border:{{ $typeMeta['soft_border'] }}; --type-soft-text:{{ $typeMeta['soft_text'] }};"
                                            >
                                                {{ $typeMeta['label'] }}
                                            </span>
                                            @if(!empty($typeMeta['title']))
                                                <div class="ps-update-title">{{ $typeMeta['title'] }}</div>
                                            @endif
                                        </div>
                                        @if(!$isLifecycleUpdate)
                                            <div class="ps-update-meta">
                                                Added By: {{ $update->createdBy?->name ?: 'Unknown user' }}<br>
                                                Added On: {{ optional($update->created_at)->format('d M Y h:i A') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ps-update-content">{!! $update->content !!}</div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="ps-update-empty">No production updates are available for the selected filters.</div>
                    @endif
                </div>
            </section>
        </section>
        @endif

        <section class="ps-tab-panel {{ $activeTab === 'timeline' ? 'is-active' : '' }}" data-tab-panel="timeline">
            <section class="ps-card">
                <div class="ps-card-head">
                    <div class="ps-card-title">Process Timeline</div>
                    <div class="ps-card-sub">Who handled each stage with date and time</div>
                </div>
                <div class="ps-card-body">
                    <div class="ps-timeline">
                        <div class="ps-timeline-item" style="--timeline-color:#2563eb;">
                            <div class="ps-timeline-title">Project Initiated</div>
                            <div class="ps-timeline-sub">Initial project request creation</div>
                            <div class="ps-timeline-meta">
                                Initiated By: {{ $projectItem->initiatedBy?->name ?: 'Not available' }}<br>
                                Initiated On: {{ optional($projectItem->created_at)->format('d M Y h:i A') ?: 'Not available' }}
                            </div>
                        </div>

                        <div class="ps-timeline-item" style="--timeline-color:#0f766e;">
                            <div class="ps-timeline-title">OVP Approval</div>
                            <div class="ps-timeline-sub">OVP review and confirmation</div>
                            <div class="ps-timeline-meta">
                                OVP Approved By: {{ $projectItem->reviewedBy?->name ?: 'Not available' }}<br>
                                OVP Approved On: {{ optional($projectItem->reviewed_at)->format('d M Y h:i A') ?: 'Not available' }}
                            </div>
                        </div>

                        <div class="ps-timeline-item" style="--timeline-color:#7c3aed;">
                            <div class="ps-timeline-title">Project Approval</div>
                            <div class="ps-timeline-sub">Production approval stage action</div>
                            <div class="ps-timeline-meta">
                                Approved By: {{ $projectItem->productionApprovalReviewedBy?->name ?: 'Not available' }}<br>
                                Approved On: {{ optional($projectItem->production_approval_reviewed_at)->format('d M Y h:i A') ?: 'Not available' }}
                            </div>
                        </div>

                        <div class="ps-timeline-item" style="--timeline-color:#166534;">
                            <div class="ps-timeline-title">TL Allocation</div>
                            <div class="ps-timeline-sub">Latest TL allocation ownership status</div>
                            <div class="ps-timeline-meta">
                                Current Status: {{ str_replace('_', ' ', (string) $projectItem->project_allocation_status) ?: 'Not available' }}<br>
                                Allocated By: {{ $projectItem->projectAllocatedBy?->name ?: 'Pending' }}<br>
                                Allocated On: {{ optional($projectItem->project_allocated_at)->format('d M Y h:i A') ?: 'Pending' }}
                            </div>
                        </div>

                        <div class="ps-timeline-item" style="--timeline-color:#ea580c;">
                            <div class="ps-timeline-title">Employee Allocation</div>
                            <div class="ps-timeline-sub">Mapped employee assignment for execution</div>
                            <div class="ps-timeline-meta">
                                Employee Allocation Status: {{ str_replace('_', ' ', $currentTeamStatus) ?: 'Pending' }}<br>
                                Allocated By: {{ $currentTeamAllocatedByName }}<br>
                                Allocated On: {{ optional($currentTeamAllocatedAt)->format('d M Y h:i A') ?: 'Pending' }}<br>
                                Team Members:
                                {{ $allocatedEmployees->isNotEmpty() ? $allocatedEmployees->pluck('name')->implode(', ') : 'Pending' }}
                            </div>
                        </div>

                        @foreach(($projectItem->projectUpdates ?? collect())->where('type', 'schedule_history')->sortBy('created_at') as $historyUpdate)
                            <div class="ps-timeline-item" style="--timeline-color:#b91c1c;">
                                <div class="ps-timeline-title">Schedule & Status Changed</div>
                                <div class="ps-timeline-sub">Project delivery date and status update history</div>
                                <div class="ps-timeline-meta">
                                    Changed By: {{ $historyUpdate->createdBy?->name ?: 'System' }}<br>
                                    Changed On: {{ optional($historyUpdate->created_at)->format('d M Y h:i A') }}<br>
                                    <div style="margin-top: 8px; padding: 10px; background: #fff5f5; border: 1px solid #fecaca; border-radius: 8px; font-size: 13px; color: #7f1d1d; line-height: 1.5;">
                                        {!! $historyUpdate->content !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </section>

        @if($isContentCalendarDept)
        <section class="ps-tab-panel {{ $activeTab === 'content_calendar' ? 'is-active' : '' }}" data-tab-panel="content_calendar" id="content-calendar-panel">
            <section class="ps-card">
                <div class="ps-card-head" style="background:linear-gradient(135deg,#f0fdf4 0%,#eff6ff 100%);">
                    <div>
                        <div class="ps-card-title" style="display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-calendar2-week" style="color:#059669;font-size:18px;"></i>
                            Content Calendar
                        </div>
                        <div class="ps-card-sub">Google Sheets-integrated content calendar with real-time auto-refresh and live data synchronization.</div>
                    </div>
                    <div id="cc-sync-status" style="display:none;">
                        <div class="cc-sync-badge">
                            <span class="cc-sync-dot"></span>
                            <span id="cc-sync-text">Live</span>
                        </div>
                    </div>
                </div>
                <div class="ps-card-body">
                    {{-- Remarks Display / Approval status banner --}}
                    @if($projectItem->content_calendar_approved)
                        <div class="cc-approval-banner" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
                            <div style="background-color: #dcfce7; color: #166534; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="bi bi-patch-check-fill" style="font-size: 20px;"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="font-weight: 700; color: #166534; font-size: 15px; margin-bottom: 4px;">Content Calendar Approved</div>
                                <div style="color: #1e293b; font-size: 14px; line-height: 1.5;">
                                    <strong>Remarks:</strong> {{ $projectItem->content_calendar_remarks }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Approve Button (only for designing department) --}}
                    @if(!$projectItem->content_calendar_approved && $contentCalendarSheetUrl && auth()->user()?->belongsToDesigningDepartment())
                        <div style="margin-bottom: 20px; display: flex; justify-content: flex-end;">
                            <button type="button" class="ps-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; font-weight: 600; padding: 10px 20px; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); cursor: pointer; display: inline-flex; align-items: center; gap: 8px;" id="cc-approve-btn">
                                <i class="bi bi-patch-check"></i> Approve Content Calendar
                            </button>
                        </div>
                    @endif

                    {{-- Sheet URL Configure Form --}}
                    @if(auth()->user()?->belongsToDesigningDepartment())
                        @if($contentCalendarSheetUrl)
                            <div style="margin-bottom: 20px;">
                                <a href="{{ $contentCalendarSheetUrl }}" target="_blank" class="ps-btn" style="gap:6px; display:inline-flex; align-items:center; background:#fff; border:1px solid #dbe1e8; border-radius:8px; padding:10px 16px; font-weight:600; color:#374151; text-decoration:none;">
                                    <i class="bi bi-box-arrow-up-right"></i> Open Content Calendar Sheet
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="cc-sheet-form">
                            <div class="cc-sheet-form-title">
                                <i class="bi bi-link-45deg" style="font-size:16px;"></i>
                                Google Sheet URL Configure
                            </div>
                            <form method="POST" action="{{ route('projects.content-calendar-sheet.update', $projectItem) }}" class="" id="cc-sheet-url-form">
                                @csrf
                                @method('PATCH')
                                <div class="cc-sheet-input-row">
                                    <input
                                        type="url"
                                        name="content_calendar_sheet_url"
                                        id="cc-sheet-url-input"
                                        class="cc-sheet-input"
                                        placeholder="https://docs.google.com/spreadsheets/d/YOUR_SHEET_ID/edit..."
                                        value="{{ $contentCalendarSheetUrl }}"
                                        @if($projectItem->content_calendar_approved) disabled @endif
                                    >
                                    @if(!$projectItem->content_calendar_approved)
                                        <button type="submit" class="cc-sheet-btn">
                                            <i class="bi bi-save"></i> Save URL
                                        </button>
                                    @else
                                        <button type="button" class="cc-sheet-btn disabled" style="background: #cbd5e1; cursor: not-allowed; border-color: #cbd5e1; color: #64748b;" disabled>
                                            <i class="bi bi-lock-fill"></i> Locked
                                        </button>
                                    @endif
                                    @if($contentCalendarSheetUrl)
                                        <a href="{{ $contentCalendarSheetUrl }}" target="_blank" class="ps-btn" style="gap:6px;">
                                            <i class="bi bi-box-arrow-up-right"></i> Open Sheet
                                        </a>
                                    @endif
                                </div>
                                @error('content_calendar_sheet_url')
                                    <div class="ps-allocate-note" style="color:#b91c1c;margin-top:8px;">{{ $message }}</div>
                                @enderror
                            </form>
                        </div>
                    @endif

                    {{-- Content Calendar Table --}}
                    @if($contentCalendarSheetUrl)
                        <div class="cc-sync-bar">
                            <div class="cc-sync-badge" id="cc-live-badge">
                                <span class="cc-sync-dot"></span>
                                <span id="cc-last-synced">Connecting...</span>
                            </div>
                            <button type="button" class="cc-refresh-btn" id="cc-manual-refresh" onclick="ccFetchSheet()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh Now
                            </button>
                        </div>
                        <div id="cc-table-area">
                            <div class="cc-loading" id="cc-loading">
                                <div class="cc-spinner"></div>
                                <span>Loading Content Calendar data from Google Sheet...</span>
                            </div>
                        </div>
                    @else
                        <div class="cc-empty-state">
                            <div class="cc-empty-icon">📋</div>
                            <div class="cc-empty-title">Content Calendar Not Configured</div>
                            {{--  <div class="cc-empty-sub">Enter your Google Sheet URL above and click Save. The spreadsheet data will automatically display here.</div>  --}}
                        </div>
                    @endif

                </div>
            </section>
        </section>
        @endif

        {{-- Testing Panel Section --}}
        @if($canSeeTestingTab)
        <section class="ps-tab-panel {{ $activeTab === 'testing' ? 'is-active' : '' }}" data-tab-panel="testing">
            <section class="ps-card">
                <div class="ps-card-head" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div>
                        <div class="ps-card-title">🧪 Project Testing Handover</div>
                        <div class="ps-card-sub">Development team testing links, credentials, and QA status logs.</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <button type="button" class="ps-btn" style="background: #dc2626; color: #ffffff; border: none; font-weight: 700; padding: 10px 18px; border-radius: 999px; box-shadow: 0 4px 12px rgba(220,38,38,0.25); cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onclick="openAddBugModal()">
                            <i class="bi bi-bug-fill"></i> + Add Bug
                        </button>
                        <button type="button" class="ps-btn" style="background: linear-gradient(135deg, #fe5f04, #ff8a3d); color: #ffffff; border: none; font-weight: 700; padding: 10px 20px; border-radius: 999px; box-shadow: 0 4px 12px rgba(254,95,4,0.25); cursor: pointer; display: inline-flex; align-items: center; gap: 8px;" onclick="openMoveToTestingModal()">
                            <i class="bi bi-send-check-fill"></i> Move to Testing
                        </button>
                    </div>
                </div>
                <div class="ps-card-body">
                    @forelse($testingDetails as $detail)
                        <div style="background: #ffffff; border-radius: 16px; padding: 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 14px rgba(0,0,0,0.03); position: relative;">
                            <div style="position: absolute; top: 0; left: 0; bottom: 0; width: 5px; background: linear-gradient(180deg, #fe5f04, #ff8745); border-radius: 16px 0 0 16px;"></div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 30px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 12px; font-weight: 700;">
                                        <i class="bi bi-bug-fill"></i> Moved to Testing
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 30px; background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 600;">
                                        <i class="bi bi-clock-history"></i>
                                        {{ $detail->created_at?->format('d M Y, h:i A') }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    @if($detail->testingTl)
                                        <div style="font-size: 13px; font-weight: 600; color: #0284c7;">
                                            <i class="bi bi-person-check-fill" style="margin-right:4px;"></i>Testing TL: {{ $detail->testingTl->name }}
                                        </div>
                                    @endif
                                    <div style="display: inline-flex; align-items: center; gap: 8px; background: #f8fafc; padding: 4px 14px 4px 6px; border-radius: 30px; border: 1px solid #e2e8f0;">
                                        <div style="width: 26px; height: 26px; border-radius: 50%; background: linear-gradient(135deg, #fe5f04 0%, #ff8745 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                            {{ strtoupper(substr($detail->movedBy?->name ?? 'D', 0, 1)) }}
                                        </div>
                                        <span style="font-size: 12.5px; font-weight: 600; color: #334155;">{{ $detail->movedBy?->name ?? 'Developer' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 14px;">
                                @if($detail->credentials)
                                    <div style="background: #f8fafc; padding: 14px 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
                                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569; margin-bottom: 6px;">🔑 Credentials / Access Details</div>
                                        <div style="font-size: 13px; color: #1e293b; font-family: monospace; white-space: pre-line;">{!! e($detail->credentials) !!}</div>
                                    </div>
                                @endif
                            </div>

                            @if($detail->notes)
                                <div style="margin-top: 14px; background: #fff8f3; padding: 14px 16px; border-radius: 12px; border: 1px solid #ffd8bf;">
                                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #c2410c; margin-bottom: 6px;">📝 Developer Remarks / Instructions</div>
                                    <div style="font-size: 13px; color: #334155; line-height: 1.6; white-space: pre-line;">{!! e($detail->notes) !!}</div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div style="text-align: center; padding: 48px 20px; background: #f8fafc; border-radius: 16px; border: 2px dashed #cbd5e1;">
                            <div style="font-size: 40px; margin-bottom: 12px;">🧪</div>
                            <div style="font-size: 16px; font-weight: 700; color: #334155;">Project Not Moved to Testing Yet</div>
                            <div style="font-size: 13px; color: #64748b; margin-top: 4px; max-width: 480px; margin-left: auto; margin-right: auto;">
                                Development team can click <strong>Move to Testing</strong> above to submit testing URL, access credentials, and send notification email to the Testing TL.
                            </div>
                        </div>
                    @endforelse

                    {{-- Reported Bugs List Section --}}
                    @if(isset($bugs) && $bugs->isNotEmpty())
                        <div style="margin-top: 24px; padding-top: 20px; border-top: 2px dashed #cbd5e1;">
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-bug-fill" style="color: #dc2626;"></i> Reported Project Bugs
                                    <span style="font-size: 12px; font-weight: 700; background: #fee2e2; color: #991b1b; padding: 2px 10px; border-radius: 20px;">{{ $bugs->count() }}</span>
                                </div>
                            </div>

                            <div style="display: grid; gap: 12px;">
                                @foreach($bugs as $bug)
                                    <div style="background: #ffffff; border-radius: 12px; padding: 16px; border: 1px solid #fecaca; box-shadow: 0 2px 8px rgba(220,38,38,0.04);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                @php
                                                    $priorityBadge = match($bug->priority) {
                                                        'High' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'icon' => 'bi-exclamation-triangle-fill'],
                                                        'Low' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd', 'icon' => 'bi-info-circle-fill'],
                                                        default => ['bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa', 'icon' => 'bi-exclamation-circle-fill'],
                                                    };
                                                @endphp
                                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; background: {{ $priorityBadge['bg'] }}; color: {{ $priorityBadge['color'] }}; border: 1px solid {{ $priorityBadge['border'] }}; font-size: 11px; font-weight: 800;">
                                                    <i class="bi {{ $priorityBadge['icon'] }}"></i> {{ $bug->priority }} Priority Bug
                                                </span>
                                                <span style="font-size: 12px; color: #64748b;">
                                                    <i class="bi bi-clock-history"></i> {{ $bug->created_at?->format('d M Y, h:i A') }}
                                                </span>
                                            </div>

                                            <div style="font-size: 12px; color: #475569; font-weight: 600;">
                                                👤 Reported by {{ $bug->createdBy?->name ?? 'Tester' }}
                                            </div>
                                        </div>

                                        <div style="font-size: 13.5px; color: #1e293b; line-height: 1.6; white-space: pre-line;">{!! e($bug->description) !!}</div>

                                        @if($bug->attachment_path)
                                            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                                                <a href="{{ asset($bug->attachment_path) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; color: #0284c7; text-decoration: underline;">
                                                    <i class="bi bi-paperclip" style="font-size: 14px;"></i> View / Download Attachment ({{ $bug->attachment_original_name ?: 'File' }})
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </section>
        @endif

    </div>
</div>

{{-- Move to Testing Modal --}}
<div class="ps-modal-overlay" id="move-to-testing-modal-overlay" onclick="closeMoveToTestingModal()"></div>
<div class="ps-modal" id="move-to-testing-modal" style="max-width: 600px; overflow: hidden;">
    {{-- Process Loading Overlay --}}
    <div id="moveToTestingLoadingOverlay" style="display: none; position: absolute; inset: 0; background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; flex-direction: column; gap: 14px; text-align: center; padding: 24px;">
        <div style="width: 48px; height: 48px; border: 4px solid #fed7aa; border-top-color: #fe5f04; border-radius: 50%; animation: psSpin 0.85s linear infinite;"></div>
        <div>
            <div style="font-size: 16px; font-weight: 800; color: #1e293b;">Sending Notification Email...</div>
            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Notifying Testing TL & Team Leads. Please wait a moment...</div>
        </div>
    </div>

    <div class="ps-modal-head">
        <div>
            <div class="ps-card-title" style="display:flex; align-items:center; gap:8px; color:#ea580c;">
                🧪 Move Project to Testing
            </div>
            <div class="ps-card-sub">Submit testing credentials to transfer project to QA/Testing TL.</div>
        </div>
        <button type="button" class="ps-modal-close" onclick="closeMoveToTestingModal()" aria-label="Close modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="ps-modal-body">
        <form id="moveToTestingForm" method="POST" action="{{ route('projects.move-to-testing', $projectItem) }}" onsubmit="handleMoveToTestingSubmit(event, this)">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Select Testing TL <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="testing_tl_id" class="pjd-select" style="width: 100%; min-height: 42px; padding: 8px 12px; border-radius: 10px; border: 1px solid #cbd5e1;" required>
                        <option value="">-- Select Testing TL --</option>
                        @foreach($testingTlUsers as $tlUser)
                            <option value="{{ $tlUser->id }}">{{ $tlUser->name }} ({{ $tlUser->role_display_name }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Testing Credentials / Access Info
                    </label>
                    <textarea name="credentials" rows="3" class="cc-sheet-input" style="width: 100%; min-height: 80px; padding: 10px; border-radius: 10px; font-family: monospace;" placeholder="Username: admin@test.com&#10;Password: Test@1234&#10;Admin URL: https://admin.test.com"></textarea>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Developer Notes / Instructions
                    </label>
                    <textarea name="notes" rows="3" class="cc-sheet-input" style="width: 100%; min-height: 80px; padding: 10px; border-radius: 10px;" placeholder="Please test user authentication, payment checkout flow, and PDF report generation..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                    <button type="button" class="ps-btn" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" onclick="closeMoveToTestingModal()">
                        Cancel
                    </button>
                    <button type="submit" id="btnMoveToTestingSubmit" class="ps-btn" style="background: linear-gradient(135deg, #fe5f04, #ff8745); color: #ffffff; border: none; font-weight: 700; padding: 10px 22px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                        <span id="btnMoveToTestingIcon">🚀</span>
                        <span id="btnMoveToTestingText">Submit & Send Mail to Testing TL</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Add Bug Modal --}}
<div class="ps-modal-overlay" id="add-bug-modal-overlay" onclick="closeAddBugModal()"></div>
<div class="ps-modal" id="add-bug-modal" style="max-width: 560px; overflow: hidden;">
    <div class="ps-modal-head">
        <div>
            <div class="ps-card-title" style="display:flex; align-items:center; gap:8px; color:#dc2626;">
                🐞 Report Project Bug
            </div>
            <div class="ps-card-sub">Log bug description, set priority level, and upload screenshot or attachment.</div>
        </div>
        <button type="button" class="ps-modal-close" onclick="closeAddBugModal()" aria-label="Close modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="ps-modal-body">
        <form method="POST" action="{{ route('projects.bugs.store', $projectItem) }}" enctype="multipart/form-data" onsubmit="handleAddBugSubmit(event, this)">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Priority Level <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="priority" class="pjd-select" style="width: 100%; min-height: 42px; padding: 8px 12px; border-radius: 10px; border: 1px solid #cbd5e1;" required>
                        <option value="High">🔴 High Priority</option>
                        <option value="Medium" selected>🟠 Medium Priority</option>
                        <option value="Low">🔵 Low Priority</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Bug Description <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea name="description" rows="4" class="cc-sheet-input" style="width: 100%; min-height: 100px; padding: 10px; border-radius: 10px;" placeholder="Describe the issue, step-by-step reproduction, or expected vs actual behavior..." required></textarea>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Attachment / File Upload (Screenshot, PDF, Log File)
                    </label>
                    <input type="file" name="attachment" class="cc-sheet-input" style="width: 100%; padding: 8px 10px;" accept="image/*,.pdf,.doc,.docx,.zip">
                    <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Files will be stored in public folder and viewable by team.</div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                    <button type="button" class="ps-btn" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" onclick="closeAddBugModal()">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitBugShow" class="ps-btn" style="background: #dc2626; color: #ffffff; border: none; font-weight: 700; padding: 10px 22px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                        <span id="btnSubmitBugIconShow">🐞</span>
                        <span id="btnSubmitBugTextShow">Submit Bug Report</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes psSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.ps-spin-icon {
    display: inline-block;
    animation: psSpin 0.85s linear infinite;
}
</style>

<script>
function openAddBugModal() {
    const btn = document.getElementById('btnSubmitBugShow');
    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        const icon = document.getElementById('btnSubmitBugIconShow');
        const text = document.getElementById('btnSubmitBugTextShow');
        if (icon) icon.innerHTML = '🐞';
        if (text) text.textContent = 'Submit Bug Report';
    }
    document.getElementById('add-bug-modal-overlay').classList.add('is-open');
    document.getElementById('add-bug-modal').classList.add('is-open');
}
function closeAddBugModal() {
    document.getElementById('add-bug-modal-overlay').classList.remove('is-open');
    document.getElementById('add-bug-modal').classList.remove('is-open');
}
function handleAddBugSubmit(event, form) {
    const btn = document.getElementById('btnSubmitBugShow');
    if (btn) {
        if (btn.disabled) {
            event.preventDefault();
            return false;
        }
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'wait';
        const icon = document.getElementById('btnSubmitBugIconShow');
        const text = document.getElementById('btnSubmitBugTextShow');
        if (icon) icon.innerHTML = '<i class="bi bi-arrow-repeat ps-spin-icon"></i>';
        if (text) text.textContent = ' Submitting Bug Report...';
    }
}
function openMoveToTestingModal() {
    const overlay = document.getElementById('moveToTestingLoadingOverlay');
    if (overlay) overlay.style.display = 'none';
    const btn = document.getElementById('btnMoveToTestingSubmit');
    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        document.getElementById('btnMoveToTestingIcon').innerHTML = '🚀';
        document.getElementById('btnMoveToTestingText').textContent = 'Submit & Send Mail to Testing TL';
    }
    document.getElementById('move-to-testing-modal-overlay').classList.add('is-open');
    document.getElementById('move-to-testing-modal').classList.add('is-open');
}
function closeMoveToTestingModal() {
    document.getElementById('move-to-testing-modal-overlay').classList.remove('is-open');
    document.getElementById('move-to-testing-modal').classList.remove('is-open');
}
function handleMoveToTestingSubmit(event, form) {
    const overlay = document.getElementById('moveToTestingLoadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
    const btn = document.getElementById('btnMoveToTestingSubmit');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.75';
        btn.style.cursor = 'wait';
        const icon = document.getElementById('btnMoveToTestingIcon');
        const text = document.getElementById('btnMoveToTestingText');
        if (icon) icon.innerHTML = '<i class="bi bi-arrow-repeat ps-spin-icon"></i>';
        if (text) text.textContent = ' Sending Notification Mail...';
    }
}
</script>

<div class="ps-modal-overlay {{ $hasUpdateErrors ? 'is-open' : '' }}" data-update-modal-overlay></div>
<div class="ps-modal {{ $hasUpdateErrors ? 'is-open' : '' }}" data-update-modal>
    <div class="ps-modal-head">
        <div>
            <div class="ps-card-title">Add Production Update</div>
            <div class="ps-card-sub">Add production update, meeting update, or weekly update with rich text formatting.</div>
        </div>
        <button type="button" class="ps-modal-close" data-close-update-modal aria-label="Close update modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="ps-modal-body">
        @include('pages.projects.partials.update-form', [
            'updateFormAction' => route('projects.updates.store', $projectItem),
            'updateEditorId' => 'projectUpdateEditor',
            'showProjectSelector' => false,
        ])
    </div>
</div>

{{-- Content Calendar Approval Modal --}}
<div class="ps-modal-overlay" id="cc-approve-modal-overlay"></div>
<div class="ps-modal" id="cc-approve-modal" style="max-width: 500px;">
    <div class="ps-modal-head">
        <div>
            <div class="ps-card-title" style="color: #059669; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-patch-check-fill"></i> Approve Content Calendar
            </div>
            <div class="ps-card-sub">Please enter your mandatory remarks to approve this calendar.</div>
        </div>
        <button type="button" class="ps-modal-close" id="cc-close-approve-modal" aria-label="Close modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <form method="POST" action="{{ route('projects.content-calendar.approve', $projectItem) }}" id="cc-approve-form">
        @csrf
        @method('PATCH')
        <div class="ps-modal-body">
            <div class="ps-input-group" style="margin-bottom: 16px;">
                <label for="cc-remarks-input" class="ps-label" style="font-weight: 600; margin-bottom: 6px; display: block; color: #1e293b;">
                    Remarks <span style="color: #ef4444;">*</span>
                </label>
                <textarea
                    name="remarks"
                    id="cc-remarks-input"
                    class="ps-textarea"
                    rows="4"
                    placeholder="Enter approval remarks (required)..."
                    style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; font-size: 14px; resize: vertical;"
                    required
                ></textarea>
                <div id="cc-remarks-error" style="color: #ef4444; font-size: 13px; margin-top: 4px; display: none;">Remarks are required to approve.</div>
            </div>
        </div>
        <div class="ps-modal-foot" style="display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f1f5f9; background-color: #f8fafc; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
            <button type="button" class="ps-btn text" id="cc-cancel-approve-btn" style="border: 1px solid #cbd5e1; color: #475569; background: white;">Cancel</button>
            <button type="submit" class="ps-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; font-weight: 600;">Confirm & Approve</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const updateModal = document.querySelector('[data-update-modal]');
    const updateModalOverlay = document.querySelector('[data-update-modal-overlay]');
    const updateOpenButtons = document.querySelectorAll('[data-open-update-modal]');
    const updateCloseButtons = document.querySelectorAll('[data-close-update-modal]');

    function setUpdateModalState(isOpen) {
        if (!updateModal || !updateModalOverlay) {
            return;
        }

        updateModal.classList.toggle('is-open', isOpen);
        updateModalOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    updateOpenButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setUpdateModalState(true);
        });
    });

    updateCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setUpdateModalState(false);
        });
    });

    if (updateModalOverlay) {
        updateModalOverlay.addEventListener('click', function () {
            setUpdateModalState(false);
        });
    }

    document.querySelectorAll('[data-tab-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = button.getAttribute('data-tab-target');

            document.querySelectorAll('[data-tab-target]').forEach(function (item) {
                item.classList.toggle('is-active', item === button);
            });

            document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === target);
            });
        });
    });

    if (window.jQuery && window.jQuery.fn.select2) {
        const $selects = window.jQuery('.ps-tl-select, .ps-employee-select');

        $selects.each(function () {
            const $select = window.jQuery(this);

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                placeholder: $select.data('placeholder') || 'Select options',
                closeOnSelect: false,
            });

            $select.next('.select2-container').find('.select2-selection--multiple').addClass('ps-select2-selection');
        });
    }

    if (window.tinymce && document.getElementById('projectUpdateEditor')) {
        window.tinymce.remove('#projectUpdateEditor');
        window.tinymce.init({
            selector: 'textarea#projectUpdateEditor',
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
            menubar: false,
            height: 280,
            plugins: 'lists link table code',
            toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link table | alignleft aligncenter alignright | code',
            content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; }',
            setup: function (editor) {
                editor.on('change keyup', function () {
                    window.tinymce.triggerSave();
                });
            },
        });
    }

    document.querySelectorAll('form[action$="/updates"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (window.tinymce) {
                window.tinymce.triggerSave();
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setUpdateModalState(false);
        }
    });

    // Content Calendar Approve Modal Logic
    const ccApproveBtn = document.getElementById('cc-approve-btn');
    const ccApproveModal = document.getElementById('cc-approve-modal');
    const ccApproveOverlay = document.getElementById('cc-approve-modal-overlay');
    const ccCancelBtn = document.getElementById('cc-cancel-approve-btn');
    const ccCloseBtn = document.getElementById('cc-close-approve-modal');
    const ccApproveForm = document.getElementById('cc-approve-form');
    const ccRemarksInput = document.getElementById('cc-remarks-input');
    const ccRemarksError = document.getElementById('cc-remarks-error');

    function setCcApproveModalState(isOpen) {
        if (!ccApproveModal || !ccApproveOverlay) return;
        ccApproveModal.classList.toggle('is-open', isOpen);
        ccApproveOverlay.classList.toggle('is-open', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
        if (isOpen) {
            ccRemarksInput.value = '';
            ccRemarksError.style.display = 'none';
            ccRemarksInput.focus();
        }
    }

    if (ccApproveBtn) {
        ccApproveBtn.addEventListener('click', function () {
            setCcApproveModalState(true);
        });
    }

    if (ccCancelBtn) {
        ccCancelBtn.addEventListener('click', function () {
            setCcApproveModalState(false);
        });
    }

    if (ccCloseBtn) {
        ccCloseBtn.addEventListener('click', function () {
            setCcApproveModalState(false);
        });
    }

    if (ccApproveOverlay) {
        ccApproveOverlay.addEventListener('click', function () {
            setCcApproveModalState(false);
        });
    }

    if (ccApproveForm) {
        ccApproveForm.addEventListener('submit', function (e) {
            if (!ccRemarksInput.value.trim()) {
                e.preventDefault();
                ccRemarksError.style.display = 'block';
            } else {
                ccRemarksError.style.display = 'none';
            }
        });
    }

    // Also close CC Approve modal on Escape
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setCcApproveModalState(false);
        }
    });
});
</script>

@if($isContentCalendarDept && $contentCalendarSheetUrl)
<script>
(function () {
    'use strict';

    // ── Config ──────────────────────────────────────────────────────
    // Laravel endpoint — no CORS, no third-party proxy needed
    var FETCH_URL    = @json(route('projects.content-calendar-data', $projectItem));
    var REFRESH_SECS = 30;
    var ccTimer      = null;
    var ccCountdown  = REFRESH_SECS;

    // ── DOM refs ─────────────────────────────────────────────────────
    var tableArea  = document.getElementById('cc-table-area');
    var lastSynced = document.getElementById('cc-last-synced');
    var liveBadge  = document.getElementById('cc-live-badge');

    if (!tableArea) { return; }

    // ── CSV parser (handles quoted fields & embedded commas) ─────────
    function parseCsv(text) {
        var rows  = [];
        var lines = text.split(/\r?\n/);
        lines.forEach(function (line) {
            if (line.trim() === '') { return; }
            var row = [], inQuote = false, cell = '';
            for (var i = 0; i < line.length; i++) {
                var ch = line[i];
                if (ch === '"') {
                    if (inQuote && line[i + 1] === '"') { cell += '"'; i++; }
                    else { inQuote = !inQuote; }
                } else if (ch === ',' && !inQuote) {
                    row.push(cell.trim()); cell = '';
                } else {
                    cell += ch;
                }
            }
            row.push(cell.trim());
            rows.push(row);
        });
        return rows;
    }

    // ── Render one cell: auto-hyperlink URLs ─────────────────────────
    function renderCell(val) {
        if (!val || val.trim() === '') {
            return '<span style="color:#cbd5e1;">—</span>';
        }
        var str = String(val);
        if (/^https?:\/\//i.test(str)) {
            var display = str.length > 50 ? str.substring(0, 47) + '…' : str;
            return '<a href="' + escAttr(str) + '" target="_blank" rel="noopener noreferrer" class="cc-cell-link">'
                + escHtml(display) + '</a>';
        }
        return escHtml(str);
    }

    // ── Render parsed rows as a styled table ─────────────────────────
    function renderTable(rows) {
        if (!rows || rows.length === 0) {
            tableArea.innerHTML = '<div class="cc-empty-state">'
                + '<div class="cc-empty-icon">📭</div>'
                + '<div class="cc-empty-title">Sheet is Empty</div>'
                + '<div class="cc-empty-sub">Google Sheet-la data illai. Sheet-la data add pannunga — 30 seconds-la auto-refresh aagum.</div>'
                + '</div>';
            return;
        }

        var headerRow = rows[0];
        var dataRows  = rows.slice(1).filter(function (r) {
            return r.some(function (c) { return c && c.trim() !== ''; });
        });

        var countBadge = '<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">'
            + '<span style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">📊 ' + headerRow.length + ' Columns</span>'
            + '<span style="width:4px;height:4px;border-radius:50%;background:#d1d5db;display:inline-block;"></span>'
            + '<span style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.06em;">' + dataRows.length + ' Rows</span>'
            + '</div>';

        var html = countBadge + '<div class="cc-table-wrap"><table class="cc-table"><thead><tr>';
        headerRow.forEach(function (h, i) {
            html += '<th><span style="display:flex;align-items:center;gap:5px;">'
                + '<span style="opacity:.4;font-size:10px;">' + (i + 1) + '</span>'
                + escHtml(h || '–')
                + '</span></th>';
        });
        html += '</tr></thead><tbody>';

        if (dataRows.length === 0) {
            html += '<tr><td colspan="' + headerRow.length + '" style="text-align:center;color:#94a3b8;padding:32px;font-size:13px;">No data rows found</td></tr>';
        } else {
            dataRows.forEach(function (row) {
                html += '<tr>';
                headerRow.forEach(function (_, ci) {
                    html += '<td>' + renderCell(row[ci] || '') + '</td>';
                });
                html += '</tr>';
            });
        }

        html += '</tbody></table></div>';
        tableArea.innerHTML = html;
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escAttr(s) {
        return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    // ── Core fetch — calls our own Laravel endpoint (no CORS) ────────
    function ccFetchSheet() {
        if (lastSynced) { lastSynced.textContent = 'Syncing…'; }
        if (liveBadge)  { liveBadge.style.borderColor = '#fde68a'; }

        fetch(FETCH_URL, {
            method:      'GET',
            credentials: 'same-origin',
            headers:     { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.error) { throw new Error(data.error); }
            var rows = parseCsv(data.csv);
            renderTable(rows);
            var now = new Date();
            ccCountdown = REFRESH_SECS;
            if (lastSynced) {
                lastSynced.textContent = 'Synced ' + now.toLocaleTimeString();
            }
            if (liveBadge) { liveBadge.style.borderColor = '#a7f3d0'; }
            scheduleCountdown();
        })
        .catch(function (err) {
            tableArea.innerHTML = '<div class="cc-error">'
                + '<strong>⚠ ' + escHtml(err.message) + '</strong><br>'
                + 'Sheet URL confirm pannunga — "Published to web" or "Anyone with link → Viewer" permission venum.<br><br>'
                + '<button onclick="ccFetchSheet()" style="padding:7px 14px;border-radius:8px;border:1px solid #fca5a5;background:#fff;color:#b91c1c;font-size:12px;font-weight:700;cursor:pointer;">↺ Retry</button>'
                + '</div>';
            if (lastSynced) { lastSynced.textContent = 'Sync failed'; }
            if (liveBadge)  { liveBadge.style.borderColor = '#fca5a5'; }
            if (ccTimer) { clearInterval(ccTimer); }
            ccCountdown = REFRESH_SECS;
            scheduleCountdown();
        });
    }

    // ── Countdown ticker ─────────────────────────────────────────────
    function scheduleCountdown() {
        if (ccTimer) { clearInterval(ccTimer); }
        ccTimer = setInterval(function () {
            ccCountdown--;
            if (lastSynced && lastSynced.textContent.indexOf('Synced') !== -1) {
                lastSynced.textContent = lastSynced.textContent.replace(/ · Next.*/, '')
                    + ' · Next in ' + ccCountdown + 's';
            }
            if (ccCountdown <= 0) {
                clearInterval(ccTimer);
                ccFetchSheet();
            }
        }, 1000);
    }

    // ── Tab activation wiring ─────────────────────────────────────────
    var ccPanel  = document.getElementById('content-calendar-panel');
    var ccTabBtn = document.getElementById('cc-tab-btn');

    if (ccPanel && ccPanel.classList.contains('is-active')) {
        ccFetchSheet();
    }

    if (ccTabBtn) {
        ccTabBtn.addEventListener('click', function () {
            if (ccTimer) { clearInterval(ccTimer); }
            ccCountdown = REFRESH_SECS;
            setTimeout(ccFetchSheet, 100);
        });
    }

    window.ccFetchSheet = ccFetchSheet;
})();
</script>
@endif

{{-- Support Portal Style Process Overlay for TL Allocation --}}
<div id="tlAllocationProcessOverlay" class="support-process-overlay" style="display: none;">
    <div class="support-process-card">
        <div class="support-process-icon-wrap">
            <div class="support-process-spinner"></div>
            <i class="bi bi-envelope-paper-fill support-process-icon"></i>
        </div>
        <h4 id="tlProcessOverlayTitle" class="support-process-title">Allocating TL &amp; Sending Email...</h4>
        <p id="tlProcessOverlaySubtitle" class="support-process-subtitle">Please wait while project allocation details are sent to allocated Team Lead(s)...</p>

        <div class="support-progress-wrapper">
            <div class="support-progress-bar">
                <div id="tlSupportProgressFill" class="support-progress-fill"></div>
            </div>
            <div class="support-progress-status">
                <span id="tlSupportProgressText">Connecting to server...</span>
                <span id="tlSupportProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>

{{-- TL Allocation Confirmation Modal --}}
<div id="tlConfirmModal" class="support-process-overlay" style="display: none;">
    <div class="support-process-card" style="width: 520px; max-width: calc(100vw - 32px); text-align: left; padding: 0; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 20px 24px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-person-check-fill"></i> Confirm TL Allocation
                </h3>
                <p style="margin: 4px 0 0; font-size: 12px; opacity: 0.9;">Project #{{ $projectItem->id }} - {{ $projectItem->product_name ?: 'Product' }}</p>
            </div>
            <button type="button" id="tlConfirmCloseBtn" style="background: rgba(255,255,255,0.2); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; font-weight: 700; display: flex; align-items: center; justify-content: center;">✕</button>
        </div>

        <div style="padding: 24px;">
            <p style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 8px;">Are you sure you want to allocate this project to the selected Team Lead(s)?</p>
            <p style="font-size: 13px; color: #475569; margin: 0 0 16px; line-height: 1.5;">
                An email notification with complete project details will be dispatched automatically to:
            </p>
            
            <div id="tlConfirmNamesList" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; font-size: 13px; color: #1e40af; font-weight: 700;">
                <!-- Selected TL names injected dynamically -->
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; font-size: 12px; color: #64748b; line-height: 1.6;">
                <strong>Project:</strong> {{ $projectItem->product_name ?: 'Product' }}<br>
                <strong>Client/Company:</strong> {{ $projectItem->lead?->company_name ?: $projectItem->lead?->contact_name ?: 'N/A' }}<br>
                <strong>Department:</strong> {{ $projectItem->department?->name ?: 'Production' }}
            </div>
        </div>

        <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" id="tlConfirmCancelBtn" style="padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: 13px; font-weight: 700; cursor: pointer;">Cancel</button>
            <button type="button" id="tlConfirmSubmitBtn" style="padding: 10px 22px; border-radius: 10px; border: none; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: #fff; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(30,64,175,0.3);">
                Yes, Allocate &amp; Send Mail
            </button>
        </div>
    </div>
</div>

<style>
.support-process-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    animation: fadeInOverlay 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeInOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}
.support-process-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 40px 36px;
    width: 460px;
    max-width: calc(100vw - 32px);
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
    text-align: center;
    transform: scale(0.95);
    animation: scaleInCard 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes scaleInCard {
    to { transform: scale(1); }
}
.support-process-icon-wrap {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.support-process-spinner {
    position: absolute;
    inset: 0;
    border: 3.5px solid #dbeafe;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spinOverlay 0.8s linear infinite;
}
@keyframes spinOverlay {
    to { transform: rotate(360deg); }
}
.support-process-icon {
    font-size: 32px;
    color: #2563eb;
}
.support-process-title {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px;
}
.support-process-subtitle {
    font-size: 13px;
    color: #64748b;
    margin: 0 0 28px;
    line-height: 1.5;
}
.support-progress-wrapper {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px 20px;
}
.support-progress-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 12px;
}
.support-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #1d4ed8 0%, #3b82f6 100%);
    border-radius: 999px;
    transition: width 0.3s ease;
}
.support-progress-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tlForm = document.getElementById('tl-allocation-form');
    var tlConfirmModal = document.getElementById('tlConfirmModal');
    var tlConfirmCloseBtn = document.getElementById('tlConfirmCloseBtn');
    var tlConfirmCancelBtn = document.getElementById('tlConfirmCancelBtn');
    var tlConfirmSubmitBtn = document.getElementById('tlConfirmSubmitBtn');
    var tlConfirmNamesList = document.getElementById('tlConfirmNamesList');
    var tlOverlay = document.getElementById('tlAllocationProcessOverlay');
    var progressInterval = null;

    if (!tlForm) return;

    tlForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var selectEl = tlForm.querySelector('select[name="tl_user_ids[]"]');
        if (!selectEl) return;

        var selectedOptions = Array.from(selectEl.selectedOptions);
        if (selectedOptions.length === 0) {
            alert('Please select at least one Team Lead (TL) for allocation.');
            return;
        }

        var selectedNames = selectedOptions.map(function (opt) {
            return opt.text.split('|')[0].trim();
        });

        if (tlConfirmNamesList) {
            tlConfirmNamesList.innerHTML = '<div style="margin-bottom:6px; color:#475569; font-weight:600;">📧 Selected Team Lead(s):</div>' +
                selectedNames.map(function (name) {
                    return '<div style="font-size:14px; font-weight:800; color:#1e40af; margin-bottom:2px;">✓ ' + escapeHtml(name) + '</div>';
                }).join('');
        }

        if (tlConfirmModal) tlConfirmModal.style.display = 'flex';
    });

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function closeConfirmModal() {
        if (tlConfirmModal) tlConfirmModal.style.display = 'none';
    }

    if (tlConfirmCloseBtn) tlConfirmCloseBtn.addEventListener('click', closeConfirmModal);
    if (tlConfirmCancelBtn) tlConfirmCancelBtn.addEventListener('click', closeConfirmModal);

    if (tlConfirmSubmitBtn) {
        tlConfirmSubmitBtn.addEventListener('click', function () {
            closeConfirmModal();
            showTlProgressOverlay();
            setTimeout(function () {
                tlForm.submit();
            }, 600);
        });
    }

    function showTlProgressOverlay() {
        var fill = document.getElementById('tlSupportProgressFill');
        var percentText = document.getElementById('tlSupportProgressPercent');
        var statusText = document.getElementById('tlSupportProgressText');
        if (!tlOverlay || !fill || !percentText || !statusText) return;

        tlOverlay.style.display = 'flex';
        var currentProgress = 5;
        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
        statusText.innerText = 'Connecting to server...';

        if (progressInterval) clearInterval(progressInterval);

        progressInterval = setInterval(function() {
            if (currentProgress < 30) {
                currentProgress += Math.floor(Math.random() * 8) + 4;
                statusText.innerText = 'Building project & requirement details...';
            } else if (currentProgress < 75) {
                currentProgress += Math.floor(Math.random() * 6) + 3;
                statusText.innerText = 'Sending email notification to allocated Team Lead(s)...';
            } else if (currentProgress < 94) {
                currentProgress += Math.floor(Math.random() * 3) + 1;
                statusText.innerText = 'Finalizing TL project allocation...';
            }

            if (currentProgress > 94) {
                currentProgress = 94;
            }

            fill.style.width = currentProgress + '%';
            percentText.innerText = currentProgress + '%';
        }, 250);
    }
});
</script>

{{-- Support Portal Style Process Overlay for Team / Employee Allocation (Orange Theme) --}}
<div id="teamAllocationProcessOverlay" class="support-process-overlay" style="display: none;">
    <div class="support-process-card" style="border: 1px solid #ffedd5;">
        <div class="support-process-icon-wrap">
            <div class="support-process-spinner" style="border: 3.5px solid #ffedd5; border-top-color: #ea580c;"></div>
            <i class="bi bi-people-fill support-process-icon" style="color: #ea580c;"></i>
        </div>
        <h4 id="teamProcessOverlayTitle" class="support-process-title">Allocating Team &amp; Sending Email...</h4>
        <p id="teamProcessOverlaySubtitle" class="support-process-subtitle">Please wait while project details are sent to allocated Team Members...</p>

        <div class="support-progress-wrapper" style="background: #fff7ed; border-color: #ffedd5;">
            <div class="support-progress-bar" style="background: #fed7aa;">
                <div id="teamSupportProgressFill" class="support-progress-fill" style="background: linear-gradient(90deg, #ea580c 0%, #f97316 100%);"></div>
            </div>
            <div class="support-progress-status" style="color: #9a3412;">
                <span id="teamSupportProgressText">Connecting to server...</span>
                <span id="teamSupportProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>

{{-- Team Allocation Confirmation Modal (Orange Theme) --}}
<div id="teamConfirmModal" class="support-process-overlay" style="display: none;">
    <div class="support-process-card" style="width: 520px; max-width: calc(100vw - 32px); text-align: left; padding: 0; overflow: hidden; border: 1px solid #ffedd5;">
        <div style="background: linear-gradient(135deg, #ea580c 0%, #f97316 100%); padding: 20px 24px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-people-fill"></i> Confirm Team Allocation
                </h3>
                <p style="margin: 4px 0 0; font-size: 12px; opacity: 0.95;">Project #{{ $projectItem->id }} - {{ $projectItem->product_name ?: 'Product' }}</p>
            </div>
            <button type="button" id="teamConfirmCloseBtn" style="background: rgba(255,255,255,0.2); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; font-weight: 700; display: flex; align-items: center; justify-content: center;">✕</button>
        </div>

        <div style="padding: 24px;">
            <p style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 8px;">Are you sure you want to allocate these Team Members to this project?</p>
            <p style="font-size: 13px; color: #475569; margin: 0 0 16px; line-height: 1.5;">
                An email notification with complete project details will be dispatched automatically to:
            </p>
            
            <div id="teamConfirmNamesList" style="background: #fff7ed; border: 1px solid #ffedd5; border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; font-size: 13px; color: #c2410c; font-weight: 700;">
                <!-- Selected Team Member names injected dynamically -->
            </div>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; font-size: 12px; color: #64748b; line-height: 1.6;">
                <strong>Project:</strong> {{ $projectItem->product_name ?: 'Product' }}<br>
                <strong>Client/Company:</strong> {{ $projectItem->lead?->company_name ?: $projectItem->lead?->contact_name ?: 'N/A' }}<br>
                <strong>Department:</strong> {{ $projectItem->department?->name ?: 'Production' }}
            </div>
        </div>

        <div style="padding: 16px 24px; background: #fff7ed; border-top: 1px solid #ffedd5; display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" id="teamConfirmCancelBtn" style="padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: 13px; font-weight: 700; cursor: pointer;">Cancel</button>
            <button type="button" id="teamConfirmSubmitBtn" style="padding: 10px 22px; border-radius: 10px; border: none; background: linear-gradient(135deg, #ea580c 0%, #f97316 100%); color: #fff; font-size: 13px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 12px rgba(234,88,12,0.35);">
                Yes, Allocate Team &amp; Send Mail
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var teamForm = document.getElementById('employee-allocation-form');
    var teamConfirmModal = document.getElementById('teamConfirmModal');
    var teamConfirmCloseBtn = document.getElementById('teamConfirmCloseBtn');
    var teamConfirmCancelBtn = document.getElementById('teamConfirmCancelBtn');
    var teamConfirmSubmitBtn = document.getElementById('teamConfirmSubmitBtn');
    var teamConfirmNamesList = document.getElementById('teamConfirmNamesList');
    var teamOverlay = document.getElementById('teamAllocationProcessOverlay');
    var teamProgressInterval = null;

    if (!teamForm) return;

    teamForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var selectEl = teamForm.querySelector('select[name="employee_user_ids[]"]');
        if (!selectEl) return;

        var selectedOptions = Array.from(selectEl.selectedOptions);
        if (selectedOptions.length === 0) {
            alert('Please select at least one team member for allocation.');
            return;
        }

        var selectedNames = selectedOptions.map(function (opt) {
            return opt.text.split('|')[0].trim();
        });

        if (teamConfirmNamesList) {
            teamConfirmNamesList.innerHTML = '<div style="margin-bottom:6px; color:#9a3412; font-weight:600;">📧 Selected Team Member(s):</div>' +
                selectedNames.map(function (name) {
                    return '<div style="font-size:14px; font-weight:800; color:#ea580c; margin-bottom:2px;">✓ ' + escapeHtml(name) + '</div>';
                }).join('');
        }

        if (teamConfirmModal) teamConfirmModal.style.display = 'flex';
    });

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function closeTeamConfirmModal() {
        if (teamConfirmModal) teamConfirmModal.style.display = 'none';
    }

    if (teamConfirmCloseBtn) teamConfirmCloseBtn.addEventListener('click', closeTeamConfirmModal);
    if (teamConfirmCancelBtn) teamConfirmCancelBtn.addEventListener('click', closeTeamConfirmModal);

    if (teamConfirmSubmitBtn) {
        teamConfirmSubmitBtn.addEventListener('click', function () {
            closeTeamConfirmModal();
            showTeamProgressOverlay();
            setTimeout(function () {
                teamForm.submit();
            }, 600);
        });
    }

    function showTeamProgressOverlay() {
        var fill = document.getElementById('teamSupportProgressFill');
        var percentText = document.getElementById('teamSupportProgressPercent');
        var statusText = document.getElementById('teamSupportProgressText');
        if (!teamOverlay || !fill || !percentText || !statusText) return;

        teamOverlay.style.display = 'flex';
        var currentProgress = 5;
        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
        statusText.innerText = 'Connecting to server...';

        if (teamProgressInterval) clearInterval(teamProgressInterval);

        teamProgressInterval = setInterval(function() {
            if (currentProgress < 30) {
                currentProgress += Math.floor(Math.random() * 8) + 4;
                statusText.innerText = 'Preparing project details for team...';
            } else if (currentProgress < 75) {
                currentProgress += Math.floor(Math.random() * 6) + 3;
                statusText.innerText = 'Sending email notifications to team members...';
            } else if (currentProgress < 94) {
                currentProgress += Math.floor(Math.random() * 3) + 1;
                statusText.innerText = 'Finalizing team allocation...';
            }

            if (currentProgress > 94) {
                currentProgress = 94;
            }

            fill.style.width = currentProgress + '%';
            percentText.innerText = currentProgress + '%';
        }, 250);
    }
});
</script>
@endpush
