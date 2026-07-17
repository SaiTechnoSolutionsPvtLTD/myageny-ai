<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesTarget extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['user_id', 'target_amount', 'company_id', 'target_month', 'branch_id'];

    /**
     * Target belongs to one User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Target belongs to one Branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
