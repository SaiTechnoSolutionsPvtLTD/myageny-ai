<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'supplier_masters';

    protected $fillable = [
        'company_id',
        'supplier_name',
        'company_name',
        'mobile_number',
        'email',
        'address',
        'gst_number',
        'bank_name',
        'account_name',
        'account_number',
        'ifsc_code',
        'branch_name',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Relationship: User who created the supplier record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: User who last updated the supplier record.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to only include active suppliers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by company id or global.
     */
    public function scopeForCompany($query, $companyId = null)
    {
        return $query->when($companyId, function ($q) use ($companyId) {
            $q->where(function ($sub) use ($companyId) {
                $sub->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        });
    }
}
