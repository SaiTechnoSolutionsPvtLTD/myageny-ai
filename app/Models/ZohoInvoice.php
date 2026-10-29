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

    /**
     * Always resolve sub_total from column, raw_data, line_items, or total - tax_total.
     */
    public function getSubTotalAttribute($value): float
    {
        $val = (float) $value;
        if ($val > 0) {
            return $val;
        }

        $raw = (float) ($this->raw_data['sub_total'] ?? $this->raw_data['bcy_sub_total'] ?? 0);
        if ($raw > 0) {
            return $raw;
        }

        $items = $this->line_items ?? [];
        if (! empty($items) && is_array($items)) {
            $sum = 0.0;
            foreach ($items as $item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $rate = (float) ($item['rate'] ?? 0);
                $amt = (float) ($item['item_total'] ?? ($qty * $rate));
                if ($amt == 0 && $rate > 0 && $qty > 0) {
                    $amt = $qty * $rate;
                }
                $sum += $amt;
            }
            if ($sum > 0) {
                return $sum;
            }
        }

        $total = (float) ($this->attributes['total'] ?? 0);
        $tax   = (float) ($this->attributes['tax_total'] ?? 0);
        if ($total > 0) {
            return $tax > 0 ? max(0.0, round($total - $tax, 2)) : $total;
        }

        return 0.0;
    }
}
