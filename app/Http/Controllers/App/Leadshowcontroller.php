<?php

// ================================================================
// FILE: app/Http/Controllers/App/LeadShowController.php
// Handles all sub-resource actions on a Lead for the mobile app:
//   Call Updates, Reminders, Products, Product Payments, Quotations
// ================================================================

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadCallUpdate;
use App\Models\LeadProduct;
use App\Models\Product;
use App\Models\LeadProductPayment;
use App\Models\LeadReminder;
use App\Models\LeadProductPriceRequest;
use App\Models\Quotation;
use App\Models\QuotationSetting;
use App\Models\QuotationItem;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;

#[OA\Tag(name: "Lead Sub-Resources", description: "Call updates, reminders, products, payments, and quotations on a lead")]

class LeadShowController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    // ════════════════════════════════════════════════════════════════
    // CALL UPDATES
    // ════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: "/api/mobile/leads/{lead}/calls",
        summary: "Add a call update to a lead",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead", in: "path", required: true, description: "Lead ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["called_at", "call_type", "outcome"],
                properties: [
                    new OA\Property(property: "called_at",         type: "string", format: "date-time", example: "2027-01-15T10:30:00"),
                    new OA\Property(property: "call_type",         type: "string", enum: ["outgoing", "incoming", "missed"], example: "outgoing"),
                    new OA\Property(property: "duration_minutes",  type: "integer", nullable: true, example: 15),
                    new OA\Property(property: "outcome",           type: "string", example: "interested", description: "Key from LeadCallUpdate::OUTCOMES"),
                    new OA\Property(property: "notes",             type: "string", nullable: true, example: "Customer wants demo next week"),
                    new OA\Property(property: "next_follow_up",    type: "string", format: "date", nullable: true, example: "2027-01-22"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Call update created",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Call update added successfully."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")),
            new OA\Response(response: 404, description: "Lead not found",   content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]

    public function storeCall(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $data = $request->validate([
            // call_type / duration_minutes / client-supplied called_at are no
            // longer required — web dropped them from the schema. Kept nullable
            // here only so older app builds mid-rollout don't hard-fail.
            'call_type'                => ['nullable', 'in:outgoing,incoming,missed'],
            'duration_minutes'         => ['nullable', 'integer', 'min:0'],
            'outcome'                  => ['required'],
            // Matches the key the Flutter form already sends today
            // (outcome_subcategory_id) — no app-side payload change needed.
            'outcome_subcategory_id'   => ['required'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
            'next_follow_up'           => ['nullable', 'date', 'after_or_equal:today'],
            'followup_time'            => ['nullable'],
            'reminder_remarks'         => ['nullable', 'string', 'max:500'],
        ]);

        $data['lead_id']             = $lead->id;
        // Server time, not client time — same as web. Avoids device-clock/
        // timezone drift landing in call history.
        $data['called_at']           = now();
        $data['outcome_subcategory'] = $data['outcome_subcategory_id'];
        unset($data['outcome_subcategory_id']);
        $data['user_id']             = $request->user()->id;
        $data['company_id']          = $lead->company_id;

        $call = LeadCallUpdate::create($data);

        if (! empty($request->next_follow_up)) {
            $reminderTitle = trim((string) $request->reminder_remarks);
            if ($reminderTitle === '') {
                $reminderTitle = 'Follow-up Call: ' . ($request->outcome ?: 'Lead Follow-up');
            }

            \App\Models\LeadReminder::create([
                'lead_id'        => $lead->id,
                'user_id'        => $request->user()->id,
                'title'          => \Illuminate\Support\Str::limit($reminderTitle, 150),
                'description'    => $request->notes ? \Illuminate\Support\Str::limit((string) $request->notes, 500) : null,
                'remind_at'      => $request->next_follow_up,
                'remainder_time' => $request->followup_time ?: '10:00:00',
                'type'           => 'follow_up',
                'priority'       => 'high',
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Call update added successfully.',
            'data'    => $this->formatCall($call),
        ], 201);
    }

    #[OA\Put(
        path: "/api/mobile/leads/{lead}/calls/{call}",
        summary: "Update a call update",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead", in: "path", required: true, description: "Lead ID",        schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "call", in: "path", required: true, description: "Call Update ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["outcome", "outcome_subcategory_id"],
                properties: [
                    new OA\Property(property: "outcome",                 type: "string", example: "interested", description: "Key from LeadCallUpdate::OUTCOMES"),
                    new OA\Property(property: "outcome_subcategory_id",  type: "string"),
                    new OA\Property(property: "notes",                   type: "string", nullable: true, example: "Customer wants demo next week"),
                    new OA\Property(property: "next_follow_up",          type: "string", format: "date", nullable: true, example: "2027-01-22"),
                    new OA\Property(property: "followup_time",           type: "string", nullable: true, example: "10:30:00"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Call update updated",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Call update updated successfully."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden",       content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found",       content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function updateCall(Request $request, Lead $lead, LeadCallUpdate $call): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);
        abort_if($call->lead_id !== $lead->id, 403, 'Call does not belong to this lead.');

        $data = $request->validate([
            'outcome'                => ['required'],
            'outcome_subcategory_id' => ['required'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
            'next_follow_up'         => ['nullable', 'date'],
            'followup_time'          => ['nullable'],
            'reminder_remarks'       => ['nullable', 'string', 'max:500'],
        ]);

        $call->update([
            'outcome'             => $data['outcome'],
            'outcome_subcategory' => $data['outcome_subcategory_id'],
            'notes'               => $data['notes'] ?? null,
            'next_follow_up'      => $data['next_follow_up'] ?: null,
            'followup_time'       => $data['followup_time'] ?: null,
        ]);

        // Mirrors web's updateCall(): editing a call can also raise a new
        // follow-up reminder, same as creating one does, when a follow-up
        // date is (re)selected and the user typed a reminder title for it.
        if (! empty($data['next_follow_up']) && ! empty($data['reminder_remarks'])) {
            \App\Models\LeadReminder::create([
                'lead_id'        => $lead->id,
                'user_id'        => $request->user()->id,
                'title'          => \Illuminate\Support\Str::limit(trim($data['reminder_remarks']), 150),
                'description'    => $data['notes'] ? \Illuminate\Support\Str::limit((string) $data['notes'], 500) : null,
                'remind_at'      => $data['next_follow_up'],
                'remainder_time' => $data['followup_time'] ?: '10:00:00',
                'type'           => 'follow_up',
                'priority'       => 'high',
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Call update updated successfully.',
            'data'    => $this->formatCall($call->fresh()),
        ]);
    }

    #[OA\Delete(
        path: "/api/mobile/leads/{lead}/calls/{call}",
        summary: "Delete a call update",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead", in: "path", required: true, description: "Lead ID",        schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "call", in: "path", required: true, description: "Call Update ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Call deleted",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Call record removed."),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden",       content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found",       content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function destroyCall(Lead $lead, LeadCallUpdate $call): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, request()->user()), 403);

        abort_if($call->lead_id !== $lead->id, 403, 'Call does not belong to this lead.');
        $call->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Call record removed.',
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // REMINDERS
    // ════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: "/api/mobile/leads/{lead}/reminders",
        summary: "Add a reminder to a lead",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead", in: "path", required: true, description: "Lead ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "remind_at", "type", "priority"],
                properties: [
                    new OA\Property(property: "title",       type: "string",  example: "Follow up call with Ravi"),
                    new OA\Property(property: "description", type: "string",  nullable: true, example: "Discuss bulk order pricing"),
                    new OA\Property(property: "remind_at",   type: "string",  format: "date-time", example: "2027-01-20T09:00:00"),
                    new OA\Property(property: "type",        type: "string",  enum: ["follow_up", "meeting", "call", "email", "demo", "other"], example: "call"),
                    new OA\Property(property: "priority",    type: "string",  enum: ["low", "medium", "high"], example: "medium"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Reminder created",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Reminder set successfully."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")),
            new OA\Response(response: 404, description: "Lead not found",  content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function storeReminder(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $data = $request->validate([
            'title'          => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string', 'max:500'],
            'remind_at'      => ['required', 'date', 'after:now'],
            'type'           => ['required', 'in:' . implode(',', array_keys(LeadReminder::TYPES))],
            'priority'       => ['required', 'in:low,medium,high'],
            // Optional here (unlike the web form's separate date+time inputs,
            // the app's picker sends one combined remind_at) — derived below
            // when omitted so the row is still fully populated the same way
            // web's is. Kept nullable/optional rather than required so this
            // doesn't become a breaking change for the existing app build.
            'remainder_time' => ['nullable'],
        ]);

        $data['lead_id'] = $lead->id;
        $data['user_id'] = auth()->id();

        // The app's Set Reminder form only has a single combined date+time
        // picker (remind_at carries both), unlike the web form's separate
        // date/time inputs — so remainder_time is never sent from the app.
        // Derive it from remind_at's own time-of-day here so the stored row
        // ends up exactly as populated as a web-created one: both
        // LeadReminder::type_label-style displays (web's Tasks page, this
        // app's own new Reminders & Tasks screen) read remainder_time
        // separately from remind_at, and would otherwise show a blank/
        // fallback time for every reminder created from the app.
        if (empty($data['remainder_time'])) {
            $data['remainder_time'] = \Carbon\Carbon::parse($data['remind_at'])->format('H:i:s');
        }

        $reminder = LeadReminder::create($data);
        $reminder->load('user:id,name');

        return response()->json([
            'status'  => true,
            'message' => 'Reminder set successfully.',
            'data'    => $this->formatReminder($reminder),
        ], 201);
    }

    #[OA\Patch(
        path: "/api/mobile/leads/{lead}/reminders/{reminder}/complete",
        summary: "Mark a reminder as completed",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",     in: "path", required: true, description: "Lead ID",     schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "reminder", in: "path", required: true, description: "Reminder ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Reminder completed",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Reminder marked as completed."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function completeReminder(Lead $lead, LeadReminder $reminder): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, request()->user()), 403);

        abort_if($reminder->lead_id !== $lead->id, 403, 'Reminder does not belong to this lead.');
        $reminder->update(['is_completed' => true, 'completed_at' => now()]);
        $reminder->load('user:id,name');

        return response()->json([
            'status'  => true,
            'message' => 'Reminder marked as completed.',
            'data'    => $this->formatReminder($reminder),
        ]);
    }

    #[OA\Delete(
        path: "/api/mobile/leads/{lead}/reminders/{reminder}",
        summary: "Delete a reminder",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",     in: "path", required: true, description: "Lead ID",     schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "reminder", in: "path", required: true, description: "Reminder ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Reminder deleted",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Reminder removed."),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function destroyReminder(Lead $lead, LeadReminder $reminder): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, request()->user()), 403);

        abort_if($reminder->lead_id !== $lead->id, 403, 'Reminder does not belong to this lead.');
        $reminder->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Reminder removed.',
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // CST & WEEKLY UPDATES
    // Mirrors LeadController::storeCstUpdate() (web) — Update Type +
    // Notes only, no product picker (the web form doesn't offer one
    // either; lead_product_id is left null here to match). Route-level
    // 'can:add-cst-update,lead' middleware (see routes/api.php + the
    // Gate defined in AppServiceProvider) enforces the same
    // isCustomerSuccessUser() restriction web applies inline via
    // abort_unless — kept here too as a defense-in-depth check.
    // ════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: "/api/mobile/leads/{lead}/cst-updates",
        summary: "Add a CST / Weekly / Review / Escalation update to a lead",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead", in: "path", required: true, description: "Lead ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: "CST update recorded",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "CST Update added successfully."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function storeCstUpdate(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);
        abort_unless($request->user()?->allowsCstUpdates() && $request->user()?->isCustomerSuccessUser(), 403, 'Only Customer Success Team members can add CST updates.');

        $data = $request->validate([
            'update_type' => ['required', 'in:cst_update,weekly_update,review,escalation'],
            'notes'       => ['required', 'string', 'max:5000'],
        ]);

        $update = $lead->cstUpdates()->create([
            'company_id'      => $lead->company_id,
            'lead_product_id' => null,
            'update_type'     => $data['update_type'],
            'notes'           => $data['notes'],
            'user_id'         => $request->user()->id,
        ]);

        $update->load('user:id,name');

        return response()->json([
            'status'  => true,
            'message' => 'CST Update added successfully.',
            'data'    => [
                'id'                => $update->id,
                'update_type'       => $update->update_type,
                'update_type_label' => $update->update_type_label,
                'notes'             => $update->notes,
                'user'              => $update->user
                    ? ['id' => $update->user->id, 'name' => $update->user->name]
                    : null,
                'created_at'        => $update->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    // ════════════════════════════════════════════════════════════════
    // LEAD PRODUCTS
    // ════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: "/api/mobile/leads/{lead}/products",
        summary: "Add a product to a lead",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead", in: "path", required: true, description: "Lead ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["product_name", "product_status", "unit_price", "quantity"],
                properties: [
                    new OA\Property(property: "product_name",     type: "string",  example: "Solar Panel 10kW"),
                    new OA\Property(property: "product_status",   type: "string",  enum: ["new", "hot", "warm", "cold", "converted"], example: "hot"),
                    new OA\Property(property: "description",      type: "string",  nullable: true, example: "Mono PERC 10kW panel"),
                    new OA\Property(property: "unit_price",       type: "number",  format: "float", example: 45000.00),
                    new OA\Property(property: "quantity",         type: "integer", example: 4),
                    new OA\Property(property: "discount_percent", type: "number",  format: "float", nullable: true, example: 5.0),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Product added",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Product added successfully."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")),
            new OA\Response(response: 404, description: "Lead not found",  content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function storeProduct(Request $request, Lead $lead): JsonResponse
    {

        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $data = $request->validate([
            'product_name'     => ['required', 'string', 'max:150'],
            'product_status'   => ['required', 'in:new,hot,warm,cold,converted'],
            'description'      => ['nullable', 'string', 'max:500'],
            'unit_price'       => ['required', 'numeric', 'min:0'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['lead_id']          = $lead->id;
        $data['discount_percent'] = $data['discount_percent'] ?? 0;
        $data['payment_status']   = 'pending';
        $data['company_id']   = $request->company_id;

        $product = LeadProduct::create($data);

        return response()->json([
            'status'  => true,
            'message' => "Product \"{$product->product_name}\" added successfully.",
            'data'    => $this->formatProduct($product),
        ], 201);
    }

    #[OA\Patch(
        path: "/api/mobile/leads/{lead}/products/{product}/status",
        summary: "Update a product's status",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",    in: "path", required: true, description: "Lead ID",    schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "product", in: "path", required: true, description: "Product ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["product_status"],
                properties: [
                    new OA\Property(property: "product_status", type: "string", enum: ["new", "hot", "warm", "cold", "converted"], example: "converted"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Status updated",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Product status updated to Converted."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function updateProductStatus(Request $request, Lead $lead, LeadProduct $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);
        abort_if($product->lead_id !== $lead->id, 403, 'Product does not belong to this lead.');

        $request->validate([
            'lead_status_id' => ['nullable', 'integer', 'exists:lead_statuses,id'],
            'product_status' => ['nullable', 'string'],
        ]);

        $companyId = $lead->company_id ?? $request->user()?->company_id;
        $statuses = \App\Models\LeadStatus::query()
            ->when(
                $companyId,
                fn($q) => $q->where(fn($sq) => $sq->where('company_id', $companyId)->orWhereNull('company_id')),
                fn($q) => $q->whereNull('company_id')
            )
            ->orderBy('name')
            ->get();

        // Resolve from the company-scoped $statuses collection above, not a
        // bare LeadStatus::find() — the latter would accept a client-supplied
        // lead_status_id belonging to another company (exists:lead_statuses,id
        // validates existence only, not company ownership).
        $status = $request->filled('lead_status_id')
            ? $statuses->firstWhere('id', (int) $request->lead_status_id)
            : $statuses->first(
                fn($option) =>
                LeadProduct::statusKey($option->name) === LeadProduct::statusKey($request->product_status)
            );

        if (! $status) {
            return response()->json(['status' => false, 'message' => 'Please select a valid status.'], 422);
        }

        $product->update([
            'lead_status_id' => $status->id,
            'product_status' => LeadProduct::statusKey($status->name),
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Product status updated to {$status->name}.",
            'data'    => $this->formatProduct($product->fresh('leadStatus')),
        ]);
    }

    #[OA\Put(
        path: "/api/mobile/leads/{lead}/products/{product}",
        summary: "Update a lead product",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",    in: "path", required: true, description: "Lead ID",    schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "product", in: "path", required: true, description: "Product ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "product_name",     type: "string",  nullable: true),
                    new OA\Property(property: "product_status",   type: "string",  enum: ["new", "hot", "warm", "cold", "converted"], nullable: true),
                    new OA\Property(property: "description",      type: "string",  nullable: true),
                    new OA\Property(property: "unit_price",       type: "number",  format: "float", nullable: true),
                    new OA\Property(property: "quantity",         type: "integer", nullable: true),
                    new OA\Property(property: "discount_percent", type: "number",  format: "float", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Product updated",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Product updated."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function updateProduct(Request $request, Lead $lead, LeadProduct $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);
        abort_if($product->lead_id !== $lead->id, 403, 'Product does not belong to this lead.');

        $data = $request->validate([
            'product_id'       => ['required', 'integer', 'exists:products,id'],
            'product_status'   => ['required', 'in:new,hot,warm,cold,converted'],
            'description'      => ['nullable', 'string', 'max:500'],
            'unit_price'       => ['required', 'numeric', 'min:0'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Reused, not duplicated — identical helper the web edit form uses to
        // derive the display name from the selected catalog product/category.
        $catalogProduct = Product::with('category')->findOrFail($data['product_id']);
        $data['product_name']     = $this->leadProductName($catalogProduct);
        $data['discount_percent'] = $data['discount_percent'] ?? 0;
        // Changing the underlying product invalidates any custom lead-status
        // previously tied to the old product — same reset the web performs.
        $data['lead_status_id']   = null;

        $product->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Product updated.',
            'data'    => $this->formatProduct($product->fresh()),
        ]);
    }

    private function leadProductName(Product $product): string
    {
        return $product->product_name
            ?: trim(($product->category?->name ? $product->category->name . ' | ' : '') . $product->package_name);
    }

    #[OA\Delete(
        path: "/api/mobile/leads/{lead}/products/{product}",
        summary: "Delete a product from a lead",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",    in: "path", required: true, description: "Lead ID",    schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "product", in: "path", required: true, description: "Product ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product deleted",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Product removed from lead."),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function destroyProduct(Lead $lead, LeadProduct $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, request()->user()), 403);

        abort_if($product->lead_id !== $lead->id, 403, 'Product does not belong to this lead.');
        $product->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Product removed from lead.',
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // PRODUCT PAYMENTS
    // ════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: "/api/mobile/leads/{lead}/products/{product}/payments",
        summary: "Record a payment for a lead product",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",    in: "path", required: true, description: "Lead ID",    schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "product", in: "path", required: true, description: "Product ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount", "payment_mode", "payment_date"],
                properties: [
                    new OA\Property(property: "amount",           type: "number", format: "float", example: 50000.00),
                    new OA\Property(property: "payment_mode",     type: "string", enum: ["cash", "bank_transfer", "cheque", "upi", "card"], example: "upi"),
                    new OA\Property(property: "payment_date",     type: "string", format: "date", example: "2027-01-15"),
                    new OA\Property(property: "reference_number", type: "string", nullable: true, example: "UPI/2027/001234"),
                    new OA\Property(property: "notes",            type: "string", nullable: true, example: "Advance payment"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Payment recorded",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Payment of ₹50,000.00 recorded."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function storeProductPayment(Request $request, Lead $lead, \App\Models\LeadProduct $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        abort_if($product->lead_id !== $lead->id, 403, 'Product does not belong to this lead.');

        if ($product->product_status_key !== 'converted') {
            return response()->json([
                'status' => false,
                'message' => 'Payments can be added only after the product status is Converted.',
            ], 422);
        }

        $data = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_mode'     => ['required', 'string', 'in:cash,bank_transfer,cheque,upi,card'],
            'payment_date'     => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $data['lead_product_id'] = $product->id;
        $data['lead_id']         = $lead->id;
        $data['recorded_by']     = auth()->id();

        $payment = \App\Models\LeadProductPayment::create($data);

        // Sync payment status on product
        $product->syncPaymentStatus();

        return response()->json([
            'status'  => true,
            'message' => 'Payment of ₹' . number_format($data['amount'], 2) . ' recorded.',
            'data'    => $this->formatPayment($payment),
        ], 201);
    }

    public function productPayments(Request $request, Lead $lead, LeadProduct $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);
        abort_if($product->lead_id !== $lead->id, 403, 'Product does not belong to this lead.');

        $payments = $product->payments()
            ->with('recordedBy:id,name')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => [
                'payments' => $payments,
                'product'  => $this->formatProduct($product->fresh()),
            ],
        ]);
    }

    #[OA\Delete(
        path: "/api/mobile/leads/{lead}/products/{product}/payments/{payment}",
        summary: "Delete a product payment",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",    in: "path", required: true, description: "Lead ID",    schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "product", in: "path", required: true, description: "Product ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "payment", in: "path", required: true, description: "Payment ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Payment deleted",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Payment entry removed."),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function destroyProductPayment(Lead $lead, LeadProduct $product, LeadProductPayment $payment): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, request()->user()), 403);

        abort_if($payment->lead_product_id !== $product->id, 403, 'Payment does not belong to this product.');

        $payment->delete();
        $product->syncPaymentStatus();

        return response()->json([
            'status'  => true,
            'message' => 'Payment entry removed.',
        ]);
    }

    // ════════════════════════════════════════════════════════════════
    // QUOTATIONS
    // ════════════════════════════════════════════════════════════════

    #[OA\Post(
        path: "/api/mobile/leads/{lead}/quotations",
        summary: "Create a quotation for a lead",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead", in: "path", required: true, description: "Lead ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["quotation_date", "items"],
                properties: [
                    new OA\Property(property: "quotation_date",   type: "string", format: "date",  example: "2027-01-15"),
                    new OA\Property(property: "valid_until",      type: "string", format: "date",  nullable: true, example: "2027-01-30"),
                    new OA\Property(property: "discount_amount",  type: "number", format: "float", nullable: true, example: 5000.00),
                    new OA\Property(property: "tax_percent",      type: "number", format: "float", nullable: true, example: 18.0),
                    new OA\Property(property: "terms_conditions", type: "string", nullable: true),
                    new OA\Property(property: "notes",            type: "string", nullable: true),
                    new OA\Property(
                        property: "items",
                        type: "array",
                        minItems: 1,
                        items: new OA\Items(
                            required: ["product_name", "quantity", "unit_price"],
                            properties: [
                                new OA\Property(property: "product_name",     type: "string",  example: "Solar Panel 10kW"),
                                new OA\Property(property: "description",      type: "string",  nullable: true),
                                new OA\Property(property: "quantity",         type: "integer", example: 4),
                                new OA\Property(property: "unit",             type: "string",  nullable: true, example: "Nos"),
                                new OA\Property(property: "unit_price",       type: "number",  format: "float", example: 45000.00),
                                new OA\Property(property: "discount_percent", type: "number",  format: "float", nullable: true, example: 5.0),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Quotation created",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Quotation QT-0001 created."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")),
            new OA\Response(response: 404, description: "Lead not found",  content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function storeQuotation(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $data = $request->validate([
            'quotation_date'           => ['required', 'date'],
            'valid_until'              => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'discount_amount'          => ['nullable', 'numeric', 'min:0'],
            'tax_percent'              => ['nullable', 'numeric', 'min:0', 'max:100'],
            'terms_conditions'         => ['nullable', 'string'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_name'     => ['required', 'string', 'max:150'],
            'items.*.description'      => ['nullable', 'string'],
            'items.*.quantity'         => ['required', 'integer', 'min:1'],
            'items.*.unit'             => ['nullable', 'string', 'max:20'],
            'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $quotation = Quotation::create([
            'lead_id'          => $lead->id,
            'created_by'       => auth()->id(),
            'quotation_date'   => $data['quotation_date'],
            'valid_until'      => $data['valid_until'] ?? null,
            'discount_amount'  => $data['discount_amount'] ?? 0,
            'tax_percent'      => $data['tax_percent'] ?? 0,
            'terms_conditions' => $data['terms_conditions'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'status'           => 'draft',
            'subtotal'         => 0,
            'tax_amount'       => 0,
            'grand_total'      => 0,
        ]);

        foreach ($data['items'] as $i => $item) {
            QuotationItem::create([
                'quotation_id'     => $quotation->id,
                'product_name'     => $item['product_name'],
                'description'      => $item['description'] ?? null,
                'quantity'         => $item['quantity'],
                'unit'             => $item['unit'] ?? 'Nos',
                'unit_price'       => $item['unit_price'],
                'discount_percent' => $item['discount_percent'] ?? 0,
                'sort_order'       => $i,
            ]);
        }

        $quotation->refresh()->recalculateTotals();
        $quotation->load(['items', 'createdBy:id,name']);

        return response()->json([
            'status'  => true,
            'message' => "Quotation {$quotation->quotation_number} created.",
            'data'    => $this->formatQuotation($quotation),
        ], 201);
    }

    #[OA\Patch(
        path: "/api/mobile/leads/{lead}/quotations/{quotation}/status",
        summary: "Update quotation status",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",      in: "path", required: true, description: "Lead ID",      schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "quotation", in: "path", required: true, description: "Quotation ID", schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", description: "Key from Quotation::STATUSES", example: "sent"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Status updated",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Quotation status updated to Sent."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function updateQuotationStatus(Request $request, Lead $lead, Quotation $quotation): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        abort_if($quotation->lead_id !== $lead->id, 403, 'Quotation does not belong to this lead.');

        $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(Quotation::STATUSES))],
        ]);

        $quotation->update(['status' => $request->status]);
        $quotation->load(['items', 'createdBy:id,name']);

        return response()->json([
            'status'  => true,
            'message' => "Quotation status updated to {$quotation->status_label}.",
            'data'    => $this->formatQuotation($quotation),
        ]);
    }

    #[OA\Delete(
        path: "/api/mobile/leads/{lead}/quotations/{quotation}",
        summary: "Delete a quotation",
        security: [["sanctum" => []]],
        tags: ["Lead Sub-Resources"],
        parameters: [
            new OA\Parameter(name: "lead",      in: "path", required: true, description: "Lead ID",      schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "quotation", in: "path", required: true, description: "Quotation ID", schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Quotation deleted",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Quotation deleted."),
                ])
            ),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function destroyQuotation(Lead $lead, Quotation $quotation): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, request()->user()), 403);

        abort_if($quotation->lead_id !== $lead->id, 403, 'Quotation does not belong to this lead.');
        $quotation->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Quotation deleted.',
        ]);
    }

    public function approveQuotation(Request $request, Lead $lead, Quotation $quotation): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);
        abort_if($quotation->lead_id !== $lead->id, 403, 'Quotation does not belong to this lead.');

        $quotation->update([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);
        $quotation->load(['items', 'lead', 'approver']);

        return response()->json([
            'status'  => true,
            'message' => 'Quotation approved successfully.',
            'data'    => $this->formatQuotation($quotation), // or equivalent shape used by getAllQuotations
        ]);
    }

    public function sendQuotationEmail(Request $request, Lead $lead, Quotation $quotation): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);
        abort_if($quotation->lead_id !== $lead->id, 403, 'Quotation does not belong to this lead.');

        $quotation->loadMissing(['items.product', 'createdBy']);
        $lead->loadMissing(['createdBy', 'assignedTo']);
        $email = trim((string) $lead->email);

        if ($email === '') {
            return response()->json([
                'status'  => false,
                'message' => 'Lead email not available for this quotation.',
            ], 422);
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'status'  => false,
                'message' => 'Lead email is invalid. Please update the lead email and try again.',
            ], 422);
        }

        try {
            $pdf      = $this->makeQuotationPdf($quotation);
            $filename = $this->quotationPdfFilename($quotation);

            $agreeUrl = URL::signedRoute('quotations.customer-response', [
                'quotation' => $quotation->id,
                'response'  => Quotation::CUSTOMER_RESPONSE_AGREE,
            ]);
            $disagreeUrl = URL::signedRoute('quotations.customer-response', [
                'quotation' => $quotation->id,
                'response'  => Quotation::CUSTOMER_RESPONSE_DISAGREE,
            ]);

            Mail::send('emails.quotation', [
                'quotation'   => $quotation,
                'lead'        => $lead,
                'agreeUrl'    => $agreeUrl,
                'disagreeUrl' => $disagreeUrl,
            ], function ($message) use ($quotation, $lead, $email, $pdf, $filename) {
                $message->to($email, $lead->contact_name ?: null)
                    ->subject('Quotation ' . $quotation->quotation_no)
                    ->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
            });
        } catch (Throwable $exception) {
            Log::error('Quotation email send failed.', [
                'quotation_id' => $quotation->id,
                'email'        => $email,
                'error'        => $exception->getMessage(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Quotation mail could not be sent. Please check mail settings and try again.',
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Quotation ' . $quotation->quotation_no . ' sent to ' . $email . '.',
        ]);
    }

    private function makeQuotationPdf(Quotation $quotation)
    {
        $quotation->loadMissing(['items.product', 'createdBy', 'lead.createdBy', 'lead.assignedTo']);
        $quoteSetting = $this->quotationSettingsFor($quotation);

        return Pdf::loadView('pages.quotations.quotation_format_1', compact('quotation', 'quoteSetting'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);
    }

    private function quotationSettingsFor(Quotation $quotation): array
    {
        $branchId = auth()->user()?->branch_id
            ?? $quotation->lead?->createdBy?->branch_id;

        $settings = QuotationSetting::where('branch_id', $branchId)->get();

        return [
            'logo' => $settings->where('key', 'logo')->first()?->value,
            'theme_color' => $settings->where('key', 'theme_color')->first()?->value,
            'secondary_color' => $settings->where('key', 'secondary_color')->first()?->value,
            'header_text_color' => $settings->where('key', 'header_text_color')->first()?->value,
            'prefix' => $settings->where('key', 'prefix')->first()?->value,
            'number_padding' => $settings->where('key', 'number_padding')->first()?->value,
            'terms' => $settings->where('key', 'terms')->first()?->value,
            'company_address' => $settings->where('key', 'company_address')->first()?->value,
            'company_name' => $settings->where('key', 'company_name')->first()?->value,
            'company_phone' => $settings->where('key', 'company_phone')->first()?->value,
            'company_email' => $settings->where('key', 'company_email')->first()?->value,
            'company_gstin' => $settings->where('key', 'company_gstin')->first()?->value,
            'bank_name' => $settings->where('key', 'bank_name')->first()?->value,
            'bank_account' => $settings->where('key', 'bank_account')->first()?->value,
            'bank_ifsc' => $settings->where('key', 'bank_ifsc')->first()?->value,
            'watermark_text' => $settings->where('key', 'watermark_text')->first()?->value,
            'signature' => $settings->where('key', 'signature')->first()?->value,
            'account_name' => $settings->where('key', 'account_name')->first()?->value,
            'bank_branch' => $settings->where('key', 'bank_branch')->first()?->value,
            'bank_upi' => $settings->where('key', 'bank_upi')->first()?->value,
        ];
    }

    private function quotationPdfFilename(Quotation $quotation): string
    {
        return 'QT-' . str_pad((string) $quotation->id, 6, '0', STR_PAD_LEFT) . '.pdf';
    }

    // ════════════════════════════════════════════════════════════════
    // PRIVATE FORMATTERS
    // ════════════════════════════════════════════════════════════════

    private function formatCall(LeadCallUpdate $call): array
    {
        return [
            'id'                  => $call->id,
            'called_at'           => $call->called_at?->toIso8601String(),
            'call_type'           => $call->call_type,
            'call_type_label'     => $call->call_type_label,
            'duration_minutes'    => $call->duration_minutes,
            'outcome'             => $call->outCome?->name ?? $call->outcome,
            'outcome_label'       => $call->outComeSubCategory?->name ?? $call->outcome_subcategory,
            // Raw foreign key ids, separate from the display strings above —
            // 'outcome'/'outcome_label' resolve to names for display, but the
            // mobile Edit Call form needs the actual ids to preselect the
            // right dropdown/subcategory chip. Both `outcome` and
            // `outcome_subcategory` columns on this model already *are* the
            // FK ids (see LeadCallUpdate::outCome()/outComeSubCategory()),
            // so these just expose the raw, unresolved values under
            // unambiguous names.
            'outcome_id'          => $call->getRawOriginal('outcome'),
            'outcome_subcategory_id' => $call->getRawOriginal('outcome_subcategory'),
            'outcome_color'       => $call->outcome_color,
            'notes'               => $call->notes,
            'next_follow_up'      => $call->next_follow_up?->toDateString(),
            'followup_time'       => $call->followup_time,
            'user'                => $call->user ? ['id' => $call->user->id, 'name' => $call->user->name] : null,
        ];
    }

    private function formatReminder(LeadReminder $reminder): array
    {
        return [
            'id'           => $reminder->id,
            'title'        => $reminder->title,
            'description'  => $reminder->description,
            'remind_at'    => optional($reminder->remind_at)->format('Y-m-d H:i:s'),
            // Must be an explicit ->format() string, not the raw Carbon
            // instance — Carbon's default JSON serialization converts to UTC
            // first, which shifted every displayed time back by the app's
            // UTC+5:30 offset (e.g. 15:26 stored -> 09:56 shown on mobile).
            'remainder_time' => optional($reminder->remainder_time)->format('H:i:s'),
            'type'         => $reminder->type,
            'type_label'   => $reminder->type_label,
            'type_icon'    => $reminder->type_icon,
            'priority'     => $reminder->priority,
            'is_completed' => (bool) $reminder->is_completed,
            'is_overdue'   => $reminder->is_overdue,
            'completed_at' => optional($reminder->completed_at)->format('Y-m-d H:i:s'),
            'user'         => $reminder->user ? ['id' => $reminder->user->id, 'name' => $reminder->user->name] : null,
        ];
    }

    private function formatProduct(LeadProduct $product): array
    {
        return [
            'id'               => $product->id,
            'product_name'     => $product->product_name,
            'product_status'   => $product->product_status,
            'lead_status_id'   => $product->lead_status_id,
            'lead_status_name' => $product->leadStatus?->name,
            'description'      => $product->description,
            'unit_price'       => (float) $product->unit_price,
            'quantity'         => $product->quantity,
            'discount_percent' => (float) $product->discount_percent,
            'total_price'      => (float) $product->total_price,
            'payment_status'   => $product->payment_status,
            'amount_paid'      => (float) $product->amount_paid,
            'amount_pending'   => (float) ($product->total_price - $product->amount_paid),
        ];
    }

    private function formatPayment(LeadProductPayment $payment): array
    {
        return [
            'id'               => $payment->id,
            'amount'           => (float) $payment->amount,
            'payment_mode'     => $payment->payment_mode,
            'payment_date'     => $payment->payment_date?->toDateString(),
            'reference_number' => $payment->reference_number,
            'notes'            => $payment->notes,
        ];
    }

    private function formatQuotation(Quotation $quotation): array
    {
        return [
            'id'               => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'quotation_date'   => $quotation->quotation_date?->toDateString(),
            'valid_until'      => $quotation->valid_until?->toDateString(),
            'status'           => $quotation->status,
            'status_label'     => $quotation->status_label,
            'subtotal'         => (float) $quotation->subtotal,
            'discount_amount'  => (float) $quotation->discount_amount,
            'tax_percent'      => (float) $quotation->tax_percent,
            'tax_amount'       => (float) $quotation->tax_amount,
            'grand_total'      => (float) $quotation->grand_total,
            'terms_conditions' => $quotation->terms_conditions,
            'notes'            => $quotation->notes,
            'created_by'       => $quotation->createdBy
                ? ['id' => $quotation->createdBy->id, 'name' => $quotation->createdBy->name]
                : null,
            'created_at'       => $quotation->created_at?->toIso8601String(),
            'items'            => $quotation->relationLoaded('items')
                ? $quotation->items->map(fn($item) => [
                    'id'               => $item->id,
                    'product_name'     => $item->product_name,
                    'description'      => $item->description,
                    'quantity'         => $item->quantity,
                    'unit'             => $item->unit,
                    'unit_price'       => (float) $item->unit_price,
                    'discount_percent' => (float) $item->discount_percent,
                    'line_total'       => (float) $item->line_total,
                ])->values()
                : [],
        ];
    }

    public function priceRequest(Request $request): JsonResponse
    {
        if (! $request->user()?->allowsPriceRequests()) {
            return response()->json(['status' => false, 'message' => 'Price request feature is disabled for your company.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'lead_id'                          => ['required', 'exists:leads,id'],
            'deal_name'                        => ['required', 'string', 'max:255'],
            'products'                         => ['required', 'array', 'min:1'],
            'products.*.product_id'            => ['required', 'exists:products,id'],
            'products.*.requested_unit_price'  => ['required', 'numeric', 'min:0'],
            'products.*.quantity'              => ['required', 'integer', 'min:1'],
            'products.*.discount_percent'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'products.*.remarks'               => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Not present in the web store() you shared, but added for consistency
        // with every other mobile lead-scoped endpoint in this app (updateProduct,
        // storeProductPayment, leadsMeta, etc.), all of which gate on
        // canAccessLead — otherwise any authenticated mobile user could submit a
        // price request against a lead outside their branch/company visibility.
        $lead = Lead::findOrFail($request->lead_id);
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $created = DB::transaction(function () use ($request) {
            $rows = [];
            foreach ($request->products as $row) {
                $product = Product::findOrFail($row['product_id']);
                $rows[] = LeadProductPriceRequest::create([
                    'lead_id'              => $request->lead_id,
                    'product_id'           => $product->id,
                    'deal_name'            => $request->deal_name,
                    'product_name'         => $product->package_name,
                    'product_description'  => $product->description,
                    'original_unit_price'  => (float) $product->final_price,
                    'requested_unit_price' => (float) $row['requested_unit_price'],
                    'quantity'             => (int) $row['quantity'],
                    'discount_percent'     => (float) ($row['discount_percent'] ?? 0),
                    'remarks'              => $row['remarks'] ?? null,
                    'status'               => 'pending',
                    'requested_by'         => $request->user()->id,
                ]);
            }
            return $rows;
        });

        return response()->json([
            'status'  => true,
            'message' => 'Price change request sent for admin approval.',
            'data'    => $created,
        ], 201);
    }
}
