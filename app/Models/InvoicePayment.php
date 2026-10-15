<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoice_payments';

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'payment_number',
        'payment_date',
        'amount_received',
        'is_tds_deducted',
        'tds_percentage',
        'tds_amount',
        'total_settled_amount',
        'payment_mode',
        'reference_number',
        'bank_name',
        'notes',
        'attachment_path',
        'attachment_name',
        'recorded_by',
    ];

    protected $casts = [
        'payment_date'         => 'date',
        'amount_received'      => 'decimal:2',
        'is_tds_deducted'      => 'boolean',
        'tds_percentage'       => 'decimal:2',
        'tds_amount'           => 'decimal:2',
        'total_settled_amount' => 'decimal:2',
    ];

    const PAYMENT_MODES = [
        'bank_transfer' => 'Bank Transfer (NEFT/RTGS/IMPS)',
        'upi'           => 'UPI Payment',
        'cheque'        => 'Cheque / DD',
        'cash'          => 'Cash',
        'card'          => 'Credit / Debit Card',
        'other'         => 'Other',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getPaymentModeLabelAttribute(): string
    {
        return self::PAYMENT_MODES[$this->payment_mode] ?? ucwords(str_replace('_', ' ', (string) $this->payment_mode));
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->attachment_path && file_exists(public_path($this->attachment_path))) {
            return asset($this->attachment_path);
        }
        return null;
    }
}
