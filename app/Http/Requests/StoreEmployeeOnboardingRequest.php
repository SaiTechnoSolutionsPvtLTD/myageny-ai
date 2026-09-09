<?php

namespace App\Http\Requests;

use App\Models\EmployeeOnboarding;
use App\Models\Role;
use App\Models\RoleHierarchyMapping;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEmployeeOnboardingRequest extends FormRequest
{
    private const TEAM_LEAD_ROLE_KEYS = ['tl', 'team_lead', 'team_leader', 'teamlead', 'manager'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('portal_email') && $this->filled('email')) {
            $this->merge(['portal_email' => $this->input('email')]);
        }
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    private function baseRules(): array
    {
        $fileRules = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        return [
            'role_id' => ['required', 'exists:roles,id', $this->roleMatchesDepartmentRule()],
            'department_id' => ['required', 'exists:departments,id'],
            'portal_email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'portal_password' => ['required', 'string', 'min:8', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'tl_user_id' => ['required', 'exists:users,id', $this->validTeamLeadRule()],
            'name' => ['required', 'string', 'max:150'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'correspondence_address' => ['nullable', 'string', 'max:2000'],
            'permanent_address' => ['nullable', 'string', 'max:2000'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150'],
            'date_of_birth' => ['required', 'date'],
            'joining_date' => ['nullable', 'date'],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'marital_status' => ['required', Rule::in(['single', 'married'])],
            'date_of_marriage' => ['nullable', 'date', 'required_if:marital_status,married'],
            'aadhaar_card_no' => ['nullable', 'regex:/^\d{12}$/'],
            'pan_card_no' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
            'photograph' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],

            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_relation' => ['nullable', 'string', 'max:100'],
            'emergency_contact_no' => ['nullable', 'string', 'max:20'],

            'educations' => ['nullable', 'array'],
            'educations.*.qualification' => ['nullable', 'string', 'max:150'],
            'educations.*.institution_name' => ['nullable', 'string', 'max:255'],
            'educations.*.year_of_passing' => ['nullable', 'string', 'max:20'],
            'educations.*.percentage' => ['nullable', 'string', 'max:20'],
            'educations.*.specialization' => ['nullable', 'string', 'max:255'],

            'employments' => ['nullable', 'array'],
            'employments.*.organisation' => ['nullable', 'string', 'max:255'],
            'employments.*.designation' => ['nullable', 'string', 'max:150'],
            'employments.*.period_from' => ['nullable', 'date'],
            'employments.*.period_to' => ['nullable', 'date'],
            'employments.*.annual_ctc' => ['nullable', 'string', 'max:50'],

            'family_details' => ['nullable', 'array'],
            'family_details.*.name' => ['nullable', 'string', 'max:150'],
            'family_details.*.relation' => ['nullable', 'string', 'max:100'],
            'family_details.*.occupation' => ['nullable', 'string', 'max:150'],
            'family_details.*.date_of_birth' => ['nullable', 'date'],
            'family_details.*.mobile_no' => ['nullable', 'string', 'max:20'],

            'reference_name' => ['nullable', 'string', 'max:150'],
            'reference_organization_name' => ['nullable', 'string', 'max:150'],
            'reference_designation' => ['nullable', 'string', 'max:150'],
            'reference_contact_no' => ['nullable', 'string', 'max:20'],
            'reference_mail_id' => ['nullable', 'email', 'max:150'],

            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_ifsc_code' => ['nullable', 'string', 'max:30'],
            'bank_branch' => ['nullable', 'string', 'max:150'],
            'salary_effective_from' => ['nullable', 'date'],
            'gross_salary' => ['nullable', 'numeric', 'min:0'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'hra' => ['nullable', 'numeric', 'min:0'],
            'special_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowance' => ['nullable', 'numeric', 'min:0'],
            'esi_enabled' => ['nullable', 'boolean'],
            'esi_no' => ['nullable', 'string', 'max:50'],
            'esi_employee_contribution' => ['nullable', 'numeric', 'min:0'],
            'esi_employer_contribution' => ['nullable', 'numeric', 'min:0'],
            'pf_enabled' => ['nullable', 'boolean'],
            'uan_no' => ['nullable', 'string', 'max:50'],
            'pf_account_no' => ['nullable', 'string', 'max:50'],
            'pf_employee_contribution' => ['nullable', 'numeric', 'min:0'],
            'pf_employer_contribution' => ['nullable', 'numeric', 'min:0'],
            'professional_tax' => ['nullable', 'numeric', 'min:0'],
            'tds_amount' => ['nullable', 'numeric', 'min:0'],
            'loan_deduction' => ['nullable', 'numeric', 'min:0'],
            'other_deduction' => ['nullable', 'numeric', 'min:0'],
            'total_deduction' => ['nullable', 'numeric', 'min:0'],
            'net_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_payment_mode' => ['nullable', Rule::in(['bank_transfer', 'cash', 'cheque', 'upi'])],
            'deduction_notes' => ['nullable', 'string', 'max:2000'],

            'declaration_date' => ['nullable', 'date'],
            'declaration_place' => ['nullable', 'string', 'max:150'],
            'signature' => $fileRules,

            'document_10th_marksheet' => $fileRules,
            'document_12th_marksheet' => $fileRules,
            'document_consolidated_marksheet' => $fileRules,
            'document_course_completion_certificate' => $fileRules,
            'document_degree_certificate' => $fileRules,
            'document_provisional_certificate' => $fileRules,
            'document_tc' => $fileRules,
            'document_aadhaar_card' => $fileRules,
            'document_pan_card' => $fileRules,
            'document_voter_id' => $fileRules,
            'document_driving_licence' => $fileRules,
            'document_experience_certificate' => $fileRules,
            'document_salary_slips' => $fileRules,
            'document_bank_passbook' => $fileRules,

            'status' => ['required', Rule::in([EmployeeOnboarding::STATUS_ACTIVE, EmployeeOnboarding::STATUS_INACTIVE, EmployeeOnboarding::STATUS_RESIGNED])],
            'employee_type' => ['nullable', Rule::in(['billable', 'non_billable'])],
        ];
    }

    private function roleMatchesDepartmentRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $role = Role::withoutGlobalScopes()->find($value);

            if (! $role) {
                return;
            }

            $companyId = auth()->user()?->company_id;
            if ($companyId !== null && (int) $role->company_id !== (int) $companyId) {
                $fail('Selected role does not belong to your company.');
                return;
            }

            $departmentId = $this->input('department_id');
            if ($departmentId && $role->department_id && (int) $role->department_id !== (int) $departmentId) {
                $fail('Selected role does not belong to the selected department.');
            }
        };
    }

    private function validTeamLeadRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $teamLead = User::withoutGlobalScopes()
                ->with('roles.roleMapping')
                ->find($value);

            if (! $teamLead) {
                return;
            }

            if (! $teamLead->is_active) {
                $fail('Selected TL account is inactive.');
                return;
            }

            $companyId = auth()->user()?->company_id;
            if ($companyId !== null && (int) $teamLead->company_id !== (int) $companyId) {
                $fail('Selected TL does not belong to your company.');
                return;
            }

            $branchId = $this->input('branch_id');
            if ($branchId && $teamLead->branch_id && (int) $teamLead->branch_id !== (int) $branchId) {
                $fail('Selected TL does not belong to the selected branch.');
                return;
            }

            if (! $this->userCanManageSelectedRole($teamLead)) {
                $fail('Selected TL does not match the mapped parent role for the selected role.');
            }
        };
    }

    private function userCanManageSelectedRole(User $user): bool
    {
        $roleId = $this->input('role_id');

        if ($roleId) {
            $mapping = RoleHierarchyMapping::with('parentRole')
                ->where('child_role_id', $roleId)
                ->first();

            if ($mapping) {
                if ($this->userHasMappedParentRole($user, (int) $mapping->parent_role_id, $mapping->parentRole)) {
                    return true;
                }
            }
        }

        if ($this->userHasTeamLeadRoleForDepartment($user, $this->input('department_id'))) {
            return true;
        }

        return $this->isBranchAdminOrManagerUser($user);
    }

    private function isBranchAdminOrManagerUser(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin()) {
            return true;
        }

        $adminOrManagerKeys = [
            'branch_admin',
            'branch_manager',
            'manager',
            'admin',
            'company_admin',
            'super_admin',
            'coo',
            'cbo',
            'chief_operating_officer',
            'cheif_operating_officer',
            'chief_business_officer',
        ];

        return $user->roles->contains(function (Role $role) use ($adminOrManagerKeys) {
            $roleKeys = collect([$role->name, $role->display_name])
                ->filter()
                ->flatMap(function (string $roleName) {
                    $normalized = $this->normalizeRoleKey($roleName);

                    return [$normalized, Str::afterLast($normalized, '__')];
                })
                ->unique();

            return $roleKeys->intersect($adminOrManagerKeys)->isNotEmpty();
        });
    }

    private function userHasMappedParentRole(User $user, int $parentRoleId, ?Role $parentRole): bool
    {
        if ($user->roles->contains('id', $parentRoleId)) {
            return true;
        }

        if (! $parentRole) {
            return false;
        }

        $parentRoleKeys = collect([$parentRole->name, $parentRole->display_name])
            ->filter()
            ->flatMap(function (string $roleName) {
                $normalized = $this->normalizeRoleKey($roleName);

                return [$normalized, Str::afterLast($normalized, '__'), Str::afterLast($roleName, '__')];
            })
            ->map(fn (string $roleName) => $this->normalizeRoleKey($roleName))
            ->unique();

        $companySuperAdminId = auth()->user()?->company_id
            ? optional(\App\Models\Company::find(auth()->user()->company_id))->super_admin_user_id
            : null;

        if (($user->isSuperAdmin() || (int) $user->id === (int) $companySuperAdminId) && $parentRoleKeys->intersect(['super_admin', 'admin'])->isNotEmpty()) {
            return true;
        }

        if ($user->isCompanyAdmin() && $parentRoleKeys->contains('company_admin')) {
            return true;
        }

        return false;
    }

    private function userHasTeamLeadRoleForDepartment(User $user, mixed $departmentId): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles->contains(function (Role $role) use ($departmentId) {
            $departmentMatches = ! $departmentId
                || ! $role->department_id
                || (int) $role->department_id === (int) $departmentId;

            return $departmentMatches && $this->roleLooksLikeTeamLead($role);
        });
    }

    private function roleLooksLikeTeamLead(Role $role): bool
    {
        if ($role->relationLoaded('roleMapping') && $role->roleMapping?->access_level === 'team') {
            return true;
        }

        $roleKeys = collect([$role->name, $role->display_name])
            ->filter()
            ->flatMap(function (string $roleName) {
                $normalized = $this->normalizeRoleKey($roleName);

                return [$normalized, Str::afterLast($normalized, '__')];
            })
            ->unique();

        return $roleKeys->intersect(self::TEAM_LEAD_ROLE_KEYS)->isNotEmpty();
    }

    private function normalizeRoleKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();
    }
}
