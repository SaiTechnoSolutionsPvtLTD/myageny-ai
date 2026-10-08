<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdSpend extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ad_spends';

    protected $fillable = [
        'company_id',
        'branch_id',
        'spend_date',
        'ad_account_id',
        'amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'spend_date' => 'date',
        'amount'     => 'float',
    ];

    public function adAccount()
    {
        return $this->belongsTo(AdAccountMaster::class, 'ad_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
