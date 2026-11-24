@extends('layouts.app')

@section('title', 'Courier - ' . $courier->courier_code)

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.couriers.partials.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Courier Details — {{ $courier->courier_code }}</div>
            <div class="eob-breadcrumb">HRMS > Courier > {{ $courier->courier_code }}</div>
        </div>
        <div class="eob-actions">
            @can('courier.edit')
                <a href="{{ route('hrms.couriers.edit', $courier) }}" class="eob-btn eob-btn-primary">Edit Courier</a>
            @endcan
            @can('courier.delete')
                <form method="POST" action="{{ route('hrms.couriers.destroy', $courier) }}" onsubmit="return confirm('Delete courier {{ $courier->courier_code }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="eob-btn eob-btn-danger">Delete</button>
                </form>
            @endcan
            <a href="{{ route('hrms.couriers.index') }}" class="eob-btn eob-btn-ghost">Back</a>
        </div>
    </div>

    <div class="eob-body">
        @include('pages.hrms.couriers.partials.tabs')

        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif

        <div class="eob-show-layout">
            <div class="eob-profile-sticky">
                <div class="eob-profile">
                    <div class="eob-profile-banner"></div>
                    <div class="eob-profile-body">
                        <div class="eob-avatar" style="background:#fe5f04; color:#fff;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="eob-profile-name">{{ $courier->courier_code }}</div>
                        <div class="eob-profile-mail">{{ $courier->form?->title ?? $courier->form_title }}</div>

                        <div class="eob-empid-card" style="margin-top:16px;">
                            <div class="eob-empid-label">Courier Reference</div>
                            <div class="eob-empid-value">{{ $courier->courier_code }}</div>
                            <div class="eob-empid-sub">Dynamic Form: {{ $courier->form?->title ?? $courier->form_title }}</div>
                        </div>

                        <div class="eob-side-list">
                            <div class="eob-side-item">
                                <div class="eob-side-label">Created By</div>
                                <div class="eob-side-value">{{ $courier->creator?->name ?? 'System' }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Created At</div>
                                <div class="eob-side-value">{{ optional($courier->created_at)->format('d M Y, h:i A') }}</div>
                            </div>
                            <div class="eob-side-item">
                                <div class="eob-side-label">Last Updated</div>
                                <div class="eob-side-value">{{ optional($courier->updated_at)->format('d M Y, h:i A') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="eob-section-stack">
                <div class="eob-show-card">
                    <div class="eob-card-head">
                        <div>
                            <div class="eob-card-title">Entered Courier Information</div>
                            <div class="eob-card-sub">All values submitted via {{ $courier->form?->title ?? $courier->form_title }}.</div>
                        </div>
                        <span class="cr-count-badge">{{ $courier->values->count() }} Field(s)</span>
                    </div>

                    <div class="eob-card-body">
                        @if($courier->values->isEmpty())
                            <div class="eob-empty">No field values recorded for this courier entry.</div>
                        @else
                            <div class="eob-show-grid">
                                @foreach($courier->values as $val)
                                    <div class="eob-show-item {{ $val->field_type === 'textarea' ? 'cr-full' : '' }}">
                                        <div class="eob-show-label">{{ $val->field_label }}</div>
                                        <div class="eob-show-value" style="margin-top:8px;">
                                            @if($val->field_type === 'file')
                                                @if($val->file_path)
                                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                                        <a href="{{ asset('storage/' . $val->file_path) }}" target="_blank" class="cr-file-pill">
                                                            <i class="bi bi-eye"></i> View {{ $val->file_name ?: 'File' }}
                                                        </a>
                                                        <a href="{{ route('hrms.couriers.values.download', [$courier, $val]) }}" class="cr-file-pill">
                                                            <i class="bi bi-download"></i> Download
                                                        </a>
                                                    </div>
                                                    @if($val->isImage())
                                                        <img src="{{ asset('storage/' . $val->file_path) }}" alt="{{ $val->file_name }}" class="cr-img-thumb">
                                                    @endif
                                                @else
                                                    <span class="eob-muted">No file uploaded</span>
                                                @endif
                                            @elseif($val->field_type === 'date' && $val->value)
                                                {{ \Carbon\Carbon::parse($val->value)->format('d M Y') }}
                                            @elseif($val->field_type === 'textarea' && $val->value)
                                                <div style="font-weight:500; line-height:1.6;">{!! nl2br(e($val->value)) !!}</div>
                                            @elseif($val->value !== null && $val->value !== '')
                                                {{ $val->value }}
                                            @else
                                                <span class="eob-muted">—</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
