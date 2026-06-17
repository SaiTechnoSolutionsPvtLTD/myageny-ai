<?php

namespace App\Http\Requests;

use App\Models\ProductOvpFormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductOvpFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        $isOptionType = in_array($this->input('field_type'), ProductOvpFormField::OPTION_TYPES, true);

        return [
            'label' => ['required', 'string', 'max:255'],
            'field_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_ovp_form_fields', 'field_name')->where(fn ($query) => $query->where('product_id', $productId)),
            ],
            'field_type' => ['required', Rule::in(ProductOvpFormField::FIELD_TYPES)],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'default_value' => ['nullable', 'string'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'use_in_ovp' => ['boolean'],
            'use_in_production_initiation' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => $isOptionType
                ? ['required', 'array', 'min:1']
                : ['nullable'],
            'options.*.label' => ['required_with:options', 'string', 'max:255'],
            'options.*.value' => ['required_with:options', 'string', 'max:255'],
            'validation_rules' => ['nullable', 'array'],
            'validation_rules.min' => ['nullable', 'numeric'],
            'validation_rules.max' => ['nullable', 'numeric'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'field_name' => ProductOvpFormField::makeFieldName((string) $this->input('label', '')),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
