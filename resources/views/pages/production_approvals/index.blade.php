@extends('layouts.app')

@section('title', 'Production Approvals')

@push('styles')
<style>
.pa-page { min-height:100%; background:linear-gradient(180deg,#f7f8fb 0%,#f1f5f9 100%); font-family:'Inter',sans-serif; }
.pa-topbar { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:0 28px; height:64px; background:#fff; border-bottom:1px solid #e5e7eb; }
.pa-title { font-size:20px; font-weight:900; color:#111827; }
.pa-breadcrumb { font-size:12px; color:#6b7280; margin-top:3px; }
.pa-chip { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#eef6ff; border:1px solid #cfe1ff; color:#1d4ed8; font-size:12px; font-weight:800; }
.pa-body { padding:22px 28px 34px; display:grid; gap:18px; }
.pa-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
.pa-card { display:block; text-decoration:none; border-radius:22px; border:1px solid #e5e7eb; padding:18px; box-shadow:0 12px 34px rgba(15,23,42,.06); transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
.pa-card:hover { transform:translateY(-2px); box-shadow:0 16px 38px rgba(15,23,42,.1); }
.pa-card.pending { background:linear-gradient(180deg,#fffaf3 0%,#ffffff 100%); border-color:#f6d7a7; }
.pa-card.approval { background:linear-gradient(180deg,#f3fcf5 0%,#ffffff 100%); border-color:#bce6c7; }
.pa-card.rejected { background:linear-gradient(180deg,#fff5f5 0%,#ffffff 100%); border-color:#f5c2c7; }
.pa-card.is-active.pending { box-shadow:0 0 0 3px rgba(245,158,11,.14), 0 16px 38px rgba(15,23,42,.1); }
.pa-card.is-active.approval { box-shadow:0 0 0 3px rgba(34,197,94,.14), 0 16px 38px rgba(15,23,42,.1); }
.pa-card.is-active.rejected { box-shadow:0 0 0 3px rgba(239,68,68,.12), 0 16px 38px rgba(15,23,42,.1); }
.pa-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.pa-card-title { font-size:16px; font-weight:900; color:#111827; }
.pa-card-sub { margin-top:4px; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
.pa-badge { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; border:1px solid transparent; }
.pa-badge.pending { color:#b45309; background:#fff1d6; border-color:#fcd9a2; }
.pa-badge.approval { color:#166534; background:#e9f9ee; border-color:#bce6c7; }
.pa-badge.rejected { color:#b91c1c; background:#fee2e2; border-color:#fecaca; }
.pa-card-count { margin-top:18px; font-size:44px; line-height:1; font-weight:900; letter-spacing:-.05em; color:#111827; }
.pa-table-card { background:#fff; border:1px solid #e5e7eb; border-radius:22px; overflow:hidden; box-shadow:0 12px 34px rgba(15,23,42,.06); }
.pa-table-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid #eef2f7; background:#fcfcfd; }
.pa-table-title { font-size:16px; font-weight:900; color:#111827; }
.pa-table-sub { font-size:12px; color:#6b7280; margin-top:3px; }
.pa-table-wrap { overflow-x:auto; }
.pa-table { width:100%; border-collapse:collapse; }
.pa-table th { padding:12px 14px; text-align:left; border-bottom:1px solid #eef2f7; background:#fafbfc; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; white-space:nowrap; }
.pa-table td { padding:14px; border-bottom:1px solid #f3f4f6; font-size:13px; color:#111827; vertical-align:middle; }
.pa-row { cursor: pointer; }
.pa-table tbody tr.pa-row:hover td { background:#f8fafc; }
.pa-product { font-weight:800; color:#111827; }
.pa-product-link { font-weight:800; color:#111827; text-decoration:none; transition:color .16s ease; }
.pa-product-link:hover { color:#166534; text-decoration:underline; }
.pa-meta { font-size:11px; color:#6b7280; margin-top:3px; }
.pa-status-pill { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; border:1px solid transparent; }
.pa-status-pill.pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.pa-status-pill.approval { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.pa-status-pill.rejected { color:#b91c1c; background:#fef2f2; border-color:#fecaca; }
.pa-action-btn { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; font-size:12px; font-weight:800; text-decoration:none; cursor:pointer; }
.pa-action-btn:hover { background:#dbeafe; }
.pa-action-muted { font-size:12px; color:#9ca3af; }
.pa-empty { padding:40px 20px; text-align:center; color:#6b7280; font-size:13px; }
.pa-flash { padding:12px 14px; border-radius:14px; font-size:13px; font-weight:700; }
.pa-flash.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.pa-modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; padding:16px; background:rgba(15,23,42,.58); z-index:1200; }
.pa-modal.is-open { display:flex; }
.pa-modal-card { width:min(100%, 1480px); height:min(94vh, 980px); display:flex; flex-direction:column; background:#fff; border-radius:24px; border:1px solid #e5e7eb; box-shadow:0 24px 80px rgba(15,23,42,.22); overflow:hidden; }
.pa-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #eef2f7; }
.pa-modal-title { font-size:18px; font-weight:900; color:#111827; }
.pa-modal-sub { margin-top:4px; font-size:12px; color:#6b7280; }
.pa-modal-close { border:none; background:#f8fafc; width:36px; height:36px; border-radius:10px; cursor:pointer; font-size:18px; color:#475569; }
.pa-modal-card form { display:flex; flex-direction:column; flex:1; min-height:0; }
.pa-modal-body { padding:20px; display:grid; gap:20px; overflow-y:auto; flex:1; min-height:0; }
.pa-review-layout { display:grid; gap:20px; align-items:start; }
.pa-remarks-panel { display:grid; gap:14px; padding:18px; border:1px solid #e5e7eb; border-radius:18px; background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%); }
.pa-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; }
.pa-input { width:100%; padding:11px 12px; border:1px solid #dbe1e8; border-radius:12px; background:#f8fafc; font-size:13px; color:#111827; }
.pa-textarea { min-height:160px; resize:vertical; }
.pa-link { display:inline-flex; align-items:center; gap:8px; color:#1d4ed8; font-size:13px; font-weight:700; text-decoration:none; }
.pa-link:hover { text-decoration:underline; }
.pa-custom-panel { display:grid; gap:14px; padding:18px; border:1px solid #e5e7eb; border-radius:18px; background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%); grid-column:1/-1; }
.pa-custom-list { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
.pa-custom-item { padding:12px 14px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
.pa-custom-item-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; }
.pa-custom-item-value { margin-top:6px; font-size:13px; color:#111827; line-height:1.6; word-break:break-word; }
.pa-custom-empty { padding:16px; border:1px dashed #cbd5e1; border-radius:14px; background:#fff; color:#94a3b8; font-size:13px; text-align:center; }
.pa-modal-actions { display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:16px 20px 20px; border-top:1px solid #eef2f7; background:#fff; position:sticky; bottom:0; z-index:2; }
.pa-btn { display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:12px; border:1px solid #d1d5db; background:#fff; color:#111827; font-size:13px; font-weight:800; cursor:pointer; }
.pa-btn.approve { background:#166534; border-color:#166534; color:#fff; }
.pa-btn.reject { background:#b91c1c; border-color:#b91c1c; color:#fff; }
@media (max-width: 1280px) {
    .pa-custom-list { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width: 1100px) {
    .pa-grid { grid-template-columns:1fr; }
}
@media (max-width: 768px) {
    .pa-topbar { height:auto; padding:16px 18px; align-items:flex-start; flex-direction:column; }
    .pa-body { padding:18px 16px 24px; }
    .pa-custom-list { grid-template-columns:1fr; }
    .pa-modal { padding:8px; }
    .pa-modal-card { width:100%; height:96vh; border-radius:18px; }
    .pa-modal-body { padding:16px; }
    .pa-modal-actions { padding:14px 16px 16px; }
}

/* Select2 Premium Overrides */
.select2-container {
    width: 100% !important;
}
.select2-container--open {
    z-index: 9999999 !important;
}
.pa-filter-bar .select2-container--default .select2-selection--single {
    height: 40px !important;
    border: 1px solid #dbe1e8 !important;
    border-radius: 12px !important;
    background: #fff !important;
    display: flex !important;
    align-items: center !important;
}
.pa-filter-bar .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
    padding-left: 12px !important;
    padding-right: 32px !important;
    font-size: 13px !important;
    color: #111827 !important;
    font-weight: 500 !important;
}
.pa-filter-bar .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px !important;
    right: 10px !important;
}
.pa-filter-bar .select2-container--default.select2-container--focus .select2-selection--single,
.pa-filter-bar .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #166534 !important;
    box-shadow: 0 0 0 4px rgba(22,101,52,.12) !important;
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
    background: #166534 !important;
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

/* Accordion Filter Bar */
.pa-filter-accordion {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 22px;
    box-shadow: 0 12px 34px rgba(15,23,42,.04);
    margin-bottom: 16px;
    overflow: hidden;
    transition: all .2s ease;
}
.pa-accordion-header {
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
.pa-accordion-header:hover {
    background: #fafafa;
}
.pa-accordion-title {
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: .05em;
    font-size: 13px;
    color: #374151;
}
.pa-accordion-arrow {
    font-size: 14px;
    color: #6b7280;
    transition: transform .2s ease;
}
.pa-filter-accordion.is-active .pa-accordion-arrow {
    transform: rotate(180deg);
}
.pa-accordion-body {
    padding: 0 24px 24px;
    border-top: 1px solid #f3f4f6;
}
.pa-filter-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 999px;
    background: #e9f9ee;
    color: #166534;
    font-size: 11px;
    font-weight: 800;
    margin-left: 8px;
    border: 1px solid #bce6c7;
    text-transform: uppercase;
    letter-spacing: .05em;
}
</style>
@endpush

@section('content')
<div class="pa-page">
    <div class="pa-topbar">
        <div>
            <div class="pa-title">Production Approvals</div>
            <div class="pa-breadcrumb">CRM Dashboard > Production Approvals</div>
        </div>
        <div class="pa-chip">Approval Stage Tracker</div>
    </div>

    <div class="pa-body">
        @if(session('success'))
            <div class="pa-flash success" style="margin-bottom: 20px;">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="pa-flash rejected" style="margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $hasActiveFilters = request()->filled('start_date') ||
                                request()->filled('end_date') ||
                                request()->filled('product_id') ||
                                request()->filled('status') ||
                                request()->filled('user_id') ||
                                request()->filled('department_id');
        @endphp

        {{-- Filter Accordion --}}
        <div class="pa-filter-accordion {{ $hasActiveFilters ? 'is-active' : '' }}">
            <button type="button" class="pa-accordion-header">
                <span class="pa-accordion-title">
                    <i class="bi bi-funnel-fill" style="color: #166534;"></i> FILTER OPTIONS
                    @if($hasActiveFilters)
                        <span class="pa-filter-badge">Active</span>
                    @endif
                </span>
                <span class="pa-accordion-arrow">
                    <i class="bi bi-chevron-down"></i>
                </span>
            </button>
            <div class="pa-accordion-body" style="{{ $hasActiveFilters ? 'display: block;' : 'display: none;' }}">
                <div class="pa-filter-bar" style="padding-top: 20px;">
                    <form method="GET" action="{{ route('production-approvals.index') }}" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; align-items:flex-end;">
                        <input type="hidden" name="bucket" value="{{ $selectedBucket }}">

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Start Date</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="pa-input" style="padding:9px 12px; background:#fff;">
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">End Date</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="pa-input" style="padding:9px 12px; background:#fff;">
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Product</label>
                            <div style="position:relative;">
                                <select name="product_id" class="pa-input select2" style="padding:9px 12px; background:#fff;">
                                    <option value="">All Products</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->product_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Status</label>
                            <div style="position:relative;">
                                <select name="status" class="pa-input select2" style="padding:9px 12px; background:#fff;">
                                    <option value="">All Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approval" {{ request('status') == 'approval' ? 'selected' : '' }}>Approval</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Actioned User</label>
                            <div style="position:relative;">
                                <select name="user_id" class="pa-input select2" style="padding:9px 12px; background:#fff;">
                                    <option value="">All Users</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <label style="font-size:11px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Department</label>
                            <div style="position:relative;">
                                <select name="department_id" class="pa-input select2" style="padding:9px 12px; background:#fff;">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="display:flex; gap:10px; grid-column: 1 / -1; justify-content: flex-end; margin-top:4px;">
                            <button type="submit" class="pa-btn approve" style="padding:10px 20px; font-size:13px; font-weight:800;">Apply Filters</button>
                            <a href="{{ route('production-approvals.index', ['bucket' => $selectedBucket]) }}" class="pa-btn" style="padding:10px 20px; font-size:13px; font-weight:800; text-decoration:none; line-height:18px;">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <section class="pa-grid">
            @foreach($cards as $key => $card)
                <a
                    href="{{ route('production-approvals.index', ['bucket' => $key]) }}"
                    class="pa-card {{ $key }} {{ $selectedBucket === $key ? 'is-active' : '' }}"
                >
                    <div class="pa-card-head">
                        <div>
                            <div class="pa-card-title">{{ $card['title'] }}</div>
                            <div class="pa-card-sub">{{ $card['status_label'] }}</div>
                        </div>
                        <span class="pa-badge {{ $key }}">{{ $card['status_label'] }}</span>
                    </div>
                    <div class="pa-card-count">{{ number_format($card['count']) }}</div>
                </a>
            @endforeach
        </section>

        <section class="pa-table-card">
            <div class="pa-table-head">
                <div>
                    <div class="pa-table-title">{{ $selectedCard['title'] }} List</div>
                    <div class="pa-table-sub">Showing items currently in {{ strtolower($selectedCard['status_label']) }}.</div>
                </div>
                <span class="pa-badge {{ $selectedBucket }}">{{ number_format($selectedCard['count']) }} Items</span>
            </div>

            @if($selectedCard['items']->isNotEmpty())
                <div class="pa-table-wrap">
                    <table class="pa-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Lead / Company</th>
                                <th>Department</th>
                                <th>OVP Reviewed</th>
                                <th>Status</th>
                                <th>Actioned By</th>
                                <th>Actioned On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedCard['items'] as $item)
                                @php
                                    $statusBucket = match (strtolower((string) $item->production_approval_status)) {
                                        'approval', 'approved' => 'approval',
                                        'rejected', 'reject' => 'rejected',
                                        default => 'pending',
                                    };
                                    $displayCompany = $item->company_name ?: ($item->lead?->company_name ?: 'No company');
                                    $displayClient = $item->client_name ?: ($item->lead?->contact_name ?: 'No client');
                                @endphp
                                <tr class="pa-row" data-href="{{ $item->lead_id ? route('leads.show', $item->lead_id) : '#' }}">
                                    <td>
                                        <div class="pa-product">
                                            @if($item->lead_id)
                                                <a href="{{ route('leads.show', $item->lead_id) }}" class="pa-product-link" title="View Lead Details">
                                                    {{ $item->product_name }}
                                                </a>
                                            @else
                                                {{ $item->product_name }}
                                            @endif
                                        </div>
                                        @if(auth()->user()->canViewBudgetApprovalDetails() && $item->lead_budget_amount)
                                            <div style="font-size: 11px; font-weight: 700; color: #166534; margin-top: 4px; display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; background: #e9f9ee; border-radius: 4px; border: 1px solid #bce6c7;">
                                                💰 Budget: ₹{{ number_format($item->lead_budget_amount, 2) }} ({{ $item->budget_amount_type }})
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $displayCompany }}
                                        <div class="pa-meta">{{ $displayClient }}</div>
                                    </td>
                                    <td>{{ $item->department?->name ?: 'No department' }}</td>
                                    <td>
                                        {{ optional($item->reviewed_at)->format('d M Y h:i A') ?: 'Not reviewed' }}
                                        <div class="pa-meta">{{ $item->reviewedBy?->name ?: 'OVP pending' }}</div>
                                    </td>
                                    <td><span class="pa-status-pill {{ $statusBucket }}">{{ str_replace('_', ' ', (string) $item->production_approval_status) }}</span></td>
                                    <td>{{ $item->productionApprovalReviewedBy?->name ?: 'Pending' }}</td>
                                    <td>{{ optional($item->production_approval_reviewed_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                                    <td>
                                        @if($statusBucket === 'pending')
                                            <button
                                                type="button"
                                                class="pa-action-btn"
                                                data-pa-open
                                                data-action="{{ route('production-approvals.review', $item) }}"
                                                data-product-name="{{ $item->product_name }}"
                                                data-company-name="{{ $displayCompany }}"
                                                data-approval-remarks="{{ $item->production_approval_remarks }}"
                                                data-custom-form='@json($item->custom_form_data ?? [])'
                                                data-is-budget-approval-needed="{{ optional($item->product)->is_budget_approval_needed ? 1 : 0 }}"
                                            >
                                                Review
                                            </button>
                                        @else
                                            <span class="pa-action-muted">Completed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($selectedCard['items']->hasPages())
                    @include('partials.table-pagination', ['paginator' => $selectedCard['items']])
                @endif
            @else
                <div class="pa-empty">No production approval items are available in this status right now.</div>
            @endif
        </section>
    </div>

    <div class="pa-modal" id="pa-review-modal">
        <div class="pa-modal-card">
            <div class="pa-modal-head">
                <div>
                    <div class="pa-modal-title">Production Approval Review</div>
                    <div class="pa-modal-sub">Approve or reject this request. The action user, date, and time will be saved automatically.</div>
                </div>
                <button type="button" class="pa-modal-close" data-pa-close>&times;</button>
            </div>

            <form method="POST" id="pa-review-form">
                @csrf
                <input type="hidden" name="decision" id="pa-decision-input">

                <div class="pa-modal-body">
                    <div class="pa-review-layout">
                        <div class="pa-custom-panel">
                            <div id="pa-custom-form-wrap" class="pa-custom-list"></div>
                        </div>

                        <div class="pa-remarks-panel" id="pa-budget-panel" style="display: none;">
                            <label class="pa-label" style="color: #111827; margin-bottom: 8px; display: block; font-weight: 900;">Budget Approval Details</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div style="display:flex; flex-direction:column; gap:6px;">
                                    <label class="pa-label" for="pa-budget-amount">Lead Budget Amount <span style="color: #dc2626;">*</span></label>
                                    <input type="number" id="pa-budget-amount" name="lead_budget_amount" min="0" step="0.01" class="pa-input" placeholder="Enter budget amount">
                                </div>
                                <div style="display:flex; flex-direction:column; gap:6px;">
                                    <label class="pa-label" for="pa-budget-type">Budget Amount Type <span style="color: #dc2626;">*</span></label>
                                    <select id="pa-budget-type" name="budget_amount_type" class="pa-input">
                                        <option value="">— Select type —</option>
                                        <option value="Daily">Daily</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:6px; margin-top:12px; display:none;" id="pa-budget-custom-wrap">
                                <label class="pa-label" for="pa-budget-custom">Custom Budget Type <span style="color: #dc2626;">*</span></label>
                                <input type="text" id="pa-budget-custom" name="budget_amount_type_custom" class="pa-input" placeholder="Type custom budget type">
                            </div>
                        </div>

                        <div class="pa-remarks-panel">
                            <label class="pa-label" for="pa-approval-remarks">Production Approval Remarks <span style="color:#dc2626;">*</span></label>
                            <textarea id="pa-approval-remarks" name="production_approval_remarks" class="pa-input pa-textarea" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="pa-modal-actions">
                    <button type="button" class="pa-btn" data-pa-close>Cancel</button>
                    <button type="submit" class="pa-btn reject" data-decision="rejected">Reject</button>
                    <button type="submit" class="pa-btn approve" data-decision="approval">Approve</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Production Approval Confirmation Modal (Approval) --}}
    <div class="pa-modal" id="pa-confirm-modal">
        <div class="pa-modal-card" style="width: min(100%, 520px); height: auto; max-height: 90vh;">
            <div class="pa-modal-head" style="background:#f0fdf4; border-bottom:1px solid #bbf7d0;">
                <div>
                    <div class="pa-modal-title" style="color:#166534; display:flex; align-items:center; gap:8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Confirm Production Approval
                    </div>
                </div>
                <button type="button" class="pa-modal-close" id="pa-confirm-close">&times;</button>
            </div>
            <div style="padding: 24px;">
                <p style="font-size: 15px; font-weight: 700; color: #111827; margin: 0 0 8px;">Are you sure you want to approve this production request?</p>
                <p style="font-size: 13px; color: #4b5563; margin: 0 0 16px; line-height: 1.5;">
                    An email notification will be dispatched automatically to:<br>
                    <span style="display:inline-flex; align-items:center; gap:6px; margin-top:8px; font-weight:700; color:#047857; background:#ecfdf5; padding:6px 12px; border-radius:8px; border:1px solid #a7f3d0; font-size:12px;">
                        📧 Lead Sales Person &amp; projects@saitechnosolutions.net
                    </span>
                </p>
                <div id="pa-confirm-details-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; font-size:13px; color:#334155; line-height:1.6;">
                    <!-- Filled dynamically by JS -->
                </div>
            </div>
            <div class="pa-modal-actions" style="justify-content: flex-end; gap: 10px; padding: 16px 24px 20px;">
                <button type="button" class="pa-btn" id="pa-confirm-cancel">Cancel</button>
                <button type="button" class="pa-btn approve" id="pa-confirm-submit-btn" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color:#fff; font-weight:800; padding:10px 20px;">
                    Yes, Approve &amp; Send Mail
                </button>
            </div>
        </div>
    </div>

    {{-- Production Approval Confirmation Modal (Rejection) --}}
    <div class="pa-modal" id="pa-reject-confirm-modal">
        <div class="pa-modal-card" style="width: min(100%, 520px); height: auto; max-height: 90vh;">
            <div class="pa-modal-head" style="background:#fef2f2; border-bottom:1px solid #fecaca;">
                <div>
                    <div class="pa-modal-title" style="color:#b91c1c; display:flex; align-items:center; gap:8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        Confirm Production Rejection
                    </div>
                </div>
                <button type="button" class="pa-modal-close" id="pa-reject-confirm-close">&times;</button>
            </div>
            <div style="padding: 24px;">
                <p style="font-size: 15px; font-weight: 700; color: #111827; margin: 0 0 8px;">Are you sure you want to REJECT this production approval request?</p>
                <p style="font-size: 13px; color: #4b5563; margin: 0 0 16px; line-height: 1.5;">
                    An email notification will be dispatched automatically to:<br>
                    <span style="display:inline-flex; align-items:center; gap:6px; margin-top:8px; font-weight:700; color:#b91c1c; background:#fef2f2; padding:6px 12px; border-radius:8px; border:1px solid #fecaca; font-size:11px; line-height:1.4;">
                        📧 customersuccessteam.sts@gmail.com, customersuccess@saitechnosolutions.net &amp; Sales Person
                    </span>
                </p>
                <div id="pa-reject-confirm-details-box" style="background:#fff1f2; border:1px solid #fecdd3; border-radius:12px; padding:14px 16px; font-size:13px; color:#881337; line-height:1.6;">
                    <!-- Filled dynamically by JS -->
                </div>
            </div>
            <div class="pa-modal-actions" style="justify-content: flex-end; gap: 10px; padding: 16px 24px 20px;">
                <button type="button" class="pa-btn" id="pa-reject-confirm-cancel">Cancel</button>
                <button type="button" class="pa-btn reject" id="pa-reject-confirm-submit-btn" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color:#fff; font-weight:800; padding:10px 20px;">
                    Yes, Reject &amp; Send Mail
                </button>
            </div>
        </div>
    </div>

    {{-- Support Portal Style Preloader Overlay --}}
    <div id="paLoadingOverlay" class="support-process-overlay" style="display: none;">
        <div class="support-process-card">
            <div class="support-process-icon-wrap">
                <div id="paSpinner" class="support-process-spinner"></div>
                <i id="paIcon" class="bi bi-envelope-paper-fill support-process-icon"></i>
            </div>
            <h4 id="paLoadingTitle" class="support-process-title">Processing Request &amp; Sending Email...</h4>
            <p id="paLoadingText" class="support-process-subtitle">Please wait while status is updated and email notifications are dispatched...</p>

            <div class="support-progress-wrapper">
                <div class="support-progress-bar">
                    <div id="paProgressFill" class="support-progress-fill"></div>
                </div>
                <div class="support-progress-status">
                    <span id="paProgressText">Connecting to server...</span>
                    <span id="paProgressPercent">0%</span>
                </div>
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
        border: 3.5px solid #e2e8f0;
        border-top-color: #166534;
        border-radius: 50%;
        animation: spinOverlay 0.8s linear infinite;
    }
    @keyframes spinOverlay {
        to { transform: rotate(360deg); }
    }
    .support-process-icon {
        font-size: 32px;
        color: #166534;
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
        background: linear-gradient(90deg, #166534 0%, #22c55e 100%);
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
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Accordion Toggle
    if (window.jQuery) {
        window.jQuery('.pa-accordion-header').on('click', function() {
            const $accordion = window.jQuery(this).closest('.pa-filter-accordion');
            const $body = $accordion.find('.pa-accordion-body');
            $accordion.toggleClass('is-active');
            $body.slideToggle(200);
        });
    }

    const modal = document.getElementById('pa-review-modal');
    const form = document.getElementById('pa-review-form');

    if (!modal || !form) {
        return;
    }

    const decisionInput = document.getElementById('pa-decision-input');
    const approvalRemarksInput = document.getElementById('pa-approval-remarks');
    const customFormWrap = document.getElementById('pa-custom-form-wrap');

    const confirmModal = document.getElementById('pa-confirm-modal');
    const confirmCloseBtn = document.getElementById('pa-confirm-close');
    const confirmCancelBtn = document.getElementById('pa-confirm-cancel');
    const confirmSubmitBtn = document.getElementById('pa-confirm-submit-btn');
    const confirmDetailsBox = document.getElementById('pa-confirm-details-box');

    const rejectConfirmModal = document.getElementById('pa-reject-confirm-modal');
    const rejectConfirmCloseBtn = document.getElementById('pa-reject-confirm-close');
    const rejectConfirmCancelBtn = document.getElementById('pa-reject-confirm-cancel');
    const rejectConfirmSubmitBtn = document.getElementById('pa-reject-confirm-submit-btn');
    const rejectConfirmDetailsBox = document.getElementById('pa-reject-confirm-details-box');

    const loadingOverlay = document.getElementById('paLoadingOverlay');
    let paProgressInterval = null;

    let currentButton = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderCustomFormData(entries) {
        if (!customFormWrap) {
            return;
        }

        if (!Array.isArray(entries) || entries.length === 0) {
            customFormWrap.innerHTML = '<div class="pa-custom-empty">No customized form data available for this request.</div>';
            return;
        }

        customFormWrap.innerHTML = entries.map(function (entry) {
            let valueHtml = '-';

            if (entry && entry.type === 'file' && entry.value && typeof entry.value === 'object') {
                valueHtml = '<a href="' + escapeHtml(entry.value.url || '#') + '" target="_blank" rel="noopener" class="pa-link">' + escapeHtml(entry.value.name || 'View file') + '</a>';
            } else if (Array.isArray(entry && entry.value)) {
                valueHtml = escapeHtml(entry.value.join(', '));
            } else if (entry && entry.value !== null && entry.value !== undefined && entry.value !== '') {
                valueHtml = escapeHtml(String(entry.value));
            }

            return '<div class="pa-custom-item">' +
                '<div class="pa-custom-item-label">' + escapeHtml(entry.label || entry.field_name || 'Field') + '</div>' +
                '<div class="pa-custom-item-value">' + valueHtml + '</div>' +
            '</div>';
        }).join('');
    }

    const budgetPanel = document.getElementById('pa-budget-panel');
    const budgetAmount = document.getElementById('pa-budget-amount');
    const budgetType = document.getElementById('pa-budget-type');
    const budgetCustom = document.getElementById('pa-budget-custom');
    const budgetCustomWrap = document.getElementById('pa-budget-custom-wrap');

    budgetType.addEventListener('change', function () {
        if (this.value === 'custom') {
            budgetCustomWrap.style.display = 'block';
            if (decisionInput.value === 'approval') {
                budgetCustom.setAttribute('required', 'required');
            }
        } else {
            budgetCustomWrap.style.display = 'none';
            budgetCustom.removeAttribute('required');
        }
    });

    function openModal(button) {
        currentButton = button;
        form.action = button.dataset.action || '';
        approvalRemarksInput.value = button.dataset.approvalRemarks || '';
        renderCustomFormData(JSON.parse(button.dataset.customForm || '[]'));
        decisionInput.value = '';

        const isBudgetNeeded = button.dataset.isBudgetApprovalNeeded === '1';
        form.dataset.isBudgetApprovalNeeded = button.dataset.isBudgetApprovalNeeded;

        if (isBudgetNeeded) {
            budgetPanel.style.display = 'block';
        } else {
            budgetPanel.style.display = 'none';
        }

        modal.classList.add('is-open');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        form.reset();
        decisionInput.value = '';
        form.removeAttribute('data-is-budget-approval-needed');
        budgetPanel.style.display = 'none';
        budgetCustomWrap.style.display = 'none';
        budgetAmount.removeAttribute('required');
        budgetType.removeAttribute('required');
        budgetCustom.removeAttribute('required');
        renderCustomFormData([]);
    }

    document.querySelectorAll('[data-pa-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button);
        });
    });

    document.querySelectorAll('[data-pa-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    form.querySelectorAll('[data-decision]').forEach(function (button) {
        button.addEventListener('click', function () {
            decisionInput.value = button.dataset.decision || '';

            if (button.dataset.decision === 'rejected') {
                budgetAmount.removeAttribute('required');
                budgetType.removeAttribute('required');
                budgetCustom.removeAttribute('required');
            } else {
                if (form.dataset.isBudgetApprovalNeeded === '1') {
                    budgetAmount.setAttribute('required', 'required');
                    budgetType.setAttribute('required', 'required');
                    if (budgetType.value === 'custom') {
                        budgetCustom.setAttribute('required', 'required');
                    }
                }
            }
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var prodName = (currentButton && currentButton.dataset.productName) || 'Product';
        var companyName = (currentButton && currentButton.dataset.companyName) || '';
        var remarks = approvalRemarksInput.value || 'None';

        if (decisionInput.value === 'approval') {
            var bAmount = budgetAmount.value;
            var bType = budgetType.value === 'custom' ? budgetCustom.value : budgetType.value;

            var detailsHtml = '<div><strong>Product:</strong> ' + escapeHtml(prodName) + '</div>';
            if (companyName) detailsHtml += '<div><strong>Client/Company:</strong> ' + escapeHtml(companyName) + '</div>';
            if (bAmount) detailsHtml += '<div><strong>Approved Budget:</strong> ₹' + escapeHtml(bAmount) + ' (' + escapeHtml(bType) + ')</div>';
            detailsHtml += '<div><strong>Approval Remarks:</strong> ' + escapeHtml(remarks) + '</div>';

            if (confirmDetailsBox) confirmDetailsBox.innerHTML = detailsHtml;
            if (confirmModal) confirmModal.classList.add('is-open');
        } else if (decisionInput.value === 'rejected') {
            var rejectDetailsHtml = '<div><strong>Product:</strong> ' + escapeHtml(prodName) + '</div>';
            if (companyName) rejectDetailsHtml += '<div><strong>Client/Company:</strong> ' + escapeHtml(companyName) + '</div>';
            rejectDetailsHtml += '<div style="margin-top:6px; color:#b91c1c; font-weight:700;"><strong>Rejection Reason:</strong> "' + escapeHtml(remarks) + '"</div>';

            if (rejectConfirmDetailsBox) rejectConfirmDetailsBox.innerHTML = rejectDetailsHtml;
            if (rejectConfirmModal) rejectConfirmModal.classList.add('is-open');
        }
    });

    if (confirmSubmitBtn) {
        confirmSubmitBtn.addEventListener('click', function () {
            if (confirmModal) confirmModal.classList.remove('is-open');
            if (modal) modal.classList.remove('is-open');
            showPaProgressOverlay('approval');
            setTimeout(function () {
                form.submit();
            }, 600);
        });
    }

    if (rejectConfirmSubmitBtn) {
        rejectConfirmSubmitBtn.addEventListener('click', function () {
            if (rejectConfirmModal) rejectConfirmModal.classList.remove('is-open');
            if (modal) modal.classList.remove('is-open');
            showPaProgressOverlay('rejection');
            setTimeout(function () {
                form.submit();
            }, 600);
        });
    }

    function closeConfirmModal() {
        if (confirmModal) confirmModal.classList.remove('is-open');
    }

    function closeRejectConfirmModal() {
        if (rejectConfirmModal) rejectConfirmModal.classList.remove('is-open');
    }

    if (confirmCloseBtn) confirmCloseBtn.addEventListener('click', closeConfirmModal);
    if (confirmCancelBtn) confirmCancelBtn.addEventListener('click', closeConfirmModal);

    if (rejectConfirmCloseBtn) rejectConfirmCloseBtn.addEventListener('click', closeRejectConfirmModal);
    if (rejectConfirmCancelBtn) rejectConfirmCancelBtn.addEventListener('click', closeRejectConfirmModal);

    function showPaProgressOverlay(type) {
        var fill = document.getElementById('paProgressFill');
        var percentText = document.getElementById('paProgressPercent');
        var statusText = document.getElementById('paProgressText');
        var titleText = document.getElementById('paLoadingTitle');
        var subtitleText = document.getElementById('paLoadingText');
        var spinner = document.getElementById('paSpinner');
        var icon = document.getElementById('paIcon');

        if (!loadingOverlay || !fill || !percentText || !statusText) return;

        if (type === 'rejection') {
            if (titleText) titleText.innerText = 'Rejecting & Sending Rejection Email...';
            if (subtitleText) subtitleText.innerText = 'Please wait while rejection email is sent to Customer Success Team & Sales Person...';
            if (spinner) spinner.style.borderTopColor = '#dc2626';
            if (icon) {
                icon.className = 'bi bi-x-circle-fill support-process-icon';
                icon.style.color = '#dc2626';
            }
            if (fill) fill.style.background = 'linear-gradient(90deg, #dc2626 0%, #ef4444 100%)';
        } else {
            if (titleText) titleText.innerText = 'Approving & Sending Email...';
            if (subtitleText) subtitleText.innerText = 'Please wait while approval email is sent to Sales Person & projects@saitechnosolutions.net...';
            if (spinner) spinner.style.borderTopColor = '#166534';
            if (icon) {
                icon.className = 'bi bi-check-circle-fill support-process-icon';
                icon.style.color = '#166534';
            }
            if (fill) fill.style.background = 'linear-gradient(90deg, #166534 0%, #22c55e 100%)';
        }

        loadingOverlay.style.display = 'flex';
        var currentProgress = 5;
        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
        statusText.innerText = 'Connecting to server...';

        if (paProgressInterval) clearInterval(paProgressInterval);

        paProgressInterval = setInterval(function() {
            if (currentProgress < 30) {
                currentProgress += Math.floor(Math.random() * 8) + 4;
                statusText.innerText = type === 'rejection' ? 'Updating production status to rejected...' : 'Updating production approval status...';
            } else if (currentProgress < 75) {
                currentProgress += Math.floor(Math.random() * 6) + 3;
                statusText.innerText = type === 'rejection' ? 'Sending email to Customer Success Team & Sales Person...' : 'Sending email to Sales Person & projects@saitechnosolutions.net...';
            } else if (currentProgress < 94) {
                currentProgress += Math.floor(Math.random() * 3) + 1;
                statusText.innerText = 'Finalizing request review...';
            }

            if (currentProgress > 94) {
                currentProgress = 94;
            }

            fill.style.width = currentProgress + '%';
            percentText.innerText = currentProgress + '%';
        }, 250);
    }

    document.querySelectorAll('.pa-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('button') || event.target.closest('a') || event.target.closest('select') || event.target.closest('form') || event.target.closest('input')) {
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
