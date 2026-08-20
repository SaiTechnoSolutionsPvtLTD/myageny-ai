<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutsideOfficeAttendanceRequest extends Model
{
    use HasFactory, BelongsToCompany;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_CHECKIN  = 'checkin';
    public const TYPE_CHECKOUT = 'checkout';

    // Same branch-visibility scope as DailyAttendance, so a Branch Admin's
    // outside-office queue only ever shows their own branch's employees —
    // matches DailyAttendance::booted() exactly, kept in sync deliberately
    // rather than shared, since the two models scope different tables.
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
        'request_type',
        'daily_attendance_id',
        'requested_at',
        'attendance_date',
        'photo',
        'latitude',
        'longitude',
        'location',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_remarks',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'intern_joining_form_id' => 'integer',
        'daily_attendance_id' => 'integer',
        'requested_at' => 'datetime',
        'attendance_date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class, 'employee_id');
    }

    public function intern(): BelongsTo
    {
        return $this->belongsTo(InternJoiningForm::class, 'intern_joining_form_id');
    }

    public function dailyAttendance(): BelongsTo
    {
        return $this->belongsTo(DailyAttendance::class, 'daily_attendance_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
