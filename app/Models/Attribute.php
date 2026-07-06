<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Attribute extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'product_category_id', 'name', 'key', 'field_type',
        'options', 'unit', 'placeholder', 'is_required', 'sort_order', 'is_active', 'company_id',
    ];

    protected $casts = [
        'options'     => 'array',
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];

    // ── Boot ──────────────────────────────────────────────────────────
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->key)) {
                $model->key = Str::slug($model->name, '_');
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function presetValues()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    public function productValues()
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    public function getOptionsArrayAttribute(): array
    {
        return $this->options ?? [];
    }
}