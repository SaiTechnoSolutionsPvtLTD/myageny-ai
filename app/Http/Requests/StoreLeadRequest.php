<?php
// ================================================================
// FILE: app/Http/Requests/Lead/StoreLeadRequest.php
// ================================================================

namespace App\Http\Requests;

use App\Models\Lead;
use App\Models\LeadFormField;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return array_merge([
            'company_name'  => ['required', 'string', 'max:150'],
            'contact_name'  => ['required', 'string', 'max:100'],
            'lead_date'     => ['required', 'date'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'email'         => ['nullable', 'email', 'max:150'],
            'lead_source_id' => ['required', 'integer', 'exists:lead_sources,id'],
            // 'lead_status_id' => ['nullable', 'integer', 'exists:lead_statuses,id'],
            // 'product_name'  => ['nullable', 'string', 'max:100'],
            'assigned_to'   => ['required', 'exists:users,id'],
            'pre_sale_executive_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority'      => ['required', 'in:low,medium,high'],
            'deal_value'    => ['nullable', 'numeric', 'min:0'],
            'remarks'       => ['nullable', 'string', 'max:2000'],
            'branch_id'     => ['required', 'exists:branches,id'],

        ], $this->customFieldRules());
    }

    public function messages(): array
    {
        return array_merge([
            'company_name.required'  => 'Company name is required.',
            'contact_name.required'  => 'Contact person name is required.',
            'lead_date.required'     => 'Lead date is required.',
            'mobile_number.required' => 'Mobile number is required.',
            'lead_source_id.required' => 'Please select a lead source.',
            'lead_source_id.exists'   => 'Selected lead source is invalid.',
            'lead_status_id.exists'   => 'Selected lead status is invalid.',
            'assigned_to.required'   => 'Please select an assigned user.',
            'branch_id.required'     => 'Please select a branch.',
            'priority.required'      => 'Please select a priority level.',
        ], $this->customFieldMessages());
    }

    protected function customFieldRules(): array
    {
        $rules = [];

        foreach ($this->activeCustomFields() as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            switch ($field->field_type) {
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'select':
                case 'radio':
                    $fieldRules[] = 'string';
                    $options = collect($field->options ?? [])
                        ->pluck('value')
                        ->filter(fn ($value) => $value !== null && $value !== '')
                        ->values()
                        ->all();
                    if (!empty($options)) {
                        $fieldRules[] = Rule::in($options);
                    }
                    break;
                case 'file':
                    $fieldRules = [$field->is_required ? 'required' : 'nullable', 'file', 'max:10240'];
                    break;
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;
            }

            $rules['custom_fields.' . $field->id] = $fieldRules;
        }

        return $rules;
    }

    protected function customFieldMessages(): array
    {
        $messages = [];

        foreach ($this->activeCustomFields() as $field) {
            $key = 'custom_fields.' . $field->id;
            $messages[$key . '.required'] = "{$field->label} is required.";
            $messages[$key . '.email'] = "Please enter a valid {$field->label}.";
            $messages[$key . '.numeric'] = "{$field->label} must be a number.";
            $messages[$key . '.date'] = "{$field->label} must be a valid date.";
            $messages[$key . '.in'] = "Please select a valid {$field->label}.";
        }

        return $messages;
    }

    protected function activeCustomFields()
    {
        $branchId = $this->input('branch_id');

        return LeadFormField::query()
            ->where('is_active', true)
            ->where('show_on_lead_create', true)
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($branchQuery) use ($branchId) {
                    $branchQuery->whereNull('branch_id')
                        ->orWhere('branch_id', $branchId);
                });
            })
            ->when(!$branchId, fn ($query) => $query->whereNull('branch_id'))
            ->get();
    }
}