<?php
// ================================================================
// FILE: app/Models/LeadProductPayment.php
// ================================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadProductPayment extends Model
{
    protected $fillable = [
        'lead_product_id',
        'lead_id',
        'recorded_by',
        'amount',
        'payment_mode',
        'payment_date',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    const PAYMENT_MODE_ICONS = [
        'cash'          => '💵',
        'bank_transfer' => '🏦',
        'cheque'        => '📝',
        'upi'           => '📱',
        'card'          => '💳',
    ];

    const PAYMENT_MODE_COLORS = [
        'cash'          => '#16a34a',
        'bank_transfer' => '#2563eb',
        'cheque'        => '#7c3aed',
        'upi'           => '#ea580c',
        'card'          => '#0284c7',
    ];

    public function product()    { return $this->belongsTo(LeadProduct::class, 'lead_product_id'); }
    public function lead()       { return $this->belongsTo(Lead::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    public function getFormattedAmountAttribute(): string
    {
        return '₹' . number_format($this->amount, 2);
    }

    public function getModeLabelAttribute(): string
    {
        return LeadProduct::PAYMENT_MODES[$this->payment_mode] ?? ucfirst($this->payment_mode);
    }

    public function getModeIconAttribute(): string
    {
        return self::PAYMENT_MODE_ICONS[$this->payment_mode] ?? '💰';
    }

    public function getModeColorAttribute(): string
    {
        return self::PAYMENT_MODE_COLORS[$this->payment_mode] ?? '#7c7c7c';
    }

    public function leadProduct()
    {
        return $this->belongsTo(LeadProduct::class);
    }

    public function toJsPayload(): array
{
    return [
        'id'                 => $this->id,
        'amount'             => (float) $this->amount,
        'formatted_amount'   => '₹' . number_format((float) $this->amount, 2),
        'payment_mode'       => $this->payment_mode,
        'payment_mode_label' => \App\Models\LeadProduct::PAYMENT_MODES[$this->payment_mode] ?? ucfirst((string) $this->payment_mode),
        'payment_date'       => $this->payment_date?->format('Y-m-d'),
        'reference_number'   => $this->reference_number,
        'notes'              => $this->notes,
        'recorded_by'        => $this->recorded_by,
        'created_at'         => $this->created_at?->format('d M Y h:i A'),
    ];
}
}