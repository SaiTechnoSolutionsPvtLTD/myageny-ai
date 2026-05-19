<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_name' => ['required', 'string', 'max:150'],
            'asset_category' => [
                'nullable',
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
            'asset_status' => ['required', Rule::in(['available', 'assigned', 'in_service', 'damaged', 'retired'])],
            'assigned_employee_id' => ['nullable', 'exists:employee_onboardings,id'],
            'assigned_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:150'],
            'condition_notes' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
