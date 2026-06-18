<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseKeepingWork extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'house_keeping_category_id',
        'work_name',
        'notes',
        'sort_order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(HouseKeepingCategory::class, 'house_keeping_category_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(HouseKeepingCompletion::class);
    }
}
