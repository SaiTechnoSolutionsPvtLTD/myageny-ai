<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\RoleHierarchyMapping;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConvertInternToEmployeeRequest extends FormRequest
{
    private const TEAM_LEAD_ROLE_KEYS = ['tl', 'team_lead', 'team_leader', 'teamlead', 'manager'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'portal_email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'portal_password' => ['required', 'string', 'min:8', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'role_id' => ['required', 'exists:roles,id', $this->roleMatchesDepartmentRule()],
            'tl_user_id' => ['required', 'exists:users,id', $this->validTeamLeadRule()],
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
