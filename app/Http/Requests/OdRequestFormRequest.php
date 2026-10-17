<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class OdRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('gate_out_time') && filled($this->gate_out_time)) {
            try {
                $this->merge([
                    'gate_out_time' => Carbon::parse($this->gate_out_time)->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                // Keep raw input to allow date_format rule to handle validation failure
            }
        }

        if ($this->has('gate_in_time') && filled($this->gate_in_time)) {
            try {
                $this->merge([
                    'gate_in_time' => Carbon::parse($this->gate_in_time)->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                // Keep raw input to allow date_format rule to handle validation failure
            }
        }
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date', 'after_or_equal:today'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'gate_out_time' => ['nullable', 'date_format:H:i'],
            'gate_in_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required_without:gate_in_time', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.after_or_equal' => 'Past dates cannot be selected for OD requests.',
            'to_date.after_or_equal' => 'To Date must be equal to or after From Date.',
            'gate_out_time.date_format' => 'Gateout time must be a valid time format (HH:MM).',
            'gate_in_time.date_format' => 'Gatein time must be a valid time format (HH:MM).',
            'reason.required_without' => 'Remarks are required when Gate-in time is not provided.',
        ];
    }
}
