<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentReminder extends Model
{
    use HasFactory, BelongsToCompany;

    public const TYPES = [
        'follow_up' => 'Follow-up',
        'interview' => 'Interview',
        'call'      => 'Call',
        'email'     => 'Email',
        'other'     => 'Other',
    ];

    public const TYPE_ICONS = [
        'follow_up' => '🔔',
        'interview' => '📅',
        'call'      => '📞',
        'email'     => '📧',
        'other'     => '📌',
    ];

    public const PRIORITIES = [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
    ];

    protected $fillable = [
        'company_id',
        'recruitment_candidate_id',
        'recruitment_call_update_id',
        'user_id',
        'title',
        'description',
        'remind_at',
        'type',
        'priority',
        'is_completed',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'remind_at'    => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'recruitment_candidate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function callUpdate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCallUpdate::class, 'recruitment_call_update_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_completed && $this->remind_at && $this->remind_at->startOfDay()->lt(today());
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? '📌';
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_completed', false)->whereDate('remind_at', '<', today());
    }

    public function scopeToday($query)
    {
        return $query->where('is_completed', false)->whereDate('remind_at', today());
    }
}
