<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OdRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date', 'after_or_equal:today'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.after_or_equal' => 'Past dates cannot be selected for OD requests.',
            'to_date.after_or_equal' => 'To Date must be equal to or after From Date.',
        ];
    }
}
