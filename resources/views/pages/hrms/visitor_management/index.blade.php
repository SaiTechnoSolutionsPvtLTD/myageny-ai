@extends('layouts.app')

@section('title', 'Visitor Management')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
    <style>
        .vm-status { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; }
        .vm-status-checked_in { background:#fff7ed; color:#c2410c; }
        .vm-status-checked_out { background:#f0fdf4; color:#15803d; }
        .vm-type-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#eef2ff; color:#3730a3; margin-top:4px; }
        .vm-type-candidate { background:#f0fdf4; color:#166534; }
        .vm-type-client { background:#eff6ff; color:#1e40af; }
        .vm-type-others { background:#f3f4f6; color:#374151; }
    </style>
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Visitor Management</div>
            <div class="eob-breadcrumb">HRMS > Visitor Management</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('visitor-management.qr-code') }}" class="eob-btn eob-btn-ghost">QR Code</a>
            <a href="{{ route('visitor-management.create') }}" class="eob-btn eob-btn-primary">Add Visitor</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div class="eob-filter-card">
            <form method="GET" action="{{ route('visitor-management.index') }}" class="eob-filter-form">
                <div class="eob-field">
                    <label class="eob-label">Search</label>
                    <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Name, mobile, email, position, company, meet">
                </div>
                <div class="eob-field" style="max-width:180px;">
                    <label class="eob-label">Visitor Type</label>
                    <select name="visitor_type" class="eob-select">
                        <option value="">All Types</option>
                        <option value="candidate" @selected(request('visitor_type') === 'candidate')>Candidate</option>
                        <option value="client" @selected(request('visitor_type') === 'client')>Client</option>
                        <option value="others" @selected(request('visitor_type') === 'others')>Others</option>
                    </select>
                </div>
                <div class="eob-field" style="max-width:180px;">
                    <label class="eob-label">Date</label>
                    <input type="date" name="visit_date" class="eob-input" value="{{ request('visit_date') }}">
                </div>
                <div class="eob-field" style="max-width:180px;">
                    <label class="eob-label">Status</label>
                    <select name="status" class="eob-select">
                        <option value="">All Status</option>
                        <option value="checked_in" @selected(request('status') === 'checked_in')>Checked In</option>
                        <option value="checked_out" @selected(request('status') === 'checked_out')>Checked Out</option>
                    </select>
                </div>
                <div class="eob-actions">
                    <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                    @if(request()->hasAny(['search', 'visitor_type', 'visit_date', 'status']))
                        <a href="{{ route('visitor-management.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="eob-table-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Visitor Entries</div>
                    <div class="eob-card-sub">Track visitor entry, exit, type, and meeting details.</div>
                </div>
                <div class="eob-results">{{ $visitors->total() }} visitor(s)</div>
            </div>

            @if($visitors->isEmpty())
                <div class="eob-empty">No visitor entries found.</div>
            @else
                <div style="overflow-x:auto;">
                    <table class="eob-list-table">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Type</th>
                                <th>Mobile</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Whom To Meet</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visitors as $visitor)
                                <tr>
                                    <td>
                                        <div class="eob-cell-title">{{ $visitor->visitor_name }}</div>
                                        @if($visitor->visitor_type === 'candidate' && ($visitor->email || $visitor->applied_position))
                                            <div class="eob-cell-sub">{{ $visitor->applied_position ?: 'Candidate' }} {{ $visitor->email ? '('.$visitor->email.')' : '' }}</div>
                                        @elseif($visitor->visitor_type === 'client' && $visitor->company_name)
                                            <div class="eob-cell-sub">{{ $visitor->company_name }}</div>
                                        @else
                                            <div class="eob-cell-sub">#{{ $visitor->id }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="vm-type-badge vm-type-{{ $visitor->visitor_type ?: 'others' }}">{{ $visitor->visitor_type_label }}</span>
                                    </td>
                                    <td>{{ $visitor->mobile_number }}</td>
                                    <td>{{ $visitor->visit_date->format('d M Y') }}</td>
                                    <td>
                                        <div class="eob-cell-title">{{ \Carbon\Carbon::parse($visitor->in_time)->format('h:i A') }}</div>
                                        <div class="eob-cell-sub">Out: {{ $visitor->out_time ? \Carbon\Carbon::parse($visitor->out_time)->format('h:i A') : 'Pending' }}</div>
                                    </td>
                                    <td>{{ $visitor->person_to_meet }}</td>
                                    <td><span class="vm-status vm-status-{{ $visitor->status }}">{{ str_replace('_', ' ', $visitor->status) }}</span></td>
                                    <td>
                                        <details class="eob-table-dropdown">
                                            <summary class="eob-table-dropdown-trigger">Actions</summary>
                                            <div class="eob-table-dropdown-menu">
                                                <a href="{{ route('visitor-management.show', $visitor) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                                <a href="{{ route('visitor-management.edit', $visitor) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
                                                <form method="POST" action="{{ route('visitor-management.destroy', $visitor) }}" onsubmit="return confirm('Delete this visitor entry?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="eob-table-dropdown-item danger"><i class="bi bi-trash"></i> Delete</button>
                                                </form>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($visitors->hasPages())
                    @include('partials.table-pagination', ['paginator' => $visitors])
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
