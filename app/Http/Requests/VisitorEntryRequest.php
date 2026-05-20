<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitorEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_name' => ['required', 'string', 'max:150'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'visit_date' => ['required', 'date'],
            'in_time' => ['required', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i', 'after_or_equal:in_time'],
            'person_to_meet' => ['required', 'string', 'max:150'],
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }
}
