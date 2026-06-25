<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;
        $companyId = auth()->user()?->company_id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('branches', 'name')
                    ->ignore($branchId)
                    ->where(function ($query) use ($companyId) {
                        $query->whereNull('deleted_at');

                        if ($companyId !== null) {
                            $query->where('company_id', $companyId);
                        }
                    }),
            ],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('branches', 'code')
                    ->ignore($branchId)
                    ->where(function ($query) use ($companyId) {
                        $query->whereNull('deleted_at');

                        if ($companyId !== null) {
                            $query->where('company_id', $companyId);
                        }
                    }),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
