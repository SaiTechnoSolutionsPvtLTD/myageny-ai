<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionRequest extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_id',
        'permission_date',
        'from_time',
        'to_time',
        'total_minutes',
        'reason',
        'status',
        'current_step',
        'submitted_at',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'permission_date' => 'date',
            'total_minutes' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PermissionApproval::class)->orderBy('step_order');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
