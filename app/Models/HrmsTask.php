<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmsTask extends Model
{
    protected $table = 'hrms_tasks';

    protected $fillable = [
        'company_id',
        'user_id',
        'created_by',
        'task_date',
        'task_time',
        'remarks',
        'status',
        'mail_sent',
    ];

    protected $casts = [
        'task_date' => 'date',
        'mail_sent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
