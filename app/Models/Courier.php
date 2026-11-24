<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'courier_code',
        'courier_form_id',
        'form_title',
        'created_by',
        'updated_by',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(CourierForm::class, 'courier_form_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CourierValue::class, 'courier_id')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
