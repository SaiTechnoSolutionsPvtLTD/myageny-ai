<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityManagement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'facility_managements';
    protected $fillable = [
        'facility_title_id',
        'title',
        'entry_date',
        'entry_time',
        'office_mopping_date',
        'office_cleaning_date',
        'toilet_cleaning_date',
        'remarks',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'office_mopping_date' => 'date',
        'office_cleaning_date' => 'date',
        'toilet_cleaning_date' => 'date',
    ];

    public function facilityTitle(): BelongsTo
    {
        return $this->belongsTo(FacilityTitle::class);
    }
}
