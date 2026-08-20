<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'email',
        'mobile_number',
        'address',
        'number_of_accounts',
        'expiry_date',
        'company_status',
        'facebook_client_id',
        'facebook_client_secret',
        'super_admin_user_id',
        'show_price_request',
        'show_production_update',
        'show_approval_history',
        'show_cst_updates',
    ];

    protected function casts(): array
    {
        return [
            'number_of_accounts' => 'integer',
            'expiry_date' => 'date',
            'show_price_request' => 'boolean',
            'show_production_update' => 'boolean',
            'show_approval_history' => 'boolean',
            'show_cst_updates' => 'boolean',
        ];
    }

    public function allowsPriceRequests(): bool
    {
        return (bool) ($this->show_price_request ?? true);
    }

    public function allowsProductionUpdates(): bool
    {
        return (bool) ($this->show_production_update ?? true);
    }

    public function allowsApprovalHistory(): bool
    {
        return (bool) ($this->show_approval_history ?? true);
    }

    public function allowsCstUpdates(): bool
    {
        return (bool) ($this->show_cst_updates ?? true);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->company_status === 'active' ? 'Activate' : 'Deactivate';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function activeUsers(): HasMany
    {
        return $this->users()->where('is_active', true);
    }

    public function getUserLimitAttribute(): int
    {
        return (int) $this->number_of_accounts;
    }

    public function getExpiryStatusAttribute(): string
    {
        if (! $this->expiry_date) {
            return 'No expiry date';
        }

        return $this->isExpired() ? 'Expired' : 'Active';
    }

    public function syncExpiryState(bool $force = false): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        if (! $force && ! $this->expiry_date->endOfDay()->isPast()) {
            return false;
        }

        DB::transaction(function () {
            $this->forceFill(['company_status' => 'inactive'])->saveQuietly();

            $now = now();

            $this->users()
                ->update([
                    'is_active' => false,
                    'user_status' => 'inactive',
                    'updated_at' => $now,
                ]);
        });

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date?->endOfDay()->isPast() ?? false;
    }
}
