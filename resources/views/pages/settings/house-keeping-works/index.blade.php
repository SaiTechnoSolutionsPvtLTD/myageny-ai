@extends('layouts.app')
@section('title', 'House Keeping Works')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">House Keeping Works</h2>
                <p class="crm-subtitle">Select category and maintain work entries like Vessels washing, Drying clothes, and office cleaning points.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('house-keeping.index') }}" class="crm-btn crm-btn-ghost">Back</a>
                <a href="{{ route('settings.house-keeping-works.create') }}" class="crm-btn crm-btn-primary">+ Add Work</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div style="margin-bottom:16px;">
            <form method="GET" action="{{ route('settings.house-keeping-works.index') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
                <select name="house_keeping_category_id" class="crm-input" style="max-width:260px;">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('house_keeping_category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="search" class="crm-input" value="{{ request('search') }}" placeholder="Search work or notes" style="max-width:320px;">
                <button type="submit" class="crm-btn crm-btn-primary">Search</button>
                @if(request()->filled('search') || request()->filled('house_keeping_category_id'))
                    <a href="{{ route('settings.house-keeping-works.index') }}" class="crm-btn crm-btn-ghost">Reset</a>
                @endif
            </form>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Work Entry</th>
                        <th>Notes</th>
                        <th>Sort Order</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($works as $work)
                    <tr>
                        <td>{{ ($works->firstItem() ?? 1) + $loop->index }}</td>
                        <td><span class="crm-badge crm-badge-blue">{{ $work->category?->name ?: 'N/A' }}</span></td>
                        <td><strong>{{ $work->work_name }}</strong></td>
                        <td>{{ \Illuminate\Support\Str::limit($work->notes ?: 'No notes added.', 70) }}</td>
                        <td>{{ $work->sort_order }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                                    <a href="{{ route('settings.house-keeping-works.edit', $work) }}" class="crm-table-dropdown-item">Edit</a>
                                    <form action="{{ route('settings.house-keeping-works.destroy', $work) }}" method="POST" onsubmit="return confirm('Delete this house keeping work?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="crm-table-dropdown-item danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="crm-empty">No house keeping works created yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($works->hasPages())
                @include('partials.table-pagination', ['paginator' => $works])
            @endif
        </div>
    </div>
</main>
@endsection
