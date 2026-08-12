@extends('layouts.app')

@section('title', 'Expense Category Master - myAgenci.ai')

@push('styles')
<style>
.exp-cat-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.exp-cat-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
    padding: 28px;
    border: 1px solid #e6e8ee;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
}
.exp-cat-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.exp-cat-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.exp-cat-subtitle {
    margin: 10px 0 0;
    max-width: 640px;
    font-size: 14px;
    line-height: 1.6;
    color: #6b7280;
}
.exp-cat-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    border: none;
    transition: all .2s ease;
    text-decoration: none;
    font-family: inherit;
}
.exp-cat-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #fff;
    box-shadow: 0 6px 20px rgba(254, 95, 4, 0.28);
}
.exp-cat-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(254, 95, 4, 0.38);
    color: #fff;
}
.exp-cat-btn-outline {
    background: #fff;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.exp-cat-btn-outline:hover {
    border-color: #fe5f04;
    color: #fe5f04;
}

/* Table Card */
.exp-cat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}
.exp-cat-filter-bar {
    padding: 18px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    background: #fafafa;
}
.exp-search-input {
    padding: 8px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 13px;
    width: 260px;
    outline: none;
}
.exp-search-input:focus {
    border-color: #fe5f04;
}
.exp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.exp-table th {
    background: #f9fafb;
    padding: 14px 20px;
    text-align: left;
    font-weight: 800;
    color: #4b5563;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .6px;
    border-bottom: 1px solid #e5e7eb;
}
.exp-table td {
    padding: 14px 20px;
    border-bottom: 1px solid #f3f4f6;
    color: #1f2937;
    vertical-align: middle;
}
.exp-table tr:hover { background: #fffdfb; }

/* Modal */
.exp-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}
.exp-modal {
    background: #ffffff;
    border-radius: 20px;
    width: 90%;
    max-width: 520px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
    animation: popIn .2s ease;
    overflow: hidden;
}
@keyframes popIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.exp-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
}
.exp-modal-title { font-size: 18px; font-weight: 800; color: #111827; }
.exp-modal-close {
    background: none; border: none; font-size: 22px; color: #9ca3af; cursor: pointer;
}
.exp-modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.exp-form-group { display: flex; flex-direction: column; gap: 6px; }
.exp-form-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #4b5563; }
.exp-input, .exp-textarea {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    background: #fff;
}
.exp-input:focus, .exp-textarea:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.exp-modal-foot {
    padding: 16px 24px;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
</style>
@endpush

@section('content')
<div class="exp-cat-page">

    {{-- Hero --}}
    <div class="exp-cat-hero">
        <div>
            <div class="exp-cat-kicker">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                HRMS Masters
            </div>
            <h2 class="exp-cat-title">Expense Category Master</h2>
            <p class="exp-cat-subtitle">Define and manage expense category masters used across petty cash claims, employee expenses, and finance accounting.</p>
        </div>
        <div>
            <button class="exp-cat-btn exp-cat-btn-primary" onclick="openCreateModal()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Expense Category
            </button>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div style="margin-bottom:20px; padding:14px 18px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Main Table Card --}}
    <div class="exp-cat-card">
        <form method="GET" action="{{ route('settings.expense-categories.index') }}">
            <div class="exp-cat-filter-bar">
                <div style="display:flex; align-items:center; gap:12px;">
                    <input type="text" name="search" class="exp-search-input" placeholder="Search category name, code…" value="{{ request('search') }}">
                    
                    <select name="status" class="exp-search-input" style="width:140px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <button type="submit" class="exp-cat-btn exp-cat-btn-primary" style="padding:7px 16px; font-size:12px; height:34px; border-radius:9px;">Search</button>

                    @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('settings.expense-categories.index') }}" style="font-size:12px; color:#6b7280; text-decoration:none; font-weight:600;">Reset</a>
                    @endif
                </div>

                <div style="font-size:12px; font-weight:700; color:#6b7280;">
                    Total: {{ $categories->total() }} Categories
                </div>
            </div>
        </form>

        <table class="exp-table">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Category Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $index => $category)
                <tr>
                    <td style="color:#9ca3af; font-weight:600;">
                        {{ $categories->firstItem() + $index }}
                    </td>
                    <td>
                        <strong style="color:#111827; font-size:14px;">{{ $category->name }}</strong>
                    </td>
                    <td>
                        @if($category->code)
                            <span style="display:inline-block; padding:3px 8px; border-radius:6px; background:#eff6ff; color:#2563eb; font-weight:800; font-size:11px; font-family:monospace;">
                                {{ strtoupper($category->code) }}
                            </span>
                        @else
                            <span style="color:#9ca3af; font-style:italic;">-</span>
                        @endif
                    </td>
                    <td style="color:#6b7280; max-width:280px;">
                        {{ $category->description ?? '-' }}
                    </td>
                    <td>
                        <form method="POST" action="{{ route('settings.expense-categories.toggle-status', $category) }}">
                            @csrf @method('PATCH')
                            <button type="submit" style="background:none; border:none; cursor:pointer;" title="Click to toggle status">
                                <span class="usr-badge {{ $category->is_active ? 'ub-active' : 'ub-inactive' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </button>
                        </form>
                    </td>
                    <td style="color:#9ca3af; font-size:12px;">
                        {{ $category->created_at ? $category->created_at->format('d M Y') : '-' }}
                    </td>
                    <td style="text-align:right;">
                        <div style="display:inline-flex; gap:8px;">
                            <button class="exp-cat-btn exp-cat-btn-outline" style="padding:5px 12px; font-size:12px;"
                                onclick='openEditModal(@json($category))'>
                                Edit
                            </button>

                            <form method="POST" action="{{ route('settings.expense-categories.destroy', $category) }}" onsubmit="return confirm('Delete this expense category?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="exp-cat-btn exp-cat-btn-outline" style="padding:5px 10px; font-size:12px; color:#dc2626; border-color:#fecaca;">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:50px 20px; color:#6b7280;">
                        <div style="font-size:36px; margin-bottom:8px;">📁</div>
                        <div style="font-weight:700; color:#374151; font-size:15px;">No Expense Categories Found</div>
                        <div style="font-size:13px; margin-top:4px;">Click "+ Add Expense Category" to create your first category.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($categories->hasPages())
        <div style="padding:16px 24px; border-top:1px solid #f3f4f6;">
            {{ $categories->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Add / Edit Category Modal --}}
