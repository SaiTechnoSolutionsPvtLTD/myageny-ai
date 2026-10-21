<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'company_id',
        'branch_id',
        'supplier_id',
        'po_number',
        'po_date',
        'delivery_date',
        'payment_terms',
        'delivery_terms',
        'place_of_supply',
        'supplier_name',
        'company_name',
        'supplier_email',
        'supplier_phone',
        'supplier_gstin',
        'supplier_pan',
        'supplier_address',
        'supplier_city',
        'supplier_state',
        'supplier_pincode',
        'supplier_country',
        'shipping_attention',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_pincode',
        'shipping_country',
        'subtotal',
        'total_cgst',
        'total_sgst',
        'total_igst',
        'total_tax',
        'round_off',
        'total_amount',
        'notes',
        'terms_conditions',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'po_date'       => 'date',
        'delivery_date' => 'date',
        'subtotal'      => 'decimal:2',
        'total_cgst'    => 'decimal:2',
        'total_sgst'    => 'decimal:2',
        'total_igst'    => 'decimal:2',
        'total_tax'     => 'decimal:2',
        'round_off'     => 'decimal:2',
        'total_amount'  => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id')->orderBy('sort_order', 'asc');
    }

    public function supplier()
    {
        return $this->belongsTo(SupplierMaster::class, 'supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope query to active company.
     */
    public function scopeForCompany($query, ?int $companyId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query;
    }

    /**
     * Generate the next unique PO number in format PO-yymm-xxxx or PO/2026-27/xxxx.
     */
    public static function generateNextPoNumber(?int $companyId): string
    {
        $prefix = 'PO-' . date('ym') . '-';
        $query = static::where('po_number', 'LIKE', $prefix . '%');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $count = $query->count() + 1;

        $candidate = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        while (static::where('po_number', $candidate)->exists()) {
            $count++;
            $candidate = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    /**
     * Get HTML status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'draft'     => '<span class="acm-badge acm-badge-gray" style="padding:4px 10px; font-weight:700; border-radius:6px; font-size:11px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;"><i class="bi bi-file-earmark"></i> Draft</span>',
            'issued'    => '<span class="acm-badge acm-badge-blue" style="padding:4px 10px; font-weight:700; border-radius:6px; font-size:11px; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;"><i class="bi bi-send-check"></i> Issued</span>',
            'approved'  => '<span class="acm-badge acm-badge-green" style="padding:4px 10px; font-weight:700; border-radius:6px; font-size:11px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;"><i class="bi bi-check-circle"></i> Approved</span>',
            'received'  => '<span class="acm-badge acm-badge-purple" style="padding:4px 10px; font-weight:700; border-radius:6px; font-size:11px; background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff;"><i class="bi bi-box-seam"></i> Received</span>',
            'cancelled' => '<span class="acm-badge acm-badge-red" style="padding:4px 10px; font-weight:700; border-radius:6px; font-size:11px; background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;"><i class="bi bi-x-circle"></i> Cancelled</span>',
        ];

        return $badges[strtolower((string) $this->status)] ?? '<span class="acm-badge acm-badge-gray">' . ucfirst($this->status) . '</span>';
    }

    /**
     * Convert total amount to Indian currency words format.
     */
    public function getAmountInWordsAttribute(): string
    {
        return Invoice::convertToIndianCurrencyWords((float) $this->total_amount);
    }
}
