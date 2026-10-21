<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'item_name',
        'description',
        'hsn_sac_code',
        'qty',
        'rate',
        'amount',
        'cgst_percent',
        'cgst_amount',
        'sgst_percent',
        'sgst_amount',
        'igst_percent',
        'igst_amount',
        'total_amount',
        'sort_order',
    ];

    protected $casts = [
        'qty'          => 'decimal:2',
        'rate'         => 'decimal:2',
        'amount'       => 'decimal:2',
        'cgst_percent' => 'decimal:2',
        'cgst_amount'  => 'decimal:2',
        'sgst_percent' => 'decimal:2',
        'sgst_amount'  => 'decimal:2',
        'igst_percent' => 'decimal:2',
        'igst_amount'  => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function itemMaster()
    {
        return $this->belongsTo(ItemMaster::class, 'item_id');
    }
}
