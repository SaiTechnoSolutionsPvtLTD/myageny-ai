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

    // ─── Default values if no setting found ──────────────────
    public static array $defaults = [
        'logo'              => null,
        'theme_color'       => '#fe5f04',
        'secondary_color'   => '#fe5f04',
        'header_text_color' => '#ffffff',
        'terms'             => '<p>1. This quotation is valid for 30 days from the date of issue.</p>
                                 <p>2. Payment terms: 50% advance, 50% on delivery.</p>
                                 <p>3. Prices are subject to change without prior notice.</p>',
        'signature'         => null,
        'company_address'   => '',
        'prefix'            => 'QUO-',
        'next_number'       => 1,
        'number_padding'    => 5,
        'company_name'      => '',
        'company_phone'     => '',
        'company_email'     => '',
        'company_gstin'     => '',
        'bank_name'         => '',
        'bank_account'      => '',
        'bank_ifsc'         => '',
        'watermark_text'    => '',
        'show_watermark'    => false,
    ];

    // ─── Get all settings as associative array (cached) ──────
    public static function allSettings(?int $branchId = null): array
    {
        $cacheKey = 'quotation_settings_' . ($branchId ?? 'global');

        return Cache::remember($cacheKey, 3600, function () use ($branchId) {
            $settings = static::defaults();

            // Load global defaults (branch_id = null)
            static::whereNull('branch_id')
                ->get()
                ->each(fn($s) => $settings[$s->key] = $s->resolvedValue());

            // Override with branch-specific settings
            if (!is_null($branchId))  {
                static::where('branch_id', $branchId)
                    ->get()
                    ->each(fn($s) => $settings[$s->key] = $s->resolvedValue());
            }

            return $settings;
        });
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
