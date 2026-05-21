<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
      return true;
    }

    public function rules(): array
    {
        $companyId = auth()->user()?->company_id;

        return [
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
            'branch_id'             => [
                'nullable',
                'exists:branches,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($companyId) {
                    if (! $value || $companyId === null) {
                        return;
                    }

                    $branch = Branch::withoutGlobalScopes()->find($value);

                    if ($branch && (int) $branch->company_id !== (int) $companyId) {
                        $fail('Selected branch does not belong to your company.');
                    }
                },
            ],
            'role'                  => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) use ($companyId) {
                    $role = Role::withoutGlobalScopes()->where('name', $value)->first();

                    if (! $role) {
                        $fail('Selected role does not exist.');
                        return;
                    }

                    if ($companyId !== null && (int) $role->company_id !== (int) $companyId) {
                        $fail('Selected role does not belong to your company.');
                    }
                },
            ],
            'is_active'             => ['boolean'],
            'avatar'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Full name is required.',
            'email.required'     => 'Email address is required.',
            'email.unique'       => 'This email is already registered.',
            'password.required'  => 'Password is required.',
            'password.min'       => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required'      => 'Please assign a role to this user.',
            'role.exists'        => 'Selected role does not exist.',
            'avatar.image'       => 'Avatar must be an image file.',
            'avatar.max'         => 'Avatar must not exceed 2MB.',
        ];
    }
}
