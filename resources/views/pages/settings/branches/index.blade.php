@extends('layouts.app')
@section('title', 'Branches')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Branches</h2>
                <p class="crm-subtitle">Manage company-wise branches. One default branch will always remain available.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('settings.index') }}" class="crm-btn crm-btn-ghost">Back</a>
                <a href="{{ route('settings.branches.create') }}" class="crm-btn crm-btn-primary">+ Add Branch</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div style="margin-bottom:16px;">
            <form method="GET" action="{{ route('settings.branches.index') }}" style="display:flex; gap:10px; flex-wrap:wrap;">
                <input type="text" name="search" class="crm-input" value="{{ request('search') }}" placeholder="Search branch name, code, city, or state" style="max-width:360px;">
                <button type="submit" class="crm-btn crm-btn-primary">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('settings.branches.index') }}" class="crm-btn crm-btn-ghost">Reset</a>
                @endif
            </form>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Branch Name</th>
                        <th>Code</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td>{{ ($branches->firstItem() ?? 1) + $loop->index }}</td>
                        <td><strong>{{ $branch->name }}</strong></td>
                        <td>{{ $branch->code ?: '—' }}</td>
                        <td>{{ collect([$branch->city, $branch->state])->filter()->implode(', ') ?: '—' }}</td>
                        <td>{{ $branch->is_active ? 'Active' : 'Inactive' }}</td>
                        <td>{{ $branch->is_default ? 'Yes' : 'No' }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                                    <a href="{{ route('settings.branches.edit', $branch) }}" class="crm-icon-btn" title="Edit">✏️</a>
                                    <form action="{{ route('settings.branches.destroy', $branch) }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this branch?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="crm-icon-btn danger" title="Delete">🗑️</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="crm-empty">No branches found.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($branches->hasPages())
                @include('partials.table-pagination', ['paginator' => $branches])
            @endif
        </div>
    </div>
</main>
@endsection
