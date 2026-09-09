<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class EmployeeOnboarding extends Model
{
    use HasFactory, BelongsToCompany;

    protected static function booted(): void
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            if (! auth()->hasUser()) {
                return;
            }

            $user = auth()->user();
            if ($user && $user->isBranchAdmin()) {
                $branchIds = $user->getMyBranchIds();
                if (!empty($branchIds)) {
                    $builder->whereHas('portalUser', function ($query) use ($branchIds) {
                        $query->whereIn('branch_id', $branchIds);
                    });
                }
            }
        });
    }

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_RESIGNED = 'resigned';

    public const DOCUMENT_FIELDS = [
        'photograph',
        'signature',
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
    ];

    protected $fillable = [
        'employee_id',
        'source_intern_joining_form_id',
        'role_id',
        'department_id',
        'portal_user_id',
        'company_id',
        'name',
        'father_name',
        'correspondence_address',
        'permanent_address',
        'mobile',
        'email',
        'date_of_birth',
        'joining_date',
        'blood_group',
        'marital_status',
        'date_of_marriage',
        'aadhaar_card_no',
        'pan_card_no',
        'photograph',
        'emergency_contact_name',
        'emergency_relation',
        'emergency_contact_no',
        'reference_name',
        'reference_organization_name',
        'reference_designation',
        'reference_contact_no',
        'reference_mail_id',
        'bank_name',
        'bank_account_name',
        'bank_account_no',
        'bank_ifsc_code',
        'bank_branch',
        'salary_effective_from',
        'gross_salary',
        'basic_salary',
        'hra',
        'special_allowance',
        'other_allowance',
        'esi_enabled',
        'esi_no',
        'esi_employee_contribution',
        'esi_employer_contribution',
        'pf_enabled',
        'uan_no',
        'pf_account_no',
        'pf_employee_contribution',
        'pf_employer_contribution',
        'professional_tax',
        'tds_amount',
        'loan_deduction',
        'other_deduction',
        'total_deduction',
        'net_salary',
        'salary_payment_mode',
        'deduction_notes',
        'declaration_date',
        'declaration_place',
        'signature',
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
        'status',
        'employee_type',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'date_of_marriage' => 'date',
            'salary_effective_from' => 'date',
            'gross_salary' => 'decimal:2',
            'basic_salary' => 'decimal:2',
            'hra' => 'decimal:2',
            'special_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'esi_enabled' => 'boolean',
            'esi_employee_contribution' => 'decimal:2',
            'esi_employer_contribution' => 'decimal:2',
            'pf_enabled' => 'boolean',
            'pf_employee_contribution' => 'decimal:2',
            'pf_employer_contribution' => 'decimal:2',
            'professional_tax' => 'decimal:2',
            'tds_amount' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'declaration_date' => 'date',
        ];
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class)->orderBy('sort_order');
    }

    public function employments(): HasMany
    {
        return $this->hasMany(EmployeeEmployment::class)->orderBy('sort_order');
    }

    public function familyDetails(): HasMany
    {
        return $this->hasMany(EmployeeFamilyDetail::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sourceIntern(): BelongsTo
    {
        return $this->belongsTo(InternJoiningForm::class, 'source_intern_joining_form_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getBranchAttribute(): ?Branch
    {
        if ($this->relationLoaded('portalUser') && $this->portalUser?->relationLoaded('branch')) {
            if ($this->portalUser->branch) {
                return $this->portalUser->branch;
            }
        } elseif ($this->portalUser?->branch) {
            return $this->portalUser->branch;
        }

        if ($this->employee_id) {
            $code = preg_replace('/[0-9]+$/', '', $this->employee_id);
            if ($code) {
                return Branch::withoutGlobalScopes()->where('code', $code)->first();
            }
        }

        return null;
    }

    public function getBranchNameAttribute(): string
    {
        return $this->branch?->name ?? '—';
    }

    public function getFileUrl(?string $field = 'photograph'): ?string
    {
        $path = $field ? ($this->{$field} ?? null) : null;
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public function getProfileCompletionPercentageAttribute(): int
    {
        $checks = [
            !empty($this->name),
            !empty($this->mobile),
            !empty($this->email),
            !empty($this->date_of_birth),
            !empty($this->joining_date),
            !empty($this->father_name),
            !empty($this->correspondence_address) || !empty($this->permanent_address),
            !empty($this->department_id),
            !empty($this->role_id),
            !empty($this->portal_user_id),
            !empty($this->emergency_contact_name) && !empty($this->emergency_contact_no),
            !empty($this->bank_account_no) && !empty($this->bank_ifsc_code),
            !empty($this->aadhaar_card_no),
            !empty($this->pan_card_no),
            (float) $this->gross_salary > 0,
            $this->relationLoaded('educations') ? $this->educations->isNotEmpty() : $this->educations()->exists(),
            $this->relationLoaded('familyDetails') ? $this->familyDetails->isNotEmpty() : $this->familyDetails()->exists(),
            !empty($this->photograph),
            !empty($this->signature) || !empty($this->document_aadhaar_card) || !empty($this->document_pan_card) || !empty($this->document_10th_marksheet),
            !empty($this->declaration_date) || !empty($this->salary_effective_from) || !empty($this->blood_group),
        ];

        $filledCount = count(array_filter($checks));
        $totalChecks = count($checks);

        return (int) round(($filledCount / $totalChecks) * 100);
    }
}
