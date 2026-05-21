<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveTypeId = $this->route('leave_type')?->id;
        $companyId = auth()->user()?->company_id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('leave_types', 'name')
                    ->ignore($leaveTypeId)
                    ->where(function ($query) use ($companyId) {
                        $query->whereNull('deleted_at')
                            ->where(function ($companyQuery) use ($companyId) {
                                if ($companyId === null) {
                                    $companyQuery->whereNull('company_id');

                                    return;
                                }

                                $companyQuery->whereNull('company_id')
                                    ->orWhere('company_id', $companyId);
                            });
                    }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
