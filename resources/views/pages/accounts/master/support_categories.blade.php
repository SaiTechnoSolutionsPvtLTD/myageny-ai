@extends('layouts.app')

@section('title', 'Support Categories Master - Accounts')

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
.acm-btn-primary { background:linear-gradient(135deg,#0d9488,#14b8a6); color:#fff; box-shadow:0 4px 14px rgba(13,148,136,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(13,148,136,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#0d9488; color:#0d9488; }
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

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:92%; max-width:520px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; position:sticky; top:0; z-index:10; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:14px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#0d9488; box-shadow:0 0 0 3px rgba(13,148,136,.1); }
</style>
@endpush

@section('content')
<div class="acm-page">

    {{-- Standard Uniform Topbar --}}
    <div class="acm-topbar">
        <div class="acm-topbar-left">
            <div class="acm-title">
                <i class="bi bi-headset" style="color:#fe5f04;"></i>
                <span>Support Categories</span>
            </div>
            <div class="acm-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span class="sep">/</span>
                <a href="{{ route('accounts.master.index') }}">Master Hub</a>
                <span class="sep">/</span>
                <span class="current">Support Categories</span>
            </div>
        </div>
        <div class="acm-topbar-actions">
            <a href="{{ route('accounts.master.index') }}" class="acm-btn acm-btn-secondary">
                <i class="bi bi-arrow-left"></i> Master Hub
            </a>
            <button type="button" class="acm-btn acm-btn-primary" onclick="openAddCategoryModal()">
                <i class="bi bi-plus-lg"></i> Add Category
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

    {{-- Category Stats Cards --}}
    <div class="acm-stats-grid">
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdfa; color:#0d9488;">
                <i class="bi bi-headset"></i>
            </div>
            <div>
                <div class="acm-stat-label">Total Categories</div>
                <div class="acm-stat-val">{{ $categoryStats['total'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Active Categories</div>
                <div class="acm-stat-val">{{ $categoryStats['active'] }}</div>
            </div>
        </div>
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fee2e2; color:#dc2626;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <div class="acm-stat-label">Inactive Categories</div>
                <div class="acm-stat-val">{{ $categoryStats['inactive'] }}</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="acm-card">
        <form method="GET" action="{{ route('accounts.master.support-categories.index') }}">
            <div class="acm-filter-bar">
                <div class="acm-search-wrap">
                    <i class="bi bi-search acm-search-ico"></i>
                    <input type="text" name="search" class="acm-search-input" placeholder="Search category name, code, description…" value="{{ request('search') }}">
                </div>
                <select name="status" class="acm-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px;">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('accounts.master.support-categories.index') }}" class="acm-btn acm-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1;">Reset</a>
                @endif
            </div>
        </form>

        @if($categories->isEmpty())
            <div style="text-align:center; padding:55px 20px; color:#94a3b8;">
                <i class="bi bi-headset" style="font-size:44px; display:block; margin-bottom:10px; color:#cbd5e1;"></i>
                <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Support Categories Found</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:18px;">Click the button below to add your first support category.</div>
                <button type="button" class="acm-btn acm-btn-primary" onclick="openAddCategoryModal()">
                    <i class="bi bi-plus-lg"></i> Add Support Category
                </button>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Code</th>
                            <th>Subcategories</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">{{ $categories->firstItem() + $loop->index }}</td>
                            <td>
                                <div style="font-weight:800; color:#0f172a; font-size:14px;">{{ $category->name }}</div>
                            </td>
                            <td>
                                @if($category->code)
                                    <code style="background:#f1f5f9; padding:2px 8px; border-radius:6px; font-size:12px; color:#0f766e; font-weight:700;">{{ $category->code }}</code>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('accounts.master.support-subcategories.index', ['category_id' => $category->id]) }}"
                                   style="text-decoration:none;"
                                   title="Click to view subcategories under this category">
                                    <span class="acm-badge" style="background:#f0fdfa; color:#0d9488; border:1px solid #ccfbf1; font-weight:700;">
                                        <i class="bi bi-diagram-3-fill"></i> {{ $category->subcategories_count }} {{ Str::plural('Subcategory', $category->subcategories_count) }}
                                    </span>
                                </a>
                            </td>
                            <td style="color:#64748b; max-width:260px;">
                                {{ $category->description ?: '—' }}
                            </td>
                            <td>
                                <span class="acm-badge status-{{ $category->status }}">{{ ucfirst($category->status) }}</span>
                            </td>
                            <td>
                                <div style="font-size:12px; color:#475569; font-weight:600;">{{ $category->creator?->name ?? 'Admin' }}</div>
                                <div style="font-size:11px; color:#94a3b8;">{{ $category->created_at->format('d M, Y') }}</div>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <button type="button" class="acm-btn acm-btn-secondary" style="padding:4px 10px; font-size:11px;"
                                            onclick='openEditCategoryModal(@json($category))'
                                            title="Edit Category">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form method="POST" action="{{ route('accounts.master.support-categories.toggle-status', $category->id) }}" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Toggle Active/Inactive">
                                            {{ $category->status === 'active' ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('accounts.master.support-categories.destroy', $category->id) }}" onsubmit="return confirm('Are you sure you want to delete this support category? All associated subcategories will also be deleted.')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Category">
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

            @if($categories->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #e2e8f0;">
                    {{ $categories->links() }}
                </div>
            @endif
        @endif
    </div>
    </div>
</div>

{{-- MODAL 1: Add Support Category --}}
<div class="modal-overlay" id="addCategoryModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-folder-plus" style="color:#0d9488; margin-right:6px;"></i> Add Support Category
            </div>
            <button type="button" class="modal-close" onclick="closeModal('addCategoryModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('accounts.master.support-categories.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Category Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Technical Support, Billing & Invoicing, Account Access" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category Code</label>
                    <input type="text" name="code" class="form-input" placeholder="e.g. SUP-TECH, SUP-BILL">
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Scope</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Brief note about tickets and requests under this category..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status <span style="color:#dc2626;">*</span></label>
                    <select name="status" class="form-input" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('addCategoryModal')">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-check-lg"></i> Save Support Category
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: Edit Support Category --}}
<div class="modal-overlay" id="editCategoryModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pencil-square" style="color:#0d9488; margin-right:6px;"></i> Edit Support Category
            </div>
            <button type="button" class="modal-close" onclick="closeModal('editCategoryModal')">&times;</button>
        </div>
        <form id="editCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Category Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="edit_cat_name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category Code</label>
                    <input type="text" id="edit_cat_code" name="code" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Scope</label>
                    <textarea id="edit_cat_description" name="description" class="form-input" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status <span style="color:#dc2626;">*</span></label>
                    <select id="edit_cat_status" name="status" class="form-input" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div style="padding:14px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="acm-btn acm-btn-secondary" onclick="closeModal('editCategoryModal')">Cancel</button>
                <button type="submit" class="acm-btn acm-btn-primary">
                    <i class="bi bi-save"></i> Update Category
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAddCategoryModal() {
    $('#addCategoryModal').addClass('active');
}

function openEditCategoryModal(item) {
    var url = '{{ route('accounts.master.support-categories.update', ':id') }}'.replace(':id', item.id);
    $('#editCategoryForm').attr('action', url);
    $('#edit_cat_name').val(item.name || '');
    $('#edit_cat_code').val(item.code || '');
    $('#edit_cat_description').val(item.description || '');
    $('#edit_cat_status').val(item.status || 'active');
    $('#editCategoryModal').addClass('active');
}

function closeModal(modalId) {
    $('#' + modalId).removeClass('active');
}

$(document).on('click', '.modal-overlay', function(e) {
    if (e.target === this) {
        $(this).removeClass('active');
    }
});
</script>
@endpush
@endsection
