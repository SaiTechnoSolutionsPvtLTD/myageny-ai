<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use BelongsToCompany;

    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
        'department_id',
        'company_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function roleMapping(): HasOne
    {
        return $this->hasOne(RoleMapping::class);
    }

    public function roleParentMapping(): HasOne
    {
        return $this->hasOne(RoleHierarchyMapping::class, 'child_role_id');
    }

    public function childRoleMappings(): HasMany
    {
        return $this->hasMany(RoleHierarchyMapping::class, 'parent_role_id');
    }

    public static function formatRoleName(?string $name, ?string $displayName = null): string
    {
        if (!empty($displayName)) {
            return $displayName;
        }

        if (empty($name)) {
            return 'No role mapped';
        }

        $clean = preg_replace('/^company_\d+__/', '', $name);

        return ucwords(str_replace(['_', '-'], ' ', $clean));
    }

    public function getFormattedNameAttribute(): string
    {
        return static::formatRoleName($this->name, $this->display_name);
    }

    public static function tenantRoleName(string $name, ?int $companyId): string
    {
        $slug = str($name)->slug('_')->value();

        if (! $companyId) {
            return $slug;
        }

        return 'company_' . $companyId . '__' . $slug;
    }
}
