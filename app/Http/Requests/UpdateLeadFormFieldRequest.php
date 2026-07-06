<?php

namespace App\Http\Requests;

use App\Models\LeadFormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadFormFieldRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'                => ['sometimes', 'required', 'string', 'max:255'],
            'field_type'           => ['sometimes', 'required', Rule::in(['text','number','select','radio','textarea','date','email','phone'])],
            'placeholder'          => ['nullable', 'string', 'max:255'],
            'default_value'        => ['nullable', 'string'],
            'is_required'          => ['boolean'],
            'is_active'            => ['boolean'],
            'show_on_lead_create'  => ['boolean'],
            'sort_order'           => ['integer', 'min:0'],
            'branch_id'            => ['nullable', 'integer'],

            'options'              => ['nullable', 'array'],
            'options.*.label'      => ['required_with:options', 'string', 'max:255'],
            'options.*.value'      => ['required_with:options', 'string', 'max:255'],

            'is_calculation'       => ['boolean'],
            'calculation_formula'  => ['nullable', 'string', 'max:1000'],
            'calculation_label'    => ['nullable', 'string', 'max:255'],

            'validation_rules'     => ['nullable', 'array'],
            'validation_rules.min' => ['nullable', 'numeric'],
            'validation_rules.max' => ['nullable', 'numeric'],
        ];
    }
}