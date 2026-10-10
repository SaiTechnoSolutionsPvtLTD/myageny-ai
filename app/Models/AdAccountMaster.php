<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdAccountMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ad_account_masters';

    protected $fillable = [
        'company_id',
        'branch_id',
        'account_name',
        'account_id',
        'platform',
        'status',
        'notes',
        'created_by',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function budgetRequests()
    {
        return $this->hasMany(AdBudgetRequest::class, 'ad_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
