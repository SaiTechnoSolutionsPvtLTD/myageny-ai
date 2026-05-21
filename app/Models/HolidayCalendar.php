<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HolidayCalendar extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'holiday_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company_visibility', function (Builder $builder) {
            if (! auth()->hasUser()) {
                return;
            }

            $companyId = auth()->user()?->company_id;

            if ($companyId === null) {
                return;
            }

            $builder->where(function (Builder $query) use ($companyId) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
        });

        static::creating(function (HolidayCalendar $holiday) {
            if ($holiday->company_id === null && auth()->user()?->company_id !== null) {
                $holiday->company_id = auth()->user()->company_id;
            }
        });
    }

    public function scopeOwnedByCompany(Builder $query, ?int $companyId): Builder
    {
        return $companyId === null
            ? $query->whereNull('company_id')
            : $query->where('company_id', $companyId);
    }
}
