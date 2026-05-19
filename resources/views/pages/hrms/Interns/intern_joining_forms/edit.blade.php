@extends('layouts.app')

@section('title', 'Edit Intern Joining Form')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.Interns.intern_joining_forms.styles')
@endpush

@section('content')
<div class="intern-page">
    <div class="intern-shell">
        <div class="eob-topbar">
            <div>
                <div class="eob-title">Edit Intern Joining Form</div>
                <div class="eob-breadcrumb">HRMS > Intern Joining Forms > Edit</div>
            </div>
            <div class="eob-actions">
                <a href="{{ route('interns.show', $form) }}" class="eob-btn eob-btn-primary">View</a>
                <a href="{{ route('interns.index') }}" class="eob-btn eob-btn-ghost">Back</a>
            </div>
        </div>
        <div class="intern-body">
            @if($errors->any())
                <div class="intern-alert intern-alert-danger">
                    <div class="fw-bold">Please review the highlighted fields before continuing.</div>
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('pages.hrms.Interns.intern_joining_forms.form', [
                'form' => $form,
                'action' => route('interns.update', $form),
                'method' => 'PUT',
                'submitLabel' => 'Update Intern Form',
                'cancelRoute' => route('interns.show', $form),
                'documentLabels' => $documentLabels,
            ])
        </div>
    </div>
</div>
@endsection
