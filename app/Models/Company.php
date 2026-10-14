<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeOnboarding;

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

    public function hasReachedUserLimit(): bool
    {
        return $this->user_limit > 0 && $this->activeUsers()->count() >= $this->user_limit;
    }

    public function remainingUserSlots(): int
    {
        return max(0, $this->user_limit - $this->activeUsers()->count());
    }

    public function getExpiryStatusAttribute(): string
    {
        if (! $this->expiry_date) {
            return 'No expiry date';
        }

        return $this->isExpired() ? 'Expired' : 'Active';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date ? $this->expiry_date->endOfDay()->isPast() : false;
    }

    /**
     * Activate the company, all its users, branches, and employees.
     */
    public function activateCompanyAndUsers(): void
    {
        DB::transaction(function () {
            $now = now();

            if ($this->company_status !== 'active') {
                $this->forceFill(['company_status' => 'active'])->saveQuietly();
            }

            $this->users()->update([
                'is_active' => true,
                'user_status' => 'active',
                'updated_at' => $now,
            ]);

            $this->branches()->update([
                'is_active' => true,
                'updated_at' => $now,
            ]);

            EmployeeOnboarding::where('company_id', $this->id)
                ->where('status', '!=', EmployeeOnboarding::STATUS_RESIGNED)
                ->update([
                    'status' => EmployeeOnboarding::STATUS_ACTIVE,
                    'updated_at' => $now,
                ]);
        });
    }

    /**
     * Deactivate the company, all its users, branches, and employees.
     */
    public function deactivateCompanyAndUsers(): void
    {
        DB::transaction(function () {
            $now = now();

            if ($this->company_status !== 'inactive') {
                $this->forceFill(['company_status' => 'inactive'])->saveQuietly();
            }

            $this->users()->update([
                'is_active' => false,
                'user_status' => 'inactive',
                'updated_at' => $now,
            ]);

            $this->branches()->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);

            EmployeeOnboarding::where('company_id', $this->id)
                ->where('status', '!=', EmployeeOnboarding::STATUS_RESIGNED)
                ->update([
                    'status' => EmployeeOnboarding::STATUS_INACTIVE,
                    'updated_at' => $now,
                ]);
        });
    }

    /**
     * Synchronize company and user status based on expiry date and company status.
     */
    public function syncExpiryState(bool $force = false): bool
    {
        $isExpired = $this->isExpired();
        $isInactive = $this->company_status === 'inactive';

        // If company is expired or inactive, deactivate all users
        if ($isExpired || $isInactive) {
            $hasActiveUsers = $this->users()->where(function ($q) {
                $q->where('is_active', true)->orWhere('user_status', 'active');
            })->exists();

            if ($hasActiveUsers || ($isExpired && $this->company_status !== 'inactive') || $force) {
                $this->deactivateCompanyAndUsers();
                return true;
            }

            return false;
        }

        // If company is active and not expired, but force is requested (e.g. extension)
        if (! $isExpired && $this->company_status === 'active' && $force) {
            $this->activateCompanyAndUsers();
            return true;
        }

        return false;
    }
}
