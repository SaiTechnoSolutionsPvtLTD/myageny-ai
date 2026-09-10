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
                    $branchCodes = Branch::withoutGlobalScopes()->whereIn('id', $branchIds)->pluck('code')->filter()->all();

                    $builder->where(function ($query) use ($branchIds, $branchCodes) {
                        $query->where(function ($eqQuery) use ($branchIds, $branchCodes) {
                            $eqQuery->where('attendee_type', 'employee')
                                ->whereHas('employee', function ($eq) use ($branchIds, $branchCodes) {
                                    $eq->where(function ($q) use ($branchIds, $branchCodes) {
                                        $q->whereHas('portalUser', function ($puQ) use ($branchIds) {
                                            $puQ->whereIn('branch_id', $branchIds);
                                        });
                                        foreach ($branchCodes as $code) {
                                            $q->orWhere('employee_id', 'like', $code . '%');
                                        }
                                    });
                                });
                        })->orWhere(function ($iqQuery) use ($branchIds, $branchCodes) {
                            $iqQuery->where('attendee_type', 'intern')
                                ->whereHas('intern', function ($iq) use ($branchIds, $branchCodes) {
                                    $iq->where(function ($q) use ($branchIds, $branchCodes) {
                                        $q->whereHas('portalUser', function ($puQ) use ($branchIds) {
                                            $puQ->whereIn('branch_id', $branchIds);
                                        });
                                        foreach ($branchCodes as $code) {
                                            $q->orWhere('intern_id', 'like', $code . '%');
                                        }
                                    });
                                });
                        })->orWhere(function ($uqQuery) use ($branchIds) {
                            $uqQuery->where('attendee_type', 'user')
                                ->whereHas('user', function ($uq) use ($branchIds) {
                                    $uq->whereIn('branch_id', $branchIds);
                                });
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
        'is_outside_office_checkin',
        'outside_office_checkin_reason',
        'is_outside_office_checkout',
        'outside_office_checkout_reason',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'intern_joining_form_id' => 'integer',
        'login_latitude' => 'float',
        'login_longitude' => 'float',
        'logout_latitude' => 'float',
        'logout_longitude' => 'float',
        'attendance_date' => 'date',
        'is_outside_office_checkin' => 'boolean',
        'is_outside_office_checkout' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class, 'employee_id');
    }

    public function intern(): BelongsTo
    {
        return $this->belongsTo(InternJoiningForm::class, 'intern_joining_form_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
