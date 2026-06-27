<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignSettingTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'product_type',
        'daily_target',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'daily_target' => 'integer',
            'is_active'    => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Return all targets for a given company, keyed by [user_id][product_type].
     *
     * @param  int|null  $companyId
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function mapForCompany(?int $companyId): \Illuminate\Support\Collection
    {
        return static::where('company_id', $companyId)
            ->get()
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->keyBy('product_type'));
    }

    /**
     * Default built-in product types shown when no custom types are configured.
     */
    public static function defaultProductTypes(): array
    {
        return ['Poster', 'Video', 'Logo', 'Flyer', 'Banner', 'Reel'];
    }
}
