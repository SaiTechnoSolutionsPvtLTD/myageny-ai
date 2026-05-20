@extends('layouts.app')
@section('title', 'Create Facility Entry')

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
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Create Facility Entry</h2>
                <p class="crm-subtitle">Select a facility title. Date and time will be stored automatically.</p>
            </div>
            <div class="crm-header-actions">
                @can('settings.manage')
                    <a href="{{ route('settings.facility-titles.create') }}" class="crm-btn crm-btn-ghost">+ Add Title Master</a>
                @endcan
                <a href="{{ route('facility-management.index') }}" class="crm-btn crm-btn-ghost">Back</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <form method="POST" action="{{ route('facility-management.store') }}">
            @csrf
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
                                <option value="{{ $facilityTitle->id }}" @selected((string) old('facility_title_id') === (string) $facilityTitle->id)>
                                    {{ $facilityTitle->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-auto-grid">
                        <div>
                            <label class="crm-label">Date</label>
                            <input type="text" class="crm-input crm-readonly" value="{{ $currentDateTime->format('d M Y') }}" readonly>
                        </div>
                        <div>
                            <label class="crm-label">Time</label>
                            <input type="text" class="crm-input crm-readonly" value="{{ $currentDateTime->format('h:i A') }}" readonly>
                        </div>
                    </div>

                    <div>
                        <label class="crm-label">Remarks</label>
                        <textarea name="remarks" class="crm-input" rows="4" placeholder="Optional remarks">{{ old('remarks') }}</textarea>
                    </div>
                </div>
                <div class="crm-form-foot">
                    <a href="{{ route('facility-management.index') }}" class="crm-btn crm-btn-ghost">Cancel</a>
                    <button type="submit" class="crm-btn crm-btn-primary" @disabled($facilityTitles->isEmpty())>Create Entry</button>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection
