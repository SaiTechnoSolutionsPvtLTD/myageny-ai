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
        'lead_id',
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

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function renewals()
    {
        return $this->hasMany(DomainRenewal::class, 'domain_record_id')->orderBy('renewal_date', 'desc')->orderBy('id', 'desc');
    }

    public function latestRenewal()
    {
        return $this->hasOne(DomainRenewal::class, 'domain_record_id')->latestOfMany('renewal_date');
    }

    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at, false);
    }
}
