<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class QuotationSetting extends Model
{
    protected $fillable = ['branch_id', 'key', 'value', 'type', 'label', 'group', 'description'];

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
                    $builder->whereIn($builder->getModel()->getTable() . '.branch_id', $branchIds);
                }
            }
        });
    }

    // ─── Default clean values if no setting found ─────────────
    public static array $defaults = [
        'logo'              => '',
        'theme_color'       => '#fe5f04',
        'secondary_color'   => '#fe5f04',
        'header_text_color' => '#ffffff',
        'terms'             => '',
        'signature'         => '',
        'company_address'   => '',
        'prefix'            => 'QUO-',
        'next_number'       => 1,
        'number_padding'    => 5,
        'company_name'      => '',
        'company_phone'     => '',
        'company_email'     => '',
        'company_gstin'     => '',
        'bank_name'         => '',
        'account_name'      => '',
        'bank_account'      => '',
        'bank_ifsc'         => '',
        'bank_branch'       => '',
        'bank_upi'          => '',
        'watermark_text'    => '',
        'show_watermark'    => false,
    ];

    // ─── Get all settings for a branch as associative array (cached) ──────
    public static function allSettings(?int $branchId = null): array
    {
        $cacheKey = 'quotation_settings_' . ($branchId ?? 'global');

        return Cache::remember($cacheKey, 3600, function () use ($branchId) {
            $settings = static::defaults();

            // 1. Global defaults (branch_id = null)
            static::withoutGlobalScopes()
                ->whereNull('branch_id')
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->get()
                ->each(function($s) use (&$settings) {
                    $val = $s->resolvedValue();
                    if ($val !== null && $val !== '') {
                        $settings[$s->key] = $val;
                    }
                });

            // 2. Override with branch-specific settings
            if (!is_null($branchId)) {
                static::withoutGlobalScopes()
                    ->where('branch_id', $branchId)
                    ->whereNotNull('value')
                    ->where('value', '!=', '')
                    ->get()
                    ->each(function($s) use (&$settings) {
                        $val = $s->resolvedValue();
                        if ($val !== null && $val !== '') {
                            $settings[$s->key] = $val;
                        }
                    });
            }

            return $settings;
        });
    }

    /**
     * Resolve Quotation Settings strictly for a specific Quotation.
     * Looks up settings for the quotation's company and branches without hardcoding or leaking other companies' data.
     */
    public static function forQuotation(Quotation $quotation): array
    {
        $companyId = $quotation->company_id
            ?? $quotation->lead?->company_id
            ?? $quotation->lead?->branch?->company_id
            ?? $quotation->createdBy?->company_id
            ?? auth()->user()?->company_id;

        $specificBranchId = $quotation->lead?->branch_id ?? $quotation->createdBy?->branch_id;

        $company = null;
        $defaultBranchId = null;
        $companyBranchIds = [];

        if ($companyId) {
            $company = Company::find($companyId);
            $companyBranchIds = Branch::where('company_id', $companyId)->pluck('id')->toArray();
            $defaultBranchId = Branch::where('company_id', $companyId)->where('is_default', 1)->value('id')
                ?? Branch::where('company_id', $companyId)->value('id');
        }

        $branchesToTry = array_filter(array_unique([
            $specificBranchId,
            $defaultBranchId,
            auth()->user()?->branch_id,
            ...$companyBranchIds,
        ]));

        $settings = static::defaults();
        if ($company) {
            $settings['company_name']    = $company->company_name ?? '';
            $settings['company_address'] = $company->address ?? '';
            $settings['company_phone']   = $company->mobile_number ?? '';
            $settings['company_email']   = $company->email ?? '';
        }

        // Apply from lowest priority to highest priority
        $reversedBranches = array_reverse($branchesToTry);
        foreach ($reversedBranches as $bId) {
            $rows = static::withoutGlobalScopes()
                ->where('branch_id', $bId)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->get();
            foreach ($rows as $r) {
                $val = $r->resolvedValue();
                if ($val !== null && $val !== '') {
                    $settings[$r->key] = $val;
                }
            }
        }

        // Fix storage path prefixes
        foreach (['logo', 'signature'] as $fileKey) {
            if (!empty($settings[$fileKey]) && is_string($settings[$fileKey]) && str_starts_with($settings[$fileKey], '/storage/')) {
                $settings[$fileKey] = substr($settings[$fileKey], 9);
            }
        }

        return $settings;
    }

    // ─── Get single setting ───────────────────────────────────
    public static function get(string $key, ?int $branchId = null, mixed $default = null): mixed
    {
        $settings = static::allSettings($branchId);
        return $settings[$key] ?? $default ?? static::$defaults[$key] ?? null;
    }

    // ─── Save / update a setting ─────────────────────────────
    public static function set(string $key, mixed $value, ?int $branchId = null): self
    {
        $setting = static::updateOrCreate(
            ['branch_id' => $branchId, 'key' => $key],
            ['value' => $value]
        );

        // Bust cache
        Cache::forget('quotation_settings_' . ($branchId ?? 'global'));
        Cache::forget('quotation_settings_global');

        return $setting;
    }

    // ─── Resolve value (handle file types) ───────────────────
    public function resolvedValue(): mixed
    {
        if ($this->type === 'file' && $this->value) {
            return Storage::url($this->value);
        }
        if ($this->type === 'boolean') {
            return (bool) $this->value;
        }
        return $this->value;
    }

    // ─── Build defaults array ─────────────────────────────────
    private static function defaults(): array
    {
        return static::$defaults;
    }

    // ─── Generate next quotation number ──────────────────────
    public static function generateQuotationNumber(?int $branchId = null): string
    {
        $settings = static::allSettings($branchId);
        $prefix    = $settings['prefix'] ?? 'QUO-';
        $next      = (int)($settings['next_number'] ?? 1);
        $padding   = (int)($settings['number_padding'] ?? 5);

        $number = $prefix . str_pad($next, $padding, '0', STR_PAD_LEFT);

        // Increment next_number
        static::set('next_number', $next + 1, $branchId);

        return $number;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
