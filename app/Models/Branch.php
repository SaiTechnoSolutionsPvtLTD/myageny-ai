<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'city',
        'state',
        'phone',
        'email',
        'manager_id',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
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
