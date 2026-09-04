@extends('layouts.app')
@section('title', 'Lead Source — Settings')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">


    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Lead Source</h2>
                <p class="crm-subtitle">Track where your leads originate from</p>
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
                    <tr><th>#</th><th>Source Name</th><th>Created</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($sources as $source)
                    <tr>
                        <td>{{ ($sources->firstItem() ?? 1) + $loop->index }}</td>
                        <td><span class="crm-badge crm-badge-blue">{{ $source->name }}</span></td>
                        <td>{{ $source->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                                    <button type="button" class="crm-table-dropdown-item" onclick="openEdit({{ $source->id }}, '{{ addslashes($source->name) }}'); this.closest('details')?.removeAttribute('open');">
                                        <i class="bi bi-pencil"></i>
                                        <span>Edit</span>
                                    </button>
                                    <form action="{{ route('settings.lead-sources.destroy', $source) }}" method="POST"
                                          onsubmit="return confirm('Delete this source?')">
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
                    <tr><td colspan="4" class="crm-empty">No lead sources yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($sources->hasPages())
                @include('partials.table-pagination', ['paginator' => $sources])
            @endif
        </div>
    </div>

    {{-- ADD MODAL --}}
    <div id="addModal" class="crm-modal-overlay" style="display:none">
        <div class="crm-modal">
            <div class="crm-modal-header">
                <h3>Add Lead Source</h3>
                <button onclick="closeModal('addModal')">✕</button>
            </div>
            <form action="{{ route('settings.lead-sources.store') }}" method="POST">
                @csrf
                <div class="crm-modal-body">
                    <label class="crm-label">Source Name <span class="req">*</span></label>
                    <input type="text" name="name" class="crm-input" placeholder="e.g. Website, Referral, Cold Call…" required>
                </div>
                <div class="crm-modal-footer">
                    <button type="button" class="crm-btn crm-btn-ghost" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary">Save Source</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div id="editModal" class="crm-modal-overlay" style="display:none">
        <div class="crm-modal">
            <div class="crm-modal-header">
                <h3>Edit Lead Source</h3>
                <button onclick="closeModal('editModal')">✕</button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="crm-modal-body">
                    <label class="crm-label">Source Name <span class="req">*</span></label>
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
    document.getElementById('editForm').action = `/settings/lead-sources/${id}`;
    openModal('editModal');
}
</script>
@endpush
@endsection
