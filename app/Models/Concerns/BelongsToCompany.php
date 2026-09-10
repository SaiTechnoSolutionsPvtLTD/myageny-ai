<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (! auth()->hasUser()) {
                return;
            }

            $user = auth()->user();

            if ($user?->company_id) {
                $table = $builder->getModel()->getTable();
                $builder->where(function (Builder $query) use ($table, $user) {
                    $query->where($table . '.company_id', $user->company_id)
                        ->orWhereNull($table . '.company_id');
                });
            }
        });

        static::creating(function ($model) {
            if (! auth()->hasUser()) {
                return;
            }

            if (! isset($model->company_id) || $model->company_id === null) {
                $companyId = auth()->user()?->company_id;

                if ($companyId) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
