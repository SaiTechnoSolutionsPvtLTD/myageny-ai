<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_title_id' => [
                'required',
                'integer',
                Rule::exists('facility_titles', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
