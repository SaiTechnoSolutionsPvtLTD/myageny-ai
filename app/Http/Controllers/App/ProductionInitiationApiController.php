<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\LeadProduct;
use App\Models\ProductOvpFormField;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                    'id'           => $f->id,
                    'label'        => $f->label,
                    'field_type'   => $f->field_type,
                    'field_name'   => $f->field_name,
                    'placeholder'  => $f->placeholder,
                    'is_required'  => (bool) $f->is_required,
                    'options'      => $f->options ?? [],
                    'sort_order'   => $f->sort_order,
                    'help_text'    => $f->help_text ?? null,
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

        return response()->json([
            'success' => true,
            'data' => [
                'lead_product_id'   => $leadProduct->id,
                'product_name'      => $leadProduct->product_name ?? $leadProduct->product?->package_name ?? '',
                'product_id'        => $productId,
                'already_initiated' => $leadProduct->latestProductionInitiation !== null,
                'fields'            => $fields,
                'existing_data'     => (object) $existingData,
            ],
        ]);
    }

    public function store(Request $request, LeadProduct $leadProduct): JsonResponse
    {
        if ($leadProduct->latestProductionInitiation) {
            return response()->json([
                'success' => false,
                'message' => 'Production already initiated for this product.',
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

        // Validate required fields
        $errors = [];
        foreach ($fields as $field) {
            if ($field->is_required) {
                $key = 'fields.' . $field->field_name;
                $val = $request->input('fields.' . $field->field_name);
                if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                    $errors[$field->field_name] = $field->label . ' is required.';
                }
            }
        }
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ], 422);
        }

        // Build custom_form_data
        $customFormData = [];
        foreach ($fields as $field) {
            $value = $request->input('fields.' . $field->field_name);
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
            'company_id'          => $leadProduct->lead?->company_id,
            'lead_id'             => $leadProduct->lead_id,
            'lead_product_id'     => $leadProduct->id,
            'product_id'          => $leadProduct->product_id,
            'department_id'       => $departmentId,
            'product_name'        => $leadProduct->product_name ?? $leadProduct->product?->package_name ?? '',
            'total_working_days'  => $request->input('total_working_days', 0),
            'requirements'        => $requirements, // Now always has a value
            'custom_form_data'    => $customFormData,
            'status'              => 'pending',
            'production_approval_status' => 'pending',
            'company_name'        => $lead?->company_name ?? '',
            'client_name'         => $lead?->contact_name ?? '',
            'initiated_by'        => auth()->id(),
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