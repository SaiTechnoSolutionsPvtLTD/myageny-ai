<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimesheet extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'production_initiation_id',
        'user_id',
        'timesheet_date',
        'project_delivery_date',
        'poster_count',
        'video_count',
        'committed_posters',
        'committed_videos',
        'waiting_posters',
        'waiting_videos',
        'day_closing_update',
    ];

    protected $casts = [
        'timesheet_date' => 'date',
        'project_delivery_date' => 'date',
        'poster_count' => 'integer',
        'video_count' => 'integer',
        'committed_posters' => 'integer',
        'committed_videos' => 'integer',
        'waiting_posters' => 'integer',
        'waiting_videos' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProductionInitiation::class, 'production_initiation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
