<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\LeadProduct;
use App\Models\ProductOvpFormField;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductionInitiationApiController extends Controller
{
    public function schema(Request $request, LeadProduct $leadProduct): JsonResponse
    {
        $leadProduct->load(['product', 'latestProductionInitiation', 'lead']);

        $productId = $leadProduct->product_id;

        $fields = [];
        if ($productId) {
            $fields = ProductOvpFormField::query()
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->where('use_in_production_initiation', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn($f) => [
                    'id'                => $f->id,
                    'label'             => $f->label,
                    'field_type'        => $f->field_type,
                    'field_name'        => $f->field_name,
                    'placeholder'       => $f->placeholder,
                    'is_required'       => (bool) $f->is_required,
                    'options'           => $f->options ?? [],
                    'sort_order'        => $f->sort_order,
                    'help_text'         => $f->help_text ?? null,
                    'default_value'     => $f->default_value,
                    'validation_rules'  => $f->validation_rules ?? [],
                ])
                ->values()
                ->toArray();
        }

        // Build existing data from lead and lead product
        $lead = $leadProduct->lead;
        $existingData = [];

        // Map lead fields to OVP field names
        $fieldMapping = [
            'ovp_customer_name' => $lead?->contact_name,
            'ovp_e_mail_id' => $lead?->email,
            'ovp_mobile_number' => $lead?->mobile_number,
            'ovp_client_side_poc' => $lead?->contact_name,
            'ovp_company_name' => $lead?->company_name,
        ];

        // Get existing data from latest production initiation if available
        if ($leadProduct->latestProductionInitiation) {
            $existing = $leadProduct->latestProductionInitiation->custom_form_data ?? [];
            foreach ($existing as $item) {
                $existingData[$item['field_name'] ?? $item['label'] ?? ''] = $item['value'] ?? '';
            }
        }

        // If no production initiation exists, populate from lead data
        if (empty($existingData)) {
            foreach ($fieldMapping as $fieldName => $value) {
                if ($value !== null && $value !== '') {
                    $existingData[$fieldName] = $value;
                }
            }
        }

        // Also add the product name
        $existingData['product_name'] = $leadProduct->product_name ?? $leadProduct->product?->package_name ?? '';
        $product = $leadProduct->product;
        $departments = $product
            ? $product->departments()->get(['departments.id', 'departments.name'])
            : collect();

        return response()->json([
            'success' => true,
            'data' => [
                'lead_product_id'   => $leadProduct->id,
                'product_name'      => $leadProduct->product_name ?? $leadProduct->product?->package_name ?? '',
                'product_id'        => $productId,
                'already_initiated' => $leadProduct->latestProductionInitiation !== null,
                'fields'            => $fields,
                'existing_data'     => (object) $existingData,
                'departments'       => $departments->map(fn($d) => [
                    'id'   => $d->id,
                    'name' => $d->name,
                ])->values()->toArray(),
            ],
        ]);
    }

    public function store(Request $request, LeadProduct $leadProduct): JsonResponse
    {

        // $validator = Validator::make($request->all(), [
        //     'product_name'       => ['nullable', 'string', 'max:255'],
        //     'total_working_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        //     'ui_available'       => ['nullable', 'boolean'],
        //     'requirements'       => ['nullable', 'string', 'max:5000'],
        //     'attachment'         => ['nullable', 'file', 'max:10240'],
        // ]);
        // if ($validator->fails()) {
        //     return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        // }

        if ($leadProduct->latestProductionInitiation) {
            return response()->json([
                'success' => false,
                'message' => 'Production already initiated for this product.',
            ], 422);
        }

        $hasPayment = $leadProduct->payments()->exists() || $leadProduct->amount_paid >= 1;
        if (!$hasPayment) {
            return response()->json([
                'success' => false,
                'message' => 'A minimum payment of ₹1 is required before moving this lead to Production.',
            ], 422);
        }

        $leadProduct->load(['lead', 'product']);

        $productId = $leadProduct->product_id;
        $fields = [];
        if ($productId) {
            $fields = ProductOvpFormField::query()
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->where('use_in_production_initiation', true)
                ->orderBy('sort_order')
                ->get();
        }

        $fieldValues = json_decode((string) $request->input('fields', '{}'), true) ?: [];

        // ── Validate required fields ────────────────────────────────────────
        // File fields are validated against the uploaded file itself
        // (hasFile), not $fieldValues — the mobile client sends dynamic file
        // uploads as separate multipart parts keyed by field_name (see
        // ProductionInitiationProvider.submitInitiation()), so presence of
        // the file is the real source of truth for "is it filled in".
        $errors = [];
        foreach ($fields as $field) {
            if (! $field->is_required) {
                continue;
            }

            if ($field->field_type === 'file') {
                if (! $request->hasFile($field->field_name)) {
                    $errors[$field->field_name] = $field->label . ' is required.';
                }
                continue;
            }

            $val = $fieldValues[$field->field_name] ?? null;
            if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                $errors[$field->field_name] = $field->label . ' is required.';
            }
        }
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ], 422);
        }

        // ── Store any dynamically configured file fields ────────────────────
        // Every ProductOvpFormField with field_type === 'file' may have a
        // same-named multipart part in the request — no field name is
        // hardcoded, so this scales to any number of file fields (e.g.
        // 'ovp_file', 'ovp_document', or any future field added in the
        // Configured Fields admin screen) without further code changes.
        $uploadedFilesByField = [];
        foreach ($fields as $field) {
            if ($field->field_type !== 'file') {
                continue;
            }

            $uploadedFile = $request->file($field->field_name);
            if (! $uploadedFile) {
                continue;
            }

            $path = $uploadedFile->store('production-initiations', 'public');
            $uploadedFilesByField[$field->field_name] = [
                'path' => $path,
                'name' => $uploadedFile->getClientOriginalName(),
                'url'  => Storage::disk('public')->url($path),
            ];
        }

        // Legacy single "attachment" column — shown as a generic Attachment
        // link on the web Project Detail page (attachment_path/attachment_name).
        // The mobile form has no separate generic-attachment upload, only
        // dynamic OVP fields, so this is kept populated from the first
        // dynamically uploaded file for backward compatibility with that view.
        $firstUploadedFile = $uploadedFilesByField ? reset($uploadedFilesByField) : null;
        $attachmentPath = $firstUploadedFile['path'] ?? null;
        $attachmentName = $firstUploadedFile['name'] ?? null;

        // Build custom_form_data
        $customFormData = [];
        foreach ($fields as $field) {
            $value = $field->field_type === 'file'
                ? ($uploadedFilesByField[$field->field_name] ?? null)
                : ($fieldValues[$field->field_name] ?? null);

            $customFormData[] = [
                'field_id'   => $field->id,
                'field_name' => $field->field_name,
                'label'      => $field->label,
                'field_type' => $field->field_type,
                'value'      => $value,
            ];
        }

        $lead = $leadProduct->lead;

        // ── Get department like web does ──────────────────────────────────
        $departmentId = null;

        // First, try to get department from request
        if ($request->has('department_id') && $request->input('department_id')) {
            $departmentId = $request->input('department_id');
        } else {
            // Try to get department from product's departments
            $product = $leadProduct->product;
            if ($product && $product->departments()->exists()) {
                $firstDepartment = $product->departments()->first();
                if ($firstDepartment) {
                    $departmentId = $firstDepartment->id;
                }
            }

            // If still no department, get the first available department
            if (!$departmentId) {
                $firstDepartment = Department::first();
                if ($firstDepartment) {
                    $departmentId = $firstDepartment->id;
                }
            }
        }

        // ── Handle requirements like web does ──────────────────────────────
        // Web sets a default value if requirements is empty
        $requirements = $request->input('requirements');
        if (empty($requirements) || trim($requirements) === '') {
            $requirements = 'Submitted via production customization form.';
        }

        $initiation = $leadProduct->productionInitiations()->create([
            'company_id'             => $leadProduct->lead?->company_id,
            'lead_id'                => $leadProduct->lead_id,
            'lead_product_id'        => $leadProduct->id,
            'product_id'             => $leadProduct->product_id,
            'department_id'          => $departmentId,
            'product_name'           => $leadProduct->product_name ?? $leadProduct->product?->package_name ?? '',
            'total_working_days'     => $request->input('total_working_days') ?: 1,
            'requirements'           => $requirements,
            'custom_form_data'       => $customFormData,
            'status'                 => 'ovp_pending',
            'ovp_allocation_status'  => 'allocation_pending',
            'ovp_allocated_to'       => null,
            'ovp_allocated_by'       => null,
            'ovp_allocated_at'       => null,
            'company_name'           => $lead?->company_name ?? '',
            'client_name'            => $lead?->contact_name ?? '',
            'initiated_by'           => auth()->id(),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Production initiated successfully.',
            'data'    => [
                'id'         => $initiation->id,
                'status'     => $initiation->status,
                'created_at' => $initiation->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
