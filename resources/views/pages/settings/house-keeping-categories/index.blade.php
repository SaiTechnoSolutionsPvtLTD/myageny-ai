@extends('layouts.app')
@section('title', 'House Keeping Categories')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">House Keeping Categories</h2>
                <p class="crm-subtitle">Create category masters like Office, Ladies Toilet, and House for the monthly cleaning sheet.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('house-keeping.index') }}" class="crm-btn crm-btn-ghost">Back</a>
                <a href="{{ route('settings.house-keeping-categories.create') }}" class="crm-btn crm-btn-primary">+ Add Category</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div style="margin-bottom:16px;">
            <form method="GET" action="{{ route('settings.house-keeping-categories.index') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
                <input type="text" name="search" class="crm-input" value="{{ request('search') }}" placeholder="Search category or description" style="max-width:360px;">
                <button type="submit" class="crm-btn crm-btn-primary">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('settings.house-keeping-categories.index') }}" class="crm-btn crm-btn-ghost">Reset</a>
                @endif
            </form>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Works</th>
                        <th>Sort Order</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>{{ ($categories->firstItem() ?? 1) + $loop->index }}</td>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td>{{ \Illuminate\Support\Str::limit($category->description ?: 'No description added.', 70) }}</td>
                        <td><span class="crm-count-badge">{{ $category->works_count }}</span></td>
                        <td>{{ $category->sort_order }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                                    <a href="{{ route('settings.house-keeping-categories.edit', $category) }}" class="crm-table-dropdown-item">Edit</a>
                                    <form action="{{ route('settings.house-keeping-categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this house keeping category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="crm-table-dropdown-item danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="crm-empty">No house keeping categories created yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($categories->hasPages())
                @include('partials.table-pagination', ['paginator' => $categories])
            @endif
        </div>
    </div>
</main>
@endsection
