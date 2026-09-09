@extends('layouts.app')
@section('title', 'Outcome Category — Masters')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">


    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Outcome Category</h2>
                <p class="crm-subtitle">Group your call/meeting outcomes</p>
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
                    <tr><th>#</th><th>Category Name</th><th>Sub-categories</th><th>Created</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($categories as $cat)
                    <tr>
                        <td>{{ ($categories->firstItem() ?? 1) + $loop->index }}</td>
                        <td><strong>{{ $cat->name }}</strong></td>
                        <td><span class="crm-count-badge">{{ $cat->sub_categories_count }}</span></td>
                        <td>{{ $cat->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                                    <button type="button" class="crm-table-dropdown-item" onclick="openEdit({{ $cat->id }}, '{{ addslashes($cat->name) }}'); this.closest('details')?.removeAttribute('open');">
                                        <i class="bi bi-pencil"></i>
                                        <span>Edit</span>
                                    </button>
                                    <form action="{{ route('masters.outcome-categories.destroy', $cat) }}" method="POST"
                                          onsubmit="return confirm('Delete this category and all its sub-categories?')">
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
                    <tr><td colspan="5" class="crm-empty">No outcome categories yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($categories->hasPages())
                @include('partials.table-pagination', ['paginator' => $categories])
            @endif
        </div>
    </div>

    {{-- ADD --}}
    <div id="addModal" class="crm-modal-overlay" style="display:none">
        <div class="crm-modal">
            <div class="crm-modal-header"><h3>Add Outcome Category</h3><button onclick="closeModal('addModal')">✕</button></div>
            <form action="{{ route('masters.outcome-categories.store') }}" method="POST">
                @csrf
                <div class="crm-modal-body">
                    <label class="crm-label">Category Name <span class="req">*</span></label>
                    <input type="text" name="name" class="crm-input" placeholder="e.g. Follow-up, Closed, Interested…" required>
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
            <div class="crm-modal-header"><h3>Edit Outcome Category</h3><button onclick="closeModal('editModal')">✕</button></div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="crm-modal-body">
                    <label class="crm-label">Category Name <span class="req">*</span></label>
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
function openEdit(id, name) {
    document.getElementById('editName').value = name;
    document.getElementById('editForm').action = `/masters/outcome-categories/${id}`;
    openModal('editModal');
}
</script>
@endpush
@endsection
