<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes, BelongsToCompany;

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
                    $builder->whereIn($builder->getModel()->getTable() . '.id', $branchIds);
                }
            }
        });
    }

    public const TYPE_COCO = 'COCO';
    public const TYPE_NON_COCO = 'NON COCO';

    public const BRANCH_TYPES = [
        self::TYPE_COCO => 'COCO',
        self::TYPE_NON_COCO => 'NON COCO',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'branch_type',
        'address',
        'city',
        'state',
        'phone',
        'email',
        'latitude',
        'longitude',
        'manager_id',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public const DEFAULT_GEOFENCE_RADIUS_METERS = 50.0;

    public function getAttendanceRadiusMetersAttribute(): float
    {
        return (float) config('hrms.attendance_radius_meters', self::DEFAULT_GEOFENCE_RADIUS_METERS);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function ensureDefaultForCurrentCompany(): ?self
    {
        $companyId = auth()->user()?->company_id;

        if (! $companyId) {
            return null;
        }

        $defaultBranch = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('is_default', true)
            ->first();

        if ($defaultBranch) {
            return $defaultBranch;
        }

        $firstBranch = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        if ($firstBranch) {
            $firstBranch->update(['is_default' => true]);

            return $firstBranch;
        }

        return static::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'name' => 'Default Branch',
            'code' => 'CMP' . $companyId . '-MAIN',
            'is_active' => true,
            'is_default' => true,
        ]);
    }
}
