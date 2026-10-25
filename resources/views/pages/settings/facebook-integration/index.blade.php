@extends('layouts.app')
@section('title', 'Facebook Campaign Integration - Settings')

@push('styles')
@include('pages.settings.partials.table-styles')
<style>
.crm-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.crm-table {
    min-width: 800px;
}

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
                <form action="{{ route('settings.facebook-integration.sync-all') }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="crm-btn crm-btn-ghost" style="display:inline-flex; align-items:center; gap:6px; background:#fff7ed; color:#fe5f04; border-color:#fdba74;" onclick="return confirm('Are you sure you want to sync all migrated campaigns?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        Sync All
                    </button>
                </form>
                @can('facebook_integration.manage')
                <button type="button" class="crm-btn crm-btn-ghost open-update-token-modal" style="display:inline-flex; align-items:center; gap:6px; background:#f0fdf4; color:#16a34a; border-color:#bbf7d0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-1.5 1.5L10 13V17h4l7.5-7.5a2.121 2.121 0 0 0-3-3z"/><circle cx="7.5" cy="16.5" r="5.5"/></svg>
                    Update Access Token
                </button>
                @endcan
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
                                        @can('facebook_integration.manage')
                                        <button type="button" class="crm-table-dropdown-item updatecampaigntoken" data-camp_id="{{ $campaignMaster->id }}" data-camp_name="{{ $campaignMaster->campaign_name }}">Update Token</button>
                                        @endcan
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

    <!-- ====== UPDATE ACCESS TOKEN MODAL ====== -->
    <div class="crm-modal-overlay" id="updateTokenModal" style="display:none;">
        <div class="crm-modal" style="width:620px; max-width:95vw; max-height:90vh; overflow-y:auto;">
            <div class="crm-modal-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; background:#ecfdf5; color:#059669;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-1.5 1.5L10 13V17h4l7.5-7.5a2.121 2.121 0 0 0-3-3z"/><circle cx="7.5" cy="16.5" r="5.5"/></svg>
                    </span>
                    <div>
                        <h3 style="margin:0; font-size:17px; font-weight:700;">Update Facebook Access Token</h3>
                        <p style="margin:2px 0 0; font-size:12px; color:#6b7280;">Update Facebook Graph API access token for lead sync</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('updateTokenModal')">✕</button>
            </div>
            <form action="{{ route('settings.facebook-integration.update-token') }}" method="POST" id="updateTokenForm">
                @csrf
                <div class="crm-modal-body" style="padding:20px; display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <label class="crm-label" style="font-weight:600; margin-bottom:6px; display:block;">Apply Token To <span style="color:#ef4444;">*</span></label>
                        <select name="target_type" id="tokenTargetType" class="crm-select" required>
                            <option value="all">All Integrated Campaigns & Ad Accounts</option>
                            <option value="ad_account">Specific Ad Account</option>
                            <option value="campaign">Specific Campaign</option>
                        </select>
                    </div>

                    <div id="adAccountSelectGroup" style="display:none;">
                        <label class="crm-label" style="font-weight:600; margin-bottom:6px; display:block;">Select Ad Account <span style="color:#ef4444;">*</span></label>
                        <select name="ad_account_id" id="tokenAdAccountSelect" class="crm-select">
                            <option value="">-- Choose Ad Account --</option>
                            @foreach ($adAccounts ?? [] as $adAcc)
                                <option value="{{ $adAcc->act_id }}">{{ $adAcc->act_name }} ({{ $adAcc->act_id }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="campaignSelectGroup" style="display:none;">
                        <label class="crm-label" style="font-weight:600; margin-bottom:6px; display:block;">Select Campaign <span style="color:#ef4444;">*</span></label>
                        <select name="campaign_id" id="tokenCampaignSelect" class="crm-select">
                            <option value="">-- Choose Campaign --</option>
                            @foreach ($allCampaigns ?? [] as $camp)
                                <option value="{{ $camp->id }}">{{ $camp->campaign_name }} (#{{ $camp->id }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="crm-label" style="font-weight:600; margin-bottom:6px; display:block;">Facebook Access Token <span style="color:#ef4444;">*</span></label>
                        <textarea
                            name="access_token"
                            id="facebookAccessTokenInput"
                            rows="4"
                            class="crm-input"
                            style="width:100%; font-family:monospace; font-size:12px; resize:vertical; padding:10px 12px; border:1px solid #e1dee3; border-radius:8px;"
                            placeholder="EAA..."
                            required>{{ old('access_token', 'EAAW4M0czKR4BStmBTUDIPdMiCmJTJ4C0JiuvCHWZAJazjs2Lejof0942QLlzgobhtVGZCZB9ZCRUDKdR3dRPFBw4NoibdjlLZC9NpMFHv0b8WLsZBBhI08GZBZCZA9ALySVCQ29zUUl9ZACR2w4ZA37trr5ZC70PYcssX2666Faa0j16DuiyqMIli85jIRhTpcCO') }}</textarea>
                        <small style="display:block; margin-top:4px; color:#6b7280; font-size:11px;">
                            Paste your Graph API User or System User Access Token (starts with EAA...)
                        </small>
                    </div>
                </div>
                <div class="crm-modal-footer" style="padding:14px 20px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:10px; background:#f9fafb; border-radius:0 0 12px 12px;">
                    <button type="button" class="crm-btn crm-btn-ghost" onclick="closeModal('updateTokenModal')">Cancel</button>
                    <button type="submit" class="crm-btn crm-btn-primary" style="background:#059669; border-color:#059669; color:#fff;">Save Token</button>
                </div>
            </form>
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
