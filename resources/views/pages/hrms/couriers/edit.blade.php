@extends('layouts.app')

@section('title', 'Edit Courier - ' . $courier->courier_code)

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.couriers.partials.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Edit Courier — {{ $courier->courier_code }}</div>
            <div class="eob-breadcrumb">HRMS > Courier > {{ $courier->courier_code }} > Edit</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.couriers.show', $courier) }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @include('pages.hrms.couriers.partials.tabs')

        @if($errors->any())
            <div class="eob-alert eob-alert-error">
                <strong>Please fix the following errors:</strong>
                <ul style="margin:6px 0 0; padding-left:18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('hrms.couriers.update', $courier) }}" enctype="multipart/form-data" class="eob-card">
            @csrf
            @method('PUT')

            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Update Courier Details</div>
                    <div class="eob-card-sub">Form: <strong>{{ $form?->title ?? $courier->form_title }}</strong></div>
                </div>
                <span class="cr-count-badge">{{ $courier->courier_code }}</span>
            </div>

            <div class="eob-card-body">
                <div class="eob-form-grid">
                    @if($form && $form->fields->isNotEmpty())
                        @foreach($form->fields as $field)
                            @include('pages.hrms.couriers.partials.dynamic-field-input', [
                                'field' => $field,
                                'existingValue' => $existingValuesByFieldId->get($field->id),
                                'courier' => $courier,
                            ])
                        @endforeach
                    @else
                        @foreach($courier->values as $val)
                            <div class="eob-group {{ $val->field_type === 'textarea' ? 'full' : '' }}">
                                <label class="eob-label">{{ $val->field_label }}</label>
                                @if($val->field_type === 'textarea')
                                    <textarea name="snapshot_values[{{ $val->id }}]" class="eob-textarea">{{ old("snapshot_values.{$val->id}", $val->value) }}</textarea>
                                @elseif($val->field_type === 'date')
                                    <input type="date" name="snapshot_values[{{ $val->id }}]" class="eob-input" value="{{ old("snapshot_values.{$val->id}", $val->value) }}">
                                @elseif($val->field_type === 'number')
                                    <input type="number" step="any" name="snapshot_values[{{ $val->id }}]" class="eob-input" value="{{ old("snapshot_values.{$val->id}", $val->value) }}">
                                @elseif($val->field_type === 'file')
                                    <input type="file" name="snapshot_values[{{ $val->id }}]" class="eob-input">
                                    @if($val->file_path)
                                        <div class="cr-file-preview-box">
                                            <span>{{ $val->file_name ?: 'Uploaded File' }}</span>
                                            <label class="cr-check-row" style="color:#be123c; font-size:12px;">
                                                <input type="checkbox" name="remove_snapshot_files[{{ $val->id }}]" value="1">
                                                <span>Remove file</span>
                                            </label>
                                        </div>
                                    @endif
                                @else
                                    <input type="text" name="snapshot_values[{{ $val->id }}]" class="eob-input" value="{{ old("snapshot_values.{$val->id}", $val->value) }}">
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="eob-foot">
                <a href="{{ route('hrms.couriers.show', $courier) }}" class="eob-btn eob-btn-ghost">Cancel</a>
                <button type="submit" class="eob-btn eob-btn-primary">Update Courier</button>
            </div>
        </form>
    </div>
</div>
@endsection
