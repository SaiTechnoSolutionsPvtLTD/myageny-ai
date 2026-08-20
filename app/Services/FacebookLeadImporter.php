<?php

namespace App\Services;

use App\Models\AssignedUser;
use App\Models\CampaignFieldMigration;
use App\Models\CampaignMaster;
use App\Models\Lead;
use App\Models\LeadFieldValue;
use App\Models\LeadFormField;
use App\Models\LeadProduct;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookLeadImporter
{
    protected const GRAPH_VERSION = 'v19.0';
    protected const LEAD_FIELDS = 'id,created_time,field_data,ad_id,campaign_id';

    public function importIntegratedCampaigns(): array
    {
        $campaigns = CampaignMaster::query()
            ->where(function ($query) {
                $query->where('is_integrated', 1)
                    ->orWhereHas('fieldMigrations');
            })
            ->orderBy('id')
            ->get();

        $summary = [
            'campaigns' => $campaigns->count(),
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($campaigns as $campaign) {
            try {
                $result = $this->importCampaign($campaign);
                $summary['processed']++;
                $summary['created'] += $result['created'];
                $summary['updated'] += $result['updated'];
                $summary['skipped'] += $result['skipped'];
                $summary['failed'] += $result['failed'];

                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $err) {
                        $formattedErr = "Campaign '{$campaign->campaign_name}': {$err}";
                        if (!in_array($formattedErr, $summary['errors'], true)) {
                            $summary['errors'][] = $formattedErr;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $summary['processed']++;
                $summary['failed']++;

                $errMessage = "Campaign '{$campaign->campaign_name}': " . $this->sanitizeErrorMessage($e->getMessage());
                if (!in_array($errMessage, $summary['errors'], true)) {
                    $summary['errors'][] = $errMessage;
                }

                Log::error('Facebook campaign import failed.', [
                    'campaign_master_id' => $campaign->id,
                    'campaign_name' => $campaign->campaign_name,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    public function importCampaign(CampaignMaster $campaign): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $campaignIdentifier = $this->campaignIdentifier($campaign);
        $accessToken = $this->resolveAccessToken($campaign);

        if (!$campaignIdentifier || !$accessToken) {
            Log::warning('Skipping Facebook campaign import due to missing identifier or token.', [
                'campaign_master_id' => $campaign->id,
            ]);

            $stats['failed'] = 1;
            $stats['errors'][] = 'Missing campaign identifier or access token.';

            return $stats;
        }

        try {
            $submissions = $this->fetchLeadPages($campaign, $accessToken);
        } catch (\Throwable $e) {
            $stats['failed'] = 1;
            $stats['errors'][] = $this->sanitizeErrorMessage($e->getMessage());

            Log::error('Facebook campaign lead fetch failed.', [
                'campaign_master_id' => $campaign->id,
                'campaign_name' => $campaign->campaign_name,
                'message' => $e->getMessage(),
            ]);

            return $stats;
        }

        $campaignIdentifier = $this->campaignIdentifier($campaign) ?: $campaignIdentifier;
        $fieldMappings = CampaignFieldMigration::query()
            ->where('campaign_id', $campaign->id)
            ->get();

        $leadFields = LeadFormField::query()
            ->whereIn('id', $fieldMappings->pluck('lead_field_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $assignedUsers = $this->activeAssignedUsers($campaign);

        foreach ($submissions as $submission) {
            try {
                $status = $this->importSubmission(
                    $campaign,
                    $campaignIdentifier,
                    $submission,
                    $fieldMappings,
                    $leadFields,
                    $assignedUsers
                );

                $stats[$status]++;
            } catch (\Throwable $e) {
                $stats['failed']++;
                $errorMsg = $this->sanitizeErrorMessage($e->getMessage());
                if (!in_array($errorMsg, $stats['errors'], true)) {
                    $stats['errors'][] = $errorMsg;
                }

                Log::error('Facebook lead import failed for submission.', [
                    'campaign_master_id' => $campaign->id,
                    'facebook_lead_id' => data_get($submission, 'id'),
                    'facebook_campaign_name' => $campaign->campaign_name,
                    'message' => $e->getMessage(),
                    'mapped_field_names' => $fieldMappings->pluck('crm_field_name')->filter()->values()->all(),
                ]);
            }
        }

        return $stats;
    }

    public function fetchLeadSubmissions(CampaignMaster $campaign): Collection
    {
        $campaignIdentifier = $this->campaignIdentifier($campaign);
        $accessToken = $this->resolveAccessToken($campaign);

        if (!$campaignIdentifier || !$accessToken) {
            throw new \RuntimeException('Missing Facebook lead form/campaign ID or access token.');
        }

        return $this->fetchLeadPages($campaign, $accessToken);
    }

    public function fetchLeadsFromNodeId(string $nodeId, string $accessToken): Collection
    {
        return $this->fetchLeadsFromNode($nodeId, $accessToken);
    }

    protected function fetchLeadPages(CampaignMaster $campaign, string $accessToken): Collection
    {
        $candidateIds = collect([$campaign->camp_id, $campaign->ad_id])
            ->filter()
            ->unique()
            ->values();
        $lastFetchError = null;

        foreach ($candidateIds as $candidateId) {
            try {
                $leads = $this->fetchLeadsFromNode((string) $candidateId, $accessToken);
                if ($leads->isNotEmpty()) {
                    return $leads;
                }
            } catch (\Throwable $e) {
                $lastFetchError = $e->getMessage();

                Log::warning('Facebook lead fetch failed for node, trying fallback resolution.', [
                    'campaign_master_id' => $campaign->id,
                    'node_id' => $candidateId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $formIds = $this->resolveLeadFormIds($campaign, $candidateIds, $accessToken);

        if ($formIds->isEmpty()) {
            $message = 'No valid Facebook lead form could be resolved for this campaign.';

            if ($lastFetchError) {
                $message .= ' Last Facebook response: ' . $lastFetchError;
            }

            throw new \RuntimeException($message);
        }

        $allLeads = collect();

        foreach ($formIds as $formId) {
            try {
                $allLeads = $allLeads->merge($this->fetchLeadsFromNode((string) $formId, $accessToken));
            } catch (\Throwable $e) {
                Log::warning('Facebook lead fetch failed for form node.', [
                    'campaign_master_id' => $campaign->id,
                    'form_id' => $formId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($formIds->count() === 1 && $campaign->camp_id !== (string) $formIds->first()) {
            $campaign->forceFill(['camp_id' => (string) $formIds->first()])->saveQuietly();
        }

        return $allLeads->unique('id')->values();
    }

    protected function fetchLeadsFromNode(string $nodeId, string $accessToken): Collection
    {
        $nodeId = trim($nodeId);
        $accessToken = trim($accessToken);

        if ($nodeId === '' || $accessToken === '') {
            throw new \RuntimeException('Facebook lead node ID or access token is missing.');
        }

        $url = "https://graph.facebook.com/" . self::GRAPH_VERSION . "/" . rawurlencode($nodeId) . "/leads";


        $leads = collect();
        $after = null;

        for ($page = 0; $page < 100; $page++) {
            $query = [
                'access_token' => $accessToken,
                'fields' => self::LEAD_FIELDS,
                'limit' => 100,
            ];

            if ($after) {
                $query['after'] = $after;
            }

            $response = Http::timeout(30)
                ->acceptJson()
                ->get($url, $query);

            $payload = $response->json();

            if ($response->failed()) {
                $errorMessage = data_get($payload, 'error.message');
                throw new \RuntimeException($errorMessage ?: ('Facebook Graph API request failed with status ' . $response->status() . ' for node ' . $nodeId));
            }

            if (!empty($payload['error'])) {
                throw new \RuntimeException(data_get($payload, 'error.message', 'Facebook Graph API returned an error.'));
            }

            $leads = $leads->merge(collect($payload['data'] ?? []));

            $after = data_get($payload, 'paging.cursors.after');
            if (!$after || !data_get($payload, 'paging.next')) {
                break;
            }
        }

        return $leads;
    }

    protected function resolveLeadFormIds(CampaignMaster $campaign, Collection $candidateIds, string $accessToken): Collection
    {
        $formIds = collect();

        foreach ($candidateIds as $candidateId) {
            $formIds = $formIds->merge($this->resolveLeadFormIdsFromAd((string) $candidateId, $accessToken));
        }

        if ($formIds->isNotEmpty()) {
            return $formIds->filter()->unique()->values();
        }

        if ($campaign->ad_id) {
            $formIds = $formIds->merge($this->resolveLeadFormIdsFromAdAccount($campaign, (string) $campaign->ad_id, $accessToken));
        }

        return $formIds->filter()->unique()->values();
    }

    protected function resolveAccessToken(CampaignMaster $campaign): ?string
    {
        $token = trim((string) $campaign->access_token);

        if ($token !== '') {
            return $token;
        }

        $adAccountId = trim((string) $campaign->ad_id);

        if ($adAccountId === '') {
            return null;
        }

        $candidates = collect([
            $adAccountId,
            Str::startsWith($adAccountId, 'act_') ? Str::after($adAccountId, 'act_') : 'act_' . $adAccountId,
        ])->filter()->unique()->values();

        $fallbackToken = (string) DB::table('ad_accounts')
            ->whereIn('act_id', $candidates)
            ->value('token');

        if ($fallbackToken === '') {
            return null;
        }

        $campaign->forceFill(['access_token' => $fallbackToken])->saveQuietly();

        return $fallbackToken;
    }

    protected function campaignIdentifier(CampaignMaster $campaign): ?string
    {
        $identifier = trim((string) ($campaign->camp_id ?: $campaign->ad_id));

        return $identifier !== '' ? $identifier : null;
    }

    protected function appendAccessToken(string $url, string $accessToken): string
    {
        if (str_contains($url, 'access_token=')) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query([
            'access_token' => $accessToken,
        ]);
    }

    protected function resolveLeadFormIdsFromAd(string $adId, string $accessToken): Collection
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->get("https://graph.facebook.com/" . self::GRAPH_VERSION . "/{$adId}", [
                'access_token' => $accessToken,
                'fields' => 'id,creative{id,object_story_spec,effective_object_story_id},adcreatives{id,object_story_spec}',
            ]);

        if ($response->failed()) {
            return collect();
        }

        $payload = $response->json();

        return collect([
            data_get($payload, 'creative.object_story_spec.link_data.call_to_action.value.lead_gen_form_id'),
            data_get($payload, 'creative.object_story_spec.video_data.call_to_action.value.lead_gen_form_id'),
        ])->merge(
            collect(data_get($payload, 'adcreatives.data', []))->flatMap(function (array $creative) {
                return [
                    data_get($creative, 'object_story_spec.link_data.call_to_action.value.lead_gen_form_id'),
                    data_get($creative, 'object_story_spec.video_data.call_to_action.value.lead_gen_form_id'),
                ];
            })
        )->filter()->unique()->values();
    }

    protected function resolveLeadFormIdsFromAdAccount(CampaignMaster $campaign, string $adAccountId, string $accessToken): Collection
    {
        $normalizedAdAccountId = Str::startsWith($adAccountId, 'act_') ? $adAccountId : 'act_' . $adAccountId;
        $response = Http::timeout(30)
            ->acceptJson()
            ->get("https://graph.facebook.com/" . self::GRAPH_VERSION . "/{$normalizedAdAccountId}/ads", [
                'access_token' => $accessToken,
                'fields' => 'id,name,creative{object_story_spec}',
                'limit' => 500,
            ]);

        if ($response->failed()) {
            return collect();
        }

        $payload = $response->json();

        return collect(data_get($payload, 'data', []))
            ->filter(function (array $ad) use ($campaign) {
                return !empty($campaign->campaign_name)
                    && strcasecmp((string) data_get($ad, 'name', ''), (string) $campaign->campaign_name) === 0;
            })
            ->flatMap(function (array $ad) {
                return [
                    data_get($ad, 'creative.object_story_spec.link_data.call_to_action.value.lead_gen_form_id'),
                    data_get($ad, 'creative.object_story_spec.video_data.call_to_action.value.lead_gen_form_id'),
                ];
            })
            ->filter()
            ->unique()
            ->values();
    }

    protected function importSubmission(
        CampaignMaster $campaign,
        string $campaignIdentifier,
        array $submission,
        Collection $fieldMappings,
        Collection $leadFields,
        Collection $assignedUsers
    ): string {
        $facebookLeadId = (string) data_get($submission, 'id');

        if ($facebookLeadId === '') {
            return 'skipped';
        }

        $mappedValues = $this->mapSubmissionValues($submission, $fieldMappings, $leadFields);
        $mappedValues = $this->applyCampaignProductDefaults($campaign, $mappedValues);

        $existingLead = Lead::query()
            ->where('facebook_lead_id', $facebookLeadId)
            ->first();

        if ($existingLead) {
            $this->createLeadProductIfPossible($existingLead, $mappedValues['core'], $this->resolveNextAssignedUser($campaignIdentifier, $assignedUsers));

            return $this->syncMappedValuesForExistingLead($existingLead, $mappedValues, $leadFields)
                ? 'updated'
                : 'skipped';
        }

        $assignedUser = $this->resolveNextAssignedUser($campaignIdentifier, $assignedUsers);
        $leadDate = Carbon::parse(data_get($submission, 'created_time', now()))->toDateString();

        $leadPayload = $this->buildLeadPayload(
            $campaign,
            $campaignIdentifier,
            $facebookLeadId,
            $leadDate,
            $mappedValues,
            $assignedUser,
            $submission
        );

        DB::transaction(function () use ($leadPayload, $mappedValues, $leadFields, $assignedUser) {
            $lead = Lead::create($leadPayload);

            $this->syncCustomFieldValues($lead, $mappedValues['custom'], $leadFields);
            $this->createLeadProductIfPossible($lead, $mappedValues['core'], $assignedUser);
        });

        return 'created';
    }

    protected function syncMappedValuesForExistingLead(Lead $lead, array $mappedValues, Collection $leadFields): bool
    {
        $updated = false;

        $core = $mappedValues['core'] ?? [];
        $fillableUpdates = [];

        foreach ($core as $column => $val) {
            if ($val === null || $val === '') {
                continue;
            }

            if (empty($lead->{$column}) || $lead->{$column} !== $val) {
                $fillableUpdates[$column] = $val;
            }
        }

        if (!empty($fillableUpdates)) {
            $lead->fill($fillableUpdates);
            if ($lead->isDirty()) {
                $lead->save();
                $updated = true;
            }
        }

        if (!empty($mappedValues['custom'])) {
            $this->syncCustomFieldValues($lead, $mappedValues['custom'], $leadFields);
            $updated = true;
        }

        return $updated;
    }

    protected function syncCustomFieldValues(Lead $lead, array $customValues, Collection $leadFields): void
    {
        foreach ($customValues as $leadFieldId => $value) {
            $field = $leadFields->get($leadFieldId) ?? LeadFormField::find($leadFieldId);

            if (!$field) {
                continue;
            }

            if (is_array($value)) {
                $value = array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
                $normalizedValue = json_encode($value);
            } else {
                $normalizedValue = $value !== null ? trim((string) $value) : null;
            }

            if ($normalizedValue === null || $normalizedValue === '' || $normalizedValue === '[]') {
                LeadFieldValue::query()
                    ->where('lead_id', $lead->id)
                    ->where('lead_form_field_id', $field->id)
                    ->delete();
                continue;
            }

            LeadFieldValue::updateOrCreate(
                [
                    'lead_id' => $lead->id,
                    'lead_form_field_id' => $field->id,
                ],
                [
                    'value' => $normalizedValue,
                ]
            );
        }
    }

    protected function mapSubmissionValues(array $submission, Collection $fieldMappings, Collection $leadFields): array
    {
        $core = [];
        $custom = [];
        $firstName = null;
        $lastName = null;

        foreach ((array) data_get($submission, 'field_data', []) as $field) {
            $facebookFieldName = (string) data_get($field, 'name');
            $values = (array) data_get($field, 'values', []);
            $value = count($values) > 1 ? array_values($values) : data_get($field, 'values.0');

            if ($facebookFieldName === '' || $value === null || $value === '') {
                continue;
            }

            // 1. Exact match on campaign_field_name
            $mapping = $fieldMappings->firstWhere('campaign_field_name', $facebookFieldName);

            // 2. Normalized case-insensitive match on campaign_field_name
            if (!$mapping) {
                $normFbName = Str::of($facebookFieldName)->trim()->lower()->replace([' ', '-'], '_')->value();
                $mapping = $fieldMappings->first(function ($m) use ($normFbName) {
                    $normMappedName = Str::of((string) $m->campaign_field_name)->trim()->lower()->replace([' ', '-'], '_')->value();
                    return $normMappedName === $normFbName;
                });
            }

            if ($mapping) {
                $leadField = $leadFields->get($mapping->lead_field_id);

                if ($leadField) {
                    $custom[$leadField->id] = $value;
                    continue;
                }

                $fieldName = $this->normalizeLeadColumnName($mapping->crm_field_name);

                if ($fieldName && $this->isLeadColumn($fieldName)) {
                    $core[$fieldName] = $value;
                    continue;
                }
            }

            // 3. Fallback for standard Facebook Lead Ads keys if unmapped or missing in DB mapping
            $normKey = Str::of($facebookFieldName)->trim()->lower()->replace([' ', '-'], '_')->value();

            if (!isset($core['contact_name'])) {
                if (in_array($normKey, ['full_name', 'name', 'contact_name', 'client_name', 'customer_name'], true) && is_string($value)) {
                    $core['contact_name'] = trim($value);
                } elseif (in_array($normKey, ['first_name', 'firstname'], true) && is_string($value)) {
                    $firstName = trim($value);
                } elseif (in_array($normKey, ['last_name', 'lastname'], true) && is_string($value)) {
                    $lastName = trim($value);
                }
            }

            if (!isset($core['email']) && in_array($normKey, ['email', 'email_address'], true) && is_string($value)) {
                $core['email'] = trim($value);
            }

            if (!isset($core['mobile_number']) && in_array($normKey, ['phone_number', 'phone', 'mobile', 'mobile_number', 'mobile_no'], true) && is_string($value)) {
                $core['mobile_number'] = $this->cleanPhoneNumber($value);
            }

            if (!isset($core['company_name']) && in_array($normKey, ['company', 'company_name', 'business_name', 'organization'], true) && is_string($value)) {
                $core['company_name'] = trim($value);
            }

            if (!isset($core['remarks']) && in_array($normKey, ['remarks', 'message', 'notes', 'comments'], true) && is_string($value)) {
                $core['remarks'] = trim($value);
            }
        }

        if (!isset($core['contact_name']) && ($firstName || $lastName)) {
            $core['contact_name'] = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        }

        if (isset($core['mobile_number'])) {
            $core['mobile_number'] = $this->cleanPhoneNumber($core['mobile_number']);
        }

        return [
            'core' => $core,
            'custom' => $custom,
        ];
    }

    protected function cleanPhoneNumber(mixed $value): string
    {
        $str = is_array($value) ? implode('', $value) : (string) $value;
        $str = Str::after($str, 'p:');
        $cleaned = preg_replace('/[^\d+]/', '', $str);

        return $cleaned !== '' ? $cleaned : (trim((string) $value) ?: '0000000000');
    }

    protected function buildLeadPayload(
        CampaignMaster $campaign,
        string $campaignIdentifier,
        string $facebookLeadId,
        string $leadDate,
        array $mappedValues,
        ?User $assignedUser,
        array $submission
    ): array {
        $core = $mappedValues['core'];
        $companyName = trim((string) ($core['company_name'] ?? $core['contact_name'] ?? 'Facebook Lead'));
        $contactName = trim((string) ($core['contact_name'] ?? $core['company_name'] ?? 'Facebook Lead'));
        $mobileNumber = $this->cleanPhoneNumber($core['mobile_number'] ?? '0000000000');

        return [
            'company_name' => $companyName !== '' ? $companyName : 'Facebook Lead',
            'contact_name' => $contactName !== '' ? $contactName : 'Facebook Lead',
            'lead_date'    => $core['lead_date'] ?? $leadDate,
            'mobile_number' => $mobileNumber !== '' ? $mobileNumber : '0000000000',
            'email'        => $core['email'] ?? null,
            'lead_source_id' => $this->normalizeLeadSourceId($core['lead_source'] ?? null, $assignedUser?->company_id)
                ?? $this->defaultLeadSourceId($assignedUser?->company_id),
            'lead_status_id' => $this->normalizeLeadStatusId($core['lead_status'] ?? null, $assignedUser?->company_id)
                ?? $this->defaultLeadStatusId($assignedUser?->company_id),
            'product_name' => $core['product_name'] ?? null,
            'product_id'   => $this->normalizeProductId($core['product_id'] ?? null),
            'priority'     => $this->normalizePriority($core['priority'] ?? null),
            'deal_value'   => $this->normalizeMoney($core['deal_value'] ?? null),
            'remarks'      => $core['remarks'] ?? '',
            'assigned_to'  => $assignedUser?->id,
            'created_by'   => $assignedUser?->id,
            'company_id'   => $assignedUser?->company_id,
            'facebook_lead_id'     => $facebookLeadId,
            'facebook_campaign_id' => data_get($submission, 'campaign_id') ?: data_get($submission, 'ad_id') ?: $campaignIdentifier,
            'facebook_payload'     => $submission,
        ];
    }

    protected function applyCampaignProductDefaults(CampaignMaster $campaign, array $mappedValues): array
    {
        $productId = $this->normalizeProductId($mappedValues['core']['product_id'] ?? null);

        if ($productId || ! $campaign->product_id) {
            return $mappedValues;
        }

        $campaign->loadMissing('product');

        if (! $campaign->product) {
            return $mappedValues;
        }

        $mappedValues['core']['product_id'] = $campaign->product->id;
        $mappedValues['core']['product_name'] = $mappedValues['core']['product_name']
            ?? ($campaign->product->package_name ?: $campaign->product->product_name);

        return $mappedValues;
    }

    protected function createLeadProductIfPossible(Lead $lead, array $coreValues, ?User $assignedUser): void
    {
        $productId = $this->normalizeProductId($coreValues['product_id'] ?? null);
        $productName = $coreValues['product_name'] ?? null;

        if (! $productId && ! $productName) {
            return;
        }

        if (! $productId) {
            Log::warning('Skipping Facebook lead product creation because mapped product_id is missing.', [
                'lead_id' => $lead->id,
                'facebook_lead_id' => $lead->facebook_lead_id,
                'product_name' => $productName,
            ]);

            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            Log::warning('Skipping Facebook lead product creation because mapped product_id was not found.', [
                'lead_id' => $lead->id,
                'facebook_lead_id' => $lead->facebook_lead_id,
                'product_id' => $productId,
                'product_name' => $productName,
            ]);

            return;
        }

        LeadProduct::firstOrCreate(
            [
                'lead_id'    => $lead->id,
                'product_id' => $product->id,
            ],
            [
                'deal_name'      => $product->package_name ?: $product->product_name ?: 'Facebook Imported Product',
                'product_name'   => $productName ?: $product->package_name ?: $product->product_name ?: 'Facebook Imported Product',
                'description'    => $product->description,
                'unit_price'     => (float) ($product->final_price ?? 0),
                'quantity'       => 1,
                'discount_percent' => 0,
                'remarks'        => 'Created automatically from Facebook lead import.',
                'product_status' => 'new',
                'amount_paid'    => 0,
                'lead_source_id' => $lead->lead_source_id,
                'created_by'     => $assignedUser?->id ?: $lead->assigned_to ?: $lead->created_by,
                'company_id'     => $lead->company_id,
            ]
        );
    }

    protected function activeAssignedUsers(CampaignMaster $campaign): Collection
    {
        $userIds = AssignedUser::query()
            ->where('campaign_id', $campaign->id)
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            })
            ->orderBy('id')
            ->get();
    }

    protected function resolveNextAssignedUser(string $campaignIdentifier, Collection $assignedUsers): ?User
    {
        if ($assignedUsers->isEmpty()) {
            return null;
        }

        $userIds = $assignedUsers->pluck('id')->all();

        $lastAssignedLead = Lead::query()
            ->where('facebook_campaign_id', $campaignIdentifier)
            ->whereIn('assigned_to', $userIds)
            ->latest('id')
            ->first();

        $lastAssignedUserId = $lastAssignedLead?->assigned_to;
        $lastIndex = $assignedUsers->search(fn (User $user) => $user->id === $lastAssignedUserId);
        $nextIndex = $lastIndex === false ? 0 : (($lastIndex + 1) % $assignedUsers->count());

        return $assignedUsers->values()->get($nextIndex);
    }

    protected function buildRemarks(CampaignMaster $campaign, array $submission, ?string $mappedRemarks): string
    {
        $parts = array_filter([
            $mappedRemarks,
            'Imported from Facebook campaign: ' . $campaign->campaign_name,
            'Facebook lead ID: ' . data_get($submission, 'id'),
            data_get($submission, 'campaign_id') ? 'Facebook campaign ID: ' . data_get($submission, 'campaign_id') : null,
            data_get($submission, 'ad_id') ? 'Facebook ad ID: ' . data_get($submission, 'ad_id') : null,
        ]);

        return Str::limit(implode("\n", $parts), 65000, '');
    }

    protected function defaultLeadStatusId(?int $companyId = null): ?int
    {
        $query = LeadStatus::query();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $statuses = $query->orderBy('id')->get(['id', 'name']);

        $preferred = $statuses->first(function (LeadStatus $status) {
            return $this->normalizeStatusKey($status->name) === 'new';
        });

        return $preferred?->id ?: $statuses->first()?->id;
    }

    /**
     * Resolve a lead source name string to its ID in lead_sources.
     * If not found by name, tries to find or create one with that name.
     */
    protected function normalizeLeadSourceId(mixed $value, ?int $companyId = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If an integer-like value, return as-is (already an ID)
        if (is_numeric($value)) {
            return (int) $value;
        }

        $name = trim((string) $value);

        if ($name === '') {
            return null;
        }

        $query = LeadSource::query();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $sources = $query->get(['id', 'name']);

        $nameLower = strtolower($name);
        $matched = $sources->first(
            fn (LeadSource $s) => strtolower(trim($s->name)) === $nameLower
        );

        return $matched?->id;
    }

    /**
     * Find or create the default 'Facebook' lead source for the given company.
     */
    protected function defaultLeadSourceId(?int $companyId = null): ?int
    {
        $query = LeadSource::query();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $sources = $query->orderBy('id')->get(['id', 'name']);

        // Look for a source named 'facebook' (case-insensitive)
        $preferred = $sources->first(
            fn (LeadSource $s) => strtolower(trim($s->name)) === 'facebook'
        );

        if ($preferred) {
            return $preferred->id;
        }

        // If none found and we have a company, create 'Facebook' source
        if ($companyId !== null) {
            $created = LeadSource::create([
                'name'       => 'Facebook',
                'company_id' => $companyId,
            ]);

            return $created->id;
        }

        // Fall back to any first source
        return $sources->first()?->id;
    }

    protected function normalizePriority(?string $priority): string
    {
        $value = Str::of((string) $priority)->trim()->lower()->value();

        return array_key_exists($value, Lead::PRIORITIES) ? $value : 'medium';
    }

    protected function normalizeMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    }

    protected function normalizeProductId(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    protected function normalizeLeadStatusId(mixed $value, ?int $companyId = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $statusKey = $this->normalizeStatusKey((string) $value);

        if ($statusKey === '') {
            return null;
        }

        $query = LeadStatus::query();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $statuses = $query->get(['id', 'name']);

        $matchedStatus = $statuses->first(function (LeadStatus $status) use ($statusKey) {
            return $this->normalizeStatusKey($status->name) === $statusKey;
        });

        return $matchedStatus?->id;
    }

    protected function normalizeStatusKey(?string $value): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', (string) $value), '_'));
    }

    protected function normalizeLeadColumnName(?string $crmFieldName): ?string
    {
        if (!$crmFieldName) {
            return null;
        }

        $normalized = Str::of($crmFieldName)->trim()->lower()->replace([' ', '-'], '_')->value();

        return match ($normalized) {
            'company', 'companyname', 'company_name' => 'company_name',
            'clientname', 'contact_name', 'contactname', 'name' => 'contact_name',
            'mobile', 'mobilenumber', 'mobile_number', 'phone', 'phone_number' => 'mobile_number',
            'email', 'email_address' => 'email',
            'lead_source', 'leadsource' => 'lead_source',
            'lead_status', 'status' => 'lead_status',
            'product', 'product_name', 'productname' => 'product_name',
            'product_id' => 'product_id',
            'priority' => 'priority',
            'deal_value', 'dealvalue', 'amount' => 'deal_value',
            'remarks', 'notes', 'message' => 'remarks',
            'lead_date', 'entry_date', 'entrydate' => 'lead_date',
            default => $normalized,
        };
    }

    protected function isLeadColumn(string $fieldName): bool
    {
        return in_array($fieldName, [
            'company_name',
            'contact_name',
            'lead_date',
            'mobile_number',
            'email',
            'lead_source',
            'lead_source_id',
            'lead_status',
            'product_name',
            'product_id',
            'priority',
            'deal_value',
            'remarks',
        ], true);
    }

    public function sanitizeErrorMessage(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            return 'Unknown error occurred.';
        }

        $message = preg_replace('/access_token=([^&\s]+)/i', 'access_token=[hidden]', $message);
        $message = preg_replace('/\bEAA[A-Za-z0-9_-]{20,}\b/', '[hidden-token]', $message);

        return $message;
    }
}