<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductOvpFormFieldRequest;
use App\Http\Requests\UpdateProductOvpFormFieldRequest;
use App\Models\Product;
use App\Models\ProductOvpFormField;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductOvpFormController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    public function builder(Product $product): View
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);

        $product->load('ovpFormFields');

        return view('pages.products.ovp-form', compact('product'));
    }

    public function index(Product $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);

        $fields = $product->ovpFormFields()->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $fields,
        ]);
    }

    public function store(StoreProductOvpFormFieldRequest $request, Product $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);

        $data = $request->validated();
        if (! in_array($data['field_type'], ProductOvpFormField::OPTION_TYPES, true)) {
            $data['options'] = null;
        }

        $field = $product->ovpFormFields()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'OVP field created successfully.',
            'data' => $field,
        ], 201);
    }

    public function update(UpdateProductOvpFormFieldRequest $request, Product $product, ProductOvpFormField $field): JsonResponse
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);
        abort_unless((int) $field->product_id === (int) $product->id, 404);

        $data = $request->validated();

        if (array_key_exists('label', $data)) {
            $data['field_name'] = ProductOvpFormField::makeFieldName($data['label']);
        }

        $fieldType = $data['field_type'] ?? $field->field_type;
        if (! in_array($fieldType, ProductOvpFormField::OPTION_TYPES, true)) {
            $data['options'] = null;
        }

        $field->update($data);

        return response()->json([
            'success' => true,
            'message' => 'OVP field updated successfully.',
            'data' => $field->fresh(),
        ]);
    }

    public function destroy(Product $product, ProductOvpFormField $field): JsonResponse
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);
        abort_unless((int) $field->product_id === (int) $product->id, 404);

        $field->delete();

        return response()->json([
            'success' => true,
            'message' => 'OVP field deleted successfully.',
        ]);
    }

    public function toggle(Product $product, ProductOvpFormField $field): JsonResponse
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);
        abort_unless((int) $field->product_id === (int) $product->id, 404);

        $field->update(['is_active' => ! $field->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $field->is_active,
            'data' => $field->fresh(),
        ]);
    }

    public function reorder(Request $request, Product $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer', 'exists:product_ovp_form_fields,id'],
            'order.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $product) {
            foreach ($request->input('order', []) as $item) {
                $product->ovpFormFields()
                    ->whereKey($item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'OVP field order updated.',
        ]);
    }

    public function schema(Product $product): JsonResponse
    {
        abort_unless($this->visibility->canAccessProduct($product), 403);

        $fields = $product->ovpFormFields()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ProductOvpFormField $field) => [
                'id' => $field->id,
                'label' => $field->label,
                'field_name' => $field->field_name,
                'field_type' => $field->field_type,
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'default_value' => $field->default_value,
                'is_required' => $field->is_required,
                'use_in_ovp' => $field->use_in_ovp,
                'use_in_production_initiation' => $field->use_in_production_initiation,
                'sort_order' => $field->sort_order,
                'validation_rules' => $field->validation_rules ?? [],
                'options' => $field->options ?? [],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'schema' => $fields,
        ]);
    }
}
