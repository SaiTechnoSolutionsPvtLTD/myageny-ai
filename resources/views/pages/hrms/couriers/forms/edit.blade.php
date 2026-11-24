@extends('layouts.app')

@section('title', 'Edit Dynamic Courier Form - ' . $form->title)

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.couriers.partials.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Edit Dynamic Courier Form</div>
            <div class="eob-breadcrumb">HRMS > Courier > Dynamic Courier Form > Edit</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.couriers.forms.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @include('pages.hrms.couriers.partials.tabs')

        @if(session('info'))
            <div class="eob-alert eob-alert-success">{{ session('info') }}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please review the form fields below and fix any errors.</div>
        @endif

        @include('pages.hrms.couriers.forms.form', [
            'form' => $form,
            'action' => route('hrms.couriers.forms.update', $form),
            'method' => 'PUT',
            'submitLabel' => 'Update Courier Form',
            'cancelRoute' => route('hrms.couriers.forms.index'),
        ])
    </div>
</div>
@endsection
