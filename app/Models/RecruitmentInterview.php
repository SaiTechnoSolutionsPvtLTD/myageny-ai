<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentInterview extends Model
{
    use HasFactory, BelongsToCompany;

    public const MODES = [
        'phone' => 'Phone',
        'video' => 'Video',
        'in_person' => 'In Person',
    ];

    public const STATUSES = [
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'company_id',
        'recruitment_candidate_id',
        'scheduled_by',
        'interviewer_id',
        'scheduled_at',
        'round',
        'mode',
        'interviewer_name',
        'interview_link',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'recruitment_candidate_id');
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function getModeLabelAttribute(): string
    {
        return self::MODES[$this->mode] ?? ucfirst(str_replace('_', ' ', (string) $this->mode));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
