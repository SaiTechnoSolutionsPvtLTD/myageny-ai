<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyAttendance extends Model
{
    use HasFactory, BelongsToCompany;

    protected static function booted(): void
    {
        static::addGlobalScope('branch', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (! auth()->hasUser()) {
                return;
            }

            $user = auth()->user();
            if ($user && $user->isBranchAdmin()) {
                $branchIds = $user->getMyBranchIds();
                if (!empty($branchIds)) {
                    $builder->where(function ($query) use ($branchIds) {
                        $query->whereHas('employee.portalUser', function ($q) use ($branchIds) {
                            $q->whereIn('branch_id', $branchIds);
                        })->orWhereHas('intern.portalUser', function ($q) use ($branchIds) {
                            $q->whereIn('branch_id', $branchIds);
                        });
                    });
                }
            }
        });
    }

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendee_type',
        'intern_joining_form_id',
        'employee_name',
        'attendance_photo',
        'logout_photo',
        'login_location',
        'login_latitude',
        'login_longitude',
        'login_time',
        'logout_location',
        'logout_latitude',
        'logout_longitude',
        'logout_time',
        'overall_working_hours',
        'attendance_date',
        'attendance_status',
        'leave_category',
        'leave_session',
        'remarks',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'intern_joining_form_id' => 'integer',
        'login_latitude' => 'float',
        'login_longitude' => 'float',
        'logout_latitude' => 'float',
        'logout_longitude' => 'float',
        'attendance_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class, 'employee_id');
    }

    public function intern(): BelongsTo
    {
        return $this->belongsTo(InternJoiningForm::class, 'intern_joining_form_id');
    }
}
