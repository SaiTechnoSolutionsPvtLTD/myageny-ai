<?php

namespace App\Http\Requests\HRMS;

use App\Models\CourierFormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CourierFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.id' => ['nullable', 'integer'],
            'fields.*.label' => ['required', 'string', 'max:150'],
            'fields.*.field_type' => ['required', Rule::in(CourierFormField::fieldTypeKeys())],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.help_text' => ['nullable', 'string', 'max:255'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.show_in_list' => ['nullable', 'boolean'],
            'fields.*.options_text' => ['nullable', 'string'],
        ];
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
            ->values()
            ->all();

        $this->merge(['fields' => $fields]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('fields', []) as $index => $field) {
                $type = $field['field_type'] ?? '';
                if (! in_array($type, ['select', 'radio'], true)) {
                    continue;
                }

                $options = self::parseOptions($field['options_text'] ?? '');
                if (count($options) === 0) {
                    $validator->errors()->add(
                        "fields.{$index}.options_text",
                        'Please enter at least one option for dropdown or radio fields.'
                    );
                }
            }
        });
    }

    public static function parseOptions(?string $optionsText): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $optionsText))
            ->map(fn ($item) => trim($item))
            ->filter(fn ($item) => $item !== '')
            ->values()
            ->all();
    }
}
