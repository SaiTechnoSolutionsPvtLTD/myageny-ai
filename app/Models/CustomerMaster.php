<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_masters';

    protected $fillable = [
        'company_id',
        'lead_id',
        'customer_name',
        'company_name',
        'mobile_number',
        'email',
        'pan_number',
        'gst_number',
        'payment_terms',
        'billing_attention',
        'billing_country',
        'billing_address_street_1',
        'billing_address_street_2',
        'billing_city',
        'billing_state',
        'billing_pin_code',
        'shipping_attention',
        'shipping_country',
        'shipping_address_street_1',
        'shipping_address_street_2',
        'shipping_city',
        'shipping_state',
        'shipping_pin_code',
        'remarks',
        'status',
        'created_by',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getFullBillingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->billing_attention ? 'Attn: ' . $this->billing_attention : null,
            $this->billing_address_street_1,
            $this->billing_address_street_2,
            $this->billing_city,
            $this->billing_state,
            $this->billing_pin_code,
            $this->billing_country,
        ]);

        return implode(', ', $parts);
    }

    public function getFullShippingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->shipping_attention ? 'Attn: ' . $this->shipping_attention : null,
            $this->shipping_address_street_1,
            $this->shipping_address_street_2,
            $this->shipping_city,
            $this->shipping_state,
            $this->shipping_pin_code,
            $this->shipping_country,
        ]);

        return implode(', ', $parts);
    }
}
