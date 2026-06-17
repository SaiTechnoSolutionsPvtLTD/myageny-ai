<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'dashboard_route',
    ];

    public static function dashboardRouteOptions(): array
    {
        return [
            'hrms.dashboard' => 'HR Dashboard',
            'dashboard.admin' => 'CRM / Admin Dashboard',
        ];
    }

    public function getDashboardRouteLabelAttribute(): string
    {
        return static::dashboardRouteOptions()[$this->dashboard_route] ?? 'Default User Dashboard';
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function employeeOnboardings(): HasMany
    {
        return $this->hasMany(EmployeeOnboarding::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps()->orderBy('package_name');
    }
}
