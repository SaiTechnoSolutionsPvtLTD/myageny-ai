@extends('layouts.app')

@section('title', 'Leads')

@push('styles')
<style>
/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   LEAD INDEX â€” Full filter + table
   Theme: Clean white, orange accent, data-rich
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.ld-page { display:flex; flex-direction:column; height:100%; overflow:hidden; background:#f4f5f7; font-family:'Inter',sans-serif; }

/* â”€â”€ Topbar â”€â”€ */
.ld-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; flex-shrink:0; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.ld-page-title { font-size:18px; font-weight:800; color:#121212; }
.ld-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.ld-topbar-right { display:flex; align-items:center; gap:10px; }
.ld-btn { display:flex; align-items:center; gap:6px; padding:8px 18px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; text-decoration:none; transition:all .15s; border:none; }
.ld-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.ld-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); }
.ld-btn-outline { background:#fff; color:#121212; border:1px solid #e1dee3; }
.ld-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

/* â”€â”€ Filter Bar â”€â”€ */
.ld-filter-wrap-outer { background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:60px; z-index:25; }
.ld-filter-bar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding:10px 28px; }
.ld-filter-label { font-size:11px; font-weight:800; color:#9e9e9e; text-transform:uppercase; letter-spacing:.8px; white-space:nowrap; display:flex; align-items:center; gap:5px; margin-right:4px; }
.ld-filter-count { display:inline-flex; align-items:center; justify-content:center; width:17px; height:17px; border-radius:50%; background:#fe5f04; color:#fff; font-size:9px; font-weight:800; }
.ld-filter-group { display:flex; align-items:center; gap:7px; flex-wrap:wrap; flex:1; }
.ld-fw { position:relative; }
.ld-fi { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#9e9e9e; pointer-events:none; width:12px; height:12px; }
.ld-fs, .ld-fi-input {
    appearance:none; -webkit-appearance:none;
    padding:7px 26px 7px 27px;
    background:#f8f8f8; border:1px solid #e1dee3;
    border-radius:8px; font-size:12px; font-weight:600;
    color:#2e2e2e; cursor:pointer; outline:none;
    transition:all .15s; font-family:inherit; min-width:120px;
}
.ld-fs:focus, .ld-fi-input:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.ld-fc { position:absolute; right:7px; top:50%; transform:translateY(-50%); pointer-events:none; color:#9e9e9e; width:11px; height:11px; }
.ld-fi-input { padding:7px 10px 7px 27px; min-width:110px; }
.ld-date-range { display:flex; align-items:center; gap:5px; }
.ld-date-sep { font-size:11px; color:#9e9e9e; font-weight:600; }
.ld-qb { padding:5px 10px; border-radius:7px; background:#f8f8f8; border:1px solid #e1dee3; font-size:11px; font-weight:600; color:#7c7c7c; cursor:pointer; font-family:inherit; transition:all .15s; }
.ld-qb:hover, .ld-qb.active { background:#fff0e6; border-color:#fe5f04; color:#fe5f04; }
.ld-reset-btn { display:flex; align-items:center; gap:5px; padding:7px 12px; border-radius:8px; background:none; border:1px solid #e1dee3; font-size:12px; font-weight:600; color:#9e9e9e; cursor:pointer; font-family:inherit; transition:all .15s; }
.ld-reset-btn:hover { border-color:#dc2626; color:#dc2626; }
.ld-chips-bar { display:none; align-items:center; gap:6px; flex-wrap:wrap; padding:7px 28px; border-top:1px solid #f0eef2; }
.ld-chip { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:20px; background:rgba(254,95,4,.09); border:1px solid rgba(254,95,4,.22); font-size:11px; font-weight:700; color:#fe5f04; cursor:pointer; }
.ld-chip:hover { background:rgba(254,95,4,.15); }
.ld-search-box { position:relative; flex:0 0 auto; }
.ld-search-ico { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#9e9e9e; width:13px; height:13px; pointer-events:none; }
.ld-search-input { padding:7px 12px 7px 32px; border:1px solid #e1dee3; border-radius:8px; font-size:12px; font-family:inherit; outline:none; background:#f8f8f8; color:#121212; transition:all .15s; min-width:200px; }
.ld-search-input:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

/* Filter accordion overrides */
.ld-filter-wrap-outer { background:#f4f5f7; padding:10px 28px; }
.ld-filter-accordion { background:#fff; border:1px solid #e1dee3; border-radius:12px; overflow:hidden; box-shadow:0 8px 20px rgba(18,18,18,.035); }
.ld-filter-toggle { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 16px; cursor:pointer; user-select:none; }
.ld-filter-toggle::-webkit-details-marker { display:none; }
.ld-filter-toggle-left { display:flex; align-items:center; gap:11px; min-width:0; }
.ld-filter-icon { width:32px; height:32px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; background:#fff7ed; color:#fe5f04; flex-shrink:0; }
.ld-filter-title { font-size:14px; font-weight:800; color:#121212; }
.ld-filter-sub { font-size:11px; color:#8e8e8e; margin-top:2px; }
.ld-filter-toggle-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.ld-filter-count { display:none; align-items:center; justify-content:center; min-width:22px; height:22px; padding:0 7px; border-radius:999px; background:#fe5f04; color:#fff; font-size:11px; font-weight:800; }
.ld-filter-chevron { width:30px; height:30px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e1dee3; color:#8e8e8e; transition:all .15s; }
.ld-filter-accordion[open] .ld-filter-chevron { transform:rotate(180deg); color:#fe5f04; border-color:#fed7aa; background:#fff7ed; }
.ld-filter-body { border-top:1px solid #f0eef2; }
.ld-filter-bar { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:12px; padding:14px 16px; align-items:end; }
.ld-filter-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; justify-content:flex-end; }
.ld-fw { display:flex; flex-direction:column; gap:6px; min-width:0; }
.ld-field-label { font-size:11px; font-weight:800; color:#7c7c7c; text-transform:uppercase; letter-spacing:.04em; }
.ld-input-wrap { position:relative; }
.ld-fi { left:10px; font-size:13px; line-height:1; width:auto; height:auto; }
.ld-fs, .ld-fi-input { width:100%; height:38px; padding:8px 30px 8px 31px; border-radius:9px; min-width:0; }
.ld-fc { right:9px; font-size:11px; line-height:1; width:auto; height:auto; }
.ld-date-range { display:grid; grid-template-columns:minmax(0,1fr) auto minmax(0,1fr); gap:8px; align-items:end; grid-column:span 2; }
.ld-date-sep { height:38px; display:flex; align-items:center; color:#9e9e9e; font-size:11px; font-weight:800; text-transform:uppercase; }
.ld-quick-dates { display:flex; gap:6px; flex-wrap:wrap; align-items:center; }
.ld-qb { height:34px; padding:0 11px; border-radius:8px; font-weight:700; }
.ld-reset-btn { display:inline-flex; align-items:center; justify-content:center; height:38px; padding:0 13px; border-radius:9px; background:#fff; color:#7c7c7c; text-decoration:none; font-weight:700; }
.ld-reset-btn:hover { background:#fff5f5; }
.ld-chips-bar { padding:0 16px 14px; border-top:0; }
.ld-search-box { position:relative; flex:auto; }
.ld-search-ico { font-size:13px; width:auto; height:auto; }
.ld-search-input { width:100%; height:38px; border-radius:9px; min-width:0; }

@media (max-width: 1200px) {
    .ld-filter-bar { grid-template-columns:repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 700px) {
    .ld-filter-wrap-outer { padding:10px 18px; }
    .ld-filter-toggle { align-items:flex-start; }
    .ld-filter-bar, .ld-date-range { grid-template-columns:1fr; }
    .ld-date-range { grid-column:span 1; }
    .ld-date-sep { height:auto; }
    .ld-filter-actions { justify-content:flex-start; }
}

/* â”€â”€ Body â”€â”€ */
.ld-body { flex:1; overflow-y:auto; padding:18px 28px 32px; display:flex; flex-direction:column; gap:14px; }
.ld-body::-webkit-scrollbar { width:5px; }
.ld-body::-webkit-scrollbar-thumb { background:#e1dee3; border-radius:3px; }

@keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

/* â”€â”€ Stats â”€â”€ */
.ld-stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; animation:fadeUp .35s ease both; }
.ld-stat {
    position:relative;
    overflow:hidden;
    background:linear-gradient(180deg,#ffffff 0%, #fffdfa 100%);
    border:1px solid #e7e1de;
    border-radius:18px;
    padding:12px 14px 11px;
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    box-shadow:0 10px 24px rgba(18,18,18,.04);
}
.ld-stat::before {
    content:'';
    position:absolute;
    inset:0 0 auto 0;
    height:4px;
    background:var(--stat-color, #fe5f04);
}
.ld-stat:hover {
    transform:translateY(-2px);
    box-shadow:0 16px 32px rgba(18,18,18,.08);
    border-color:#ddd2cb;
}
.ld-stat-top {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
}
.ld-stat-icon {
    width:36px;
    height:36px;
    border-radius:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.45);
}
.ld-stat-trend {
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:5px 9px;
    border-radius:999px;
    background:#fff7f1;
    border:1px solid #fde2cf;
    color:var(--stat-color, #fe5f04);
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
    white-space:nowrap;
}
.ld-stat-label { font-size:10px; font-weight:800; color:#9e9e9e; text-transform:uppercase; letter-spacing:.5px; }
.ld-stat-value { font-size:24px; font-weight:900; color:#121212; line-height:1; margin-top:4px; letter-spacing:-.03em; }
.ld-stat-sub {
    font-size:11px;
    color:#7c7c7c;
    margin-top:8px;
    padding-top:8px;
    border-top:1px solid #f2ece8;
    line-height:1.4;
}

/* â”€â”€ Table Card â”€â”€ */
.ld-table-card { background:#fff; border:1px solid #e1dee3; border-radius:13px; overflow:hidden; animation:fadeUp .35s .1s ease both; }
.ld-table-top { display:flex; justify-content:space-between; align-items:center; padding:13px 18px; border-bottom:1px solid #f0eef2; }
.ld-table-title { font-size:14px; font-weight:700; color:#121212; }
.ld-table-sub { font-size:11px; color:#9e9e9e; margin-top:2px; }
.ld-results { font-size:12px; color:#9e9e9e; }
.ld-results strong { color:#121212; font-weight:700; }

/* Table */
.ld-tbl { width:100%; border-collapse:collapse; }
.ld-tbl th { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:#9e9e9e; padding:9px 13px; text-align:left; border-bottom:1px solid #f0eef2; background:#fafafa; white-space:nowrap; }
.ld-tbl td { padding:12px 13px; font-size:13px; color:#121212; border-bottom:1px solid #f7f6f9; vertical-align:middle; }
.ld-tbl tbody tr:last-child td { border-bottom:none; }
.ld-tbl tbody tr:hover td { background:#fdf9f6; cursor:pointer; }

/* Cells */
.ld-company-cell { display:flex; align-items:center; gap:9px; }
.ld-co-logo { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:#fff; flex-shrink:0; }
.ld-co-name { font-size:13px; font-weight:700; color:#121212; }
.ld-co-contact { font-size:11px; color:#9e9e9e; margin-top:1px; }
.ld-id { font-family:monospace; font-size:11px; color:#9e9e9e; }
.ld-contact-stack { display:flex; flex-direction:column; gap:7px; }
.ld-mobile,
.ld-email-text { display:flex; align-items:center; }
.ld-contact-link {
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:6px 10px;
    border-radius:10px;
    text-decoration:none;
    border:1px solid transparent;
    transition:all .15s ease;
    max-width:100%;
}
.ld-contact-link svg { flex-shrink:0; }
.ld-contact-link span {
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.ld-mobile .ld-contact-link {
    background:#fff7ed;
    border-color:#fed7aa;
    color:#c2410c;
    font-size:12px;
    font-weight:700;
    font-family:monospace;
}
.ld-email-text .ld-contact-link {
    background:#eff6ff;
    border-color:#bfdbfe;
    color:#1d4ed8;
    font-size:11px;
    font-weight:600;
}
.ld-contact-link:hover {
    transform:translateY(-1px);
    box-shadow:0 8px 18px rgba(18,18,18,.08);
}
.ld-mobile .ld-contact-link:hover {
    background:#ffedd5;
    border-color:#fb923c;
    color:#9a3412;
}
.ld-email-text .ld-contact-link:hover {
    background:#dbeafe;
    border-color:#60a5fa;
    color:#1e40af;
}
.ld-deal { font-size:13px; font-weight:700; color:#121212; }
.ld-date { font-size:12px; color:#7c7c7c; }

/* Badges */
.ld-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; border:1px solid transparent; }
.ld-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }

/* Priority badges */
.ld-pri-low    { background:#f0fdf4; color:#16a34a; border-color:#bbf7d0; }
.ld-pri-medium { background:#fffbeb; color:#b45309; border-color:#fde68a; }
.ld-pri-high   { background:#fef2f2; color:#dc2626; border-color:#fecaca; }

/* Owner */
.ld-owner { display:flex; align-items:center; gap:6px; }
.ld-owner-av { width:22px; height:22px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:800; color:#fff; flex-shrink:0; }

/* Branch */
.ld-branch { font-size:11px; color:#7c7c7c; }

/* Actions */
.ld-actions { position:relative; display:flex; justify-content:center; }
.ld-action-trigger {
    width:32px; height:32px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center;
    border:1px solid #e1dee3; background:#fafafa; cursor:pointer; color:#9e9e9e; transition:all .15s;
}
.ld-action-trigger:hover,
.ld-actions.open .ld-action-trigger { border-color:#fe5f04; color:#fe5f04; background:#fff7ed; }
.ld-action-menu {
    position:absolute; top:calc(100% + 8px); right:0; min-width:150px; padding:8px; border-radius:12px;
    border:1px solid #ece7ef; background:#fff; box-shadow:0 18px 36px rgba(15,23,42,.12); z-index:40; display:none;
}
.ld-actions.open .ld-action-menu { display:block; }
.ld-action-item {
    width:100%; display:flex; align-items:center; gap:9px; padding:9px 10px; border:none; background:transparent;
    border-radius:9px; font-size:12px; font-weight:700; color:#475569; text-decoration:none; cursor:pointer; font-family:inherit;
}
.ld-action-item:hover { background:#f8fafc; color:#0f172a; }
.ld-action-item svg { flex-shrink:0; }
.ld-action-item.edit:hover { background:#fff7ed; color:#c2410c; }
.ld-action-item.delete:hover { background:#fef2f2; color:#dc2626; }

/* Empty */
.ld-empty { text-align:center; padding:60px 20px; color:#9e9e9e; }
.ld-empty-icon { font-size:44px; margin-bottom:12px; }
.ld-empty-title { font-size:16px; font-weight:700; color:#7c7c7c; margin-bottom:6px; }

/* Pagination */
.ld-pag { display:flex; justify-content:space-between; align-items:center; padding:12px 18px; border-top:1px solid #f0eef2; }
.ld-pag-info { font-size:12px; color:#9e9e9e; }
.ld-pag-links { display:flex; gap:4px; }
.ld-pag-link { padding:5px 10px; border-radius:7px; font-size:12px; font-weight:600; text-decoration:none; color:#7c7c7c; border:1px solid #e1dee3; transition:all .15s; }
.ld-pag-link:hover, .ld-pag-link.active { background:#fe5f04; color:#fff; border-color:#fe5f04; }
.ld-pag-link.disabled { opacity:.4; pointer-events:none; }

/* Alert */
.ld-alert { padding:12px 16px; border-radius:10px; font-size:13px; display:flex; align-items:center; gap:10px; }
.ld-alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.ld-alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }

/* Delete Modal */
.ld-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:999; align-items:center; justify-content:center; backdrop-filter:blur(4px); }
.ld-modal { background:#fff; border-radius:16px; padding:28px; max-width:380px; width:90%; box-shadow:0 24px 60px rgba(0,0,0,.18); animation:popIn .2s ease; }
@keyframes popIn { from { opacity:0; transform:scale(.94) translateY(8px); } to { opacity:1; transform:scale(1) translateY(0); } }
.ld-modal-icon { width:52px; height:52px; border-radius:14px; background:#fef2f2; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
.ld-modal-title { font-size:17px; font-weight:800; color:#121212; text-align:center; margin-bottom:8px; }
.ld-modal-sub { font-size:13px; color:#7c7c7c; text-align:center; line-height:1.6; margin-bottom:22px; }
.ld-modal-btns { display:flex; gap:10px; }
.ld-modal-btn { flex:1; padding:10px; border-radius:9px; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; border:none; transition:all .15s; }
.ld-modal-cancel { background:#f5f4f6; color:#7c7c7c; border:1px solid #e1dee3; }
.ld-modal-delete { background:linear-gradient(135deg,#dc2626,#ef4444); color:#fff; box-shadow:0 4px 12px rgba(220,38,38,.3); }

@media (max-width: 1200px) {
    .ld-stats-row { grid-template-columns:repeat(2,1fr); }
}

@media (max-width: 700px) {
    .ld-stats-row { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')

<div class="ld-page">

    {{-- Topbar --}}
    <div class="ld-topbar">
        <div>
            <div class="ld-page-title">Leads</div>
            <div class="ld-breadcrumb">Sales > Leads</div>
        </div>
        <div class="ld-topbar-right">
            <a href="{{ route('leads.create') }}" class="ld-btn ld-btn-primary">
                <i class="bi bi-plus-lg"></i>
                New Lead
            </a>
        </div>
    </div>

    {{-- â•â• FILTER BAR â•â• --}}
    <div class="ld-filter-wrap-outer">
        <details class="ld-filter-accordion" id="leadFilters" @if($filterPanelOpen) open @endif>
            <summary class="ld-filter-toggle">
                <div class="ld-filter-toggle-left">
                    <span class="ld-filter-icon"><i class="bi bi-funnel"></i></span>
                    <div>
                        <div class="ld-filter-title">Filters</div>
                        <div class="ld-filter-sub">Default view shows today's leads. Open this panel to search or change date range.</div>
                    </div>
                </div>
                <div class="ld-filter-toggle-right">
                    <span class="ld-filter-count" id="fCount">0</span>
                    <span class="ld-filter-chevron"><i class="bi bi-chevron-down"></i></span>
                </div>
            </summary>

            <div class="ld-filter-body">
                <form method="GET" action="{{ route('leads.index') }}" id="filterForm">
                    <div class="ld-filter-bar">
                        <div class="ld-fw">
                            <label class="ld-field-label" for="f_search">Search</label>
                            <div class="ld-search-box">
                                <i class="bi bi-search ld-search-ico"></i>
                                <input type="text" name="search" class="ld-search-input" id="f_search" placeholder="Company, contact, email..."
                                       value="{{ request('search') }}" oninput="delaySubmit()">
                            </div>
                        </div>

                        <div class="ld-fw">
                            <label class="ld-field-label" for="f_branch">Branch</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-building ld-fi"></i>
                                <select name="branch_id" class="ld-fs" id="f_branch" onchange="autoSubmit()">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected':'' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down ld-fc"></i>
                            </div>
                        </div>

                        <div class="ld-fw">
                            <label class="ld-field-label" for="f_mobile">Mobile</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-telephone ld-fi"></i>
                                <input type="text" name="mobile_number" class="ld-fi-input" id="f_mobile"
                                       placeholder="Mobile no." value="{{ request('mobile_number') }}" oninput="delaySubmit()">
                            </div>
                        </div>

                        <div class="ld-fw">
                            <label class="ld-field-label" for="f_source">Lead Source</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-broadcast ld-fi"></i>
                                <select name="lead_source" class="ld-fs" id="f_source" onchange="autoSubmit()">
                                    <option value="">All Sources</option>
                                    @foreach(\App\Models\Lead::sourceOptions() as $key => $label)
                                        <option value="{{ $key }}" {{ request('lead_source') == $key ? 'selected':'' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down ld-fc"></i>
                            </div>
                        </div>

                        <div class="ld-fw">
                            <label class="ld-field-label" for="f_status">Lead Status</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-kanban ld-fi"></i>
                                <select name="lead_status" class="ld-fs" id="f_status" onchange="autoSubmit()">
                                    <option value="">All Statuses</option>
                                    @foreach(\App\Models\Lead::statusOptions() as $key => $label)
                                        <option value="{{ $key }}" {{ request('lead_status') == $key ? 'selected':'' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down ld-fc"></i>
                            </div>
                        </div>

                        <div class="ld-fw">
                            <label class="ld-field-label" for="f_priority">Priority</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-flag ld-fi"></i>
                                <select name="priority" class="ld-fs" id="f_priority" onchange="autoSubmit()">
                                    <option value="">All Priorities</option>
                                    @foreach(\App\Models\Lead::PRIORITIES as $key => $label)
                                        <option value="{{ $key }}" {{ request('priority') == $key ? 'selected':'' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down ld-fc"></i>
                            </div>
                        </div>

                        <div class="ld-fw">
                            <label class="ld-field-label" for="f_user">Assigned To</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-person ld-fi"></i>
                                <select name="assigned_to" class="ld-fs" id="f_user" onchange="autoSubmit()">
                                    <option value="">All Users</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected':'' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down ld-fc"></i>
                            </div>
                        </div>

                        <div class="ld-fw">
                            <label class="ld-field-label" for="f_product">Product</label>
                            <div class="ld-input-wrap">
                                <i class="bi bi-box-seam ld-fi"></i>
                                <input type="text" name="product_name" class="ld-fi-input" id="f_product"
                                       placeholder="Product name" value="{{ request('product_name') }}" oninput="delaySubmit()">
                            </div>
                        </div>

                        <div class="ld-date-range">
                            <div class="ld-fw">
                                <label class="ld-field-label" for="f_date_from">From Date</label>
                                <div class="ld-input-wrap">
                                    <i class="bi bi-calendar-event ld-fi"></i>
                                    <input type="date" name="date_from" class="ld-fi-input" id="f_date_from"
                                           value="{{ request('date_from') }}" onchange="autoSubmit()">
                                </div>
                            </div>
                            <span class="ld-date-sep">to</span>
                            <div class="ld-fw">
                                <label class="ld-field-label" for="f_date_to">To Date</label>
                                <div class="ld-input-wrap">
                                    <i class="bi bi-calendar-check ld-fi"></i>
                                    <input type="date" name="date_to" class="ld-fi-input" id="f_date_to"
                                           value="{{ request('date_to') }}" onchange="autoSubmit()">
                                </div>
                            </div>
                        </div>

                        <div class="ld-fw">
                            <label class="ld-field-label">Quick Dates</label>
                            <div class="ld-quick-dates">
                                <button type="button" class="ld-qb" id="quickToday" onclick="setQ('today')">Today</button>
                                <button type="button" class="ld-qb" id="quickWeek" onclick="setQ('week')">Week</button>
                                <button type="button" class="ld-qb" id="quickMonth" onclick="setQ('month')">Month</button>
                                <button type="button" class="ld-qb" id="quickYear" onclick="setQ('year')">Year</button>
                            </div>
                        </div>

                        <div class="ld-filter-actions">
                            <button type="submit" class="ld-btn ld-btn-primary">
                                <i class="bi bi-check2-circle"></i>
                                Apply Filter
                            </button>
                            <a href="{{ route('leads.index', ['reset' => 1]) }}" class="ld-reset-btn">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                Reset
                            </a>
                        </div>
                    </div>

                    <div id="chipsBar" class="ld-chips-bar"></div>
                </form>
            </div>
        </details>
    </div>

    @if(false)
    <div class="ld-filter-wrap-outer">
    <form method="GET" action="{{ route('leads.index') }}" id="filterForm">
        <div class="ld-filter-bar">

            {{-- Search --}}
            <div class="ld-search-box">
                <svg class="ld-search-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" class="ld-search-input" placeholder="Company, contact, emailâ€¦"
                       value="{{ request('search') }}" oninput="delaySubmit()">
            </div>

            <span class="ld-filter-label">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>

                <span class="ld-filter-count" id="fCount" style="display:none">0</span>
            </span>

            <div class="ld-filter-group">

                {{-- Branch --}}
                <div class="ld-fw">
                    <svg class="ld-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    <select name="branch_id" class="ld-fs" id="f_branch" onchange="autoSubmit()">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected':'' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <svg class="ld-fc" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>

                {{-- Mobile --}}
                <div class="ld-fw">
                    <svg class="ld-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6"/></svg>
                    <input type="text" name="mobile_number" class="ld-fi-input" id="f_mobile"
                           placeholder="Mobile no." value="{{ request('mobile_number') }}" oninput="delaySubmit()" style="min-width:130px;">
                </div>

                {{-- Lead Source --}}
                <div class="ld-fw">
                    <svg class="ld-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    <select name="lead_source" class="ld-fs" id="f_source" onchange="autoSubmit()">
                        <option value="">All Sources</option>
                        @foreach(\App\Models\Lead::sourceOptions() as $key => $label)
                        <option value="{{ $key }}" {{ request('lead_source') == $key ? 'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <svg class="ld-fc" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>

                {{-- User (Assigned To) --}}
                <div class="ld-fw">
                    <svg class="ld-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <select name="assigned_to" class="ld-fs" id="f_user" onchange="autoSubmit()">
                        <option value="">All Users</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected':'' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <svg class="ld-fc" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </div>

                {{-- Date Range --}}
                <div class="ld-date-range">
                    <div class="ld-fw">
                        <svg class="ld-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <input type="date" name="date_from" class="ld-fi-input" id="f_date_from"
                               value="{{ request('date_from') }}" onchange="updateFilters()" style="min-width:130px;">
                    </div>
                    <span class="ld-date-sep">â†’</span>
                    <div class="ld-fw">
                        <svg class="ld-fi" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <input type="date" name="date_to" class="ld-fi-input" id="f_date_to"
                               value="{{ request('date_to') }}" onchange="updateFilters()" style="min-width:130px;">
                    </div>
                </div>

                {{-- Quick date buttons --}}
                <div style="display:flex;gap:4px;">
                    <button type="button" class="ld-qb" onclick="setQ('today')">Today</button>
                    <button type="button" class="ld-qb" onclick="setQ('week')">Week</button>
                    <button type="button" class="ld-qb" onclick="setQ('month')">Month</button>
                </div>
            </div>

            {{-- Reset --}}
            @if(request()->hasAny(['search','branch_id','mobile_number','lead_source','assigned_to','date_from','date_to']))
            <a href="{{ route('leads.index') }}" class="ld-reset-btn">
                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.36"/></svg>
                Reset
            </a>
            @endif
        </div>

        {{-- Active filter chips --}}
        <div id="chipsBar" class="ld-chips-bar"></div>
    </form>
    </div>

    @endif

    {{-- Body --}}
    <div class="ld-body">

        {{-- Flash --}}
        @if(session('success'))
        <div class="ld-alert ld-alert-success">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            {!! session('success') !!}
        </div>
        @endif
        @if(session('error'))
        <div class="ld-alert ld-alert-error">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
            {!! session('error') !!}
        </div>
        @endif

        {{-- Stats Row --}}
        <div class="ld-stats-row">

            @php
                $statItems = [
                    ['label'=>'Total Leads',         'value'=> $stats['total'],          'sub'=>'Matching current filters', 'color'=>'#fe5f04','bg'=>'#fff0e6','icon'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
                    ['label'=>'Total Product Count', 'value'=> $stats['total_products'], 'sub'=>'Products linked to leads', 'color'=>'#2563eb','bg'=>'#eff6ff','icon'=>'<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>'],
                    ['label'=>'Pipeline Value',      'value'=> '₹'.number_format($stats['pipeline'], 2), 'sub'=>'Value across selected leads', 'color'=>'#7c3aed','bg'=>'#faf5ff','icon'=>'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
                    ['label'=>'Untouched Leads Count',     'value'=> $stats['new'],            'sub'=>'No call updates yet',      'color'=>'#16a34a','bg'=>'#f0fdf4','icon'=>'<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
                ];
            @endphp
            @foreach($statItems as $s)
            <div class="ld-stat" style="--stat-color: {{ $s['color'] }}">
                <div class="ld-stat-top">
                    <div class="ld-stat-icon" style="background:{{ $s['bg'] }}">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="{{ $s['color'] }}" stroke-width="2">{!! $s['icon'] !!}</svg>
                    </div>
                    <div class="ld-stat-trend">Summary</div>
                </div>
                <div class="ld-stat-label">{{ $s['label'] }}</div>
                <div class="ld-stat-value">{{ $s['value'] }}</div>
                <div class="ld-stat-sub">{{ $s['sub'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="ld-table-card">
            <div class="ld-table-top">
                <div>
                    <div class="ld-table-title">Lead Sheet</div>
                    <div class="ld-table-sub">Recent automation runs across workflows</div>
                </div>
                <div class="ld-results">
                    Showing <strong>{{ $leads->firstItem() ?? 0 }}-{{ $leads->lastItem() ?? 0 }}</strong>
                    of <strong>{{ $leads->total() }}</strong> leads
                </div>
            </div>

            @if($leads->isEmpty())
            <div class="ld-empty">
                <div class="ld-empty-icon"><i class="bi bi-clipboard2-data"></i></div>
                <div class="ld-empty-title">No leads found</div>
                <p style="font-size:13px">Try adjusting your filters or <a href="{{ route('leads.create') }}" style="color:#fe5f04;font-weight:700">add a new lead</a>.</p>
            </div>
            @else
            <div style="overflow-x:auto;">
                <div class="table-responsive">


                <table class="ld-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Company / Contact</th>
                            <th>Mobile</th>
                            <th>Source</th>
                            <th>Deal Value</th>
                            <th>Assigned To</th>
                            <th>Branch</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $colors = ['#fe5f04','#7c3aed','#2563eb','#16a34a','#be123c','#0284c7','#b45309','#0f766e']; @endphp
                        @foreach($leads as $lead)
                        @php
                            $sc = $lead->status_color;
                            $avatarColor = $colors[$lead->id % count($colors)];
                        @endphp
                        <tr onclick="window.location='{{ route('leads.show', $lead) }}'">
                            <td><span class="ld-id">LD-{{ str_pad($lead->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                            <td>
                                <div class="ld-company-cell">
                                    <div class="ld-co-logo" style="background:{{ $avatarColor }}">
                                        {{ strtoupper(substr($lead->company_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="ld-co-name">{{ $lead->company_name }}</div>
                                        <div class="ld-co-contact">{{ $lead->contact_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="ld-contact-stack">
                                    <div class="ld-mobile">
                                        <a href="tel:{{ $lead->mobile_number }}" class="ld-contact-link" onclick="event.stopPropagation()">
                                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.71 19a19.5 19.5 0 0 1-5.52-5.51A19.79 19.79 0 0 1 3.08 4.18 2 2 0 0 1 5.06 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L9.91 9.91a16 16 0 0 0 5.71 5.71l.63-.63a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                            <span>{{ $lead->mobile_number }}</span>
                                        </a>
                                    </div>
                                    @if($lead->email)
                                    <div class="ld-email-text">
                                        <a href="mailto:{{ $lead->email }}" class="ld-contact-link" onclick="event.stopPropagation()">
                                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                                <polyline points="22,6 12,13 2,6"/>
                                            </svg>
                                            <span>{{ $lead->email }}</span>
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="ld-badge" style="background:#f5f4f6;color:#555;border-color:#e1dee3;">
                                    {{ $lead->source_label }}
                                </span>
                            </td>
                            <td><span class="ld-deal">{{ number_format($lead->products->sum('total_price'), 2) }}</span></td>
                            <td>
                                @if($lead->assignedTo)
                                <div class="ld-owner">
                                    <div class="ld-owner-av" style="background:{{ $colors[$lead->assigned_to % count($colors)] }}">
                                        {{ strtoupper(substr($lead->assignedTo->name, 0, 1)) }}
                                    </div>
                                    <span style="font-size:12px">{{ $lead->assignedTo->name }}</span>
                                </div>
                                @else
                                <span style="color:#9e9e9e;font-size:12px">Unassigned</span>
                                @endif
                            </td>
                            <td><span class="ld-branch">{{ $lead->branch?->name ?? '-' }}</span></td>
                            <td><span class="ld-date">{{ $lead->lead_date->format('d M Y') }}</span></td>
                            <td onclick="event.stopPropagation()">
                                <div class="ld-actions" data-action-menu>
                                    <button type="button" class="ld-action-trigger" title="Actions" aria-label="Lead actions" onclick="toggleActionMenu(event, this)">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="5" r="1.5"/>
                                            <circle cx="12" cy="12" r="1.5"/>
                                            <circle cx="12" cy="19" r="1.5"/>
                                        </svg>
                                    </button>
                                    <div class="ld-action-menu">
                                    @can('leads.view')
                                    <a href="{{ route('leads.show', $lead) }}" class="ld-action-item" title="View">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span>View</span>
                                    </a>
                                    @endcan
                                    @can('leads.edit')
                                    <a href="{{ route('leads.edit', $lead) }}" class="ld-action-item edit" title="Edit">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        <span>Edit</span>
                                    </a>
                                    @endcan
                                    @can('leads.delete')
                                    <button class="ld-action-item delete" title="Delete"
                                        onclick="confirmDelete({{ $lead->id }}, '{{ addslashes($lead->company_name) }}')">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>
                                        <span>Delete</span>
                                    </button>
                                    @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            {{-- Pagination --}}
            @if($leads->hasPages())
                @include('partials.table-pagination', ['paginator' => $leads])
            @endif
            @if(false && $leads->hasPages())
            <div class="ld-pag">
                <div class="ld-pag-info">Page {{ $leads->currentPage() }} of {{ $leads->lastPage() }}</div>
                <div class="ld-pag-links">
                    <a href="{{ $leads->previousPageUrl() ?? '#' }}" class="ld-pag-link {{ !$leads->onFirstPage() ? '' : 'disabled' }}">â€¹</a>
                    @foreach($leads->getUrlRange(max(1,$leads->currentPage()-2), min($leads->lastPage(),$leads->currentPage()+2)) as $page => $url)
                    <a href="{{ $url }}" class="ld-pag-link {{ $page == $leads->currentPage() ? 'active':'' }}">{{ $page }}</a>
                    @endforeach
                    <a href="{{ $leads->nextPageUrl() ?? '#' }}" class="ld-pag-link {{ $leads->hasMorePages() ? '':'disabled' }}">â€º</a>
                </div>
            </div>
            @endif
            @endif
        </div>

    </div>
</div>

{{-- Delete Modal --}}
<div class="ld-modal-overlay" id="deleteModal">
    <div class="ld-modal">
        <div class="ld-modal-icon">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>
        </div>
        <div class="ld-modal-title">Delete Lead?</div>
        <div class="ld-modal-sub" id="deleteSub">This action cannot be undone.</div>
        <div class="ld-modal-btns">
            <button class="ld-modal-btn ld-modal-cancel" onclick="closeModal()">Cancel</button>
            <form id="deleteForm" method="POST" style="flex:1">
                @csrf @method('DELETE')
                <button type="submit" class="ld-modal-btn ld-modal-delete" style="width:100%">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Filter logic
const todayDate = @json(now()->toDateString());
const defaultFromDate = @json($defaultFromDate);
const defaultToDate = @json($defaultToDate);
const ff = {
    f_search:    { label:'Search',   sel:'#f_search' },
    f_branch:    { label:'Branch',   sel:'#f_branch' },
    f_mobile:    { label:'Mobile',   sel:'#f_mobile' },
    f_source:    { label:'Source',   sel:'#f_source' },
    f_status:    { label:'Status',   sel:'#f_status' },
    f_priority:  { label:'Priority', sel:'#f_priority' },
    f_user:      { label:'User',     sel:'#f_user' },
    f_product:   { label:'Product',  sel:'#f_product' },
    f_date_from: { label:'From',     sel:'#f_date_from' },
    f_date_to:   { label:'To',       sel:'#f_date_to' },
};

function isActiveFilter(id, value) {
    if (!value) return false;
    if (id === 'f_date_from' && value === defaultFromDate) return false;
    if (id === 'f_date_to' && value === defaultToDate) return false;
    return true;
}

function updateFilters() {
    let count = 0;
    const chips = [];

    Object.entries(ff).forEach(([id, cfg]) => {
        const el = document.querySelector(cfg.sel);
        if (!el || !isActiveFilter(id, el.value)) return;

        count++;
        const display = el.tagName === 'SELECT' ? el.options[el.selectedIndex].text : el.value;
        chips.push(`<button type="button" class="ld-chip" onclick="clearF('${id}')">${cfg.label}: ${display} x</button>`);
    });

    const cnt = document.getElementById('fCount');
    if (cnt) {
        cnt.textContent = count;
        cnt.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    const bar = document.getElementById('chipsBar');
    if (bar) {
        bar.style.display = count > 0 ? 'flex' : 'none';
        bar.innerHTML = chips.join('');
    }

    const from = document.getElementById('f_date_from')?.value;
    const to = document.getElementById('f_date_to')?.value;
    
    // Toggle active state for quick buttons
    document.getElementById('quickToday')?.classList.toggle('active', from === todayDate && to === todayDate);
    document.getElementById('quickMonth')?.classList.toggle('active', from === defaultFromDate && to === defaultToDate);

    // Calculate dynamic values for Week and Year active states
    const parts = defaultToDate.split('-').map(Number);
    const today = new Date(parts[0], parts[1] - 1, parts[2]);
    const fmt = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    
    const mon = new Date(today);
    mon.setDate(today.getDate() - today.getDay() + 1);
    const sun = new Date(mon);
    sun.setDate(mon.getDate() + 6);
    const weekStart = fmt(mon);
    const weekEnd = fmt(sun);
    document.getElementById('quickWeek')?.classList.toggle('active', from === weekStart && to === weekEnd);

    const yearStart = `${parts[0]}-01-01`;
    const yearEnd = `${parts[0]}-12-31`;
    document.getElementById('quickYear')?.classList.toggle('active', from === yearStart && to === yearEnd);
}

function clearF(id) {
    const el = document.querySelector(ff[id].sel);
    if (el) {
        el.value = '';
    }
    updateFilters();
    document.getElementById('filterForm').submit();
}

function autoSubmit() {
    updateFilters();
    document.getElementById('filterForm').submit();
}

function delaySubmit() {
    clearTimeout(st);
    updateFilters();
    st = setTimeout(() => document.getElementById('filterForm').submit(), 600);
}

function setQ(p) {
    const parts = defaultToDate.split('-').map(Number);
    const today = new Date(parts[0], parts[1] - 1, parts[2]);
    const fmt = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    const f = document.getElementById('f_date_from');
    const t = document.getElementById('f_date_to');
    if (!f || !t) return;

    let targetFrom = '';
    let targetTo = '';

    if (p === 'today') {
        targetFrom = todayDate;
        targetTo = todayDate;
    } else if (p === 'week') {
        const mon = new Date(today);
        mon.setDate(today.getDate() - today.getDay() + 1);
        const sun = new Date(mon);
        sun.setDate(mon.getDate() + 6);
        targetFrom = fmt(mon);
        targetTo = fmt(sun);
    } else if (p === 'month') {
        targetFrom = defaultFromDate;
        targetTo = defaultToDate;
    } else if (p === 'year') {
        targetFrom = `${parts[0]}-01-01`;
        targetTo = `${parts[0]}-12-31`;
    }

    // Toggle logic: if active date range matches, clear date filter completely
    if (f.value === targetFrom && t.value === targetTo) {
        f.value = '';
        t.value = '';
    } else {
        f.value = targetFrom;
        t.value = targetTo;
    }

    updateFilters();
    document.getElementById('filterForm').submit();
}

function closeActionMenus() {
    document.querySelectorAll('[data-action-menu].open').forEach(menu => menu.classList.remove('open'));
}

function toggleActionMenu(event, button) {
    event.stopPropagation();
    const wrapper = button.closest('[data-action-menu]');
    const shouldOpen = !wrapper.classList.contains('open');
    closeActionMenus();
    if (shouldOpen) {
        wrapper.classList.add('open');
    }
}

// Delete modal
function confirmDelete(id, name) {
    closeActionMenus();
    document.getElementById('deleteSub').textContent = `Delete lead "${name}"? This cannot be undone.`;
    document.getElementById('deleteForm').action = `/leads/${id}`;
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeModal() { document.getElementById('deleteModal').style.display = 'none'; }
document.getElementById('deleteModal').addEventListener('click', e => { if(e.target===document.getElementById('deleteModal')) closeModal(); });
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });
document.addEventListener('click', closeActionMenus);
</script>
@endpush
