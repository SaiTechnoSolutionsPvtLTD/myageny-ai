@extends('layouts.app')

@section('title', 'Support Subcategories Master - Accounts')

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
.acm-btn-primary { background:linear-gradient(135deg,#4f46e5,#6366f1); color:#fff; box-shadow:0 4px 14px rgba(79,70,229,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(79,70,229,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#4f46e5; color:#4f46e5; }
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.acm-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:nowrap; overflow-x:auto; }
.acm-search-wrap { position:relative; flex:1; min-width:220px; }
.acm-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.acm-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.acm-select { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.status-active { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-inactive { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

/* Hierarchy Chain Display */
.hierarchy-chain-wrap { display:flex; align-items:center; flex-wrap:wrap; gap:6px; max-width:440px; }
.h-level-tag { display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:3px 8px; font-size:11px; }
.h-badge { font-size:10px; font-weight:800; background:#4f46e5; color:#fff; border-radius:4px; padding:1px 5px; letter-spacing:.3px; }
.h-name { font-weight:700; color:#334155; }
.h-users-list { display:inline-flex; align-items:center; flex-wrap:wrap; gap:4px; }
.h-user-chip { display:inline-flex; align-items:center; gap:3px; font-size:11px; font-weight:600; background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; border-radius:4px; padding:1px 6px; }
.h-arrow { font-size:16px; color:#94a3b8; font-weight:bold; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:94%; max-width:720px; max-height:92vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:16px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; }
.form-input-sm { padding:6px 10px; font-size:12px; }
.form-input:focus { border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.1); }

/* Hierarchy Builder Styles */
.hierarchy-builder-section { background:#faf5ff; border:1px solid #e9d5ff; border-radius:14px; padding:18px; margin-top:8px; }
.hb-title { font-size:13px; font-weight:800; color:#581c87; display:flex; align-items:center; gap:8px; margin-bottom:4px; }
.hb-desc { font-size:12px; color:#7e22ce; margin-bottom:14px; line-height:1.4; }
.hierarchy-levels-container { display:flex; flex-direction:column; gap:12px; }
.hierarchy-level-box { background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:14px; box-shadow:0 2px 6px rgba(0,0,0,.02); position:relative; }
.level-box-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; padding-bottom:8px; border-bottom:1px dashed #e2e8f0; flex-wrap:wrap; gap:8px; }
.level-num-pill { display:inline-flex; align-items:center; padding:3px 10px; background:#4f46e5; color:#fff; border-radius:999px; font-size:11px; font-weight:800; }
.btn-remove-level { background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; border-radius:6px; padding:4px 8px; font-size:11px; font-weight:700; cursor:pointer; transition:all .15s; }
.btn-remove-level:hover { background:#fca5a5; }
.level-box-body { display:flex; flex-direction:column; gap:10px; }
</style>
@endpush

@section('content')
<div class="acm-page">

    {{-- Standard Uniform Topbar --}}
    <div class="acm-topbar">
        <div class="acm-topbar-left">
            <div class="acm-title">
                <i class="bi bi-diagram-3-fill" style="color:#fe5f04;"></i>
                <span>Support Subcategories</span>
            </div>
            <div class="acm-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.master.index') }}">Master Hub</a>
                <span class="sep">/</span>
                <span class="current">Support Subcategories</span>
            </div>
        </div>
        <div class="acm-topbar-actions">
            <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Master Hub
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="openAddSubcategoryModal()">
                <i class="bi bi-plus-lg"></i> Add Subcategory
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

    {{-- Subcategory Stats Cards --}}
    <div class="acm-stats-grid">
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#eef2ff; color:#4f46e5;">
                <i class="bi bi-diagram-3-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Total Subcategories</div>
                <div class="acm-stat-val">{{ $subcategoryStats['total'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Active Subcategories</div>
                <div class="acm-stat-val">{{ $subcategoryStats['active'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fee2e2; color:#dc2626;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Inactive Subcategories</div>
                <div class="acm-stat-val">{{ $subcategoryStats['inactive'] }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="acm-card">
        <form method="GET" action="{{ route('accounts.master.support-subcategories.index') }}">
            <div class="acm-filter-bar">
                <div class="acm-search-wrap">
                    <i class="bi bi-search acm-search-ico"></i>
                    <input type="text" name="search" class="acm-search-input" placeholder="Search subcategory, code, parent category…" value="{{ request('search') }}">
                </div>
                <select name="category_id" class="acm-select" onchange="this.form.submit()">
                    <option value="">All Support Categories</option>
                    @foreach($activeCategoryOptions as $cat)
                        <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }} {{ $cat->code ? "({$cat->code})" : '' }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="acm-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px;">Filter</button>
                @if(request()->hasAny(['search', 'category_id', 'status']))
                    <a href="{{ route('accounts.master.support-subcategories.index') }}" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1;">Reset</a>
                @endif
            </div>
        </form>

        @if($subcategories->isEmpty())
            <div style="text-align:center; padding:55px 20px; color:#94a3b8;">
                <i class="bi bi-diagram-3" style="font-size:44px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Support Subcategories Found</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:18px;">Click the button below to add your first support subcategory with multi-tier user hierarchy.</div>
                <button type="button" class="acm-btn acm-btn-primary" onclick="openAddSubcategoryModal()">
                    <i class="bi bi-plus-lg"></i> Add Support Subcategory
                </button>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subcategory Name</th>
                            <th>Parent Category</th>
                            <th>Code</th>
                            <th>User Hierarchy Chain</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subcategories as $subcategory)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">{{ $subcategories->firstItem() + $loop->index }}</td>
                            <td>
                                <div style="font-weight:800; color:#0f172a; font-size:14px;">{{ $subcategory->name }}</div>
                            </td>
                            <td>
                                @if($subcategory->category)
                                    <span class="acm-badge" style="background:#f0fdfa; color:#0d9488; border:1px solid #ccfbf1; font-weight:700;">
                                        <i class="bi bi-headset"></i> {{ $subcategory->category->name }}
                                    </span>
                                @else
                                    <span style="color:#94a3b8;">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @if($subcategory->code)
                                    <code style="background:#f1f5f9; padding:2px 8px; border-radius:6px; font-size:12px; color:#4338ca; font-weight:700;">{{ $subcategory->code }}</code>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $hierarchyList = $subcategory->formatted_hierarchy;
                                @endphp
                                @if(!empty($hierarchyList))
                                    <div class="hierarchy-chain-wrap">
                                        @foreach($hierarchyList as $lvl)
                                            <div class="h-level-tag">
                                                <span class="h-badge">L{{ $lvl['level'] }}</span>
                                                <span class="h-name">{{ $lvl['level_name'] }}:</span>
                                                <div class="h-users-list">
                                                    @forelse($lvl['users'] as $u)
                                                        <span class="h-user-chip" title="{{ $u['name'] }} ({{ $u['email'] }})">
                                                            <i class="bi bi-person"></i> {{ $u['name'] }}
                                                        </span>
                                                    @empty
                                                        <span style="color:#94a3b8; font-size:11px;">No users</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                            @if(!$loop->last)
                                                <i class="bi bi-arrow-right-short h-arrow"></i>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color:#94a3b8; font-size:12px; font-style:italic;">No hierarchy assigned</span>
                                @endif
                            </td>
                            <td style="color:#64748b; max-width:220px;">
                                {{ $subcategory->description ?: '—' }}
                            </td>
                            <td>
                                <span class="acm-badge status-{{ $subcategory->status }}">{{ ucfirst($subcategory->status) }}</span>
                            </td>
                            <td>
                                <div style="font-size:12px; color:#475569; font-weight:600;">{{ $subcategory->creator?->name ?? 'Admin' }}</div>
                                <div style="font-size:11px; color:#94a3b8;">{{ $subcategory->created_at->format('d M, Y') }}</div>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 10px; font-size:11px;"
                                            onclick='openEditSubcategoryModal(@json($subcategory))'
                                            title="Edit Subcategory">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form method="POST" action="{{ route('accounts.master.support-subcategories.toggle-status', $subcategory->id) }}" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Toggle Active/Inactive">
                                            {{ $subcategory->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('accounts.master.support-subcategories.destroy', $subcategory->id) }}" onsubmit="return confirm('Are you sure you want to delete this support subcategory?')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Subcategory">
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

            @if($subcategories->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #e2e8f0;">
                    {{ $subcategories->links() }}
                </div>
            @endif
        @endif
    </div>
    </div>
</div>

{{-- MODAL 1: Add Support Subcategory --}}
<div class="modal-overlay" id="addSubcategoryModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-diagram-3-fill" style="color:#4f46e5; margin-right:6px;"></i> Add Support Subcategory
            </div>
            <button type="button" class="modal-close" onclick="closeModal('addSubcategoryModal')">&times;</button>
        </div>
        <form id="addSubcategoryForm" method="POST" action="{{ route('accounts.master.support-subcategories.store') }}">
            @csrf
            <input type="hidden" id="add_hierarchy_json" name="hierarchy_json">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Parent Support Category <span style="color:#dc2626;">*</span></label>
                    <select name="account_support_category_id" class="form-input" required>
                        <option value="">Select Support Category...</option>
                        @foreach($activeCategoryOptions as $cat)
                            <option value="{{ $cat->id }}" {{ (string) request('category_id') === (string) $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }} {{ $cat->code ? "({$cat->code})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Subcategory Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Bug Reporting, Password Reset, Hardware Replacement" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subcategory Code</label>
                    <input type="text" name="code" class="form-input" placeholder="e.g. SUP-BUG, SUP-PASS">
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Scope</label>
                    <textarea name="description" class="form-input" rows="2" placeholder="Brief note about this support subcategory..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status <span style="color:#dc2626;">*</span></label>
                    <select name="status" class="form-input" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                {{-- User-Wise Multiple Hierarchy Builder --}}
                <div class="hierarchy-builder-section">
                    <div class="hb-title">
                        <i class="bi bi-people-fill"></i> User-Wise Multiple Hierarchy Allocation
                    </div>
                    <div class="hb-desc">
                        Configure multi-level escalation & handling hierarchy. For each level, assign one or more responsible users who will handle tickets sequentially.
                    </div>

                    <div class="hierarchy-levels-container" id="add_hierarchy_container">
                        {{-- Injected dynamically --}}
                    </div>

                    <div style="margin-top:12px;">
                        <button type="button" class="acm-btn acm-btn-secondary" style="font-size:12px; padding:6px 12px;" onclick="addHierarchyLevel('add')">
                            <i class="bi bi-plus-circle"></i> Add Next Hierarchy Level
                        </button>
                    </div>
                </div>

            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('addSubcategoryModal')">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Save Support Subcategory
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: Edit Support Subcategory --}}
<div class="modal-overlay" id="editSubcategoryModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#4f46e5; margin-right:6px;"></i> Edit Support Subcategory
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editSubcategoryModal')">&times;</button>
        </div>
        <form id="editSubcategoryForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit_hierarchy_json" name="hierarchy_json">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Parent Support Category <span style="color:#dc2626;">*</span></label>
                    <select id="edit_subcat_category_id" name="account_support_category_id" class="form-input" required>
                        <option value="">Select Support Category...</option>
                        @foreach($activeCategoryOptions as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }} {{ $cat->code ? "({$cat->code})" : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Subcategory Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="edit_subcat_name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subcategory Code</label>
                    <input type="text" id="edit_subcat_code" name="code" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Scope</label>
                    <textarea id="edit_subcat_description" name="description" class="form-input" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status <span style="color:#dc2626;">*</span></label>
                    <select id="edit_subcat_status" name="status" class="form-input" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                {{-- User-Wise Multiple Hierarchy Builder --}}
                <div class="hierarchy-builder-section">
                    <div class="hb-title">
                        <i class="bi bi-people-fill"></i> User-Wise Multiple Hierarchy Allocation
                    </div>
                    <div class="hb-desc">
                        Configure multi-level escalation & handling hierarchy. For each level, assign one or more responsible users who will handle tickets sequentially.
                    </div>

                    <div class="hierarchy-levels-container" id="edit_hierarchy_container">
                        {{-- Injected dynamically --}}
                    </div>

                    <div style="margin-top:12px;">
                        <button type="button" class="acm-btn acm-btn-secondary" style="font-size:12px; padding:6px 12px;" onclick="addHierarchyLevel('edit')">
                            <i class="bi bi-plus-circle"></i> Add Next Hierarchy Level
                        </button>
                    </div>
                </div>

            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('editSubcategoryModal')">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-save"></i> Update Subcategory
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const COMPANY_USERS = @json($companyUsers);
let addCounter = 0;
let editCounter = 0;

function renderLevelHtml(prefix, levelIndex, levelNum, data = {}) {
    const levelName = data.level_name || ('Level ' + levelNum);
    const notes = data.notes || '';
    const selectedUserIds = (data.user_ids || []).map(function(id) { return String(id); });

    let optionsHtml = '';
    COMPANY_USERS.forEach(function(u) {
        const isSelected = selectedUserIds.includes(String(u.id)) ? 'selected' : '';
        const desig = u.designation ? ` (${u.designation})` : '';
        optionsHtml += `<option value="${u.id}" ${isSelected}>${u.name}${desig} - ${u.email}</option>`;
    });

    return `
    <div class="hierarchy-level-box" id="${prefix}_level_box_${levelIndex}" data-idx="${levelIndex}">
        <div class="level-box-header">
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="level-num-pill">Level ${levelNum}</span>
                <input type="text" name="hierarchy[${levelIndex}][level_name]" class="form-input form-input-sm level-name-input" 
                       value="${levelName}" placeholder="e.g. Level ${levelNum} - Frontline Support" style="font-weight:700; width:260px;" required>
            </div>
            <button type="button" class="btn-remove-level" onclick="removeHierarchyLevel('${prefix}', ${levelIndex})" title="Remove this Level">
                <i class="bi bi-trash"></i> Remove Level
            </button>
        </div>
        <div class="level-box-body">
            <div class="form-group" style="margin-bottom:8px;">
                <label class="form-label" style="font-size:11px; margin-bottom:4px;">
                    Assigned User(s) for this Level <span style="color:#dc2626;">*</span>
                </label>
                <select name="hierarchy[${levelIndex}][user_ids][]" class="form-input select2-users" multiple="multiple" style="width:100%;">
                    ${optionsHtml}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-size:11px; margin-bottom:4px;">
                    Escalation Notes / SLA Instruction (Optional)
                </label>
                <input type="text" name="hierarchy[${levelIndex}][notes]" class="form-input form-input-sm level-notes-input" 
                       value="${notes}" placeholder="e.g. Handle within 2 hours, escalate to next level if unresolved">
            </div>
        </div>
    </div>
    `;
}

function initModalSelect2($modal) {
    $modal.find('.select2-users').each(function() {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).select2({
                placeholder: 'Select one or more users for this level...',
                allowClear: true,
                dropdownParent: $modal
            });
        }
    });
}

function renumberHierarchy(prefix) {
    const $container = $(`#${prefix}_hierarchy_container`);
    const $boxes = $container.find('.hierarchy-level-box');
    $boxes.each(function(i, box) {
        const newLevelNum = i + 1;
        $(box).find('.level-num-pill').text('Level ' + newLevelNum);
    });
}

function addHierarchyLevel(prefix, data = {}) {
    const $container = $(`#${prefix}_hierarchy_container`);
    const currentCount = $container.find('.hierarchy-level-box').length;
    const levelNum = currentCount + 1;
    const idx = (prefix === 'add' ? ++addCounter : ++editCounter);

    const html = renderLevelHtml(prefix, idx, levelNum, data);
    $container.append(html);

    const $modal = $(`#${prefix}SubcategoryModal`);
    initModalSelect2($modal);
}

function removeHierarchyLevel(prefix, idx) {
    const $container = $(`#${prefix}_hierarchy_container`);
    const count = $container.find('.hierarchy-level-box').length;
    if (count <= 1) {
        alert('A subcategory must have at least one hierarchy level.');
        return;
    }
    $(`#${prefix}_level_box_${idx}`).remove();
    renumberHierarchy(prefix);
}

function openAddSubcategoryModal() {
    $('#add_hierarchy_container').empty();
    addCounter = 0;
    addHierarchyLevel('add', { level_name: 'Level 1 - Frontline Support' });
    $('#addSubcategoryModal').addClass('active');
}

function openEditSubcategoryModal(item) {
    var url = '{{ route('accounts.master.support-subcategories.update', ':id') }}'.replace(':id', item.id);
    $('#editSubcategoryForm').attr('action', url);
    $('#edit_subcat_category_id').val(item.account_support_category_id || '');
    $('#edit_subcat_name').val(item.name || '');
    $('#edit_subcat_code').val(item.code || '');
    $('#edit_subcat_description').val(item.description || '');
    $('#edit_subcat_status').val(item.status || 'active');

    // Populate hierarchy levels
    $('#edit_hierarchy_container').empty();
    editCounter = 0;
    var levels = item.hierarchy;
    if (Array.isArray(levels) && levels.length > 0) {
        levels.forEach(function(lvl, i) {
            addHierarchyLevel('edit', {
                level_name: lvl.level_name || ('Level ' + (i + 1)),
                user_ids: lvl.user_ids || (lvl.user_id ? [lvl.user_id] : []),
                notes: lvl.notes || ''
            });
        });
    } else {
        addHierarchyLevel('edit', { level_name: 'Level 1 - Frontline Support' });
    }

    $('#editSubcategoryModal').addClass('active');
}

function closeModal(modalId) {
    $('#' + modalId).removeClass('active');
}

// Form submit serialization
$('#addSubcategoryForm, #editSubcategoryForm').on('submit', function() {
    const isAdd = $(this).attr('id') === 'addSubcategoryForm';
    const prefix = isAdd ? 'add' : 'edit';
    const hierarchyData = [];

    $(`#${prefix}_hierarchy_container .hierarchy-level-box`).each(function(i, box) {
        const levelNum = i + 1;
        const levelName = $(box).find('.level-name-input').val();
        const userIds = $(box).find('.select2-users').val() || [];
        const notes = $(box).find('.level-notes-input').val();

        hierarchyData.push({
            level: levelNum,
            level_name: levelName,
            user_ids: userIds.map(Number),
            notes: notes
        });
    });

    $(`#${prefix}_hierarchy_json`).val(JSON.stringify(hierarchyData));
});

$(document).on('click', '.modal-overlay', function(e) {
    if (e.target === this) {
        $(this).removeClass('active');
    }
});
</script>
@endpush
@endsection
