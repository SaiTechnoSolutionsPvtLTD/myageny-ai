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
use App\Models\LeadProductPayment;
use App\Models\LeadReminder;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

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
                    new OA\Property(property: "call_type",         type: "string", enum: ["outgoing","incoming","missed"], example: "outgoing"),
                    new OA\Property(property: "duration_minutes",  type: "integer", nullable: true, example: 15),
                    new OA\Property(property: "outcome",           type: "string", example: "interested", description: "Key from LeadCallUpdate::OUTCOMES"),
                    new OA\Property(property: "notes",             type: "string", nullable: true, example: "Customer wants demo next week"),
                    new OA\Property(property: "next_follow_up",    type: "string", format: "date", nullable: true, example: "2027-01-22"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Call update created",
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
            'called_at'        => ['required', 'date'],
            'call_type'        => ['required', 'in:outgoing,incoming,missed'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'outcome'          => ['required'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'next_follow_up'   => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $data['lead_id'] = $lead->id;
        $data['user_id'] = auth()->id();

        $call = LeadCallUpdate::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Call update added successfully.',
            'data'    => $this->formatCall($call),
        ], 201);
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
            new OA\Response(response: 200, description: "Call deleted",
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
                    new OA\Property(property: "type",        type: "string",  enum: ["follow_up","meeting","call","email","demo","other"], example: "call"),
                    new OA\Property(property: "priority",    type: "string",  enum: ["low","medium","high"], example: "medium"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Reminder created",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Reminder set successfully."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")),
            new OA\Response(response: 404, description: "Lead not found",  content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error",content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
        ]
    )]
    public function storeReminder(Request $request, Lead $lead): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($lead, $request->user()), 403);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'remind_at'   => ['required', 'date', 'after:now'],
            'type'        => ['required', 'in:' . implode(',', array_keys(LeadReminder::TYPES))],
            'priority'    => ['required', 'in:low,medium,high'],
        ]);

        $data['lead_id'] = $lead->id;
        $data['user_id'] = auth()->id();

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
            new OA\Response(response: 200, description: "Reminder completed",
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
            new OA\Response(response: 200, description: "Reminder deleted",
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
                    new OA\Property(property: "product_status",   type: "string",  enum: ["new","hot","warm","cold","converted"], example: "hot"),
                    new OA\Property(property: "description",      type: "string",  nullable: true, example: "Mono PERC 10kW panel"),
                    new OA\Property(property: "unit_price",       type: "number",  format: "float", example: 45000.00),
                    new OA\Property(property: "quantity",         type: "integer", example: 4),
                    new OA\Property(property: "discount_percent", type: "number",  format: "float", nullable: true, example: 5.0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Product added",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Product added successfully."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")),
            new OA\Response(response: 404, description: "Lead not found",  content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error",content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
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
                    new OA\Property(property: "product_status", type: "string", enum: ["new","hot","warm","cold","converted"], example: "converted"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Status updated",
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
            fn ($q) => $q->where(fn ($sq) => $sq->where('company_id', $companyId)->orWhereNull('company_id')),
            fn ($q) => $q->whereNull('company_id')
        )
        ->orderBy('name')
        ->get();

    $status = $request->filled('lead_status_id')
        ? $statuses->firstWhere('id', (int) $request->lead_status_id)
        : $statuses->first(fn ($option) => LeadProduct::statusKey($option->name) === LeadProduct::statusKey($request->product_status));

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
                    new OA\Property(property: "product_status",   type: "string",  enum: ["new","hot","warm","cold","converted"], nullable: true),
                    new OA\Property(property: "description",      type: "string",  nullable: true),
                    new OA\Property(property: "unit_price",       type: "number",  format: "float", nullable: true),
                    new OA\Property(property: "quantity",         type: "integer", nullable: true),
                    new OA\Property(property: "discount_percent", type: "number",  format: "float", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Product updated",
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
            'product_name'     => ['sometimes', 'required', 'string', 'max:150'],
            'product_status'   => ['sometimes', 'required', 'in:new,hot,warm,cold,converted'],
            'description'      => ['nullable', 'string', 'max:500'],
            'unit_price'       => ['sometimes', 'required', 'numeric', 'min:0'],
            'quantity'         => ['sometimes', 'required', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $product->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Product updated.',
            'data'    => $this->formatProduct($product),
        ]);
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
            new OA\Response(response: 200, description: "Product deleted",
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
                    new OA\Property(property: "payment_mode",     type: "string", enum: ["cash","bank_transfer","cheque","upi","card"], example: "upi"),
                    new OA\Property(property: "payment_date",     type: "string", format: "date", example: "2027-01-15"),
                    new OA\Property(property: "reference_number", type: "string", nullable: true, example: "UPI/2027/001234"),
                    new OA\Property(property: "notes",            type: "string", nullable: true, example: "Advance payment"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Payment recorded",
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
    return back()->with('error', 'Payments can be added only after the product status is Converted.');
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
            new OA\Response(response: 200, description: "Payment deleted",
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
            new OA\Response(response: 201, description: "Quotation created",
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: "status",  type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string",  example: "Quotation QT-0001 created."),
                    new OA\Property(property: "data",    type: "object"),
                ])
            ),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/UnauthenticatedResponse")),
            new OA\Response(response: 404, description: "Lead not found",  content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error",content: new OA\JsonContent(ref: "#/components/schemas/ValidationErrorResponse")),
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
            new OA\Response(response: 200, description: "Status updated",
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
            new OA\Response(response: 200, description: "Quotation deleted",
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

    // ════════════════════════════════════════════════════════════════
    // PRIVATE FORMATTERS
    // ════════════════════════════════════════════════════════════════

    private function formatCall(LeadCallUpdate $call): array
    {
        return [
            'id'               => $call->id,
            'called_at'        => $call->called_at?->toIso8601String(),
            'call_type'        => $call->call_type,
            'call_type_label'  => $call->call_type_label,
            'duration_minutes' => $call->duration_minutes,
            'outcome'          => $call->outcome,
            'outcome_label'    => $call->outcome_label,
            'outcome_color'    => $call->outcome_color,
            'notes'            => $call->notes,
            'next_follow_up'   => $call->next_follow_up?->toDateString(),
            'user'             => $call->user ? ['id' => $call->user->id, 'name' => $call->user->name] : null,
        ];
    }

    private function formatReminder(LeadReminder $reminder): array
    {
        return [
            'id'           => $reminder->id,
            'title'        => $reminder->title,
            'description'  => $reminder->description,
            'remind_at'    => $reminder->remind_at?->toIso8601String(),
            'type'         => $reminder->type,
            'type_label'   => $reminder->type_label,
            'type_icon'    => $reminder->type_icon,
            'priority'     => $reminder->priority,
            'is_completed' => (bool) $reminder->is_completed,
            'is_overdue'   => $reminder->is_overdue,
            'completed_at' => $reminder->completed_at?->toIso8601String(),
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
}