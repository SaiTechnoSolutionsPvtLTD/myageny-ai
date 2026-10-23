<?php

namespace App\Http\Requests;

use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'asset_name' => ['nullable', 'string', 'max:150'],
            'asset_category' => [
                'required',
                'string',
                'max:100',
                Rule::exists('asset_categories', 'name')->whereNull('deleted_at'),
            ],
            'brand' => ['nullable', 'string', 'max:100'],
            'model_name' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:150'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'warranty_expiry_date' => ['nullable', 'date'],
            'asset_status' => ['nullable', Rule::in(['available', 'assigned', 'in_service', 'damaged', 'retired'])],
            'assigned_employee_id' => ['nullable', 'exists:employee_onboardings,id'],
            'assigned_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:150'],
            'condition_notes' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:3000'],
            'custom_fields' => ['nullable', 'array'],
            'custom_files' => ['nullable', 'array'],
            'custom_files.*' => ['nullable', 'file', 'max:20480'],
        ];

        if ($this->filled('asset_category')) {
            $category = AssetCategory::where('name', $this->asset_category)->first();
            $customFields = $category?->custom_fields;
            if (is_array($customFields)) {
                foreach ($customFields as $cf) {
                    if (! empty($cf['required']) && ! empty($cf['name'])) {
                        $fName = $cf['name'];
                        if (($cf['type'] ?? '') === 'file') {
                            $rules["custom_files.{$fName}"] = ['required', 'file', 'max:20480'];
                        } else {
                            $rules["custom_fields.{$fName}"] = ['required'];
                        }
                    }
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'asset_category.required' => 'Please select an Asset Category.',
            'custom_fields.*.required' => 'This category field is required.',
            'custom_files.*.required' => 'Please upload the required file.',
        ];
    }
}
