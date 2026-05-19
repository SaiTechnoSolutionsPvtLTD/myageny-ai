@extends('layouts.app')
@section('title', 'Department')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Department</h2>
                <p class="crm-subtitle">Create and manage employee departments with soft delete support.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('hrms.masters.index') }}" class="crm-btn crm-btn-ghost">Back</a>
                <a href="{{ route('settings.departments.create') }}" class="crm-btn crm-btn-primary">+ Add Department</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div style="margin-bottom:16px;">
            <form method="GET" action="{{ route('settings.departments.index') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
                <input type="text" name="search" class="crm-input" value="{{ request('search') }}" placeholder="Search department name or description" style="max-width:360px;">
                <button type="submit" class="crm-btn crm-btn-primary">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('settings.departments.index') }}" class="crm-btn crm-btn-ghost">Reset</a>
                @endif
            </form>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($departments as $department)
                    <tr>
                        <td>{{ ($departments->firstItem() ?? 1) + $loop->index }}</td>
                        <td><strong>{{ $department->name }}</strong></td>
                        <td>{{ \Illuminate\Support\Str::limit($department->description ?: 'No description added.', 70) }}</td>
                        <td>{{ $department->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                            <a href="{{ route('settings.departments.edit', $department) }}" class="crm-icon-btn" title="Edit">✏️</a>
                            <form action="{{ route('settings.departments.destroy', $department) }}" method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete this department? It will be soft deleted.')">
                                @csrf
                                @method('DELETE')
                                <button class="crm-icon-btn danger" title="Delete">🗑️</button>
                            </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="crm-empty">No departments created yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($departments->hasPages())
                @include('partials.table-pagination', ['paginator' => $departments])
            @endif
        </div>
    </div>
</main>
@endsection
