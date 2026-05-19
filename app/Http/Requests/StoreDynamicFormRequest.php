<?php

namespace App\Http\Requests;

use App\Models\DynamicFormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDynamicFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    protected function prepareForValidation(): void
    {
        $fields = collect($this->input('fields', []))
            ->map(function ($field) {
                $field = is_array($field) ? $field : [];
                $options = $field['options_text'] ?? '';
                $field['options_text'] = is_string($options) ? trim($options) : '';

                return $field;
            })
            ->all();

        $this->merge(['fields' => $fields]);
    }

    protected function baseRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'success_message' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'allow_multiple_submissions' => ['nullable', 'boolean'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.id' => ['nullable', 'integer'],
            'fields.*.label' => ['required', 'string', 'max:150'],
            'fields.*.field_type' => ['required', Rule::in(DynamicFormField::FIELD_TYPES)],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.help_text' => ['nullable', 'string', 'max:255'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.options_text' => ['nullable', 'string'],
        ];
    }
}