<div class="exp-modal-overlay" id="categoryModal">
    <div class="exp-modal">
        <div class="exp-modal-head">
            <div class="exp-modal-title" id="modalTitle">Add Expense Category</div>
            <button class="exp-modal-close" onclick="closeModal()">✕</button>
        </div>

        <form id="categoryForm" method="POST" action="{{ route('settings.expense-categories.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="exp-modal-body">
                <div class="exp-form-group">
                    <label class="exp-form-label">Category Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="name" id="catNameInput" class="exp-input" required placeholder="E.g., Travel & Transportation, Office Supplies">
                </div>

                <div class="exp-form-group">
                    <label class="exp-form-label">Short Code / Abbreviation (Optional)</label>
                    <input type="text" name="code" id="catCodeInput" class="exp-input" placeholder="E.g., TRV, OFF, MEAL">
                </div>

                <div class="exp-form-group">
                    <label class="exp-form-label">Description (Optional)</label>
                    <textarea name="description" id="catDescInput" rows="3" class="exp-textarea" placeholder="Brief details about what expenses fall into this category..."></textarea>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="is_active" id="catActiveInput" value="1" checked style="width:16px; height:16px; accent-color:#fe5f04;">
                    <label for="catActiveInput" style="font-size:13px; font-weight:700; color:#374151; cursor:pointer;">Active Category</label>
                </div>
            </div>

            <div class="exp-modal-foot">
                <button type="button" class="exp-cat-btn exp-cat-btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="exp-cat-btn exp-cat-btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add Expense Category';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('categoryForm').action = "{{ route('settings.expense-categories.store') }}";
    document.getElementById('catNameInput').value = '';
    document.getElementById('catCodeInput').value = '';
    document.getElementById('catDescInput').value = '';
    document.getElementById('catActiveInput').checked = true;
    document.getElementById('categoryModal').style.display = 'flex';
}

function openEditModal(category) {
    document.getElementById('modalTitle').textContent = 'Edit Expense Category';
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('categoryForm').action = `/settings/expense-categories/${category.id}`;
    document.getElementById('catNameInput').value = category.name || '';
    document.getElementById('catCodeInput').value = category.code || '';
    document.getElementById('catDescInput').value = category.description || '';
    document.getElementById('catActiveInput').checked = Boolean(category.is_active);
    document.getElementById('categoryModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endpush
