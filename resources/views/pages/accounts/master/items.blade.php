@extends('layouts.app')

@section('title', 'Items Master - Accounts')

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

.code-chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; background:#f1f5f9; color:#0f172a; font-family:monospace; font-size:12px; font-weight:700; border:1px solid #e2e8f0; }
.gst-chip { display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:800; border:1px solid #bfdbfe; }

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
                <i class="bi bi-box-seam" style="color:#0284c7;"></i>
                <span>Items Master</span>
            </div>
            <div class="acm-sub">Manage service catalog and billing items, HSN/SAC codes, pricing rates, GST slabs, and remarks</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="acm-btn acm-btn-primary" onclick="openAddItemModal()">
                <i class="bi bi-plus-lg"></i> Add New Item
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
            <div class="acm-stat-icon" style="background:#f0f9ff; color:#0284c7;">
                <i class="bi bi-box-seam"></i>
            </div>
            <div>
                <div class="acm-stat-label">Total Items</div>
                <div class="acm-stat-val">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Active Items</div>
                <div class="acm-stat-val">{{ $stats['active'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fee2e2; color:#dc2626;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Inactive Items</div>
                <div class="acm-stat-val">{{ $stats['inactive'] }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="acm-card">
        <form method="GET" action="{{ route('accounts.master.items.index') }}">
            <div class="acm-filter-bar">
                <div class="acm-search-wrap">
                    <i class="bi bi-search acm-search-ico"></i>
                    <input type="text" name="search" class="acm-search-input" placeholder="Search item name, description, HSN/SAC code, remarks…" value="{{ request('search') }}">
                </div>
                <select name="status" class="acm-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px;">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('accounts.master.items.index') }}" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1;">Reset</a>
                @endif
            </div>
        </form>

        @if($items->isEmpty())
            <div style="text-align:center; padding:55px 20px; color:#94a3b8;">
                <i class="bi bi-box-seam" style="font-size:44px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Items Registered</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:18px;">Click the button below to add your first item to the catalog.</div>
                <button type="button" class="acm-btn acm-btn-primary" onclick="openAddItemModal()">
                    <i class="bi bi-plus-lg"></i> Add New Item
                </button>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name & Description</th>
                            <th>HSN/SAC Code</th>
                            <th style="text-align:right;">Rate (₹)</th>
                            <th>GST (%)</th>
                            <th>Remarks</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">{{ $items->firstItem() + $loop->index }}</td>
                            <td>
                                <div style="font-weight:800; color:#0f172a; font-size:14px;">{{ $item->item_name }}</div>
                                @if($item->description)
                                    <div style="font-size:12px; color:#64748b; margin-top:2px; max-width:320px; line-height:1.4;">
                                        {{ Str::limit($item->description, 80) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($item->hsn_sac_code)
                                    <span class="code-chip">{{ $item->hsn_sac_code }}</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:800; color:#0f172a; font-size:14px; white-space:nowrap;">
                                ₹{{ number_format((float) $item->rate, 2) }}
                            </td>
                            <td>
                                <span class="gst-chip">{{ number_format((float) $item->gst, 2) }}%</span>
                            </td>
                            <td>
                                @if($item->remarks)
                                    <span style="font-size:12px; color:#475569;" title="{{ $item->remarks }}">
                                        {{ Str::limit($item->remarks, 45) }}
                                    </span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="acm-badge status-{{ $item->status }}">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    {{-- View Details --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick='openViewItemModal(@json($item))'
                                            title="View Full Item Details">
                                        <i class="bi bi-eye"></i> View
                                    </button>

                                    {{-- Edit --}}
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;"
                                            onclick='openEditItemModal(@json($item))'
                                            title="Edit Item">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>

                                    {{-- Toggle Status --}}
                                    <form method="POST" action="{{ route('accounts.master.items.toggle-status', $item->id) }}" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Toggle Active/Inactive">
                                            {{ $item->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('accounts.master.items.destroy', $item->id) }}" onsubmit="return confirm('Are you sure you want to delete item: {{ addslashes($item->item_name) }}?')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Item">
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

            @if($items->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                    {{ $items->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- Modal 1: Add Item --}}
<div id="addItemModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-box-seam" style="color:#0284c7;"></i>
                <span>Add New Item</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('addItemModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.master.items.store') }}">
            @csrf
            <div class="modal-body">

                {{-- Group 1: Item Details --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-tag"></i> Item Specifications
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Item Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="item_name" class="form-input" placeholder="e.g. Website Maintenance Retainer / Domain Hosting" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">HSN / SAC Code</label>
                            <input type="text" name="hsn_sac_code" class="form-input" placeholder="e.g. 998314 or 4802">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rate (₹)</label>
                            <input type="number" step="0.01" min="0" name="rate" class="form-input" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GST Rate (%)</label>
                            <select name="gst" class="form-input">
                                <option value="0">0% (Exempted / Nil)</option>
                                <option value="5">5%</option>
                                <option value="12">12%</option>
                                <option value="18" selected>18% (Standard Services)</option>
                                <option value="28">28%</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Group 2: Description & Remarks --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-card-text"></i> Description & Notes
                    </div>
                    <div style="display:flex; flex-direction:column; gap:12px; margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input" rows="3" placeholder="Provide a detailed description of this product or service…"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-input" rows="2" placeholder="Internal notes, pricing rules, or remarks…"></textarea>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                    <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('addItemModal')">Cancel</button>
                    <button type="submit" class="acm-btn acm-btn-primary">
                        <i class="bi bi-check-lg"></i> Save Item
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Edit Item --}}
<div id="editItemModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#fe5f04;"></i>
                <span>Edit Item</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editItemModal')">&times;</button>
        </div>
        <form method="POST" id="editItemForm" action="">
            @csrf
            @method('PUT')
            <div class="modal-body">

                {{-- Group 1: Item Details --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-tag"></i> Item Specifications
                    </div>
                    <div class="form-grid-2" style="margin-top:10px;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Item Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="item_name" id="editItemName" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">HSN / SAC Code</label>
                            <input type="text" name="hsn_sac_code" id="editHsnSacCode" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rate (₹)</label>
                            <input type="number" step="0.01" min="0" name="rate" id="editRate" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GST Rate (%)</label>
                            <select name="gst" id="editGst" class="form-input">
                                <option value="0">0% (Exempted / Nil)</option>
                                <option value="5">5%</option>
                                <option value="12">12%</option>
                                <option value="18">18% (Standard Services)</option>
                                <option value="28">28%</option>
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

                {{-- Group 2: Description & Remarks --}}
                <div>
                    <div class="form-section-title">
                        <i class="bi bi-card-text"></i> Description & Notes
                    </div>
                    <div style="display:flex; flex-direction:column; gap:12px; margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editDescription" class="form-input" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" id="editRemarks" class="form-input" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                    <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('editItemModal')">Cancel</button>
                    <button type="submit" class="acm-btn acm-btn-primary">
                        <i class="bi bi-check-lg"></i> Update Item
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal 3: View Item --}}
<div id="viewItemModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-box-seam" style="color:#0284c7;"></i>
                <span id="viewItemTitle">Item Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('viewItemModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Item Name</div>
                    <div class="detail-val" id="vItemName" style="font-size:16px; color:#0f172a;"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">HSN / SAC Code</div>
                    <div class="detail-val" id="vHsnSacCode"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-val" id="vStatus"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Rate (₹)</div>
                    <div class="detail-val" id="vRate" style="color:#0f172a; font-size:15px;"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">GST Rate</div>
                    <div class="detail-val" id="vGst"></div>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Description</div>
                    <div class="detail-val" id="vDescription" style="font-weight:500; line-height:1.6; white-space:pre-wrap;"></div>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Remarks</div>
                    <div class="detail-val" id="vRemarks" style="font-weight:500; line-height:1.6; white-space:pre-wrap;"></div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top:8px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('viewItemModal')">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openAddItemModal() {
    document.getElementById('addItemModal').classList.add('active');
}

function openEditItemModal(item) {
    const form = document.getElementById('editItemForm');
    form.action = "{{ url('accounts/master/items') }}/" + item.id;

    document.getElementById('editItemName').value = item.item_name || '';
    document.getElementById('editHsnSacCode').value = item.hsn_sac_code || '';
    document.getElementById('editRate').value = item.rate !== null ? parseFloat(item.rate).toFixed(2) : '0.00';

    // GST select
    const gstVal = item.gst !== null ? parseInt(item.gst) : 18;
    const gstSelect = document.getElementById('editGst');
    if (gstSelect) {
        gstSelect.value = gstVal;
        // If not in standard options, append or select closest
        if (gstSelect.value != gstVal) {
            const opt = document.createElement('option');
            opt.value = item.gst;
            opt.textContent = item.gst + '% (Custom)';
            gstSelect.appendChild(opt);
            gstSelect.value = item.gst;
        }
    }

    document.getElementById('editStatus').value = item.status || 'active';
    document.getElementById('editDescription').value = item.description || '';
    document.getElementById('editRemarks').value = item.remarks || '';

    document.getElementById('editItemModal').classList.add('active');
}

function openViewItemModal(item) {
    document.getElementById('viewItemTitle').textContent = item.item_name || 'Item Details';
    document.getElementById('vItemName').textContent = item.item_name || '—';
    document.getElementById('vHsnSacCode').innerHTML = item.hsn_sac_code ? `<span class="code-chip">${escapeHtml(item.hsn_sac_code)}</span>` : '<span style="color:#94a3b8;">—</span>';

    const statusBadge = item.status === 'active'
        ? '<span class="acm-badge status-active">Active</span>'
        : '<span class="acm-badge status-inactive">Inactive</span>';
    document.getElementById('vStatus').innerHTML = statusBadge;

    const rateNum = item.rate !== null ? parseFloat(item.rate).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
    document.getElementById('vRate').textContent = '₹' + rateNum;

    const gstNum = item.gst !== null ? parseFloat(item.gst).toFixed(2) : '0.00';
    document.getElementById('vGst').innerHTML = `<span class="gst-chip">${gstNum}%</span>`;

    document.getElementById('vDescription').textContent = item.description || '—';
    document.getElementById('vRemarks').textContent = item.remarks || '—';

    document.getElementById('viewItemModal').classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Close modals on escape key or clicking backdrop
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => modal.classList.remove('active'));
    }
});

document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
</script>
@endpush
@endsection
