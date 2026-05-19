@extends('layouts.app')

@section('title', $form->title . ' Responses')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.dynamic_forms.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">{{ $form->title }} Responses</div>
            <div class="eob-breadcrumb">HRMS > Form Builder > Responses</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('dynamic-forms.export', ['dynamicForm' => $form] + request()->query()) }}" class="eob-btn eob-btn-primary">Export Filtered CSV</a>
            <a href="{{ route('dynamic-forms.show', $form) }}" class="eob-btn eob-btn-ghost">Back To Form</a>
        </div>
    </div>

    <div class="eob-body">
        <div class="eob-filter-card">
            <form method="GET" action="{{ route('dynamic-forms.responses', $form) }}" class="eob-filter-form">
                <div class="eob-field">
                    <label class="eob-label">Search</label>
                    <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Search response text or submission ID">
                </div>
                <div class="eob-field" style="max-width:180px;">
                    <label class="eob-label">Date From</label>
                    <input type="date" name="date_from" class="eob-input" value="{{ request('date_from') }}">
                </div>
                <div class="eob-field" style="max-width:180px;">
                    <label class="eob-label">Date To</label>
                    <input type="date" name="date_to" class="eob-input" value="{{ request('date_to') }}">
                </div>
                <div class="eob-field" style="max-width:220px;">
                    <label class="eob-label">Filter Field</label>
                    <select name="field_id" class="eob-select">
                        <option value="">All Fields</option>
                        @foreach($form->fields as $field)
                            <option value="{{ $field->id }}" @selected((string) request('field_id') === (string) $field->id)>{{ $field->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-field">
                    <label class="eob-label">Field Value</label>
                    <input type="text" name="field_value" class="eob-input" value="{{ request('field_value') }}" placeholder="Enter specific field value">
                </div>
                <div class="eob-actions">
                    <button type="submit" class="eob-btn eob-btn-primary">Apply Filters</button>
                    @if(request()->hasAny(['search', 'date_from', 'date_to', 'field_id', 'field_value']))
                        <a href="{{ route('dynamic-forms.responses', $form) }}" class="eob-btn eob-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="eob-table-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Collected Responses</div>
                    <div class="eob-card-sub">Review submissions in table format and filter by date, field, or response text.</div>
                </div>
                <div class="eob-results">{{ $submissions->total() }} response(s)</div>
            </div>

            @if($submissions->isEmpty())
                <div class="eob-empty">No responses matched the selected filters.</div>
            @else
                <div class="df-response-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Submission ID</th>
                                <th>Submitted At</th>
                                @foreach($form->fields as $field)
                                    <th>{{ $field->label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($responseRows as $row)
                                <tr>
                                    <td>{{ $row['submission_id'] }}</td>
                                    <td>{{ $row['submitted_at'] }}</td>
                                    @foreach($form->fields as $field)
                                        @php $value = $row['values'][$field->id] ?? ''; @endphp
                                        <td>
                                            @if($field->field_type === 'file' && $value)
                                                <a href="{{ $value }}" target="_blank">Open File</a>
                                            @else
                                                {{ $value ?: '—' }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($submissions->hasPages())
                    @include('partials.table-pagination', ['paginator' => $submissions])
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
