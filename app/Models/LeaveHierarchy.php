<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveHierarchy extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'role_id',
        'branch_ids',
        'approval_chain',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'branch_ids'     => 'array',
        'approval_chain' => 'array',
        'is_active'      => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function isAllBranches(): bool
    {
        return empty($this->branch_ids);
    }

    public function branches()
    {
        if ($this->isAllBranches()) {
            return collect();
        }

        return Branch::withoutGlobalScopes()
            ->whereIn('id', $this->branch_ids)
            ->get();
    }

    public function branchNames(): string
    {
        if ($this->isAllBranches()) {
            return 'All Branches';
        }

        $branches = $this->branches();
        if ($branches->isEmpty()) {
            return 'All Branches';
        }

        return $branches->pluck('name')->implode(', ');
    }
}
