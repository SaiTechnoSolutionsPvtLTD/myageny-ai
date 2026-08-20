<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadFormFieldRequest;
use App\Http\Requests\UpdateLeadFormFieldRequest;
use App\Http\Resources\LeadFormFieldResource;
use App\Models\LeadFormField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Lead Form Field Customization
 *
 * APIs for managing dynamic custom fields attached to the Lead form.
 */
class LeadFormFieldController extends Controller
{
    // ----------------------------------------------------------------
    //  GET /api/lead-form-fields
    // ----------------------------------------------------------------
    /**
     * List all custom fields.
     *
     * Supports filtering by:
     *   - field_type  (text|number|select|radio|textarea|date|email|phone)
     *   - is_active   (1|0)
     *   - branch_id
     */
    public function index(Request $request): JsonResponse
    {
        $query = LeadFormField::with('company');

        if ($request->filled('field_type')) {
            $query->where('field_type', $request->field_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool)$request->is_active);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('branch_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $request->branch_id);
            });
        }

        $fields = $query->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data'    => LeadFormFieldResource::collection($fields),
            'total'   => $fields->count(),
        ]);
    }

    // ----------------------------------------------------------------
    //  POST /api/lead-form-fields
    // ----------------------------------------------------------------
    /**
     * Create a new custom field.
     *
     * @bodyParam company_id          int     optional  Company ID.
     * @bodyParam label               string  required  Human-readable label. Example: "Budget Amount"
     * @bodyParam field_type          string  required  One of: text, number, select, radio, textarea, date, email, phone. Example: "number"
     * @bodyParam placeholder         string  optional  Placeholder text.
     * @bodyParam default_value       string  optional  Default value.
     * @bodyParam is_required         bool    optional  Default false.
     * @bodyParam is_active           bool    optional  Default true.
     * @bodyParam sort_order          int     optional  Display order.
     * @bodyParam options             array   required (for select/radio)  Array of {label, value}.
     * @bodyParam is_calculation      bool    optional  Mark as a calculated field.
     * @bodyParam calculation_formula string  required (if is_calculation=true)  Formula using field_names. Example: "cf_quantity * cf_unit_price"
     * @bodyParam calculation_label   string  optional  Description of the formula.
     * @bodyParam validation_rules    object  optional  e.g. {"min":0,"max":999999}
     * @bodyParam branch_id           int     optional  Scope to a specific branch.
     */
    public function store(StoreLeadFormFieldRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->user()?->company_id ?? session('company_id');
        }

        // Ensure options are null for non-option types
        if (!in_array($data['field_type'], LeadFormField::OPTION_TYPES)) {
            $data['options'] = null;
        }

        $field = LeadFormField::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Field created successfully.',
            'data'    => new LeadFormFieldResource($field->load('company')),
        ], 201);
    }

    // ----------------------------------------------------------------
    //  GET /api/lead-form-fields/{id}
    // ----------------------------------------------------------------
    /**
     * Get a single custom field.
     */
    public function show(LeadFormField $leadFormField): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new LeadFormFieldResource($leadFormField),
        ]);
    }

    // ----------------------------------------------------------------
    //  PUT /api/lead-form-fields/{id}
    // ----------------------------------------------------------------
    /**
     * Update an existing custom field.
     */
    public function update(UpdateLeadFormFieldRequest $request, LeadFormField $leadFormField): JsonResponse
    {
        $data = $request->validated();

        // Clear options if type was changed to a non-option type
        if (isset($data['field_type']) && !in_array($data['field_type'], LeadFormField::OPTION_TYPES)) {
            $data['options'] = null;
        }

        $leadFormField->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Field updated successfully.',
            'data'    => new LeadFormFieldResource($leadFormField->fresh()),
        ]);
    }

    // ----------------------------------------------------------------
    //  DELETE /api/lead-form-fields/{id}
    // ----------------------------------------------------------------
    /**
     * Soft-delete a custom field.
     */
    public function destroy(LeadFormField $leadFormField): JsonResponse
    {
        $leadFormField->delete();

        return response()->json([
            'success' => true,
            'message' => 'Field deleted successfully.',
        ]);
    }

    // ----------------------------------------------------------------
    //  PATCH /api/lead-form-fields/{id}/toggle
    // ----------------------------------------------------------------
    /**
     * Toggle active status of a field.
     */
    public function toggle(LeadFormField $leadFormField): JsonResponse
    {
        $leadFormField->update(['is_active' => !$leadFormField->is_active]);

        return response()->json([
            'success'   => true,
            'message'   => 'Field status toggled.',
            'is_active' => $leadFormField->is_active,
            'data'      => new LeadFormFieldResource($leadFormField),
        ]);
    }

    // ----------------------------------------------------------------
    //  POST /api/lead-form-fields/reorder
    // ----------------------------------------------------------------
    /**
     * Reorder fields via drag-and-drop.
     *
     * @bodyParam order  array  required  Array of {id, sort_order}.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'              => ['required', 'array'],
            'order.*.id'         => ['required', 'integer', 'exists:lead_form_fields,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->order as $item) {
                LeadFormField::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Fields reordered successfully.',
        ]);
    }

    // ----------------------------------------------------------------
    //  GET /api/lead-form-fields/schema
    // ----------------------------------------------------------------
    /**
     * Returns the active form schema (for mobile app form rendering).
     * Groups fields by type and includes options for select/radio fields.
     */
    public function schema(Request $request): JsonResponse
    {
        $query = LeadFormField::where('is_active', true)->orderBy('sort_order');

        if ($request->filled('branch_id')) {
            $query->where(function ($q) use ($request) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $request->branch_id);
            });
        }

        $fields = $query->get()->map(function (LeadFormField $f) {
            $schema = [
                'id'             => $f->id,
                'label'          => $f->label,
                'field_name'     => $f->field_name,
                'field_type'     => $f->field_type,
                'placeholder'    => $f->placeholder,
                'default_value'  => $f->default_value,
                'is_required'    => $f->is_required,
                'sort_order'     => $f->sort_order,
                'validation'     => $f->validation_rules ?? [],
            ];

            if ($f->hasOptions()) {
                $schema['options'] = $f->options ?? [];
            }

            if ($f->is_calculation) {
                $schema['is_calculation']      = true;
                $schema['calculation_formula'] = $f->calculation_formula;
                $schema['calculation_label']   = $f->calculation_label;
            }

            return $schema;
        });

        return response()->json([
            'success' => true,
            'schema'  => $fields,
        ]);
    }

    // ----------------------------------------------------------------
    //  POST /api/lead-form-fields/calculate
    // ----------------------------------------------------------------
    /**
     * Evaluate calculation fields given a map of current field values.
     * Useful for real-time calculation preview on the mobile / web form.
     *
     * @bodyParam values  object  required  Key-value map of field_name => numeric_value.
     *                            Example: {"cf_quantity": 5, "cf_unit_price": 200}
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'values'   => ['required', 'array'],
        ]);

        $calcFields = LeadFormField::where('is_calculation', true)
            ->where('is_active', true)
            ->get();

        $results = [];
        foreach ($calcFields as $field) {
            $result = $field->evaluate($request->values);
            $results[$field->field_name] = [
                'label'  => $field->label,
                'result' => $result,
            ];
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    // ----------------------------------------------------------------
    //  GET /api/lead-form-fields/options  (field type options list)
    // ----------------------------------------------------------------
    /**
     * Returns the available field types with labels — useful for a
     * "create field" dropdown in the admin UI.
     */
    public function fieldTypes(): JsonResponse
    {
        return response()->json([
            'success'     => true,
            'field_types' => [
                ['value' => 'text',     'label' => 'Text Input',       'icon' => 'type'],
                ['value' => 'number',   'label' => 'Number Input',     'icon' => 'hash'],
                ['value' => 'select',   'label' => 'Select Box',       'icon' => 'chevron-down'],
                ['value' => 'radio',    'label' => 'Radio Button',     'icon' => 'circle'],
                ['value' => 'textarea', 'label' => 'Text Area',        'icon' => 'align-left'],
                ['value' => 'date',     'label' => 'Date Picker',      'icon' => 'calendar'],
                ['value' => 'email',    'label' => 'Email',            'icon' => 'mail'],
                ['value' => 'phone',    'label' => 'Phone Number',     'icon' => 'phone'],
                ['value' => 'file',     'label' => 'File Upload',      'icon' => 'paperclip'],
            ],
        ]);
    }
}