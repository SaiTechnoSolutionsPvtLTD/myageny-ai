<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\RestrictsEmployeesToOwnBranch;
use App\Http\Controllers\App\Concerns\ScopesLeadStatusAndSourceToCompany;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadReminder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\LeadFormField;
use App\Models\LeadFieldValue;
use App\Models\LeadProductPriceRequest;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\LeadCallUpdate;
use App\Models\LeadStatus;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use App\Services\NotificationService;

#[OA\Tag(name: "Leads", description: "Lead management endpoints for mobile app")]

class LeadController extends Controller
{
    use RestrictsEmployeesToOwnBranch;
    use ScopesLeadStatusAndSourceToCompany;

    public function __construct(private readonly DataVisibilityService $visibility, private readonly NotificationService $notifications) {}

    // =========================================================================
    // INDEX — List all leads with filters + pagination
    // =========================================================================

    #[OA\Get(
        path: "/api/mobile/leads",
        summary: "List leads with filters & pagination",
        security: [["sanctum" => []]],
        tags: ["Leads"],
        parameters: [
            new OA\Parameter(name: "search",        in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "branch_id",     in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lead_source",   in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "lead_status",   in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "priority",      in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "assigned_to",   in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "pre_sale_executive_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "mobile_number", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "product_name",  in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "date_from",     in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "date_to",       in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "per_page",      in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
            new OA\Parameter(name: "page",          in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated list of leads"),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Lead::with(['branch:id,name', 'assignedTo:id,name', 'createdBy:id,name', 'product:id,product_name', 'products', 'leadSource:id,name', 'leadStatus:id,name'])
            ->orderByDesc('id');

        $this->visibility->applyLeadVisibility($query, $request->user());

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('company_name',   'like', "%{$s}%")
                    ->orWhere('contact_name', 'like', "%{$s}%")
                    ->orWhere('mobile_number', 'like', "%{$s}%")
                    ->orWhere('email',        'like', "%{$s}%")
                    ->orWhereHas('product', fn($pq) => $pq->where('product_name', 'like', "%{$s}%"))
                    // Mobile's Lead List "Search" field (both the screen's own
                    // search bar and the Filter Leads sheet's Search field
                    // route through this same param) also needs to match the
                    // assigned user's name — matches the Lead Products
                    // screen's own combined search, which already does this.
                    ->orWhereHas('assignedTo', fn($uq) => $uq->where('name', 'like', "%{$s}%"));
            });
        }

