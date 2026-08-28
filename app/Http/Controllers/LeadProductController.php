<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\ProductionInitiation;
use App\Models\ProductionWorkflowMapping;
use App\Models\Role;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\ProductionCountReport;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LeadProductController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    // ══════════════════════════════════════════════════════════════════
    //  PRODUCTS
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/products
     * Returns all active products for the multi-select dropdown.
     */
    public function productList(Request $request): JsonResponse
    {
        $query = Product::with('category')
            ->where('status', 'active')
            ->orderBy('sort_order');

        if ($request->filled('lead_id')) {
            $lead = Lead::find($request->lead_id);
            if ($lead && $lead->company_id) {
                $query->withoutGlobalScope('company')
                    ->where('company_id', $lead->company_id);
            } else {
                $this->visibility->applyProductVisibility($query);
            }
        } else {
            $this->visibility->applyProductVisibility($query);
        }

        $products = $query
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->package_name,
                'category'    => $p->category?->name,
                'description' => $p->description,
                'price'       => (float) $p->final_price,
                'base_price'  => (float) $p->base_price,
                'discount_type' => $p->discount_type,
                'discount_value' => (float) $p->discount_value,
                'discount_percent' => $p->discount_type === 'percentage'
                    ? (float) $p->discount_value
                    : ((float) $p->base_price > 0
                        ? round(((float) $p->discount_value / (float) $p->base_price) * 100, 2)
                        : 0),
            ]);

        return response()->json(['data' => $products]);
    }

    /**
     * GET /api/products/{id}
     * Returns single product details.
     */
    public function productDetail(Product $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);

        $product->loadMissing('ovpFormFields');

        return response()->json([
            'data' => [
                'id'          => $product->id,
                'name'        => $product->package_name,
                'category'    => $product->category?->name,
                'description' => $product->description,
                'price'       => (float) $product->final_price,
                'base_price'  => (float) $product->base_price,
                'discount_type' => $product->discount_type,
                'discount_value' => (float) $product->discount_value,
                'discount_percent' => $product->discount_type === 'percentage'
                    ? (float) $product->discount_value
                    : ((float) $product->base_price > 0
                        ? round(((float) $product->discount_value / (float) $product->base_price) * 100, 2)
                        : 0),
                'attributes'  => $product->attributeValues->map(fn($av) => [
                    'name'  => $av->attribute->name,
                    'value' => $av->value,
                    'unit'  => $av->attribute->unit,
                ])->toArray(),
                'ovp_form_schema' => $product->ovpFormFields
                    ->where('is_active', true)
                    ->where('use_in_production_initiation', true)
                    ->values()
                    ->map(fn ($field) => [
                        'id' => $field->id,
                        'label' => $field->label,
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        'placeholder' => $field->placeholder,
                        'help_text' => $field->help_text,
                        'default_value' => $field->default_value,
                        'is_required' => $field->is_required,
                        'use_in_production_initiation' => $field->use_in_production_initiation,
                        'options' => $field->options ?? [],
                        'validation_rules' => $field->validation_rules ?? [],
                    ])->all(),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    //  LEAD PRODUCTS (Deals)
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/lead-products/{lead_id}
     * Returns all deals grouped by deal_name for the accordion UI.
     */
    public function index(int $leadId): JsonResponse
    {


        $lead = Lead::findOrFail($leadId);
        // abort_unless($this->visibility->canAccessLead($lead), 403);

        $statusOptions = $this->statusOptionsForLead($lead);

        $products = LeadProduct::with([
                'payments.recordedBy',
                'leadStatus',
                'product.departments:id,name',
                'latestProductionInitiation.department:id,name',
            ])
            ->where('lead_id', $leadId)
            ->latest()
            ->get();

        // Group into deals (accordion)
        $deals = $products->groupBy('deal_name')->map(function ($items, $dealName) use ($statusOptions) {
            $totalValue   = $items->sum('total_price');
            // Calculate total paid from payments for each product
            $totalPaid    = $items->sum(fn($p) => $p->amount_paid);
            $totalPending = $totalValue - $totalPaid;
            $status       = $this->resolveStatusPayload($items->first(), $statusOptions);

            return [
                'deal_name'     => $dealName,
                'status'        => $status['value'],
                'status_id'     => $status['id'],
                'status_label'  => $status['label'],
                'total_value'   => round($totalValue, 2),
                'total_paid'    => round($totalPaid, 2),
                'total_pending' => round($totalPending, 2),
                'products'      => $items->map(fn($p) => $p->toJsPayload())->values(),
            ];
        })->values();

        // Overall summary
        $summary = [
            'total_value'   => round($products->sum('total_price'), 2),
            'total_paid'    => round($products->sum(fn($p) => $p->amount_paid), 2),
            'total_pending' => round($products->sum(fn($p) => $p->amount_pending), 2),
            'product_count' => $products->count(),
            'converted'     => $products->filter(fn ($p) => $this->productStatusKey($p) === 'converted')->count(),
        ];

        return response()->json([
            'deals'    => $deals,
            'summary'  => $summary,
            'statuses' => $statusOptions->values()->map(fn ($status) => [
                'id'   => $status->id,
                'name' => $status->name,
            ]),
        ]);
    }

    /**
     * POST /api/lead-products
     * Creates a new deal with multiple products.
     */
    public function store(Request $request): JsonResponse
    {

        $user = auth()->user();

        $v = Validator::make($request->all(), [
            'lead_id'            => ['required', 'exists:leads,id'],
            'deal_name'          => ['required', 'string', 'max:255'],
            'products'           => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.remarks'    => ['nullable', 'string', 'max:1000'],
            'products.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'products.*.quantity'   => ['nullable', 'integer', 'min:1'],
            'products.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $lead = Lead::findOrFail($request->lead_id);
        abort_unless($this->visibility->canAccessLead($lead), 403);
        $defaultStatus = $this->defaultStatusForLead($lead);

        if ($user->allowsPriceRequests() && !$this->isAdmin($user)) {
            foreach ($request->products as $row) {
                $product = Product::findOrFail($row['product_id']);
                abort_unless($this->visibility->canAccessProduct($product), 403);
                $requestedPrice = round((float) ($row['unit_price'] ?? $product->final_price), 2);
                $defaultPrice = round((float) $product->final_price, 2);

                if ($requestedPrice !== $defaultPrice) {
                    return response()->json([
                        'message' => 'Price was changed. Please send a price change request for admin approval.',
                    ], 422);
                }
            }
        }

        $created = DB::transaction(function () use ($request, $defaultStatus) {
            $rows = [];
            foreach ($request->products as $row) {
                $product  = Product::findOrFail($row['product_id']);
                abort_unless($this->visibility->canAccessProduct($product), 403);
                $unitPrice = $row['unit_price'] ?? $product->final_price;
                $qty       = $row['quantity'] ?? 1;
                $disc      = $row['discount_percent'] ?? 0;

                $rows[] = LeadProduct::create([
                    'lead_id'          => $request->lead_id,
                    'product_id'       => $product->id,
                    'deal_name'        => $request->deal_name,
                    'product_name'     => $product->package_name,
                    'description'      => $product->description,
                    'unit_price'       => $unitPrice,
                    'company_id'       => $request->company_id,
                    'quantity'         => $qty,
                    'discount_percent' => $disc,
                    'remarks'          => $row['remarks'] ?? null,
                    'product_status'   => LeadProduct::statusKey($defaultStatus?->name ?? 'new'),
                    'lead_status_id'   => $defaultStatus?->id,
                    'created_by'       =>  auth()->id(),
                ]);
            }
            return $rows;
        });

        return response()->json([
            'message'  => 'Deal created successfully.',
            'products' => array_map(fn($p) => $p->toJsPayload(), $created),
        ], 201);
    }

    /**
     * PUT /api/lead-products/status
     * Updates status for a single product or every product in a deal.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'lead_id'         => ['required', 'exists:leads,id'],
            'deal_name'       => ['nullable', 'string'],
            'product_id'      => ['nullable', 'integer', 'exists:lead_products,id'],
            'lead_status_id'  => ['nullable', 'integer', 'exists:lead_statuses,id'],
            'product_status'  => ['nullable', 'string'],
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $lead = Lead::findOrFail($request->lead_id);
        abort_unless($this->visibility->canAccessLead($lead), 403);
        $status = $this->resolveRequestedStatus($lead, $request);

        if (! $request->filled('product_id') && ! $request->filled('deal_name')) {
            return response()->json([
                'errors' => ['product_id' => ['Please select a product or deal to update.']],
            ], 422);
        }

        if (! $status) {
            return response()->json([
                'errors' => ['lead_status_id' => ['Please select a valid lead status.']],
            ], 422);
        }

        $updatePayload = [
            'lead_status_id' => $status->id,
            'product_status' => LeadProduct::statusKey($status->name),
        ];

        if ($request->filled('product_id')) {
            $product = LeadProduct::where('lead_id', $request->lead_id)
                ->whereKey((int) $request->product_id)
                ->firstOrFail();

            if ($this->productStatusKey($product) === 'converted') {
                return response()->json([
                    'errors' => ['product_status' => ['Converted product status cannot be changed again.']],
                ], 422);
            }

            $product->update($updatePayload);
        } else {
            $products = LeadProduct::where('lead_id', $request->lead_id)
                ->where('deal_name', $request->deal_name)
                ->get();

            if ($products->contains(fn (LeadProduct $product) => $this->productStatusKey($product) === 'converted')) {
                return response()->json([
                    'errors' => ['product_status' => ['Converted product status cannot be changed again.']],
                ], 422);
            }

            LeadProduct::whereIn('id', $products->pluck('id'))->update($updatePayload);
        }

        return response()->json([
            'message' => 'Status updated.',
            'status'  => [
                'id'   => $status->id,
                'name' => $status->name,
            ],
        ]);
    }

    /**
     * PUT /api/lead-products/{id}
     * Updates a single product inside a lead deal.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        $v = Validator::make($request->all(), [
            'deal_name'        => ['required', 'string', 'max:255'],
            'product_id'       => ['required', 'integer', 'exists:products,id'],
            'unit_price'       => ['required', 'numeric', 'min:0'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'remarks'          => ['nullable', 'string', 'max:1000'],
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $leadProduct = LeadProduct::with(['lead', 'product'])->findOrFail($id);
        abort_unless($leadProduct->lead && $this->visibility->canAccessLead($leadProduct->lead), 403);

        if ($leadProduct->product) {
            abort_unless($this->visibility->canAccessProduct($leadProduct->product), 403);
        }

        $catalogProduct = Product::with('category')->findOrFail((int) $request->product_id);
        abort_unless($this->visibility->canAccessProduct($catalogProduct), 403);

        $requestedPrice = round((float) $request->unit_price, 2);
        $currentPrice = round((float) $leadProduct->unit_price, 2);
        $baselinePrice = (int) $leadProduct->product_id === (int) $catalogProduct->id
            ? $currentPrice
            : round((float) $catalogProduct->final_price, 2);

        if ($user->allowsPriceRequests() && !$this->isAdmin($user) && $requestedPrice !== $baselinePrice) {
            return response()->json([
                'message' => 'Price was changed. Please send a price change request for admin approval.',
            ], 422);
        }

        $leadProduct->update([
            'deal_name'        => $request->deal_name,
            'product_id'       => $catalogProduct->id,
            'product_name'     => $catalogProduct->product_name
                ?: trim(($catalogProduct->category?->name ? $catalogProduct->category->name . ' | ' : '') . $catalogProduct->package_name),
            'unit_price'       => $requestedPrice,
            'quantity'         => (int) $request->quantity,
            'discount_percent' => (float) ($request->discount_percent ?? 0),
            'remarks'          => $request->remarks,
        ]);

        $leadProduct->load(['payments.recordedBy', 'leadStatus', 'product.departments:id,name', 'latestProductionInitiation.department:id,name']);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $leadProduct->toJsPayload(),
        ]);
    }

    /**
     * DELETE /api/lead-products/{id}
     * Soft-deletes a single lead product.
     */
    public function destroy(int $id): JsonResponse
    {
        $lp = LeadProduct::with('lead')->findOrFail($id);
        abort_unless($lp->lead && $this->visibility->canAccessLead($lp->lead), 403);
        $lp->delete();
        return response()->json(['message' => 'Product removed from deal.']);
    }

    public function productionDetail(int $leadProductId): JsonResponse
    {
        $leadProduct = LeadProduct::with(['lead', 'product.departments:id,name'])
            ->findOrFail($leadProductId);
        abort_unless($leadProduct->lead && $this->visibility->canAccessLead($leadProduct->lead), 403);

        if ($this->productStatusKey($leadProduct) !== 'converted') {
            return response()->json([
                'message' => 'Production can be initiated only after the product status is Converted.',
            ], 422);
        }

        if ($leadProduct->payments()->count() < 1) {
            return response()->json([
                'message' => 'At least 1 received payment is required before moving this product to production.',
            ], 422);
        }

        $product = $leadProduct->product;
        $departments = $product?->departments ?? collect();
        $workflowMappings = ProductionWorkflowMapping::query()
            ->whereIn('department_id', $departments->pluck('id'))
            ->get()
            ->keyBy('department_id');
        $roles = Role::query()->get();

        $departmentPayload = $departments->map(function ($department) use ($workflowMappings, $roles) {
            $mapping = $workflowMappings->get($department->id);
            $workflow = $this->buildProductionWorkflowPreview(
                $mapping?->workflow_data ?? [],
                $roles
            );

            return [
                'id' => $department->id,
                'name' => $department->name,
                'is_development' => str_contains(strtolower($department->name), 'development'),
                'workflow_mapped' => ! empty($workflow['stages']),
                'workflow' => $workflow,
            ];
        })->values();

        return response()->json([
            'product' => [
                'lead_product_id' => $leadProduct->id,
                'product_id' => $product?->id,
                'name' => $product?->package_name ?: $leadProduct->product_name,
            ],
            'lead' => [
                'contact_name' => $leadProduct->lead?->contact_name,
                'mobile_number' => $leadProduct->lead?->mobile_number,
                'email' => $leadProduct->lead?->email,
                'company_name' => $leadProduct->lead?->company_name,
            ],
            'ovp_form_schema' => $product?->ovpFormFields()
                ->where('is_active', true)
                ->where('use_in_production_initiation', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get([
                    'id',
                    'label',
                    'field_name',
                    'field_type',
                    'placeholder',
                    'help_text',
                    'default_value',
                    'is_required',
                    'use_in_production_initiation',
                    'options',
                    'validation_rules',
                ]) ?? [],
            'departments' => $departmentPayload,
            'latest_initiation' => ProductionInitiation::query()
                ->where('lead_product_id', $leadProduct->id)
                ->latest()
                ->first([
                    'id',
                    'department_id',
                    'product_name',
                    'total_working_days',
                    'ui_available',
                    'requirements',
                    'attachment_name',
                    'status',
                    'custom_form_data',
                    'created_at',
                ]),
        ]);
    }

    public function storeProductionInitiation(Request $request, int $leadProductId): JsonResponse
    {
        $leadProduct = LeadProduct::with(['lead', 'product.departments:id,name'])
            ->findOrFail($leadProductId);
        abort_unless($leadProduct->lead && $this->visibility->canAccessLead($leadProduct->lead), 403);

        if ($this->productStatusKey($leadProduct) !== 'converted') {
            return response()->json([
                'errors' => ['product_status' => ['Production can be initiated only after the product status is Converted.']],
            ], 422);
        }

        if ($leadProduct->payments()->count() < 1) {
            return response()->json([
                'errors' => ['payments' => ['At least 1 received payment is required before moving this product to production.']],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'total_working_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'ui_available' => ['nullable', 'boolean'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $departmentId = (int) $request->input('department_id');
        $mappedDepartment = $leadProduct->product?->departments?->firstWhere('id', $departmentId);

        if (! $mappedDepartment) {
            return response()->json([
                'errors' => ['department_id' => ['The selected department is not mapped to this product.']],
            ], 422);
        }

        $workflowMapping = ProductionWorkflowMapping::query()
            ->where('department_id', $mappedDepartment->id)
            ->first();

        if (! $workflowMapping) {
            return response()->json([
                'errors' => ['department_id' => ['No production workflow mapping is configured for the selected department.']],
            ], 422);
        }

        $roles = Role::query()->get();
        $workflowPreview = $this->buildProductionWorkflowPreview($workflowMapping->workflow_data ?? [], $roles);
        $latestInitiation = ProductionInitiation::query()
            ->where('lead_product_id', $leadProduct->id)
            ->latest()
            ->first();
        $attachment = $request->file('attachment');
        $attachmentPath = (string) ($latestInitiation?->attachment_path ?? '');
        if ($attachment) {
            $targetDir = public_path('uploads/production-initiations');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            $extension = $attachment->getClientOriginalExtension();
            $filename = time() . '_' . uniqid('pi_') . ($extension ? '.' . $extension : '');
            $attachment->move($targetDir, $filename);
            $attachmentPath = 'uploads/production-initiations/' . $filename;
        }
        $customFormData = $this->prepareProductCustomizationData($request, $leadProduct->product);
        $requirements = trim((string) $request->input('requirements', (string) ($latestInitiation?->requirements ?? '')));

        if ($requirements === '') {
            $requirements = 'Submitted via production customization form.';
        }

        $initiation = DB::transaction(function () use (
            $attachment,
            $attachmentPath,
            $customFormData,
            $leadProduct,
            $latestInitiation,
            $mappedDepartment,
            $request,
            $requirements,
            $workflowPreview
        ) {
            $initiation = ProductionInitiation::create([
                'company_id' => $leadProduct->company_id ?: $leadProduct->lead?->company_id ?: auth()->user()?->company_id,
                'lead_id' => $leadProduct->lead_id,
                'lead_product_id' => $leadProduct->id,
                'product_id' => $leadProduct->product_id,
                'department_id' => $mappedDepartment->id,
                'product_name' => $request->input('product_name'),
                'total_working_days' => (int) ($request->input('total_working_days') ?: ($latestInitiation?->total_working_days ?: 1)),
                'ui_available' => $request->has('ui_available')
                    ? (bool) $request->boolean('ui_available')
                    : (bool) ($latestInitiation?->ui_available ?? false),
                'requirements' => $requirements,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachment
                    ? $attachment->getClientOriginalName()
                    : (string) ($latestInitiation?->attachment_name ?? ''),
                'workflow_snapshot' => $workflowPreview,
                'custom_form_data' => $customFormData,
                'status' => 'ovp_pending',
                'ovp_allocation_status' => 'allocation_pending',
                'ovp_allocated_to' => null,
                'ovp_allocated_by' => null,
                'ovp_allocated_at' => null,
                'initiated_by' => auth()->id(),
            ]);

            $this->storeCountWiseReportForInitiation($initiation, $leadProduct, $mappedDepartment->id);

            return $initiation;
        });

        // Send Email notification to Customer Success Team & CC
        try {
            // $toEmails = ['kesavaraj@saitechnosolutions.net'];
            $toEmails = ['customersuccess@saitechnosolutions.net', 'customersuccessteam.sts@gmail.com'];
            $ccEmail = 'tamilarasan@saitechnosolutions.net';
            // $ccEmail = 'kesavarajs.sts@gmail.com';

            $lead = $leadProduct->lead;
            $initiatedBy = auth()->user();

            Mail::send('emails.production_initiation', [
                'initiation' => $initiation,
                'leadProduct' => $leadProduct,
                'lead' => $lead,
                'departmentName' => $mappedDepartment->name,
                'initiatedBy' => $initiatedBy,
            ], function ($message) use ($toEmails, $ccEmail, $leadProduct, $initiation) {
                $message->to($toEmails)
                    ->cc($ccEmail)
                    ->subject('New Production Initiation - Lead #' . $leadProduct->lead_id . ' (' . ($initiation->product_name ?: $leadProduct->product_name) . ')');
            });
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Failed to send production initiation email.', [
                'initiation_id' => $initiation->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Production initiation submitted successfully.',
            'initiation' => [
                'id' => $initiation->id,
                'department_name' => $mappedDepartment->name,
                'product_name' => $initiation->product_name,
                'total_working_days' => $initiation->total_working_days,
                'ui_available' => $initiation->ui_available,
                'requirements' => $initiation->requirements,
                'attachment_name' => $initiation->attachment_name,
                'custom_form_data' => $initiation->custom_form_data,
                'status' => $initiation->status,
            ],
            'workflow' => $workflowPreview,
        ], 201);
    }

    private function storeCountWiseReportForInitiation(
        ProductionInitiation $initiation,
        LeadProduct $leadProduct,
        int $departmentId
    ): void {
        $product = $leadProduct->product;

        if (! $product?->count_wise_report) {
            return;
        }

        $counts = $this->countWiseProductCounts($product);

        ProductionCountReport::updateOrCreate(
            ['production_initiation_id' => $initiation->id],
            [
                'company_id' => $initiation->company_id,
                'lead_id' => $leadProduct->lead_id,
                'lead_product_id' => $leadProduct->id,
                'product_id' => $leadProduct->product_id,
                'department_id' => $departmentId,
                'poster_count' => $counts['poster_count'],
                'video_count' => $counts['video_count'],
                'status' => 'initiated',
            ]
        );
    }

    private function countWiseProductCounts(Product $product): array
    {
        $product->loadMissing('attributeValues.attribute');

        return [
            'poster_count' => $this->firstAttributeCount($product, ['posters_count', 'poster_count']),
            'video_count' => $this->firstAttributeCount($product, ['video_count', 'videos_count']),
        ];
    }

    private function firstAttributeCount(Product $product, array $keys): int
    {
        foreach ($product->attributeValues as $attributeValue) {
            $attributeKey = strtolower((string) ($attributeValue->attribute?->key ?? ''));
            $attributeName = strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) ($attributeValue->attribute?->name ?? '')));

            if (! in_array($attributeKey, $keys, true) && ! in_array(trim($attributeName, '_'), $keys, true)) {
                continue;
            }

            return max(0, (int) $attributeValue->value);
        }

        return 0;
    }

    private function prepareProductCustomizationData(Request $request, ?Product $product): array
    {
        if (! $product) {
            return [];
        }

        $fields = $product->ovpFormFields()
            ->where('is_active', true)
            ->where('use_in_production_initiation', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($fields->isEmpty()) {
            return [];
        }

        $rules = [];
        foreach ($fields as $field) {
            $fieldKey = 'custom_fields.' . $field->field_name;
            $fileKey = 'custom_files.' . $field->field_name;
            $baseRules = $field->is_required ? ['required'] : ['nullable'];

            switch ($field->field_type) {
                case 'number':
                    $rules[$fieldKey] = array_merge($baseRules, ['numeric']);
                    if (($field->validation_rules['min'] ?? null) !== null) {
                        $rules[$fieldKey][] = 'min:' . $field->validation_rules['min'];
                    }
                    if (($field->validation_rules['max'] ?? null) !== null) {
                        $rules[$fieldKey][] = 'max:' . $field->validation_rules['max'];
                    }
                    break;
                case 'checkbox':
                    $rules[$fieldKey] = array_merge($baseRules, ['array']);
                    $rules[$fieldKey . '.*'] = ['string'];
                    break;
                case 'select':
                case 'radio':
                    $allowed = collect($field->options ?? [])->pluck('value')->filter()->all();
                    $rules[$fieldKey] = array_merge($baseRules, ['string'], $allowed ? ['in:' . implode(',', $allowed)] : []);
                    break;
                case 'date':
                    $rules[$fieldKey] = array_merge($baseRules, ['date']);
                    break;
                case 'file':
                    $rules[$fileKey] = array_merge($baseRules, ['file', 'max:10240']);
                    break;
                default:
                    $rules[$fieldKey] = array_merge($baseRules, ['string', 'max:5000']);
                    break;
            }
        }

        Validator::make($request->all(), $rules)->validate();

        $customValues = (array) $request->input('custom_fields', []);
        $stored = [];

        foreach ($fields as $field) {
            $fieldName = $field->field_name;

            if ($field->field_type === 'file') {
                $uploadedFile = $request->file('custom_files.' . $fieldName);
                if (! $uploadedFile) {
                    continue;
                }

                $targetDir = public_path('uploads/production-initiations/custom-fields');
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $extension = $uploadedFile->getClientOriginalExtension();
                $filename = time() . '_' . uniqid('cf_') . ($extension ? '.' . $extension : '');
                $uploadedFile->move($targetDir, $filename);
                $path = 'uploads/production-initiations/custom-fields/' . $filename;

                $stored[] = [
                    'field_id' => $field->id,
                    'field_name' => $fieldName,
                    'label' => $field->label,
                    'type' => $field->field_type,
                    'value' => [
                        'path' => $path,
                        'name' => $uploadedFile->getClientOriginalName(),
                        'url' => asset($path),
                    ],
                ];

                continue;
            }

            $value = $customValues[$fieldName] ?? null;

            if (is_array($value)) {
                $value = array_values(array_filter($value, fn ($item) => $item !== null && $item !== ''));
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $stored[] = [
                'field_id' => $field->id,
                'field_name' => $fieldName,
                'label' => $field->label,
                'type' => $field->field_type,
                'value' => $value,
            ];
        }

        return $stored;
    }

    // ══════════════════════════════════════════════════════════════════
    //  PAYMENTS
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/payments/{lead_product_id}
     * Returns payment history for one lead product + overall for lead.
     */
    public function paymentHistory(int $leadProductId): JsonResponse
    {
        $lp = LeadProduct::with(['lead', 'payments.recordedBy', 'product.departments:id,name'])->findOrFail($leadProductId);
        // abort_unless($lp->lead && $this->visibility->canAccessLead($lp->lead), 403);

        // Overall payments for the lead
        $overall = LeadProductPayment::with(['leadProduct', 'recordedBy'])
            ->where('lead_id', $lp->lead_id)
            ->latest('payment_date')
            ->get()
            ->map(fn($p) => $p->toJsPayload());

        return response()->json([
            'product'  => $lp->toJsPayload(),
            'overall'  => $overall,
        ]);
    }

    /**
     * POST /api/payments
     * Stores a new payment and recalculates lead product's total_paid.
     */
    public function storePayment(Request $request): JsonResponse
    {
        $actorId = (int) ($request->user()?->id ?? auth()->id() ?? 0);
        if ($actorId <= 0) {
            return response()->json([
                'message' => 'Authentication required to record payments.',
            ], 401);
        }

        $lp = LeadProduct::with('lead')->findOrFail($request->lead_product_id);
        // abort_unless($lp->lead && $this->visibility->canAccessLead($lp->lead), 403);

        if ($lp->product_status_key !== 'converted') {
            return response()->json([
                'errors' => ['product_status' => ['Payments can be added only after the product status is Converted.']],
            ], 422);
        }

        // Calculate balance from payments table (dynamic)
        $balancePayment = $lp->total_price - $lp->amount_paid;

        if($balancePayment < $request->amount)
            {
        return response()->json([
            'status'  => 'Error',
            'message'  => 'Please Check you payment',
        ], 500);
            }

        $v = Validator::make($request->all(), [
            'lead_product_id' => ['required', 'exists:lead_products,id'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'payment_mode'    => ['required', 'in:cash,bank_transfer,cheque,upi,card'],
            'payment_date'    => ['required', 'date'],
            'reference_number'=> ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'attachment'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $lp = LeadProduct::with('lead')->findOrFail($request->lead_product_id);
        // abort_unless($lp->lead && $this->visibility->canAccessLead($lp->lead), 403);

        $payment = DB::transaction(function () use ($request, $lp, $actorId) {
            $attachment = $request->file('attachment');
            $attachmentPath = null;
            $attachmentName = null;

            if ($attachment) {
                $attachmentName = $attachment->getClientOriginalName();
                $filename = time() . '_' . Str::random(8) . '.' . $attachment->getClientOriginalExtension();
                $targetDir = public_path('uploads/lead-product-payments');
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $attachment->move($targetDir, $filename);
                $attachmentPath = 'uploads/lead-product-payments/' . $filename;
            }

            $p = LeadProductPayment::create([
                'lead_product_id' => $lp->id,
                'lead_id'         => $lp->lead_id,
                'amount'          => $request->amount,
                'payment_mode'    => $request->payment_mode,
                'payment_date'    => $request->payment_date,
                'reference_number'=> $request->reference_number,
                'notes'           => $request->notes,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'recorded_by'     => $actorId,
            ]);
            return $p;
        });

        $payment->load('recordedBy');

        return response()->json([
            'message'  => 'Payment recorded.',
            'payment'  => $payment->toJsPayload(),
            'product'  => $lp->fresh()->toJsPayload(),
        ], 201);
    }

    /**
     * DELETE /api/payments/{id}
     * Removes a payment and recalculates totals.
     */
    public function destroyPayment(int $id): JsonResponse
    {
        $payment = LeadProductPayment::findOrFail($id);
        $lp      = $payment->leadProduct()->with('lead')->first();
        // abort_unless($lp && $lp->lead && $this->visibility->canAccessLead($lp->lead), 403);

        DB::transaction(function () use ($payment, $lp) {
            if ($payment->attachment_path) {
                $publicFile = public_path($payment->attachment_path);
                if (file_exists($publicFile)) {
                    @unlink($publicFile);
                } elseif (Storage::disk('public')->exists($payment->attachment_path)) {
                    Storage::disk('public')->delete($payment->attachment_path);
                }
            }
            $payment->delete();
            $lp->recalcPaid();
        });

        return response()->json([
            'message' => 'Payment removed.',
            'product' => $lp->fresh()->toJsPayload(),
        ]);
    }

    private function statusOptionsForLead(Lead $lead)
    {
        $companyId = $lead->company_id ?? Auth::user()?->company_id;

        return LeadStatus::query()
            ->when(
                $companyId,
                fn ($query) => $query->where(fn ($statusQuery) => $statusQuery
                    ->where('company_id', $companyId)
                    ->orWhereNull('company_id')),
                fn ($query) => $query->whereNull('company_id')
            )
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);
    }

    private function defaultStatusForLead(Lead $lead): ?LeadStatus
    {
        $statuses = $this->statusOptionsForLead($lead);

        return $statuses->first(fn ($status) => LeadProduct::statusKey($status->name) === 'new')
            ?? $statuses->first();
    }

    private function resolveRequestedStatus(Lead $lead, Request $request): ?LeadStatus
    {
        $statuses = $this->statusOptionsForLead($lead);

        if ($request->filled('lead_status_id')) {
            return $statuses->firstWhere('id', (int) $request->lead_status_id);
        }

        if ($request->filled('product_status')) {
            $requestedKey = LeadProduct::statusKey($request->product_status);

            return $statuses->first(fn ($status) => LeadProduct::statusKey($status->name) === $requestedKey);
        }

        return null;
    }

    private function resolveStatusPayload(LeadProduct $product, $statusOptions): array
    {
        $status = $product->leadStatus;

        if (! $status && $product->lead_status_id) {
            $status = $statusOptions->firstWhere('id', (int) $product->lead_status_id);
        }

        if (! $status && $product->product_status) {
            $statusKey = LeadProduct::statusKey($product->product_status);
            $status = $statusOptions->first(fn ($option) => LeadProduct::statusKey($option->name) === $statusKey);
        }

        $label = $status?->name ?: $product->status_label ?: 'New';

        return [
            'id'    => $status?->id,
            'value' => $status?->id ? (string) $status->id : LeadProduct::statusKey($label),
            'label' => $label,
        ];
    }

    private function productStatusKey(LeadProduct $product): string
    {
        return LeadProduct::statusKey($product->leadStatus?->name ?? $product->product_status);
    }

    protected function isAdmin($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'Super Admin', 'admin']);
    }

    private function buildProductionWorkflowPreview(array $workflowData, $roles): array
    {
        $stageDefinitions = $this->productionWorkflowStageDefinitions();
        $roleNames = $roles->mapWithKeys(fn (Role $role) => [
            (int) $role->id => $role->display_name ?: str($role->name)->after('__')->replace('_', ' ')->title()->value(),
        ]);

        $stages = [];

        foreach ($stageDefinitions as $stageKey => $stage) {
            $payload = $workflowData[$stageKey] ?? [];
            $groups = [];

            foreach ($stage['role_fields'] as $fieldKey => $field) {
                $names = collect($payload[$fieldKey] ?? [])
                    ->map(fn ($roleId) => $roleNames[(int) $roleId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $groups[] = [
                    'label' => $field['label'],
                    'roles' => $names,
                ];
            }

            $notes = [];

            foreach ($stage['text_fields'] as $fieldKey => $field) {
                $value = trim((string) ($payload[$fieldKey] ?? ''));

                if ($value !== '') {
                    $notes[] = [
                        'label' => $field['label'],
                        'value' => $value,
                    ];
                }
            }

            $stages[] = [
                'key' => $stageKey,
                'step' => $stage['step'],
                'title' => $stage['title'],
                'description' => $stage['description'],
                'groups' => $groups,
                'notes' => $notes,
            ];
        }

        return ['stages' => $stages];
    }

    private function productionWorkflowStageDefinitions(): array
    {
        return [
            'production_initiation' => [
                'step' => 'Stage 1',
                'title' => 'Production Initiation',
                'description' => 'The entry point where the production request is initiated by the business or intake team.',
                'role_fields' => [
                    'initiation_roles' => ['label' => 'Initiation Roles'],
                ],
                'text_fields' => [
                    'priority_template' => ['label' => 'Priority Template'],
                ],
            ],
            'ovp_team_review' => [
                'step' => 'Stage 2',
                'title' => 'OVP Team Review',
                'description' => 'The stage where the OVP team reviews and verifies the incoming request.',
                'role_fields' => [
                    'ovp_review_roles' => ['label' => 'OVP Review Roles'],
                    'business_team_roles' => ['label' => 'Business Team Roles'],
                ],
                'text_fields' => [],
            ],
            'production_approval_team' => [
                'step' => 'Stage 3',
                'title' => 'Production Approval Team',
                'description' => 'The stage for approval-side roles and checklist verification after OVP review.',
                'role_fields' => [
                    'approval_roles' => ['label' => 'Approval Roles'],
                ],
                'text_fields' => [
                    'approval_checklist' => ['label' => 'Approval Checklist'],
                ],
            ],
            'project_coordinator' => [
                'step' => 'Stage 4',
                'title' => 'Project Coordinator',
                'description' => 'The stage where the approved request is received and the project scope and timeline are coordinated.',
                'role_fields' => [
                    'coordinator_roles' => ['label' => 'Project Coordinator Roles'],
                ],
                'text_fields' => [
                    'timeline_template' => ['label' => 'Timeline Template'],
                ],
            ],
            'allocation_multiple_tl_split' => [
                'step' => 'Stage 5 & 6',
                'title' => 'Allocation and Multiple TL Split',
                'description' => 'Configure the required roles for the Project Coordinator to TL to Developer allocation flow.',
                'role_fields' => [
                    'tl_roles' => ['label' => 'TL Roles'],
                    'developer_roles' => ['label' => 'Developer Roles'],
                ],
                'text_fields' => [
                    'allocation_note' => ['label' => 'Allocation Note'],
                ],
            ],
        ];
    }
}