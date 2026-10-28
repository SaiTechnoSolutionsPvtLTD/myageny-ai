<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoInvoice extends Model
{
    use HasFactory;

    protected $table = 'zoho_invoices';

    protected $fillable = [
        'company_id',
        'zoho_invoice_id',
        'invoice_number',
        'customer_id',
        'customer_name',
        'company_name',
        'invoice_date',
        'due_date',
        'status',
        'currency_code',
        'currency_symbol',
        'exchange_rate',
        'sub_total',
        'tax_total',
        'total',
        'balance',
        'payment_terms',
        'reference_number',
        'billing_address',
        'shipping_address',
        'line_items',
        'raw_data',
        'is_migrated_to_native',
        'migrated_native_invoice_id',
        'synced_at',
    ];

    protected $casts = [
        'invoice_date'          => 'date',
        'due_date'              => 'date',
        'exchange_rate'         => 'decimal:4',
        'sub_total'             => 'decimal:2',
        'tax_total'             => 'decimal:2',
        'total'                 => 'decimal:2',
        'balance'               => 'decimal:2',
        'billing_address'       => 'array',
        'shipping_address'      => 'array',
        'line_items'            => 'array',
        'raw_data'              => 'array',
        'is_migrated_to_native' => 'boolean',
        'synced_at'             => 'datetime',
    ];

    public function migratedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'migrated_native_invoice_id');
    }
}
