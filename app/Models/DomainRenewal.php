<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DomainRenewal extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'domain_renewals';

    protected $fillable = [
        'company_id',
        'domain_record_id',
        'renewal_date',
        'expires_at',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'renewal_date' => 'date',
        'expires_at'   => 'date',
        'amount'       => 'decimal:2',
    ];

    public function domain()
    {
        return $this->belongsTo(DomainRecord::class, 'domain_record_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
