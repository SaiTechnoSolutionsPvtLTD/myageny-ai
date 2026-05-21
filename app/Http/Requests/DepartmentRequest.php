<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('departments', 'name')
                    ->ignore($departmentId)
                    ->where(function ($query) {
                        $query->whereNull('deleted_at');

                        if (auth()->user()?->company_id !== null) {
                            $query->where('company_id', auth()->user()->company_id);
                        }
                    }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'dashboard_route' => ['nullable', 'string', Rule::in(array_keys(\App\Models\Department::dashboardRouteOptions()))],
        ];
    }
}
