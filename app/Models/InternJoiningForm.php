<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InternJoiningForm extends Model
{
    use HasFactory;

    public const INTERN_ID_PREFIX = 'STSINT';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RESIGNED = 'resigned';

    public const DOCUMENT_FIELDS = [
        'document_10th_marksheet',
        'document_12th_marksheet',
        'document_consolidated_marksheet',
        'document_course_completion_certificate',
        'document_degree_certificate',
        'document_provisional_certificate',
        'document_tc',
        'document_aadhaar_card',
        'document_pan_card',
        'document_voter_id',
        'document_driving_licence',
        'document_experience_certificate',
        'document_salary_slips',
        'document_bank_passbook',
        'signature_path',
    ];

    protected $fillable = [
        'intern_id',
        'photograph',
        'name',
        'father_name',
        'correspondence_address',
        'permanent_address',
        'mobile',
        'email',
        'date_of_birth',
        'blood_group',
        'marital_status',
        'date_of_marriage',
        'aadhaar_card_no',
        'pan_card_no',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_no',
        'internship_start_date',
        'internship_duration_months',
        'internship_end_date',
        'internship_status',
        'role_id',
        'department_id',
        'portal_user_id',
        'declaration_accepted',
        'declaration_date',
        'declaration_place',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_marriage' => 'date',
            'internship_start_date' => 'date',
            'internship_end_date' => 'date',
            'internship_duration_months' => 'integer',
            'declaration_date' => 'date',
            'declaration_accepted' => 'boolean',
        ];
    }

    public function educationalDetails(): HasMany
    {
        return $this->hasMany(InternEducationalDetail::class)->orderBy('sort_order');
    }

    public function employmentDetails(): HasMany
    {
        return $this->hasMany(InternEmploymentDetail::class)->orderBy('sort_order');
    }

    public function familyDetails(): HasMany
    {
        return $this->hasMany(InternFamilyDetail::class)->orderBy('sort_order');
    }

    public function documents(): HasOne
    {
        return $this->hasOne(InternDocument::class);
    }

    public function convertedEmployee(): HasOne
    {
        return $this->hasOne(EmployeeOnboarding::class, 'source_intern_joining_form_id');
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('internship_status', self::STATUS_ACTIVE);
    }
}
