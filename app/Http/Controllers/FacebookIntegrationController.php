<?php

namespace App\Http\Controllers;

use App\Models\AssignedUser;
use App\Models\CampaignFieldMigration;
use App\Models\CampaignFields;
use App\Models\CampaignMaster;
use App\Models\FbuserMaster;
use App\Models\Lead;
use App\Models\LeadFormField;
use App\Models\LeadProduct;
use App\Models\Product;
use App\Models\User;
use App\Services\FacebookLeadImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class FacebookIntegrationController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['id', 'campaign_name', 'campaign_id', 'assigned_users'];
        $sortBy = in_array($request->get('sort_by'), $allowedSorts, true) ? $request->get('sort_by') : 'id';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';

        $campaignMastersQuery = CampaignMaster::query()
            ->with(['assignedUsers' => function ($query) {
                $query->select('id', 'campaign_id', 'user_id', 'user_name')
                    ->orderBy('user_name');
            }])
            ->when($request->filled('campaign_name'), function ($query) use ($request) {
                $query->where('campaign_name', 'like', '%' . trim($request->campaign_name) . '%');
            })
            ->when($request->filled('campaign_id'), function ($query) use ($request) {
                $campaignId = trim($request->campaign_id);

                $query->where(function ($subQuery) use ($campaignId) {
                    $subQuery->where('camp_id', 'like', '%' . $campaignId . '%')
                        ->orWhere('ad_id', 'like', '%' . $campaignId . '%');
                });
            })
            ->when($request->filled('assigned_user'), function ($query) use ($request) {
                $query->whereHas('assignedUsers', function ($assignedQuery) use ($request) {
                    $assignedQuery->where('user_id', $request->assigned_user);
                });
            });

        match ($sortBy) {
            'campaign_name' => $campaignMastersQuery
                ->orderByRaw('LOWER(campaign_name) ' . $sortDir)
                ->orderByDesc('id'),
            'campaign_id' => $campaignMastersQuery
                ->orderByRaw("LOWER(COALESCE(NULLIF(camp_id, ''), NULLIF(ad_id, ''), '')) " . $sortDir)
                ->orderByDesc('id'),
            'assigned_users' => $campaignMastersQuery
                ->orderBy(
                    AssignedUser::query()
                        ->selectRaw('MIN(user_name)')
                        ->whereColumn('campaign_id', 'campaign_masters.id'),
                    $sortDir
                )
                ->orderByRaw('LOWER(campaign_name) asc'),
            default => $campaignMastersQuery->orderBy('id', $sortDir),
        };

        $campaignMasters = $campaignMastersQuery
            ->paginate(10)
            ->withQueryString();

        $activeUsers = User::where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.settings.facebook-integration.index', compact('campaignMasters', 'activeUsers', 'sortBy', 'sortDir'));
    }

    public function facebookIndex() {
        try {
            return response()->json( [
                'view' => view( 'pages.settings.facebook-integration.fb-integrate' )->with( 'success', 'page loaded successfully' )->render(),
            ] );
        } catch ( \Throwable $th ) {
            Log::error( $th );
            return redirect()->back()->with( 'error', 'something went wrong' );
        }
    }

    // STEP 1 FETCHING FIELD DATA USING API

    public function connectfb(Request $request, FacebookLeadImporter $importer) {
        try {
            $adid = $request->adid;
            $accesstoken = $request->accesstoken;

            $leads = $importer->fetchLeadsFromNodeId((string) $adid, (string) $accesstoken);

            $campaignName = 'Facebook Lead Form ' . $adid;

            try {
                $campaign = $this->graphGet("https://graph.facebook.com/v19.0/{$adid}", [
                    'fields' => 'name',
                    'access_token' => $accesstoken,
                ]);


                $campaignName = $campaign['name'] ?? $campaignName;
            } catch (\Throwable $e) {
                Log::warning('Unable to fetch Facebook lead form name.', [
                    'node_id' => $adid,
                    'message' => $e->getMessage(),
                ]);
            }

            // Insert campaign details into campaign master
            $existingCampaign = CampaignMaster::query()
                ->where('camp_id', $adid)
                ->orWhere('ad_id', $adid)
                ->first();

            if ( !$existingCampaign ) {
                $campaign_master = CampaignMaster::create( [
                    'ad_id' => data_get($leads->first(), 'ad_id') ?: $adid,
                    'camp_id' => $adid,
                    'access_token' => $accesstoken,
                    'campaign_name' => $campaignName,
                ] );
            } else {
                $campaign_master = $existingCampaign;
                $campaign_master->forceFill([
                    'camp_id' => $campaign_master->camp_id ?: $adid,
                    'access_token' => $accesstoken,
                    'campaign_name' => $campaign_master->campaign_name ?: $campaignName,
                ])->save();
            }

            $this->storeCampaignFieldsFromSubmissions($campaign_master->id, $leads);
            $camid = $campaign_master->id;

            return response()->json( [
                'view' => view('pages.settings.facebook-integration.fb-field-mapping', [
                    'campaignMappings' => $this->buildCampaignMappingViewData((array) $camid),
                    'crmFieldGroups' => $this->crmFieldGroups(),
                    'isEditMode' => false,
                ])->render(),
                'cam_id' => $campaign_master->id
            ] );
        } catch ( \Throwable $th ) {
            Log::error( $th );
            return redirect()->back()->with( 'error', 'Something went wrong' );
        }
    }

    // MULTIPLE CAMPAIGNS

    public function multipleCampaigns( Request $request ) {
        try {
            $adid = $request->adid;
            $accesstoken = $request->accesstoken;

            // First API request
            $url = "https://graph.facebook.com/v20.0/act_{$adid}?fields=ads{name}&access_token={$accesstoken}";
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            $response = curl_exec( $ch );
            curl_close( $ch );
            $campaigns = json_decode( $response, true );

            foreach ( $campaigns[ 'ads' ][ 'data' ] as $campaign ) {
                $campaign_id = $campaign[ 'id' ];
                $campaign_name = $campaign[ 'name' ];

                $existingCampaign = CampaignMaster::where( 'camp_id', $campaign_id )->first();

                // dd( $existingCampaign );

                if ( !$existingCampaign ) {
                    CampaignMaster::create( [
                        'ad_id' => $adid,
                        'camp_id'=>$campaign_id,
                        'access_token' => $accesstoken,
                        'campaign_name' => $campaign_name,
                    ] );
                } else {
                    $existingCampaign->update( [
                        'ad_id' => $adid,
                        'camp_id'=>$campaign_id,
                        'access_token' => $accesstoken,
                        'campaign_name' => $campaign_name,
                    ] );
                }
            }

            // Insert campaign details into campaign master

            return response()->json( [
                'view' => view( 'pages.settings.facebook-integration.multiple-campaigns', compact( 'adid' ) )->render(),
                'ad_id' => $adid,
            ] );

        } catch ( \Throwable $th ) {
            Log::error( $th );
            return redirect()->back()->with( 'error', 'Something went wrong' );
        }
    }

    public function chooseCampaigns(Request $request, FacebookLeadImporter $importer) {
        $selected_campaigns = $request->selectedCampaigns;

        // dd( $selected_campaigns );

        foreach ( $selected_campaigns as $camps ) {
            $campaign = CampaignMaster::where( 'id', $camps )->first();

            $campaign->update([
                'is_integrated'=>1
            ]);

            $leads = $importer->fetchLeadSubmissions($campaign);
            $this->storeCampaignFieldsFromSubmissions($camps, $leads);
            $camid = $selected_campaigns;

            // dd( $camps );
        }

        return response()->json( [
            'view' => view('pages.settings.facebook-integration.fb-field-mapping', [
                'campaignMappings' => $this->buildCampaignMappingViewData((array) $camid),
                'crmFieldGroups' => $this->crmFieldGroups(),
                'isEditMode' => false,
            ])->render(),
            // 'cam_id' => $camps,
        ] );
    }

    public function mapfields( Request $request ) {
        try {
            $campaignMappings = $request->input('campaigns', []);
            $camIds = [];

            foreach ($campaignMappings as $campaignMapping) {
                $campaignId = data_get($campaignMapping, 'campaignId');
                $fieldMappings = data_get($campaignMapping, 'fieldMappings', []);

                if (!$campaignId || empty($fieldMappings)) {
                    continue;
                }

                $camIds[] = $campaignId;
                CampaignFieldMigration::where('campaign_id', $campaignId)->delete();

                foreach ($fieldMappings as $fm) {
                    $campaignField = CampaignFields::find($fm['campaignFieldId'] ?? null);
                    [$leadFieldId, $crmFieldName] = $this->resolveCrmFieldMapping($fm);

                    if (!$campaignField || !$crmFieldName) {
                        continue;
                    }

                    CampaignFieldMigration::updateOrCreate(
                        [
                            'campaign_id' => $campaignId,
                            'campaign_field_id' => $campaignField->id,
                        ],
                        [
                            'lead_field_id' => $leadFieldId,
                            'campaign_field_name' => $campaignField->field_name,
                            'crm_field_name' => $crmFieldName,
                        ]
                    );
                }

                $campaign = CampaignMaster::where('id', $campaignId)->first();
                if ($campaign) {
                    $campaign->new_fields = 0;
                    $campaign->save();
                }
            }

            return response()->json( [
                'view' => view( 'pages.settings.facebook-integration.lead-mapping', ['cams' => $camIds] )->render(),
            ] );
        } catch ( \Throwable $th ) {
            Log::error( $th );
            return redirect()->back()->with( 'error', 'Something went wrong' );
        }

    }

    public function fbassignleads( Request $request ) {
        try {
            $cams = $request->cams;

            foreach ( $cams as $cam ) {
                $campaignId = $cam[ 'campaignId' ];
                $campaignAssigned = $cam[ 'assigned' ];
                $productId = !empty($cam['productId']) ? (int) $cam['productId'] : null;

                $users = User::whereIn( 'id', $campaignAssigned )
                ->where(function ($query) {
                    $query->where('is_active', true)
                        ->orWhere('user_status', 'active');
                })
                ->get();

                $userCount = $users->count();

                if ( $userCount == 0 ) {
                    return response()->json( [ 'error' => 'No active selected users found' ], 404 );
                }

                $product = $productId ? Product::query()->find($productId) : null;

                if (! $product) {
                    return response()->json(['error' => 'Please select a valid product for each campaign.'], 422);
                }

                AssignedUser::where('campaign_id', $campaignId)->delete();

                foreach ( $users as $user ) {
                    AssignedUser::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'campaign_id' => $campaignId,
                        ],
                        [
                            'user_name' => $user->name,
                        ]
                    );
                }

                CampaignMaster::where('id', $campaignId)->update([
                    'product_id' => $product->id,
                ]);

                $this->syncExistingFacebookLeadProducts($campaignId, $product);
            }

            return response()->json( [
                'view' => view( 'pages.settings.facebook-integration.lead-maping-success', compact( 'users', 'cams' ) )->render(),
            ] );

        } catch ( \Exception $e ) {
            return response()->json( [ 'error' => 'Failed to assign lead: ' . $e->getMessage() ], 500 );
        }
    }

    protected function syncExistingFacebookLeadProducts(int $campaignId, Product $product): void
    {
        $campaign = CampaignMaster::query()->find($campaignId);

        if (! $campaign) {
            return;
        }

        $campaignIdentifiers = collect([$campaign->camp_id, $campaign->ad_id])
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($campaignIdentifiers->isEmpty()) {
            return;
        }

        Lead::query()
            ->whereIn('facebook_campaign_id', $campaignIdentifiers)
            ->get()
            ->each(function (Lead $lead) use ($product) {
                LeadProduct::firstOrCreate(
                    [
                        'lead_id'    => $lead->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'deal_name'      => $product->package_name ?: $product->product_name ?: 'Facebook Imported Product',
                        'product_name'   => $product->package_name ?: $product->product_name ?: 'Facebook Imported Product',
                        'description'    => $product->description,
                        'unit_price'     => (float) ($product->final_price ?? 0),
                        'quantity'       => 1,
                        'discount_percent' => 0,
                        'remarks'        => 'Created automatically from Facebook campaign product mapping.',
                        'product_status' => 'new',
                        'amount_paid'    => 0,
                        'lead_source_id' => $lead->lead_source_id,
                        'created_by'     => $lead->assigned_to ?: $lead->created_by,
                        'company_id'     => $lead->company_id,
                    ]
                );
            });
    }

    public function viewAssigned() {
        try {

            $active_campaigns = CampaignMaster::where( 'status', 1 )->where('is_integrated',1)->get();
            $assigned_users = AssignedUser::all();
            $users = User::where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            })->get();

            return response()->json( [
                'view' => view( 'pages.settings.facebook-integration.assigned-user-view', compact( 'active_campaigns', 'assigned_users', 'users' ) )->with( 'success', 'page loaded successfully' )->render(),
            ] );
        } catch ( \Throwable $th ) {
            Log::error( $th );
            return redirect()->back()->with( 'error', 'Something went wrong' );
        }
    }

    public function assignUsers( Request $request ) {
        try {
            $selected = $request->selectedData;

            foreach ( $selected as $select ) {
                $campaign_id = $select[ 'campaignId' ];
                $users = $select[ 'users' ];

                $original_users = User::whereIn( 'id', $users )->get();

                AssignedUser::where('campaign_id', $campaign_id)
                    ->whereNotIn('user_id', $users)
                    ->delete();

                foreach ( $original_users as $original_user ) {
                    AssignedUser::updateOrCreate(
                        [ 'campaign_id' => $campaign_id, 'user_id' => $original_user->id ],
                        [ 'user_name' => $original_user->name ]
                    );
                }
            }

            $active_campaigns = CampaignMaster::where( 'status', 1 )->where('is_integrated',1)->get();
            $assigned_users = AssignedUser::all();
            $users = User::where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            })->get();

            return response()->json( [
                'view' => view( 'pages.settings.facebook-integration.assigned-user-view', compact( 'active_campaigns', 'assigned_users', 'users' ) )
                ->with( 'success', 'Users assigned successfully' )
                ->render(),
            ] );
        } catch ( \Throwable $th ) {
            Log::error( 'Error in assigning users: ' . $th->getMessage(), [ 'error' => $th ] );

            return redirect()->back()->with( 'error', 'Something went wrong while assigning users.' );
        }
    }

      /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */

    public function socialiteRedirect() {

        return Socialite::driver( 'facebook' )->scopes(['email', 'ads_management', 'ads_read','public_profile','business_management','pages_show_list','read_insights','leads_retrieval'])->redirect();
        // return Socialite::driver('facebook')
        // ->scopes(['ads_read', 'ads_management', 'business_management', 'pages_show_list', 'read_insights'])
        // ->redirect();
    }

    public function socialiteCallback(Request $request) {

        try {

            $user = Socialite::driver( 'facebook' )->user();


            $fb_user = FbuserMaster::create( [
                'fbuser_id'=>$user->id,
                'name'=>$user->name,
                'phone'=>$user->phone,
            ] );

            $shortLivedToken = $user->token;

            $appId = env('FACEBOOK_CLIENT_ID');
            $appSecret = env('FACEBOOK_CLIENT_SECRET');


            $url = "https://graph.facebook.com/v17.0/oauth/access_token?grant_type=fb_exchange_token&client_id=$appId&client_secret=$appSecret&fb_exchange_token=$shortLivedToken";

            // Initialize a cURL session
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $responseData = json_decode($response, true);

            // Check for any error
            if (isset($responseData['error'])) {
                return response()->json(['error' => $responseData['error']['message']], 400);
            }

            $accessToken = $responseData['access_token'];
            // $accessToken = $shortLivedToken;


            // First API request
            $url = "https://graph.facebook.com/v17.0/me?fields=id,name,adaccounts{name}&access_token={$accessToken}";
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            $response = curl_exec( $ch );
            curl_close( $ch );
            $userData = json_decode( $response, true );

            if (isset($userData['error'])) {
                return redirect()->route('settings.fb_ac_error')->with('error', 'No ad account');
            }

            if($userData){

                $adaccounts = $userData['adaccounts'];

                $ads = $adaccounts['data'];

                foreach($ads as $ad){
                    $act_id = $ad['id'];
                    $act_name = $ad['name'];

                    $existingAdaccount = DB::table('ad_accounts')->where('act_id',$act_id)->first();


                    if ( !$existingAdaccount ) {
                        DB::table('ad_accounts')->insert([
                            'act_id' => $act_id,
                            'act_name'=>$act_name,
                            'token'=>$accessToken,
                        ]);
                    } else {
                        DB::table('ad_accounts')->where('act_id',$act_id)->update([
                            'act_id' => $act_id,
                            'act_name'=>$act_name,
                            'token'=>$accessToken,
                        ]);
                    }

                }

            }
                // dd($adaccounts['data']);

                $adid = $adaccounts['data'];
                $serializedAdId = urlencode(serialize($adid));

                return redirect()->route( 'settings.fb_multiple_accounts',['adid' => $serializedAdId])->with( 'success', 'Everything went as planned' );


        } catch ( \Exception $e ) {
            Log::error( 'Error logging user in facebook: ' . $e->getMessage(), [ 'error' => $e ] );

            // return redirect()->route( 'facebook_integration' )->with( 'error', 'Something went wrong while assigning users.' );
            return redirect()->back()->with('error','something went wrong');
        }
    }

       public function chooseadaccs(Request $request){
        $adaccounts = $request->selectedAdAccounts;

        foreach($adaccounts as $acc){

            $adaccount = DB::table('ad_accounts')->where('act_id',$acc)->first();
            $token = $adaccount->token;

            $url = "https://graph.facebook.com/v20.0/$acc/ads?fields=name&access_token={$token}&pretty=0&limit=500";

            // Exponential backoff parameters
            $retryCount = 0;
            $maxRetries = 5;
            $waitTime = 1; // Start with 1 second

            while ($retryCount < $maxRetries) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $response = curl_exec($ch);
                curl_close($ch);

                $campaigns = json_decode($response, true);

                // Check for rate limit error
                if (isset($campaigns['error']) && $campaigns['error']['code'] == 17) {
                    // Rate limit reached, wait and retry
                    sleep($waitTime);
                    $waitTime *= 2; // Double the wait time for exponential backoff
                    $retryCount++;
                } else {
                    // No rate limit error, continue processing
                    if (isset($campaigns['data'])) {
                        $camps = $campaigns['data'];

                        foreach ($camps as $campaign) {
                            $campaign_id = $campaign['id'];
                            $campaign_name = $campaign['name'];

                            $existingCampaign = CampaignMaster::where('camp_id', $campaign_id)->first();

                            if (!$existingCampaign) {
                                CampaignMaster::create([
                                    'ad_id' => $acc,
                                    'camp_id' => $campaign_id,
                                    'access_token' => $token,
                                    'campaign_name' => $campaign_name,
                                ]);
                            } else {
                                $existingCampaign->update([
                                    'ad_id' => $acc,
                                    'camp_id' => $campaign_id,
                                    'access_token' => $token,
                                    'campaign_name' => $campaign_name,
                                ]);
                            }
                        }
                    }
                    break; // Exit retry loop if successful
                }
            }
        }

        $adid = $adaccounts;

        return response()->json([
            'view' => view('pages.settings.facebook-integration.multiple-campaigns', compact('adid'))->render(),
            'ad_id' => $adid,
        ]);
    }


    public function deleteintegration( Request $request ) {
        try {
            $camp_id = $request->camp_id;

            $campaign = CampaignMaster::find($camp_id);

            if ($campaign) {
                CampaignFieldMigration::where('campaign_id', $camp_id)->delete();
                CampaignFields::where('campaign_id', $camp_id)->delete();
                AssignedUser::where('campaign_id', $camp_id)->delete();
                $campaign->delete();
            }

            return response()->json(['success' => 'Facebook integration deleted successfully.'], 200);

        } catch ( \Throwable $e ) {
            Log::error( 'Error deleting facebook integration: ' . $e->getMessage(), [ 'error' => $e ] );
            return response()->json(['error' => 'Failed to delete integration.'], 500);
        }
    }

    public function editfieldmaps(Request $request, FacebookLeadImporter $importer)
    {
        try {
            $camp_id = $request->camid;

            // Get the campaign master record
            $campaign_master = CampaignMaster::find($camp_id);

            if (!$campaign_master) {
                return response()->json(['error' => 'Campaign not found'], 404);
            }

            try {
                $leads = $importer->fetchLeadSubmissions($campaign_master);
                $this->storeCampaignFieldsFromSubmissions($camp_id, $leads);
            } catch (\Throwable $e) {
                Log::warning('Could not fetch new FB lead submissions during edit: ' . $e->getMessage());
            }

            return response()->json([
                'view' => view('pages.settings.facebook-integration.fb-edit-field-mapping', [
                    'campaignMappings' => $this->buildCampaignMappingViewData([$camp_id]),
                    'crmFieldGroups' => $this->crmFieldGroups(),
                    'isEditMode' => true,
                ])->render(),
                'cam_id' => $camp_id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error during Facebook integration update: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Something went wrong while updating the campaign fields.'], 500);
        }
    }


    public function fbMultipleCampaigns($adid){
        $adid = unserialize(urldecode($adid));
        return view('pages.settings.facebook-integration.fb-multiple-leads',compact('adid'))->with('success','Logged and fetched data successfully');
    }

    public function fbMultipleAdAccs($adid){
        $adid = unserialize(urldecode($adid));
        $ids = array_column($adid, 'id');
        $adAccs = DB::table('ad_accounts')->whereIn('act_id',$ids)->get();
        return view('pages.settings.facebook-integration.multiple-ad-accs',compact('adid','adAccs'))->with('success','Logged and fetched data successfully');
    }

    public function fberrorLogin(){
        return view('pages.settings.facebook-integration.fb-account-error')->with('error','ad account not found');
    }

    public function syncCampaign(CampaignMaster $campaignMaster, FacebookLeadImporter $importer)
    {
        try {
            $summary = $importer->importCampaign($campaignMaster);

            $message = "Facebook sync completed for {$campaignMaster->campaign_name}. Imported {$summary['created']} leads, updated {$summary['updated']} leads, skipped {$summary['skipped']}, failed {$summary['failed']}.";

            if (!empty($summary['failed']) && !empty($summary['errors'])) {
                $message .= ' Failed reason(s): ' . Str::limit(implode('; ', $summary['errors']), 500);
            }

            return redirect()
                ->route('settings.facebook-integration')
                ->with('success', $message);
        } catch (\Throwable $th) {
            Log::error('Error syncing Facebook campaign.', [
                'campaign_master_id' => $campaignMaster->id,
                'message' => $th->getMessage(),
            ]);

            return redirect()
                ->route('settings.facebook-integration')
                ->with('error', $this->facebookSyncFailureMessage($th));
        }
    }

    public function syncAllCampaigns(FacebookLeadImporter $importer)
    {
        try {
            $summary = $importer->importIntegratedCampaigns();

            if (($summary['campaigns'] ?? 0) === 0) {
                return redirect()
                    ->route('settings.facebook-integration')
                    ->with('success', 'No migrated campaigns found to sync.');
            }

            $message = "Facebook sync completed for all {$summary['campaigns']} migrated campaign(s). Imported {$summary['created']} leads, updated {$summary['updated']} leads, skipped {$summary['skipped']}, failed {$summary['failed']}.";

            if (!empty($summary['failed']) && !empty($summary['errors'])) {
                $message .= ' Failed reason(s): ' . Str::limit(implode('; ', $summary['errors']), 500);
            }

            return redirect()
                ->route('settings.facebook-integration')
                ->with('success', $message);
        } catch (\Throwable $th) {
            Log::error('Error syncing all Facebook campaigns.', [
                'message' => $th->getMessage(),
            ]);

            return redirect()
                ->route('settings.facebook-integration')
                ->with('error', $this->facebookSyncFailureMessage($th));
        }
    }

    protected function facebookSyncFailureMessage(\Throwable $th): string
    {
        $message = trim($th->getMessage());

        if ($message === '') {
            return 'Facebook sync failed. Please check the campaign access token and mapping.';
        }

        $message = preg_replace('/access_token=([^&\s]+)/i', 'access_token=[hidden]', $message);
        $message = preg_replace('/\bEAA[A-Za-z0-9_-]{20,}\b/', '[hidden-token]', $message);

        return 'Facebook sync failed: ' . Str::limit($message, 260);
    }

    protected function storeCampaignFieldsFromSubmissions(int $campaignId, iterable $submissions): void
    {
        $existingFields = DB::table('campaign_fields')
            ->where('campaign_id', $campaignId)
            ->pluck('field_name')
            ->map(fn ($fieldName) => (string) $fieldName)
            ->all();

        $newFields = collect($submissions)
            ->flatMap(function ($submission) {
                return collect(data_get($submission, 'field_data', []))
                    ->pluck('name')
                    ->filter();
            })
            ->map(fn ($fieldName) => (string) $fieldName)
            ->reject(fn ($fieldName) => in_array($fieldName, $existingFields, true))
            ->unique()
            ->values();

        if ($newFields->isEmpty()) {
            return;
        }

        DB::table('campaign_fields')->insert(
            $newFields
                ->map(fn ($fieldName) => [
                    'campaign_id' => $campaignId,
                    'field_name' => $fieldName,
                ])
                ->all()
        );
    }

    protected function graphGet(string $url, array $query = []): array
    {
        $response = Http::timeout(30)->acceptJson()->get($url, $query);

        if ($response->failed()) {
            throw new \RuntimeException('Facebook Graph API request failed with status ' . $response->status());
        }

        $payload = $response->json();

        if (isset($payload['error'])) {
            throw new \RuntimeException($payload['error']['message'] ?? 'Facebook Graph API returned an error.');
        }

        return $payload;
    }

    protected function crmFieldGroups(): array
    {
        $defaultFields = [
            ['key' => 'company_name', 'label' => 'Company Name'],
            ['key' => 'contact_name', 'label' => 'Contact Name'],
            ['key' => 'mobile_number', 'label' => 'Mobile Number'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'lead_date', 'label' => 'Lead Date'],
            ['key' => 'lead_source', 'label' => 'Lead Source'],
            ['key' => 'lead_status', 'label' => 'Lead Status'],
            ['key' => 'product_name', 'label' => 'Product Name'],
            ['key' => 'product_id', 'label' => 'Product ID'],
            ['key' => 'priority', 'label' => 'Priority'],
            ['key' => 'deal_value', 'label' => 'Deal Value'],
            ['key' => 'remarks', 'label' => 'Remarks'],
        ];

        $customFields = LeadFormField::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'label', 'field_name'])
            ->map(fn (LeadFormField $field) => [
                'key' => 'custom:' . $field->id,
                'label' => $field->label,
                'field_name' => $field->field_name,
            ])
            ->values()
            ->all();

        return [
            'default' => $defaultFields,
            'custom' => $customFields,
        ];
    }

    protected function buildCampaignMappingViewData(array $campaignIds): array
    {
        $campaignIds = collect($campaignIds)->filter()->unique()->values();

        if ($campaignIds->isEmpty()) {
            return [];
        }

        $campaigns = CampaignMaster::query()
            ->whereIn('id', $campaignIds)
            ->get()
            ->keyBy('id');

        $campaignFields = CampaignFields::query()
            ->whereIn('campaign_id', $campaignIds)
            ->orderBy('id')
            ->get()
            ->groupBy('campaign_id');

        $migrations = CampaignFieldMigration::query()
            ->whereIn('campaign_id', $campaignIds)
            ->get()
            ->groupBy('campaign_id');

        $leadFields = LeadFormField::query()
            ->whereIn('id', $migrations->flatten()->pluck('lead_field_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        return $campaignIds->map(function ($campaignId) use ($campaigns, $campaignFields, $migrations, $leadFields) {
            $campaign = $campaigns->get($campaignId);

            if (!$campaign) {
                return null;
            }

            $migrationByFieldId = $migrations->get($campaignId, collect())->keyBy('campaign_field_id');

            return [
                'campaign' => $campaign,
                'rows' => $campaignFields->get($campaignId, collect())->map(function (CampaignFields $field) use ($migrationByFieldId, $leadFields) {
                    $mapping = $migrationByFieldId->get($field->id);
                    $leadField = $mapping ? $leadFields->get($mapping->lead_field_id) : null;

                    return [
                        'campaign_field_id' => $field->id,
                        'campaign_field_name' => $field->field_name,
                        'crm_field_key' => $leadField
                            ? 'custom:' . $leadField->id
                            : ($this->crmFieldKeyFromName($mapping?->crm_field_name)
                                ?: $this->guessCrmFieldKey($field->field_name)),
                    ];
                })->values()->all(),
            ];
        })->filter()->values()->all();
    }

    protected function crmFieldKeyFromName(?string $crmFieldName): ?string
    {
        if (!$crmFieldName) {
            return null;
        }

        if (Str::startsWith($crmFieldName, 'custom:')) {
            return $crmFieldName;
        }

        $normalized = Str::of($crmFieldName)->trim()->lower()->replace([' ', '-'], '_')->value();
        $defaultKeys = collect($this->crmFieldGroups()['default'])->pluck('key');

        return $defaultKeys->contains($normalized) ? 'core:' . $normalized : null;
    }

    protected function resolveCrmFieldMapping(array $mapping): array
    {
        $crmFieldKey = (string) ($mapping['crmFieldKey'] ?? '');

        if ($crmFieldKey === '' && !empty($mapping['crmFieldId'])) {
            $crmFieldKey = 'custom:' . $mapping['crmFieldId'];
        }

        if (Str::startsWith($crmFieldKey, 'custom:')) {
            $fieldId = (int) Str::after($crmFieldKey, 'custom:');
            $crmField = LeadFormField::find($fieldId);

            if (!$crmField) {
                return [null, null];
            }

            return [$crmField->id, $this->customCrmFieldName($crmField)];
        }

        if (Str::startsWith($crmFieldKey, 'core:')) {
            $fieldName = Str::after($crmFieldKey, 'core:');

            return [null, $fieldName];
        }

        return [null, null];
    }

    protected function customCrmFieldName(LeadFormField $field): string
    {
        $fieldName = trim((string) $field->field_name);

        return $fieldName !== '' ? $fieldName : 'custom:' . $field->id;
    }

    protected function guessCrmFieldKey(?string $facebookFieldName): ?string
    {
        if (!$facebookFieldName) {
            return null;
        }

        $normalized = Str::of($facebookFieldName)->trim()->lower()->replace([' ', '-'], '_')->value();

        $coreField = match ($normalized) {
            'full_name', 'name', 'customer_name', 'client_name' => 'contact_name',
            'email', 'email_address' => 'email',
            'phone_number', 'phone', 'mobile', 'mobile_no', 'mobile_number' => 'mobile_number',
            'company', 'company_name', 'business_name' => 'company_name',
            'message', 'remarks', 'notes' => 'remarks',
            default => null,
        };

        return $coreField ? 'core:' . $coreField : null;
    }
}
