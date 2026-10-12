@extends('layouts.app')

@section('title', 'Vendor Master - Accounts')

@push('styles')
<style>
.acm-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.acm-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); flex-wrap:wrap; gap:12px; }
.acm-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; display:flex; align-items:center; gap:10px; }
.acm-sub { font-size:13px; color:#64748b; }

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
.acm-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.acm-select { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
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
</style>
@endpush

@section('content')
<div class="acm-page">

    {{-- Topbar --}}
    <div class="acm-topbar">
        <div>
            <div style="margin-bottom:6px;">
                <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary" style="padding:4px 10px; font-size:12px;">
                    <i class="bi bi-arrow-left"></i> Back to Master Hub
                </a>
            </div>
            <div class="acm-title">
                <i class="bi bi-shop" style="color:#059669;"></i>
                <span>Vendor Master</span>
            </div>
            <div class="acm-sub">Register and manage supplier & vendor details, GST credentials, and bank settlement accounts</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="acm-btn acm-btn-primary" onclick="openAddVendorModal()">
                <i class="bi bi-plus-lg"></i> Add New Vendor
            </button>
        </div>
    </div>

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
            <div class="acm-stat-icon" style="background:#ecfdf5; color:#059669;">
                <i class="bi bi-shop"></i>
            </div>
            <div>
                <div class="acm-stat-label">Total Vendors</div>
                <div class="acm-stat-val">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Active Vendors</div>
                <div class="acm-stat-val">{{ $stats['active'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fee2e2; color:#dc2626;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Inactive Vendors</div>
                <div class="acm-stat-val">{{ $stats['inactive'] }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="acm-card">
        <form method="GET" action="{{ route('accounts.master.vendors.index') }}">
            <div class="acm-filter-bar">
                <div class="acm-search-wrap">
                    <i class="bi bi-search acm-search-ico"></i>
                    <input type="text" name="search" class="acm-search-input" placeholder="Search vendor name, company, mobile, GST, bank…" value="{{ request('search') }}">
                </div>
                <select name="status" class="acm-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px;">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('accounts.master.vendors.index') }}" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1;">Reset</a>
                @endif
            </div>
        </form>

        @if($vendors->isEmpty())
            <div style="text-align:center; padding:55px 20px; color:#94a3b8;">
                <i class="bi bi-shop" style="font-size:44px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Vendors Registered</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:18px;">Click the button below to register your first vendor profile.</div>
                <button type="button" class="acm-btn acm-btn-primary" onclick="openAddVendorModal()">
                    <i class="bi bi-plus-lg"></i> Add New Vendor
                </button>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vendor & Company</th>
                            <th>Contact Info</th>
                            <th>GST & Address</th>
                            <th>Bank Credentials</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendors as $vendor)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">{{ $vendors->firstItem() + $loop->index }}</td>
                            <td>
                                <div style="font-weight:800; color:#0f172a; font-size:14px;">{{ $vendor->vendor_name }}</div>
                                @if($vendor->company_name)
                                    <div style="font-size:12px; color:#64748b; font-weight:600; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                        <i class="bi bi-building"></i> {{ $vendor->company_name }}
                                    </div>
                                @else
                                    <span style="font-size:11px; color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($vendor->mobile_number)
                                    <div style="font-size:12px; color:#0f172a; font-weight:600; display:flex; align-items:center; gap:4px;">
                                        <i class="bi bi-telephone text-muted"></i> {{ $vendor->mobile_number }}
                                    </div>
                                @endif
                                @if($vendor->email)
                                    <div style="font-size:12px; color:#64748b; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                        <i class="bi bi-envelope text-muted"></i> {{ $vendor->email }}
                                    </div>
                                @endif
                                @if(!$vendor->mobile_number && !$vendor->email)
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($vendor->gst_number)
                                    <div>
                                        <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:11px; color:#0f172a; font-weight:700;">GST: {{ $vendor->gst_number }}</code>
                                    </div>
                                @endif
                                @if($vendor->address)
                                    <div style="font-size:12px; color:#64748b; max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;" title="{{ $vendor->address }}">
                                        <i class="bi bi-geo-alt text-muted"></i> {{ $vendor->address }}
                                    </div>
                                @elseif(!$vendor->gst_number)
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @if($vendor->bank_name || $vendor->account_number)
                                    <div style="font-size:12px; font-weight:700; color:#0f172a;">
                                        {{ $vendor->bank_name ?? 'Bank A/C' }}
                                    </div>
                                    <div style="font-size:11px; color:#64748b; font-family:monospace; margin-top:1px;">
                                        @if($vendor->account_number)
                                            <span>A/C: {{ $vendor->account_number }}</span>
                                        @endif
                                        @if($vendor->ifsc_code)
                                            <span style="margin-left:4px; color:#059669; font-weight:700;">({{ $vendor->ifsc_code }})</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="acm-badge status-{{ $vendor->status }}">{{ ucfirst($vendor->status) }}</span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    {{-- View Details --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick='openViewVendorModal(@json($vendor))'
                                            title="View Full Vendor Details">
                                        <i class="bi bi-eye"></i> View
                                    </button>

                                    {{-- Edit --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick='openEditVendorModal(@json($vendor))'
                                            title="Edit Vendor">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>

                                    {{-- Toggle Status --}}
                                    <form method="POST" action="{{ route('accounts.master.vendors.toggle-status', $vendor->id) }}" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Toggle Active/Inactive">
                                            {{ $vendor->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('accounts.master.vendors.destroy', $vendor->id) }}" onsubmit="return confirm('Are you sure you want to delete vendor: {{ addslashes($vendor->vendor_name) }}?')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Vendor">
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

            @if($vendors->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                    {{ $vendors->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- Modal 1: Add Vendor --}}
<div id="addVendorModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-shop" style="color:#059669;"></i>
                <span>Register New Vendor</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('addVendorModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.master.vendors.store') }}">
            @csrf
            <div class="modal-body">

                {{-- Group 1: General & Contact --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-person-badge"></i> Basic & Contact Information
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Vendor Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="vendor_name" class="form-input" placeholder="e.g. Ramesh Kumar / Office Supplies Co." required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-input" placeholder="e.g. Apex Tech Solutions Pvt Ltd">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile_number" class="form-input" placeholder="e.g. +91 98765 43210">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" placeholder="e.g. billing@apextech.com">
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
                            <input type="text" name="gst_number" class="form-input" placeholder="e.g. 33AAAAA0000A1Z5" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-input" rows="2" placeholder="Street address, city, state, pin code..."></textarea>
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
                            <input type="text" name="bank_name" class="form-input" placeholder="e.g. HDFC Bank, ICICI Bank, SBI">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Name (Beneficiary)</label>
                            <input type="text" name="account_name" class="form-input" placeholder="e.g. Apex Tech Solutions Pvt Ltd">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" class="form-input" placeholder="e.g. 50100234567890">
                        </div>
                        <div class="form-group">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="ifsc_code" class="form-input" placeholder="e.g. HDFC0001234" style="text-transform:uppercase;">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Branch Name</label>
                            <input type="text" name="branch_name" class="form-input" placeholder="e.g. Anna Nagar, Chennai">
                        </div>
                    </div>
                </div>

            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('addVendorModal')">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Register Vendor
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Edit Vendor --}}
<div id="editVendorModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#fe5f04;"></i>
                <span>Edit Vendor Profile</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editVendorModal')">&times;</button>
        </div>
        <form id="editVendorForm" method="POST">
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
                            <label class="form-label">Vendor Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="edit_vendor_name" name="vendor_name" class="form-input" required>
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
                            <input type="text" id="edit_account_number" name="account_number" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" id="edit_ifsc_code" name="ifsc_code" class="form-input" style="text-transform:uppercase;">
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
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('editVendorModal')">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 3: View Vendor Details --}}
<div id="viewVendorModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-info-circle-fill" style="color:#2563eb;"></i>
                <span>Vendor Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('viewVendorModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:12px; border-bottom:1px solid #f1f5f9;">
                <div>
                    <div id="view_vendor_name_header" style="font-size:18px; font-weight:800; color:#0f172a;"></div>
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
                        <div id="view_acc_no" class="detail-val" style="font-family:monospace; font-size:14px;">—</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">IFSC Code</div>
                        <div id="view_ifsc" class="detail-val" style="font-family:monospace; color:#059669;">—</div>
                    </div>
                    <div class="detail-item" style="grid-column:1 / -1;">
                        <div class="detail-label">Branch Name</div>
                        <div id="view_branch" class="detail-val">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('viewVendorModal')">Close</button>
            <button type="button" id="viewModalEditBtn" class="acm-btn acm-btn-primary">
                <i class="bi bi-pencil-square"></i> Edit Vendor
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var currentViewingVendor = null;

function openAddVendorModal() {
    $('#addVendorModal').addClass('active');
}

function openEditVendorModal(item) {
    var url = '{{ route('accounts.master.vendors.update', ':id') }}'.replace(':id', item.id);
    $('#editVendorForm').attr('action', url);
    $('#edit_vendor_name').val(item.vendor_name || '');
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

    closeModal('viewVendorModal');
    $('#editVendorModal').addClass('active');
}

function openViewVendorModal(item) {
    currentViewingVendor = item;
    $('#view_vendor_name_header').text(item.vendor_name || '—');
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

    $('#viewModalEditBtn').off('click').on('click', function() {
        openEditVendorModal(currentViewingVendor);
    });

    $('#viewVendorModal').addClass('active');
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
@endsection
