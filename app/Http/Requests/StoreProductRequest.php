<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // replace with gate/policy check
    }

    public function rules(): array
    {
        return [
            'product_category_id'   => ['required', 'exists:product_categories,id'],
            'package_name'          => ['required', 'string', 'max:255'],
            'base_price'            => ['required', 'numeric', 'min:0'],
            'tax_type'              => ['required', 'in:percentage,fixed'],
            'tax_value'             => ['required', 'numeric', 'min:0'],
            'discount_type'         => ['required', 'in:percentage,fixed'],
            'discount_value'        => ['required', 'numeric', 'min:0'],
            'description'           => ['nullable', 'string'],
            'status'                => ['required', 'in:active,inactive,draft'],
            'sort_order'            => ['nullable', 'integer', 'min:0'],
            'assigned_to'           => ['nullable', 'exists:users,id'],
            'count_wise_report'     => ['boolean'],
            'is_this_renewal_product'=> ['boolean'],
            'is_budget_approval_needed'=> ['boolean'],
            'department_ids'        => ['nullable', 'array'],
            'department_ids.*'      => ['integer', 'exists:departments,id'],

            // Dynamic attributes
            'attributes'            => ['nullable', 'array'],
            'attributes.*.attribute_id' => ['required_with:attributes', 'exists:attributes,id'],
            'attributes.*.value'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_category_id.required' => 'Please select a category.',
            'product_category_id.exists'   => 'Selected category is invalid.',
            'base_price.required'          => 'Base price is required.',
            'base_price.numeric'           => 'Base price must be a number.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_value'      => $this->tax_value ?? 0,
            'discount_value' => $this->discount_value ?? 0,
            'sort_order'     => $this->sort_order ?? 0,
            'count_wise_report' => $this->boolean('count_wise_report'),
            'is_this_renewal_product' => $this->boolean('is_this_renewal_product'),
            'is_budget_approval_needed' => $this->boolean('is_budget_approval_needed'),
        ]);
    }
}
