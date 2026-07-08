<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    private const SKU_PREFIX = 'STSPRD';
    private const SKU_PADDING = 4;

    protected $fillable = [
        'product_category_id', 'package_name', 'sku',
        'base_price', 'tax_type', 'tax_value', 'discount_type', 'discount_value',
        'final_price', 'description', 'status', 'sort_order', 'product_name',
        'company_id', 'created_by', 'assigned_to', 'count_wise_report',
    ];

    protected $casts = [
        'base_price'     => 'float',
        'tax_value'      => 'float',
        'discount_value' => 'float',
        'final_price'    => 'float',
        'count_wise_report' => 'boolean',
    ];

    // ── Boot ──────────────────────────────────────────────────────────
    protected static function boot(): void
    {
        parent::boot();

        $callback = function (self $model) {
            $model->final_price = $model->computeFinalPrice();
            if (empty($model->sku)) {
                $model->sku = self::generateNextSku();
            }
        };

        static::creating($callback);
        static::updating($callback);

        static::creating(function (self $model) {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class)->with('attribute')->orderBy('sort_order');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->withTimestamps()->orderBy('name');
    }

    public function ovpFormFields(): HasMany
    {
        return $this->hasMany(ProductOvpFormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function productionInitiations(): HasMany
    {
        return $this->hasMany(ProductionInitiation::class)->latest();
    }

    public function productionCountReports(): HasMany
    {
        return $this->hasMany(ProductionCountReport::class);
    }

    public function scopeCountWise($query)
    {
        return $query->where('count_wise_report', true);
    }

    // ── Price Logic ───────────────────────────────────────────────────
    public function computeFinalPrice(): float
    {
        $base = (float) $this->base_price;

        // Apply discount first
        if ($this->discount_type === 'percentage') {
            $discounted = $base - ($base * $this->discount_value / 100);
        } else {
            $discounted = $base - $this->discount_value;
        }

        // Apply tax
        if ($this->tax_type === 'percentage') {
            $final = $discounted + ($discounted * $this->tax_value / 100);
        } else {
            $final = $discounted + $this->tax_value;
        }

        return round(max(0, $final), 2);
    }

    // ── Accessors ─────────────────────────────────────────────────────
    public function getFormattedFinalPriceAttribute(): string
    {
        return '₹' . number_format($this->final_price, 2);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'   => '<span class="pm-badge pm-badge--active">Active</span>',
            'inactive' => '<span class="pm-badge pm-badge--inactive">Inactive</span>',
            'draft'    => '<span class="pm-badge pm-badge--draft">Draft</span>',
            default    => '',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('product_category_id', $categoryId);
    }

    public static function generateNextSku(): string
    {
        $lastNumber = static::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->get(['sku'])
            ->max(fn (self $product) => self::extractSkuNumber($product->sku)) ?? 0;

        return self::formatSkuNumber($lastNumber + 1);
    }

    public static function formatSkuNumber(int $number): string
    {
        return self::SKU_PREFIX . str_pad((string) $number, self::SKU_PADDING, '0', STR_PAD_LEFT);
    }

    public static function isFormattedSku(?string $sku): bool
    {
        if (! is_string($sku) || $sku === '') {
            return false;
        }

        return preg_match('/^' . self::SKU_PREFIX . '\d+$/', $sku) === 1;
    }

    public static function extractSkuNumber(?string $sku): int
    {
        if (! self::isFormattedSku($sku)) {
            return 0;
        }

        return (int) substr($sku, strlen(self::SKU_PREFIX));
    }
}
