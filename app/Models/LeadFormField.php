<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadFormField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'label',
        'field_name',
        'field_type',
        'placeholder',
        'default_value',
        'is_required',
        'is_active',
        'show_on_lead_create',
        'sort_order',
        'is_calculation',
        'calculation_formula',
        'calculation_label',
        'options',
        'validation_rules',
        'branch_id',
    ];

    protected $casts = [
        'is_required'          => 'boolean',
        'is_active'            => 'boolean',
        'show_on_lead_create'  => 'boolean',
        'is_calculation'       => 'boolean',
        'options'              => 'array',
        'validation_rules'     => 'array',
        'sort_order'           => 'integer',
    ];

    // ----------------------------------------------------------------
    //  Field types that support options (select / radio)
    // ----------------------------------------------------------------
    public const OPTION_TYPES = ['select', 'radio'];

    // ----------------------------------------------------------------
    //  Relationships
    // ----------------------------------------------------------------
    public function sections()
    {
        return $this->belongsToMany(
            LeadFormSection::class,
            'lead_form_field_section',
            'lead_form_field_id',
            'lead_form_section_id'
        )->withPivot('sort_order');
    }

    public function fieldValues()
    {
        return $this->hasMany(LeadFieldValue::class);
    }

    // ----------------------------------------------------------------
    //  Helpers
    // ----------------------------------------------------------------

    /**
     * Convert a human-readable label to a safe snake_case field_name.
     */
    public static function makeFieldName(string $label): string
    {
        return 'cf_' . preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($label)));
    }

    /**
     * Check if this field type supports option lists.
     */
    public function hasOptions(): bool
    {
        return in_array($this->field_type, self::OPTION_TYPES);
    }

    /**
     * Evaluate a simple calculation formula against a key=>value map.
     * Formula syntax: field_names joined by +  -  *  /  ( )
     * Example:  "cf_quantity * cf_unit_price"
     *
     * Returns float or null on error.
     */
    public function evaluate(array $values): ?float
    {
        if (!$this->is_calculation || !$this->calculation_formula) {
            return null;
        }

        $formula = $this->calculation_formula;

        // Replace field names with numeric values (only allow known numeric fields)
        foreach ($values as $key => $val) {
            if (is_numeric($val)) {
                $formula = str_replace($key, (float)$val, $formula);
            }
        }

        // Strip anything that is not a digit, operator, dot, space, or parentheses
        $safe = preg_replace('/[^0-9\+\-\*\/\.\(\)\s]/', '', $formula);

        try {
            // phpcs:ignore
            $result = eval("return ($safe);");
            return is_numeric($result) ? (float)$result : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}