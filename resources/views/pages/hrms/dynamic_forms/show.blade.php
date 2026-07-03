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

                    <!-- Dynamic QR Code Generator block -->
                    <div style="margin-top:28px; padding-top:20px; border-top:1px dashed #e1dee3; display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
                        <div style="padding:12px; border:1px solid #e1dee3; border-radius:14px; background:#fff; box-shadow: 0 4px 12px rgba(18,18,18,0.03);">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($shareUrl) }}" alt="Form QR Code" style="width:130px; height:130px; display:block;">
                        </div>
                        <div style="flex: 1; min-width: 200px;">
                            <div style="font-weight:800; font-size:15px; color:#121212;">Form QR Code</div>
                            <div style="font-size:12px; color:#7c7c7c; margin-top:4px; line-height: 1.5;">Scan this QR code with a mobile device to open and fill the form instantly. You can also print this QR code to collect offline feedback.</div>
                            <div style="margin-top:12px; display:flex; gap:10px;">
                                <a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data={{ urlencode($shareUrl) }}" target="_blank" download="qr-code-{{ $form->slug }}.png" class="eob-btn eob-btn-ghost" style="padding:8px 14px; font-size:12px;">Download QR Code</a>
                            </div>
                        </div>
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
