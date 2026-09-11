<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmmSheetLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'smm_sheet_id',
        'user_id',
        'department',
        'action',
        'posters_added',
        'videos_added',
        'design_posters_before',
        'design_posters_after',
        'design_videos_before',
        'design_videos_after',
        'dm_posters_before',
        'dm_posters_after',
        'dm_videos_before',
        'dm_videos_after',
        'remarks',
    ];

    protected $casts = [
        'posters_added'         => 'integer',
        'videos_added'          => 'integer',
        'design_posters_before' => 'integer',
        'design_posters_after'  => 'integer',
        'design_videos_before'  => 'integer',
        'design_videos_after'   => 'integer',
        'dm_posters_before'     => 'integer',
        'dm_posters_after'      => 'integer',
        'dm_videos_before'      => 'integer',
        'dm_videos_after'       => 'integer',
    ];

    public function smmSheet(): BelongsTo
    {
        return $this->belongsTo(SmmSheet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
