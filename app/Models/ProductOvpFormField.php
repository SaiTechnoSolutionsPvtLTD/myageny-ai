<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOvpFormField extends Model
{
    use HasFactory, SoftDeletes;

    public const FIELD_TYPES = ['text', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date', 'file'];
    public const OPTION_TYPES = ['select', 'radio', 'checkbox'];

    protected $fillable = [
        'product_id',
        'label',
        'field_name',
        'field_type',
        'placeholder',
        'help_text',
        'default_value',
        'is_required',
        'is_active',
        'use_in_ovp',
        'use_in_production_initiation',
        'sort_order',
        'options',
        'validation_rules',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'use_in_ovp' => 'boolean',
        'use_in_production_initiation' => 'boolean',
        'sort_order' => 'integer',
        'options' => 'array',
        'validation_rules' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function makeFieldName(string $label): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($label)));

        return 'ovp_' . trim((string) $normalized, '_');
    }

    public function hasOptions(): bool
    {
        return in_array($this->field_type, self::OPTION_TYPES, true);
    }
}
