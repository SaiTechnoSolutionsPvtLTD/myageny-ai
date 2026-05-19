<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendee_type',
        'intern_joining_form_id',
        'employee_name',
        'attendance_photo',
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
