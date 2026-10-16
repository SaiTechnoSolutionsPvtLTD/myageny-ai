<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'credit_notes';

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'credit_note_number',
        'credit_note_date',
        'reason',
        'subtotal',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_tax',
        'total_amount',
        'status',
        'notes',
        'attachment_path',
        'attachment_name',
        'created_by',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'subtotal'         => 'decimal:2',
        'cgst_amount'      => 'decimal:2',
        'sgst_amount'      => 'decimal:2',
        'igst_amount'      => 'decimal:2',
        'total_tax'        => 'decimal:2',
        'total_amount'     => 'decimal:2',
    ];

    const REASONS = [
        'Order Cancellation'        => 'Order / Project Cancellation',
        'Discount / Price Revision' => 'Post-Invoice Discount / Price Revision',
        'Deficiency in Service'     => 'Deficiency in Service / Quality Issue',
        'Overbilling'               => 'Overbilling / Calculation Error',
        'Change in Scope'           => 'Change in Scope / Deliverables Reduced',
        'Other'                     => 'Other Reason',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(CreditNoteItem::class, 'credit_note_id');
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query;
    }

    /**
     * Generate next Credit Note number in format CN-yymm-xxxx.
     */
    public static function generateNextNumber(?int $companyId): string
    {
        $prefix = 'CN-' . date('ym') . '-';
        $query = static::where('credit_note_number', 'LIKE', $prefix . '%');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $count = $query->count() + 1;

        $candidate = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        while (static::where('credit_note_number', $candidate)->exists()) {
            $count++;
            $candidate = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->attachment_path && file_exists(public_path($this->attachment_path))) {
            return asset($this->attachment_path);
        }
        return null;
    }
}
