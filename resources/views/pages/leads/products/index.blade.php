@extends('layouts.app')

@section('title', 'Lead Products')

@push('styles')
<style>
.lpd-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.lpd-topbar { display:flex; align-items:center; justify-content:space-between; padding:0 28px; height:60px; background:#fff; border-bottom:1px solid #e1dee3; }
.lpd-title { font-size:18px; font-weight:800; color:#121212; }
.lpd-breadcrumb { font-size:12px; color:#9e9e9e; margin-top:2px; }
.lpd-top-actions { display:flex; align-items:center; gap:10px; }
.lpd-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:700; font-family:inherit; text-decoration:none; border:1px solid #e1dee3; background:#fff; color:#111827; transition:all .15s ease; }
.lpd-btn:hover { border-color:#fe5f04; color:#fe5f04; background:#fff7ed; }
.lpd-body { padding:18px 28px 32px; display:flex; flex-direction:column; gap:18px; }
.lpd-stats { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; }
.lpd-stat {
    position:relative; overflow:hidden; background:linear-gradient(180deg,#ffffff 0%, #fffdfa 100%);
    border:1px solid #e7e1de; border-radius:20px; padding:16px;
    box-shadow:0 12px 28px rgba(18,18,18,.05); transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.lpd-stat::before {
    content:''; position:absolute; inset:0 0 auto 0; height:4px; background:var(--stat-color, #fe5f04);
}
.lpd-stat:hover { transform:translateY(-2px); box-shadow:0 18px 34px rgba(18,18,18,.08); border-color:#ddd2cb; }
.lpd-stat-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:12px; }
.lpd-stat-icon { width:42px; height:42px; border-radius:14px; display:flex; align-items:center; justify-content:center; box-shadow:inset 0 1px 0 rgba(255,255,255,.55); }
.lpd-stat-icon svg { width:18px; height:18px; }
.lpd-stat-chip {
    display:inline-flex; align-items:center; gap:5px; padding:5px 8px; border-radius:999px;
    background:rgba(255,255,255,.9); border:1px solid rgba(0,0,0,.06); color:var(--stat-color, #fe5f04);
    font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.04em;
}
.lpd-stat-label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:#9e9e9e; }
.lpd-stat-value { font-size:26px; font-weight:900; color:#111827; line-height:1; margin-top:6px; letter-spacing:-.03em; }
.lpd-stat-sub { margin-top:10px; padding-top:9px; border-top:1px solid #f2ece8; font-size:11px; color:#7c7c7c; line-height:1.45; }
.lpd-filter-card, .lpd-table-card { background:#fff; border:1px solid #e1dee3; border-radius:18px; overflow:hidden; }
.lpd-filter-card {
    display:block;
}
.lpd-filter-toggle {
    width:100%; display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 16px;
    border:none; background:linear-gradient(180deg,#fffaf7 0%, #fff 100%); cursor:pointer; text-align:left; font-family:inherit;
    list-style:none;
}
.lpd-filter-toggle::-webkit-details-marker { display:none; }
.lpd-filter-toggle:hover { background:linear-gradient(180deg,#fff7f1 0%, #fff 100%); }
.lpd-filter-card[open] .lpd-filter-toggle { border-bottom:1px solid #f0eef2; }
.lpd-filter-toggle-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.lpd-filter-title { font-size:14px; font-weight:800; color:#111827; }
.lpd-filter-sub { font-size:11px; color:#9e9e9e; margin-top:2px; }
.lpd-filter-pill {
    display:inline-flex; align-items:center; gap:6px; padding:7px 10px; border-radius:999px;
    background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:11px; font-weight:800;
}
.lpd-filter-chevron {
    width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px;
    border:1px solid #e8e3e8; background:#fff; color:#7c7c7c; transition:transform .18s ease, color .18s ease, border-color .18s ease;
}
.lpd-filter-card[open] .lpd-filter-chevron { transform:rotate(180deg); color:#fe5f04; border-color:#fed7aa; }
.lpd-filter-body { display:none; }
.lpd-filter-card[open] .lpd-filter-body { display:block; }
.lpd-filter-form { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; padding:16px; }
.lpd-field { display:flex; flex-direction:column; gap:7px; }
.lpd-label { font-size:11px; font-weight:800; color:#7c7c7c; text-transform:uppercase; letter-spacing:.5px; }
.lpd-input, .lpd-select {
    width:100%; padding:11px 13px; border:1px solid #e1dee3; border-radius:12px;
    background:#fbfbfc; font-size:13px; font-family:inherit; color:#111827; outline:none; transition:all .15s ease;
}
.lpd-input:hover, .lpd-select:hover { border-color:#d7d1d8; background:#fff; }
.lpd-input:focus, .lpd-select:focus { border-color:#fe5f04; background:#fff; box-shadow:0 0 0 4px rgba(254,95,4,.1); }
.lpd-field-wide { grid-column:span 2; }
.lpd-filter-actions { display:flex; align-items:flex-end; gap:10px; }
.lpd-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; border-color:transparent; box-shadow:0 4px 14px rgba(254,95,4,.22); }
.lpd-btn-primary:hover { color:#fff; border-color:transparent; background:linear-gradient(135deg,#f35700,#ff7422); }
.lpd-btn-ghost { background:#fff; }
.lpd-table-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; border-bottom:1px solid #f0eef2; }
.lpd-table-title { font-size:15px; font-weight:800; color:#111827; }
.lpd-table-sub { font-size:11px; color:#9e9e9e; margin-top:2px; }
.lpd-result { font-size:12px; color:#7c7c7c; }
.lpd-result strong { color:#111827; }
.lpd-table-wrap { overflow-x:auto; }
.lpd-table { width:100%; border-collapse:collapse; }
.lpd-table th { padding:10px 14px; text-align:left; border-bottom:1px solid #f0eef2; background:#fafafa; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:#9e9e9e; white-space:nowrap; }
.lpd-table td { padding:13px 14px; border-bottom:1px solid #f7f6f9; font-size:13px; color:#111827; vertical-align:middle; }
.lpd-table tbody tr { transition:transform .12s ease, box-shadow .12s ease; }
.lpd-table tbody tr:hover td { background:#fdf9f6; }
.lpd-row-link { cursor:pointer; }
.lpd-id { font-family:monospace; font-size:11px; color:#9e9e9e; }
.lpd-client { display:flex; flex-direction:column; gap:3px; min-width:180px; }
.lpd-client-name { font-size:13px; font-weight:800; color:#111827; }
.lpd-client-sub { font-size:11px; color:#9e9e9e; }
.lpd-mobile-link { color:#c2410c; text-decoration:none; font-weight:700; font-family:monospace; }
.lpd-mobile-link:hover { text-decoration:underline; }
.lpd-product-cell { display:flex; flex-direction:column; gap:3px; min-width:220px; }
.lpd-product-name { font-size:13px; font-weight:800; color:#111827; }
.lpd-product-meta { font-size:11px; color:#9e9e9e; }
.lpd-money { font-weight:800; color:#111827; white-space:nowrap; }
.lpd-money-sub { display:block; margin-top:3px; font-size:11px; color:#9e9e9e; font-weight:600; }
.lpd-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; border:1px solid transparent; font-size:11px; font-weight:800; white-space:nowrap; }
.lpd-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.lpd-empty { text-align:center; padding:56px 20px; color:#9e9e9e; }
.lpd-empty svg { width:44px; height:44px; margin-bottom:12px; color:#cbd5e1; }
@media (max-width: 1200px) {
    .lpd-stats, .lpd-filter-form { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    .lpd-field-wide { grid-column:span 2; }
}
@media (max-width: 768px) {
    .lpd-topbar { height:auto; padding:16px 20px; align-items:flex-start; flex-direction:column; gap:12px; }
    .lpd-body { padding:16px 20px 24px; }
    .lpd-stats, .lpd-filter-form { grid-template-columns:1fr; }
    .lpd-filter-actions { flex-wrap:wrap; }
    .lpd-filter-toggle { flex-direction:column; align-items:flex-start; }
    .lpd-filter-toggle-right { width:100%; justify-content:space-between; }
    .lpd-field-wide { grid-column:span 1; }
}
.lpd-btn.active {
    background: #fff0e6;
    border-color: #fe5f04;
    color: #fe5f04;
    font-weight: 700;
}
</style>
@endpush

@section('content')
@php
    $hasCustomFilters =
        request()->filled('lead_id')
        || request()->filled('mobile_number')
        || request()->filled('product_id')
        || request()->filled('product_status')
        || request()->filled('product_active')
        || request()->filled('branch_id')
        || request()->filled('assigned_to')
        || (request()->has('date_from') && request('date_from') !== $defaultFromDate)
        || (request()->has('date_to') && request('date_to') !== $defaultToDate);
@endphp
<div class="lpd-page">
    <div class="lpd-topbar">
        <div>
            <div class="lpd-title">Lead Products</div>
            <div class="lpd-breadcrumb">Sales > Lead Products</div>
        </div>
        <div class="lpd-top-actions">
            <a href="{{ route('leads.index') }}" class="lpd-btn">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                All Leads
            </a>
        </div>
    </div>

    <div class="lpd-body">
        <div class="lpd-stats">
            <div class="lpd-stat" style="--stat-color:#fe5f04;">
                <div class="lpd-stat-top">
                    <div class="lpd-stat-icon" style="background:#fff0e6;color:#fe5f04;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/>
                            <path d="M12 12l8-4.5"/>
                            <path d="M12 12v9"/>
                            <path d="M12 12L4 7.5"/>
                        </svg>
                    </div>
                    <span class="lpd-stat-chip">Volume</span>
                </div>
                <div class="lpd-stat-label">Total Products</div>
                <div class="lpd-stat-value">{{ number_format($stats['total_products']) }}</div>
                <div class="lpd-stat-sub">Lead products matching current filters</div>
            </div>

            <div class="lpd-stat" style="--stat-color:#2563eb;">
                <div class="lpd-stat-top">
                    <div class="lpd-stat-icon" style="background:#eff6ff;color:#2563eb;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <span class="lpd-stat-chip">Value</span>
                </div>
                <div class="lpd-stat-label">Total Price</div>
                <div class="lpd-stat-value">Rs {{ number_format($stats['total_value'], 2) }}</div>
                <div class="lpd-stat-sub">Combined value of filtered lead products</div>
            </div>

            <div class="lpd-stat" style="--stat-color:#16a34a;">
                <div class="lpd-stat-top">
                    <div class="lpd-stat-icon" style="background:#f0fdf4;color:#16a34a;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M12 1v22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"/>
                            <polyline points="7 12 10 15 17 8"/>
                        </svg>
                    </div>
                    <span class="lpd-stat-chip">Received</span>
                </div>
                <div class="lpd-stat-label">Received Cost</div>
                <div class="lpd-stat-value">Rs {{ number_format($stats['received'], 2) }}</div>
                <div class="lpd-stat-sub">Amount already collected from clients</div>
            </div>

            <div class="lpd-stat" style="--stat-color:#dc2626;">
                <div class="lpd-stat-top">
                    <div class="lpd-stat-icon" style="background:#fef2f2;color:#dc2626;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <span class="lpd-stat-chip">Pending</span>
                </div>
                <div class="lpd-stat-label">Pending Cost</div>
                <div class="lpd-stat-value">Rs {{ number_format($stats['pending'], 2) }}</div>
                <div class="lpd-stat-sub">Outstanding amount still pending</div>
            </div>
        </div>

        <details class="lpd-filter-card" id="leadProductFilters" @if($filterPanelOpen) open @endif>
            <summary class="lpd-filter-toggle" id="leadProductFiltersToggle">
                <div>
                    <div class="lpd-filter-title">Filter Lead Products</div>
                    <div class="lpd-filter-sub">Narrow results by product, client, branch, owner, or created date</div>
                </div>
                <div class="lpd-filter-toggle-right">
                    <div class="lpd-filter-pill">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        {{ $leadProducts->total() }} results
                    </div>
                    <span class="lpd-filter-chevron">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </div>
            </summary>
            <div class="lpd-filter-body" id="leadProductFiltersBody">
            <form method="GET" action="{{ route('leads.products.index') }}" class="lpd-filter-form">
                <div class="lpd-field">
                    <label class="lpd-label" for="lead_id">Lead ID</label>
                    <input id="lead_id" type="text" name="lead_id" class="lpd-input" value="{{ request('lead_id') }}" placeholder="Example: 25">
                </div>

                <div class="lpd-field">
                    <label class="lpd-label" for="mobile_number">Mobile Number</label>
                    <input id="mobile_number" type="text" name="mobile_number" class="lpd-input" value="{{ request('mobile_number') }}" placeholder="Client mobile number">
                </div>

                <div class="lpd-field">
                    <label class="lpd-label" for="product_id">Product</label>
                    <select id="product_id" name="product_id" class="lpd-select">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>
                                {{ $product->package_name ?: $product->product_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lpd-field">
                    <label class="lpd-label" for="product_status">Product Status</label>
                    <select id="product_status" name="product_status" class="lpd-select">
                        <option value="">All Product Status</option>
                        @foreach($statusOptions as $statusOption)
                            <option value="{{ $statusOption->id }}" @selected((string) request('product_status') === (string) $statusOption->id)>{{ $statusOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lpd-field">
                    <label class="lpd-label" for="branch_id">Branch</label>
                    <select id="branch_id" name="branch_id" class="lpd-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lpd-field">
                    <label class="lpd-label" for="assigned_to">Assigned To</label>
                    <select id="assigned_to" name="assigned_to" class="lpd-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lpd-field">
                    <label class="lpd-label" for="product_active">Product Status (Catalog)</label>
                    <select id="product_active" name="product_active" class="lpd-select">
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('product_active') === 'active')>Active</option>
                        <option value="inactive" @selected(request('product_active') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="lpd-field">
                    <label class="lpd-label" for="date_from">Created From</label>
                    <input id="date_from" type="date" name="date_from" class="lpd-input" value="{{ request('date_from', $defaultFromDate) }}">
                </div>

                <div class="lpd-field">
                    <label class="lpd-label" for="date_to">Created To</label>
                    <input id="date_to" type="date" name="date_to" class="lpd-input" value="{{ request('date_to', $defaultToDate) }}">
                </div>

                <div class="lpd-field">
                    <label class="lpd-label">Quick Dates</label>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <button type="button" class="lpd-btn" style="padding: 8px 12px; font-size:12px; border-radius:10px; cursor:pointer;" id="quickToday" onclick="setQ('today')">Today</button>
                        <button type="button" class="lpd-btn" style="padding: 8px 12px; font-size:12px; border-radius:10px; cursor:pointer;" id="quickWeek" onclick="setQ('week')">Week</button>
                        <button type="button" class="lpd-btn" style="padding: 8px 12px; font-size:12px; border-radius:10px; cursor:pointer;" id="quickMonth" onclick="setQ('month')">Month</button>
                        <button type="button" class="lpd-btn" style="padding: 8px 12px; font-size:12px; border-radius:10px; cursor:pointer;" id="quickYear" onclick="setQ('year')">Year</button>
                    </div>
                </div>

                <div class="lpd-filter-actions">
                    <button type="submit" class="lpd-btn lpd-btn-primary">Filter</button>
                    @if($hasCustomFilters)
                        <a href="{{ route('leads.products.index') }}" class="lpd-btn lpd-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
            </div>
        </details>

        <div class="lpd-table-card">
            <div class="lpd-table-head">
                <div>
                    <div class="lpd-table-title">Lead Product Sheet</div>
                    <div class="lpd-table-sub">Client, product, lead status, and payment overview in one place</div>
                </div>
                <div class="lpd-result">
                    Showing <strong>{{ $leadProducts->firstItem() ?? 0 }}-{{ $leadProducts->lastItem() ?? 0 }}</strong> of <strong>{{ $leadProducts->total() }}</strong>
                </div>
            </div>

            @if($leadProducts->isEmpty())
                <div class="lpd-empty">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/>
                        <path d="M12 12v9"/>
                        <path d="M12 12l8-4.5"/>
                        <path d="M12 12L4 7.5"/>
                    </svg>
                    <div style="font-size:16px;font-weight:800;color:#7c7c7c;">No lead products found</div>
                    <p style="font-size:13px;margin-top:6px;">Try changing the filters or add products inside a lead.</p>
                </div>
            @else
                <div class="lpd-table-wrap">
                    <table class="lpd-table">
                        <thead>
                            <tr>
                                <th>Lead ID</th>
                                <th>Client Name</th>
                                <th>Mobile Number</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Product Status</th>
                                <th>Received Cost</th>
                                <th>Pending Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leadProducts as $leadProduct)
                                @php
                                    $lead = $leadProduct->lead;
                                    $productStatus = $leadProduct->product_status_config;
                                    $productName = $leadProduct->product?->package_name ?: ($leadProduct->product?->product_name ?: $leadProduct->product_name);
                                    $clientName = $lead?->company_name ?: ($lead?->contact_name ?: 'Unknown Client');
                                @endphp
                                <tr class="{{ $lead ? 'lpd-row-link' : '' }}" @if($lead) onclick="window.location='{{ route('leads.show', $lead) }}'" @endif>
                                    <td><span class="lpd-id">LD-{{ str_pad($leadProduct->lead_id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                    <td>
                                        <div class="lpd-client">
                                            <div class="lpd-client-name">{{ $clientName }}</div>
                                            <div class="lpd-client-sub">{{ $lead?->contact_name ?: 'No contact name' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($lead?->mobile_number)
                                            <a class="lpd-mobile-link" href="tel:{{ $lead->mobile_number }}" onclick="event.stopPropagation()">{{ $lead->mobile_number }}</a>
                                        @else
                                            <span class="lpd-client-sub">No mobile</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="lpd-product-cell">
                                            <div class="lpd-product-name">{{ $productName ?: 'Unnamed Product' }}</div>
                                            <div class="lpd-product-meta">
                                                Product ID: {{ $leadProduct->product_id ?: 'N/A' }}
                                                @if($lead?->branch?->name)
                                                    | {{ $lead->branch->name }}
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="lpd-money">Rs {{ number_format((float) $leadProduct->total_price, 2) }}</span>
                                        <span class="lpd-money-sub">Qty {{ $leadProduct->quantity ?? 1 }}</span>
                                    </td>
                                    <td>
                                        <span class="lpd-badge" style="background:{{ $productStatus['bg'] }};color:{{ $productStatus['text'] }};border-color:{{ $productStatus['border'] }};">
                                            <span class="lpd-dot" style="background:{{ $productStatus['dot'] }};"></span>
                                            {{ $leadProduct->status_label }}
                                        </span>
                                    </td>
                                    <td><span class="lpd-money" style="color:#15803d;">Rs {{ number_format((float) $leadProduct->amount_paid, 2) }}</span></td>
                                    <td><span class="lpd-money" style="color:#dc2626;">Rs {{ number_format((float) $leadProduct->amount_pending, 2) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($leadProducts->hasPages())
                    @include('partials.table-pagination', ['paginator' => $leadProducts])
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const todayDate = @json(now()->toDateString());
const defaultFromDate = @json($defaultFromDate);
const defaultToDate = @json($defaultToDate);

function updateFilters() {
    const from = document.getElementById('date_from')?.value;
    const to = document.getElementById('date_to')?.value;

    document.getElementById('quickToday')?.classList.toggle('active', from === todayDate && to === todayDate);
    document.getElementById('quickMonth')?.classList.toggle('active', from === defaultFromDate && to === defaultToDate);

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

function setQ(p) {
    const parts = defaultToDate.split('-').map(Number);
    const today = new Date(parts[0], parts[1] - 1, parts[2]);
    const fmt = d => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };
    const f = document.getElementById('date_from');
    const t = document.getElementById('date_to');
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

    if (f.value === targetFrom && t.value === targetTo) {
        f.value = '';
        t.value = '';
    } else {
        f.value = targetFrom;
        t.value = targetTo;
    }

    updateFilters();
    f.closest('form').submit();
}

document.addEventListener('DOMContentLoaded', updateFilters);
</script>
@endpush
