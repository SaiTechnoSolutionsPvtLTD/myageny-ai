@extends('layouts.app')
@section('title', 'Edit Facility Entry')

@push('styles')
@include('pages.settings.partials.table-styles')
<style>
.crm-form-wrap { max-width:760px; margin:0 auto; background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.crm-form-head { padding:20px 24px; border-bottom:1px solid #f1f1f1; }
.crm-form-body { padding:24px; display:flex; flex-direction:column; gap:18px; }
.crm-form-foot { padding:20px 24px; border-top:1px solid #f1f1f1; display:flex; justify-content:flex-end; gap:10px; }
.crm-auto-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; }
.crm-readonly { background:#f8f8f8; color:#555; }
</style>
@endpush

@section('content')
@php
    $entryDate = $facilityEntry->entry_date
        ?? $facilityEntry->office_mopping_date
        ?? $facilityEntry->office_cleaning_date
        ?? $facilityEntry->toilet_cleaning_date
        ?? $facilityEntry->created_at;
    $entryTime = $facilityEntry->entry_time
        ? \Carbon\Carbon::parse($facilityEntry->entry_time)->format('h:i A')
        : optional($facilityEntry->created_at)->format('h:i A');
@endphp
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Edit Facility Entry</h2>
                <p class="crm-subtitle">Update the selected facility title for this entry.</p>
            </div>
            <div class="crm-header-actions">
                @can('settings.manage')
                    <a href="{{ route('settings.facility-titles.create') }}" class="crm-btn crm-btn-ghost">+ Add Title Master</a>
                @endcan
                <a href="{{ route('facility-management.index') }}" class="crm-btn crm-btn-ghost">Back</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <form method="POST" action="{{ route('facility-management.update', $facilityEntry) }}">
            @csrf
            @method('PUT')
            <div class="crm-form-wrap">
                <div class="crm-form-head">
                    <h3 style="margin:0; font-size:16px; font-weight:700;">Facility Details</h3>
                </div>
                <div class="crm-form-body">
                    <div>
                        <label class="crm-label">Title <span class="req">*</span></label>
                        <select name="facility_title_id" class="crm-input" required @disabled($facilityTitles->isEmpty())>
                            <option value="">Select title</option>
                            @foreach($facilityTitles as $facilityTitle)
                                <option value="{{ $facilityTitle->id }}" @selected((string) old('facility_title_id', $facilityEntry->facility_title_id) === (string) $facilityTitle->id)>
                                    {{ $facilityTitle->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-auto-grid">
                        <div>
                            <label class="crm-label">Date</label>
                            <input type="text" class="crm-input crm-readonly" value="{{ $entryDate?->format('d M Y') ?? 'N/A' }}" readonly>
                        </div>
                        <div>
                            <label class="crm-label">Time</label>
                            <input type="text" class="crm-input crm-readonly" value="{{ $entryTime ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="crm-form-foot">
                    <a href="{{ route('facility-management.index') }}" class="crm-btn crm-btn-ghost">Cancel</a>
                    <button type="submit" class="crm-btn crm-btn-primary" @disabled($facilityTitles->isEmpty())>Update Entry</button>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection
