<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNoteItem extends Model
{
    use HasFactory;

    protected $table = 'credit_note_items';

    protected $fillable = [
        'credit_note_id',
        'invoice_item_id',
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

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }
}
