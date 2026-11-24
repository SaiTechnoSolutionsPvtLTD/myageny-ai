@extends('layouts.app')

@section('title', 'Create Dynamic Courier Form')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.couriers.partials.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Setup Dynamic Courier Form</div>
            <div class="eob-breadcrumb">HRMS > Courier > Dynamic Courier Form > Setup</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.couriers.index') }}" class="eob-btn eob-btn-ghost">Back to Couriers</a>
        </div>
    </div>

    <div class="eob-body">
        @include('pages.hrms.couriers.partials.tabs')

        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please review the form fields below and fix any errors.</div>
        @endif

        @include('pages.hrms.couriers.forms.form', [
            'form' => null,
            'action' => route('hrms.couriers.forms.store'),
            'method' => 'POST',
            'submitLabel' => 'Save Dynamic Courier Form',
            'cancelRoute' => route('hrms.couriers.index'),
        ])
    </div>
</div>
@endsection
