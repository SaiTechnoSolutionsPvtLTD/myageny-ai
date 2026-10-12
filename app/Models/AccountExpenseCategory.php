<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'account_expense_categories';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'status',
        'created_by',
    ];

    public function subcategories()
    {
        return $this->hasMany(AccountExpenseSubcategory::class, 'account_expense_category_id');
    }

    public function activeSubcategories()
    {
        return $this->hasMany(AccountExpenseSubcategory::class, 'account_expense_category_id')->where('status', 'active');
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
