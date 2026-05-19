@extends('layouts.app')

@section('title', 'Create Asset Entry')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Create Asset Entry</div>
            <div class="eob-breadcrumb">HRMS > Assets > Create</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('assets.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please review the highlighted fields and try again.</div>
        @endif

        @include('pages.hrms.assets.form', [
            'asset' => null,
            'generatedAssetCode' => $generatedAssetCode,
            'assetCategories' => $assetCategories,
            'employees' => $employees,
            'statusOptions' => $statusOptions,
            'action' => route('assets.store'),
            'method' => 'POST',
            'submitLabel' => 'Create Asset',
            'cancelRoute' => route('assets.index'),
        ])
    </div>
</div>
@endsection
