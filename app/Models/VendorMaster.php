<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendor_masters';

    protected $fillable = [
        'company_id',
        'vendor_name',
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
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
