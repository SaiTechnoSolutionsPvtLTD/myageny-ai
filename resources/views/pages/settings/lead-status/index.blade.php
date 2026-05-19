@extends('layouts.app')
@section('title', 'Lead Status — Settings')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">


    <div class="crm-page-body">
        {{-- Page title + Add button --}}
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Lead Status</h2>
                <p class="crm-subtitle">Define the stages a lead can be in</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('settings.index') }}" class="crm-btn crm-btn-ghost">← Back</a>
                <button class="crm-btn crm-btn-primary" onclick="openModal('addModal')">+ Add New</button>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        {{-- Table --}}
        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status Name</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($statuses as $status)
                    <tr>
                        <td>{{ ($statuses->firstItem() ?? 1) + $loop->index }}</td>
                        <td><span class="crm-badge">{{ $status->name }}</span></td>
                        <td>{{ $status->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                            <button class="crm-icon-btn" onclick="openEdit({{ $status->id }}, '{{ addslashes($status->name) }}')">✏️</button>
                            <form action="{{ route('settings.lead-statuses.destroy', $status) }}" method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete this status?')">
                                @csrf @method('DELETE')
                                <button class="crm-icon-btn danger">🗑️</button>
                            </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="crm-empty">No lead statuses yet. Add one!</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($statuses->hasPages())
                @include('partials.table-pagination', ['paginator' => $statuses])
            @endif
        </div>
    </div>

    {{-- ADD MODAL --}}
    <div id="addModal" class="crm-modal-overlay" style="display:none">
        <div class="crm-modal">
            <div class="crm-modal-header">
                <h3>Add Lead Status</h3>
                <button onclick="closeModal('addModal')">✕</button>
            </div>
            <form action="{{ route('settings.lead-statuses.store') }}" method="POST">
                @csrf
                <div class="crm-modal-body">
                    <label class="crm-label">Status Name <span class="req">*</span></label>
                    <input type="text" name="name" class="crm-input" placeholder="e.g. New, Contacted, Qualified…" required>
                </div>
                <div class="crm-modal-footer">
                    <button type="button" class="crm-btn crm-btn-ghost" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary">Save Status</button>
                </div>
            </form>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div id="editModal" class="crm-modal-overlay" style="display:none">
        <div class="crm-modal">
            <div class="crm-modal-header">
                <h3>Edit Lead Status</h3>
                <button onclick="closeModal('editModal')">✕</button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="crm-modal-body">
                    <label class="crm-label">Status Name <span class="req">*</span></label>
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
    document.getElementById('editForm').action = `/settings/lead-statuses/${id}`;
    openModal('editModal');
}
</script>
@endpush
@endsection
