<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens, BelongsToCompany {
        hasRole as protected spatieHasRole;
        hasAnyRole as protected spatieHasAnyRole;
        hasAllRoles as protected spatieHasAllRoles;
        hasExactRoles as protected spatieHasExactRoles;
        getRoleNames as protected spatieGetRoleNames;
        hasPermissionTo as protected spatieHasPermissionTo;
        checkPermissionTo as protected spatieCheckPermissionTo;
        hasAnyPermission as protected spatieHasAnyPermission;
        hasAllPermissions as protected spatieHasAllPermissions;
        hasDirectPermission as protected spatieHasDirectPermission;
        getAllPermissions as protected spatieGetAllPermissions;
        getPermissionsViaRoles as protected spatieGetPermissionsViaRoles;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'branch_id',
        'phone',
        'avatar',
        'photo',
        'designation',
        'is_active',
        'user_status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('branch', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (! auth()->hasUser()) {
                return;
            }

            $user = auth()->user();
            if ($user && $user->isBranchAdmin()) {
                $branchIds = $user->getMyBranchIds();
                if (!empty($branchIds)) {
                    $builder->whereIn($builder->getModel()->getTable() . '.branch_id', $branchIds);
                }
            }
        });
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['designation'] = $value;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['designation'] ?? null;
    }

    public function setAvatarAttribute($value): void
    {
        $this->attributes['photo'] = $value;
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->attributes['photo'] ?? null;
    }

    public function setIsActiveAttribute($value): void
    {
        $isActive = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $isActive = $isActive ?? (bool) $value;

        $this->attributes['is_active'] = $isActive;
        $this->attributes['user_status'] = $isActive ? 'active' : 'inactive';
    }

    public function getIsActiveAttribute($value): bool
    {
        if ($value !== null) {
            return (bool) $value;
        }

        return ($this->attributes['user_status'] ?? null) === 'active';
    }

    public function setUserStatusAttribute($value): void
    {
        $status = $value === 'active' ? 'active' : 'inactive';

        $this->attributes['user_status'] = $status;
        $this->attributes['is_active'] = $status === 'active';
    }

    // ── Relationships ──────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user');
    }

    protected ?array $memoizedBranchIds = null;

    public function getMyBranchIds(): array
    {
        if ($this->memoizedBranchIds === null) {
            $ids = [];
            try {
                $ids = \DB::table('branch_user')
                    ->where('user_id', $this->id)
                    ->pluck('branch_id')
                    ->map(fn($id) => (int) $id)
                    ->all();
            } catch (\Throwable $e) {
                // Fail-safe if table doesn't exist
            }
            if ($this->branch_id) {
                $ids[] = (int) $this->branch_id;
            }
            $this->memoizedBranchIds = array_unique($ids);
        }
        return $this->memoizedBranchIds;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function managedUserMappings(): HasMany
    {
        return $this->hasMany(UserMapping::class, 'manager_id');
    }

    public function managerMappings(): HasMany
    {
        return $this->hasMany(UserMapping::class, 'user_id');
    }

    public function managedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_mappings', 'manager_id', 'user_id')
            ->withTimestamps();
    }

    public function mappedManagers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_mappings', 'user_id', 'manager_id')
            ->withTimestamps();
    }

    // ── Convenience Helpers (wrap Spatie) ──────────────────────

    /**
     * Get the user's primary role name.
     */
    public function getRoleNameAttribute(): string
    {
        return $this->resolvedRoles()->first()?->name ?? 'No Role';
    }

    /**
     * Get the user's primary role display name.
     */
    public function getRoleDisplayNameAttribute(): string
    {
        $role = $this->resolvedRoles()->first();
        return $role ? ($role->display_name ?? ucfirst(str_replace('_', ' ', $role->name))) : 'No Role';
    }

    /**
     * Get the user's branch name.
     */
    public function getBranchNameAttribute(): string
    {
        return $this->branch?->name ?? 'No Branch';
    }

    /**
     * Check if user is super admin (bypasses permission checks).
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasSystemRole();
    }

    public function isSystemAdmin(): bool
    {
        if ($this->company_id !== null) {
            return false;
        }

        return $this->hasSystemRole();
    }

    public function isCompanyAdmin(): bool
    {
        if ($this->company_id === null) {
            return false;
        }

        return $this->hasExactRoleName(Role::tenantRoleName('company_admin', $this->company_id));
    }

    public function isBranchAdmin(): bool
    {
        if ($this->company_id === null) {
            return false;
        }

        return $this->hasExactRoleName(Role::tenantRoleName('branch_admin', $this->company_id));
    }

    private function hasExactRoleName(string $roleName): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->resolvedRoles()->contains('name', $roleName);
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', self::class)
            ->where('model_has_roles.model_id', $this->getKey())
            ->where('roles.name', $roleName)
            ->exists();
    }

    public function hasCrmPermission(string $permission): bool
    {
        return $this->getAllPermissions()->contains('name', $permission);
    }

    private function hasSystemRole(): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->resolvedRoles()->contains(fn($role) => $this->isSystemRoleName($role->name));
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', self::class)
            ->where('model_has_roles.model_id', $this->getKey())
            ->get(['roles.name'])
            ->contains(fn($role) => $this->isSystemRoleName($role->name));
    }

    private function isSystemRoleName(string $roleName): bool
    {
        return in_array(Str::of($roleName)->lower()->replace(' ', '_')->value(), ['super_admin', 'admin'], true);
    }

    public function dashboardRoute(): string
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin() || $this->hasAdminLikeRole()) {
            return 'dashboard.admin';
        }

        if ($departmentRoute = $this->departmentDashboardRoute()) {
            return $departmentRoute;
        }

        if ($this->belongsToCustomerSupportDepartment() || $this->hasCustomerSupportLikeRole()) {
            return 'dashboard.customer-success';
        }

        if ($this->isHrmsAttendanceOnlyUser()) {
            return 'hrms.dashboard';
        }

        if ($this->belongsToHrDepartment() || $this->hasHrLikeRole()) {
            return 'hrms.dashboard';
        }

        if ($this->belongsToSalesDepartment() || $this->hasExecutiveLikeRole()) {
            return 'dashboard.admin';
        }

        return 'dashboard.admin';
    }

    public function departmentDashboardRoute(): ?string
    {
        $roles = $this->resolvedRoles(withDepartment: true);

        $route = $roles
            ->map(fn($role) => $role->department?->dashboard_route)
            ->filter()
            ->first();

        if (! $route || ! app('router')->has($route)) {
            return null;
        }

        return $route;
    }

    public function belongsToHrDepartment(): bool
    {
        return collect($this->departmentKeys()->all())->intersect([
            'hr',
            'human_resource',
            'human_resources',
            'hrms',
            'people_operations',
            'talent_acquisition',
            'recruitment',
        ])->isNotEmpty();
    }

    public function hasHrLikeRole(): bool
    {
        return collect($this->roleKeys()->all())->intersect([
            'hr',
            'human_resource',
            'human_resources',
            'hr_manager',
            'hr_executive',
            'hrms',
            'people_operations',
            'talent_acquisition',
            'recruitment',
        ])->isNotEmpty();
    }

    public function belongsToDesigningDepartment(): bool
    {
        return collect($this->departmentKeys()->all())->contains(function ($key) {
            return str_contains($key, 'design');
        });
    }

    public function belongsToDigitalMarketingDepartment(): bool
    {
        return collect($this->departmentKeys()->all())->contains(function ($key) {
            return str_contains($key, 'digital') || str_contains($key, 'marketing') || str_contains($key, 'dm');
        });
    }

    public function belongsToSalesDepartment(): bool
    {
        return collect($this->departmentKeys()->all())->intersect([
            'sales',
            'crm',
            'business_development',
            'marketing',
            'telecalling',
        ])->isNotEmpty();
    }

    public function hasSalesLikeRole(): bool
    {
        return collect($this->roleKeys()->all())->intersect([
            'sales_manager',
            'sales_executive',
            'sales_tl',
            'sales_intern',
            'bde',
            'business_development_executive',
            'telecaller',
        ])->isNotEmpty();
    }

    public function hasExecutiveLikeRole(): bool
    {
        return collect($this->roleKeys()->all())->intersect([
            'executive',
            'sales_executive',
            'hr_executive',
            'bde',
            'business_development_executive',
            'telecaller',
        ])->isNotEmpty();
    }

    public function belongsToCustomerSupportDepartment(): bool
    {
        return collect($this->departmentKeys()->all())->contains(function ($key) {
            return str_contains($key, 'support') || str_contains($key, 'success') || str_contains($key, 'cst');
        });
    }

    public function hasCustomerSupportLikeRole(): bool
    {
        return collect($this->roleKeys()->all())->contains(function ($key) {
            return str_contains($key, 'support') || str_contains($key, 'success') || str_contains($key, 'cst');
        });
    }

    public function hasPreSalesLikeRole(): bool
    {
        return collect($this->roleKeys()->all())->contains(function ($key) {
            return str_contains($key, 'pre_sale') || str_contains($key, 'presale');
        });
    }

    public function belongsToTestingDepartment(): bool
    {
        return collect($this->departmentKeys()->all())->contains(function ($key) {
            return str_contains($key, 'testing') || str_contains($key, 'qa');
        });
    }

    public function hasTestingLikeRole(): bool
    {
        return collect($this->roleKeys()->all())->contains(function ($key) {
            return str_contains($key, 'testing') || str_contains($key, 'qa');
        });
    }

    public function belongsToDevelopmentDepartment(): bool
    {
        return collect($this->departmentKeys()->all())->contains(function ($key) {
            return str_contains($key, 'development') || str_contains($key, 'dev') || str_contains($key, 'software') || str_contains($key, 'web') || str_contains($key, 'app');
        });
    }

    public function hasDevelopmentLikeRole(): bool
    {
        return collect($this->roleKeys()->all())->contains(function ($key) {
            return str_contains($key, 'developer') || str_contains($key, 'development') || str_contains($key, 'software') || str_contains($key, 'web') || str_contains($key, 'app');
        });
    }

    public function hasTlLikeRole(): bool
    {
        $keys = collect($this->roleKeys()->all());

        if ($keys->intersect([
            'tl',
            'team_lead',
            'team_leader',
            'teamlead',
            'project_manager',
            'web_team_leader',
            'mobile_app_team_leader',
            'design_team_lead',
            'team_lead_digital_marketing',
            'software_team_leader',
            'human_resource',
            'senior_customer_success_team_executive',
            'senior_customer_success_executive',
            'senior_success_executive',
            'senior_customer_support_executive',
            'senior_support_executive',
            'senior_cst_executive',
        ])->isNotEmpty()) {
            return true;
        }

        return $keys->contains(fn($key) => Str::contains($key, [
            'tl',
            'team_lead',
            'teamleader',
            'team_leader',
            'manager',
            'lead',
        ]));
    }

    public function hasAdminLikeRole(): bool
    {
        return collect($this->roleKeys()->all())->intersect([
            'super_admin',
            'admin',
            'company_admin',
            'branch_admin',
            'coo',
            'cbo',
            'cheif_operating_officer',
            'chief_operating_officer',
            'chief_business_officer',
            'development_project_coordinator'
        ])->isNotEmpty();
    }

    public function isDevelopmentProjectCoordinator(): bool
    {
        $keys = collect($this->roleKeys()->all());
        if ($keys->contains('development_project_coordinator')) {
            return true;
        }

        return $this->resolvedRoles(withDepartment: true)->contains(function ($role) {
            $roleNameKey = \Illuminate\Support\Str::slug((string) $role->name, '_');
            $displayNameKey = \Illuminate\Support\Str::slug((string) ($role->display_name ?? ''), '_');
            $deptKey = \Illuminate\Support\Str::slug((string) ($role->department?->name ?? ''), '_');

            if ($roleNameKey === 'development_project_coordinator' || $displayNameKey === 'development_project_coordinator') {
                return true;
            }

            $isPc = collect([$roleNameKey, $displayNameKey])->intersect(['project_coordinator', 'project_coordination', 'pc'])->isNotEmpty();
            return $isPc && \Illuminate\Support\Str::contains($deptKey, 'develop');
        });
    }

    public function canViewBudgetApprovalDetails(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $keys = collect($this->roleKeys()->all());

        if ($keys->intersect([
            'company_admin',
            'coo',
            'cbo',
            'cheif_operating_officer',
            'chief_operating_officer',
            'chief_business_officer'
        ])->isNotEmpty()) {
            return true;
        }

        if ($this->belongsToDigitalMarketingDepartment() && $this->hasTlLikeRole()) {
            return true;
        }

        if ($keys->contains(fn($k) => Str::contains($k, ['digital_marketing']) && Str::contains($k, ['tl', 'lead', 'leader']))) {
            return true;
        }

        return false;
    }

    public function canAccessProjectsModule(): bool
    {
        return $this->can('modules_menu.projects')
            || $this->hasAdminLikeRole()
            || $this->hasTlLikeRole()
            || $this->hasExecutiveLikeRole();
    }

    public function canViewProjectsDashboardSwitcher(): bool
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin()) {
            return true;
        }

        $keys = collect($this->roleKeys()->all());

        return $keys->intersect([
            'company_admin',
            'cto',
            'chief_technology_officer',
            'cheif_technology_officer',
            'coo',
            'chief_operating_officer',
            'cheif_operating_officer',
            'development_project_coordinator',
            'project_coordinator',
            'project_coordination',
            'pc',
        ])->isNotEmpty();
    }

    public function canAccessCstModule(): bool
    {
        return $this->isCompanyAdmin()
            || $this->hasAdminLikeRole()
            || $this->belongsToCustomerSupportDepartment()
            || $this->hasCustomerSupportLikeRole();
    }

    public function isCustomerSuccessUser(): bool
    {
        if ($this->isSuperAdmin() || $this->isCompanyAdmin()) {
            return true;
        }

        return $this->belongsToCustomerSupportDepartment() || $this->hasCustomerSupportLikeRole();
    }

    public function canAccessCrmModule(): bool
    {
        return $this->isSuperAdmin()
            || $this->isCompanyAdmin()
            || $this->hasAdminLikeRole()
            || $this->hasSalesLikeRole()
            || $this->belongsToSalesDepartment()
            || $this->belongsToDigitalMarketingDepartment()
            || $this->can('modules_menu.crm')
            || $this->can('dashboard.view')
            || $this->can('leads.menuview');
    }

    public function isExecutiveHrmsUser(): bool
    {
        return $this->hasExecutiveLikeRole()
            && ! $this->hasSalesLikeRole()
            && ! $this->belongsToSalesDepartment()
            && ! $this->belongsToHrDepartment()
            && ! $this->hasAdminLikeRole()
            && ! $this->isCompanyAdmin()
            && ! $this->isSuperAdmin();
    }

    public function isHrmsAttendanceOnlyUser(): bool
    {
        return $this->hasExecutiveLikeRole()
            && ! $this->hasSalesLikeRole()
            && ! $this->belongsToSalesDepartment()
            && ! $this->belongsToHrDepartment()
            && ! $this->hasAdminLikeRole()
            && ! $this->isCompanyAdmin()
            && ! $this->isSuperAdmin();
    }

    /**
     * Check if the user is HR or Admin.
     */
    public function isHrOrAdmin(): bool
    {
        return $this->isSuperAdmin()
            || $this->isCompanyAdmin()
            || $this->hasAdminLikeRole()
            || $this->hasHrLikeRole()
            || $this->belongsToHrDepartment();
    }

    public function roleKeys(): \Illuminate\Support\Collection
    {
        $roles = $this->resolvedRoles();

        return collect($roles
            ->flatMap(function ($role) {
                return array_filter([
                    $this->normalizeDashboardKey((string) $role->name),
                    $this->normalizeDashboardKey((string) ($role->display_name ?? '')),
                ]);
            })
            ->unique()
            ->values()
            ->all());
    }

    private function departmentKeys(): \Illuminate\Support\Collection
    {
        $roles = $this->resolvedRoles(withDepartment: true);

        return collect($roles
            ->map(fn($role) => $this->normalizeDashboardKey((string) ($role->department?->name ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all());
    }

    private function normalizeDashboardKey(string $value): string
    {
        $value = Str::contains($value, '__')
            ? Str::afterLast($value, '__')
            : $value;

        return Str::of($value)
            ->lower()
            ->replace('&', 'and')
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();
    }

    public function resolvedRoles(bool $withDepartment = false): EloquentCollection
    {
        $this->ensureSpatieRelationsAreModels();

        if ($this->relationLoaded('roles')) {
            $roles = $this->getRelation('roles');

            if ($roles instanceof EloquentCollection && $roles->every(fn($role) => $role instanceof Role)) {
                return $withDepartment ? $roles->loadMissing('department') : $roles;
            }

            $this->unsetRelation('roles');
        }

        $query = $this->roles();

        if ($withDepartment) {
            $query->with('department');
        }

        $roles = $query->get();
        $this->setRelation('roles', $roles);

        return $roles;
    }

    public function resolvedPermissions(): EloquentCollection
    {
        $this->ensureSpatieRelationsAreModels();

        if ($this->relationLoaded('permissions')) {
            $permissions = $this->getRelation('permissions');

            if ($permissions instanceof EloquentCollection && $permissions->every(fn($permission) => $permission instanceof Permission)) {
                return $permissions;
            }

            $this->unsetRelation('permissions');
        }

        $permissions = $this->permissions()->get();
        $this->setRelation('permissions', $permissions);

        return $permissions;
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieHasRole($roles, $guard);
    }

    public function hasAnyRole(...$roles): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieHasAnyRole(...$roles);
    }

    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieHasAllRoles($roles, $guard);
    }

    public function hasExactRoles($roles, ?string $guard = null): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieHasExactRoles($roles, $guard);
    }

    public function getRoleNames(): \Illuminate\Support\Collection
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieGetRoleNames();
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    public function checkPermissionTo($permission, $guardName = null): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieCheckPermissionTo($permission, $guardName);
    }

    public function hasAnyPermission(...$permissions): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieHasAnyPermission(...$permissions);
    }

    public function hasAllPermissions(...$permissions): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieHasAllPermissions(...$permissions);
    }

    public function hasDirectPermission($permission): bool
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieHasDirectPermission($permission);
    }

    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieGetAllPermissions();
    }

    public function getPermissionsViaRoles(): \Illuminate\Support\Collection
    {
        $this->ensureSpatieRelationsAreModels();

        return $this->spatieGetPermissionsViaRoles();
    }

    private function ensureSpatieRelationsAreModels(): void
    {
        if ($this->relationLoaded('roles')) {
            $roles = $this->getRelation('roles');

            if (! ($roles instanceof EloquentCollection) || ! $roles->every(fn($role) => $role instanceof Role)) {
                $this->unsetRelation('roles');
            }
        }

        if ($this->relationLoaded('permissions')) {
            $permissions = $this->getRelation('permissions');

            if (! ($permissions instanceof EloquentCollection) || ! $permissions->every(fn($permission) => $permission instanceof Permission)) {
                $this->unsetRelation('permissions');
            }
        }
    }

    public function employeeOnboarding(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\EmployeeOnboarding::class, 'portal_user_id');
    }

    public function internJoiningForm(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\InternJoiningForm::class, 'portal_user_id');
    }

    // gokul

    public function isDevelopmentTeam(): bool
    {
        return ! $this->hasSalesLikeRole() && ! $this->hasCustomerSupportLikeRole();
    }

    public function canAccessMobileProjectsModule(): bool
    {
        return $this->can('modules_menu.projects')
            || $this->isSuperAdmin()
            || $this->isCompanyAdmin()
            || $this->isDevelopmentTeam();
    }

    public function canAccessMobileCrmModule(): bool
    {
        return $this->canAccessCrmModule();
    }

    public function canAccessMobileCstModule(): bool
    {
        return $this->can('modules_menu.cst')
            || $this->isSuperAdmin()
            || $this->isCompanyAdmin()
            || $this->hasCustomerSupportLikeRole();
    }

    public function canAccessHrmsModule(): bool
    {
        return true;
    }
}
