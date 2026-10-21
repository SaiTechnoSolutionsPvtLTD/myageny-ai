@extends('layouts.app')

@section('title', 'Supplier Master - Accounts')

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

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.acm-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:wrap; }
.acm-search-wrap { position:relative; flex:1; min-width:240px; }
.acm-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.acm-search-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.acm-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.acm-select { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:capitalize; }
.status-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-inactive { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:94%; max-width:680px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:18px; }

.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .form-grid-2 { grid-template-columns:1fr; } }
.form-section-title { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-bottom:4px; display:flex; align-items:center; gap:6px; border-bottom:1px dashed #e2e8f0; padding-bottom:6px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

/* Detail View Rows */
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media (max-width: 600px) { .detail-grid { grid-template-columns:1fr; } }
.detail-item { background:#f8fafc; padding:12px 14px; border-radius:10px; border:1px solid #e2e8f0; }
.detail-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px; }
.detail-val { font-size:13px; font-weight:700; color:#0f172a; word-break:break-word; }

.copy-btn { border:none; background:none; color:#64748b; cursor:pointer; padding:2px 4px; border-radius:4px; font-size:12px; }
.copy-btn:hover { color:#2563eb; background:#fff; }
</style>
@endpush

@section('content')
<div class="acm-page">

    {{-- Standard Uniform Topbar --}}
    <div class="acm-topbar">
        <div class="acm-topbar-left">
            <div class="acm-title">
                <i class="bi bi-truck" style="color:#2563eb;"></i>
                <span>Supplier Master</span>
            </div>
            <div class="acm-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.master.index') }}">Master Hub</a>
                <span class="sep">/</span>
                <span class="current">Suppliers</span>
            </div>
        </div>
        <div class="acm-topbar-actions">
            <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Master Hub
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="openAddSupplierModal()">
                <i class="bi bi-plus-lg"></i> Add New Supplier
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
                ⚠️ {{ session('error') }}
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
                <div class="acm-stat-icon" style="background:#eff6ff; color:#2563eb;">
                    <i class="bi bi-truck"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Total Suppliers</div>
                    <div class="acm-stat-val">{{ $stats['total'] }}</div>
                </div>
            </div>
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Active Suppliers</div>
                    <div class="acm-stat-val">{{ $stats['active'] }}</div>
                </div>
            </div>
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fee2e2; color:#dc2626;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Inactive Suppliers</div>
                    <div class="acm-stat-val">{{ $stats['inactive'] }}</div>
                </div>
            </div>
        </div>

        {{-- Main Table Card --}}
        <div class="acm-card">
            <form method="GET" action="{{ route('accounts.master.suppliers.index') }}">
                <div class="acm-filter-bar">
                    <div class="acm-search-wrap">
                        <i class="bi bi-search acm-search-ico"></i>
                        <input type="text" name="search" class="acm-search-input" placeholder="Search supplier name, company, mobile, GST, bank, address…" value="{{ request('search') }}">
                    </div>
                    <select name="status" class="acm-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <button type="submit" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px;">Filter</button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('accounts.master.suppliers.index') }}" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1;">Reset</a>
                    @endif
                </div>
            </form>

            @if($suppliers->isEmpty())
                <div style="text-align:center; padding:55px 20px; color:#94a3b8;">
                    <i class="bi bi-truck" style="font-size:44px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                    <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Suppliers Registered</div>
                    <div style="font-size:13px; color:#64748b; margin-bottom:18px;">Click the button below to register your first supplier profile.</div>
                    <button type="button" class="acm-btn acm-btn-primary" onclick="openAddSupplierModal()">
                        <i class="bi bi-plus-lg"></i> Add New Supplier
                    </button>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="acm-tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Supplier & Company</th>
                                <th>Contact Info</th>
                                <th>GST & Address</th>
                                <th>Bank Credentials</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($suppliers as $supplier)
                            <tr>
                                <td style="color:#94a3b8; font-weight:700;">{{ $suppliers->firstItem() + $loop->index }}</td>
                                <td>
                                    <div style="font-weight:800; color:#0f172a; font-size:14px;">{{ $supplier->supplier_name }}</div>
                                    @if($supplier->company_name)
                                        <div style="font-size:12px; color:#64748b; font-weight:600; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                            <i class="bi bi-building"></i> {{ $supplier->company_name }}
                                        </div>
                                    @else
                                        <span style="font-size:11px; color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($supplier->mobile_number)
                                        <div style="font-size:12px; color:#0f172a; font-weight:600; display:flex; align-items:center; gap:4px;">
                                            <i class="bi bi-telephone text-muted"></i> {{ $supplier->mobile_number }}
                                        </div>
                                    @endif
                                    @if($supplier->email)
                                        <div style="font-size:12px; color:#64748b; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                            <i class="bi bi-envelope text-muted"></i> {{ $supplier->email }}
                                        </div>
                                    @endif
                                    @if(!$supplier->mobile_number && !$supplier->email)
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($supplier->gst_number)
                                        <div>
                                            <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:11px; color:#0f172a; font-weight:700;">GST: {{ $supplier->gst_number }}</code>
                                        </div>
                                    @endif
                                    @if($supplier->address)
                                        <div style="font-size:12px; color:#64748b; max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;" title="{{ $supplier->address }}">
                                            <i class="bi bi-geo-alt text-muted"></i> {{ $supplier->address }}
                                        </div>
                                    @elseif(!$supplier->gst_number)
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($supplier->bank_name || $supplier->account_number)
                                        <div style="font-size:12px; font-weight:700; color:#0f172a;">
                                            {{ $supplier->bank_name ?? 'Bank A/C' }}
                                        </div>
                                        <div style="font-size:11px; color:#64748b; font-family:monospace; margin-top:1px;">
                                            @if($supplier->account_number)
                                                <span>A/C: {{ $supplier->account_number }}</span>
                                            @endif
                                            @if($supplier->ifsc_code)
                                                <span style="margin-left:4px; color:#2563eb; font-weight:700;">({{ $supplier->ifsc_code }})</span>
                                            @endif
                                        </div>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="acm-badge status-{{ $supplier->status }}">{{ ucfirst($supplier->status) }}</span>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; align-items:center; gap:6px;">
                                        {{-- View Details --}}
                                        <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                                onclick='openViewSupplierModal(@json($supplier))'
                                                title="View Full Supplier Details">
                                            <i class="bi bi-eye"></i> View
                                        </button>

                                        {{-- Edit --}}
                                        <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                                onclick='openEditSupplierModal(@json($supplier))'
                                                title="Edit Supplier">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>

                                        {{-- Toggle Status --}}
                                        <form method="POST" action="{{ route('accounts.master.suppliers.toggle-status', $supplier->id) }}" style="margin:0; display:inline-block;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Toggle Active/Inactive">
                                                {{ $supplier->status === 'active' ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>

                                        {{-- Delete --}}
                                        <form method="POST" action="{{ route('accounts.master.suppliers.destroy', $supplier->id) }}" onsubmit="return confirm('Are you sure you want to delete supplier: {{ addslashes($supplier->supplier_name) }}?')" style="margin:0; display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Supplier">
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

                @if($suppliers->hasPages())
                    <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                        {{ $suppliers->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

{{-- ======================================================== --}}
{{-- Modal 1: Add Supplier                                    --}}
{{-- ======================================================== --}}
<div id="addSupplierModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-truck" style="color:#2563eb;"></i>
                <span>Register New Supplier</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('addSupplierModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.master.suppliers.store') }}">
            @csrf
            <div class="modal-body">

                {{-- Group 1: General & Contact --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-person-badge"></i> Basic & Contact Information
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Supplier Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="supplier_name" class="form-input" placeholder="e.g. Ramesh Kumar / Global Tech Supplies" required value="{{ old('supplier_name') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-input" placeholder="e.g. Apex Hardware & IT Solutions Pvt Ltd" value="{{ old('company_name') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile_number" class="form-input" placeholder="e.g. +91 98765 43210" value="{{ old('mobile_number') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" placeholder="e.g. orders@apexhardware.com" value="{{ old('email') }}">
                        </div>
                    </div>
                </div>

                {{-- Group 2: Tax & Address --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-geo-alt"></i> Tax & Business Address
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" class="form-input" placeholder="e.g. 33AAAAA0000A1Z5" style="text-transform:uppercase;" value="{{ old('gst_number') }}">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-input" rows="2" placeholder="Street address, city, state, pin code...">{{ old('address') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Group 3: Bank Settlement Details --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-bank"></i> Bank Settlement Credentials
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-input" placeholder="e.g. HDFC Bank, ICICI Bank, SBI" value="{{ old('bank_name') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Name (Beneficiary)</label>
                            <input type="text" name="account_name" class="form-input" placeholder="e.g. Apex Hardware & IT Solutions" value="{{ old('account_name') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" class="form-input" placeholder="e.g. 50100234567890" style="font-family:monospace;" value="{{ old('account_number') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-input" placeholder="e.g. HDFC0001234" style="text-transform:uppercase; font-family:monospace;" value="{{ old('ifsc_code') }}">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Branch Name</label>
                            <input type="text" name="branch_name" class="form-input" placeholder="e.g. Anna Nagar, Chennai" value="{{ old('branch_name') }}">
                        </div>
                    </div>
                </div>

            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('addSupplierModal')">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Register Supplier
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- Modal 2: Edit Supplier                                   --}}
{{-- ======================================================== --}}
<div id="editSupplierModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#fe5f04;"></i>
                <span>Edit Supplier Profile</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editSupplierModal')">&times;</button>
        </div>
        <form id="editSupplierForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">

                {{-- Group 1: General & Contact --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-person-badge"></i> Basic & Contact Information
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Supplier Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="edit_supplier_name" name="supplier_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" id="edit_company_name" name="company_name" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" id="edit_mobile_number" name="mobile_number" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" id="edit_email" name="email" class="form-input">
                        </div>
                    </div>
                </div>

                {{-- Group 2: Tax & Address --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-geo-alt"></i> Tax & Business Address
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">GST Number</label>
                            <input type="text" id="edit_gst_number" name="gst_number" class="form-input" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Address</label>
                            <textarea id="edit_address" name="address" class="form-input" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Group 3: Bank Settlement Details --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-bank"></i> Bank Settlement Credentials
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Bank Name</label>
                            <input type="text" id="edit_bank_name" name="bank_name" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Name (Beneficiary)</label>
                            <input type="text" id="edit_account_name" name="account_name" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Number</label>
                            <input type="text" id="edit_account_number" name="account_number" class="form-input" style="font-family:monospace;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" id="edit_ifsc_code" name="ifsc_code" class="form-input" style="text-transform:uppercase; font-family:monospace;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch Name</label>
                            <input type="text" id="edit_branch_name" name="branch_name" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status <span style="color:#dc2626;">*</span></label>
                            <select id="edit_status" name="status" class="form-input" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('editSupplierModal')">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- Modal 3: View Supplier Details                           --}}
{{-- ======================================================== --}}
<div id="viewSupplierModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-info-circle-fill" style="color:#2563eb;"></i>
                <span>Supplier Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('viewSupplierModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:12px; border-bottom:1px solid #f1f5f9;">
                <div>
                    <div id="view_supplier_name_header" style="font-size:18px; font-weight:800; color:#0f172a;"></div>
                    <div id="view_company_name_header" style="font-size:13px; color:#64748b; font-weight:600; margin-top:2px;"></div>
                </div>
                <div id="view_status_badge"></div>
            </div>

            {{-- Contact & Tax --}}
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Mobile Number</div>
                    <div id="view_mobile" class="detail-val">—</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email Address</div>
                    <div id="view_email" class="detail-val">—</div>
                </div>
                <div class="detail-item" style="grid-column:1 / -1;">
                    <div class="detail-label">GST Number</div>
                    <div id="view_gst" class="detail-val">—</div>
                </div>
                <div class="detail-item" style="grid-column:1 / -1;">
                    <div class="detail-label">Business Address</div>
                    <div id="view_address" class="detail-val" style="white-space:pre-line;">—</div>
                </div>
            </div>

            {{-- Bank Details Box --}}
            <div>
                <div class="form-section-title" style="margin-bottom:10px;">
                    <i class="bi bi-bank"></i> Bank Account Information
                </div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Bank Name</div>
                        <div id="view_bank" class="detail-val">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Account Name</div>
                        <div id="view_acc_name" class="detail-val">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Account Number</div>
                        <div class="detail-val" style="font-family:monospace; font-size:14px; display:flex; align-items:center; gap:6px;">
                            <span id="view_acc_no">—</span>
                            <button type="button" class="copy-btn" id="view_copy_ac" title="Copy Account Number">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">IFSC Code</div>
                        <div class="detail-val" style="font-family:monospace; color:#2563eb; display:flex; align-items:center; gap:6px;">
                            <span id="view_ifsc">—</span>
                            <button type="button" class="copy-btn" id="view_copy_ifsc" title="Copy IFSC Code">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                    <div class="detail-item" style="grid-column:1 / -1;">
                        <div class="detail-label">Branch Name</div>
                        <div id="view_branch" class="detail-val">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('viewSupplierModal')">Close</button>
            <button type="button" id="viewModalEditBtn" class="acm-btn acm-btn-primary">
                <i class="bi bi-pencil-square"></i> Edit Supplier
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
var currentViewingSupplier = null;

function openAddSupplierModal() {
    $('#addSupplierModal').addClass('active');
}

function openEditSupplierModal(item) {
    var url = '{{ route('accounts.master.suppliers.update', ':id') }}'.replace(':id', item.id);
    $('#editSupplierForm').attr('action', url);
    $('#edit_supplier_name').val(item.supplier_name || '');
    $('#edit_company_name').val(item.company_name || '');
    $('#edit_mobile_number').val(item.mobile_number || '');
    $('#edit_email').val(item.email || '');
    $('#edit_address').val(item.address || '');
    $('#edit_gst_number').val(item.gst_number || '');
    $('#edit_bank_name').val(item.bank_name || '');
    $('#edit_account_name').val(item.account_name || '');
    $('#edit_account_number').val(item.account_number || '');
    $('#edit_ifsc_code').val(item.ifsc_code || '');
    $('#edit_branch_name').val(item.branch_name || '');
    $('#edit_status').val(item.status || 'active');

    closeModal('viewSupplierModal');
    $('#editSupplierModal').addClass('active');
}

function openViewSupplierModal(item) {
    currentViewingSupplier = item;
    $('#view_supplier_name_header').text(item.supplier_name || '—');
    $('#view_company_name_header').text(item.company_name ? item.company_name : '');
    var badgeClass = item.status === 'active' ? 'status-active' : 'status-inactive';
    var badgeText = item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : 'Active';
    $('#view_status_badge').html('<span class="acm-badge ' + badgeClass + '">' + badgeText + '</span>');

    $('#view_mobile').text(item.mobile_number || '—');
    $('#view_email').text(item.email || '—');
    $('#view_gst').text(item.gst_number || '—');
    $('#view_address').text(item.address || '—');

    $('#view_bank').text(item.bank_name || '—');
    $('#view_acc_name').text(item.account_name || '—');
    $('#view_acc_no').text(item.account_number || '—');
    $('#view_ifsc').text(item.ifsc_code || '—');
    $('#view_branch').text(item.branch_name || '—');

    // Bind copy triggers
    if (item.account_number) {
        $('#view_copy_ac').show().off('click').on('click', function() { copyText(item.account_number, this); });
    } else {
        $('#view_copy_ac').hide();
    }
    if (item.ifsc_code) {
        $('#view_copy_ifsc').show().off('click').on('click', function() { copyText(item.ifsc_code, this); });
    } else {
        $('#view_copy_ifsc').hide();
    }

    $('#viewModalEditBtn').off('click').on('click', function() {
        openEditSupplierModal(currentViewingSupplier);
    });

    $('#viewSupplierModal').addClass('active');
}

function copyText(text, btnElement) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        var original = $(btnElement).html();
        $(btnElement).html('<i class="bi bi-check2" style="color:#16a34a;"></i>');
        setTimeout(function() { $(btnElement).html(original); }, 1500);
    }).catch(function(err) {
        console.error('Copy failed', err);
    });
}

function closeModal(modalId) {
    $('#' + modalId).removeClass('active');
}

$(document).on('click', '.modal-overlay', function(e) {
    if (e.target === this) {
        $(this).removeClass('active');
    }
});

$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        $('.modal-overlay.active').removeClass('active');
    }
});
</script>
@endpush
