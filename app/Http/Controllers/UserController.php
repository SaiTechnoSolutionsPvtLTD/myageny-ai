<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\EmployeeOnboarding;
use App\Models\InternJoiningForm;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    /**
     * List all users with filters.
     */
    public function index(Request $request)
    {
        // $this->authorize('users.view');

        $company = $this->currentCompany();

        $query = User::with(['branch', 'branches', 'roles'])
            ->when($company, fn($q) => $q->where('company_id', $company->id))
            ->when($request->search, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('name', 'like', '%'.$request->search.'%')
                       ->orWhere('email', 'like', '%'.$request->search.'%')
                       ->orWhere('designation', 'like', '%'.$request->search.'%')
                )
            )
            ->when($request->branch_id, fn($q) =>
                $q->inBranches([(int) $request->branch_id])
            )
            ->when($request->role, fn($q) =>
                $q->whereHas('roles', fn($q2) =>
                    $q2->where('name', $request->role)
                )
            )
            ->when($request->status !== null && $request->status !== '', fn($q) =>
                $q->where('is_active', $request->status)
            )
            ->orderByDesc('is_active')
            ->when($request->activity, function ($q) use ($request) {
                if ($request->activity === 'last_seen') {
                    $q->whereNotNull('last_login_at')
                      ->orderByDesc('last_login_at');
                } elseif ($request->activity === 'recent_login') {
                    $q->whereNotNull('last_login_at')
                      ->where('last_login_at', '>=', now()->subHours(24))
                      ->orderByDesc('last_login_at');
                } elseif ($request->activity === 'inactive_3days') {
                    $q->where(function ($q2) {
                        $q2->whereNull('last_login_at')
                           ->orWhere('last_login_at', '<=', now()->subDays(3));
                    })->orderByRaw('last_login_at IS NULL DESC, last_login_at ASC');
                }
            })
            ->when(!$request->activity, fn($q) => $q->latest())
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::active()->orderByDesc('is_default')->orderBy('name')->get();
        $roles    = Role::withoutGlobalScope('company')
            ->when($company, fn($q) => $q->where('company_id', $company->id))
            ->orderBy('name')
            ->get();

        $userLimit = $company ? (int) $company->number_of_accounts : null;
        $activeUsersCount = $company ? $company->activeUsers()->count() : null;
        $totalCompanyUsers = $company ? $company->users()->count() : null;
        $hasReachedLimit = $company && $userLimit !== null ? ($activeUsersCount >= $userLimit) : false;
        $remainingAccounts = ($company && $userLimit !== null) ? max(0, $userLimit - $activeUsersCount) : null;

        return view('pages.users.index', compact(
            'query',
            'branches',
            'roles',
            'company',
            'userLimit',
            'activeUsersCount',
            'totalCompanyUsers',
            'hasReachedLimit',
            'remainingAccounts'
        ));
    }

    /**
     * Show create user form.
     */
    public function create()
    {
        // $this->authorize('users.manage');

        $company = $this->currentCompany();

        if ($company && $company->activeUsers()->count() >= $company->user_limit) {
            return redirect()
                ->route('users.index')
                ->with('error', "User limit reached for this company ({$company->activeUsers()->count()} of {$company->user_limit} accounts used, including Company Admin). Please contact admin to increase the limit.");
        }

        Branch::ensureDefaultForCurrentCompany();
        $branches = Branch::active()->orderByDesc('is_default')->orderBy('name')->get();
        $roles    = Role::withoutGlobalScope('company')
            ->where('company_id', auth()->user()?->company_id)
            ->orderBy('name')
            ->get();

        $userLimit = $company ? (int) $company->number_of_accounts : null;
        $activeUsersCount = $company ? $company->activeUsers()->count() : null;
        $remainingAccounts = ($company && $userLimit !== null) ? max(0, $userLimit - $activeUsersCount) : null;

        return view('pages.users.create', compact('branches', 'roles', 'company', 'userLimit', 'activeUsersCount', 'remainingAccounts'));
    }

    /**
     * Store new user.
     */
    public function store(StoreUserRequest $request)
    {
        $company = $this->currentCompany();

        if ($company && $company->activeUsers()->count() >= $company->user_limit) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "User limit reached for this company ({$company->activeUsers()->count()} of {$company->user_limit} accounts used, including Company Admin). Please contact admin to increase the limit.");
        }

        $data = $request->validated();
        $roleName = $data['role'] ?? null;
        $data['is_active'] = $request->boolean('is_active');
        $data['company_id'] = auth()->user()?->company_id;

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $selectedBranches = $data['branches'] ?? [];
        unset($data['password_confirmation'], $data['role'], $data['branches']);

        $user = User::create($data);
        $user->branches()->sync($selectedBranches);

        // Assign role via Spatie
        if ($roleName) {
            $role = $this->resolveAssignableRole($roleName);

            $user->syncRoles([$role]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->syncCompanySuperAdmin($user, $role->name);
            $this->syncEmployeeOnboardingRole($user, $role);
        }

        return redirect()
            ->route('users.index')
            ->with('success', "User <strong>{$user->name}</strong> created successfully.");
    }

    /**
     * Show user detail.
     */
    public function show(User $user)
    {
        $user->load(['branch', 'roles', 'roles.permissions', 'permissions']);
        $roles = Role::withoutGlobalScope('company')
            ->where('company_id', $user->company_id)
            ->orderByRaw('COALESCE(display_name, name)')
            ->get();
        $permissions = Permission::orderBy('module')
            ->orderByRaw('COALESCE(display_name, name)')
            ->get()
            ->groupBy(fn (Permission $permission) => $permission->module ?: 'general');

        return view('pages.users.show', compact('user', 'roles', 'permissions'));
    }

    /**
     * Show edit form.
     */
    public function edit(User $user)
    {
        // $this->authorize('users.manage');

        Branch::ensureDefaultForCurrentCompany();
        $branches    = Branch::active()->orderByDesc('is_default')->orderBy('name')->get();
        $roles       = Role::withoutGlobalScope('company')
            ->where('company_id', $user->company_id)
            ->orderBy('display_name')
            ->get();
        $currentRole = $user->roles->first()?->name;
        if (! $currentRole) {
            $employee = EmployeeOnboarding::withoutGlobalScopes()
                ->where('portal_user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();
            $currentRole = $employee?->role?->name;
        }

        return view('pages.users.edit', compact('user', 'branches', 'roles', 'currentRole'));
    }

    /**
     * Update user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // $this->authorize('users.manage');

        $data = $request->validated();
        $roleName = $data['role'] ?? null;
        $data['is_active'] = $request->boolean('is_active');

        // Handle avatar
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Only update password if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $selectedBranches = $data['branches'] ?? [];
        unset($data['password_confirmation'], $data['role'], $data['branches']);

        $user->update($data);
        $user->branches()->sync($selectedBranches);

        $this->syncEmployeeOnboardingStatus($user);
        $this->syncInternJoiningFormStatus($user);

        // Sync role
        if ($roleName) {
            $role = $this->resolveAssignableRole($roleName);

            $user->syncRoles([$role]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->syncCompanySuperAdmin($user, $role->name);
            $this->syncEmployeeOnboardingRole($user, $role);
        } else {
            $user->syncRoles([]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->syncCompanySuperAdmin($user, null);
            $this->syncEmployeeOnboardingRole($user, null);
        }

        return redirect()
            ->route('users.index')
            ->with('success', "User <strong>{$user->name}</strong> updated successfully.");
    }

    /**
     * Soft-delete user.
     */
    public function destroy(User $user)
    {
        // $this->authorize('users.manage');

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSystemAdmin()) {
            return back()->with('error', 'Super admin accounts cannot be deleted.');
        }

        if ($this->isCurrentCompanySuperAdmin($user)) {
            return back()->with('error', 'Company admin account cannot be deleted. Assign another company admin first.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "User <strong>{$user->name}</strong> has been removed.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(User $user)
    {
        // $this->authorize('users.manage');

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($this->isCurrentCompanySuperAdmin($user)) {
            return back()->with('error', 'Company admin account cannot be deactivated. Assign another company admin first.');
        }

        if (! $user->is_active) {
            $company = $this->currentCompany();
            if ($company && $company->activeUsers()->count() >= $company->user_limit) {
                return back()->with('error', "Cannot activate user. Account limit reached ({$company->activeUsers()->count()} of {$company->user_limit} accounts used, including Company Admin). Please contact admin to increase the limit.");
            }
        }

        $newActive = ! $user->is_active;
        $user->update([
            'is_active' => $newActive,
            'user_status' => $newActive ? 'active' : 'inactive',
        ]);

        $this->syncEmployeeOnboardingStatus($user);
        $this->syncInternJoiningFormStatus($user);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User <strong>{$user->name}</strong> has been {$status}.");
    }

    private function syncEmployeeOnboardingStatus(User $user): void
    {
        $employee = EmployeeOnboarding::withoutGlobalScopes()
            ->where('portal_user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (! $employee) {
            return;
        }

        if (! $user->is_active) {
            $employee->update(['status' => EmployeeOnboarding::STATUS_INACTIVE]);
        } elseif ($user->is_active && $employee->status === EmployeeOnboarding::STATUS_INACTIVE) {
            $employee->update(['status' => EmployeeOnboarding::STATUS_ACTIVE]);
        }
    }

    private function syncEmployeeOnboardingRole(User $user, ?Role $role): void
    {
        $employees = EmployeeOnboarding::withoutGlobalScopes()
            ->where('portal_user_id', $user->id)
            ->orWhere(function ($q) use ($user) {
                $q->where('email', $user->email);
                if (!empty($user->official_email)) {
                    $q->orWhere('official_email', $user->official_email);
                }
            })
            ->get();

        foreach ($employees as $employee) {
            $employee->portal_user_id = $user->id;
            $employee->role_id = $role?->id;
            if ($role && $role->department_id) {
                $employee->department_id = $role->department_id;
            }
            $employee->save();
        }

        $interns = InternJoiningForm::withoutGlobalScopes()
            ->where('portal_user_id', $user->id)
            ->orWhere('email', $user->email)
            ->get();

        foreach ($interns as $intern) {
            $intern->portal_user_id = $user->id;
            $intern->role_id = $role?->id;
            if ($role && $role->department_id) {
                $intern->department_id = $role->department_id;
            }
            $intern->save();
        }
    }

    private function syncInternJoiningFormStatus(User $user): void
    {
        $intern = InternJoiningForm::withoutGlobalScopes()
            ->where('portal_user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (! $intern) {
            return;
        }

        if (! $user->is_active) {
            $intern->update(['internship_status' => InternJoiningForm::STATUS_INACTIVE]);
        } elseif ($user->is_active && $intern->internship_status === InternJoiningForm::STATUS_INACTIVE) {
            $intern->update(['internship_status' => InternJoiningForm::STATUS_ACTIVE]);
        }
    }

    /**
     * Reset user password (admin action).
     */
    public function resetPassword(Request $request, User $user)
    {
        // $this->authorize('users.manage');

        $request->validate([
            'new_password'              => ['required', 'string', 'min:8', 'confirmed'],
            'new_password_confirmation' => ['required'],
        ]);

        $user->update(['password' => Hash::make($request->new_password)]);

        return back()->with('success', "Password for <strong>{$user->name}</strong> has been reset.");
    }

    public function authIndex()
    {
        return view('pages.auth_menu.index');
    }

    private function currentCompany(): ?Company
    {
        $companyId = auth()->user()?->company_id;

        if (! $companyId) {
            return null;
        }

        return Company::find($companyId);
    }

    private function syncCompanySuperAdmin(User $user, ?string $roleName): void
    {
        if (! $user->company_id) {
            return;
        }

        $company = Company::find($user->company_id);

        if (! $company) {
            return;
        }

        $companyAdminRole = Role::tenantRoleName('company_admin', $company->id);

        if ($roleName === $companyAdminRole) {
            if ($company->super_admin_user_id && (int) $company->super_admin_user_id !== (int) $user->id) {
                $previousSuperAdmin = User::find($company->super_admin_user_id);

                if ($previousSuperAdmin) {
                    $previousSuperAdmin->removeRole($companyAdminRole);
                }
            }

            $company->update(['super_admin_user_id' => $user->id]);

            return;
        }

        if ((int) $company->super_admin_user_id === (int) $user->id) {
            $company->update(['super_admin_user_id' => null]);
        }
    }

    private function isCurrentCompanySuperAdmin(User $user): bool
    {
        if (! $user->company_id) {
            return false;
        }

        return Company::whereKey($user->company_id)
            ->where('super_admin_user_id', $user->id)
            ->exists();
    }

    private function resolveAssignableRole(string $roleName): Role
    {
        return Role::withoutGlobalScopes()
            ->where('name', $roleName)
            ->firstOrFail();
    }
}
