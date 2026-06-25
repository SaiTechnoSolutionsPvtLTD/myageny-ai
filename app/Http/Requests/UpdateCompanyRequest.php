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
        ];
    }
}
