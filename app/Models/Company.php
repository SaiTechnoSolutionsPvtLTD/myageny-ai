<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'email',
        'mobile_number',
        'address',
        'number_of_accounts',
        'company_status',
        'facebook_client_id',
        'facebook_client_secret',
        'super_admin_user_id',
    ];

    protected function casts(): array
    {
        return [
            'number_of_accounts' => 'integer',
        ];
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
}
