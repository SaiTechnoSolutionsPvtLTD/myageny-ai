<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RolePermissionController extends Controller
{
    private const DEFAULT_PERMISSION_ACTIONS = [
        'create',
        'edit',
        'view',
        'delete',
        'update',
        'approve',
        'reject',
    ];

    // ── ROLES ──────────────────────────────────────────────────

    /**
     * List all roles.
     */
    public function rolesIndex()
    {
        $roles = Role::withCount('users')
            ->with(['permissions', 'department'])
            ->orderByRaw('COALESCE(display_name, name)')
            ->paginate(10)
            ->withQueryString();

        return view('pages.auth_menu.roles.index', compact('roles'));
    }

    /**
     * Show create role form.
     */
    public function rolesCreate()
    {
        $departments = Department::orderBy('name')->get();

        return view('pages.auth_menu.roles.create', compact('departments'));
    }

    /**
     * Store new role.
     */
    public function rolesStore(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'display_name' => ['required', 'string', 'max:150'],
            'description'  => ['nullable', 'string', 'max:500'],
            'department_id'=> ['nullable', 'exists:departments,id'],
            'permissions'  => ['nullable', 'array'],
            'permissions.*'=> ['exists:permissions,name'],
        ]);

        $roleName = Role::tenantRoleName($data['name'], $companyId);

        if (Role::withoutGlobalScopes()->where('name', $roleName)->exists()) {
            return back()
                ->withErrors(['name' => 'A role with this key already exists for this company.'])
                ->withInput();
        }

        $role = Role::create([
            'name'         => $roleName,
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
            'department_id'=> $data['department_id'] ?? null,
            'guard_name'   => 'web',
            'company_id'   => $companyId,
        ]);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return redirect()->route('auth.roles.index')
            ->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Show edit role form.
     */
    public function rolesEdit(Role $role)
    {
        $departments = Department::orderBy('name')->get();

        return view('pages.auth_menu.roles.edit', compact('role', 'departments'));
    }

    /**
     * Update role.
     */
    public function rolesUpdate(Request $request, Role $role)
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:150'],
            'description'  => ['nullable', 'string', 'max:500'],
            'department_id'=> ['nullable', 'exists:departments,id'],
        ]);

        $role->update([
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
            'department_id'=> $data['department_id'] ?? null,
        ]);
        return redirect()->route('auth.roles.index')
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function rolesPermissionsEdit(Role $role)
    {
        $permissions = Permission::orderBy('module')
            ->orderByRaw('COALESCE(display_name, name)')
            ->get()
            ->groupBy(fn (Permission $permission) => $permission->module ?: 'general');
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('pages.auth_menu.roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    public function rolesPermissionsUpdate(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        $roleLabel = $role->display_name ?: $role->name;

        return redirect()
            ->route('auth.roles.permissions.edit', $role)
            ->with('success', "Permissions updated for role '{$roleLabel}'.");
    }

    /**
     * Delete role.
     */
    public function rolesDestroy(Role $role)
    {
        if (strtolower(str_replace(' ', '_', $role->name)) === 'super_admin') {
            return back()->with('error', 'The super_admin role cannot be deleted.');
        }

        if ($role->company_id && $role->name === Role::tenantRoleName('company_admin', $role->company_id)) {
            return back()->with('error', 'The company_admin role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "Cannot delete role with {$role->users()->count()} assigned user(s). Reassign users first.");
        }

        $role->delete();

        return redirect()->route('auth.roles.index')
            ->with('success', "Role deleted successfully.");
    }

    // ── PERMISSIONS ────────────────────────────────────────────

    /**
     * List all permissions grouped by module.
     */
    public function permissionsIndex(Request $request)
    {
        $selectedModule = trim((string) $request->input('module', ''));
        $search = trim((string) $request->input('search', ''));

        $permissionPages = Permission::query()
            ->when($selectedModule !== '', function ($query) use ($selectedModule) {
                $query->where('module', $selectedModule);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('display_name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('module', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('module')
            ->orderByRaw('COALESCE(display_name, name)')
            ->paginate(15)
            ->withQueryString();

        $modules = Permission::query()
            ->select('module')
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $permissions = $permissionPages->getCollection()
            ->groupBy(fn (Permission $permission) => $permission->module ?: 'general');

        return view('pages.auth_menu.permissions.index', compact('permissions', 'permissionPages', 'modules', 'selectedModule', 'search'));
    }

    /**
     * Show create permission form.
     */
    public function permissionsCreate()
    {
        $defaultActions = self::DEFAULT_PERMISSION_ACTIONS;

        return view('pages.auth_menu.permissions.create', compact('defaultActions'));
    }

    /**
     * Store new permission.
     */
    public function permissionsStore(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        $data = $request->validate([
            'module'       => ['required', 'string', 'max:50'],
            'actions'      => ['nullable', 'array'],
            'actions.*'    => ['string', 'in:' . implode(',', self::DEFAULT_PERMISSION_ACTIONS)],
            'custom_actions' => ['nullable', 'string', 'max:500'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);

        $moduleKey = Str::slug($data['module'], '_');
        $moduleName = Str::title(str_replace('_', ' ', $moduleKey));
        $customActions = collect(preg_split('/[\s,]+/', (string) ($data['custom_actions'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $action) => Str::slug($action, '_'))
            ->filter()
            ->values()
            ->all();
        $actions = collect($data['actions'] ?? [])
            ->merge($customActions)
            ->unique()
            ->values();

        if ($actions->isEmpty()) {
            return back()
                ->withErrors(['actions' => 'Select at least one default action or add a custom action.'])
                ->withInput();
        }

        $createdPermissions = [];
        $skippedPermissions = [];

        foreach ($actions as $action) {
            $permissionName = Permission::tenantPermissionName($moduleKey, $action, $companyId);

            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'display_name' => Str::title($action . ' ' . $moduleName),
                    'module' => $moduleKey,
                    'description' => $data['description'] ?: ('Allows users to ' . $action . ' ' . strtolower($moduleName) . '.'),
                    'company_id' => $companyId,
                ]
            );

            if ($permission->wasRecentlyCreated) {
                $createdPermissions[] = $permissionName;
            } else {
                $skippedPermissions[] = $permissionName;
            }
        }

        $message = count($createdPermissions) . " permission(s) created for module '{$moduleName}'.";

        if ($skippedPermissions !== []) {
            $message .= ' Skipped existing: ' . implode(', ', $skippedPermissions) . '.';
        }

        return redirect()
            ->route('auth.permissions.index')
            ->with('success', $message);
    }

    /**
     * Show edit permission form.
     */
    public function permissionsEdit(Permission $permission)
    {
        return view('pages.auth_menu.permissions.edit', compact('permission'));
    }

    /**
     * Update permission.
     */
    public function permissionsUpdate(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:150'],
            'module'       => ['required', 'string', 'max:50'],
            'description'  => ['nullable', 'string', 'max:500'],
        ]);

        $permission->update($data);

        return redirect()
            ->route('auth.permissions.index')
            ->with('success', "Permission '{$permission->name}' updated.");
    }

    /**
     * Delete permission.
     */
    public function permissionsDestroy(Permission $permission)
    {
        $permission->delete();

        return back()->with('success', "Permission deleted.");
    }

    // ── USER ROLE ASSIGNMENT ───────────────────────────────────

    /**
     * Assign roles and permissions to a user.
     */
    public function assignUserRole(Request $request, User $user)
    {
        $companyId = auth()->user()?->company_id;

        $data = $request->validate([
            'roles'       => ['nullable', 'array'],
            'roles.*'     => ['exists:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*'=> ['exists:permissions,name'],
        ]);

        if ($companyId !== null) {
            $invalidRole = Role::withoutGlobalScopes()
                ->whereIn('name', $data['roles'] ?? [])
                ->where(function ($query) use ($companyId) {
                    $query->whereNull('company_id')->orWhere('company_id', '!=', $companyId);
                })
                ->exists();

            $invalidPermission = Permission::withoutGlobalScopes()
                ->whereIn('name', $data['permissions'] ?? [])
                ->where(function ($query) use ($companyId) {
                    $query->whereNull('company_id')->orWhere('company_id', '!=', $companyId);
                })
                ->exists();

            abort_if($invalidRole || $invalidPermission, 403);
        }

        // Sync roles
        $user->syncRoles($data['roles'] ?? []);

        // Sync direct permissions (extra permissions beyond role)
        $user->syncPermissions($data['permissions'] ?? []);

        $this->syncCompanySuperAdmin($user, $data['roles'] ?? []);

        return back()->with('success', "Roles and permissions updated for {$user->name}.");
    }

    private function syncCompanySuperAdmin(User $user, array $roleNames): void
    {
        if (! $user->company_id) {
            return;
        }

        $companyAdminRole = Role::tenantRoleName('company_admin', $user->company_id);
        $company = \App\Models\Company::find($user->company_id);

        if (! $company) {
            return;
        }

        if (in_array($companyAdminRole, $roleNames, true)) {
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
}
