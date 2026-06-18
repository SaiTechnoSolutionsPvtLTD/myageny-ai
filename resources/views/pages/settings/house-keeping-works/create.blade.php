@extends('layouts.app')
@section('title', 'Create House Keeping Work')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body" style="max-width:760px;">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Create House Keeping Work</h2>
                <p class="crm-subtitle">Choose a category and add the work entry text shown in the cleaning sheet.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('settings.house-keeping-works.index') }}" class="crm-btn crm-btn-ghost">Back</a>
            </div>
        </div>

        <div class="crm-table-wrap" style="padding:20px;">
            <form method="POST" action="{{ route('settings.house-keeping-works.store') }}" style="display:grid; gap:16px;">
                @csrf
                <div>
                    <label class="crm-label">Category <span class="req">*</span></label>
                    <select name="house_keeping_category_id" class="crm-input" required>
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('house_keeping_category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('house_keeping_category_id')<div style="margin-top:6px;color:#dc2626;font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="crm-label">Work Entry <span class="req">*</span></label>
                    <input type="text" name="work_name" class="crm-input" value="{{ old('work_name') }}" required>
                    @error('work_name')<div style="margin-top:6px;color:#dc2626;font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="crm-label">Notes</label>
                    <textarea name="notes" class="crm-input" rows="4">{{ old('notes') }}</textarea>
                    @error('notes')<div style="margin-top:6px;color:#dc2626;font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="crm-label">Sort Order</label>
                    <input type="number" name="sort_order" class="crm-input" min="0" value="{{ old('sort_order', 0) }}">
                    @error('sort_order')<div style="margin-top:6px;color:#dc2626;font-size:12px;">{{ $message }}</div>@enderror
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" class="crm-btn crm-btn-primary">Create Work</button>
                    <a href="{{ route('settings.house-keeping-works.index') }}" class="crm-btn crm-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
