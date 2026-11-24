@extends('layouts.app')

@section('title', $form->title . ' - Dynamic Courier Form')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.couriers.partials.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">{{ $form->title }}</div>
            <div class="eob-breadcrumb">HRMS > Courier > Dynamic Courier Form</div>
        </div>
        <div class="eob-actions">
            @if($form->is_active)
                @can('courier.create')
                    <a href="{{ route('hrms.couriers.create') }}" class="eob-btn eob-btn-primary">+ Create Courier</a>
                @endcan
            @endif
            @can('courier.edit')
                <a href="{{ route('hrms.couriers.forms.edit', $form) }}" class="eob-btn eob-btn-ghost">Edit Form Fields</a>
            @endcan
            @can('courier.delete')
                <form method="POST" action="{{ route('hrms.couriers.forms.destroy', $form) }}" onsubmit="return confirm('Reset / delete this dynamic Courier form? Existing courier entries will remain preserved.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="eob-btn eob-btn-danger">Delete Form</button>
                </form>
            @endcan
            <a href="{{ route('hrms.couriers.index') }}" class="eob-btn eob-btn-ghost">Courier Entries</a>
        </div>
    </div>

    <div class="eob-body">
        @include('pages.hrms.couriers.partials.tabs')

        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('info'))
            <div class="eob-alert eob-alert-success">{{ session('info') }}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div class="cr-shell">
            <div class="eob-show-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Courier Form Configuration</div>
                        <div class="eob-card-sub">{{ $form->description ?: 'This single dynamic form is used for creating and managing Courier entries.' }}</div>
                    </div>
                    <span class="eob-chip {{ $form->is_active ? 'eob-chip-verified' : 'eob-chip-rejected' }}">
                        {{ $form->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="eob-card-body">
                    <div class="eob-show-grid">
                        <div class="eob-show-item">
                            <div class="eob-show-label">Total Fields</div>
                            <div class="eob-show-value">{{ $form->fields->count() }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Courier Entries Created</div>
                            <div class="eob-show-value">
                                <a href="{{ route('hrms.couriers.index') }}" style="color:#fe5f04; text-decoration:none;">
                                    {{ $form->couriers_count }} entry(s)
                                </a>
                            </div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Created By</div>
                            <div class="eob-show-value">{{ $form->creator?->name ?? 'System' }}</div>
                        </div>
                        <div class="eob-show-item">
                            <div class="eob-show-label">Last Updated</div>
                            <div class="eob-show-value">{{ optional($form->updated_at)->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="eob-show-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">Configured Fields ({{ $form->fields->count() }})</div>
                        <div class="eob-card-sub">These fields appear automatically when creating or editing a Courier entry.</div>
                    </div>
                    @can('courier.edit')
                        <a href="{{ route('hrms.couriers.forms.edit', $form) }}" class="eob-btn eob-btn-primary eob-btn-sm">
                            <i class="bi bi-pencil"></i> Edit Fields
                        </a>
                    @endcan
                </div>
                <div class="eob-card-body">
                    <div class="cr-grid">
                        @foreach($form->fields as $field)
                            <div class="cr-field-card">
                                <div class="cr-field-title-wrap" style="justify-content:space-between;">
                                    <span class="cr-field-title">{{ $field->sort_order }}. {{ $field->label }}</span>
                                    <span class="cr-type-pill">{{ \App\Models\CourierFormField::FIELD_TYPES[$field->field_type] ?? strtoupper($field->field_type) }}</span>
                                </div>
                                <div class="cr-help" style="margin-top:8px;">
                                    {{ $field->is_required ? 'Required' : 'Optional' }}
                                    • {{ $field->show_in_list ? 'Shown in Courier table' : 'Hidden from table summary' }}
                                </div>
                                @if($field->placeholder)
                                    <div class="cr-help" style="margin-top:6px;">Placeholder: {{ $field->placeholder }}</div>
                                @endif
                                @if($field->help_text)
                                    <div class="cr-help" style="margin-top:4px;">Help: {{ $field->help_text }}</div>
                                @endif
                                @if(!empty($field->options))
                                    <div class="cr-help" style="margin-top:6px;">Options: <strong>{{ implode(', ', $field->options) }}</strong></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
