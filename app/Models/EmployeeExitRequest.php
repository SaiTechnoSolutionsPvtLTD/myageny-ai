<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeExitRequest extends Model
{
    use HasFactory;

    public const EXIT_STATUS_PENDING = 'pending';
    public const EXIT_STATUS_APPROVED = 'approved';
    public const EXIT_STATUS_REJECTED = 'rejected';
    public const REVOKE_STATUS_NONE = 'none';
    public const REVOKE_STATUS_PENDING = 'pending';
    public const REVOKE_STATUS_APPROVED = 'approved';
    public const REVOKE_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_onboarding_id',
        'exit_reason',
        'exit_status',
        'exit_requested_at',
        'exit_actioned_by',
        'exit_actioned_at',
        'exit_action_remarks',
        'revoke_reason',
        'revoke_status',
        'revoke_requested_at',
        'revoke_actioned_by',
        'revoke_actioned_at',
        'revoke_action_remarks',
    ];

    protected function casts(): array
    {
        return [
            'exit_requested_at' => 'datetime',
            'exit_actioned_at' => 'datetime',
            'revoke_requested_at' => 'datetime',
            'revoke_actioned_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class, 'employee_onboarding_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exitActionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exit_actioned_by');
    }

    public function revokeActionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoke_actioned_by');
    }

    public function isOpenForEmployee(): bool
    {
        return $this->exit_status === self::EXIT_STATUS_PENDING
            || $this->revoke_status === self::REVOKE_STATUS_PENDING
            || ($this->exit_status === self::EXIT_STATUS_APPROVED && $this->revoke_status !== self::REVOKE_STATUS_APPROVED);
    }
}
