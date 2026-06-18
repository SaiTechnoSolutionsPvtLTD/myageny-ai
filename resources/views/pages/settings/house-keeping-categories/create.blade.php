@extends('layouts.app')
@section('title', 'Create House Keeping Category')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body" style="max-width:760px;">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Create House Keeping Category</h2>
                <p class="crm-subtitle">Add a cleaning category such as Office, Ladies Toilet, or House.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('settings.house-keeping-categories.index') }}" class="crm-btn crm-btn-ghost">Back</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div class="crm-table-wrap" style="padding:20px;">
            <form method="POST" action="{{ route('settings.house-keeping-categories.store') }}" style="display:grid; gap:16px;">
                @csrf
                <div>
                    <label class="crm-label">Category Name <span class="req">*</span></label>
                    <input type="text" name="name" class="crm-input" value="{{ old('name') }}" required>
                    @error('name')<div style="margin-top:6px;color:#dc2626;font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="crm-label">Description</label>
                    <textarea name="description" class="crm-input" rows="4">{{ old('description') }}</textarea>
                    @error('description')<div style="margin-top:6px;color:#dc2626;font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="crm-label">Sort Order</label>
                    <input type="number" name="sort_order" class="crm-input" min="0" value="{{ old('sort_order', 0) }}">
                    @error('sort_order')<div style="margin-top:6px;color:#dc2626;font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="crm-btn crm-btn-primary">Create Category</button>
                    <a href="{{ route('settings.house-keeping-categories.index') }}" class="crm-btn crm-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
