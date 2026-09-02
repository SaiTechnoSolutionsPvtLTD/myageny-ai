<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_initiation_id',
        'type',
        'content',
        'created_by',
        'created_at',
        'updated_at',
    ];

    public function productionInitiation(): BelongsTo
    {
        return $this->belongsTo(ProductionInitiation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
