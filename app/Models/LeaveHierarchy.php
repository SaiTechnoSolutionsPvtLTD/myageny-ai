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
        'approval_chain',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'approval_chain' => 'array',
        'is_active'      => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
