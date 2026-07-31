<style>
.fb-success-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.fb-success-head { padding:24px 24px 18px; border-bottom:1px solid #f1f1f1; display:flex; justify-content:space-between; gap:18px; align-items:flex-start; }
.fb-success-title { font-size:18px; font-weight:700; color:#121212; margin:0 0 6px; }
.fb-success-copy { font-size:13px; color:#8b8b8b; margin:0; line-height:1.6; max-width:680px; }
.fb-success-chip { display:inline-flex; align-items:center; padding:9px 12px; border-radius:999px; background:#ecfdf3; color:#166534; font-size:12px; font-weight:700; border:1px solid #bbf7d0; white-space:nowrap; }
.fb-success-body { padding:22px 24px 24px; }
.fb-success-banner { display:flex; align-items:flex-start; gap:14px; padding:18px; border-radius:14px; background:linear-gradient(180deg,#f0fdf4 0%,#ecfdf3 100%); border:1px solid #bbf7d0; margin-bottom:18px; }
.fb-success-icon { width:42px; height:42px; border-radius:12px; background:#16a34a; color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:700; flex-shrink:0; }
.fb-success-banner h4 { margin:0 0 4px; font-size:16px; font-weight:700; color:#166534; }
.fb-success-banner p { margin:0; font-size:13px; color:#166534; line-height:1.6; }
.fb-success-grid { display:grid; gap:16px; }
.fb-success-panel { border:1px solid #ece7ef; border-radius:14px; background:linear-gradient(180deg,#fff 0%,#fffcfa 100%); padding:18px; }
.fb-success-panel-title { font-size:15px; font-weight:700; color:#121212; margin:0 0 6px; line-height:1.5; }
.fb-success-panel-sub { font-size:12px; color:#8b8b8b; margin:0 0 14px; }
.fb-success-users { display:grid; gap:10px; }
.fb-success-user { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; padding:12px 14px; border:1px solid #ece7ef; border-radius:12px; background:#fff; }
.fb-success-user-main { min-width:0; }
.fb-success-user-name { font-size:14px; font-weight:700; color:#121212; margin:0 0 3px; }
.fb-success-user-meta { font-size:12px; color:#8b8b8b; margin:0; line-height:1.5; }
.fb-success-badge { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#eef4ff; color:#3355aa; border:1px solid #dbe6ff; font-size:11px; font-weight:700; white-space:nowrap; }
.fb-success-empty { padding:48px 20px; text-align:center; color:#8f8f8f; }
.fb-success-empty h3 { margin:0 0 8px; font-size:17px; color:#333; }
.fb-success-empty p { margin:0; font-size:13px; }
@media (max-width: 768px) {
    .fb-success-head, .fb-success-user { flex-direction:column; align-items:flex-start; }
    .fb-success-head, .fb-success-body { padding-left:18px; padding-right:18px; }
}
</style>

<div class="fb-success-card">
    <div class="fb-success-head">
        <div>
            <h3 class="fb-success-title">Facebook Integration Completed</h3>
            <p class="fb-success-copy">Your selected campaigns are now connected, and the assigned CRM users will receive Facebook leads according to the mapping you configured.</p>
        </div>
        <div class="fb-success-chip">Setup Complete</div>
    </div>

    <div class="fb-success-body">
        <div class="fb-success-banner">
            <div class="fb-success-icon">✓</div>
            <div>
                <h4>Users Assigned Successfully</h4>
                <p>The Facebook lead distribution setup has been saved successfully for the selected campaigns.</p>
            </div>
        </div>

        @if (!empty($cams))
            <div class="fb-success-grid">
                @foreach ($cams as $cam)
                    @php
                        $campaign = App\Models\CampaignMaster::where('id', $cam['campaignId'])->first();
                        $assignedUsers = App\Models\AssignedUser::where('campaign_id', $cam['campaignId'])->get();
                        $selectedProduct = $campaign?->product;
                    @endphp

                    <div class="fb-success-panel">
                        <h4 class="fb-success-panel-title">{{ $campaign?->campaign_name ?? 'Campaign' }}</h4>
                        <p class="fb-success-panel-sub">
                            Assigned users who will receive leads from this Facebook campaign.
                            @if ($selectedProduct)
                                <br>Default Product: {{ $selectedProduct->package_name ?: $selectedProduct->product_name }}
                            @endif
                        </p>

                        @if ($assignedUsers->count())
                            <div class="fb-success-users">
                                @foreach ($assignedUsers as $user)
                                    @php
                                        $assignedUser = App\Models\User::where('id', $user->user_id)->first();
                                    @endphp
                                    <div class="fb-success-user">
                                        <div class="fb-success-user-main">
                                            <h5 class="fb-success-user-name">{{ $assignedUser?->name ?? $user->user_name }}</h5>
                                            <p class="fb-success-user-meta">
                                                {{ $assignedUser?->mobilenum ?: 'Mobile not available' }}
                                                @if (!empty($assignedUser?->role))
                                                    <br>{{ $assignedUser->role }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="fb-success-badge">Assigned</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="fb-success-empty">
                                <h3>No Assigned Users</h3>
                                <p>No users are currently linked to this campaign.</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="fb-success-empty">
                <h3>No Campaign Data Found</h3>
                <p>We could not find the campaign assignment summary for this integration.</p>
            </div>
        @endif

        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px; padding-top:16px; border-top:1px solid #f1f1f1;">
            <button type="button" class="crm-btn crm-btn-secondary" onclick="closeModal('editMappingModal')">Close</button>
            <button type="button" class="crm-btn crm-btn-primary" onclick="window.location.reload()">Done & Refresh</button>
        </div>
    </div>
</div>
