<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_price_request'     => $this->boolean('show_price_request'),
            'show_production_update' => $this->boolean('show_production_update'),
            'show_approval_history'  => $this->boolean('show_approval_history'),
            'show_cst_updates'       => $this->boolean('show_cst_updates'),
        ]);
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'company_name'           => ['required', 'string', 'max:150'],
            'email'                  => ['required', 'email', 'max:150', Rule::unique('companies', 'email')->ignore($companyId)],
            'mobile_number'          => ['required', 'string', 'max:20'],
            'address'                => ['required', 'string', 'max:1000'],
            'number_of_accounts'     => ['required', 'integer', 'min:1'],
            'expiry_date'            => ['required', 'date'],
            'company_status'         => ['required', Rule::in(['active', 'inactive'])],
            'facebook_client_id'     => ['required', 'string', 'max:255'],
            'facebook_client_secret' => ['required', 'string', 'max:255'],
            'show_price_request'     => ['nullable', 'boolean'],
            'show_production_update' => ['nullable', 'boolean'],
            'show_approval_history'  => ['nullable', 'boolean'],
            'show_cst_updates'       => ['nullable', 'boolean'],
        ];
    }
}
