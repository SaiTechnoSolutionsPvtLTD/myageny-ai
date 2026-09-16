<?php
// ================================================================
// FILE: app/Models/LeadProductPayment.php
// ================================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LeadProductPayment extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'lead_product_id',
        'lead_id',
        'recorded_by',
        'payment_type',
        'is_tds_deducted',
        'tds_percentage',
        'tds_amount',
        'after_tds_amount',
        'amount',
        'payment_mode',
        'payment_date',
        'reference_number',
        'notes',
        'attachment_path',
        'attachment_name',
    ];

    const PAYMENT_TYPES = [
        'new_sale'        => 'New Sale',
        'balance_payment' => 'Balance Payment',
        'renewals'        => 'Renewals',
    ];

    public function getPaymentTypeLabelAttribute(): string
    {
        return self::PAYMENT_TYPES[$this->payment_type] ?? ucwords(str_replace('_', ' ', (string) $this->payment_type));
    }

    protected $casts = [
        'amount'           => 'decimal:2',
        'payment_date'     => 'date',
        'is_tds_deducted'  => 'boolean',
        'tds_percentage'   => 'decimal:2',
        'tds_amount'       => 'decimal:2',
        'after_tds_amount' => 'decimal:2',
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

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        if (str_starts_with($this->attachment_path, 'uploads/') || str_starts_with($this->attachment_path, 'public/')) {
            return asset($this->attachment_path);
        }

        if (file_exists(public_path($this->attachment_path))) {
            return asset($this->attachment_path);
        }

        return Storage::disk('public')->url($this->attachment_path);
    }

    public function toJsPayload(): array
    {
        $modeLabel = \App\Models\LeadProduct::PAYMENT_MODES[$this->payment_mode] ?? ucfirst((string) $this->payment_mode);
        $modeIcon = self::PAYMENT_MODE_ICONS[$this->payment_mode] ?? '💰';
        $modeColor = self::PAYMENT_MODE_COLORS[$this->payment_mode] ?? '#7c7c7c';
        $typeLabel = self::PAYMENT_TYPES[$this->payment_type] ?? ($this->payment_type ? ucwords(str_replace('_', ' ', (string) $this->payment_type)) : null);

        return [
            'id'                 => $this->id,
            'amount'             => (float) $this->amount,
            'formatted_amount'   => '₹' . number_format((float) $this->amount, 2),
            'payment_type'       => $this->payment_type,
            'payment_type_label' => $typeLabel,
            'is_tds_deducted'    => (bool) $this->is_tds_deducted,
            'tds_percentage'     => $this->tds_percentage !== null ? (float) $this->tds_percentage : null,
            'tds_amount'         => $this->tds_amount !== null ? (float) $this->tds_amount : null,
            'after_tds_amount'   => $this->after_tds_amount !== null ? (float) $this->after_tds_amount : null,
            'payment_mode'       => $this->payment_mode,
            'payment_mode_label' => $modeLabel,
            'payment_date'       => $this->payment_date?->format('Y-m-d'),
            'reference_number'   => $this->reference_number,
            'notes'              => $this->notes,
            'attachment_name'    => $this->attachment_name,
            'attachment_url'     => $this->attachment_url,
            'recorded_by'        => $this->recorded_by,
            'created_at'         => $this->created_at?->format('d M Y h:i A'),
            'type'               => $this->payment_type,
            'typeLabel'          => $typeLabel,
            'mode'               => $this->payment_mode,
            'modeLabel'          => $modeLabel,
            'modeIcon'           => $modeIcon,
            'modeColor'          => $modeColor,
            'date'               => $this->payment_date?->format('d M Y'),
            'ref'                => $this->reference_number ?? '',
            'by'                 => $this->recordedBy?->name ?? 'System',
            'attachment'         => $this->attachment_path
                ? [
                    'name' => $this->attachment_name ?: basename($this->attachment_path),
                    'url'  => $this->attachment_url,
                ]
                : null,
        ];
    }
}
