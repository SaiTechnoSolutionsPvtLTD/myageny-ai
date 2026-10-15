<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'sales_person_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'payment_terms',
        'place_of_supply',
        'customer_name',
        'company_name',
        'customer_email',
        'customer_phone',
        'customer_gstin',
        'customer_pan',
        'billing_attention',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_pincode',
        'billing_country',
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
        'paid_amount',
        'balance_due',
        'customer_notes',
        'terms_conditions',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'total_cgst'   => 'decimal:2',
        'total_sgst'   => 'decimal:2',
        'total_igst'   => 'decimal:2',
        'total_tax'    => 'decimal:2',
        'round_off'    => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'balance_due'  => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id')->orderBy('sort_order', 'asc');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerMaster::class, 'customer_id');
    }

    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Generate the next sequential invoice number based on company's InvoiceSetting.
     */
    public static function generateNextInvoiceNumber(?int $companyId = null): string
    {
        $setting = InvoiceSetting::forCompany($companyId);
        $nextNum = $setting->next_number ?: 1;

        // Ensure uniqueness
        while (static::where('company_id', $companyId)->where('invoice_number', $setting->formatInvoiceNumber($nextNum))->exists()) {
            $nextNum++;
        }

        return $setting->formatInvoiceNumber($nextNum);
    }

    /**
     * Status badge accessor.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'paid'           => '<span class="acm-badge status-paid"><i class="bi bi-check-circle-fill"></i> Paid</span>',
            'partially_paid' => '<span class="acm-badge status-partially-paid"><i class="bi bi-pie-chart-fill"></i> Partially Paid</span>',
            'sent'           => '<span class="acm-badge status-sent"><i class="bi bi-send-fill"></i> Sent</span>',
            'overdue'        => '<span class="acm-badge status-overdue"><i class="bi bi-exclamation-triangle-fill"></i> Overdue</span>',
            'cancelled'      => '<span class="acm-badge status-cancelled"><i class="bi bi-x-circle-fill"></i> Cancelled</span>',
            default          => '<span class="acm-badge status-draft"><i class="bi bi-file-earmark"></i> Draft</span>',
        };
    }

    /**
     * Convert amount to Indian Rupee Words.
     * e.g. 5900.00 -> "Indian Rupee Five Thousand Nine Hundred Only"
     */
    public function getAmountInWordsAttribute(): string
    {
        return self::convertToIndianCurrencyWords((float) $this->total_amount);
    }

    public static function convertToIndianCurrencyWords(float $number): string
    {
        $no = (int) floor($number);
        $decimal = (int) round(($number - $no) * 100);

        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
            50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        ];

        if ($no === 0) {
            $result = 'Zero';
        } else {
            $str = [];
            $n_str = (string) $no;
            $len = strlen($n_str);

            // Last 3 digits (Hundreds & Tens)
            $last3 = (int) substr($n_str, -3);
            $n_str = $len > 3 ? substr($n_str, 0, -3) : '';
            $hundreds = (int) ($last3 / 100);
            $tens = $last3 % 100;
            $chunk = '';
            if ($hundreds > 0) {
                $chunk .= $words[$hundreds] . ' Hundred ';
            }
            if ($tens > 0) {
                if ($tens < 20) {
                    $chunk .= $words[$tens] . ' ';
                } else {
                    $chunk .= $words[(int)($tens / 10) * 10] . ' ' . $words[$tens % 10] . ' ';
                }
            }
            if (trim($chunk) !== '') {
                $str[] = trim($chunk);
            }

            // Indian units: Thousand, Lakh, Crore
            $units = ['Thousand', 'Lakh', 'Crore'];
            $unitIdx = 0;
            while (strlen($n_str) > 0 && $unitIdx < count($units)) {
                $pair = (int) substr($n_str, -2);
                $n_str = strlen($n_str) > 2 ? substr($n_str, 0, -2) : '';
                if ($pair > 0) {
                    $chunk = '';
                    if ($pair < 20) {
                        $chunk = $words[$pair];
                    } else {
                        $chunk = $words[(int)($pair / 10) * 10] . ' ' . $words[$pair % 10];
                    }
                    $str[] = trim($chunk) . ' ' . $units[$unitIdx];
                }
                $unitIdx++;
            }

            if (strlen($n_str) > 0 && (int)$n_str > 0) {
                $str[] = self::convertToIndianCurrencyWords((float) $n_str) . ' Crore';
            }

            $result = implode(' ', array_reverse($str));
        }

        $result = trim(preg_replace('/\s+/', ' ', $result));

        $paise = '';
        if ($decimal > 0) {
            if ($decimal < 20) {
                $paise = ' and Paise ' . $words[$decimal];
            } else {
                $paise = ' and Paise ' . $words[(int)($decimal / 10) * 10] . ' ' . $words[$decimal % 10];
            }
        }

        return 'Indian Rupee ' . $result . $paise . ' Only';
    }
}
