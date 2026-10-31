<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Quotation extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

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

        static::creating(function (Quotation $quotation) {
            if (empty($quotation->quotation_no)) {
                $quotation->quotation_no = static::generateQuotationNo($quotation->company_id);
            }

            if (! $quotation->created_by && auth()->check()) {
                $quotation->created_by = auth()->id();
            }

            if (! $quotation->is_approved) {
                $creator = null;
                if ($quotation->created_by) {
                    $creator = $quotation->relationLoaded('createdBy')
                        ? $quotation->createdBy
                        : User::find($quotation->created_by);
                }
                if (! $creator && auth()->check()) {
                    $creator = auth()->user();
                }

                if ($creator && method_exists($creator, 'canAutoApproveQuotation') && $creator->canAutoApproveQuotation()) {
                    $quotation->is_approved = true;
                    $quotation->approved_by = $quotation->approved_by ?: $creator->id;
                    $quotation->approved_at = $quotation->approved_at ?: now();
                }
            }
        });

        static::created(function (Quotation $quotation) {
            try {
                $branchId = $quotation->lead?->branch_id ?? $quotation->createdBy?->branch_id ?? auth()->user()?->branch_id;
                $settings = QuotationSetting::allSettings($branchId);
                $prefix   = ! empty($settings['prefix']) ? (string) $settings['prefix'] : 'QT-';
                if (preg_match('/^' . preg_quote($prefix, '/') . '0*(\d+)$/i', (string) $quotation->quotation_no, $m)) {
                    $usedNum = (int) $m[1];
                    $currentNext = (int) ($settings['next_number'] ?? 1805);
                    if ($usedNum >= $currentNext) {
                        QuotationSetting::set('next_number', $usedNum + 1, $branchId);
                    }
                }
            } catch (\Throwable $e) {
                // Silently ignore setting cache issue
            }
        });

        static::deleting(function (Quotation $quotation) {
            if ($quotation->isForceDeleting()) {
                $quotation->items()->withTrashed()->forceDelete();
            } else {
                $quotation->items()->delete();
            }
        });

        static::restoring(function (Quotation $quotation) {
            $quotation->items()->withTrashed()->restore();
        });
    }

    public const CUSTOMER_RESPONSE_PENDING = 'pending';
    public const CUSTOMER_RESPONSE_AGREE = 'agree';
    public const CUSTOMER_RESPONSE_DISAGREE = 'disagree';
    public const CUSTOMER_RESPONSES = [
        self::CUSTOMER_RESPONSE_PENDING => 'Pending',
        self::CUSTOMER_RESPONSE_AGREE => 'Approved',
        self::CUSTOMER_RESPONSE_DISAGREE => 'Rejected',
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
        'customer_signature',
        'customer_location',
        'customer_latitude',
        'customer_longitude',
        'customer_ip',
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

    public function getConvertedInvoiceAttribute(): ?Invoice
    {
        return Invoice::where(function ($q) {
                if ($this->company_id) {
                    $q->where('company_id', $this->company_id);
                }
            })
            ->where(function ($q) {
                $q->where('customer_notes', 'like', "%{$this->quotation_no}%")
                  ->orWhere('terms_conditions', 'like', "%{$this->quotation_no}%");
            })
            ->latest('id')
            ->first();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Generate the next sequential quotation number per company / branch.
     * Format: QT-001805 (or configured prefix & padding in QuotationSetting)
     * Seamlessly continues from Zoho migrated estimates and existing quotations.
     */
    public static function generateQuotationNo(?int $companyId = null, ?int $branchId = null): string
    {
        $companyId = $companyId ?? (auth()->check() ? auth()->user()?->company_id : null);
        $branchId  = $branchId  ?? (auth()->check() ? auth()->user()?->branch_id : null);

        if (! $branchId && $companyId) {
            $branchId = Branch::where('company_id', $companyId)->where('is_default', 1)->value('id')
                ?? Branch::where('company_id', $companyId)->value('id');
        }

        $settings = QuotationSetting::allSettings($branchId);
        $prefix   = ! empty($settings['prefix']) ? (string) $settings['prefix'] : 'QT-';
        $padding  = ! empty($settings['number_padding']) ? (int) $settings['number_padding'] : 6;
        $settingNext = ! empty($settings['next_number']) ? (int) $settings['next_number'] : 1805;

        // 1. Scan native quotations for highest sequence number matching prefix or QT-
        $maxNativeSeq = 0;
        $quotesQuery = static::withoutGlobalScopes()->withTrashed();
        if ($companyId) {
            $quotesQuery->where('company_id', $companyId);
        }
        $existingQuotes = $quotesQuery->where(function ($q) use ($prefix) {
            $q->where('quotation_no', 'like', "{$prefix}%")
              ->orWhere('quotation_no', 'like', 'QT-%');
        })->pluck('quotation_no');

        foreach ($existingQuotes as $qNo) {
            $str = trim((string) $qNo);
            if (preg_match('/^' . preg_quote($prefix, '/') . '0*(\d+)$/i', $str, $m)) {
                $maxNativeSeq = max($maxNativeSeq, (int) $m[1]);
            } elseif (preg_match('/^QT-0*(\d+)$/i', $str, $m)) {
                $maxNativeSeq = max($maxNativeSeq, (int) $m[1]);
            }
        }

        // 2. Scan Zoho quotations table (estimates migrated or synced from Zoho Books)
        $maxZohoSeq = 0;
        if (Schema::hasTable('zoho_quotations')) {
            $zohoQuery = DB::table('zoho_quotations')->whereNotNull('estimate_number');
            if ($companyId) {
                $zohoQuery->where('company_id', $companyId);
            }
            $zohoEstimates = $zohoQuery->pluck('estimate_number');
            foreach ($zohoEstimates as $estNo) {
                $str = trim((string) $estNo);
                if (preg_match('/^' . preg_quote($prefix, '/') . '0*(\d+)$/i', $str, $zm)) {
                    $maxZohoSeq = max($maxZohoSeq, (int) $zm[1]);
                } elseif (preg_match('/^QT-0*(\d+)$/i', $str, $zm)) {
                    $maxZohoSeq = max($maxZohoSeq, (int) $zm[1]);
                } elseif (preg_match('/(\d{4,10})$/', $str, $zm)) {
                    $maxZohoSeq = max($maxZohoSeq, (int) $zm[1]);
                }
            }
        }

        // 3. Determine starting sequence: max of settingNext, highest existing + 1, or baseline 1805
        $highestExisting = max($maxNativeSeq, $maxZohoSeq);
        $nextSeq = max($settingNext, $highestExisting > 0 ? ($highestExisting + 1) : 1805, 1805);

        // 4. Ensure uniqueness across system (loop until unused number found)
        do {
            $candidate = $prefix . str_pad((string) $nextSeq, $padding, '0', STR_PAD_LEFT);

            $existsNative = static::withoutGlobalScopes()->withTrashed()->where('quotation_no', $candidate);
            if ($companyId) {
                $existsNative->where('company_id', $companyId);
            }

            $existsZoho = false;
            if (Schema::hasTable('zoho_quotations')) {
                $zq = DB::table('zoho_quotations')->where('estimate_number', $candidate);
                if ($companyId) {
                    $zq->where('company_id', $companyId);
                }
                $existsZoho = $zq->exists();
            }

            if (! $existsNative->exists() && ! $existsZoho) {
                return $candidate;
            }

            $nextSeq++;
        } while (true);
    }

    /**
     * Human-readable approval status.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_approved ? 'Approved' : 'Pending';
    }

    public function getCustomerSignatureUrlAttribute(): ?string
    {
        if (! $this->customer_signature) {
            return null;
        }

        if (str_starts_with($this->customer_signature, 'http://') || str_starts_with($this->customer_signature, 'https://')) {
            return $this->customer_signature;
        }

        return asset($this->customer_signature);
    }

    public function getCustomerResponseLabelAttribute(): string
    {
        return self::CUSTOMER_RESPONSES[$this->customer_response] ?? (
            match (strtolower((string) $this->customer_response)) {
                'agree', 'approved', 'approve' => 'Approved',
                'disagree', 'rejected', 'reject' => 'Rejected',
                default => 'Pending',
            }
        );
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
