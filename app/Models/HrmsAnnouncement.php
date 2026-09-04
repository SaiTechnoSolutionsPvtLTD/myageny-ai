<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmsAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_ids',
        'title',
        'message',
        'priority',
        'announcement_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'announcement_date' => 'date',
            'is_active' => 'boolean',
            'branch_ids' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isForAllBranches(): bool
    {
        return empty($this->branch_ids);
    }

    public function getTargetBranchesLabel(): string
    {
        if ($this->isForAllBranches()) {
            return 'All Branches';
        }

        $branchIds = array_map('intval', (array) $this->branch_ids);
        $branches = Branch::whereIn('id', $branchIds)->pluck('name')->all();

        return !empty($branches) ? implode(', ', $branches) : 'All Branches';
    }

    public function scopeVisibleForCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->where(function ($subQuery) use ($companyId) {
            $subQuery->whereNull('company_id');

            if ($companyId !== null) {
                $subQuery->orWhere('company_id', $companyId);
            }
        });
    }

    public function scopeVisibleForUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $companyId = $user->company_id;
        $query->visibleForCompany($companyId);

        // HR / Admin users can see all announcements to manage them
        if ($user->isSystemAdmin() || $user->isCompanyAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole()) {
            return $query;
        }

        $userBranchIds = $user->getMyBranchIds();

        return $query->where(function ($bQuery) use ($userBranchIds) {
            $bQuery->whereNull('branch_ids')
                   ->orWhere('branch_ids', '[]')
                   ->orWhereJsonLength('branch_ids', 0);

            if (!empty($userBranchIds)) {
                foreach ($userBranchIds as $bId) {
                    $bQuery->orWhereJsonContains('branch_ids', (int)$bId)
                           ->orWhereJsonContains('branch_ids', (string)$bId);
                }
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
