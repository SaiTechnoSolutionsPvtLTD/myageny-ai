@extends('layouts.app')

@section('title', 'Visitor Details')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .vm-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .vm-status-checked_in { background:#fff7ed; color:#c2410c; }
        .vm-status-checked_out { background:#f0fdf4; color:#15803d; }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Visitor Details</div>
            <div class="eob-breadcrumb">HRMS > Visitor Management > #{{ $visitor->id }}</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('visitor-management.edit', $visitor) }}" class="eob-btn eob-btn-primary">Edit</a>
            <a href="{{ route('visitor-management.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif

        <div class="eob-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">{{ $visitor->visitor_name }}</div>
                    <div class="eob-card-sub">{{ $visitor->mobile_number }} &bull; <span class="eob-badge eob-badge-blue">{{ $visitor->visitor_type_label }}</span></div>
                </div>
                <span class="vm-status vm-status-{{ $visitor->status }}">{{ str_replace('_', ' ', $visitor->status) }}</span>
            </div>
            <div class="eob-card-body">
                <div class="eob-show-grid">
                    <div class="eob-show-item">
                        <div class="eob-show-label">Visitor Type</div>
                        <div class="eob-show-value">{{ $visitor->visitor_type_label }}</div>
                    </div>
                    @if($visitor->visitor_type === 'candidate')
                        <div class="eob-show-item">
                            <div class="eob-show-label">Email ID</div>
                            <div class="eob-show-value">{{ $visitor->email ?: '-' }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Applied Position</div>
                            <div class="eob-show-value">{{ $visitor->applied_position ?: '-' }}</div>
                        </div>
                    @elseif($visitor->visitor_type === 'client')
                        <div class="eob-show-item">
                            <div class="eob-show-label">Company Name</div>
                            <div class="eob-show-value">{{ $visitor->company_name ?: '-' }}</div>
                        </div>
                    @endif
                    <div class="eob-show-item">
                        <div class="eob-show-label">Visit Date</div>
                        <div class="eob-show-value">{{ $visitor->visit_date->format('d M Y') }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">Whom To Meet</div>
                        <div class="eob-show-value">{{ $visitor->person_to_meet }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">In Time</div>
                        <div class="eob-show-value">{{ \Carbon\Carbon::parse($visitor->in_time)->format('h:i A') }}</div>
                    </div>
                    <div class="eob-show-item">
                        <div class="eob-show-label">Out Time</div>
                        <div class="eob-show-value">{{ $visitor->out_time ? \Carbon\Carbon::parse($visitor->out_time)->format('h:i A') : 'Pending' }}</div>
                    </div>
                    <div class="eob-show-item" style="grid-column:1 / -1;">
                        <div class="eob-show-label">Purpose of Meet</div>
                        <div class="eob-show-value">{{ $visitor->remarks ?: 'No remarks added.' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
