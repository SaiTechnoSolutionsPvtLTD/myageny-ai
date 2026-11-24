@extends('layouts.app')

@section('title', 'Courier Management')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    @include('pages.hrms.couriers.partials.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Courier Management</div>
            <div class="eob-breadcrumb">HRMS > Courier</div>
        </div>
        <div class="eob-actions">
            @if($form)
                @can('courier.edit')
                    <a href="{{ route('hrms.couriers.forms.edit', $form) }}" class="eob-btn eob-btn-ghost">
                        <i class="bi bi-sliders"></i> Edit Courier Form
                    </a>
                @endcan
                @if($form->is_active)
                    @can('courier.create')
                        <a href="{{ route('hrms.couriers.create') }}" class="eob-btn eob-btn-primary">
                            + Create Courier
                        </a>
                    @endcan
                @endif
            @else
                @can('courier.create')
                    <a href="{{ route('hrms.couriers.forms.index') }}" class="eob-btn eob-btn-primary">
                        + Setup Courier Form
                    </a>
                @endcan
            @endif
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

        @if(! $form && $couriers->isEmpty())
            <div class="eob-card">
                <div class="eob-empty">
                    <div style="font-size:16px; font-weight:800; color:#121212; margin-bottom:6px;">
                        Dynamic Courier Form Not Created Yet
                    </div>
                    <div style="max-width:540px; margin:0 auto 16px; line-height:1.6;">
                        Create the Dynamic Courier Form once (with Textbox, Textarea, Date, Dropdown, File Upload, etc.), and then use it to create and manage all Courier entries.
                    </div>
                    @can('courier.create')
                        <a href="{{ route('hrms.couriers.forms.index') }}" class="eob-btn eob-btn-primary">
                            + Setup Dynamic Courier Form
                        </a>
                    @endcan
                </div>
            </div>
        @else
            <div class="cr-stats-grid">
                <div class="cr-stat-card">
                    <div class="cr-stat-label">Total Couriers</div>
                    <div class="cr-stat-value">{{ $stats['total_couriers'] }}</div>
                    <div class="cr-stat-sub">All recorded courier entries</div>
                </div>
                <div class="cr-stat-card">
                    <div class="cr-stat-label">Today's Entries</div>
                    <div class="cr-stat-value" style="color:#fe5f04;">{{ $stats['today_couriers'] }}</div>
                    <div class="cr-stat-sub">Created today ({{ now()->format('d M Y') }})</div>
                </div>
                <div class="cr-stat-card">
                    <div class="cr-stat-label">Form Fields</div>
                    <div class="cr-stat-value">{{ $stats['configured_fields'] }}</div>
                    <div class="cr-stat-sub">{{ $form ? $form->title : 'Form removed' }}</div>
                </div>
            </div>

            <div class="eob-filter-card">
                <form method="GET" action="{{ route('hrms.couriers.index') }}" class="eob-filter-form">
                    <div class="eob-field">
                        <label class="eob-label">Search</label>
                        <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Search by courier code or any entered detail">
                    </div>
                    <div class="eob-field" style="max-width:180px;">
                        <label class="eob-label">From Date</label>
                        <input type="date" name="date_from" class="eob-input" value="{{ request('date_from') }}">
                    </div>
                    <div class="eob-field" style="max-width:180px;">
                        <label class="eob-label">To Date</label>
                        <input type="date" name="date_to" class="eob-input" value="{{ request('date_to') }}">
                    </div>
                    <div class="eob-actions">
                        <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                        @if(request()->hasAny(['search', 'date_from', 'date_to']))
                            <a href="{{ route('hrms.couriers.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="eob-table-card">
                <div class="eob-card-head">
                    <div>
                        <div class="eob-card-title">
                            Courier Entries
                            @if($form)
                                <span style="font-size:13px; font-weight:600; color:#fe5f04;">— {{ $form->title }}</span>
                            @endif
                        </div>
                        <div class="eob-card-sub">View, edit, or delete entered courier details.</div>
                    </div>
                    <div class="eob-results">{{ $couriers->total() }} entry(s)</div>
                </div>

                @if($couriers->isEmpty())
                    <div class="eob-empty">
                        No courier entries found.
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="eob-list-table">
                            <thead>
                                <tr>
                                    <th>Courier Code</th>
                                    @if($form && $displayFields->isNotEmpty())
                                        @foreach($displayFields as $fieldCol)
                                            <th>{{ $fieldCol->label }}</th>
                                        @endforeach
                                    @else
                                        <th>Entered Details</th>
                                    @endif
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($couriers as $courier)
                                    @php
                                        $valuesByFieldId = $courier->values->whereNotNull('courier_form_field_id')->keyBy('courier_form_field_id');
                                        $valuesByKey = $courier->values->keyBy('field_key');
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('hrms.couriers.show', $courier) }}" class="eob-cell-title" style="color:#fe5f04; text-decoration:none;">
                                                {{ $courier->courier_code }}
                                            </a>
                                        </td>

                                        @if($form && $displayFields->isNotEmpty())
                                            @foreach($displayFields as $fieldCol)
                                                @php
                                                    $val = $valuesByFieldId->get($fieldCol->id) ?? $valuesByKey->get($fieldCol->field_key);
                                                @endphp
                                                <td>
                                                    @if($val && $val->field_type === 'file' && $val->file_path)
                                                        <a href="{{ asset('storage/' . $val->file_path) }}" target="_blank" class="cr-file-pill">
                                                            <i class="bi bi-paperclip"></i> {{ \Illuminate\Support\Str::limit($val->file_name ?: 'View File', 22) }}
                                                        </a>
                                                    @elseif($val && $val->field_type === 'date' && $val->value)
                                                        {{ \Carbon\Carbon::parse($val->value)->format('d M Y') }}
                                                    @elseif($val && $val->value !== null && $val->value !== '')
                                                        {{ \Illuminate\Support\Str::limit($val->value, 60) }}
                                                    @else
                                                        <span class="eob-muted">—</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        @else
                                            <td>
                                                <div class="cr-detail-pills">
                                                    @forelse($courier->values->take(5) as $val)
                                                        <div class="cr-detail-row">
                                                            <span class="cr-detail-key">{{ $val->field_label }}:</span>
                                                            <span class="cr-detail-val">
                                                                @if($val->field_type === 'file' && $val->file_path)
                                                                    <a href="{{ asset('storage/' . $val->file_path) }}" target="_blank" class="cr-file-pill">
                                                                        <i class="bi bi-paperclip"></i> {{ \Illuminate\Support\Str::limit($val->file_name ?: 'Open File', 24) }}
                                                                    </a>
                                                                @elseif($val->field_type === 'date' && $val->value)
                                                                    {{ \Carbon\Carbon::parse($val->value)->format('d M Y') }}
                                                                @else
                                                                    {{ $val->value !== null && $val->value !== '' ? \Illuminate\Support\Str::limit($val->value, 60) : '—' }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @empty
                                                        <span class="eob-muted">—</span>
                                                    @endforelse
                                                </div>
                                            </td>
                                        @endif

                                        <td>{{ $courier->creator?->name ?? 'System' }}</td>
                                        <td>{{ optional($courier->created_at)->format('d M Y, h:i A') }}</td>
                                        <td>
                                            <details class="eob-table-dropdown">
                                                <summary class="eob-table-dropdown-trigger">Actions</summary>
                                                <div class="eob-table-dropdown-menu">
                                                    <a href="{{ route('hrms.couriers.show', $courier) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                                    @can('courier.edit')
                                                        <a href="{{ route('hrms.couriers.edit', $courier) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
                                                    @endcan
                                                    @can('courier.delete')
                                                        <form method="POST" action="{{ route('hrms.couriers.destroy', $courier) }}" onsubmit="return confirm('Delete courier {{ $courier->courier_code }}?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="eob-table-dropdown-item danger"><i class="bi bi-trash"></i> Delete</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($couriers->hasPages())
                        @include('partials.table-pagination', ['paginator' => $couriers])
                    @endif
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
