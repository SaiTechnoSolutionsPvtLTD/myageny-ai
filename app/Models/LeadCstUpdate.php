<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCstUpdate extends Model
{
    use HasFactory, BelongsToCompany;

    public const UPDATE_TYPES = [
        'cst_update' => 'CST Updates',
        'weekly_update' => 'Weekly Updates',
        'review' => 'Reviews',
        'escalation' => 'Escalation',
    ];

    public const UPDATE_TYPE_BADGES = [
        'cst_update' => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'border' => '#bfdbfe'],
        'weekly_update' => ['bg' => '#faf5ff', 'text' => '#7c3aed', 'border' => '#e9d5ff'],
        'review' => ['bg' => '#fff7ed', 'text' => '#c2410c', 'border' => '#fed7aa'],
        'escalation' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
    ];

    protected $fillable = [
        'company_id',
        'lead_id',
        'lead_product_id',
        'update_type',
        'notes',
        'user_id',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LeadProduct::class, 'lead_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUpdateTypeLabelAttribute(): string
    {
        return self::UPDATE_TYPES[$this->update_type] ?? ucfirst(str_replace('_', ' ', (string) $this->update_type));
    }
}