        // Only apply the branch_id filter param if the requesting user is
        // actually allowed to see that branch (canAssignBranch() doubles as
        // "may target/view this branch" here) — silently ignoring an
        // unauthorized value rather than erroring keeps this a well-behaved
        // filter while still closing off the "pass another branch_id to
        // peek at it" bypass named in the ticket. applyLeadVisibility()
        // above already bounds the result set by assigned_to visibility
        // regardless, so this is defense in depth, not the only guard.
        if ($request->filled('branch_id') && $this->canAssignBranch((int) $request->branch_id, $request->user())) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('mobile_number')) $query->where('mobile_number', 'like', '%' . $request->mobile_number . '%');
        // Mobile's Filter Leads modal sources its Lead Source / Lead Status
        // dropdown options from meta() below, whose keys are
        // Lead::sourceOptions()/statusOptions() — i.e. lead_sources.id /
        // lead_statuses.id (see Lead::sourceOptions() -> pluck('name','id')).
        // So the value this endpoint receives here is always the numeric FK
        // id, never the display name. Filtering against the plain
        // 'lead_source'/'lead_status' string columns (as this used to) could
        // never match that id, so selecting either filter silently returned
        // zero/incorrect results — the actual leads.lead_source_id /
        // lead_status_id FK columns are what line up with it (same columns
        // web's own LeadController@index filters by for the same reason).
        if ($request->filled('lead_source'))   $query->where('lead_source_id', $request->lead_source);
        // Matches web's LeadController@index lead_status handling: a lead
        // counts as this status either at its own top level OR via any of
        // its products (leads with multiple products can have a product
        // sitting at a different stage than the lead's own lead_status_id).
        //
        // The dropdown in the Filter Leads modal sends a numeric
        // lead_status_id (see meta() below), but the CRM Dashboard's Active
        // Customers card sends the literal keyword 'won'/'converted' —
        // this company's lead_statuses table has no row literally named
        // "Won", only "Converted", so a plain id/name lookup for 'won'
        // would never match anything. Lead::scopeConverted() is the single
        // already-correct definition of "this lead is a converted/active
        // customer" (used by the dashboard's own won-leads KPI below), so
        // reuse it here to guarantee the Lead List always shows exactly
        // the leads the dashboard counted — instead of two independent
        // status checks that could silently drift apart.
        if ($request->filled('lead_status')) {
            $statusVal = $request->lead_status;
            if (is_numeric($statusVal)) {
                $statusId = (int) $statusVal;
                $query->where(function ($q) use ($statusId) {
                    $q->where('lead_status_id', $statusId)
                        ->orWhereHas('products', fn($pq) => $pq->where('lead_status_id', $statusId));
                });
            } elseif (in_array(strtolower($statusVal), ['won', 'converted'], true)) {
                $query->converted();
            } else {
                // Any other non-numeric value — a raw status name sent
                // instead of its id. Mirrors the product_status handling
                // further down: resolve the name to its LeadStatus id.
                $statusRecord = LeadStatus::where('name', 'like', $statusVal)->first();
                if ($statusRecord) {
                    $sid = $statusRecord->id;
                    $query->where(function ($q) use ($sid) {
                        $q->where('lead_status_id', $sid)
                            ->orWhereHas('products', fn($pq) => $pq->where('lead_status_id', $sid));
                    });
                }
            }
        }
        if ($request->filled('priority'))      $query->where('priority',     $request->priority);
        if ($request->filled('assigned_to'))   $query->where('assigned_to',  $request->assigned_to);
        if ($request->filled('product_name'))  $query->whereHas('product', fn($pq) => $pq->where('product_name', 'like', '%' . $request->product_name . '%'));
        // Mirrors web's LeadController@index — the Filter Leads sheet's new
        // "Pre Sales Exec" dropdown (see meta()'s pre_sale_executives list).
        if ($request->filled('pre_sale_executive_id')) $query->where('pre_sale_executive_id', $request->pre_sale_executive_id);
        if ($request->filled('date_from'))     $query->whereDate('lead_date', '>=', $request->date_from);
        if ($request->filled('date_to'))       $query->whereDate('lead_date', '<=', $request->date_to);

        $perPage = (int) $request->input('per_page', 15);
        $leads   = $query->paginate($perPage);

        $statsBase = Lead::query();
        $this->visibility->applyLeadVisibility($statsBase, $request->user());

        $stats = [
            'total'         => (clone $statsBase)->count(),
            'new'           => (clone $statsBase)->where('lead_status', 'new')->count(),
            'won'           => (clone $statsBase)->where('lead_status', 'won')->count(),
            'lost'          => (clone $statsBase)->where('lead_status', 'lost')->count(),
            'pipeline'      => (clone $statsBase)->whereNotIn('lead_status', ['won', 'lost'])->sum('deal_value'),
            'high_priority' => (clone $statsBase)->where('priority', 'high')->whereNotIn('lead_status', ['won', 'lost'])->count(),
        ];

        return response()->json([
            'status' => true,
            'data'   => [
                'leads' => [
                    'data'         => $leads->map(fn($l) => $this->formatLeadSummary($l)),
                    'current_page' => $leads->currentPage(),
                    'last_page'    => $leads->lastPage(),
                    'per_page'     => $leads->perPage(),
                    'total'        => $leads->total(),
                ],
                'stats' => $stats,
            ],
        ]);
    }

    // =========================================================================
    // STORE — Create a new lead (with optional reminder)
    // =========================================================================

    #[OA\Post(
        path: "/api/mobile/leads",
        summary: "Create a new lead",
        security: [["sanctum" => []]],
        tags: ["Leads"],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent()),
        responses: [
            new OA\Response(response: 201, description: "Lead created"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name'  => ['required', 'string', 'max:255'],
            'contact_name'  => ['required', 'string', 'max:255'],
            'lead_date'     => ['nullable', 'date'],
            'mobile_number' => ['required', 'string', 'max:20', 'regex:' . Lead::MOBILE_NUMBER_REGEX],
            'email'         => ['nullable', 'email', 'max:255'],
            'lead_source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'lead_status_id' => ['nullable', 'integer', 'exists:lead_statuses,id'],
            'lead_status'     => ['nullable', 'string'],
            'product_id'    => ['nullable', 'integer', 'exists:products,id'],
            // The mobile app no longer exposes a Priority field on Add Lead
            // (Lead Priority – Mobile App Changes ticket) — this stays
            // 'nullable' rather than 'required' purely so the request
            // doesn't fail when the key is simply absent. The actual value
            // saved is always forced to 'medium' below, regardless of
            // whether this was sent at all, since priority is mandatory in
            // the leads table.
            'priority'      => ['nullable', 'string'],
            'remarks'       => ['nullable', 'string'],
            'branch_id'     => ['nullable', 'integer', 'exists:branches,id'],
            'assigned_to'   => ['nullable', 'integer', 'exists:users,id'],

            'reminder'             => ['nullable', 'array'],
            'reminder.remind_at'   => ['required_with:reminder', 'date', 'after:now'],
            'reminder.title'       => ['required_with:reminder', 'string', 'max:255'],
            'reminder.description' => ['nullable', 'string', 'max:1000'],
            'reminder.type'        => ['nullable', 'string'],
            'reminder.priority'    => ['nullable', 'string'],
        ]);

        $reminderCreated = false;
        $lead = null;

        DB::transaction(function () use ($validated, $request, &$lead, &$reminderCreated) {
            $leadData = collect($validated)->except('reminder')->toArray();
            $leadData['created_by'] = $request->user()->id;
            $leadData['assigned_to'] = $leadData['assigned_to'] ?? $request->user()->id;
            $leadData['branch_id'] = $leadData['branch_id'] ?? $request->user()->branch_id;
            // Priority is no longer settable from the mobile app — every
            // mobile-created lead is saved as 'medium' to satisfy the
            // leads.priority NOT NULL column, regardless of anything sent.
            $leadData['priority'] = 'medium';

            abort_unless($this->visibility->canAssignTo($leadData['assigned_to'], $request->user()), 403);
            abort_unless($this->canAssignBranch($leadData['branch_id'], $request->user()), 403);

            // `exists:lead_sources,id` / `exists:lead_statuses,id` above only
            // check the id exists *somewhere* across every company — they
            // run a raw DB query with no model scope. This is the actual
            // company-ownership check (see ScopesLeadStatusAndSourceToCompany),
            // closing off a client-supplied id from another company being
            // saved onto this lead.
            abort_unless($this->isLeadSourceIdAllowedForCompany($leadData['lead_source_id'] ?? null, $request->user()), 403);
            abort_unless($this->isLeadStatusIdAllowedForCompany($leadData['lead_status_id'] ?? null, $request->user()), 403);

            // Get source name from lead_source_id
            $leadData['lead_source'] = null;

            if (!empty($leadData['lead_source_id'])) {
                // withoutGlobalScope: the id was just confirmed above to
                // belong to this company OR be a shared NULL-company_id
                // default — BelongsToCompany's own global scope would only
                // match the former (it excludes NULL-company_id rows
                // whenever the acting user has a company_id), so a bare
                // find() here could wrongly drop the resolved name for a
                // legitimate global default.
                $leadSource = LeadSource::withoutGlobalScope('company')->find($leadData['lead_source_id']);
                $leadData['lead_source'] = $leadSource?->name;
            }

            $assignedUser = User::find($leadData['assigned_to']);
            if ($assignedUser && ($assignedUser->belongsToCustomerSupportDepartment() || $assignedUser->hasCustomerSupportLikeRole())) {
                $mappedTl = $assignedUser->mappedManagers()
                    ->where(function ($q) {
                        $q->whereHas('roles', fn ($rq) => $rq->where('name', 'like', '%tl%')->orWhere('name', 'like', '%lead%'))
                          ->orWhereHas('roles.department', fn ($dq) => $dq->where('name', 'like', '%support%')->orWhere('name', 'like', '%cst%'));
                    })
                    ->first();

                $leadData['customer_support_tl_id'] = $mappedTl?->id ?: $assignedUser->id;
                $leadData['customer_support_executive_id'] = $assignedUser->id;
                $leadData['customer_support_allocated_at'] = now();
            }

            $lead = Lead::create($leadData);

            if (!empty($validated['reminder'])) {
                LeadReminder::create([
                    'lead_id'      => $lead->id,
                    'user_id'      => $request->user()->id,
                    'remind_at'    => $validated['reminder']['remind_at'],
                    'title'        => $validated['reminder']['title'],
                    'description'  => $validated['reminder']['description'] ?? null,
                    'type'         => $validated['reminder']['type'] ?? 'follow_up',
                    'priority'     => $validated['reminder']['priority'] ?? 'medium',
                    'is_completed' => false,
                ]);
                $reminderCreated = true;
            }
        });

        $lead->load(['branch:id,name', 'assignedTo:id,name', 'createdBy:id,name', 'product:id,product_name', 'reminders']);

        return response()->json([
            'status'           => true,
            'message'          => 'Lead created successfully.',
            'data'             => $this->formatLeadDetail($lead),
            'reminder_created' => $reminderCreated,
        ], 201);
    }

    // =========================================================================
    // SHOW — Single lead details
    // =========================================================================

    #[OA\Get(
        path: "/api/mobile/leads/{id}",
        summary: "Get a single lead",
        security: [["sanctum" => []]],
        tags: ["Leads"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Lead detail"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function show(Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, request()->user()), 403);

        $lead->load([
            'branch:id,name',
            'assignedTo:id,name',
            'preSaleExecutive:id,name',
            'createdBy:id,name',
            'product:id,product_name',
            'callUpdates.user:id,name',
            'callUpdates.outCome:id,name',
            'callUpdates.outComeSubCategory:id,name',
            'reminders.user:id,name',
            'products.payments.recordedBy:id,name',
            'products.leadStatus',
            'products.latestProductionInitiation.department',
            'products.latestProductionInitiation.initiatedBy',
            'products.latestProductionInitiation.reviewedBy',
            'products.latestProductionInitiation.productionApprovalReviewedBy',
            'products.latestProductionInitiation.projectAllocatedBy',
            'products.latestProductionInitiation.employeeAllocatedBy',
            'products.latestProductionInitiation.projectUpdates.createdBy',
            'quotations.items',
            'quotations.createdBy:id,name',
            'cstUpdates.user:id,name',
            'cstUpdates.product.product:id,product_name',
            'customFieldValues.field' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('label');
            },
        ]);

        return response()->json([
            'status' => true,
            'data'   => $this->formatLeadDetail($lead),
        ]);
    }

    // =========================================================================
    // UPDATE — Full update
    // =========================================================================

    #[OA\Put(
        path: "/api/mobile/leads/{id}",
        summary: "Update a lead",
        security: [["sanctum" => []]],
        tags: ["Leads"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent()),
        responses: [
            new OA\Response(response: 200, description: "Lead updated"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $validated = $request->validate([
            'company_name'  => ['sometimes', 'required', 'string', 'max:255'],
            'contact_name'  => ['sometimes', 'required', 'string', 'max:255'],
            'lead_date'     => ['nullable', 'date'],
            'mobile_number' => ['sometimes', 'required', 'string', 'max:20', 'regex:' . Lead::MOBILE_NUMBER_REGEX],
            'email'         => ['nullable', 'email', 'max:255'],
            'lead_source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'lead_status_id' => ['nullable', 'integer', 'exists:lead_statuses,id'],
            'lead_status'     => ['nullable', 'string'],
            'product_id'    => ['nullable', 'integer', 'exists:products,id'],
            'priority'      => ['sometimes', 'required', 'string', 'in:' . implode(',', array_keys(Lead::PRIORITIES))],
            'remarks'       => ['nullable', 'string'],
            'branch_id'     => ['nullable', 'integer', 'exists:branches,id'],
            'assigned_to'   => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (array_key_exists('assigned_to', $validated) && $validated['assigned_to']) {
            abort_unless($this->visibility->canAssignTo($validated['assigned_to'], $request->user()), 403);
        }

        // Same company-ownership check as store() — `exists:lead_sources,id`
        // / `exists:lead_statuses,id` above don't check company at all, so
        // without this a lead_source_id/lead_status_id from another
        // company could be written onto this lead by editing the request.
        if (array_key_exists('lead_source_id', $validated)) {
            abort_unless($this->isLeadSourceIdAllowedForCompany($validated['lead_source_id'], $request->user()), 403);
        }
        if (array_key_exists('lead_status_id', $validated)) {
            abort_unless($this->isLeadStatusIdAllowedForCompany($validated['lead_status_id'], $request->user()), 403);
        }

        // Only enforce the branch guard when branch_id is actually being
        // *changed* to something new — the mobile Edit screen's Branch
        // field is read-only and simply resubmits the lead's existing
        // branch_id unchanged on every save (e.g. when the user only
        // edited the company name). A lead's branch_id isn't guaranteed to
        // be one of the editor's own branches (lead visibility is based on
        // assigned_to, not branch_id), so blocking a same-value
        // resubmission here would break ordinary edits for leads that
        // happen to sit in another branch. A genuine reassignment attempt
        // — the value actually differing from what's stored — still goes
        // through canAssignBranch().
        if (array_key_exists('branch_id', $validated) && $validated['branch_id'] !== $lead->branch_id) {
            abort_unless($this->canAssignBranch($validated['branch_id'], $request->user()), 403);
        }

        $lead->update($validated);

        $lead->load(['branch:id,name', 'assignedTo:id,name', 'createdBy:id,name', 'product:id,product_name', 'reminders']);

        return response()->json([
            'status'  => true,
            'message' => 'Lead updated successfully.',
            'data'    => $this->formatLeadDetail($lead),
        ]);
    }

    // =========================================================================
    // DESTROY — Soft delete
    // =========================================================================

    #[OA\Delete(
        path: "/api/mobile/leads/{id}",
        summary: "Delete a lead",
        security: [["sanctum" => []]],
        tags: ["Leads"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function destroy(Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, request()->user()), 403);

        $name = $lead->company_name;
        $lead->delete();

        return response()->json([
            'status'  => true,
            'message' => "Lead \"{$name}\" removed successfully.",
        ]);
    }

    // =========================================================================
    // UPDATE STATUS — Quick patch
    // =========================================================================

    #[OA\Patch(
        path: "/api/mobile/leads/{id}/status",
        summary: "Quick status update",
        security: [["sanctum" => []]],
        tags: ["Leads"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent()),
        responses: [
            new OA\Response(response: 200, description: "Status updated"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $request->validate([
            'lead_status' => ['required', 'string', Rule::in(Lead::statusKeys())],
        ]);

        $lead->update(['lead_status' => $request->lead_status]);

        $this->maybeNotifyHighValueConversion($lead->fresh());

        return response()->json([
            'status'       => true,
            'message'      => "Lead status updated to {$lead->status_label}.",
            'lead_status'  => $lead->lead_status,
            'status_label' => $lead->status_label,
        ]);
    }

    private function maybeNotifyHighValueConversion(Lead $lead): void
    {
        $statusKey = str((string) $lead->status_label)->lower()->replace(' ', '_')->value();
        if ($statusKey !== 'won') {
            return;
        }

        $threshold = (float) config('crm.high_value_lead_threshold', 500000);
        if ((float) $lead->deal_value < $threshold) {
            return;
        }

        $recipients = User::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($lead->company_id, fn($q) => $q->where('company_id', $lead->company_id))
            ->get()
            ->filter(fn(User $u) => $u->hasAdminLikeRole());

        $recipients = $this->notifications->filterByBranchVisibility($recipients, $lead->branch_id);

        $this->notifications->notifyMany($recipients, 'crm', 'high_value_lead_converted', [
            'title' => 'High-Value Lead Converted',
            'message' => $lead->company_name . ' converted with a deal value of ' . $lead->formatted_deal_value . '.',
            'detail' => 'Assigned to ' . ($lead->assignedTo?->name ?? 'Unassigned'),
            'action_url' => route('leads.show', $lead),
            'priority' => 'high',
            'request_type' => 'lead_conversion',
            'request_id' => $lead->id,
            'actor_name' => auth()->user()?->name,
            'status' => 'won',
        ]);
    }

    // =========================================================================
    // META — Enums / filter options
    // =========================================================================

    #[OA\Get(
        path: "/api/mobile/leads/meta",
        summary: "Get lead filter options (enums, branches, users, products)",
        security: [["sanctum" => []]],
        tags: ["Leads"],
        responses: [
            new OA\Response(response: 200, description: "Meta data for leads"),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function meta(): JsonResponse
    {
        $user = request()->user();
        // Branch Add/Edit/Filter restriction: company-wide/admin users still
        // see every branch; everyone else only sees their own (usually a
        // single branch, but getMyBranchIds() covers the branch_user pivot
        // for a user linked to more than one). See canAssignBranch() for
        // the matching write-side enforcement.
        $branchesQuery = Branch::where('is_active', true);
        // Branch also carries BelongsToCompany, but (like LeadStatus/
        // LeadSource) that global scope isn't something this file leans
        // on anywhere else — explicit here too, so a company-wide/admin
        // user's meta() never lists another company's branches alongside
        // their own.
        if ($user?->company_id) {
            $branchesQuery->where('company_id', $user->company_id);
        }
        if (! $this->visibility->isCompanyWideUser($user)) {
            $branchesQuery->whereIn('id', $user->getMyBranchIds());
        }

        return response()->json([
            'status' => true,
            'data'   => [
                // Company-scoped, non-cached alternative to
                // Lead::sourceOptions()/statusOptions() — see
                // ScopesLeadStatusAndSourceToCompany's doc comment for why
                // those shared-model helpers can't be trusted here (no
                // company filter reaches an `exists:` validation rule, and
                // their static cache doesn't distinguish between companies).
                'sources'        => $this->companyScopedLeadSourceOptions($user),
                'statuses'       => $this->companyScopedLeadStatusOptions($user),
                'priorities'     => Lead::PRIORITIES,
                'reminder_types' => LeadReminder::TYPES,
                'branches'       => $branchesQuery->orderBy('name')->get(['id', 'name']),
                'users'          => $this->restrictUserCollectionToOwnBranch(
                        $this->visibility->visibleAssignableUsers(request()->user()),
                        request()->user()
                    )->map(fn($user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ])->values(),
                'products'       => tap(Product::query(), fn($query) => $this->visibility->applyProductVisibility($query, request()->user()))
                    ->orderBy('product_name')
                    ->get(['id', 'product_name as name']),
                // Backs the Filter Leads sheet's "Pre Sales Exec" searchable
                // dropdown — mirrors web's LeadController@index
                // $preSaleExecutives query exactly (users with a pre_sale-like
                // role, or who are already set as some lead's
                // pre_sale_executive_id), just company-scoped like every
                // other list in this meta() response.
                'pre_sale_executives' => User::query()
                    ->where('is_active', true)
                    ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
                    ->where(function ($q) {
                        $q->whereHas('roles', fn ($rq) => $rq->where('name', 'like', '%pre_sale%')->orWhere('display_name', 'like', '%pre%sale%'))
                          ->orWhereIn('id', Lead::query()->whereNotNull('pre_sale_executive_id')->distinct()->pluck('pre_sale_executive_id'));
                    })
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    /**
     * GET /mobile/leads/employees-search?q=&page= — paginated, searchable
     * "Assigned To" / employee lookup for search-as-you-type pickers
     * app-wide (Lead create/edit, Lead List filter, Lead Products filter,
     * Call Updates filter, CRM Tasks filter, Price Requests filter, CST
     * Allocation filter). Mirrors ReportApiController::leadsSearchApi()'s
     * response shape (id/name rows, 20/page, meta.has_more) but for
     * employees — built as its own paginated query (not by reusing
     * DataVisibilityService::visibleAssignableUsers(), which loads the
     * full list into memory; the whole point of this endpoint is to never
     * do that) while applying the exact same sales/CRM department+role
     * filter and hierarchy visibility (visibleUserIds()) that method uses,
     * plus this file's own branch-isolation trait on top. Mobile-only.
     */
    public function employeesSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q'    => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $companyId = $this->visibility->companyIdFor($user);
        $q = trim((string) $request->input('q', ''));

        $query = User::query()
            ->where('is_active', true)
            ->where(function (\Illuminate\Database\Eloquent\Builder $sub) {
                $sub->whereHas('roles.department', function (\Illuminate\Database\Eloquent\Builder $q) {
                    $q->whereIn(DB::raw('LOWER(name)'), [
                        'sales', 'crm', 'business development', 'marketing', 'telecalling',
                    ])->orWhereIn(DB::raw('LOWER(REPLACE(name, " ", "_"))'), [
                        'sales', 'crm', 'business_development', 'marketing', 'telecalling',
                    ]);
                })->orWhereHas('roles', function (\Illuminate\Database\Eloquent\Builder $q) {
                    $q->whereIn(DB::raw('LOWER(name)'), [
                        'sales_manager', 'sales_executive', 'sales_tl', 'sales_intern', 'bde', 'business_development_executive', 'telecaller',
                    ])->orWhereIn(DB::raw('LOWER(REPLACE(name, " ", "_"))'), [
                        'sales_manager', 'sales_executive', 'sales_tl', 'sales_intern', 'bde', 'business_development_executive', 'telecaller',
                    ]);
                });
            })
            ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId));

        $visibleIds = ($user && $user->hasPreSalesLikeRole()) ? null : $this->visibility->visibleUserIds($user);
        if ($visibleIds !== null) {
            $query->whereIn('id', $visibleIds);
        }

        $query = $this->scopeEmployeeQueryToOwnBranch($query, $user);

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $employees = $query->orderBy('name')->paginate(20, ['id', 'name']);

        return response()->json([
            'status' => true,
            'data' => collect($employees->items())->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
                'has_more'     => $employees->currentPage() < $employees->lastPage(),
            ],
        ]);
    }

    /**
     * Branch-restriction guard for Lead create/update/list (mobile-only —
     * see "Fix Branch Field Access in Lead Add, Edit & Filter"). Mirrors
     * canAssignTo()'s pattern: null/omitted is always fine (defaults to the
     * user's own branch downstream), company-wide/admin roles (System
     * Admin, Company Admin, the top-tier "Branch Admin" role, CBO/COO — the
     * same set DataVisibilityService::isCompanyWideUser() already treats as
     * unrestricted everywhere else) may target any branch, and everyone
     * else may only target a branch they themselves belong to
     * (User::getMyBranchIds() — usually just one, but a user can be linked
     * to more than one branch via the branch_user pivot). This is enforced
     * here (not just hidden in the mobile UI) so a non-admin user can't
     * bypass the restriction by sending an arbitrary branch_id directly.
     *
     * Company isolation: "company-wide" only ever meant "every branch
     * *within this user's own company*", not literally every branch row in
     * the shared `branches` table. `exists:branches,id` validation checks
     * existence only, with no company scope, so without the explicit
     * company_id check below a company-wide user could target a branch_id
     * belonging to a different company entirely — see "Lead Status & Source
     * – Company and Branch-wise Data Filtering", section 4.
     */
    private function canAssignBranch(?int $branchId, User $user): bool
    {
        if ($branchId === null) {
            return true;
        }

        if ($user->company_id) {
            $branchCompanyId = Branch::withoutGlobalScope('company')
                ->whereKey($branchId)
                ->value('company_id');

            if ($branchCompanyId !== null && (int) $branchCompanyId !== (int) $user->company_id) {
                return false;
            }
        }

        if ($this->visibility->isCompanyWideUser($user)) {
            return true;
        }

        return in_array($branchId, $user->getMyBranchIds(), true);
    }

    // =========================================================================
    // PRIVATE FORMATTERS
    // =========================================================================

    private function formatLeadSummary(Lead $lead): array
    {
        $dealValue = $lead->products->sum(function ($product) {
            return ($product->total_price ?? 0) * ($product->quantity ?? 0);
        });

        $status = $lead->lead_status;
        $statusLabel = $lead->status_label;
        $statusColor = $lead->status_color;

        if (empty($status) || empty($statusLabel)) {
            $convertedStatusIds = LeadProduct::convertedStatusIds();
            $isConverted = in_array((string)$lead->lead_status_id, array_map('strval', $convertedStatusIds), true)
                || in_array(strtolower((string)$lead->lead_status), ['won', 'converted'], true)
                || $lead->products->contains(fn($p) => in_array(strtolower((string)$p->product_status), ['won', 'converted'], true) || in_array((string)$p->lead_status_id, array_map('strval', $convertedStatusIds), true));

            if ($isConverted) {
                $status = 'converted';
                $statusLabel = 'Converted';
                $statusColor = Lead::STATUS_COLORS['won'] ?? ['bg' => '#f0fdf4', 'text' => '#16a34a', 'border' => '#bbf7d0'];
            } elseif ($lead->relationLoaded('leadStatus') && $lead->leadStatus) {
                $status = $lead->leadStatus->name;
                $statusLabel = $lead->leadStatus->name;
            } elseif ($lead->lead_status_id && isset(Lead::statusOptions()[$lead->lead_status_id])) {
                $status = Lead::statusOptions()[$lead->lead_status_id];
                $statusLabel = $status;
            } elseif ($lead->products->first()?->product_status) {
                $pStatus = $lead->products->first()->product_status;
                $status = $pStatus;
                $statusLabel = ucfirst(str_replace('_', ' ', $pStatus));
            }
        }

        return [
            'id'                   => $lead->id,
            'company_name'         => $lead->company_name,
            'contact_name'         => $lead->contact_name,
            'mobile_number'        => $lead->mobile_number,
            'email'                => $lead->email,
            'lead_date'            => $lead->lead_date?->toDateString(),
            'lead_source_id'       => $lead->leadSource?->id ?: $lead->lead_source_id,
            'lead_source'          => $lead->leadSource?->name ?: ($lead->lead_source ?: ($lead->products->first()?->leadSource?->name ?: $lead->source_label)),
            'source_label'         => $lead->leadSource?->name ?: ($lead->lead_source ?: ($lead->products->first()?->leadSource?->name ?: $lead->source_label)),
            'lead_status'          => $status ?: 'new',
            'status_label'         => $statusLabel ?: 'New',
            'status_color'         => $statusColor,
            'priority'             => $lead->priority,
            'priority_label'       => $lead->priority_label,
            'priority_color'       => $lead->priority_color,
            'product_id'           => $lead->product_id,
            'product_name'         => $lead->product?->product_name ?? $lead->product_name,
            'deal_value'           => $dealValue,
            'formatted_deal_value' => number_format($dealValue, 2, '.', ''),
            'branch'               => $lead->branch
                ? ['id' => $lead->branch->id, 'name' => $lead->branch->name]
                : null,
            'assigned_to'          => $lead->assignedTo
                ? ['id' => $lead->assignedTo->id, 'name' => $lead->assignedTo->name]
                : null,
            'pre_sale_executive'   => $lead->preSaleExecutive
                ? ['id' => $lead->preSaleExecutive->id, 'name' => $lead->preSaleExecutive->name]
                : null,
            'created_at'           => $lead->created_at?->toIso8601String(),
        ];
    }

    private function formatLeadDetail(Lead $lead): array
    {
        $base = $this->formatLeadSummary($lead);

        return array_merge($base, [
            'remarks'    => $lead->remarks,
            'created_by' => $lead->createdBy
                ? ['id' => $lead->createdBy->id, 'name' => $lead->createdBy->name]
                : null,
            'updated_at' => $lead->updated_at?->toIso8601String(),

            // ── Call Updates ──────────────────────────────────────────────────
            'call_updates' => $lead->relationLoaded('callUpdates')
                ? $lead->callUpdates->map(fn($c) => [
                    'id'               => $c->id,
                    'called_at'        => $c->called_at?->format('d M Y, h.i A'),
                    'call_type'        => $c->call_type,
                    'call_type_label'  => $c->call_type_label,
                    'duration_minutes' => $c->duration_minutes,
                    'outcome'          => $c->outCome?->name ?? $c->outcome,
                    'outcome_label'    => $c->outComeSubCategory?->name ?? $c->outcome_subcategory,
                    'outcome_color'    => $c->outcome_color,
                    'notes'            => $c->notes,
                    'next_follow_up'   => $c->next_follow_up?->format('d M Y, h.i A'),
                    'user'             => $c->user
                        ? ['id' => $c->user->id, 'name' => $c->user->name]
                        : null,
                ])->values()
                : [],

            // ── Reminders ─────────────────────────────────────────────────────
            'reminders' => $lead->relationLoaded('reminders')
                ? $lead->reminders->map(fn($r) => [
                    'id'           => $r->id,
                    'title'        => $r->title,
                    'description'  => $r->description,
                    // Naive "Y-m-d H:i:s" (no timezone offset) — matches the
                    // web reference and the Reminders & Tasks module. Using
                    // toIso8601String() here appended a "+05:30" offset that
                    // Dart's DateTime.parse() converted to UTC, shifting the
                    // displayed date back a day. remainder_time (previously
                    // missing from this response) carries the actual time.
                    'remind_at'      => optional($r->remind_at)->format('Y-m-d H:i:s'),
                    // Explicit ->format() — the raw Carbon instance serializes
                    // to UTC by default (Carbon::jsonSerialize()), which
                    // silently shifted the displayed time back by the app's
                    // UTC+5:30 offset.
                    'remainder_time' => optional($r->remainder_time)->format('H:i:s'),
                    'type'         => $r->type,
                    'type_label'   => $r->type_label,
                    'type_icon'    => $r->type_icon,
                    'priority'     => $r->priority,
                    'is_completed' => (bool) $r->is_completed,
                    'is_overdue'   => $r->is_overdue,
                    'completed_at' => optional($r->completed_at)->format('Y-m-d H:i:s'),
                    'user'         => $r->user
                        ? ['id' => $r->user->id, 'name' => $r->user->name]
                        : null,
                ])->values()
                : [],

            // ── Products with Payments ────────────────────────────────────────
            'products' => $lead->relationLoaded('products')
                ? $lead->products->map(fn($p) => [
                    'id'                => $p->id,
                    'product_name'      => $p->product_name,
                    'product_status'    => $p->product_status,
                    'lead_status_id'    => $p->lead_status_id,
                    'lead_status_name'  => $p->leadStatus?->name,
                    'description'       => $p->description,
                    'unit_price'        => $p->unit_price,
                    'quantity'          => $p->quantity,
                    'discount_percent'  => $p->discount_percent,
                    'total_price'       => $p->total_price,
                    'payment_status'    => $p->payment_status,
                    'amount_paid'       => $p->amount_paid,
                    'amount_pending'    => $p->amount_pending,
                    'production'        => $this->formatProductionInitiation($p->latestProductionInitiation),

                    'payments' => $p->relationLoaded('payments')
                        ? $p->payments->map(fn($pay) => [
                            'id'               => $pay->id,
                            'amount'           => $pay->amount,
                            'formatted_amount' => $pay->formatted_amount,
                            'payment_mode'     => $pay->payment_mode,
                            'mode_label'       => $pay->mode_label,
                            'mode_icon'        => $pay->mode_icon,
                            'mode_color'       => $pay->mode_color,
                            'payment_date'     => $pay->payment_date?->format('d M Y'),
                            'reference_number' => $pay->reference_number,
                            'notes'            => $pay->notes,
                            'recorded_by'      => $pay->recordedBy
                                ? ['id' => $pay->recordedBy->id, 'name' => $pay->recordedBy->name]
                                : null,
                        ])->values()
                        : [],
                ])->values()
                : [],

            // ── Quotations ────────────────────────────────────────────────────
            'quotations' => $lead->relationLoaded('quotations')
                ? $lead->quotations->map(fn($q) => [
                    'id'               => $q->id,
                    'quotation_number' => $q->quotation_number,
                    'quotation_date'   => $q->quotation_date?->toDateString(),
                    'valid_until'      => $q->valid_until?->toDateString(),
                    'status'           => $q->status,
                    'status_label'     => $q->status_label,
                    'subtotal'         => $q->subtotal,
                    'discount_amount'  => $q->discount_amount,
                    'tax_percent'      => $q->tax_percent,
                    'tax_amount'       => $q->tax_amount,
                    'grand_total'      => $q->grand_total,
                    'terms_conditions' => $q->terms_conditions,
                    'notes'            => $q->notes,
                    'created_by'       => $q->createdBy
                        ? ['id' => $q->createdBy->id, 'name' => $q->createdBy->name]
                        : null,
                    'created_at'       => $q->created_at?->toIso8601String(),
                    'items'            => $q->items ?? [],
                ])->values()
                : [],

            'custom_field_values' => $lead->customFieldValues->map(fn($v) => [
                'id'                 => $v->id,
                'lead_id'            => $v->lead_id,
                'lead_form_field_id' => $v->lead_form_field_id,
                'value'              => $v->value,
                // Resolved absolute URL for file-type fields — mirrors
                // leads/form.blade.php's existing-file link (asset() on the
                // stored 'uploads/custom_fields/...' path) and the same
                // *_url convention used everywhere else in this API
                // (attendance_photo_url, attachment_url, photograph_url).
                // Null for non-file fields / empty values.
                'file_url'           => ($v->field?->field_type === 'file' && !empty($v->value))
                    ? (Str::startsWith($v->value, ['uploads/', 'http'])
                        ? asset($v->value)
                        : asset('storage/' . $v->value))
                    : null,
                'field'              => $v->field ? [
                    'label'      => $v->field->label,
                    'field_type' => $v->field->field_type,
                ] : null,
            ])->values(),

            // ── CST & Weekly Updates ─────────────────────────────────────────
            // Visibility mirrors pages/leads/show.blade.php: viewing the tab is
            // gated on the company-level allowsCstUpdates() flag, while adding
            // a new update is restricted to Customer Success Team members
            // (isCustomerSuccessUser()).
            'can_view_cst_updates' => (bool) (auth()->user()?->allowsCstUpdates()),
            'can_add_cst_update'   => (bool) (auth()->user()?->isCustomerSuccessUser()),
            'cst_updates_count'         => $lead->relationLoaded('cstUpdates') ? $lead->cstUpdates->count() : 0,
            'cst_only_count'            => $lead->relationLoaded('cstUpdates') ? $lead->cstUpdates->where('update_type', 'cst_update')->count() : 0,
            'weekly_only_count'         => $lead->relationLoaded('cstUpdates') ? $lead->cstUpdates->where('update_type', 'weekly_update')->count() : 0,
            'review_only_count'         => $lead->relationLoaded('cstUpdates') ? $lead->cstUpdates->where('update_type', 'review')->count() : 0,
            'escalation_only_count'     => $lead->relationLoaded('cstUpdates') ? $lead->cstUpdates->where('update_type', 'escalation')->count() : 0,
            'cst_updates' => $lead->relationLoaded('cstUpdates')
                ? $lead->cstUpdates->map(fn($u) => [
                    'id'                => $u->id,
                    'update_type'       => $u->update_type,
                    'update_type_label' => $u->update_type_label,
                    'notes'             => $u->notes,
                    'product'           => $u->product?->product
                        ? ['id' => $u->product->product->id, 'name' => $u->product->product->product_name]
                        : null,
                    'user'              => $u->user
                        ? ['id' => $u->user->id, 'name' => $u->user->name]
                        : null,
                    'created_at'        => $u->created_at?->toIso8601String(),
                ])->values()
                : [],
        ]);
    }

    private function formatProductionInitiation($history): ?array
    {
        if (! $history) {
            return null;
        }

        $currentStage = $history->employee_allocation_status
            ?: $history->project_allocation_status
            ?: $history->production_approval_status
            ?: $history->status
            ?: 'initiated';

        $isRejected = in_array(strtolower((string) $history->status), ['rejected', 'reject']);
        $remarks = $history->production_approval_remarks ?: $history->remarks;

        return [
            'id'            => $history->id,
            'status'        => $history->status,
            'current_stage' => $currentStage,
            'is_rejected'   => $isRejected,
            'remarks'       => $remarks,
            'initiated_by'  => $history->initiatedBy
                ? ['id' => $history->initiatedBy->id, 'name' => $history->initiatedBy->name]
                : null,
            'initiated_at'  => $history->created_at?->toIso8601String(),
            'ovp' => [
                'reviewed_by' => $history->reviewedBy
                    ? ['id' => $history->reviewedBy->id, 'name' => $history->reviewedBy->name]
                    : null,
                'reviewed_at' => $history->reviewed_at?->toIso8601String(),
            ],
            'production_approval' => [
                'status'      => $history->production_approval_status,
                'reviewed_by' => $history->productionApprovalReviewedBy
                    ? ['id' => $history->productionApprovalReviewedBy->id, 'name' => $history->productionApprovalReviewedBy->name]
                    : null,
                'reviewed_at' => $history->production_approval_reviewed_at?->toIso8601String(),
            ],
            'team_lead_allocation' => [
                'status'       => $history->project_allocation_status,
                'allocated_by' => $history->projectAllocatedBy
                    ? ['id' => $history->projectAllocatedBy->id, 'name' => $history->projectAllocatedBy->name]
                    : null,
                'allocated_at' => $history->project_allocated_at?->toIso8601String(),
            ],
            'employee_allocation' => [
                'status'       => $history->employee_allocation_status,
                'allocated_by' => $history->employeeAllocatedBy
                    ? ['id' => $history->employeeAllocatedBy->id, 'name' => $history->employeeAllocatedBy->name]
                    : null,
                'allocated_at' => $history->employee_allocated_at?->toIso8601String(),
            ],
            'department' => $history->department
                ? ['id' => $history->department->id, 'name' => $history->department->name]
                : null,
            'team_member_count' => count($history->project_allocated_employee_user_ids ?? []),
            'project_updates' => $history->projectUpdates->map(function ($u) {
                $content = (string) $u->content;

                $typeMetaMap = [
                    'production_update' => ['label' => 'Production Update', 'title' => 'Execution Progress'],
                    'meeting_update'    => ['label' => 'Meeting Update', 'title' => 'Discussion Notes'],
                    'weekly_update'     => ['label' => 'Weekly Update', 'title' => 'Weekly Summary'],
                ];

                $typeMeta = $typeMetaMap[$u->type] ?? [
                    'label' => ucwords(str_replace('_', ' ', (string) $u->type)),
                    'title' => 'Project Update',
                ];

                $isLifecycle = str_contains($content, 'Production Initiation')
                    || str_contains($content, 'OVP Review')
                    || str_contains($content, 'Production Approval')
                    || str_contains($content, 'Team Lead Allocation')
                    || str_contains($content, 'Team Member Allocation')
                    || str_contains($content, 'OVP Executive Allocation')
                    || str_contains($content, 'Allocation');

                $remarks = null;
                $remarksTitle = null;
                if (preg_match('/<span[^>]*>\s*([^<]*(?:Remarks|Reason)[^<]*)\s*<\/span>\s*<div[^>]*>(.*?)<\/div>/is', $content, $m)) {
                    $remarksTitle = trim(strip_tags($m[1]));
                    $remarks = trim(strip_tags($m[2]));
                }

                $badgeText = null;
                if (preg_match('/<span[^>]*style="[^"]*background:[^"]*"[^>]*>\s*([^<]+)\s*<\/span>/is', $content, $m)) {
                    $badgeText = trim(strip_tags($m[1]));
                }

                $actorText = null;
                if (preg_match('/<(?:span|div)[^>]*style="[^"]*font-weight:\s*700[^"]*"[^>]*>\s*((?:Reviewed|Initiated|Allocated)\s+by\s+[^<]+)\s*<\/(?:span|div)>/is', $content, $m)) {
                    $actorText = trim(strip_tags($m[1]));
                }

                $formattedDate = null;
                if (preg_match('/(?:📅|📅\s*)([0-9]{1,2}\s+[A-Za-z]+\s+[0-9]{4},\s+[0-9]{1,2}:[0-9]{2}\s*(?:AM|PM))/iu', $content, $m)) {
                    $formattedDate = trim($m[1]);
                } else {
                    $formattedDate = $u->created_at?->format('d M Y, h:i A');
                }

                // Structured initiation details
                $initiationDetails = null;
                if (str_contains($content, 'Initiation Details')) {
                    $initiationDetails = [];
                    if (preg_match('/<span[^>]*>\s*Product Name\s*<\/span>\s*<span[^>]*>(.*?)<\/span>/is', $content, $m)) {
                        $initiationDetails['product_name'] = trim(strip_tags($m[1]));
                    }
                    if (preg_match('/<span[^>]*>\s*Department\s*<\/span>\s*<span[^>]*>(.*?)<\/span>/is', $content, $m)) {
                        $initiationDetails['department'] = trim(strip_tags($m[1]));
                    }
                    if (preg_match('/<span[^>]*>\s*Working Days\s*<\/span>\s*<span[^>]*>(.*?)<\/span>/is', $content, $m)) {
                        $initiationDetails['working_days'] = trim(strip_tags($m[1]));
                    }
                    if (preg_match('/<span[^>]*>\s*UI Available\s*<\/span>\s*<span[^>]*>(.*?)<\/span>/is', $content, $m)) {
                        $initiationDetails['ui_available'] = trim(strip_tags($m[1]));
                    }
                    if (preg_match('/<span[^>]*>\s*Requirements\s*\/\s*Scope\s*<\/span>\s*<div[^>]*>(.*?)<\/div>/is', $content, $m)) {
                        $initiationDetails['requirements'] = trim(strip_tags($m[1]));
                    }
                    if (preg_match('/Requirements\s*\/\s*Scope.*?<a\s+href="([^"]+)"[^>]*>(.*?)<\/a>/is', $content, $m)) {
                        $initiationDetails['attachment_url'] = $m[1];
                        $name = trim(strip_tags($m[2]));
                        $name = preg_replace('/^[\x{1F4CE}\s]+/u', '', $name);
                        $initiationDetails['attachment_name'] = $name ?: 'View Attachment';
                    }
                }

                // Structured form responses
                $formResponses = [];
                if (preg_match('/<table[^>]*>(.*?)<\/table>/is', $content, $tableMatch)) {
                    if (preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/is', $tableMatch[1], $rows, PREG_SET_ORDER)) {
                        foreach ($rows as $row) {
                            $label = trim(strip_tags($row[1]));
                            $valCell = $row[2];
                            $fileUrl = null;
                            $value = null;
                            if (preg_match('/<a\s+href="([^"]+)"[^>]*>(.*?)<\/a>/is', $valCell, $linkMatch)) {
                                $fileUrl = $linkMatch[1];
                                $fileName = trim(strip_tags($linkMatch[2]));
                                $fileName = preg_replace('/^[\x{1F4CE}\s]+/u', '', $fileName);
                                $value = $fileName ?: 'View Attachment';
                            } else {
                                $value = trim(strip_tags($valCell));
                            }
                            $formResponses[] = [
                                'label'    => $label,
                                'value'    => $value,
                                'file_url' => $fileUrl,
                            ];
                        }
                    }
                }

                return [
                    'id'                 => $u->id,
                    'type'               => $u->type,
                    'type_label'         => $typeMeta['label'],
                    'type_title'         => $typeMeta['title'],
                    'content'            => $u->content,
                    'is_lifecycle'       => $isLifecycle,
                    'badge_text'         => $badgeText,
                    'actor_text'         => $actorText,
                    'formatted_date'     => $formattedDate,
                    'remarks_title'      => $remarksTitle,
                    'remarks'            => $remarks,
                    'initiation_details' => $initiationDetails,
                    'form_responses'     => $formResponses,
                    'created_by'         => $u->createdBy
                        ? ['id' => $u->createdBy->id, 'name' => $u->createdBy->name]
                        : null,
                    'created_at'         => $u->created_at?->toDateTimeString(),
                ];
            })->values(),
        ];
    }

    public function syncCustomFields(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $this->syncCustomFieldValues($lead, $request->input('custom_fields', []));

        return response()->json([
            'status'  => true,
            'message' => 'Custom fields updated.',
        ]);
    }

    protected function syncCustomFieldValues(Lead $lead, array $submittedValues): void
    {
        $fields = LeadFormField::query()
            ->where('is_active', true)
            ->where('show_on_lead_create', true)
            ->where(function ($query) use ($lead) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $lead->branch_id);
            })
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
        $allowedFieldIds = $fields->pluck('id')->all();

        LeadFieldValue::query()
            ->where('lead_id', $lead->id)
            ->whereNotIn('lead_form_field_id', $allowedFieldIds)
            ->delete();

        foreach ($fields as $field) {
            $normalizedValue = null;

            if ($field->field_type === 'file') {
                $fileInputKey = "custom_fields.{$field->id}";
                if (request()->hasFile($fileInputKey)) {
                    $uploadedFile = request()->file($fileInputKey);
                    $targetDir = public_path('uploads/custom_fields');
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $extension = $uploadedFile->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid('cf_') . ($extension ? '.' . $extension : '');
                    $uploadedFile->move($targetDir, $filename);
                    $normalizedValue = 'uploads/custom_fields/' . $filename;
                } else {
                    $existingFile = request()->input("existing_custom_files.{$field->id}");
                    $normalizedValue = $existingFile ?: ($submittedValues[$field->id] ?? null);
                }
            } else {
                $submittedValue = $submittedValues[$field->id] ?? null;

                if (is_array($submittedValue)) {
                    $submittedValue = array_values(array_filter($submittedValue, fn($value) => $value !== null && $value !== ''));
                }

                $normalizedValue = is_array($submittedValue)
                    ? json_encode($submittedValue)
                    : ($submittedValue !== null ? trim((string) $submittedValue) : null);
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

    public function customFields(): JsonResponse
    {
        $fields = LeadFormField::where('is_active', true)
            ->where('show_on_lead_create', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return response()->json(['status' => true, 'data' => $fields]);
    }

    public function leadProductFunction(Request $request)
    {
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate   = now()->endOfMonth()->toDateString();

        $allDates = $request->boolean('all_dates') || $request->input('quick_date') === 'all';

        // Resolve quick_date presets (matching web resolveQuickDate)
        $quickDate = $request->input('quick_date') ?? $request->input('quick_select');
        if ($quickDate === 'all') {
            $allDates = true;
            $dateFrom = null;
            $dateTo   = null;
        } elseif ($quickDate && $quickDate !== 'custom') {
            $dates = match ($quickDate) {
                'today'               => [now()->toDateString(), now()->toDateString()],
                'week', 'this_week'   => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                'month', 'this_month' => [$defaultFromDate, $defaultToDate],
                'quarter'             => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
                'year', 'this_year'   => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
                default               => null,
            };
            if ($dates) {
                $dateFrom = $dates[0];
                $dateTo   = $dates[1];
            } else {
                $dateFrom = $request->filled('date_from') ? $request->date_from : $defaultFromDate;
                $dateTo   = $request->filled('date_to')   ? $request->date_to   : $defaultToDate;
            }
        } else {
            $hasDateFilter = $request->filled('date_from') || $request->filled('date_to');
            if ($allDates) {
                $dateFrom = null;
                $dateTo   = null;
            } elseif ($hasDateFilter) {
                $dateFrom = $request->input('date_from');
                $dateTo   = $request->input('date_to');
            } else {
                $dateFrom = $defaultFromDate;
                $dateTo   = $defaultToDate;
            }
        }

        $query = LeadProduct::query()
            ->with(['lead.branch', 'lead.assignedTo', 'product', 'leadStatus', 'payments'])
            ->whereHas('lead')
            ->latest('created_at');

        // Company/branch/assignment scoping — web's productsIndex() applies
        // this via $this->visibility->applyLeadRelationVisibility($query);
        $this->visibility->applyLeadRelationVisibility($query, 'lead', $request->user());

        // ── Search (matches web search placeholder: client name, mobile, Lead ID, company, email) ──
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $cleanId = preg_replace('/[^0-9]/', '', $search);

            $query->where(function ($q) use ($search, $cleanId) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('product_name', 'like', "%{$search}%")
                            ->orWhere('package_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead', function ($leadQuery) use ($search, $cleanId) {
                        $leadQuery->where(function ($sq) use ($search, $cleanId) {
                            $sq->where('company_name', 'like', "%{$search}%")
                                ->orWhere('contact_name', 'like', "%{$search}%")
                                ->orWhere('mobile_number', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");

                            if ($cleanId !== '' && is_numeric($cleanId)) {
                                $sq->orWhere('id', (int) $cleanId);
                            }
                        });
                    });
            });
        }

        // ── Filters ─────────────────────────────────────────────
        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->filled('mobile_number')) {
            $query->whereHas('lead', fn($lq) =>
                $lq->where('mobile_number', 'like', '%' . $request->mobile_number . '%'));
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('lead', fn($lq) =>
                $lq->where('branch_id', $request->branch_id));
        }

        if ($request->filled('assigned_to')) {
            $query->whereHas('lead', fn($lq) =>
                $lq->where('assigned_to', $request->assigned_to));
        }

        // Product Status — mirrors web's productsIndex(): accepts either numeric id or string name,
        // and detects if this is filtering by converted status.
        $isConvertedStatusFilter = false;
        if ($request->filled('product_status')) {
            $statusVal = $request->product_status;
            if (is_numeric($statusVal)) {
                $statusRecord = LeadStatus::find($statusVal);
                $statusName = $statusRecord ? strtolower($statusRecord->name) : null;
                $isConvertedStatusFilter = ($statusName === 'converted');
                $query->where(function ($q) use ($statusVal, $statusName) {
                    $q->where('lead_status_id', (int) $statusVal);
                    if ($statusName) {
                        $q->orWhere('product_status', $statusName);
                    }
                });
            } else {
                $statusRecord = LeadStatus::where('name', 'like', $statusVal)->first();
                $statusId = $statusRecord?->id;
                $isConvertedStatusFilter = (strtolower($statusVal) === 'converted' || strtolower($statusRecord?->name ?? '') === 'converted');
                $query->where(function ($q) use ($statusVal, $statusId) {
                    $q->where('product_status', $statusVal);
                    if ($statusId) {
                        $q->orWhere('lead_status_id', $statusId);
                    }
                });
            }
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('product_active')) {
            $status = $request->product_active;
            $query->whereHas('product', fn ($q) => $q->where('status', $status));
        }

        // ── Date Filtering (strictly mirrors web productsIndex()) ──
        if (!$allDates && $dateFrom) {
            if ($isConvertedStatusFilter) {
                $query->where(function ($q) use ($dateFrom) {
                    $q->whereDate('converted_at', '>=', $dateFrom)
                      ->orWhere(function ($sub) use ($dateFrom) {
                          $sub->whereNull('converted_at')
                              ->where(function ($sub2) use ($dateFrom) {
                                  $sub2->whereDate('created_at', '>=', $dateFrom)
                                       ->orWhereHas('payments', fn ($pq) => $pq->whereDate('payment_date', '>=', $dateFrom))
                                       ->orWhereHas('lead', fn ($lq) => $lq->whereDate('lead_date', '>=', $dateFrom)->orWhereDate('created_at', '>=', $dateFrom));
                              });
                      });
                });
            } else {
                $query->where(function ($q) use ($dateFrom) {
                    $q->whereDate('created_at', '>=', $dateFrom)
                      ->orWhereDate('converted_at', '>=', $dateFrom)
                      ->orWhereHas('payments', fn ($pq) => $pq->whereDate('payment_date', '>=', $dateFrom))
                      ->orWhereHas('lead', fn ($lq) => $lq->whereDate('lead_date', '>=', $dateFrom)->orWhereDate('created_at', '>=', $dateFrom));
                });
            }
        }

        if (!$allDates && $dateTo) {
            if ($isConvertedStatusFilter) {
                $query->where(function ($q) use ($dateTo) {
                    $q->whereDate('converted_at', '<=', $dateTo)
                      ->orWhere(function ($sub) use ($dateTo) {
                          $sub->whereNull('converted_at')
                              ->where(function ($sub2) use ($dateTo) {
                                  $sub2->whereDate('created_at', '<=', $dateTo)
                                       ->orWhereHas('payments', fn ($pq) => $pq->whereDate('payment_date', '<=', $dateTo))
                                       ->orWhereHas('lead', fn ($lq) => $lq->whereDate('lead_date', '<=', $dateTo)->orWhereDate('created_at', '<=', $dateTo));
                              });
                      });
                });
            } else {
                $query->where(function ($q) use ($dateTo) {
                    $q->whereDate('created_at', '<=', $dateTo)
                      ->orWhereDate('converted_at', '<=', $dateTo)
                      ->orWhereHas('payments', fn ($pq) => $pq->whereDate('payment_date', '<=', $dateTo))
                      ->orWhereHas('lead', fn ($lq) => $lq->whereDate('lead_date', '<=', $dateTo)->orWhereDate('created_at', '<=', $dateTo));
                });
            }
        }

        // ── Payment-based scope (Home Dashboard Payment Financials cards) ──
        $paymentFilter = $request->filled('payment_filter') ? $request->payment_filter : null;

        if (in_array($paymentFilter, ['converted', 'received', 'pending'], true)) {
            $allRows = (clone $query)->get();
            $convertedStatusIds = LeadProduct::convertedStatusIds();
            $convertedRows = $allRows
                ->filter(fn($lp) => $lp->isConvertedProduct($convertedStatusIds))
                ->values();

            $productIds = $convertedRows->pluck('id');
            $paidByProduct = LeadProductPayment::whereIn('lead_product_id', $productIds)
                ->select('lead_product_id', DB::raw('SUM(amount) as total'))
                ->groupBy('lead_product_id')
                ->pluck('total', 'lead_product_id');
            $receivedFor = fn($lp) => (float) ($paidByProduct[$lp->id] ?? $lp->amount_paid);
            $pendingFor  = fn($lp) => max(0, (float) $lp->total_price - $receivedFor($lp));

            $convertedValue = (float) $convertedRows->sum('total_price');
            $receivedTotal  = (float) $convertedRows->sum($receivedFor);
            $pendingTotal   = max(0, $convertedValue - $receivedTotal);
            $stats = [
                'total_products' => $convertedRows->count(),
                'total_value'    => $convertedValue,
                'received'       => $receivedTotal,
                'pending'        => $pendingTotal,
            ];

            if ($paymentFilter === 'received') {
                $rows = $convertedRows->filter(fn($lp) => $receivedFor($lp) > 0)->values();
            } elseif ($paymentFilter === 'pending') {
                $rows = $convertedRows->filter(fn($lp) => $pendingFor($lp) > 0)->values();
            } else {
                $rows = $convertedRows;
            }

            $page    = max(1, (int) $request->input('page', 1));
            $perPage = 15;
            $leadProducts = new \Illuminate\Pagination\LengthAwarePaginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        } else {
            // ── Stats (matches web productsIndex() exactly) ──────────────
            $statsBase = (clone $query)->with('payments');
            $statsRows = $statsBase->get();
            $stats = [
                'total_products' => $statsRows->count(),
                'total_value'    => (float) $statsRows->sum('total_price'),
                'received'       => (float) $statsRows->sum(fn($lp) => $lp->amount_paid),
                'pending'        => (float) $statsRows->sum(fn($lp) => $lp->amount_pending),
            ];

            // ── Paginate ─────────────────────────────────────────────
            $leadProducts = $query->paginate(15)->withQueryString();
        }

        // Transform paginated items to enrich with computed amounts, status config, and dates
        $leadProducts->through(function ($lp) {
            return [
                'id'                       => $lp->id,
                'company_id'               => $lp->company_id,
                'lead_id'                  => $lp->lead_id,
                'raw_lead_id'              => $lp->lead_id,
                'product_name'             => $lp->product_name,
                'description'              => $lp->description,
                'unit_price'               => $lp->unit_price,
                'quantity'                 => $lp->quantity,
                'qty'                      => $lp->quantity ?? 1,
                'discount_percent'         => $lp->discount_percent,
                'total_price'              => $lp->total_price,
                'amount_paid'              => $lp->amount_paid,
                'amount_pending'           => $lp->amount_pending,
                'payment_status'           => $lp->payment_status,
                'payment_date'             => $lp->payment_date,
                'payment_mode'             => $lp->payment_mode,
                'payment_notes'            => $lp->payment_notes,
                'converted_at'             => $lp->converted_at?->toIso8601String(),
                'converted_date_formatted' => $lp->converted_at?->format('d M Y'),
                'created_at'               => $lp->created_at?->toIso8601String(),
                'updated_at'               => $lp->updated_at?->toIso8601String(),
                'product_status'           => $lp->product_status,
                'lead_status_id'           => $lp->lead_status_id,
                'lead_source_id'           => $lp->lead_source_id,
                'product_id'               => $lp->product_id,
                'deal_name'                => $lp->deal_name,
                'remarks'                  => $lp->remarks,
                'created_by'               => $lp->created_by,
                'status_label'             => $lp->status_label,
                'product_status_key'       => $lp->product_status_key,
                'product_status_config'    => $lp->product_status_config,
                'branch_name'              => $lp->lead?->branch?->name,
                'lead'                     => $lp->lead ? [
                    'id'            => $lp->lead->id,
                    'company_id'    => $lp->lead->company_id,
                    'company_name'  => $lp->lead->company_name,
                    'contact_name'  => $lp->lead->contact_name,
                    'mobile_number' => $lp->lead->mobile_number,
                    'email'         => $lp->lead->email,
                    'branch_id'     => $lp->lead->branch_id,
                    'branch'        => $lp->lead->branch ? [
                        'id'   => $lp->lead->branch->id,
                        'name' => $lp->lead->branch->name,
                        'code' => $lp->lead->branch->code,
                    ] : null,
                    'assigned_to'   => $lp->lead->assignedTo ? [
                        'id'    => $lp->lead->assignedTo->id,
                        'name'  => $lp->lead->assignedTo->name,
                        'email' => $lp->lead->assignedTo->email,
                    ] : null,
                ] : null,
                'product'                  => $lp->product ? [
                    'id'           => $lp->product->id,
                    'package_name' => $lp->product->package_name,
                    'product_name' => $lp->product->product_name,
                    'status'       => $lp->product->status,
                ] : null,
                'lead_status'              => $lp->leadStatus ? [
                    'id'   => $lp->leadStatus->id,
                    'name' => $lp->leadStatus->name,
                ] : null,
            ];
        });

        // ── Filter option lists ──────────────────────────────────
        $branches = Branch::where('is_active', true)
            ->when($request->user()?->company_id, fn($q, $companyId) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = $this->scopeEmployeeQueryToOwnBranch(\App\Models\User::query(), $request->user())
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::orderBy('package_name')
            ->get(['id', 'package_name', 'product_name']);

        // Status filter options
        $companyId = $request->user()?->company_id;
        $statusOptions = LeadStatus::query()
            ->when(
                $companyId,
                fn ($q) => $q->where(fn ($sq) => $sq->where('company_id', $companyId)->orWhereNull('company_id')),
                fn ($q) => $q->whereNull('company_id')
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'lead_products'  => $leadProducts,
            'stats'          => $stats,
            'branches'       => $branches,
            'users'          => $users,
            'status_options' => $statusOptions,
            'products'       => $products,
        ]);
    }

    public function callUpdateFunction(Request $request)
    {
        $allDates = $request->boolean('all_dates') || $request->input('all_dates') === 'true' || $request->input('all_dates') === '1';
        $quickDate = $request->input('quick_date') ?? $request->input('quick_select');

        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate   = now()->endOfMonth()->toDateString();

        if ($quickDate === 'all') {
            $allDates = true;
            $dateFrom = null;
            $dateTo   = null;
        } elseif ($quickDate && $quickDate !== 'custom') {
            $dates = match ($quickDate) {
                'today'               => [now()->toDateString(), now()->toDateString()],
                'yesterday'           => [now()->subDay()->toDateString(), now()->subDay()->toDateString()],
                'week', 'this_week'   => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
                'month', 'this_month' => [$defaultFromDate, $defaultToDate],
                'quarter'             => [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()],
                'year', 'this_year'   => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
                default               => null,
            };
            if ($dates) {
                $dateFrom = $dates[0];
                $dateTo   = $dates[1];
            } else {
                $dateFrom = $request->filled('date_from') ? $request->date_from : $defaultFromDate;
                $dateTo   = $request->filled('date_to')   ? $request->date_to   : $defaultToDate;
            }
        } else {
            $hasDateFilter = $request->filled('date_from') || $request->filled('date_to');
            if ($allDates) {
                $dateFrom = null;
                $dateTo   = null;
            } elseif ($hasDateFilter) {
                $dateFrom = $request->input('date_from');
                $dateTo   = $request->input('date_to');
            } else {
                $dateFrom = $defaultFromDate;
                $dateTo   = $defaultToDate;
            }
        }

        $query = LeadCallUpdate::with([
            'lead:id,company_name,contact_name,mobile_number,email',
            'user:id,name',
            'outCome:id,name',
            'outComeSubCategory:id,name',
        ])->latest('called_at');

        // Company/branch/assignment scoping matching web
        $this->visibility->applyLeadRelationVisibility($query, 'lead', $request->user());

        // ── Search (matches web search logic including numeric cleanId) ───────
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $cleanId = preg_replace('/[^0-9]/', '', $search);

            $query->where(function ($q) use ($search, $cleanId) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search, $cleanId) {
                        $lq->where('company_name', 'like', "%{$search}%")
                            ->orWhere('contact_name',  'like', "%{$search}%")
                            ->orWhere('mobile_number', 'like', "%{$search}%")
                            ->orWhere('email',         'like', "%{$search}%");
                        if ($cleanId !== '' && is_numeric($cleanId)) {
                            $lq->orWhere('id', (int) $cleanId);
                        }
                    })
                    ->orWhereHas('user', fn($uq) =>
                        $uq->where('name', 'like', "%{$search}%"));
            });
        }

        // ── Filters ─────────────────────────────────────────────
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('lead', fn($lq) =>
                $lq->where('branch_id', $request->branch_id));
        }

        if ($request->filled('outcome')) {
            $outcome = $request->outcome;
            if (is_numeric($outcome)) {
                $query->where('outcome', $outcome);
            } else {
                $query->where(function ($q) use ($outcome) {
                    $q->where('outcome', $outcome)
                        ->orWhereHas('outCome', fn($oq) => $oq->where('name', 'like', "%{$outcome}%"));
                });
            }
        }

        if (!$allDates && $dateFrom) {
            $query->whereDate('called_at', '>=', $dateFrom);
        }

        if (!$allDates && $dateTo) {
            $query->whereDate('called_at', '<=', $dateTo);
        }

        // ── Paginate ─────────────────────────────────────────────
        $callUpdates = $query->paginate(15)->withQueryString();

        // Enrich items with raw_lead_id and formatted fields for safe client consumption
        $callUpdates->through(function ($call) {
            $callArray = $call->toArray();
            $callArray['raw_lead_id'] = $call->lead_id;
            $callArray['lead_id_formatted'] = 'LD-' . str_pad($call->lead_id, 4, '0', STR_PAD_LEFT);
            $callArray['called_at_formatted'] = $call->called_at ? $call->called_at->format('d M Y, h:i A') : '';
            return $callArray;
        });

        // ── Filter option lists ──────────────────────────────────
        $branches = Branch::where('is_active', true)
            ->when($request->user()?->company_id, fn($q, $companyId) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = $this->scopeEmployeeQueryToOwnBranch(\App\Models\User::query(), $request->user())
            ->orderBy('name')
            ->get(['id', 'name']);

        $outcomes = \App\Models\OutcomeCategory::orderBy('name')
            ->when($request->user()?->company_id, fn($q, $companyId) => $q->where('company_id', $companyId))
            ->get(['id', 'name']);

        return response()->json([
            'call_updates' => $callUpdates,
            'branches'     => $branches,
            'users'        => $users,
            'outcomes'     => $outcomes,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'all_dates'    => $allDates,
        ]);
    }

    public function priceRequestIndex(Request $request)
    {
        if (! $request->user()?->allowsPriceRequests()) {
            return response()->json(['message' => 'Price request feature is disabled for your company.'], 403);
        }

        $query = LeadProductPriceRequest::with([
            'lead',
            'product',
            'requestedBy',
            'approvedBy'
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $search = $request->query('search', $request->query('lead_id'));
        if (!empty($search)) {
            $term = trim($search);
            $cleanLeadId = preg_replace('/^LD-0*/i', '', $term);

            $query->where(function ($q) use ($term, $cleanLeadId) {
                if (is_numeric($cleanLeadId)) {
                    $q->orWhere('lead_id', (int) $cleanLeadId);
                } elseif (is_numeric($term)) {
                    $q->orWhere('lead_id', (int) $term);
                }

                if (is_numeric($term)) {
                    $q->orWhere('requested_unit_price', $term)
                      ->orWhere('original_unit_price', $term);
                }

                $q->orWhereHas('lead', function ($lq) use ($term) {
                    $lq->where('contact_name', 'like', "%{$term}%")
                      ->orWhere('company_name', 'like', "%{$term}%");
                });

                $q->orWhere('deal_name', 'like', "%{$term}%")
                  ->orWhere('product_name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('requested_by')) $query->where('requested_by', $request->requested_by);
        if ($request->filled('date_from'))    $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('created_at', '<=', $request->date_to);

        $requests   = $query->paginate(15)->withQueryString();
        $requesters = $this->scopeEmployeeQueryToOwnBranch(\App\Models\User::query(), $request->user())
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::orderBy('package_name')
            ->get(['id', 'package_name', 'product_name'])
            ->map(fn ($p) => [
                'id'   => $p->id,
                'name' => $p->package_name ?: ($p->product_name ?: 'Product #' . $p->id),
            ])
            ->values();

        return response()->json([
            'requests'   => $requests,
            'requesters' => $requesters,
            'products'   => $products,
        ]);
    }

    public function priceRequestApprove(Request $request, LeadProductPriceRequest $req)
    {
        if (! $request->user()?->allowsPriceRequests()) {
            return response()->json(['message' => 'Price request feature is disabled for your company.'], 403);
        }

        if ($req->status !== 'pending') {
            return response()->json(['message' => 'Already processed.'], 422);
        }

        DB::transaction(function () use ($request, $req) {
            // Was a bare LeadStatus::first() — no company scope guarantee
            // and no defined ordering (see ScopesLeadStatusAndSourceToCompany).
            $defaultStatus = $this->companyScopedDefaultLeadStatus($request->user());
            $leadProduct = LeadProduct::create([
                'lead_id'          => $req->lead_id,
                'product_id'       => $req->product_id,
                'deal_name'        => $req->deal_name,
                'product_name'     => $req->product_name,
                'description'      => $req->product_description,
                'unit_price'       => $req->requested_unit_price,
                'quantity'         => $req->quantity,
                'discount_percent' => $req->discount_percent,
                'remarks'          => $req->remarks,
                'product_status'   => LeadProduct::statusKey($defaultStatus?->name ?? 'new'),
                'lead_status_id'   => $defaultStatus?->id,
                'created_by'       => $req->requested_by,
            ]);
            $req->update([
                'status'           => 'approved',
                'approved_by'      => $request->user()->id,
                'approved_at'      => now(),
                'lead_product_id'  => $leadProduct->id,
                'rejection_reason' => null,
            ]);
        });

        return response()->json([
            'message'       => 'Price request approved.',
            'price_request' => $req->fresh(['lead', 'product', 'requestedBy', 'approvedBy']),
        ]);
    }

    public function priceRequestReject(Request $request, LeadProductPriceRequest $req)
    {
        if (! $request->user()?->allowsPriceRequests()) {
            return response()->json(['message' => 'Price request feature is disabled for your company.'], 403);
        }
        if ($req->status !== 'pending') {
            return response()->json(['message' => 'Already processed.'], 422);
        }

        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $req->update([
            'status'           => 'rejected',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        return response()->json([
            'message'       => 'Price request rejected.',
            'price_request' => $req->fresh(['lead', 'product', 'requestedBy', 'approvedBy']),
        ]);
    }
}
