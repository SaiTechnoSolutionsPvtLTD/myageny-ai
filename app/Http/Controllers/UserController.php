<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Company;
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

        $query = User::with(['branch', 'roles'])
            ->when($request->search, fn($q) =>
                $q->where(fn($q2) =>
                    $q2->where('name', 'like', '%'.$request->search.'%')
                       ->orWhere('email', 'like', '%'.$request->search.'%')
                       ->orWhere('designation', 'like', '%'.$request->search.'%')
                )
            )
            ->when($request->branch_id, fn($q) =>
                $q->where('branch_id', $request->branch_id)
            )
            ->when($request->role, fn($q) =>
                $q->whereHas('roles', fn($q2) =>
                    $q2->where('name', $request->role)
                )
            )
            ->when($request->status !== null && $request->status !== '', fn($q) =>
                $q->where('is_active', $request->status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::active()->orderByDesc('is_default')->orderBy('name')->get();
        $roles    = Role::orderBy('name')->get();

        return view('pages.users.index', compact('query', 'branches', 'roles'));
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
                ->with('error', 'User limit reached for this company. Please contact admin to increase the limit.');
        }

        Branch::ensureDefaultForCurrentCompany();
        $branches = Branch::active()->orderByDesc('is_default')->orderBy('name')->get();
        $roles    = Role::withoutGlobalScope('company')
            ->where('company_id', auth()->user()?->company_id)
            ->orderBy('name')
            ->get();

        return view('pages.users.create', compact('branches', 'roles'));
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
                ->with('error', 'User limit reached for this company. Please contact admin to increase the limit.');
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

        // Sync role
        if ($roleName) {
            $role = $this->resolveAssignableRole($roleName);

            $user->syncRoles([$role]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->syncCompanySuperAdmin($user, $role->name);
        } else {
            $this->syncCompanySuperAdmin($user, null);
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

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User <strong>{$user->name}</strong> has been {$status}.");
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
