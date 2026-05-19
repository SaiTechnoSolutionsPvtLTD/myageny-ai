@extends('layouts.app')

@section('title', 'Edit Asset Entry - ' . $asset->asset_name)

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Edit Asset Entry</div>
            <div class="eob-breadcrumb">HRMS > Assets > {{ $asset->asset_name }} > Edit</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('assets.show', $asset) }}" class="eob-btn eob-btn-ghost">View</a>
            <a href="{{ route('assets.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @if($errors->any())
            <div class="eob-alert eob-alert-error">Please review the highlighted fields and try again.</div>
        @endif

        @include('pages.hrms.assets.form', [
            'asset' => $asset,
            'generatedAssetCode' => $generatedAssetCode,
            'assetCategories' => $assetCategories,
            'employees' => $employees,
            'statusOptions' => $statusOptions,
            'action' => route('assets.update', $asset),
            'method' => 'PUT',
            'submitLabel' => 'Save Changes',
            'cancelRoute' => route('assets.show', $asset),
        ])
    </div>
</div>
@endsection
