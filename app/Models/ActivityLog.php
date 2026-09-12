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
            'create', 'store', 'add', 'created', 'payment_added', 'payment_created', 'call_update_created' => 'badge-create',
            'update', 'edit', 'change', 'updated', 'call_update_updated', 'payment_updated' => 'badge-update',
            'delete', 'destroy', 'remove', 'deleted', 'call_update_deleted', 'payment_deleted' => 'badge-delete',
            'status_change' => 'badge-status',
            'approve', 'approval', 'approved' => 'badge-approve',
            'reject', 'rejected' => 'badge-reject',
            'view', 'viewed', 'viewed_details' => 'badge-view',
            'export', 'exported' => 'badge-export',
            default => 'badge-general',
        };
    }
}
