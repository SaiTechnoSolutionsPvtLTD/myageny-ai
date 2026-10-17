<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class PermissionRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('from_time') && filled($this->from_time)) {
            try {
                $this->merge([
                    'from_time' => Carbon::parse($this->from_time)->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                // Keep raw input to allow date_format rule to handle validation failure
            }
        }

        if ($this->has('to_time') && filled($this->to_time)) {
            try {
                $this->merge([
                    'to_time' => Carbon::parse($this->to_time)->format('H:i'),
                ]);
            } catch (\Throwable $e) {
                // Keep raw input to allow date_format rule to handle validation failure
            }
        }
    }

    public function rules(): array
    {
        return [
            'permission_date' => ['required', 'date'],
            'from_time' => ['required', 'date_format:H:i'],
            'to_time' => ['required', 'date_format:H:i', 'after:from_time'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_time.required' => 'From time is required.',
            'from_time.date_format' => 'Please enter a valid from time.',
            'to_time.required' => 'To time is required.',
            'to_time.date_format' => 'Please enter a valid to time.',
            'to_time.after' => 'To time must be later than from time.',
        ];
    }
}
