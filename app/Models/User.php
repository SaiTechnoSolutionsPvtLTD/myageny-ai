<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
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
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens, BelongsToCompany;

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
        return $this->roles->first()?->name ?? 'No Role';
    }

    /**
     * Get the user's primary role display name.
     */
    public function getRoleDisplayNameAttribute(): string
    {
        $role = $this->roles->first();
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

    private function hasExactRoleName(string $roleName): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', $roleName);
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
        $allPermissions = $this->getAllPermissions();

        if ($allPermissions->contains('name', $permission)) {
            return true;
        }

        if ($this->company_id === null) {
            return false;
        }

        return $allPermissions->contains('name', Permission::tenantPermissionKey($permission, $this->company_id));
    }

    private function hasSystemRole(): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn ($role) => $this->isSystemRoleName($role->name));
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', self::class)
            ->where('model_has_roles.model_id', $this->getKey())
            ->get(['roles.name'])
            ->contains(fn ($role) => $this->isSystemRoleName($role->name));
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
        $roles = $this->relationLoaded('roles')
            ? $this->roles->loadMissing('department')
            : $this->roles()->with('department')->get();

        $route = $roles
            ->map(fn ($role) => $role->department?->dashboard_route)
            ->filter()
            ->first();

        if (! $route || ! app('router')->has($route)) {
            return null;
        }

        return $route;
    }

    public function belongsToHrDepartment(): bool
    {
        return $this->departmentKeys()->intersect([
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
        return $this->roleKeys()->intersect([
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

    public function belongsToSalesDepartment(): bool
    {
        return $this->departmentKeys()->intersect([
            'sales',
            'crm',
            'business_development',
            'marketing',
            'telecalling',
        ])->isNotEmpty();
    }

    public function hasExecutiveLikeRole(): bool
    {
        return $this->roleKeys()->intersect([
            'executive',
            'sales_executive',
            'hr_executive',
            'bde',
            'business_development_executive',
            'telecaller',
        ])->isNotEmpty();
    }

    public function hasAdminLikeRole(): bool
    {
        return $this->roleKeys()->intersect([
            'super_admin',
            'admin',
            'company_admin',
            'branch_admin',
        ])->isNotEmpty();
    }

    public function isExecutiveHrmsUser(): bool
    {
        return $this->hasExecutiveLikeRole()
            && ! $this->belongsToHrDepartment()
            && ! $this->hasAdminLikeRole()
            && ! $this->isCompanyAdmin()
            && ! $this->isSuperAdmin();
    }

    public function isHrmsAttendanceOnlyUser(): bool
    {
        return $this->hasExecutiveLikeRole()
            && ! $this->belongsToHrDepartment()
            && ! $this->hasAdminLikeRole()
            && ! $this->isCompanyAdmin()
            && ! $this->isSuperAdmin();
    }

    private function roleKeys(): \Illuminate\Support\Collection
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        return $roles
            ->flatMap(function ($role) {
                return array_filter([
                    $this->normalizeDashboardKey((string) $role->name),
                    $this->normalizeDashboardKey((string) ($role->display_name ?? '')),
                ]);
            })
            ->unique()
            ->values();
    }

    private function departmentKeys(): \Illuminate\Support\Collection
    {
        $roles = $this->relationLoaded('roles')
            ? $this->roles->loadMissing('department')
            : $this->roles()->with('department')->get();

        return $roles
            ->map(fn ($role) => $this->normalizeDashboardKey((string) ($role->department?->name ?? '')))
            ->filter()
            ->unique()
            ->values();
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

    public function employeeOnboarding(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(\App\Models\EmployeeOnboarding::class, 'portal_user_id');
}

}
