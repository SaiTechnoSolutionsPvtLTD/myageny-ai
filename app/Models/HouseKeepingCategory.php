<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseKeepingCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'sort_order',
    ];

    public function works(): HasMany
    {
        return $this->hasMany(HouseKeepingWork::class)->orderBy('sort_order')->orderBy('id');
    }
}
