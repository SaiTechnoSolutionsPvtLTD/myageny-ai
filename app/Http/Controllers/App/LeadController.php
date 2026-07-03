<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadReminder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\LeadFormField;
use App\Models\LeadFieldValue;
use App\Models\LeadProductPriceRequest;
use App\Models\LeadProduct;
use App\Models\LeadCallUpdate;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Leads", description: "Lead management endpoints for mobile app")]

class LeadController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

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

        $query = Lead::with(['branch:id,name', 'assignedTo:id,name', 'createdBy:id,name', 'product:id,product_name'])
            ->latest('lead_date');

        $this->visibility->applyLeadVisibility($query, $request->user());

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('company_name',   'like', "%{$s}%")
                    ->orWhere('contact_name', 'like', "%{$s}%")
                    ->orWhere('mobile_number', 'like', "%{$s}%")
                    ->orWhere('email',        'like', "%{$s}%")
                    ->orWhereHas('product', fn($pq) => $pq->where('product_name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('branch_id'))     $query->where('branch_id',    $request->branch_id);
        if ($request->filled('mobile_number')) $query->where('mobile_number', 'like', '%' . $request->mobile_number . '%');
        if ($request->filled('lead_source'))   $query->where('lead_source',  $request->lead_source);
        if ($request->filled('lead_status'))   $query->where('lead_status',  $request->lead_status);
        if ($request->filled('priority'))      $query->where('priority',     $request->priority);
        if ($request->filled('assigned_to'))   $query->where('assigned_to',  $request->assigned_to);
        if ($request->filled('product_name'))  $query->whereHas('product', fn($pq) => $pq->where('product_name', 'like', '%' . $request->product_name . '%'));
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
            'mobile_number' => ['required', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:255'],
            'lead_source'   => ['required', 'string'],
            'product_id'    => ['nullable', 'integer', 'exists:products,id'],
            'priority'      => ['required', 'string'],
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

            abort_unless($this->visibility->canAssignTo($leadData['assigned_to'], $request->user()), 403);

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
    'createdBy:id,name',
    'product:id,product_name',
    'callUpdates.user:id,name',
    'callUpdates.outCome:id,name',
    'callUpdates.outComeSubCategory:id,name',
    'reminders.user:id,name',
    'products.payments.recordedBy:id,name',
    'products.leadStatus',                              // ← add this
    'products.latestProductionInitiation.initiatedBy',
    'products.latestProductionInitiation.reviewedBy',
    'products.latestProductionInitiation.productionApprovalReviewedBy',
    'products.latestProductionInitiation.projectAllocatedBy',
    'products.latestProductionInitiation.employeeAllocatedBy',
    'quotations.items',
    'quotations.createdBy:id,name',
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
            'mobile_number' => ['sometimes', 'required', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:255'],
            'lead_source'   => ['sometimes', 'required', 'string', Rule::in(Lead::sourceKeys())],
            'product_id'    => ['nullable', 'integer', 'exists:products,id'],
            'priority'      => ['sometimes', 'required', 'string', 'in:' . implode(',', array_keys(Lead::PRIORITIES))],
            'remarks'       => ['nullable', 'string'],
            'branch_id'     => ['nullable', 'integer', 'exists:branches,id'],
            'assigned_to'   => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (array_key_exists('assigned_to', $validated) && $validated['assigned_to']) {
            abort_unless($this->visibility->canAssignTo($validated['assigned_to'], $request->user()), 403);
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

        return response()->json([
            'status'       => true,
            'message'      => "Lead status updated to {$lead->status_label}.",
            'lead_status'  => $lead->lead_status,
            'status_label' => $lead->status_label,
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
        return response()->json([
            'status' => true,
            'data'   => [
                'sources'        => Lead::sourceOptions(),
                'statuses'       => Lead::statusOptions(),
                'priorities'     => Lead::PRIORITIES,
                'reminder_types' => LeadReminder::TYPES,
                'branches'       => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'users'          => $this->visibility->visibleAssignableUsers(request()->user())->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ])->values(),
                'products'       => tap(Product::query(), fn($query) => $this->visibility->applyProductVisibility($query, request()->user()))
                    ->orderBy('product_name')
                    ->get(['id', 'product_name as name']),
            ],
        ]);
    }

    // =========================================================================
    // PRIVATE FORMATTERS
    // =========================================================================

    private function formatLeadSummary(Lead $lead): array
    {
        return [
            'id'                   => $lead->id,
            'company_name'         => $lead->company_name,
            'contact_name'         => $lead->contact_name,
            'mobile_number'        => $lead->mobile_number,
            'email'                => $lead->email,
            'lead_date'            => $lead->lead_date?->toDateString(),
            'lead_source'          => $lead->lead_source,
            'source_label'         => $lead->source_label,
            'lead_status'          => $lead->lead_status,
            'status_label'         => $lead->status_label,
            'status_color'         => $lead->status_color,
            'priority'             => $lead->priority,
            'priority_label'       => $lead->priority_label,
            'priority_color'       => $lead->priority_color,
            'product_id'           => $lead->product_id,
            'product_name'         => $lead->product?->product_name ?? $lead->product_name,
            'deal_value'           => $lead->deal_value,
            'formatted_deal_value' => $lead->formatted_deal_value,
            'branch'               => $lead->branch
                ? ['id' => $lead->branch->id, 'name' => $lead->branch->name]
                : null,
            'assigned_to'          => $lead->assignedTo
                ? ['id' => $lead->assignedTo->id, 'name' => $lead->assignedTo->name]
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
                    'remind_at'    => $r->remind_at?->toIso8601String(),
                    'type'         => $r->type,
                    'type_label'   => $r->type_label,
                    'type_icon'    => $r->type_icon,
                    'priority'     => $r->priority,
                    'is_completed' => (bool) $r->is_completed,
                    'is_overdue'   => $r->is_overdue,
                    'completed_at' => $r->completed_at?->toIso8601String(),
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
                'field'              => $v->field ? [
                    'label'      => $v->field->label,
                    'field_type' => $v->field->field_type,
                ] : null,
            ])->values(),
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

    return [
        'current_stage' => $currentStage,
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
            $submittedValue = $submittedValues[$field->id] ?? null;

            if (is_array($submittedValue)) {
                $submittedValue = array_values(array_filter($submittedValue, fn($value) => $value !== null && $value !== ''));
            }

            $normalizedValue = is_array($submittedValue)
                ? json_encode($submittedValue)
                : ($submittedValue !== null ? trim((string) $submittedValue) : null);

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
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->toDateString();
        $dateTo   = $request->filled('date_to')   ? $request->date_to   : now()->toDateString();

        // Default to today if no dates supplied
        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            $request->merge([
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]);
        }

        $query = LeadProduct::query()
            ->with(['lead.branch', 'lead.assignedTo', 'product'])
            ->whereHas('lead')
            ->latest('created_at');

        // ── Search ──────────────────────────────────────────────
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('product', fn($pq) =>
                    $pq->where('product_name', 'like', "%{$search}%")
                        ->orWhere('package_name', 'like', "%{$search}%"))
                    ->orWhereHas('lead', fn($lq) =>
                    $lq->where('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%"));
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

        if ($request->filled('product_status')) {
            $query->where('product_status', $request->product_status);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // ── Stats (before paginating) ────────────────────────────
        $statsRows = (clone $query)->get();
        $stats = [
            'total_products' => $statsRows->count(),
            'total_value'    => (float) $statsRows->sum('total_price'),
            'received'       => (float) $statsRows->sum('amount_paid'),
            'pending'        => (float) $statsRows->sum(fn($lp) => $lp->amount_pending),
        ];

        // ── Paginate ─────────────────────────────────────────────
        $leadProducts = $query->paginate(15)->withQueryString();

        // ── Filter option lists ──────────────────────────────────
        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = \App\Models\User::orderBy('name')
            ->get(['id', 'name']);

        $products = Product::orderBy('package_name')
            ->get(['id', 'package_name', 'product_name']);

        return response()->json([
            'lead_products' => $leadProducts,
            'stats'         => $stats,
            'branches'      => $branches,
            'users'         => $users,
            'products'      => $products,
        ]);
    }

    public function callUpdateFunction(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->toDateString();
        $dateTo   = $request->filled('date_to')   ? $request->date_to   : now()->toDateString();

        $query = LeadCallUpdate::with([
            'lead:id,company_name,contact_name,mobile_number,email',
            'user:id,name',
            'outCome:id,name',
            'outComeSubCategory:id,name',
        ])->latest('called_at');

        // ── Search ──────────────────────────────────────────────
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn($lq) =>
                    $lq->where('id', $search)
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_name',  'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('email',         'like', "%{$search}%"))
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

        if ($dateFrom) {
            $query->whereDate('called_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('called_at', '<=', $dateTo);
        }

        // ── Paginate ─────────────────────────────────────────────
        $callUpdates = $query->paginate(15)->withQueryString();

        // ── Filter option lists ──────────────────────────────────
        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = \App\Models\User::orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'call_updates' => $callUpdates,
            'branches'     => $branches,
            'users'        => $users,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
        ]);
    }

    public function priceRequestIndex(Request $request)
    {
        $query = LeadProductPriceRequest::with([
            'lead',
            'product',
            'requestedBy',
            'approvedBy'
        ])->latest();

        if ($request->filled('status'))       $query->where('status', $request->status);
        if ($request->filled('lead_id'))      $query->where('lead_id', $request->lead_id);
        if ($request->filled('requested_by')) $query->where('requested_by', $request->requested_by);
        if ($request->filled('date_from'))    $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('created_at', '<=', $request->date_to);

        $requests   = $query->paginate(15)->withQueryString();
        $requesters = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'requests'   => $requests,
            'requesters' => $requesters,
        ]);
    }

    public function priceRequestApprove(Request $request, LeadProductPriceRequest $req)
    {
        if ($req->status !== 'pending') {
            return response()->json(['message' => 'Already processed.'], 422);
        }

        DB::transaction(function () use ($request, $req) {
            $defaultStatus = LeadStatus::first();
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
