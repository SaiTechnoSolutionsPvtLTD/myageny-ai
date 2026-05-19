@extends('layouts.app')

@section('title', 'Create Form')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.dynamic_forms.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Create Form</div>
            <div class="eob-breadcrumb">HRMS > Form Builder > Create</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('dynamic-forms.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please review the form fields and try again.</div>
        @endif

        @include('pages.hrms.dynamic_forms.form', [
            'form' => null,
            'action' => route('dynamic-forms.store'),
            'method' => 'POST',
            'submitLabel' => 'Create Form',
            'cancelRoute' => route('dynamic-forms.index'),
        ])
    </div>
</div>
@endsection
