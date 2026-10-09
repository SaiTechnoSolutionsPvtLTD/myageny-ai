<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DomainRecord extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'domain_records';

    protected $fillable = [
        'company_id',
        'domain_name',
        'registrar',
        'status',
        'expires_at',
        'auto_renew',
        'privacy',
        'client_name',
        'notes',
        'godaddy_domain_id',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'date',
        'auto_renew' => 'boolean',
        'privacy' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at, false);
    }
}
