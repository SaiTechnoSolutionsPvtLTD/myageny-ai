@extends('layouts.app')

@section('title', 'Customer Master - Accounts')

@push('styles')
<style>
.acm-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.acm-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.acm-topbar-left { display:flex; flex-direction:column; justify-content:center; }
.acm-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.acm-breadcrumbs { font-size:12px; color:#9e9e9e; display:flex; align-items:center; gap:6px; margin-top:2px; }
.acm-breadcrumbs a { color:#9e9e9e; text-decoration:none; transition:color .15s; }
.acm-breadcrumbs a:hover { color:#fe5f04; }
.acm-breadcrumbs .sep { color:#ccc; }
.acm-breadcrumbs .current { color:#424242; font-weight:600; }
.acm-topbar-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.acm-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }
@media (max-width: 900px) {
    .acm-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .acm-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }
.acm-btn[disabled], .acm-btn.disabled { opacity:0.65 !important; cursor:not-allowed !important; pointer-events:none !important; transform:none !important; box-shadow:none !important; }
@keyframes acmSpin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.acm-spin { display:inline-block; animation:acmSpin .8s linear infinite; }

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.acm-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:wrap; }
.acm-search-wrap { position:relative; flex:1; min-width:240px; }
.acm-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.acm-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.acm-select { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
.status-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-inactive { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.status-paid { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-sent { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
.status-partially-paid { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.status-overdue { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.status-cancelled { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.status-draft { background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; }

.code-chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; background:#f1f5f9; color:#0f172a; font-family:monospace; font-size:11px; font-weight:700; border:1px solid #e2e8f0; }
.terms-chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; background:#fef3c7; color:#92400e; font-size:11px; font-weight:700; border:1px solid #fde68a; }

/* KPI Grid for Customer View Modal */
.v-kpi-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; }
@media (max-width: 900px) { .v-kpi-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 500px) { .v-kpi-grid { grid-template-columns:1fr; } }
.v-kpi-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,.03); }
.v-kpi-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.v-kpi-title { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.v-kpi-val { font-size:18px; font-weight:800; color:#0f172a; margin-top:2px; line-height:1.2; }
.v-kpi-sub { font-size:11px; color:#94a3b8; margin-top:2px; }

.v-tabs-bar { display:flex; gap:8px; border-bottom:1px solid #e2e8f0; margin-bottom:18px; }
.v-tab-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 16px; border:none; background:none; font-size:13px; font-weight:700; color:#64748b; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1px; transition:all .15s; }
.v-tab-btn:hover { color:#0f172a; }
.v-tab-btn.active { color:#fe5f04; border-bottom-color:#fe5f04; font-weight:800; }
.v-tab-badge { font-size:10px; padding:2px 7px; border-radius:999px; background:#f1f5f9; color:#475569; font-weight:800; }
.v-tab-btn.active .v-tab-badge { background:#ffedd5; color:#c2410c; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:95%; max-width:960px; max-height:92vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:20px; }

/* Section & Grid Styling */
.form-section-title { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px dashed #e2e8f0; padding-bottom:6px; }
.form-grid-3 { display:grid; grid-template-columns:repeat(3, 1fr); gap:14px; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media (max-width: 768px) {
    .form-grid-3 { grid-template-columns:1fr; }
    .form-grid-2 { grid-template-columns:1fr; }
}

.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; box-sizing:border-box; width:100%; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

/* Lead Selector Bar */
.lead-select-card { background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:14px 18px; display:flex; flex-direction:column; gap:8px; }
.lead-badge-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; border-radius:6px; font-size:12px; font-weight:700; }

/* Address Dual Column (Screenshot Match) */
.address-container { display:grid; grid-template-columns:1fr 1fr; gap:24px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; }
@media (max-width: 768px) { .address-container { grid-template-columns:1fr; } }
.address-col-title { font-size:15px; font-weight:800; color:#0f172a; margin-bottom:14px; display:flex; align-items:center; justify-content:space-between; }
.copy-address-btn { font-size:12px; color:#2563eb; font-weight:700; text-decoration:none; cursor:pointer; background:none; border:none; padding:0; display:inline-flex; align-items:center; gap:4px; }
.copy-address-btn:hover { text-decoration:underline; color:#1d4ed8; }

.address-row { display:grid; grid-template-columns:110px 1fr; align-items:start; gap:12px; margin-bottom:10px; }
@media (max-width: 480px) { .address-row { grid-template-columns:1fr; gap:4px; } }
.address-row-label { font-size:12px; font-weight:700; color:#475569; padding-top:8px; }

/* Detail View Rows */
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns:1fr; } }
.detail-item { background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0; }
.detail-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px; }
.detail-val { font-size:13px; font-weight:700; color:#0f172a; word-break:break-word; }

.tab-nav { display:flex; gap:16px; border-bottom:2px solid #e2e8f0; padding-bottom:6px; margin-bottom:12px; font-size:13px; font-weight:700; }
.tab-nav-item { color:#64748b; cursor:pointer; padding-bottom:6px; border-bottom:2px solid transparent; margin-bottom:-8px; }
.tab-nav-item.active { color:#0f172a; border-bottom-color:#fe5f04; font-weight:800; }
</style>
@endpush

@section('content')
<div class="acm-page">

    {{-- Standard Uniform Topbar --}}
    <div class="acm-topbar">
        <div class="acm-topbar-left">
            <div class="acm-title">
                <i class="bi bi-people" style="color:#fe5f04;"></i>
                <span>Customer Master</span>
            </div>
            <div class="acm-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.master.index') }}">Master Hub</a>
                <span class="sep">/</span>
                <span class="current">Customers</span>
            </div>
        </div>
        <div class="acm-topbar-actions">
            <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Master Hub
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="openAddCustomerModal()">
                <i class="bi bi-plus-lg"></i> Add New Customer
            </button>
        </div>
    </div>

    <div class="acm-body">

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ✕ {{ session('error') }}
        </div>
    @endif
    @if(isset($errors) && $errors->any())
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="acm-stats-grid">
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fef3c7; color:#d97706;">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <div class="acm-stat-label">Total Customers</div>
                <div class="acm-stat-val">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Active Customers</div>
                <div class="acm-stat-val">{{ $stats['active'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fee2e2; color:#dc2626;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Inactive Customers</div>
                <div class="acm-stat-val">{{ $stats['inactive'] }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="acm-card">
        <form method="GET" action="{{ route('accounts.master.customers.index') }}">
            <div class="acm-filter-bar">
                <div class="acm-search-wrap">
                    <i class="bi bi-search acm-search-ico"></i>
                    <input type="text" name="search" class="acm-search-input" placeholder="Search customer name, company, mobile, email, GST, PAN, city, state…" value="{{ request('search') }}">
                </div>
                <select name="status" class="acm-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px;">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('accounts.master.customers.index') }}" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1;">Reset</a>
                @endif
            </div>
        </form>

        @if($customers->isEmpty())
            <div style="text-align:center; padding:55px 20px; color:#94a3b8;">
                <i class="bi bi-people" style="font-size:44px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Customers Registered</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:18px;">Click below to add your first customer or auto-import from converted CRM leads.</div>
                <button type="button" class="acm-btn acm-btn-primary" onclick="openAddCustomerModal()">
                    <i class="bi bi-plus-lg"></i> Add New Customer
                </button>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer & Company</th>
                            <th>Contact Info</th>
                            <th>GST & PAN</th>
                            <th>Payment Terms</th>
                            <th>Invoices & Collections</th>
                            <th>Billing Address</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $cust)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">{{ $customers->firstItem() + $loop->index }}</td>
                            <td>
                                <div style="font-weight:800; color:#0f172a; font-size:14px;">
                                    {{ $cust->customer_name }}
                                    @if($cust->lead_id)
                                        <span title="Converted from Lead #{{ $cust->lead_id }}" style="font-size:10px; background:#dbeafe; color:#1d4ed8; padding:2px 6px; border-radius:4px; margin-left:4px; font-weight:700;">
                                             <i class="bi bi-check2-circle"></i> Lead
                                        </span>
                                    @endif
                                </div>
                                @if($cust->company_name)
                                    <div style="font-size:12px; color:#64748b; font-weight:600; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                        <i class="bi bi-building"></i> {{ $cust->company_name }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($cust->mobile_number)
                                    <div style="font-size:12px; color:#0f172a; font-weight:600; display:flex; align-items:center; gap:4px;">
                                        <i class="bi bi-telephone text-muted"></i> {{ $cust->mobile_number }}
                                    </div>
                                @endif
                                @if($cust->email)
                                    <div style="font-size:12px; color:#64748b; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                        <i class="bi bi-envelope text-muted"></i> {{ $cust->email }}
                                    </div>
                                @endif
                                @if(!$cust->mobile_number && !$cust->email)
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($cust->gst_number)
                                    <div>
                                        <span class="code-chip">GST: {{ $cust->gst_number }}</span>
                                    </div>
                                @endif
                                @if($cust->pan_number)
                                    <div style="margin-top:2px;">
                                        <span class="code-chip">PAN: {{ $cust->pan_number }}</span>
                                    </div>
                                @endif
                                @if(!$cust->gst_number && !$cust->pan_number)
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($cust->payment_terms)
                                    <span class="terms-chip">{{ $cust->payment_terms }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($cust->invoices_count > 0)
                                    <div>
                                        <span style="font-weight:800; color:#0f172a; font-size:13px;">{{ $cust->invoices_count }} {{ Str::plural('Invoice', $cust->invoices_count) }}</span>
                                    </div>
                                    <div style="font-size:11px; margin-top:2px; display:flex; flex-direction:column; gap:1px;">
                                        <span style="color:#475569;">Billed: <strong>₹{{ number_format((float)$cust->invoices_total_amount, 2) }}</strong></span>
                                        <span style="color:#15803d; font-weight:700;">Paid: ₹{{ number_format((float)$cust->invoices_paid_amount, 2) }}</span>
                                        @if(((float)$cust->invoices_balance_due) > 0)
                                            <span style="color:#b91c1c; font-weight:700;">Due: ₹{{ number_format((float)$cust->invoices_balance_due, 2) }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:#94a3b8; font-size:12px;">0 Invoices</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:12px; color:#334155; max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $cust->full_billing_address }}">
                                    @if($cust->billing_city || $cust->billing_state)
                                        <strong>{{ implode(', ', array_filter([$cust->billing_city, $cust->billing_state])) }}</strong>
                                    @endif
                                    @if($cust->billing_address_street_1)
                                        <div style="font-size:11px; color:#64748b; margin-top:1px;">{{ Str::limit($cust->billing_address_street_1, 35) }}</div>
                                    @endif
                                    @if(!$cust->billing_city && !$cust->billing_state && !$cust->billing_address_street_1)
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="acm-badge status-{{ $cust->status }}">{{ ucfirst($cust->status) }}</span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    {{-- View Details --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick="openViewCustomerModal({{ $cust->id }})"
                                            title="View Full Customer Details & Invoices">
                                        <i class="bi bi-eye"></i> View
                                    </button>

                                    {{-- Edit --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick='openEditCustomerModal(@json($cust))'
                                            title="Edit Customer">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>

                                    {{-- Toggle Status --}}
                                    <form method="POST" action="{{ route('accounts.master.customers.toggle-status', $cust->id) }}" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Toggle Active/Inactive">
                                            {{ $cust->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('accounts.master.customers.destroy', $cust->id) }}" onsubmit="return confirm('Are you sure you want to delete customer: {{ addslashes($cust->customer_name) }}?')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Customer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                    {{ $customers->links() }}
                </div>
            @endif
        @endif
    </div>
    </div>
</div>

{{-- Modal 1: Add Customer --}}
<div id="addCustomerModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-people" style="color:#d97706;"></i>
                <span>Add New Customer</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('addCustomerModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.master.customers.store') }}" id="addCustomerForm">
            @csrf
            <input type="hidden" name="lead_id" id="addLeadId" value="">

            <div class="modal-body">

                {{-- Converted Leads Auto-Fetch Bar --}}
                <div class="lead-select-card">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <i class="bi bi-lightning-charge-fill" style="color:#ea580c; font-size:16px;"></i>
                            <strong style="font-size:13px; color:#9a3412;">Auto-Fetch from Converted CRM Lead</strong>
                        </div>
                        <div id="leadSelectedIndicator" style="display:none;">
                            <span class="lead-badge-chip">
                                <i class="bi bi-check-circle-fill"></i> Autofilled from Lead <span id="leadBadgeName"></span>
                            </span>
                            <button type="button" onclick="clearLeadSelection('add')" style="background:none; border:none; color:#b91c1c; font-size:11px; font-weight:700; cursor:pointer; text-decoration:underline; margin-left:6px;">Clear</button>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr auto; gap:10px; align-items:center;">
                        <div style="position:relative;">
                            <input type="text"
                                   id="addLeadSearchInput"
                                   class="form-input"
                                   placeholder="Search converted leads by Contact Name, Company Name, or Mobile Number…"
                                   oninput="debounceLeadSearch(this.value, 'add')">
                            <div id="addLeadSearchResults" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:50; background:#fff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,.15); max-height:220px; overflow-y:auto; margin-top:4px;"></div>
                        </div>
                        <select id="addRecentLeadsSelect" class="form-input" style="max-width:280px;" onchange="onLeadSelectChanged(this.value, 'add')">
                            <option value="">-- Or Pick Recent Converted Lead --</option>
                            @foreach($recentConvertedLeads as $rLead)
                                <option value="{{ $rLead->id }}">
                                    {{ $rLead->contact_name ?: $rLead->company_name }} ({{ $rLead->mobile_number ?: 'No Mobile' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Group 1: General & Contact Info --}}
                <div>
                    <div class="form-section-title">
                        <span><i class="bi bi-person-badge"></i> Customer Profile & Business Info</span>
                    </div>
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Customer Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="customer_name" id="addCustomerName" class="form-input" placeholder="e.g. Ramesh Kumar" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" id="addCompanyName" class="form-input" placeholder="e.g. Acme Technologies Pvt Ltd">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile_number" id="addMobileNumber" class="form-input" placeholder="e.g. +91 98765 43210">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="addEmail" class="form-input" placeholder="e.g. client@company.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">PAN Number</label>
                            <input type="text" name="pan_number" id="addPanNumber" class="form-input" placeholder="e.g. ABCDE1234F" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" id="addGstNumber" class="form-input" placeholder="e.g. 33AAAAA0000A1Z5" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <select name="payment_terms" id="addPaymentTerms" class="form-input">
                                <option value="">Select Payment Terms</option>
                                <option value="Due on Receipt">Due on Receipt</option>
                                <option value="Net 15">Net 15</option>
                                <option value="Net 30">Net 30</option>
                                <option value="Net 45">Net 45</option>
                                <option value="Net 60">Net 60</option>
                                <option value="Due End of Month">Due End of Month</option>
                                <option value="Due End of Next Month">Due End of Next Month</option>
                                <option value="50% Advance, 50% on Delivery">50% Advance, 50% on Delivery</option>
                                <option value="100% Advance">100% Advance</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Group 2: Address Details (Matches User Screenshot) --}}
                <div>
                    <div class="form-section-title">
                        <span><i class="bi bi-geo-alt"></i> Address Details</span>
                    </div>

                    <div class="address-container">
                        {{-- Left Column: Billing Address --}}
                        <div>
                            <div class="address-col-title">
                                <span>Billing Address</span>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Attention</span>
                                <input type="text" name="billing_attention" id="addBillingAttention" class="form-input">
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Country/Region</span>
                                <select name="billing_country" id="addBillingCountry" class="form-input">
                                    <option value="India" selected>India</option>
                                    <option value="United States">United States</option>
                                    <option value="United Arab Emirates">United Arab Emirates</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="Singapore">Singapore</option>
                                    <option value="Malaysia">Malaysia</option>
                                    <option value="Australia">Australia</option>
                                    <option value="Canada">Canada</option>
                                </select>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Address</span>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <textarea name="billing_address_street_1" id="addBillingStreet1" rows="2" class="form-input" placeholder="Street 1"></textarea>
                                    <textarea name="billing_address_street_2" id="addBillingStreet2" rows="2" class="form-input" placeholder="Street 2"></textarea>
                                </div>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">City</span>
                                <input type="text" name="billing_city" id="addBillingCity" class="form-input">
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">State</span>
                                <select name="billing_state" id="addBillingState" class="form-input">
                                    <option value="">Select or type to add</option>
                                    <option value="Tamil Nadu" selected>Tamil Nadu</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Haryana">Haryana</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Other">Other State</option>
                                </select>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Pin Code</span>
                                <input type="text" name="billing_pin_code" id="addBillingPinCode" class="form-input">
                            </div>
                        </div>

                        {{-- Right Column: Shipping Address (with Copy Link) --}}
                        <div>
                            <div class="address-col-title">
                                <span>Shipping Address</span>
                                <button type="button" class="copy-address-btn" onclick="copyBillingToShipping('add')">
                                    <i class="bi bi-arrow-down-short" style="font-size:16px;"></i> Copy billing address
                                </button>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Attention</span>
                                <input type="text" name="shipping_attention" id="addShippingAttention" class="form-input">
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Country/Region</span>
                                <select name="shipping_country" id="addShippingCountry" class="form-input">
                                    <option value="India" selected>India</option>
                                    <option value="United States">United States</option>
                                    <option value="United Arab Emirates">United Arab Emirates</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="Singapore">Singapore</option>
                                    <option value="Malaysia">Malaysia</option>
                                    <option value="Australia">Australia</option>
                                    <option value="Canada">Canada</option>
                                </select>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Address</span>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <textarea name="shipping_address_street_1" id="addShippingStreet1" rows="2" class="form-input" placeholder="Street 1"></textarea>
                                    <textarea name="shipping_address_street_2" id="addShippingStreet2" rows="2" class="form-input" placeholder="Street 2"></textarea>
                                </div>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">City</span>
                                <input type="text" name="shipping_city" id="addShippingCity" class="form-input">
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">State</span>
                                <select name="shipping_state" id="addShippingState" class="form-input">
                                    <option value="">Select or type to add</option>
                                    <option value="Tamil Nadu" selected>Tamil Nadu</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Haryana">Haryana</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Other">Other State</option>
                                </select>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Pin Code</span>
                                <input type="text" name="shipping_pin_code" id="addShippingPinCode" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Group 3: Remarks --}}
                <div class="form-group">
                    <label class="form-label">Remarks / Internal Notes</label>
                    <textarea name="remarks" id="addRemarks" class="form-input" rows="2" placeholder="Any internal notes or special instructions regarding this client…"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:6px;">
                    <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('addCustomerModal')">Cancel</button>
                    <button type="submit" id="addCustomerSubmitBtn" class="acm-btn acm-btn-primary">
                        <i class="bi bi-check-lg"></i> Create Customer
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Edit Customer --}}
<div id="editCustomerModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#fe5f04;"></i>
                <span>Edit Customer</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editCustomerModal')">&times;</button>
        </div>
        <form method="POST" id="editCustomerForm" action="">
            @csrf
            @method('PUT')
            <input type="hidden" name="lead_id" id="editLeadId" value="">

            <div class="modal-body">

                {{-- Group 1: General & Tax Details --}}
                <div>
                    <div class="form-section-title">
                        <span><i class="bi bi-person-badge"></i> Customer Profile & Business Info</span>
                    </div>
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Customer Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="customer_name" id="editCustomerName" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" id="editCompanyName" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile_number" id="editMobileNumber" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="editEmail" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">PAN Number</label>
                            <input type="text" name="pan_number" id="editPanNumber" class="form-input" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" id="editGstNumber" class="form-input" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <select name="payment_terms" id="editPaymentTerms" class="form-input">
                                <option value="">Select Payment Terms</option>
                                <option value="Due on Receipt">Due on Receipt</option>
                                <option value="Net 15">Net 15</option>
                                <option value="Net 30">Net 30</option>
                                <option value="Net 45">Net 45</option>
                                <option value="Net 60">Net 60</option>
                                <option value="Due End of Month">Due End of Month</option>
                                <option value="Due End of Next Month">Due End of Next Month</option>
                                <option value="50% Advance, 50% on Delivery">50% Advance, 50% on Delivery</option>
                                <option value="100% Advance">100% Advance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status <span style="color:#dc2626;">*</span></label>
                            <select name="status" id="editStatus" class="form-input" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Group 2: Address Details (Matches User Screenshot) --}}
                <div>
                    <div class="form-section-title">
                        <span><i class="bi bi-geo-alt"></i> Address Details</span>
                    </div>

                    <div class="address-container">
                        {{-- Left Column: Billing Address --}}
                        <div>
                            <div class="address-col-title">
                                <span>Billing Address</span>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Attention</span>
                                <input type="text" name="billing_attention" id="editBillingAttention" class="form-input">
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Country/Region</span>
                                <select name="billing_country" id="editBillingCountry" class="form-input">
                                    <option value="India">India</option>
                                    <option value="United States">United States</option>
                                    <option value="United Arab Emirates">United Arab Emirates</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="Singapore">Singapore</option>
                                    <option value="Malaysia">Malaysia</option>
                                    <option value="Australia">Australia</option>
                                    <option value="Canada">Canada</option>
                                </select>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Address</span>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <textarea name="billing_address_street_1" id="editBillingStreet1" rows="2" class="form-input" placeholder="Street 1"></textarea>
                                    <textarea name="billing_address_street_2" id="editBillingStreet2" rows="2" class="form-input" placeholder="Street 2"></textarea>
                                </div>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">City</span>
                                <input type="text" name="billing_city" id="editBillingCity" class="form-input">
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">State</span>
                                <select name="billing_state" id="editBillingState" class="form-input">
                                    <option value="">Select or type to add</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Haryana">Haryana</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Other">Other State</option>
                                </select>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Pin Code</span>
                                <input type="text" name="billing_pin_code" id="editBillingPinCode" class="form-input">
                            </div>
                        </div>

                        {{-- Right Column: Shipping Address (with Copy Link) --}}
                        <div>
                            <div class="address-col-title">
                                <span>Shipping Address</span>
                                <button type="button" class="copy-address-btn" onclick="copyBillingToShipping('edit')">
                                    <i class="bi bi-arrow-down-short" style="font-size:16px;"></i> Copy billing address
                                </button>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Attention</span>
                                <input type="text" name="shipping_attention" id="editShippingAttention" class="form-input">
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Country/Region</span>
                                <select name="shipping_country" id="editShippingCountry" class="form-input">
                                    <option value="India">India</option>
                                    <option value="United States">United States</option>
                                    <option value="United Arab Emirates">United Arab Emirates</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="Singapore">Singapore</option>
                                    <option value="Malaysia">Malaysia</option>
                                    <option value="Australia">Australia</option>
                                    <option value="Canada">Canada</option>
                                </select>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Address</span>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <textarea name="shipping_address_street_1" id="editShippingStreet1" rows="2" class="form-input" placeholder="Street 1"></textarea>
                                    <textarea name="shipping_address_street_2" id="editShippingStreet2" rows="2" class="form-input" placeholder="Street 2"></textarea>
                                </div>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">City</span>
                                <input type="text" name="shipping_city" id="editShippingCity" class="form-input">
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">State</span>
                                <select name="shipping_state" id="editShippingState" class="form-input">
                                    <option value="">Select or type to add</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Kerala">Kerala</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="Andhra Pradesh">Andhra Pradesh</option>
                                    <option value="Telangana">Telangana</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Gujarat">Gujarat</option>
                                    <option value="Haryana">Haryana</option>
                                    <option value="Uttar Pradesh">Uttar Pradesh</option>
                                    <option value="West Bengal">West Bengal</option>
                                    <option value="Rajasthan">Rajasthan</option>
                                    <option value="Punjab">Punjab</option>
                                    <option value="Other">Other State</option>
                                </select>
                            </div>

                            <div class="address-row">
                                <span class="address-row-label">Pin Code</span>
                                <input type="text" name="shipping_pin_code" id="editShippingPinCode" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Group 3: Remarks --}}
                <div class="form-group">
                    <label class="form-label">Remarks / Internal Notes</label>
                    <textarea name="remarks" id="editRemarks" class="form-input" rows="2"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:6px;">
                    <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('editCustomerModal')">Cancel</button>
                    <button type="submit" id="editCustomerSubmitBtn" class="acm-btn acm-btn-primary">
                        <i class="bi bi-check-lg"></i> Update Customer
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Modal 3: View Customer --}}
<div id="viewCustomerModal" class="modal-overlay">
    <div class="modal-box" style="max-width:1050px;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-people" style="color:#fe5f04;"></i>
                <span id="viewCustomerTitle">Customer Profile</span>
                <span id="vStatusBadge" style="margin-left:8px;"></span>
                <span id="vLeadBadge" style="display:none; margin-left:6px;"></span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('viewCustomerModal')">&times;</button>
        </div>
        <div class="modal-body" style="gap:18px;">

            {{-- 4 KPI Summary Cards --}}
            <div class="v-kpi-grid">
                {{-- 1. Total Invoices --}}
                <div class="v-kpi-card" style="border-left:4px solid #2563eb;">
                    <div class="v-kpi-icon" style="background:#eff6ff; color:#2563eb;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="v-kpi-title">Total Invoices</div>
                        <div class="v-kpi-val" id="vTotalInvoicesCount">0</div>
                        <div class="v-kpi-sub">Issued Invoices</div>
                    </div>
                </div>

                {{-- 2. Total Invoiced Amount --}}
                <div class="v-kpi-card" style="border-left:4px solid #fe5f04;">
                    <div class="v-kpi-icon" style="background:#fff7ed; color:#ea580c;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="v-kpi-title">Total Invoiced</div>
                        <div class="v-kpi-val" id="vTotalInvoicedAmount" style="color:#0f172a;">₹ 0.00</div>
                        <div class="v-kpi-sub">Total Billed Value</div>
                    </div>
                </div>

                {{-- 3. Collected Payment Amount --}}
                <div class="v-kpi-card" style="border-left:4px solid #10b981; background:#f0fdf4;">
                    <div class="v-kpi-icon" style="background:#dcfce7; color:#15803d;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="v-kpi-title" style="color:#166534;">Collected Payment</div>
                        <div class="v-kpi-val" id="vTotalCollectedAmount" style="color:#15803d;">₹ 0.00</div>
                        <div class="v-kpi-sub" style="color:#16a34a;">Total Amount Received</div>
                    </div>
                </div>

                {{-- 4. Balance Due --}}
                <div class="v-kpi-card" style="border-left:4px solid #ef4444; background:#fff7ed;">
                    <div class="v-kpi-icon" style="background:#fee2e2; color:#b91c1c;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="v-kpi-title" style="color:#991b1b;">Balance Due</div>
                        <div class="v-kpi-val" id="vTotalBalanceDue" style="color:#b91c1c;">₹ 0.00</div>
                        <div class="v-kpi-sub" style="color:#c2410c;">Remaining Outstanding</div>
                    </div>
                </div>
            </div>

            {{-- Tabs Navigation --}}
            <div class="v-tabs-bar">
                <button type="button" class="v-tab-btn active" id="tabBtnInvoices" onclick="switchCustomerTab('invoices')">
                    <i class="bi bi-receipt-cutoff"></i> Invoices & Payments
                    <span class="v-tab-badge" id="vTabInvoicesCount">0</span>
                </button>
                <button type="button" class="v-tab-btn" id="tabBtnProfile" onclick="switchCustomerTab('profile')">
                    <i class="bi bi-person-lines-fill"></i> Customer Details & Address
                </button>
            </div>

            {{-- Tab Panel 1: Invoices & Payment Collection History --}}
            <div id="tabPanelInvoices">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <div style="font-size:14px; font-weight:800; color:#0f172a;">Invoices Generated Against Customer</div>
                        <div style="font-size:12px; color:#64748b;">All sales invoices, payments collected, and balance dues for this account</div>
                    </div>
                    <div>
                        <a id="vCreateInvoiceBtn" href="#" class="acm-btn acm-btn-primary" style="padding:6px 14px; font-size:12px;">
                            <i class="bi bi-plus-lg"></i> Create New Invoice
                        </a>
                    </div>
                </div>

                <div id="vInvoicesLoading" style="text-align:center; padding:30px 10px; color:#64748b;">
                    <i class="bi bi-arrow-repeat acm-spin" style="font-size:24px; color:#fe5f04;"></i>
                    <div style="font-size:12px; font-weight:600; margin-top:8px;">Loading invoices & payment history...</div>
                </div>

                <div id="vInvoicesEmpty" style="display:none; text-align:center; padding:36px 16px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px;">
                    <i class="bi bi-file-earmark-x" style="font-size:36px; color:#94a3b8; display:block; margin-bottom:8px;"></i>
                    <div style="font-size:14px; font-weight:700; color:#334155; margin-bottom:4px;">No Invoices Created Yet</div>
                    <div style="font-size:12px; color:#64748b; margin-bottom:14px;">No sales invoices have been issued to this customer. Click below to create one.</div>
                    <a id="vCreateFirstInvoiceBtn" href="#" class="acm-btn acm-btn-primary" style="padding:6px 14px; font-size:12px;">
                        <i class="bi bi-plus-lg"></i> Create First Invoice
                    </a>
                </div>

                <div id="vInvoicesTableWrap" style="display:none; overflow-x:auto; border:1px solid #e2e8f0; border-radius:10px;">
                    <table class="acm-tbl" style="margin:0;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th style="text-align:right;">Invoice Amount</th>
                                <th style="text-align:right; color:#15803d;">Payment Collected</th>
                                <th style="text-align:right; color:#b91c1c;">Balance Due</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="vInvoicesTableBody">
                            {{-- Populated dynamically via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab Panel 2: Customer Profile & Address Details --}}
            <div id="tabPanelProfile" style="display:none; flex-direction:column; gap:16px;">
                <div class="detail-grid">
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <div class="detail-label">Customer & Company Name</div>
                        <div class="detail-val" id="vCustomerName" style="font-size:16px; color:#0f172a;"></div>
                        <div id="vCompanyName" style="font-size:13px; color:#64748b; margin-top:2px; font-weight:600;"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Mobile Number</div>
                        <div class="detail-val" id="vMobileNumber"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-val" id="vEmail"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">GST Number</div>
                        <div class="detail-val" id="vGstNumber"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">PAN Number</div>
                        <div class="detail-val" id="vPanNumber"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Payment Terms</div>
                        <div class="detail-val" id="vPaymentTerms"></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Registered Date</div>
                        <div class="detail-val" id="vCreatedAt"></div>
                    </div>
                </div>

                {{-- Addresses side by side in view --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
                        <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-geo-alt-fill" style="color:#2563eb;"></i> Billing Address
                        </div>
                        <div id="vFullBillingAddress" style="font-size:13px; color:#334155; line-height:1.6; white-space:pre-line;"></div>
                    </div>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px;">
                        <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-truck" style="color:#059669;"></i> Shipping Address
                        </div>
                        <div id="vFullShippingAddress" style="font-size:13px; color:#334155; line-height:1.6; white-space:pre-line;"></div>
                    </div>
                </div>

                <div class="detail-item" id="vRemarksWrap">
                    <div class="detail-label">Remarks / Notes</div>
                    <div class="detail-val" id="vRemarks" style="font-weight:500; line-height:1.6;"></div>
                </div>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; margin-top:10px; padding-top:14px; border-top:1px solid #e2e8f0;">
                <div>
                    <span id="vLeadLinkWrap" style="display:none;">
                        <a id="vLeadLink" href="#" target="_blank" class="acm-btn acm-btn-secondary" style="font-size:11px; padding:6px 12px;">
                            <i class="bi bi-box-arrow-up-right"></i> View Converted Lead CRM Details
                        </a>
                    </span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <a id="vBalanceSheetBtn" href="#" target="_blank" class="acm-btn acm-btn-primary" style="font-size:12px; padding:6px 14px;">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i> Balance Sheet
                    </a>
                    <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('viewCustomerModal')">Close</button>
                    <button type="button" class="acm-btn acm-btn-outline" id="vEditCustomerBtn" onclick="">
                        <i class="bi bi-pencil-square"></i> Edit Customer
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
let leadSearchTimeout = null;
let isAddCustomerSubmitting = false;
let isEditCustomerSubmitting = false;

function resetAddCustomerSubmitBtn() {
    isAddCustomerSubmitting = false;
    const btn = document.getElementById('addCustomerSubmitBtn');
    if (btn) {
        btn.disabled = false;
        btn.classList.remove('disabled');
        btn.style.pointerEvents = 'auto';
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Create Customer';
    }
}

function resetEditCustomerSubmitBtn() {
    isEditCustomerSubmitting = false;
    const btn = document.getElementById('editCustomerSubmitBtn');
    if (btn) {
        btn.disabled = false;
        btn.classList.remove('disabled');
        btn.style.pointerEvents = 'auto';
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Update Customer';
    }
}

function openAddCustomerModal() {
    resetAddCustomerSubmitBtn();
    document.getElementById('addCustomerForm').reset();
    document.getElementById('addLeadId').value = '';
    document.getElementById('leadSelectedIndicator').style.display = 'none';
    document.getElementById('addCustomerModal').classList.add('active');
}

function openEditCustomerModal(customer) {
    resetEditCustomerSubmitBtn();
    const form = document.getElementById('editCustomerForm');
    form.action = "{{ url('accounts/master/customers') }}/" + customer.id;

    document.getElementById('editLeadId').value = customer.lead_id || '';
    document.getElementById('editCustomerName').value = customer.customer_name || '';
    document.getElementById('editCompanyName').value = customer.company_name || '';
    document.getElementById('editMobileNumber').value = customer.mobile_number || '';
    document.getElementById('editEmail').value = customer.email || '';
    document.getElementById('editPanNumber').value = customer.pan_number || '';
    document.getElementById('editGstNumber').value = customer.gst_number || '';
    document.getElementById('editPaymentTerms').value = customer.payment_terms || '';
    document.getElementById('editStatus').value = customer.status || 'active';

    // Billing
    document.getElementById('editBillingAttention').value = customer.billing_attention || '';
    document.getElementById('editBillingCountry').value = customer.billing_country || 'India';
    document.getElementById('editBillingStreet1').value = customer.billing_address_street_1 || '';
    document.getElementById('editBillingStreet2').value = customer.billing_address_street_2 || '';
    document.getElementById('editBillingCity').value = customer.billing_city || '';
    document.getElementById('editBillingState').value = customer.billing_state || '';
    document.getElementById('editBillingPinCode').value = customer.billing_pin_code || '';

    // Shipping
    document.getElementById('editShippingAttention').value = customer.shipping_attention || '';
    document.getElementById('editShippingCountry').value = customer.shipping_country || 'India';
    document.getElementById('editShippingStreet1').value = customer.shipping_address_street_1 || '';
    document.getElementById('editShippingStreet2').value = customer.shipping_address_street_2 || '';
    document.getElementById('editShippingCity').value = customer.shipping_city || '';
    document.getElementById('editShippingState').value = customer.shipping_state || '';
    document.getElementById('editShippingPinCode').value = customer.shipping_pin_code || '';

    document.getElementById('editRemarks').value = customer.remarks || '';

    document.getElementById('editCustomerModal').classList.add('active');
}

let currentCustomerData = null;

function switchCustomerTab(tab) {
    const tabBtnInvoices = document.getElementById('tabBtnInvoices');
    const tabBtnProfile = document.getElementById('tabBtnProfile');
    const panelInvoices = document.getElementById('tabPanelInvoices');
    const panelProfile = document.getElementById('tabPanelProfile');

    if (!tabBtnInvoices || !tabBtnProfile || !panelInvoices || !panelProfile) return;

    if (tab === 'invoices') {
        tabBtnInvoices.classList.add('active');
        tabBtnProfile.classList.remove('active');
        panelInvoices.style.display = 'block';
        panelProfile.style.display = 'none';
    } else {
        tabBtnProfile.classList.add('active');
        tabBtnInvoices.classList.remove('active');
        panelProfile.style.display = 'flex';
        panelInvoices.style.display = 'none';
    }
}

function openViewCustomerModal(customerId) {
    // Reset tabs to Invoices view
    switchCustomerTab('invoices');

    // Show modal
    document.getElementById('viewCustomerModal').classList.add('active');

    // Reset KPIs to loading state
    document.getElementById('viewCustomerTitle').textContent = 'Loading Customer...';
    document.getElementById('vStatusBadge').innerHTML = '';
    document.getElementById('vLeadBadge').style.display = 'none';
    document.getElementById('vLeadLinkWrap').style.display = 'none';

    document.getElementById('vTotalInvoicesCount').textContent = '—';
    document.getElementById('vTabInvoicesCount').textContent = '0';
    document.getElementById('vTotalInvoicedAmount').textContent = '₹ 0.00';
    document.getElementById('vTotalCollectedAmount').textContent = '₹ 0.00';
    document.getElementById('vTotalBalanceDue').textContent = '₹ 0.00';

    document.getElementById('vInvoicesLoading').style.display = 'block';
    document.getElementById('vInvoicesEmpty').style.display = 'none';
    document.getElementById('vInvoicesTableWrap').style.display = 'none';

    fetch("{{ url('accounts/master/customers') }}/" + customerId)
        .then(res => res.json())
        .then(data => {
            if (!data || !data.customer) return;

            const c = data.customer;
            const f = data.financials;
            const invs = data.invoices;

            currentCustomerData = c;

            // Set Title & Badges
            document.getElementById('viewCustomerTitle').textContent = c.customer_name || 'Customer Profile';
            const statusBadge = c.status === 'active'
                ? '<span class="acm-badge status-active">Active</span>'
                : '<span class="acm-badge status-inactive">Inactive</span>';
            document.getElementById('vStatusBadge').innerHTML = statusBadge;

            if (c.lead_id) {
                const leadBadge = document.getElementById('vLeadBadge');
                leadBadge.innerHTML = `<span style="font-size:11px; background:#dbeafe; color:#1d4ed8; padding:3px 8px; border-radius:6px; font-weight:700;"><i class="bi bi-check2-circle"></i> Converted Lead #${c.lead_id}</span>`;
                leadBadge.style.display = 'inline-block';

                const leadLinkWrap = document.getElementById('vLeadLinkWrap');
                const leadLink = document.getElementById('vLeadLink');
                leadLink.href = "{{ url('crm/leads') }}/" + c.lead_id;
                leadLinkWrap.style.display = 'inline-block';
            }

            // Edit button handler
            document.getElementById('vEditCustomerBtn').onclick = function() {
                closeModal('viewCustomerModal');
                openEditCustomerModal(c);
            };

            // Set Create Invoice links
            const createUrl = data.create_invoice_url || ("{{ route('accounts.sales.invoices.create') }}?customer_id=" + c.id);
            document.getElementById('vCreateInvoiceBtn').href = createUrl;
            document.getElementById('vCreateFirstInvoiceBtn').href = createUrl;

            // Set Balance Sheet Link
            const bsBtn = document.getElementById('vBalanceSheetBtn');
            if (bsBtn) {
                bsBtn.href = "{{ route('accounts.reports.customer-balance-sheet') }}?customer_id=" + c.id;
            }

            // Set Financial KPIs
            document.getElementById('vTotalInvoicesCount').textContent = f.invoices_count;
            document.getElementById('vTabInvoicesCount').textContent = f.invoices_count;
            document.getElementById('vTotalInvoicedAmount').textContent = f.total_invoiced_formatted;
            document.getElementById('vTotalCollectedAmount').textContent = f.total_collected_formatted;
            document.getElementById('vTotalBalanceDue').textContent = f.total_balance_formatted;

            // Invoices Table
            document.getElementById('vInvoicesLoading').style.display = 'none';
            if (invs && invs.length > 0) {
                let rowsHtml = '';
                invs.forEach((inv) => {
                    rowsHtml += `
                        <tr>
                            <td>
                                <a href="${inv.view_url}" style="font-weight:800; color:#0f172a; text-decoration:none;" onmouseover="this.style.color='#fe5f04'" onmouseout="this.style.color='#0f172a'">
                                    ${escapeHtml(inv.invoice_number)}
                                </a>
                            </td>
                            <td style="color:#475569; font-size:12px;">${escapeHtml(inv.invoice_date)}</td>
                            <td style="color:#64748b; font-size:12px;">${escapeHtml(inv.due_date)}</td>
                            <td style="text-align:right; font-weight:800; color:#0f172a;">${escapeHtml(inv.total_formatted)}</td>
                            <td style="text-align:right; font-weight:800; color:#15803d;">${escapeHtml(inv.paid_formatted)}</td>
                            <td style="text-align:right; font-weight:800; color:${inv.balance_due > 0 ? '#dc2626' : '#15803d'};">${escapeHtml(inv.due_formatted)}</td>
                            <td>${inv.status_badge}</td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <a href="${inv.view_url}" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;" title="View Invoice">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="${inv.print_url}" target="_blank" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Print Invoice">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                document.getElementById('vInvoicesTableBody').innerHTML = rowsHtml;
                document.getElementById('vInvoicesTableWrap').style.display = 'block';
                document.getElementById('vInvoicesEmpty').style.display = 'none';
            } else {
                document.getElementById('vInvoicesTableWrap').style.display = 'none';
                document.getElementById('vInvoicesEmpty').style.display = 'block';
            }

            // Customer Profile Tab Fields
            document.getElementById('vCustomerName').textContent = c.customer_name || '—';
            document.getElementById('vCompanyName').textContent = c.company_name ? c.company_name : '';
            document.getElementById('vMobileNumber').textContent = c.mobile_number || '—';
            document.getElementById('vEmail').textContent = c.email || '—';
            document.getElementById('vGstNumber').innerHTML = c.gst_number ? `<span class="code-chip">${escapeHtml(c.gst_number)}</span>` : '<span style="color:#94a3b8;">—</span>';
            document.getElementById('vPanNumber').innerHTML = c.pan_number ? `<span class="code-chip">${escapeHtml(c.pan_number)}</span>` : '<span style="color:#94a3b8;">—</span>';
            document.getElementById('vPaymentTerms').innerHTML = c.payment_terms ? `<span class="terms-chip">${escapeHtml(c.payment_terms)}</span>` : '<span style="color:#94a3b8;">—</span>';
            document.getElementById('vCreatedAt').textContent = c.created_at || '—';

            document.getElementById('vFullBillingAddress').textContent = c.full_billing_address || 'No billing address provided.';
            document.getElementById('vFullShippingAddress').textContent = c.full_shipping_address || 'No shipping address provided.';

            if (c.remarks) {
                document.getElementById('vRemarksWrap').style.display = 'block';
                document.getElementById('vRemarks').textContent = c.remarks;
            } else {
                document.getElementById('vRemarksWrap').style.display = 'none';
            }
        })
        .catch(err => {
            console.error('Error fetching customer details:', err);
            document.getElementById('vInvoicesLoading').innerHTML = '<span style="color:#dc2626; font-size:12px;">Failed to load customer details. Please try again.</span>';
        });
}

// Copy Billing Address to Shipping Address (Screenshot Feature)
function copyBillingToShipping(mode) {
    const prefix = mode === 'edit' ? 'edit' : 'add';
    document.getElementById(prefix + 'ShippingAttention').value = document.getElementById(prefix + 'BillingAttention').value;
    document.getElementById(prefix + 'ShippingCountry').value = document.getElementById(prefix + 'BillingCountry').value;
    document.getElementById(prefix + 'ShippingStreet1').value = document.getElementById(prefix + 'BillingStreet1').value;
    document.getElementById(prefix + 'ShippingStreet2').value = document.getElementById(prefix + 'BillingStreet2').value;
    document.getElementById(prefix + 'ShippingCity').value = document.getElementById(prefix + 'BillingCity').value;
    document.getElementById(prefix + 'ShippingState').value = document.getElementById(prefix + 'BillingState').value;
    document.getElementById(prefix + 'ShippingPinCode').value = document.getElementById(prefix + 'BillingPinCode').value;
}

// Live Search Converted Leads
function debounceLeadSearch(query, mode) {
    clearTimeout(leadSearchTimeout);
    const resultsBox = document.getElementById(mode + 'LeadSearchResults');
    if (!query || query.trim().length < 2) {
        resultsBox.style.display = 'none';
        return;
    }

    leadSearchTimeout = setTimeout(() => {
        fetch("{{ route('accounts.master.customers.search-converted-leads') }}?q=" + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (!data || !data.length) {
                    resultsBox.innerHTML = '<div style="padding:10px 14px; font-size:12px; color:#94a3b8;">No matching converted leads found</div>';
                    resultsBox.style.display = 'block';
                    return;
                }

                let html = '';
                data.forEach(item => {
                    html += `
                        <div onclick="selectLeadFromSearch(${item.id}, '${escapeHtml(item.name)}', '${mode}')"
                             style="padding:8px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .15s;"
                             onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                            <div style="font-weight:700; font-size:13px; color:#0f172a;">${escapeHtml(item.name)}</div>
                            <div style="font-size:11px; color:#64748b; display:flex; gap:10px; margin-top:2px;">
                                ${item.company_name ? `<span><i class="bi bi-building"></i> ${escapeHtml(item.company_name)}</span>` : ''}
                                ${item.mobile_number ? `<span><i class="bi bi-telephone"></i> ${escapeHtml(item.mobile_number)}</span>` : ''}
                            </div>
                        </div>
                    `;
                });
                resultsBox.innerHTML = html;
                resultsBox.style.display = 'block';
            })
            .catch(() => {
                resultsBox.style.display = 'none';
            });
    }, 280);
}

function selectLeadFromSearch(leadId, leadName, mode) {
    document.getElementById(mode + 'LeadSearchResults').style.display = 'none';
    document.getElementById(mode + 'LeadSearchInput').value = leadName;
    fetchLeadDetailsAndAutoFill(leadId, mode);
}

function onLeadSelectChanged(leadId, mode) {
    if (!leadId) return;
    fetchLeadDetailsAndAutoFill(leadId, mode);
}

// Fetch Lead Details and Auto-Fill Fields
function fetchLeadDetailsAndAutoFill(leadId, mode) {
    const url = "{{ url('accounts/master/customers/lead-details') }}/" + leadId;
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data) return;

            document.getElementById(mode + 'LeadId').value = data.id;

            // Fill General Fields
            if (data.customer_name) document.getElementById(mode + 'CustomerName').value = data.customer_name;
            if (data.company_name) document.getElementById(mode + 'CompanyName').value = data.company_name;
            if (data.mobile_number) document.getElementById(mode + 'MobileNumber').value = data.mobile_number;
            if (data.email) document.getElementById(mode + 'Email').value = data.email;

            // Attention in Billing / Shipping
            if (data.customer_name) {
                document.getElementById(mode + 'BillingAttention').value = data.customer_name;
                document.getElementById(mode + 'ShippingAttention').value = data.customer_name;
            }

            // City
            if (data.city) {
                document.getElementById(mode + 'BillingCity').value = data.city;
                document.getElementById(mode + 'ShippingCity').value = data.city;
            }

            // State
            if (data.state) {
                const bState = document.getElementById(mode + 'BillingState');
                if (bState) bState.value = data.state;
                const sState = document.getElementById(mode + 'ShippingState');
                if (sState) sState.value = data.state;
            }

            // Address / Location
            if (data.address) {
                document.getElementById(mode + 'BillingStreet1').value = data.address;
                document.getElementById(mode + 'ShippingStreet1').value = data.address;
            }

            // Show badge
            const indicator = document.getElementById('leadSelectedIndicator');
            if (indicator) {
                document.getElementById('leadBadgeName').textContent = data.customer_name || ('#' + data.id);
                indicator.style.display = 'inline-flex';
            }
        });
}

function clearLeadSelection(mode) {
    document.getElementById(mode + 'LeadId').value = '';
    document.getElementById(mode + 'LeadSearchInput').value = '';
    document.getElementById(mode + 'RecentLeadsSelect').value = '';
    const indicator = document.getElementById('leadSelectedIndicator');
    if (indicator) indicator.style.display = 'none';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
    if (modalId === 'addCustomerModal') {
        resetAddCustomerSubmitBtn();
    } else if (modalId === 'editCustomerModal') {
        resetEditCustomerSubmitBtn();
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Add Customer Form Submit Listener (Loading State + Duplicate Prevention)
const addCustomerForm = document.getElementById('addCustomerForm');
if (addCustomerForm) {
    addCustomerForm.addEventListener('submit', function(e) {
        if (isAddCustomerSubmitting) {
            e.preventDefault();
            return false;
        }

        if (!this.checkValidity()) {
            return;
        }

        isAddCustomerSubmitting = true;
        const btn = document.getElementById('addCustomerSubmitBtn');
        if (btn) {
            btn.classList.add('disabled');
            btn.style.pointerEvents = 'none';
            btn.innerHTML = '<i class="bi bi-arrow-repeat acm-spin"></i> Creating Customer...';
            setTimeout(() => {
                btn.disabled = true;
            }, 20);
        }
    });
}

// Edit Customer Form Submit Listener (Loading State + Duplicate Prevention)
const editCustomerForm = document.getElementById('editCustomerForm');
if (editCustomerForm) {
    editCustomerForm.addEventListener('submit', function(e) {
        if (isEditCustomerSubmitting) {
            e.preventDefault();
            return false;
        }

        if (!this.checkValidity()) {
            return;
        }

        isEditCustomerSubmitting = true;
        const btn = document.getElementById('editCustomerSubmitBtn');
        if (btn) {
            btn.classList.add('disabled');
            btn.style.pointerEvents = 'none';
            btn.innerHTML = '<i class="bi bi-arrow-repeat acm-spin"></i> Updating Customer...';
            setTimeout(() => {
                btn.disabled = true;
            }, 20);
        }
    });
}

// Reset buttons on browser back / pageshow (bfcache)
window.addEventListener('pageshow', function() {
    resetAddCustomerSubmitBtn();
    resetEditCustomerSubmitBtn();
});

// Close modals on escape key or clicking backdrop
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
            if (modal.id === 'addCustomerModal') resetAddCustomerSubmitBtn();
            if (modal.id === 'editCustomerModal') resetEditCustomerSubmitBtn();
        });
    }
});

document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
            if (this.id === 'addCustomerModal') resetAddCustomerSubmitBtn();
            if (this.id === 'editCustomerModal') resetEditCustomerSubmitBtn();
        }
    });
});

// Close lead search dropdown on outside click
document.addEventListener('click', function(e) {
    const resBox = document.getElementById('addLeadSearchResults');
    const input = document.getElementById('addLeadSearchInput');
    if (resBox && !resBox.contains(e.target) && e.target !== input) {
        resBox.style.display = 'none';
    }
});
</script>
@endpush
@endsection
