<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'pf_employee_percentage',
        'pf_employer_percentage',
        'pf_gross_threshold',
        'pf_basic_cap',
        'esi_employee_percentage',
        'esi_employer_percentage',
        'esi_salary_limit',
        'paid_leave_days',
        'permission_days_per_month',
        'permission_hours_per_day',
        'grace_login_time',
    ];

    protected function casts(): array
    {
        return [
            'pf_employee_percentage' => 'decimal:2',
            'pf_employer_percentage' => 'decimal:2',
            'pf_gross_threshold' => 'decimal:2',
            'pf_basic_cap' => 'decimal:2',
            'esi_employee_percentage' => 'decimal:2',
            'esi_employer_percentage' => 'decimal:2',
            'esi_salary_limit' => 'decimal:2',
            'paid_leave_days' => 'decimal:2',
            'permission_days_per_month' => 'integer',
            'permission_hours_per_day' => 'decimal:2',
        ];
    }

    public static function defaults(): array
    {
        return [
            'pf_employee_percentage' => 12.00,
            'pf_employer_percentage' => 12.00,
            'pf_gross_threshold' => 21000.00,
            'pf_basic_cap' => 15000.00,
            'esi_employee_percentage' => 4.00,
            'esi_employer_percentage' => 0.00,
            'esi_salary_limit' => 21000.00,
            'paid_leave_days' => 0.00,
            'permission_days_per_month' => 0,
            'permission_hours_per_day' => 0.00,
            'grace_login_time' => '09:30:00',
        ];
    }

    public static function forCompany(?int $companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            static::defaults()
        );
    }
}
