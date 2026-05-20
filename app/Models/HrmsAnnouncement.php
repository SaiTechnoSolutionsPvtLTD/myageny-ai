<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HrmsAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'title',
        'message',
        'priority',
        'announcement_date',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'announcement_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeVisibleForCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->where(function ($subQuery) use ($companyId) {
            $subQuery->whereNull('company_id');

            if ($companyId !== null) {
                $subQuery->orWhere('company_id', $companyId);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
