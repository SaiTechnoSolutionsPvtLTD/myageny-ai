<?php

namespace App\Http\Requests;

use App\Models\VisitorEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'visitor_type' => ['required', 'string', Rule::in([
                VisitorEntry::TYPE_CANDIDATE,
                VisitorEntry::TYPE_CLIENT,
                VisitorEntry::TYPE_OTHERS,
            ])],
            'mobile_number' => ['required', 'string', 'max:30'],
            'email' => [
                'nullable',
                Rule::requiredIf($this->input('visitor_type') === VisitorEntry::TYPE_CANDIDATE),
                'email',
                'max:150',
            ],
            'applied_position' => [
                'nullable',
                Rule::requiredIf($this->input('visitor_type') === VisitorEntry::TYPE_CANDIDATE),
                'string',
                'max:150',
            ],
            'company_name' => [
                'nullable',
                Rule::requiredIf($this->input('visitor_type') === VisitorEntry::TYPE_CLIENT),
                'string',
                'max:150',
            ],
            'visit_date' => ['required', 'date'],
            'in_time' => ['required', 'date_format:H:i'],
            'out_time' => ['nullable', 'date_format:H:i', 'after_or_equal:in_time'],
            'person_to_meet' => ['required', 'string', 'max:150'],
            'remarks' => ['required', 'string', 'max:1000'],
        ];
    }
}
