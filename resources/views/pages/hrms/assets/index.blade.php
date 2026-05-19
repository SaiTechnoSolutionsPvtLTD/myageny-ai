@extends('layouts.app')

@section('title', 'Asset Entries')

@push('styles')
    @include('pages.hrms.employee_onboarding.styles')
@endpush

@section('content')
<div class="eob-page">
    <div class="eob-topbar">
        <div>
            <div class="eob-title">Asset Entries</div>
            <div class="eob-breadcrumb">HRMS > Assets</div>
        </div>
        <div class="eob-actions">
            <a href="{{ route('hrms.dashboard') }}" class="eob-btn eob-btn-ghost">Back</a>
            <a href="{{ route('assets.create') }}" class="eob-btn eob-btn-primary">Add Asset</a>
        </div>
    </div>

    <div class="eob-body">
        @if(session('success'))
            <div class="eob-alert eob-alert-success">{!! session('success') !!}</div>
        @endif
        @if(session('error'))
            <div class="eob-alert eob-alert-error">{{ session('error') }}</div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;">
            <div class="eob-show-item"><div class="eob-show-label">Total Assets</div><div class="eob-show-value">{{ $stats['total'] }}</div></div>
            <div class="eob-show-item"><div class="eob-show-label">Assigned</div><div class="eob-show-value">{{ $stats['assigned'] }}</div></div>
            <div class="eob-show-item"><div class="eob-show-label">Available</div><div class="eob-show-value">{{ $stats['available'] }}</div></div>
            <div class="eob-show-item"><div class="eob-show-label">In Service</div><div class="eob-show-value">{{ $stats['in_service'] }}</div></div>
        </div>

        <div class="eob-filter-card">
            <form method="GET" action="{{ route('assets.index') }}" class="eob-filter-form">
                <div class="eob-field">
                    <label class="eob-label">Search</label>
                    <input type="text" name="search" class="eob-input" value="{{ request('search') }}" placeholder="Asset code, name, serial, vendor, location">
                </div>
                <div class="eob-field" style="max-width:220px;">
                    <label class="eob-label">Category</label>
                    <select name="asset_category" class="eob-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(request('asset_category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-field" style="max-width:220px;">
                    <label class="eob-label">Status</label>
                    <select name="asset_status" class="eob-select">
                        <option value="">All Status</option>
                        @foreach(['available' => 'Available', 'assigned' => 'Assigned', 'in_service' => 'In Service', 'damaged' => 'Damaged', 'retired' => 'Retired'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('asset_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-field" style="max-width:280px;">
                    <label class="eob-label">Assigned Employee</label>
                    <select name="assigned_employee_id" class="eob-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) request('assigned_employee_id') === (string) $employee->id)>{{ $employee->employee_id }} - {{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="eob-actions">
                    <button type="submit" class="eob-btn eob-btn-primary">Filter</button>
                    @if(request()->hasAny(['search', 'asset_category', 'asset_status', 'assigned_employee_id']))
                        <a href="{{ route('assets.index') }}" class="eob-btn eob-btn-ghost">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="eob-table-card">
            <div class="eob-card-head">
                <div>
                    <div class="eob-card-title">Asset Registry</div>
                    <div class="eob-card-sub">Manage organization assets, ownership, and availability from one HRMS screen.</div>
                </div>
                <div class="eob-results">{{ $assets->total() }} asset(s)</div>
            </div>

            @if($assets->isEmpty())
                <div class="eob-empty">No asset entries found.</div>
            @else
                <div style="overflow-x:auto;">
                    <table class="eob-list-table">
                        <thead>
                            <tr>
                                <th>Asset</th>
                                <th>Category / Brand</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Purchase</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assets as $asset)
                                <tr>
                                    <td>
                                        <div class="eob-cell-title">{{ $asset->asset_name }}</div>
                                        <div class="eob-cell-sub">{{ $asset->asset_code }}{{ $asset->serial_number ? ' • ' . $asset->serial_number : '' }}</div>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $asset->asset_category ?: 'No category' }}</div>
                                        <div class="eob-cell-sub">{{ $asset->brand ?: 'No brand' }}{{ $asset->model_name ? ' • ' . $asset->model_name : '' }}</div>
                                    </td>
                                    <td>
                                        <span class="eob-chip eob-chip-{{ $asset->asset_status === 'available' ? 'verified' : ($asset->asset_status === 'damaged' ? 'rejected' : 'pending') }}">
                                            {{ ucwords(str_replace('_', ' ', $asset->asset_status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ $asset->assignedEmployee?->name ?: 'Not assigned' }}</div>
                                        <div class="eob-cell-sub">{{ $asset->assignedEmployee?->employee_id ?: '—' }}</div>
                                    </td>
                                    <td>
                                        <div class="eob-cell-title">{{ optional($asset->purchase_date)->format('d M Y') ?: 'N/A' }}</div>
                                        <div class="eob-cell-sub">{{ $asset->purchase_cost !== null ? number_format((float) $asset->purchase_cost, 2) : 'Cost not added' }}</div>
                                    </td>
                                    <td>{{ $asset->location ?: 'N/A' }}</td>
                                    <td>
                                        <details class="eob-table-dropdown">
                                            <summary class="eob-table-dropdown-trigger">Actions</summary>
                                            <div class="eob-table-dropdown-menu">
                                                <a href="{{ route('assets.show', $asset) }}" class="eob-table-dropdown-item"><i class="bi bi-eye"></i> View</a>
                                                <a href="{{ route('assets.edit', $asset) }}" class="eob-table-dropdown-item"><i class="bi bi-pencil"></i> Edit</a>
                                                <button
                                                    type="button"
                                                    class="eob-table-dropdown-item danger"
                                                    data-delete-trigger
                                                    data-name="{{ $asset->asset_name }}"
                                                    data-action="{{ route('assets.destroy', $asset) }}"
                                                ><i class="bi bi-trash"></i> Delete</button>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($assets->hasPages())
                    @include('partials.table-pagination', ['paginator' => $assets])
                @endif
            @endif
        </div>
    </div>
</div>

<div class="eob-modal" id="deleteModal">
    <div class="eob-modal-card">
        <div class="eob-modal-head">
            <div class="eob-modal-title">Delete asset entry?</div>
            <div class="eob-modal-copy">This will remove the asset record for <strong id="deleteAssetName">this asset</strong>.</div>
        </div>
        <div class="eob-modal-foot">
            <button type="button" class="eob-btn eob-btn-ghost" id="deleteModalCancel">Cancel</button>
            <form method="POST" id="deleteModalForm">
                @csrf
                @method('DELETE')
                <button type="submit" class="eob-btn eob-btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteModal');
    const modalForm = document.getElementById('deleteModalForm');
    const modalName = document.getElementById('deleteAssetName');
    const cancelButton = document.getElementById('deleteModalCancel');

    document.querySelectorAll('[data-delete-trigger]').forEach(function (button) {
        button.addEventListener('click', function () {
            modalForm.setAttribute('action', button.getAttribute('data-action'));
            modalName.textContent = button.getAttribute('data-name');
            modal.classList.add('is-open');
        });
    });

    cancelButton.addEventListener('click', function () {
        modal.classList.remove('is-open');
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.classList.remove('is-open');
        }
    });
});
</script>
@endpush
