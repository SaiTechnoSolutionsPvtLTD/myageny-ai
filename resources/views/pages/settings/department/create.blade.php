@extends('layouts.app')
@section('title', 'Create Department')

@push('styles')
@include('pages.settings.partials.table-styles')
<style>
.crm-form-wrap { max-width:760px; margin:0 auto; background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.crm-form-head { padding:20px 24px; border-bottom:1px solid #f1f1f1; }
.crm-form-body { padding:24px; display:flex; flex-direction:column; gap:18px; }
.crm-form-foot { padding:20px 24px; border-top:1px solid #f1f1f1; display:flex; justify-content:flex-end; gap:10px; }
.crm-textarea { width:100%; min-height:120px; padding:10px 14px; border:1px solid #e1dee3; border-radius:10px; font-size:14px; outline:none; font-family:inherit; resize:vertical; }
.crm-textarea:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
</style>
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Create Department</h2>
                <p class="crm-subtitle">Add a new department for employee classification and HR workflows.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('settings.departments.index') }}" class="crm-btn crm-btn-ghost">Back</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <form method="POST" action="{{ route('settings.departments.store') }}">
            @csrf
            <div class="crm-form-wrap">
                <div class="crm-form-head">
                    <h3 style="margin:0; font-size:16px; font-weight:700;">Department Details</h3>
                </div>
                <div class="crm-form-body">
                    <div>
                        <label class="crm-label">Department Name <span class="req">*</span></label>
                        <input type="text" name="name" class="crm-input" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label class="crm-label">Description</label>
                        <textarea name="description" class="crm-textarea" placeholder="Optional department description">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="crm-label">Department Dashboard</label>
                        <select name="dashboard_route" class="crm-input">
                            <option value="">Default User Dashboard</option>
                            @foreach($dashboardRouteOptions as $routeName => $routeLabel)
                                <option value="{{ $routeName }}" @selected(old('dashboard_route') === $routeName)>{{ $routeLabel }}</option>
                            @endforeach
                        </select>
                        <small style="display:block; margin-top:6px; color:#6b7280;">Users in this department will open this dashboard after login.</small>
                    </div>
                </div>
                <div class="crm-form-foot">
                    <a href="{{ route('settings.departments.index') }}" class="crm-btn crm-btn-ghost">Cancel</a>
                    <button type="submit" class="crm-btn crm-btn-primary">Create Department</button>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection
