@extends('layouts.app')

@section('title', 'Reject OD Request')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Reject OD Request</div>
            <div class="eob-breadcrumb">HRMS > OD Requests > Reject</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('od-requests.show', $odRequest) }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body" style="max-width: 600px; margin: 0 auto; width: 100%;">
        <div class="eob-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Confirm Rejection</div>
                    <div class="eob-card-sub">Please specify the reason for rejecting this OD request.</div>
                </div>
            </div>

            <form method="POST" action="{{ route('od-requests.reject', [$odRequest, $approval]) }}">
                @csrf
                @method('PATCH')
                <div class="eob-card-body">
                    <div class="eob-group">
                        <label class="eob-label">Employee</label>
                        <input type="text" class="eob-input" readonly value="{{ $odRequest->employee?->name ?: $odRequest->user?->name }}" style="background: #f9fafb;">
                    </div>
                    <div class="eob-group" style="margin-top: 12px;">
                        <label class="eob-label">OD Dates</label>
                        <input type="text" class="eob-input" readonly value="{{ $odRequest->from_date->format('d M Y') }} to {{ $odRequest->to_date->format('d M Y') }} ({{ $odRequest->total_days }} day(s))" style="background: #f9fafb;">
                    </div>
                    <div class="eob-group" style="margin-top: 12px;">
                        <label class="eob-label">Rejection Reason <span class="eob-label-required">*</span></label>
                        <textarea name="remarks" required class="eob-textarea" rows="4" placeholder="Explain why this OD request is rejected..."></textarea>
                    </div>
                </div>
                <div class="eob-foot">
                    <a href="{{ route('od-requests.show', $odRequest) }}" class="eob-btn eob-btn-ghost">Cancel</a>
                    <button type="submit" class="eob-btn eob-btn-danger" style="background: #dc2626; color: #fff; border-color: #dc2626;">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
