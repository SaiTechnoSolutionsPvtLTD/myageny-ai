<style>
.fb-assign-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.fb-assign-head { padding:24px 24px 18px; border-bottom:1px solid #f1f1f1; display:flex; justify-content:space-between; gap:18px; align-items:flex-start; }
.fb-assign-title { font-size:18px; font-weight:700; color:#121212; margin:0 0 6px; }
.fb-assign-copy { font-size:13px; color:#8b8b8b; margin:0; line-height:1.6; max-width:720px; }
.fb-assign-chip { display:inline-flex; align-items:center; padding:9px 12px; border-radius:999px; background:#fff6ef; color:#b45309; font-size:12px; font-weight:700; border:1px solid #ffe1c7; white-space:nowrap; }
.fb-assign-body { padding:22px 24px 24px; }
.fb-assign-layout { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1fr); gap:18px; }
.fb-assign-panel { border:1px solid #ece7ef; border-radius:14px; background:linear-gradient(180deg,#fff 0%,#fffcfa 100%); padding:18px; }
.fb-assign-panel-title { font-size:15px; font-weight:700; color:#121212; margin:0 0 6px; }
.fb-assign-panel-sub { font-size:12px; color:#8b8b8b; margin:0 0 14px; line-height:1.6; }
.fb-assign-campaigns { display:grid; gap:10px; }
.fb-assign-campaign { display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border:1px solid #ece7ef; border-radius:12px; background:#fff; }
.fb-assign-checkbox { width:18px; height:18px; margin-top:2px; accent-color:#fe5f04; }
.fb-assign-campaign-name { font-size:14px; font-weight:700; color:#121212; margin:0 0 3px; }
.fb-assign-campaign-meta { font-size:12px; color:#8b8b8b; margin:0; }
.fb-assign-dynamic { display:grid; gap:14px; margin-top:18px; }
.fb-assign-dynamic-item { border:1px solid #ece7ef; border-radius:12px; background:#fff; padding:14px; }
.fb-assign-dynamic-item h5 { margin:0 0 10px; font-size:14px; font-weight:700; color:#121212; }
.fb-assign-list { display:grid; gap:10px; }
.fb-assign-user { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; padding:12px 14px; border:1px solid #ece7ef; border-radius:12px; background:#fff; }
.fb-assign-user-name { font-size:14px; font-weight:700; color:#121212; margin:0 0 3px; }
.fb-assign-user-meta { font-size:12px; color:#8b8b8b; margin:0; }
.fb-assign-badge { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#eef4ff; color:#3355aa; border:1px solid #dbe6ff; font-size:11px; font-weight:700; white-space:nowrap; }
.fb-assign-actions { display:flex; justify-content:space-between; align-items:center; gap:14px; padding-top:18px; margin-top:18px; border-top:1px solid #f4f1f5; flex-wrap:wrap; }
.fb-assign-note { font-size:12px; color:#9a9a9a; }
.fb-assign-empty { padding:48px 20px; text-align:center; color:#8f8f8f; }
.fb-assign-empty h3 { margin:0 0 8px; font-size:17px; color:#333; }
.fb-assign-empty p { margin:0; font-size:13px; }
.chosen-container-multi .chosen-choices {
    border:1px solid #e1dee3 !important;
    border-radius:12px !important;
    background:#fff !important;
    min-height:46px !important;
    padding:6px 8px !important;
    box-shadow:none !important;
}
.chosen-container-active .chosen-choices {
    border-color:#fe5f04 !important;
    box-shadow:0 0 0 3px rgba(254,95,4,.10) !important;
}
.chosen-container-multi .chosen-choices li.search-choice {
    background:#eef4ff !important;
    border:1px solid #dbe6ff !important;
    color:#3355aa !important;
    border-radius:999px !important;
    padding:6px 24px 6px 10px !important;
    box-shadow:none !important;
}
@media (max-width: 900px) {
    .fb-assign-layout { grid-template-columns:1fr; }
}
@media (max-width: 768px) {
    .fb-assign-head, .fb-assign-actions, .fb-assign-user { flex-direction:column; align-items:flex-start; }
    .fb-assign-head, .fb-assign-body { padding-left:18px; padding-right:18px; }
}
</style>

<div class="fb-assign-card">
    <div class="fb-assign-head">
        <div>
            <h3 class="fb-assign-title">Manage Assigned Users</h3>
            <p class="fb-assign-copy">Choose integrated campaigns, assign active CRM users to each one, and review the current lead distribution setup in one place.</p>
        </div>
        <div class="fb-assign-chip">{{ $active_campaigns->count() }} Active Campaigns</div>
    </div>

    <div class="fb-assign-body">
        <div class="fb-assign-layout">
            <div class="fb-assign-panel">
                <h4 class="fb-assign-panel-title">Choose Campaigns to Reassign</h4>
                <p class="fb-assign-panel-sub">Select one or more campaigns, then choose the users who should receive leads for each selected campaign.</p>

                @if ($active_campaigns->count())
                    <form>
                        <div class="fb-assign-campaigns">
                            @foreach ($active_campaigns as $campaign)
                                <label class="fb-assign-campaign">
                                    <input class="campaign-checkbox fb-assign-checkbox" type="checkbox" value="{{ $campaign->id }}" id="flexCheckChecked{{ $campaign->id }}">
                                    <div>
                                        <h5 class="fb-assign-campaign-name">{{ $campaign->campaign_name }}</h5>
                                        <p class="fb-assign-campaign-meta">Campaign ID: {{ $campaign->camp_id }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div id="input-container" class="fb-assign-dynamic"></div>

                        <div class="fb-assign-actions">
                            <div class="fb-assign-note">Select campaigns and assign at least one user before saving.</div>
                            <button type="button" class="crm-btn crm-btn-primary" id="saveDataButton">Save Assignments</button>
                        </div>
                    </form>
                @else
                    <div class="fb-assign-empty">
                        <h3>No Active Campaigns</h3>
                        <p>There are no integrated campaigns available for user assignment right now.</p>
                    </div>
                @endif
            </div>

            <div class="fb-assign-panel">
                <h4 class="fb-assign-panel-title">Current User Assignments</h4>
                <p class="fb-assign-panel-sub">Review which users are currently assigned to each active Facebook campaign.</p>

                @if ($active_campaigns->count())
                    <div class="fb-assign-list">
                        @foreach ($active_campaigns as $campaign)
                            @php
                                $assignedUsers = App\Models\AssignedUser::where('campaign_id', $campaign->id)->get();
                            @endphp
                            <div class="fb-success-panel">
                                <h4 class="fb-success-panel-title">{{ $campaign->campaign_name }}</h4>
                                <p class="fb-success-panel-sub">Assigned users for this campaign.</p>

                                @if ($assignedUsers->count())
                                    <div class="fb-success-users">
                                        @foreach ($assignedUsers as $assigned)
                                            <div class="fb-assign-user">
                                                <div>
                                                    <h5 class="fb-assign-user-name">{{ $assigned->user_name }}</h5>
                                                    <p class="fb-assign-user-meta">
                                                        Campaign ID: {{ $campaign->camp_id }}
                                                        @if ($assigned->user?->branch)
                                                            • Branch: {{ $assigned->user->branch->name }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="fb-assign-badge">Assigned</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="fb-assign-empty">
                                        <h3>No Assigned Users</h3>
                                        <p>No users are currently assigned to this campaign.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="fb-assign-empty">
                        <h3>No Assignment Data</h3>
                        <p>There are no active campaigns with assignment information to display.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js"></script>
<script>
window.facebookAssignedUsers = @json($users);
</script>
