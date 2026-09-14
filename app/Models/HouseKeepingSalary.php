<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseKeepingSalary extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'house_keeping_salaries';

    protected $fillable = [
        'company_id',
        'house_keeping_employee_id',
        'salary_month',
        'base_salary',
        'working_days',
        'present_days',
        'absent_days',
        'calculated_salary',
        'bonus',
        'deductions',
        'paid_amount',
        'payment_date',
        'payment_mode',
        'payment_status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'base_salary'       => 'decimal:2',
        'present_days'      => 'decimal:1',
        'absent_days'       => 'decimal:1',
        'calculated_salary' => 'decimal:2',
        'bonus'             => 'decimal:2',
        'deductions'        => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'payment_date'      => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HouseKeepingEmployee::class, 'house_keeping_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
