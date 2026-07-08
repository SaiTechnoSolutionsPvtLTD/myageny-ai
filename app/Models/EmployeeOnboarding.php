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
            if ($user && $user->isBranchAdmin() && $user->branch_id) {
                $builder->whereHas('portalUser', function ($query) use ($user) {
                    $query->where('branch_id', $user->branch_id);
                });
            }
        });
    }

    public const STATUS_ACTIVE = 'active';
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
}
