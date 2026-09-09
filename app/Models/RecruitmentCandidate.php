<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecruitmentCandidate extends Model
{
    use HasFactory, BelongsToCompany;

    public const STATUS_SHORTLIST = 'shortlist';
    public const STATUS_FOLLOW_UP = 'follow_up';
    public const STATUS_RNR = 'rnr';
    public const STATUS_INTERVIEW_SCHEDULED = 'interview_scheduled';
    public const STATUS_SELECTED = 'selected';
    public const STATUS_REJECTED = 'rejected';

    public const STATUS_APPLIED = 'applied';
    public const STATUS_SCREENING = 'screening';

    public const STATUSES = [
        self::STATUS_SHORTLIST => 'Shortlist',
        self::STATUS_FOLLOW_UP => 'Follow-up',
        self::STATUS_RNR => 'RNR',
        self::STATUS_INTERVIEW_SCHEDULED => 'Interview Scheduled',
        self::STATUS_SELECTED => 'Selected',
        self::STATUS_REJECTED => 'Rejected',
    ];

    protected $fillable = [
        'company_id',
        'candidate_no',
        'name',
        'mobile_number',
        'email',
        'location',
        'job_title',
        'candidate_type',
        'institute_name',
        'course_name',
        'internship_months',
        'has_stipend',
        'stipend_amount',
        'source',
        'source_details',
        'current_ctc',
        'expected_ctc',
        'notice_period',
        'experience_years',
        'previous_company',
        'previous_hr_name',
        'previous_hr_contact',
        'relieving_reason',
        'has_laptop',
        'education_details',
        'resume_path',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'status_updated_at',
    ];

    protected $casts = [
        'current_ctc' => 'decimal:2',
        'expected_ctc' => 'decimal:2',
        'stipend_amount' => 'decimal:2',
        'experience_years' => 'integer',
        'education_details' => 'array',
        'status_updated_at' => 'datetime',
    ];

    public function callUpdates(): HasMany
    {
        return $this->hasMany(RecruitmentCallUpdate::class)->latest('called_at');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(RecruitmentInterview::class)->latest('scheduled_at');
    }

    public function latestInterview(): HasOne
    {
        return $this->hasOne(RecruitmentInterview::class)->latestOfMany('scheduled_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(RecruitmentReminder::class)->orderBy('remind_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? match ($this->status) {
            self::STATUS_APPLIED => 'Applied',
            self::STATUS_SCREENING => 'Screening',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getInitialsAttribute(): string
    {
        $words = collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $word) => substr($word, 0, 1))
            ->implode('');

        return strtoupper($words ?: 'C');
    }

    public static function generateCandidateNo(): string
    {
        $prefix = 'RC-' . now()->format('y') . '-';

        $last = static::where('candidate_no', 'like', $prefix . '%')
            ->when(auth()->check() && auth()->user()?->company_id, fn ($query) => $query->where('company_id', auth()->user()->company_id))
            ->orderByDesc('id')
            ->value('candidate_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
