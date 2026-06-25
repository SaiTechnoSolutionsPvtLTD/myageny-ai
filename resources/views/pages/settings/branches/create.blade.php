@extends('layouts.app')
@section('title', 'Create Branch')

@push('styles')
@include('pages.settings.partials.table-styles')
<style>
.crm-form-wrap { max-width:860px; margin:0 auto; background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.crm-form-head { padding:20px 24px; border-bottom:1px solid #f1f1f1; }
.crm-form-body { padding:24px; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
.crm-form-foot { padding:20px 24px; border-top:1px solid #f1f1f1; display:flex; justify-content:flex-end; gap:10px; }
.crm-textarea { width:100%; min-height:120px; padding:10px 14px; border:1px solid #e1dee3; border-radius:10px; font-size:14px; outline:none; font-family:inherit; resize:vertical; }
.crm-textarea:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
.crm-span-2 { grid-column:1 / -1; }
@media (max-width: 768px) { .crm-form-body { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Create Branch</h2>
                <p class="crm-subtitle">Add a company branch. You can also mark it as the default branch.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('settings.branches.index') }}" class="crm-btn crm-btn-ghost">Back</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <form method="POST" action="{{ route('settings.branches.store') }}">
            @csrf
            <div class="crm-form-wrap">
                <div class="crm-form-head">
                    <h3 style="margin:0; font-size:16px; font-weight:700;">Branch Details</h3>
                </div>
                <div class="crm-form-body">
                    <div>
                        <label class="crm-label">Branch Name <span class="req">*</span></label>
                        <input type="text" name="name" class="crm-input" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label class="crm-label">Code</label>
                        <input type="text" name="code" class="crm-input" value="{{ old('code') }}">
                    </div>
                    <div>
                        <label class="crm-label">City</label>
                        <input type="text" name="city" class="crm-input" value="{{ old('city') }}">
                    </div>
                    <div>
                        <label class="crm-label">State</label>
                        <input type="text" name="state" class="crm-input" value="{{ old('state') }}">
                    </div>
                    <div>
                        <label class="crm-label">Phone</label>
                        <input type="text" name="phone" class="crm-input" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <label class="crm-label">Email</label>
                        <input type="email" name="email" class="crm-input" value="{{ old('email') }}">
                    </div>
                    <div>
                        <label class="crm-label">Latitude</label>
                        <input type="number" name="latitude" class="crm-input" value="{{ old('latitude') }}" min="-90" max="90" step="0.0000001" placeholder="Ex: 13.0826800">
                    </div>
                    <div>
                        <label class="crm-label">Longitude</label>
                        <input type="number" name="longitude" class="crm-input" value="{{ old('longitude') }}" min="-180" max="180" step="0.0000001" placeholder="Ex: 80.2707200">
                    </div>
                    <div class="crm-span-2">
                        <label class="crm-label">Address</label>
                        <textarea name="address" class="crm-textarea">{{ old('address') }}</textarea>
                    </div>
                    <div>
                        <label style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                            <span>Active Branch</span>
                        </label>
                    </div>
                    <div>
                        <label style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))>
                            <span>Set as Default Branch</span>
                        </label>
                    </div>
                </div>
                <div class="crm-form-foot">
                    <a href="{{ route('settings.branches.index') }}" class="crm-btn crm-btn-ghost">Cancel</a>
                    <button type="submit" class="crm-btn crm-btn-primary">Create Branch</button>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection
