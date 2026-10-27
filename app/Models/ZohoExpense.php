<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoExpense extends Model
{
    use HasFactory;

    protected $table = 'zoho_expenses';

    protected $fillable = [
        'company_id',
        'zoho_expense_id',
        'expense_date',
        'account_id',
        'account_name',
        'paid_through_account_id',
        'paid_through_account_name',
        'vendor_id',
        'vendor_name',
        'customer_id',
        'customer_name',
        'total',
        'tax_total',
        'currency_code',
        'status',
        'reference_number',
        'description',
        'raw_data',
        'is_migrated_to_native',
        'migrated_native_expense_id',
        'synced_at',
    ];

    protected $casts = [
        'expense_date'          => 'date',
        'total'                 => 'decimal:2',
        'tax_total'             => 'decimal:2',
        'raw_data'              => 'array',
        'is_migrated_to_native' => 'boolean',
        'synced_at'             => 'datetime',
    ];

    public function migratedExpense()
    {
        return $this->belongsTo(AccountExpense::class, 'migrated_native_expense_id');
    }
}
