@extends('layouts.app')

@section('title', 'Edit Form')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.dynamic_forms.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Edit Form</div>
            <div class="eob-breadcrumb">HRMS > Form Builder > Edit</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('dynamic-forms.show', $form) }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please review the form fields and try again.</div>
        @endif

        @include('pages.hrms.dynamic_forms.form', [
            'form' => $form,
            'action' => route('dynamic-forms.update', $form),
            'method' => 'PUT',
            'submitLabel' => 'Update Form',
            'cancelRoute' => route('dynamic-forms.show', $form),
        ])
    </div>
</div>
@endsection
