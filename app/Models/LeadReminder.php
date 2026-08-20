<?php
// ================================================================
// FILE: app/Models/LeadReminder.php
// ================================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadReminder extends Model
{
    protected $fillable = [
        'lead_id','user_id','title','description',
        'remind_at','type','priority','is_completed','completed_at','remainder_time'
    ];

    protected $casts = [
        'remind_at'    => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
        'remainder_time' => 'datetime:H:i:s',
    ];

    const TYPES = [
        'follow_up' => 'Follow-up',
        'meeting'   => 'Meeting',
        'call'      => 'Call',
        'email'     => 'Email',
        'demo'      => 'Demo',
        'other'     => 'Other',
    ];

    const TYPE_ICONS = [
        'follow_up' => '🔔',
        'meeting'   => '📅',
        'call'      => '📞',
        'email'     => '📧',
        'demo'      => '🖥️',
        'other'     => '📌',
    ];

    public function lead() { return $this->belongsTo(Lead::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_completed && $this->remind_at && $this->remind_at->startOfDay()->lt(today());
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? '📌';
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_completed', false)->whereDate('remind_at', '<', today());
    }
}