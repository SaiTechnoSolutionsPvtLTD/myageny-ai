<?php

namespace App\Http\Requests;

use App\Models\LeadFormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadFormFieldRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'                => ['required', 'string', 'max:255'],
            'field_name'           => ['required', 'string', 'max:255', Rule::unique('lead_form_fields', 'field_name')],
            'field_type'           => ['required', Rule::in(['text','number','select','radio','textarea','date','email','phone'])],
            'placeholder'          => ['nullable', 'string', 'max:255'],
            'default_value'        => ['nullable', 'string'],
            'is_required'          => ['boolean'],
            'is_active'            => ['boolean'],
            'show_on_lead_create'  => ['boolean'],
            'sort_order'           => ['integer', 'min:0'],
            'branch_id'            => ['nullable', 'integer'],

            // Options: required when field_type is select or radio
            'options'              => [
                Rule::requiredIf(fn() => in_array($this->field_type, LeadFormField::OPTION_TYPES)),
                'nullable', 'array', 'min:1',
            ],
            'options.*.label'      => ['required_with:options', 'string', 'max:255'],
            'options.*.value'      => ['required_with:options', 'string', 'max:255'],

            // Calculation
            'is_calculation'       => ['boolean'],
            'calculation_formula'  => ['required_if:is_calculation,true', 'nullable', 'string', 'max:1000'],
            'calculation_label'    => ['nullable', 'string', 'max:255'],

            // Validation rules (min / max for numbers, etc.)
            'validation_rules'     => ['nullable', 'array'],
            'validation_rules.min' => ['nullable', 'numeric'],
            'validation_rules.max' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'options.required'             => 'Options are required for select and radio field types.',
            'calculation_formula.required_if' => 'A formula is required when the field is marked as a calculation field.',
        ];
    }

    /**
     * Auto-generate field_name from label if not provided.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'field_name' => LeadFormField::makeFieldName($this->label ?? ''),
        ]);
    }
}
