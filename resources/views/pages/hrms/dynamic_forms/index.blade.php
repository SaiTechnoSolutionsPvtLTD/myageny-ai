@extends('layouts.app')

@section('title', 'Form Builder')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.dynamic_forms.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Form Builder</div>
            <div class="eob-breadcrumb">HRMS > Form Builder</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('dynamic-forms.create') }}" class="eob-btn eob-btn-primary">Create Form</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif

        <div class="eob-filter-card">
            <form method="GET" action="{{ route('dynamic-forms.index') }}" class="eob-filter-form">
                <div class="eob-field">
                    <label class="eob-label">Search</label>
                    <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Search by form name">
                </div>
                <div class="eob-actions">
                    <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('dynamic-forms.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="eob-table-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Forms</div>
                    <div class="eob-card-sub">Build shareable forms and review submitted responses in one place.</div>
                </div>
                <div class="eob-results">{{ $forms->total() }} form(s)</div>
            </div>

            @if($forms->isEmpty())
                <div class="eob-empty">No forms found.</div>
            @else
                <div style="overflow-x:auto;">
                    <table class="eob-list-table">
                        <thead>
                            <tr>
                                <th>Form</th>
                                <th>Status</th>
                                <th>Fields</th>
                                <th>Responses</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($forms as $form)
                                <tr>
                                    <td>
                                        <div class="eob-cell-title">{{ $form->title }}</div>
                                        <div class="eob-cell-sub">{{ $form->slug }}</div>
                                    </td>
                                    <td>
                                        <span class="eob-chip {{ $form->is_active ? 'eob-chip-verified' : 'eob-chip-rejected' }}">
                                            {{ $form->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $form->fields_count }}</td>
                                    <td>{{ $form->submissions_count }}</td>
                                    <td>{{ optional($form->created_at)->format('d M Y') }}</td>
                                    <td>
                                        <details class="eob-table-dropdown">
                                            <summary class="eob-table-dropdown-trigger">Actions</summary>
                                            <div class="eob-table-dropdown-menu">
                                                <a href="{{ route('dynamic-forms.show', $form) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                                <a href="{{ route('dynamic-forms.edit', $form) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($forms->hasPages())
                    @include('partials.table-pagination', ['paginator' => $forms])
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
