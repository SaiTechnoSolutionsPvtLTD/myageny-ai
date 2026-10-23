<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawFields = $this->input('custom_fields');

        if (is_string($rawFields)) {
            $decoded = json_decode($rawFields, true);
            $rawFields = is_array($decoded) ? $decoded : [];
        }

        if (is_array($rawFields)) {
            $normalized = [];
            foreach ($rawFields as $index => $field) {
                if (! is_array($field) || empty(trim($field['label'] ?? ''))) {
                    continue;
                }

                $label = trim($field['label']);
                $name = ! empty($field['name']) ? Str::slug($field['name'], '_') : Str::slug($label, '_');
                if (empty($name)) {
                    $name = 'field_' . ($index + 1);
                }

                $type = in_array($field['type'] ?? '', ['text', 'number', 'checkbox', 'select', 'file', 'date', 'radio'])
                    ? $field['type']
                    : 'text';

                $options = [];
                if (! empty($field['options'])) {
                    if (is_array($field['options'])) {
                        $options = array_values(array_filter(array_map('trim', $field['options'])));
                    } else {
                        $options = array_values(array_filter(array_map('trim', explode(',', (string) $field['options']))));
                    }
                }

                $normalized[] = [
                    'id' => $field['id'] ?? ('fld_' . ($index + 1) . '_' . substr(md5($label . $index), 0, 6)),
                    'label' => $label,
                    'name' => $name,
                    'type' => $type,
                    'required' => ! empty($field['required']),
                    'options' => $options,
                    'placeholder' => trim($field['placeholder'] ?? ''),
                ];
            }

            $this->merge(['custom_fields' => $normalized]);
        }
    }

    public function rules(): array
    {
        $assetCategoryId = $this->route('asset_category')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('asset_categories', 'name')
                    ->ignore($assetCategoryId)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.id' => ['nullable', 'string'],
            'custom_fields.*.label' => ['required', 'string', 'max:150'],
            'custom_fields.*.name' => ['required', 'string', 'max:150'],
            'custom_fields.*.type' => ['required', 'string', Rule::in(['text', 'number', 'checkbox', 'select', 'file', 'date', 'radio'])],
            'custom_fields.*.required' => ['nullable', 'boolean'],
            'custom_fields.*.options' => ['nullable', 'array'],
            'custom_fields.*.placeholder' => ['nullable', 'string', 'max:255'],
        ];
    }
}
