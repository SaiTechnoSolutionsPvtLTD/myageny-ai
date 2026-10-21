<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bank_masters';

    protected $fillable = [
        'company_id',
        'bank_name',
        'account_name',
        'account_number',
        'ifsc_code',
        'branch_name',
        'upi_id',
        'swift_code',
        'is_primary',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Scope for active banks
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for primary bank
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for company filtering
     */
    public function scopeForCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;
        return $query->when($companyId, function ($q) use ($companyId) {
            $q->where(function ($sub) use ($companyId) {
                $sub->where('company_id', $companyId)->orWhereNull('company_id');
            });
        });
    }

    /**
     * User who created the record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the record
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
