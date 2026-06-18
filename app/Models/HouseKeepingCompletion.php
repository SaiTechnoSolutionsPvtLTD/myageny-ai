<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseKeepingCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_keeping_work_id',
        'completed_date',
        'completed_by',
    ];

    protected $casts = [
        'completed_date' => 'date',
    ];

    public function work(): BelongsTo
    {
        return $this->belongsTo(HouseKeepingWork::class, 'house_keeping_work_id');
    }
}
