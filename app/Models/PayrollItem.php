<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'employee_onboarding_id',
        'employee_code',
        'employee_name',
        'designation',
        'date_of_joining',
        'uan_no',
        'esi_no',
        'working_days',
        'days_attended',
        'leave_days',
        'lop_days',
        'payable_days',
        'use_pf',
        'use_esi',
        'pf_employee_percentage',
        'pf_employer_percentage',
        'esi_employee_percentage',
        'esi_employer_percentage',
        'esi_salary_limit',
        'gross_salary',
        'basic_salary',
        'hra',
        'travel_allowance',
        'other_allowance',
        'earned_basic_salary',
        'earned_hra',
        'earned_travel_allowance',
        'earned_other_allowance',
        'earned_gross_salary',
        'pf_employee_contribution',
        'pf_employer_contribution',
        'esi_employee_contribution',
        'esi_employer_contribution',
        'professional_tax',
        'tds_amount',
        'loan_deduction',
        'other_deduction',
        'total_deductions',
        'net_salary',
    ];

    protected function casts(): array
    {
        return [
            'date_of_joining' => 'date',
            'use_pf' => 'boolean',
            'use_esi' => 'boolean',
            'pf_employee_percentage' => 'decimal:2',
            'pf_employer_percentage' => 'decimal:2',
            'esi_employee_percentage' => 'decimal:2',
            'esi_employer_percentage' => 'decimal:2',
            'esi_salary_limit' => 'decimal:2',
            'days_attended' => 'decimal:2',
            'leave_days' => 'decimal:2',
            'lop_days' => 'decimal:2',
            'payable_days' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'basic_salary' => 'decimal:2',
            'hra' => 'decimal:2',
            'travel_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'earned_basic_salary' => 'decimal:2',
            'earned_hra' => 'decimal:2',
            'earned_travel_allowance' => 'decimal:2',
            'earned_other_allowance' => 'decimal:2',
            'earned_gross_salary' => 'decimal:2',
            'pf_employee_contribution' => 'decimal:2',
            'pf_employer_contribution' => 'decimal:2',
            'esi_employee_contribution' => 'decimal:2',
            'esi_employer_contribution' => 'decimal:2',
            'professional_tax' => 'decimal:2',
            'tds_amount' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class, 'employee_onboarding_id');
    }
}
