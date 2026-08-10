<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseKeepingAttendance extends Model
{
    use HasFactory;

    protected $table = 'house_keeping_attendances';

    protected $fillable = [
        'company_id',
        'house_keeping_employee_id',
        'attendance_date',
        'login_time',
        'logout_time',
        'status',
        'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HouseKeepingEmployee::class, 'house_keeping_employee_id');
    }
}
