@extends('layouts.app')

@section('title', 'PO Orders - Purchase Orders')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.inv-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.inv-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.inv-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.inv-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:20px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.1; }
.acm-stat-sub { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }

/* Filter Accordion */
.acc-filter-accordion { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); transition:box-shadow .2s; }
.acc-filter-accordion:hover { box-shadow:0 4px 12px rgba(0,0,0,.04); }
.acc-filter-toggle { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 20px; cursor:pointer; user-select:none; background:#fff; transition:background .15s ease; }
.acc-filter-toggle::-webkit-details-marker, .acc-filter-toggle::marker { display:none; content:""; }
.acc-filter-toggle:hover { background:#f8fafc; }
.acc-filter-accordion[open] .acc-filter-toggle { border-bottom:1px solid #e2e8f0; background:#fafbfc; }
.acc-filter-toggle-left { display:flex; align-items:center; gap:12px; min-width:0; }
.acc-filter-icon { width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:#fff7ed; color:#fe5f04; font-size:15px; flex-shrink:0; border:1px solid #ffedd5; }
.acc-filter-title { font-size:14px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.acc-filter-sub { font-size:12px; color:#64748b; margin-top:2px; }
.acc-filter-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; background:#fe5f04; color:#fff; font-size:11px; font-weight:800; letter-spacing:0.02em; }
.acc-filter-toggle-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.acc-filter-toggle-hint { font-size:12px; font-weight:700; color:#64748b; }
.acc-filter-toggle-hint::after { content:"Show Filters"; }
.acc-filter-accordion[open] .acc-filter-toggle-hint::after { content:"Hide Filters"; }
.acc-filter-chevron { width:32px; height:32px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e2e8f0; color:#64748b; background:#fff; transition:all .2s ease; font-size:13px; }
.acc-filter-accordion[open] .acc-filter-chevron { transform:rotate(180deg); color:#fe5f04; border-color:#fed7aa; background:#fff7ed; }
.acc-filter-body { padding:16px 20px; background:#fff; }

.inv-filter-single-row { display:flex; align-items:flex-end; gap:12px; width:100%; }
.filter-col { display:flex; flex-direction:column; gap:6px; }
.filter-col-search { flex:2.2; min-width:220px; }
.filter-col-status { flex:1.2; min-width:145px; }
.filter-col-date { flex:1; min-width:135px; }
.filter-col-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; margin-bottom:0; }
.filter-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; line-height:1; }
.filter-input-wrap { position:relative; width:100%; }
.filter-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
.filter-control { width:100%; height:38px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; color:#0f172a; box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
.filter-control:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.filter-input-wrap .filter-control { padding-left:36px; }
.filter-select { cursor:pointer; }

.acm-tbl { width:100%; border-collapse:separate; border-spacing:0; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#475569; padding:13px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.acm-tbl tbody tr { transition:background-color .15s ease; }
.acm-tbl tbody tr:hover { background-color:#f8fafc; }
.acm-tbl tbody tr:last-child td { border-bottom:none; }

.inv-code {
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-weight:800;
    color:#0f172a;
    text-decoration:none;
    font-family:'JetBrains Mono', 'Fira Code', monospace;
    font-size:13px;
    transition:color .15s;
}
.inv-code:hover { color:#fe5f04; }

/* Action Dropdown */
.inv-action-dropdown { position:relative; display:inline-block; }
.inv-action-trigger { width:32px; height:32px; border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#475569; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:all .15s; }
.inv-action-trigger:hover { border-color:#cbd5e1; background:#f8fafc; color:#0f172a; }
.inv-action-menu { position:absolute; right:0; top:calc(100% + 4px); background:#fff; border:1px solid #e2e8f0; border-radius:10px; box-shadow:0 10px 25px -5px rgba(0,0,0,.1); min-width:160px; z-index:50; display:none; padding:4px; }
.inv-action-menu.show { display:block; }
.inv-action-item { display:flex; align-items:center; gap:8px; padding:8px 12px; font-size:12px; font-weight:600; color:#334155; text-decoration:none; border-radius:6px; transition:all .15s; border:none; background:none; width:100%; text-align:left; cursor:pointer; }
.inv-action-item:hover { background:#f1f5f9; color:#0f172a; }
.inv-action-item.delete:hover { background:#fee2e2; color:#b91c1c; }
.inv-action-divider { height:1px; background:#f1f5f9; margin:4px 0; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-cart-check-fill" style="color:#fe5f04;"></i>
                <span>PO Orders (Purchase Orders)</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}"><i class="bi bi-house-door"></i> Accounts</a>
                <span>/</span>
                <span>Purchase Orders</span>
            </div>
        </div>

        <div class="inv-actions">
            <a href="{{ route('accounts.purchase-orders.create') }}" class="acm-btn acm-btn-primary">
                <i class="bi bi-plus-lg"></i>
                <span>Create Purchase Order</span>
            </a>
        </div>
    </div>

    <div class="inv-body">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-x-circle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- KPI Summary Stats --}}
        <div class="acm-stats-grid">
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fff7ed; color:#fe5f04;">
                    <i class="bi bi-cart4"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Total PO Orders</div>
                    <div class="acm-stat-val">{{ number_format($stats['total_count']) }}</div>
                    <div class="acm-stat-sub">All-time purchase orders</div>
                </div>
            </div>

            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                    <i class="bi bi-currency-rupee"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Total Ordered Value</div>
                    <div class="acm-stat-val">₹{{ number_format($stats['total_amount'], 2) }}</div>
                    <div class="acm-stat-sub">Across all purchase orders</div>
                </div>
            </div>

            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#e0f2fe; color:#0369a1;">
                    <i class="bi bi-send-check"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Issued Orders</div>
                    <div class="acm-stat-val">{{ number_format($stats['issued_count']) }}</div>
                    <div class="acm-stat-sub">Sent to suppliers</div>
                </div>
            </div>

            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#dcfce7; color:#15803d;">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Approved</div>
                    <div class="acm-stat-val">{{ number_format($stats['approved_count']) }}</div>
                    <div class="acm-stat-sub">Approved purchase orders</div>
                </div>
            </div>

            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f3e8ff; color:#7e22ce;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Goods Received</div>
                    <div class="acm-stat-val">{{ number_format($stats['received_count']) }}</div>
                    <div class="acm-stat-sub">Completed orders</div>
                </div>
            </div>
        </div>

        {{-- Filter Accordion --}}
        <details class="acc-filter-accordion" id="poFiltersAccordion" {{ $hasActiveFilters ? 'open' : '' }}>
            <summary class="acc-filter-toggle">
                <div class="acc-filter-toggle-left">
                    <span class="acc-filter-icon"><i class="bi bi-funnel"></i></span>
                    <div>
                        <div class="acc-filter-title">
                            <span>Filter &amp; Search Purchase Orders</span>
                            @if($hasActiveFilters)
                                <span class="acc-filter-badge">Active Filters</span>
                            @endif
                        </div>
                        <div class="acc-filter-sub">Filter by PO number, supplier, status, or date range.</div>
                    </div>
                </div>
                <div class="acc-filter-toggle-right">
                    <span class="acc-filter-toggle-hint"></span>
                    <span class="acc-filter-chevron"><i class="bi bi-chevron-down"></i></span>
                </div>
            </summary>

            <div class="acc-filter-body">
                <form method="GET" action="{{ route('accounts.purchase-orders.index') }}" class="inv-filter-single-row">
                    <div class="filter-col filter-col-search">
                        <label class="filter-label" for="filterSearch">Search</label>
                        <div class="filter-input-wrap">
                            <i class="bi bi-search filter-icon"></i>
                            <input type="text" id="filterSearch" name="search" class="filter-control" placeholder="PO #, Supplier name, email, phone, GSTIN..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="filter-col filter-col-status">
                        <label class="filter-label" for="filterStatus">Status</label>
                        <select id="filterStatus" name="status" class="filter-control filter-select">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-col filter-col-date">
                        <label class="filter-label" for="filterFromDate">From Date</label>
                        <input type="date" id="filterFromDate" name="from_date" class="filter-control" value="{{ request('from_date') }}">
                    </div>

                    <div class="filter-col filter-col-date">
                        <label class="filter-label" for="filterToDate">To Date</label>
                        <input type="date" id="filterToDate" name="to_date" class="filter-control" value="{{ request('to_date') }}">
                    </div>

                    <div class="filter-col filter-col-actions">
                        <button type="submit" class="acm-btn acm-btn-primary" style="height:38px;">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('accounts.purchase-orders.index') }}" class="acm-btn acm-btn-secondary" style="height:38px;" title="Reset Filters">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </details>

        {{-- Purchase Orders Table Card --}}
        <div class="acm-card">
            <div style="overflow-x:auto;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th style="width:140px;">PO Number</th>
                            <th style="width:110px;">Date</th>
                            <th style="width:110px;">Delivery Date</th>
                            <th>Supplier / Vendor</th>
                            <th style="width:140px; text-align:right;">Amount (₹)</th>
                            <th style="width:110px; text-align:center;">Status</th>
                            <th style="width:70px; text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $po)
                        <tr>
                            <td>
                                <a href="{{ route('accounts.purchase-orders.show', $po->id) }}" class="inv-code">
                                    <i class="bi bi-file-earmark-text" style="color:#fe5f04;"></i>
                                    <span>{{ $po->po_number }}</span>
                                </a>
                            </td>
                            <td>
                                <span style="font-weight:600; color:#334155;">{{ $po->po_date?->format('d M Y') ?: '-' }}</span>
                            </td>
                            <td>
                                <span style="color:#64748b;">{{ $po->delivery_date?->format('d M Y') ?: '-' }}</span>
                            </td>
                            <td>
                                <div style="font-weight:800; color:#0f172a;">{{ $po->supplier_name }}</div>
                                @if($po->company_name)
                                    <div style="font-size:12px; color:#64748b;">{{ $po->company_name }}</div>
                                @endif
                                @if($po->supplier_phone || $po->supplier_email)
                                    <div style="font-size:11px; color:#94a3b8; margin-top:2px;">
                                        {{ $po->supplier_phone }} {{ $po->supplier_email ? '• ' . $po->supplier_email : '' }}
                                    </div>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="font-weight:900; color:#0f172a; font-size:14px;">₹{{ number_format((float) $po->total_amount, 2) }}</div>
                                <div style="font-size:11px; color:#94a3b8;">Tax: ₹{{ number_format((float) $po->total_tax, 2) }}</div>
                            </td>
                            <td style="text-align:center;">
                                {!! $po->status_badge !!}
                            </td>
                            <td style="text-align:center;">
                                <div class="inv-action-dropdown">
                                    <button type="button" class="inv-action-trigger" onclick="toggleActionMenu(event, this)">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="inv-action-menu">
                                        <a href="{{ route('accounts.purchase-orders.show', $po->id) }}" class="inv-action-item">
                                            <i class="bi bi-eye" style="color:#2563eb;"></i> <span>View</span>
                                        </a>
                                        <a href="{{ route('accounts.purchase-orders.print', $po->id) }}" target="_blank" class="inv-action-item">
                                            <i class="bi bi-printer" style="color:#16a34a;"></i> <span>Print</span>
                                        </a>
                                        <a href="{{ route('accounts.purchase-orders.edit', $po->id) }}" class="inv-action-item">
                                            <i class="bi bi-pencil-square" style="color:#ea580c;"></i> <span>Edit</span>
                                        </a>
                                        <div class="inv-action-divider"></div>
                                        <form method="POST" action="{{ route('accounts.purchase-orders.destroy', $po->id) }}" onsubmit="return confirm('Are you sure you want to delete Purchase Order #{{ $po->po_number }}?')" style="margin:0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inv-action-item delete">
                                                <i class="bi bi-trash"></i> <span>Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center; padding:48px 20px; color:#64748b;">
                                <div style="width:56px; height:56px; border-radius:50%; background:#fff7ed; color:#fe5f04; display:flex; align-items:center; justify-content:center; font-size:24px; margin:0 auto 12px;">
                                    <i class="bi bi-cart-x"></i>
                                </div>
                                <div style="font-weight:800; font-size:15px; color:#0f172a;">No Purchase Orders Found</div>
                                <div style="font-size:13px; color:#94a3b8; margin-top:4px;">No PO orders match your search or filter criteria.</div>
                                <div style="margin-top:16px;">
                                    <a href="{{ route('accounts.purchase-orders.create') }}" class="acm-btn acm-btn-primary">
                                        <i class="bi bi-plus-lg"></i> Create First Purchase Order
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($purchaseOrders->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div style="font-size:13px; color:#64748b; font-weight:600;">
                        Showing {{ $purchaseOrders->firstItem() }} to {{ $purchaseOrders->lastItem() }} of {{ $purchaseOrders->total() }} purchase orders
                    </div>
                    <div>
                        {{ $purchaseOrders->links() }}
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleActionMenu(event, btn) {
    event.stopPropagation();
    const menu = btn.nextElementSibling;
    document.querySelectorAll('.inv-action-menu.show').forEach(m => {
        if (m !== menu) m.classList.remove('show');
    });
    menu.classList.toggle('show');
}

document.addEventListener('click', function() {
    document.querySelectorAll('.inv-action-menu.show').forEach(m => m.classList.remove('show'));
});
</script>
@endpush
