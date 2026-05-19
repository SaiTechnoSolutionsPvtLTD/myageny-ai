@extends('layouts.app')

@section('title', $form->title)

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.dynamic_forms.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">{{ $form->title }}</div>
            <div class="eob-breadcrumb">HRMS > Form Builder > View</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('dynamic-forms.edit', $form) }}" class="eob-btn eob-btn-primary">Edit</a>
            <a href="{{ route('dynamic-forms.responses', $form) }}" class="eob-btn eob-btn-primary">View Responses</a>
            <a href="{{ route('dynamic-forms.export', $form) }}" class="eob-btn eob-btn-ghost">Export CSV</a>
            <a href="{{ route('dynamic-forms.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif

        <div class="df-shell">
            <div class="eob-show-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Share Link</div>
                        <div class="eob-card-sub">Use this public link to collect responses from anyone.</div>
                    </div>
                    <span class="df-chip">{{ $form->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="eob-card-body">
                    <div class="df-link-box">{{ $shareUrl }}</div>
                    <div class="df-actions" style="margin-top:14px;">
                        <a href="{{ $shareUrl }}" target="_blank" class="eob-btn eob-btn-primary">Open Form</a>
                        <button type="button" class="eob-btn eob-btn-ghost" onclick="navigator.clipboard.writeText('{{ $shareUrl }}')">Copy Link</button>
                    </div>
                </div>
            </div>

            <div class="eob-show-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Field Schema</div>
                        <div class="eob-card-sub">{{ $form->fields->count() }} field(s) configured for this form.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="df-grid">
                        @foreach($form->fields as $field)
                            <div class="df-field-card">
                                <div class="df-field-title">{{ $field->label }}</div>
                                <div class="df-help" style="margin-top:6px;">{{ strtoupper($field->field_type) }}{{ $field->is_required ? ' • Required' : '' }}</div>
                                @if($field->placeholder)
                                    <div class="df-help" style="margin-top:8px;">Placeholder: {{ $field->placeholder }}</div>
                                @endif
                                @if($field->options)
                                    <div class="df-help" style="margin-top:8px;">Options: {{ implode(', ', $field->options) }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="eob-show-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Responses</div>
                        <div class="eob-card-sub">{{ $responseCount }} submission(s) collected. Open the dedicated responses page to filter and review the data.</div>
                    </div>
                </div>
                <div class="eob-card-body">
                    <div class="df-actions">
                        <a href="{{ route('dynamic-forms.responses', $form) }}" class="eob-btn eob-btn-primary">Open Responses Page</a>
                        <a href="{{ route('dynamic-forms.export', $form) }}" class="eob-btn eob-btn-ghost">Download CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
