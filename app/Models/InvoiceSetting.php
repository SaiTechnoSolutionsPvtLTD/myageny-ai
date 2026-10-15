<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    use HasFactory;

    protected $table = 'invoice_settings';

    protected $fillable = [
        'company_id',
        'branch_id',
        'invoice_prefix',
        'date_format',
        'next_number',
        'number_padding',
        'invoice_suffix',
        'reset_frequency',
        'company_name',
        'company_address',
        'company_city',
        'company_state',
        'company_pincode',
        'company_phone',
        'company_email',
        'company_gstin',
        'company_pan',
        'logo_path',
        'bank_name',
        'account_name',
        'account_number',
        'ifsc_code',
        'branch',
        'upi_id',
        'swift_code',
        'terms_conditions',
        'notes',
        'signature_path',
        'signatory_name',
        'created_by',
        'updated_by',
    ];

    /**
     * Default Terms & Conditions template.
     */
    public static function defaultTerms(): string
    {
        return "1. Payment is due within the agreed payment terms.\n" .
               "2. Please quote invoice number when remitting payment.\n" .
               "3. Make all payments via NEFT / RTGS / UPI to the bank account specified above.\n" .
               "4. Interest @ 18% p.a. will be charged on all overdue invoices.\n" .
               "5. Subject to jurisdiction of local courts.";
    }

    /**
     * Get or initialize setting instance for the given company.
     */
    public static function forCompany(?int $companyId): self
    {
        $setting = static::where('company_id', $companyId)->first();

        if (!$setting) {
            $setting = static::create([
                'company_id'       => $companyId,
                'invoice_prefix'   => 'INV-',
                'date_format'      => 'financial_year',
                'next_number'      => 561,
                'number_padding'   => 6,
                'reset_frequency'  => 'financial_year',
                'company_name'     => 'Sai Techno Solutions Private Limited',
                'company_address'  => "No: 6, 1st Floor, KCP Garden,\nKaapi Kadai Stop, Saravanampatti,\nCoimbatore 641035\nTamil Nadu\nIndia",
                'company_city'     => 'Coimbatore',
                'company_state'    => 'Tamil Nadu',
                'company_pincode'  => '641035',
                'company_gstin'    => '33ABPCS7671C1Z3',
                'bank_name'        => 'ICICI BANK',
                'account_name'     => 'SAI TECHNO SOLUTIONS PRIVATE LIMITED',
                'account_number'   => '461505000312',
                'ifsc_code'        => 'ICIC0004615',
                'branch'           => 'TOWNHALL, COIMBATORE',
                'upi_id'           => 'saite87549.ibz@icici',
                'swift_code'       => 'ICICINBB016',
                'signatory_name'   => 'Authorized Signatory',
                'signature_path'   => 'images/sign.jpeg',
                'logo_path'        => 'images/LOGO_STS.png',
                'terms_conditions' => '100% Advance Payment',
                'notes'            => 'Thanks for your business.',
            ]);
        }

        return $setting;
    }

    /**
     * Generate sample/formatted invoice number.
     */
    public function formatInvoiceNumber(?int $number = null): string
    {
        $num = $number ?? ($this->next_number ?: 1);
        $padded = str_pad((string) $num, (int) ($this->number_padding ?: 4), '0', STR_PAD_LEFT);
        $prefix = (string) ($this->invoice_prefix ?? 'INV/');

        $now = Carbon::now();
        $dateTag = '';

        switch ($this->date_format) {
            case 'financial_year':
                $year = $now->year;
                if ($now->month >= 4) {
                    $nextYrShort = substr((string) ($year + 1), -2);
                    $dateTag = $year . '-' . $nextYrShort;
                } else {
                    $curYrShort = substr((string) $year, -2);
                    $dateTag = ($year - 1) . '-' . $curYrShort;
                }
                break;

            case 'calendar_year':
                $dateTag = (string) $now->year;
                break;

            case 'year_month':
                $dateTag = $now->format('Y-m');
                break;

            case 'none':
            default:
                $dateTag = '';
                break;
        }

        $parts = [];
        if (!empty($prefix)) {
            $parts[] = rtrim($prefix, '/-');
        }
        if (!empty($dateTag)) {
            $parts[] = $dateTag;
        }
        $parts[] = $padded;
        if (!empty($this->invoice_suffix)) {
            $parts[] = ltrim($this->invoice_suffix, '/-');
        }

        $sep = str_ends_with($prefix, '-') ? '-' : '/';
        return implode($sep, $parts);
    }

    /**
     * Logo URL Accessor.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path && file_exists(public_path($this->logo_path))) {
            return asset($this->logo_path);
        }
        if (file_exists(public_path('images/LOGO_STS.png'))) {
            return asset('images/LOGO_STS.png');
        }
        return null;
    }

    /**
     * Signature URL Accessor.
     */
    public function getSignatureUrlAttribute(): ?string
    {
        if ($this->signature_path && file_exists(public_path($this->signature_path))) {
            return asset($this->signature_path);
        }
        if (file_exists(public_path('images/sign.jpeg'))) {
            return asset('images/sign.jpeg');
        }
        return null;
    }
}
