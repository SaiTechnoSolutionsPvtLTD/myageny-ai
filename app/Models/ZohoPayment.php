<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoPayment extends Model
{
    use HasFactory;

    protected $table = 'zoho_payments';

    protected $fillable = [
        'company_id',
        'zoho_payment_id',
        'payment_number',
        'customer_id',
        'customer_name',
        'payment_date',
        'payment_mode',
        'amount',
        'unused_amount',
        'reference_number',
        'bank_account_id',
        'bank_account_name',
        'description',
        'status',
        'invoices_applied',
        'raw_data',
        'is_migrated_to_native',
        'migrated_native_payment_id',
        'synced_at',
    ];

    protected $casts = [
        'payment_date'          => 'date',
        'amount'                => 'decimal:2',
        'unused_amount'         => 'decimal:2',
        'invoices_applied'      => 'array',
        'raw_data'              => 'array',
        'is_migrated_to_native' => 'boolean',
        'synced_at'             => 'datetime',
    ];

    public function migratedPayment()
    {
        return $this->belongsTo(InvoicePayment::class, 'migrated_native_payment_id');
    }
}
