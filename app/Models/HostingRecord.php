<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingRecord extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'hosting_records';

    protected $fillable = [
        'company_id',
        'hosting_name',
        'provider',
        'ip_address',
        'plan_type',
        'status',
        'renewal_date',
        'renewal_amount',
        'client_name',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'renewal_date' => 'date',
        'renewal_amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDaysUntilRenewalAttribute(): ?int
    {
        if (! $this->renewal_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->renewal_date, false);
    }
}
