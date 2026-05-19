@extends('layouts.app')
@section('title', 'Asset Categories')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Asset Categories</h2>
                <p class="crm-subtitle">Create and manage asset category master data used in HRMS asset entries.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('hrms.masters.index') }}" class="crm-btn crm-btn-ghost">Back</a>
                <a href="{{ route('settings.asset-categories.create') }}" class="crm-btn crm-btn-primary">+ Add Asset Category</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div style="margin-bottom:16px;">
            <form method="GET" action="{{ route('settings.asset-categories.index') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
                <input type="text" name="search" class="crm-input" value="{{ request('search') }}" placeholder="Search asset category or description" style="max-width:360px;">
                <button type="submit" class="crm-btn crm-btn-primary">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('settings.asset-categories.index') }}" class="crm-btn crm-btn-ghost">Reset</a>
                @endif
            </form>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Asset Category</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($assetCategories as $assetCategory)
                    <tr>
                        <td>{{ ($assetCategories->firstItem() ?? 1) + $loop->index }}</td>
                        <td><strong>{{ $assetCategory->name }}</strong></td>
                        <td>{{ \Illuminate\Support\Str::limit($assetCategory->description ?: 'No description added.', 70) }}</td>
                        <td>{{ $assetCategory->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                                    <a href="{{ route('settings.asset-categories.edit', $assetCategory) }}" class="crm-icon-btn" title="Edit" aria-label="Edit asset category">
                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-6"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <form action="{{ route('settings.asset-categories.destroy', $assetCategory) }}" method="POST" style="display:inline"
                                          onsubmit="return confirm('Delete this asset category? It will be soft deleted.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="crm-icon-btn danger" title="Delete" aria-label="Delete asset category">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="crm-empty">No asset categories created yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($assetCategories->hasPages())
                @include('partials.table-pagination', ['paginator' => $assetCategories])
            @endif
        </div>
    </div>
</main>
@endsection
