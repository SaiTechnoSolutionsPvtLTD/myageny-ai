@extends('layouts.app')
@section('title', 'Facility Management')

@push('styles')
@include('pages.settings.partials.table-styles')
@endpush

@section('content')
<main class="main-content">
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Facility Management</h2>
                <p class="crm-subtitle">Track facility entries with title, date, and time.</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('hrms.dashboard') }}" class="crm-btn crm-btn-ghost">Back</a>
                @can('settings.manage')
                    <a href="{{ route('settings.facility-titles.index') }}" class="crm-btn crm-btn-ghost">Title Master</a>
                @endcan
                <a href="{{ route('facility-management.qr-code') }}" class="crm-btn crm-btn-ghost">QR Code</a>
                <a href="{{ route('facility-management.create') }}" class="crm-btn crm-btn-primary">+ Add Activity</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div style="margin-bottom:16px; display:flex; gap:10px; flex-wrap:wrap;">
            <form method="GET" action="{{ route('facility-management.index') }}" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="search" class="crm-input" value="{{ request('search') }}" placeholder="Search facility title" style="max-width:360px;">
                <button type="submit" class="crm-btn crm-btn-primary">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('facility-management.index') }}" class="crm-btn crm-btn-ghost">Reset</a>
                @endif
            </form>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($facilityEntries as $entry)
                    @php
                        $entryDate = $entry->entry_date
                            ?? $entry->office_mopping_date
                            ?? $entry->office_cleaning_date
                            ?? $entry->toilet_cleaning_date
                            ?? $entry->created_at;
                        $entryTime = $entry->entry_time
                            ? \Carbon\Carbon::parse($entry->entry_time)->format('h:i A')
                            : optional($entry->created_at)->format('h:i A');
                    @endphp
                    <tr>
                        <td>{{ ($facilityEntries->firstItem() ?? 1) + $loop->index }}</td>
                        <td><strong>{{ $entry->facilityTitle?->name ?? $entry->title }}</strong></td>
                        <td>{{ $entryDate?->format('d M Y') ?? 'N/A' }}</td>
                        <td>{{ $entryTime ?? 'N/A' }}</td>
                        <td class="text-right">
                            <details class="crm-table-dropdown">
                                <summary class="crm-table-dropdown-trigger">Actions</summary>
                                <div class="crm-table-dropdown-menu">
                                    <a href="{{ route('facility-management.edit', $entry) }}" class="crm-table-dropdown-item">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-6"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        <span>Edit</span>
                                    </a>
                                    <form action="{{ route('facility-management.destroy', $entry) }}" method="POST" style="display:inline"
                                          onsubmit="return confirm('Delete this facility entry?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="crm-table-dropdown-item danger">
                                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="crm-empty">No facility management records found.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($facilityEntries->hasPages())
                @include('partials.table-pagination', ['paginator' => $facilityEntries])
            @endif
        </div>
    </div>
</main>
@endsection
