<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory, BelongsToCompany;

    protected static function booted(): void
    {
        static::addGlobalScope('branch', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (! auth()->hasUser()) {
                return;
            }

            $user = auth()->user();
            if ($user && $user->isBranchAdmin()) {
                $branchIds = $user->getMyBranchIds();
                if (!empty($branchIds)) {
                    $builder->whereHas('lead', function ($query) use ($branchIds) {
                        $query->whereIn('branch_id', $branchIds);
                    });
                }
            }
        });
    }

    public const CUSTOMER_RESPONSE_PENDING = 'pending';
    public const CUSTOMER_RESPONSE_AGREE = 'agree';
    public const CUSTOMER_RESPONSE_DISAGREE = 'disagree';
    public const CUSTOMER_RESPONSES = [
        self::CUSTOMER_RESPONSE_PENDING => 'Pending',
        self::CUSTOMER_RESPONSE_AGREE => 'Agree',
        self::CUSTOMER_RESPONSE_DISAGREE => 'Disagree',
    ];

    public const GST_RATE = 18.0;
    public const DEFAULT_SELLER_STATE = 'Tamil Nadu';
    public const INDIAN_STATES = [
        'Andhra Pradesh',
        'Arunachal Pradesh',
        'Assam',
        'Bihar',
        'Chhattisgarh',
        'Goa',
        'Gujarat',
        'Haryana',
        'Himachal Pradesh',
        'Jharkhand',
        'Karnataka',
        'Kerala',
        'Madhya Pradesh',
        'Maharashtra',
        'Manipur',
        'Meghalaya',
        'Mizoram',
        'Nagaland',
        'Odisha',
        'Punjab',
        'Rajasthan',
        'Sikkim',
        'Tamil Nadu',
        'Telangana',
        'Tripura',
        'Uttar Pradesh',
        'Uttarakhand',
        'West Bengal',
        'Andaman and Nicobar Islands',
        'Chandigarh',
        'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi',
        'Jammu and Kashmir',
        'Ladakh',
        'Lakshadweep',
        'Puducherry',
    ];

    protected $fillable = [
        'quotation_no',
        'quotation_date',
        'valid_until',
        'tax',
        'subtotal',
        'tax_amount',
        'total_amount',
        'lead_id',
        'is_approved',
        'customer_response',
        'customer_responded_at',
        'approved_by',
        'approved_at',
        'notes',
        'company_id',
        'created_by',
        'bill_to_address',
        'ship_to_address',
        'gst_number',
        'customer_state',
        'seller_state',
        'tax_type',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until'    => 'date',
        'is_approved'    => 'boolean',
        'customer_responded_at' => 'datetime',
        'approved_at'    => 'datetime',
        'tax'            => 'decimal:2',
        'subtotal'       => 'decimal:2',
        'tax_amount'     => 'decimal:2',
        'total_amount'   => 'decimal:2',
        'cgst_rate'      => 'decimal:2',
        'sgst_rate'      => 'decimal:2',
        'igst_rate'      => 'decimal:2',
        'cgst_amount'    => 'decimal:2',
        'sgst_amount'    => 'decimal:2',
        'igst_amount'    => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Generate the next sequential quotation number per company.
     * Format: QT-YY-0001
     */
    public static function generateQuotationNo(?int $companyId = null): string
    {
        $companyId = $companyId ?? (auth()->check() ? auth()->user()?->company_id : null);
        $year   = now()->format('y');
        $prefix = "QT-{$year}-";

        $query = static::where('quotation_no', 'like', "{$prefix}%");
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $last = $query->orderByDesc('id')->value('quotation_no');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        do {
            $candidate = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $checkQuery = static::where('quotation_no', $candidate);
            if ($companyId) {
                $checkQuery->where('company_id', $companyId);
            } else {
                $checkQuery->whereNull('company_id');
            }
            if (! $checkQuery->exists()) {
                return $candidate;
            }
            $seq++;
        } while (true);
    }

    /**
     * Human-readable approval status.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_approved ? 'Approved' : 'Pending';
    }

    public function getCustomerResponseLabelAttribute(): string
    {
        return self::CUSTOMER_RESPONSES[$this->customer_response] ?? 'Pending';
    }

    public static function normalizeState(?string $state): ?string
    {
        if (! $state) {
            return null;
        }

        $normalized = str($state)->lower()->squish()->value();

        foreach (self::INDIAN_STATES as $candidate) {
            if (str($candidate)->lower()->value() === $normalized) {
                return $candidate;
            }
        }

        return ucwords($normalized);
    }

    public static function inferStateFromGstin(?string $gstNumber): ?string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $gstNumber));

        if (strlen($normalized) < 2 || ! ctype_digit(substr($normalized, 0, 2))) {
            return null;
        }

        return [
            '01' => 'Jammu and Kashmir',
            '02' => 'Himachal Pradesh',
            '03' => 'Punjab',
            '04' => 'Chandigarh',
            '05' => 'Uttarakhand',
            '06' => 'Haryana',
            '07' => 'Delhi',
            '08' => 'Rajasthan',
            '09' => 'Uttar Pradesh',
            '10' => 'Bihar',
            '11' => 'Sikkim',
            '12' => 'Arunachal Pradesh',
            '13' => 'Nagaland',
            '14' => 'Manipur',
            '15' => 'Mizoram',
            '16' => 'Tripura',
            '17' => 'Meghalaya',
            '18' => 'Assam',
            '19' => 'West Bengal',
            '20' => 'Jharkhand',
            '21' => 'Odisha',
            '22' => 'Chhattisgarh',
            '23' => 'Madhya Pradesh',
            '24' => 'Gujarat',
            '26' => 'Dadra and Nagar Haveli and Daman and Diu',
            '27' => 'Maharashtra',
            '29' => 'Karnataka',
            '30' => 'Goa',
            '31' => 'Lakshadweep',
            '32' => 'Kerala',
            '33' => 'Tamil Nadu',
            '34' => 'Puducherry',
            '36' => 'Telangana',
            '37' => 'Andhra Pradesh',
            '38' => 'Ladakh',
        ][substr($normalized, 0, 2)] ?? null;
    }

    public static function calculateTaxBreakup(float $subtotal, ?string $customerState, ?string $sellerState = null): array
    {
        $sellerState = self::normalizeState($sellerState ?: self::DEFAULT_SELLER_STATE) ?? self::DEFAULT_SELLER_STATE;
        $customerState = self::normalizeState($customerState);

        $cleanState = strtolower((string) preg_replace('/[\s\-_]/', '', (string) $customerState));
        $isIntraState = ($cleanState === 'tamilnadu' || $cleanState === 'tn' || ($customerState !== null && strcasecmp($customerState, $sellerState) === 0));

        $taxRate = self::GST_RATE;
        $cgstRate = $isIntraState ? round($taxRate / 2, 2) : 0.0;
        $sgstRate = $isIntraState ? round($taxRate / 2, 2) : 0.0;
        $igstRate = $isIntraState ? 0.0 : $taxRate;

        $cgstAmount = round($subtotal * ($cgstRate / 100), 2);
        $sgstAmount = round($subtotal * ($sgstRate / 100), 2);
        $igstAmount = round($subtotal * ($igstRate / 100), 2);
        $taxAmount = round($cgstAmount + $sgstAmount + $igstAmount, 2);

        return [
            'seller_state' => $sellerState,
            'customer_state' => $customerState,
            'tax_type' => $isIntraState ? 'cgst_sgst' : 'igst',
            'tax_rate' => $taxRate,
            'cgst_rate' => $cgstRate,
            'sgst_rate' => $sgstRate,
            'igst_rate' => $igstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_amount' => $sgstAmount,
            'igst_amount' => $igstAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => round($subtotal + $taxAmount, 2),
        ];
    }
}
