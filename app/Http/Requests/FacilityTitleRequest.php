<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $facilityTitleId = $this->route('facility_title')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('facility_titles', 'name')
                    ->ignore($facilityTitleId)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
