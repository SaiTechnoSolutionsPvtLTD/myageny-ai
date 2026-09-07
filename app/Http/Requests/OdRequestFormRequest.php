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
            'gate_out_time' => ['required', 'date_format:H:i'],
            'gate_in_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.after_or_equal' => 'Past dates cannot be selected for OD requests.',
            'to_date.after_or_equal' => 'To Date must be equal to or after From Date.',
            'gate_out_time.required' => 'Gateout time is required.',
            'gate_out_time.date_format' => 'Gateout time must be a valid time format (HH:MM).',
            'gate_in_time.required' => 'Gatein time is required.',
            'gate_in_time.date_format' => 'Gatein time must be a valid time format (HH:MM).',
        ];
    }
}
