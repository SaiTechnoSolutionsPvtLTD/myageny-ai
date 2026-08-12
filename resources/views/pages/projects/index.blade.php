@extends('layouts.app')

@section('title', 'Projects Details')

@push('styles')
<style>
.prj-page { min-height:100%; background:linear-gradient(180deg,#f8fafc 0%,#f1f5f9 100%); font-family:'Inter',sans-serif; }
.prj-topbar { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:0 28px; height:64px; background:#fff; border-bottom:1px solid #e5e7eb; }
.prj-title { font-size:20px; font-weight:900; color:#111827; }
.prj-breadcrumb { font-size:12px; color:#6b7280; margin-top:3px; }
.prj-chip { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; font-size:12px; font-weight:800; }
.prj-body { padding:22px 28px 34px; display:grid; gap:18px; }
.prj-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.prj-card { display:block; text-decoration:none; border-radius:22px; border:1px solid #e5e7eb; padding:18px; box-shadow:0 12px 34px rgba(15,23,42,.06); transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
.prj-card:hover { transform:translateY(-2px); box-shadow:0 16px 38px rgba(15,23,42,.1); }
.prj-card.allocation_pending { background:linear-gradient(180deg,#fffaf3 0%,#ffffff 100%); border-color:#f6d7a7; }
.prj-card.allocated { background:linear-gradient(180deg,#f3fcf5 0%,#ffffff 100%); border-color:#bce6c7; }
.prj-card.is-active.allocation_pending { box-shadow:0 0 0 3px rgba(245,158,11,.14), 0 16px 38px rgba(15,23,42,.1); }
.prj-card.is-active.allocated { box-shadow:0 0 0 3px rgba(34,197,94,.14), 0 16px 38px rgba(15,23,42,.1); }
.prj-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.prj-card-title { font-size:16px; font-weight:900; color:#111827; }
.prj-card-sub { margin-top:4px; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
.prj-badge { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; border:1px solid transparent; }
.prj-badge.allocation_pending { color:#b45309; background:#fff1d6; border-color:#fcd9a2; }
.prj-badge.allocated { color:#166534; background:#e9f9ee; border-color:#bce6c7; }
.prj-card-count { margin-top:18px; font-size:44px; line-height:1; font-weight:900; letter-spacing:-.05em; color:#111827; }
.prj-table-card { background:#fff; border:1px solid #e5e7eb; border-radius:22px; overflow:hidden; box-shadow:0 12px 34px rgba(15,23,42,.06); }
.prj-table-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid #eef2f7; background:#fcfcfd; }
.prj-table-title { font-size:16px; font-weight:900; color:#111827; }
.prj-table-sub { font-size:12px; color:#6b7280; margin-top:3px; }
.prj-table-wrap { overflow-x:auto; }
.prj-table { width:100%; border-collapse:collapse; }
.prj-table th { padding:12px 14px; text-align:left; border-bottom:1px solid #eef2f7; background:#fafbfc; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; white-space:nowrap; }
.prj-table td { padding:14px; border-bottom:1px solid #f3f4f6; font-size:13px; color:#111827; vertical-align:middle; }
.prj-table tbody tr:hover td { background:#fafafa; }
.prj-product { font-weight:800; color:#111827; }
.prj-meta { font-size:11px; color:#6b7280; margin-top:3px; }
.prj-status-pill { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid transparent; }
.prj-status-pill.allocation_pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.prj-status-pill.allocated { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.prj-action-group { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.prj-view-btn { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; font-size:12px; font-weight:800; text-decoration:none; cursor:pointer; }
.prj-link { color:#1d4ed8; font-weight:800; text-decoration:none; }
.prj-link:hover { text-decoration:underline; }
.prj-action-btn { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; background:#166534; border:1px solid #166534; color:#fff; font-size:12px; font-weight:800; text-decoration:none; cursor:pointer; }
.prj-action-muted { font-size:12px; color:#9ca3af; }
.prj-empty { padding:40px 20px; text-align:center; color:#6b7280; font-size:13px; }
.prj-flash { padding:12px 14px; border-radius:14px; font-size:13px; font-weight:700; }
.prj-flash.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.prj-filter-card { background:#fff; border:1px solid #e5e7eb; border-radius:18px; padding:16px; box-shadow:0 10px 28px rgba(15,23,42,.05); }
.prj-filter-form { display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr auto; gap:12px; align-items:end; }
.prj-filter-group { display:grid; gap:6px; }
.prj-filter-label { font-size:10px; font-weight:800; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
.prj-filter-input { width:100%; min-height:42px; padding:10px 12px; border:1px solid #dbe2ea; border-radius:10px; background:#fff; color:#111827; font-size:13px; }
.prj-filter-input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 4px rgba(37,99,235,.12); }
.prj-filter-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.prj-reset-btn { display:inline-flex; align-items:center; justify-content:center; min-height:42px; padding:10px 12px; border-radius:10px; background:#fff; border:1px solid #d1d5db; color:#374151; font-size:12px; font-weight:800; text-decoration:none; }
@media (max-width: 1100px) {
    .prj-grid { grid-template-columns:1fr; }
    .prj-filter-form { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width: 768px) {
    .prj-topbar { height:auto; padding:16px 18px; align-items:flex-start; flex-direction:column; }
    .prj-body { padding:18px 16px 24px; }
    .prj-filter-form { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')
<div class="prj-page">
    @php
        $isContributorScopedView = $isContributorScopedView ?? false;
        $pageTitle = $isContributorScopedView ? 'My Allocated Projects' : ($isTlScopedView ? 'My Projects' : 'Projects Details');
        $pageCrumb = $isContributorScopedView ? 'Projects Dashboard > Allocated Projects' : ($isTlScopedView ? 'Projects Dashboard > My Projects' : 'CRM Dashboard > Projects Details');
        $listSubText = $isContributorScopedView
            ? 'Only projects allocated to you are listed here.'
            : ($isTlScopedView
            ? 'Projects allocated to you are tracked here for employee assignment.'
            : 'Production approved items are tracked here for allocation.');
    @endphp
    <div class="prj-topbar">
        <div>
            <div class="prj-title">{{ $pageTitle }}</div>
            <div class="prj-breadcrumb">{{ $pageCrumb }}</div>
        </div>
        <div class="prj-chip">{{ $isContributorScopedView ? 'Allocated Project List' : ($isTlScopedView ? 'Employee Allocation Tracker' : 'Allocation Tracker') }}</div>
    </div>

    <div class="prj-body">
        @if(session('success'))
            <div class="prj-flash success">{{ session('success') }}</div>
        @endif

        @if($isContributorScopedView)
            <section class="prj-filter-card">
                <form method="GET" action="{{ route('projects.index') }}" class="prj-filter-form">
                    <div class="prj-filter-group">
                        <label class="prj-filter-label">Search</label>
                        <input type="text" name="search" value="{{ $projectFilters['search'] ?? '' }}" class="prj-filter-input" placeholder="Project, client, company, mobile">
                    </div>
                    <div class="prj-filter-group">
                        <label class="prj-filter-label">Product</label>
                        <select name="product_id" class="prj-filter-input">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected(($projectFilters['product_id'] ?? '') === (string) $product->id)>{{ $product->product_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="prj-filter-group">
                        <label class="prj-filter-label">Department</label>
                        <select name="department_id" class="prj-filter-input">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(($projectFilters['department_id'] ?? '') === (string) $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="prj-filter-group">
                        <label class="prj-filter-label">Project Category</label>
                        <select name="project_category" class="prj-filter-input">
                            <option value="">All Categories</option>
                            @foreach($projectCategories as $categoryName)
                                <option value="{{ $categoryName }}" @selected(($projectFilters['project_category'] ?? '') === $categoryName)>{{ $categoryName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="prj-filter-group">
                        <label class="prj-filter-label">Delivery From</label>
                        <input type="date" name="delivery_from" value="{{ $projectFilters['delivery_from'] ?? '' }}" class="prj-filter-input">
                    </div>
                    <div class="prj-filter-group">
                        <label class="prj-filter-label">Delivery To</label>
                        <input type="date" name="delivery_to" value="{{ $projectFilters['delivery_to'] ?? '' }}" class="prj-filter-input">
                    </div>
                    <div class="prj-filter-actions">
                        <button type="submit" class="prj-action-btn">Filter</button>
                        <a href="{{ route('projects.index') }}" class="prj-reset-btn">Reset</a>
                    </div>
                </form>
            </section>

            <section class="prj-table-card">
                <div class="prj-table-head">
                    <div>
                        <div class="prj-table-title">Allocated Projects</div>
                        <div class="prj-table-sub">{{ $listSubText }}</div>
                    </div>
                    <span class="prj-badge allocated">{{ number_format($employeeProjects->count()) }} Items</span>
                </div>

                @if($employeeProjects->isNotEmpty())
                    <div class="prj-table-wrap">
                        <table class="prj-table">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Delivery Date</th>
                                    <th>Client Name</th>
                                    <th>Company Name</th>
                                    <th>Mobile Number</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employeeProjects as $item)
                                    <tr>
                                        <td>
                                            <div class="prj-product">{{ $item->product_name }}</div>
                                            <div class="prj-meta">{{ $item->department?->name ?: 'No department' }}</div>
                                        </td>
                                        <td>{{ optional($item->project_delivery_date)->format('d M Y') ?: 'Not available' }}</td>
                                        <td>{{ $item->client_name ?: ($item->lead?->contact_name ?: 'No client') }}</td>
                                        <td>{{ $item->company_name ?: ($item->lead?->company_name ?: 'No company') }}</td>
                                        <td>
                                            @if($item->lead?->mobile_number)
                                                <a href="tel:{{ $item->lead->mobile_number }}" class="prj-link">{{ $item->lead->mobile_number }}</a>
                                            @else
                                                <span class="prj-action-muted">Not available</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('projects.show', $item) }}" class="prj-view-btn">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="prj-empty">No allocated projects match the selected filters.</div>
                @endif
            </section>
        @else

        <section class="prj-filter-card">
            <form method="GET" action="{{ route('projects.index') }}" class="prj-filter-form">
                @if(request('bucket'))
                    <input type="hidden" name="bucket" value="{{ request('bucket') }}">
                @endif
                <div class="prj-filter-group">
                    <label class="prj-filter-label">Lead / Company Search</label>
                    <input type="text" name="search" value="{{ $projectFilters['search'] ?? '' }}" class="prj-filter-input" placeholder="Search Lead ID, company, client, mobile...">
                </div>
                <div class="prj-filter-group">
                    <label class="prj-filter-label">Product</label>
                    <select name="product_id" class="prj-filter-input">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(($projectFilters['product_id'] ?? '') === (string) $product->id)>
                                {{ $product->product_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="prj-filter-group">
                    <label class="prj-filter-label">Department</label>
                    <select name="department_id" class="prj-filter-input">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(($projectFilters['department_id'] ?? '') === (string) $dept->id)>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="prj-filter-actions">
                    <button type="submit" class="prj-action-btn">Filter</button>
                    @if(!empty($projectFilters['search']) || !empty($projectFilters['product_id']) || !empty($projectFilters['department_id']))
                        <a href="{{ route('projects.index', ['bucket' => $selectedBucket]) }}" class="prj-reset-btn">Reset</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="prj-grid">
            @foreach($cards as $key => $card)
                @php
                    $bucketQueryParams = array_merge(request()->query(), ['bucket' => $key]);
                @endphp
                <a href="{{ route('projects.index', $bucketQueryParams) }}" class="prj-card {{ $key }} {{ $selectedBucket === $key ? 'is-active' : '' }}">
                    <div class="prj-card-head">
                        <div>
                            <div class="prj-card-title">{{ $card['title'] }}</div>
                            <div class="prj-card-sub">{{ $card['status_label'] }}</div>
                        </div>
                        <span class="prj-badge {{ $key }}">{{ $card['status_label'] }}</span>
                    </div>
                    <div class="prj-card-count">{{ number_format($card['count']) }}</div>
                </a>
            @endforeach
        </section>

        <section class="prj-table-card">
            <div class="prj-table-head">
                <div>
                    <div class="prj-table-title">{{ $selectedCard['title'] }} List</div>
                    <div class="prj-table-sub">{{ $listSubText }}</div>
                </div>
                <span class="prj-badge {{ $selectedBucket }}">{{ number_format($selectedCard['count']) }} Items</span>
            </div>

            @if($selectedCard['items']->isNotEmpty())
                <div class="prj-table-wrap">
                    <table class="prj-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Lead / Company</th>
                                <th>Department</th>
                                <th>Prod. Approved By</th>
                                <th>Prod. Approved On</th>
                                <th>{{ $isTlScopedView ? 'Team Status' : 'Allocation Status' }}</th>
                                <th>{{ $isTlScopedView ? 'Employee Allocated By' : 'Allocated By' }}</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedCard['items'] as $item)
                                <tr>
                                    <td>
                                        <div class="prj-product">{{ $item->product_name }}</div>
                                        <div class="prj-meta">Working days: {{ $item->total_working_days }}</div>
                                    </td>
                                    <td>
                                        {{ $item->company_name ?: ($item->lead?->company_name ?: 'No company') }}
                                        <div class="prj-meta">{{ $item->client_name ?: ($item->lead?->contact_name ?: 'No client') }}</div>
                                    </td>
                                    <td>{{ $item->department?->name ?: 'No department' }}</td>
                                    <td>{{ $item->productionApprovalReviewedBy?->name ?: 'Pending' }}</td>
                                    <td>{{ optional($item->production_approval_reviewed_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                                    <td>
                                        @php
                                            $currentStatus = $isTlScopedView
                                                ? ($item->current_team_status ?? 'allocation_pending')
                                                : $item->project_allocation_status;
                                        @endphp
                                        <span class="prj-status-pill {{ $selectedBucket === 'allocated' || $currentStatus === 'allocated' ? 'allocated' : 'allocation_pending' }}">{{ str_replace('_', ' ', (string) $currentStatus) }}</span>
                                    </td>
                                    <td>{{ $isTlScopedView ? (($item->current_team_allocated_by_name ?? null) ?: 'Pending') : ($item->projectAllocatedBy?->name ?: 'Pending') }}</td>
                                    <td>
                                        <div class="prj-action-group">
                                            <a href="{{ route('projects.show', $item) }}" class="prj-view-btn">View</a>
                                            @if($isTlScopedView)
                                                <a href="{{ route('projects.show', $item) }}" class="prj-action-btn">{{ $currentStatus === 'allocated' ? 'Reallocate Team' : 'Allocate Team' }}</a>
                                            @elseif($item->project_allocation_status === 'allocation_pending')
                                                <a href="{{ route('projects.show', $item) }}" class="prj-action-btn">Allocate</a>
                                            @else
                                                <span class="prj-action-muted">{{ optional($isTlScopedView ? ($item->current_team_allocated_at ?? null) : $item->project_allocated_at)->format('d M Y h:i A') ?: 'Completed' }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="prj-empty">No project items are available in this status right now.</div>
            @endif
        </section>
        @endif
    </div>
</div>
@endsection
