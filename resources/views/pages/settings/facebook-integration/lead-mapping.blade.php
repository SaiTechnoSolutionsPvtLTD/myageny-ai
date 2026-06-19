<style>
.fb-map-card { background:#fff; border:1px solid #e1dee3; border-radius:16px; overflow:hidden; }
.fb-map-head { padding:24px 24px 18px; border-bottom:1px solid #f1f1f1; display:flex; justify-content:space-between; gap:18px; align-items:flex-start; }
.fb-map-title { font-size:18px; font-weight:700; color:#121212; margin:0 0 6px; }
.fb-map-copy { font-size:13px; color:#8b8b8b; margin:0; line-height:1.6; max-width:680px; }
.fb-map-chip { display:inline-flex; align-items:center; padding:9px 12px; border-radius:999px; background:#fff6ef; color:#b45309; font-size:12px; font-weight:700; border:1px solid #ffe1c7; white-space:nowrap; }
.fb-map-body { padding:22px 24px 24px; }
.fb-map-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
.fb-map-panel { border:1px solid #ece7ef; border-radius:14px; background:linear-gradient(180deg,#fff 0%,#fffcfa 100%); padding:18px; }
.fb-map-panel-title { font-size:15px; font-weight:700; color:#121212; margin:0 0 6px; line-height:1.5; }
.fb-map-panel-sub { font-size:12px; color:#8b8b8b; margin:0 0 14px; }
.fb-map-actions { display:flex; justify-content:space-between; align-items:center; gap:14px; padding-top:18px; margin-top:18px; border-top:1px solid #f4f1f5; flex-wrap:wrap; }
.fb-map-note { font-size:12px; color:#9a9a9a; }
.fb-map-empty { padding:48px 20px; text-align:center; color:#8f8f8f; }
.fb-map-empty h3 { margin:0 0 8px; font-size:17px; color:#333; }
.fb-map-empty p { margin:0; font-size:13px; }
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
.chosen-container .chosen-drop {
    border:1px solid #e1dee3 !important;
    border-radius:12px !important;
    box-shadow:0 12px 24px rgba(18,18,18,.08) !important;
}
.chosen-container .chosen-results li.highlighted {
    background:#fe5f04 !important;
}
.fb-map-field {
    margin-top: 14px;
}
.fb-map-label {
    display: block;
    margin-bottom: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #555;
}
.fb-map-select {
    width: 100%;
    min-height: 46px;
    padding: 10px 12px;
    border: 1px solid #e1dee3;
    border-radius: 12px;
    background: #fff;
    font-size: 13px;
    color: #121212;
    outline: none;
}
.fb-map-select:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254,95,4,.10);
}
@media (max-width: 900px) {
    .fb-map-grid { grid-template-columns:1fr; }
}
@media (max-width: 768px) {
    .fb-map-head, .fb-map-actions { flex-direction:column; align-items:flex-start; }
    .fb-map-head, .fb-map-body { padding-left:18px; padding-right:18px; }
}
</style>

<div class="fb-map-card">
    <div class="fb-map-head">
        <div>
            <h3 class="fb-map-title">Assign Users to Campaign Leads</h3>
            <p class="fb-map-copy">Choose the active CRM users who should receive leads from each selected Facebook campaign. You can assign different users for different campaigns before finishing the integration.</p>
        </div>
        <div class="fb-map-chip">{{ isset($cams) ? count($cams) : 0 }} Campaigns Selected</div>
    </div>

    <div class="fb-map-body">
        <div class="fbleadmappings">
            @isset($cams)
                <form action="">
                    <div class="fb-map-grid">
                        @foreach ($cams as $cam)
                            @php
                                $campaign = App\Models\CampaignMaster::where('id', $cam)->first();
                                $users = App\Models\User::where('user_status', 'active')->get();
                                $products = App\Models\Product::query()
                                    ->where('status', 'active')
                                    ->orderByRaw('COALESCE(NULLIF(package_name, \'\'), product_name) asc')
                                    ->get();
                            @endphp
                            <div class="fb-map-panel">
                                <h4 class="fb-map-panel-title">{{ $campaign->campaign_name }}</h4>
                                <p class="fb-map-panel-sub">Select one or more active users to receive incoming Facebook leads for this campaign.</p>

                                <select class="chosen-select" multiple name="fbassignleads[]" id="fbassignleads">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>

                                <div class="fb-map-field">
                                    <label class="fb-map-label" for="fbproduct-{{ $cam }}">Default Product</label>
                                    <select class="fb-map-select fb-product-select" id="fbproduct-{{ $cam }}">
                                        <option value="">Select Product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((int) $campaign?->product_id === (int) $product->id)>
                                                {{ $product->package_name ?: $product->product_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <input type="hidden" value="{{ $cam }}" name="camid" class="leadmappingcamid">
                            </div>
                        @endforeach
                    </div>

                    <div class="fb-map-actions">
                        <div class="fb-map-note">Select at least one user for each campaign before continuing.</div>
                        <button type="button" class="leadmapsubmit crm-btn crm-btn-primary">Assign Users</button>
                    </div>
                </form>
            @else
                <div class="fb-map-empty">
                    <h3>Campaign Not Found</h3>
                    <p>We could not find the selected campaign details for lead assignment.</p>
                </div>
            @endisset
        </div>
    </div>
</div>

<script src="https://harvesthq.github.io/chosen/chosen.jquery.js"></script>
