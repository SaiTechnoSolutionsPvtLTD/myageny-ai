<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountExpenseSubcategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'account_expense_subcategories';

    protected $fillable = [
        'company_id',
        'account_expense_category_id',
        'name',
        'code',
        'description',
        'status',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(AccountExpenseCategory::class, 'account_expense_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
