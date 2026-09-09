<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentCallUpdate extends Model
{
    use HasFactory, BelongsToCompany;

    public const CALL_TYPES = [
        'outgoing' => 'Outgoing',
        'incoming' => 'Incoming',
        'missed' => 'Missed',
    ];

    public const OUTCOMES = [
        'screening' => 'Screening Done',
        'interested' => 'Interested',
        'not_interested' => 'Not Interested',
        'interview_planned' => 'Interview Planned',
        'no_answer' => 'No Answer',
        'follow_up' => 'Follow-up Needed',
        'selected' => 'Selected',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'company_id',
        'recruitment_candidate_id',
        'user_id',
        'called_at',
        'call_type',
        'duration_minutes',
        'outcome',
        'notes',
        'next_follow_up_at',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'recruitment_candidate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reminder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RecruitmentReminder::class, 'recruitment_call_update_id');
    }

    public function getOutcomeLabelAttribute(): string
    {
        return self::OUTCOMES[$this->outcome] ?? ucfirst(str_replace('_', ' ', (string) $this->outcome));
    }

    public function getCallTypeLabelAttribute(): string
    {
        return self::CALL_TYPES[$this->call_type] ?? ucfirst((string) $this->call_type);
    }
}
