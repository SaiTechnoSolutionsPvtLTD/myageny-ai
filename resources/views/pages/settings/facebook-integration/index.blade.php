@extends('layouts.app')
@section('title', 'Facebook Campaign Integration - Settings')

@push('styles')
@include('pages.settings.partials.table-styles')
<style>
.crm-filter-card {
    background: #fff;
    border: 1px solid #e1dee3;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 18px;
}

.crm-filter-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    align-items: end;
}

.crm-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    background: #fff;
}

.crm-select:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.1);
}

.crm-filter-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.crm-user-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.crm-user-empty {
    color: #9e9e9e;
    font-size: 13px;
}

.crm-th-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: inherit;
    text-decoration: none;
}

.crm-th-link:hover {
    color: #fe5f04;
}

.crm-th-sort {
    font-size: 11px;
    line-height: 1;
    color: #b0b5bf;
}

.crm-th-sort.is-active {
    color: #fe5f04;
}

@media (max-width: 1100px) {
    .crm-filter-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .crm-page-header {
        flex-direction: column;
        gap: 14px;
    }

    .crm-filter-form {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')
<main class="main-content">
    @php($sortIcon = fn (string $column) => $sortBy === $column ? ($sortDir === 'asc' ? '↑' : '↓') : '↕')
    <div class="crm-page-body">
        <div class="crm-page-header">
            <div>
                <h2 class="crm-title">Facebook Campaign Integration</h2>
                <p class="crm-subtitle">Track where your leads originate from</p>
            </div>
            <div class="crm-header-actions">
                <a href="{{ route('settings.index') }}" class="crm-btn crm-btn-ghost">Back</a>
                <a href="/settings/auth/facebook" target="_blank" class="crm-btn crm-btn-primary">+ Login Facebook</a>
            </div>
        </div>

        @include('pages.settings.partials.alert')

        <div class="crm-filter-card">
            <form method="GET" action="{{ route('settings.facebook-integration') }}" class="crm-filter-form">
                <div>
                    <label class="crm-label" for="campaign_name">Campaign Name</label>
                    <input
                        id="campaign_name"
                        type="text"
                        name="campaign_name"
                        class="crm-input"
                        value="{{ request('campaign_name') }}"
                        placeholder="Search campaign name">
                </div>

                <div>
                    <label class="crm-label" for="campaign_id">Campaign ID</label>
                    <input
                        id="campaign_id"
                        type="text"
                        name="campaign_id"
                        class="crm-input"
                        value="{{ request('campaign_id') }}"
                        placeholder="Search campaign ID">
                </div>

                <div>
                    <label class="crm-label" for="assigned_user">Assigned User</label>
                    <select id="assigned_user" name="assigned_user" class="crm-select">
                        <option value="">All Assigned Users</option>
                        @foreach ($activeUsers as $user)
                            <option value="{{ $user->id }}" @selected((string) request('assigned_user') === (string) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="crm-filter-actions">
                    <button type="submit" class="crm-btn crm-btn-primary">Apply Filter</button>
                    @if (request()->filled('campaign_name') || request()->filled('campaign_id') || request()->filled('assigned_user'))
                        <a href="{{ route('settings.facebook-integration') }}" class="crm-btn crm-btn-ghost">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead style="text-transform: uppercase">
                    <tr>
                        <th>
                            <a href="{{ route('settings.facebook-integration', array_merge(request()->query(), ['sort_by' => 'id', 'sort_dir' => $sortBy === 'id' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="crm-th-link">
                                #
                                <span class="crm-th-sort {{ $sortBy === 'id' ? 'is-active' : '' }}">{{ $sortIcon('id') }}</span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('settings.facebook-integration', array_merge(request()->query(), ['sort_by' => 'campaign_name', 'sort_dir' => $sortBy === 'campaign_name' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="crm-th-link">
                                Campaign Name
                                <span class="crm-th-sort {{ $sortBy === 'campaign_name' ? 'is-active' : '' }}">{{ $sortIcon('campaign_name') }}</span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('settings.facebook-integration', array_merge(request()->query(), ['sort_by' => 'campaign_id', 'sort_dir' => $sortBy === 'campaign_id' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="crm-th-link">
                                Campaign ID
                                <span class="crm-th-sort {{ $sortBy === 'campaign_id' ? 'is-active' : '' }}">{{ $sortIcon('campaign_id') }}</span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('settings.facebook-integration', array_merge(request()->query(), ['sort_by' => 'assigned_users', 'sort_dir' => $sortBy === 'assigned_users' && $sortDir === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}" class="crm-th-link">
                                Assigned Users
                                <span class="crm-th-sort {{ $sortBy === 'assigned_users' ? 'is-active' : '' }}">{{ $sortIcon('assigned_users') }}</span>
                            </a>
                        </th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaignMasters as $i => $campaignMaster)
                        <tr>
                            <td>{{ $campaignMasters->firstItem() + $i }}</td>
                            <td>
                                <span class="crm-badge crm-badge-blue">{{ $campaignMaster->campaign_name }}</span>
                            </td>
                            <td>{{ $campaignMaster->camp_id ?: $campaignMaster->ad_id ?: '-' }}</td>
                            <td>
                                @if ($campaignMaster->assignedUsers->isNotEmpty())
                                    <div class="crm-user-list">
                                        @foreach ($campaignMaster->assignedUsers as $assignedUser)
                                            <span class="crm-badge crm-badge-purple">{{ $assignedUser->user_name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="crm-user-empty">No users assigned</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <details class="crm-table-dropdown">
                                    <summary class="crm-table-dropdown-trigger">Actions</summary>
                                    <div class="crm-table-dropdown-menu">
                                        <form action="{{ route('settings.facebook-integration.sync', $campaignMaster) }}" method="POST" style="display:inline">
                                            @csrf
                                            <button type="submit" class="crm-table-dropdown-item">Sync Now</button>
                                        </form>
                                        <button type="button" class="crm-table-dropdown-item editintegratedcamp" data-camp_id="{{ $campaignMaster->id }}">Edit Mapping</button>
                                        <button type="button" class="crm-table-dropdown-item danger deleteintegratedcamp" data-camp_id="{{ $campaignMaster->id }}">Delete</button>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="crm-empty">No Facebook campaigns found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($campaignMasters->hasPages())
                @include('partials.table-pagination', ['paginator' => $campaignMasters])
            @endif
        </div>
    </div>

    <!-- ====== EDIT MAPPING MODAL ====== -->
    <div class="crm-modal-overlay" id="editMappingModal" style="display:none;">
        <div class="crm-modal" style="width:880px; max-width:95vw; max-height:90vh; overflow-y:auto;">
            <div class="crm-modal-header">
                <h3>Edit Campaign Integration</h3>
                <button type="button" onclick="closeModal('editMappingModal')">✕</button>
            </div>
            <div class="crm-modal-body" style="padding:16px 20px;">
                <div class="man"></div>
            </div>
        </div>
    </div>

</main>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>
@include('pages.settings.facebook-integration.facebook-script')
@include('pages.settings.partials.modal-scripts')
@endpush
@endsection
