<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'user_name',
        'user_email',
        'lead_id',
        'lead_title',
        'module',
        'action',
        'description',
        'url',
        'method',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForLead(Builder $query, $leadId): Builder
    {
        return $query->where('lead_id', $leadId);
    }

    public function scopeForModule(Builder $query, $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeForAction(Builder $query, $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeDateBetween(Builder $query, $startDate, $endDate): Builder
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
              ->orWhere('user_name', 'like', "%{$term}%")
              ->orWhere('user_email', 'like', "%{$term}%")
              ->orWhere('lead_title', 'like', "%{$term}%")
              ->orWhere('ip_address', 'like', "%{$term}%")
              ->orWhere('url', 'like', "%{$term}%");
        });
    }

    public function getActionBadgeClass(): string
    {
        return match (strtolower($this->action)) {
            'login' => 'badge-login',
            'logout' => 'badge-logout',
            'create', 'store', 'add' => 'badge-create',
            'update', 'edit', 'change' => 'badge-update',
            'delete', 'destroy', 'remove' => 'badge-delete',
            'status_change' => 'badge-status',
            'approve', 'approval' => 'badge-approve',
            'reject' => 'badge-reject',
            'view' => 'badge-view',
            'export' => 'badge-export',
            default => 'badge-general',
        };
    }
}
