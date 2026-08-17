<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseKeepingEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'house_keeping_employees';

    protected $fillable = [
        'company_id',
        'name',
        'mobile_number',
        'address',
        'salary',
        'status',
        'remarks',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(HouseKeepingAttendance::class, 'house_keeping_employee_id');
    }
}
