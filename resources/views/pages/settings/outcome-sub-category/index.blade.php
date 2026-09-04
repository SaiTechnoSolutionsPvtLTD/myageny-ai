@extends('layouts.app')
@section('title', 'Outcome Sub Category — Settings')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">


    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Outcome Sub Category</h2>
                <p class="crm-subtitle">Define detailed outcomes under each category</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('masters.index') }}" class="crm-btn crm-btn-ghost">← Back</a>
                <button class="crm-btn crm-btn-primary" onclick="openModal('addModal')">+ Add New</button>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr><th>#</th><th>Sub Category Name</th><th>Parent Category</th><th>Created</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($subCategories as $sub)
                    <tr>
                        <td>{{ ($subCategories->firstItem() ?? 1) + $loop->index }}</td>
                        <td>{{ $sub->name }}</td>
                        <td><span class="crm-badge crm-badge-purple">{{ $sub->category->name ?? '—' }}</span></td>
                        <td>{{ $sub->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                                    <button type="button" class="crm-table-dropdown-item"
                                        onclick="openEdit({{ $sub->id }}, '{{ addslashes($sub->name) }}', {{ $sub->category_id }}); this.closest('details')?.removeAttribute('open');">
                                        <i class="bi bi-pencil"></i>
                                        <span>Edit</span>
                                    </button>
                                    <form action="{{ route('settings.outcome-sub-categories.destroy', $sub) }}" method="POST"
                                          onsubmit="return confirm('Delete this sub-category?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="crm-table-dropdown-item danger">
                                            <i class="bi bi-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="crm-empty">No sub-categories yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($subCategories->hasPages())
                @include('partials.table-pagination', ['paginator' => $subCategories])
            @endif
        </div>
    </div>

    {{-- ADD --}}
    <div id="addModal" class="crm-modal-overlay" style="display:none">
        <div class="crm-modal">
            <div class="crm-modal-header"><h3>Add Sub Category</h3><button onclick="closeModal('addModal')">✕</button></div>
            <form action="{{ route('settings.outcome-sub-categories.store') }}" method="POST">
                @csrf
                <div class="crm-modal-body">
                    <label class="crm-label">Outcome Category <span class="req">*</span></label>
                    <select name="category_id" class="crm-input" required>
                        <option value="">— Select Category —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <label class="crm-label" style="margin-top:12px">Sub Category Name <span class="req">*</span></label>
                    <input type="text" name="name" class="crm-input" placeholder="e.g. Rescheduled, Not Interested…" required>
                </div>
                <div class="crm-modal-footer">
                    <button type="button" class="crm-btn crm-btn-ghost" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT --}}
    <div id="editModal" class="crm-modal-overlay" style="display:none">
        <div class="crm-modal">
            <div class="crm-modal-header"><h3>Edit Sub Category</h3><button onclick="closeModal('editModal')">✕</button></div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="crm-modal-body">
                    <label class="crm-label">Outcome Category <span class="req">*</span></label>
                    <select id="editCatId" name="category_id" class="crm-input" required>
                        <option value="">— Select Category —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <label class="crm-label" style="margin-top:12px">Sub Category Name <span class="req">*</span></label>
                    <input type="text" id="editName" name="name" class="crm-input" required>
                </div>
                <div class="crm-modal-footer">
                    <button type="button" class="crm-btn crm-btn-ghost" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</main>

@push('scripts')
@include('pages.settings.partials.modal-scripts')
<script>
function openEdit(id, name, catId) {
    document.getElementById('editName').value   = name;
    document.getElementById('editCatId').value  = catId;
    document.getElementById('editForm').action  = `/settings/outcome-sub-categories/${id}`;
    openModal('editModal');
}
</script>
@endpush
@endsection
