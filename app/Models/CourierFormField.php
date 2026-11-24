<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CourierFormField extends Model
{
    use HasFactory;

    public const FIELD_TYPES = [
        'text' => 'Textbox',
        'number' => 'Number',
        'textarea' => 'Textarea',
        'date' => 'Date',
        'select' => 'Dropdown',
        'radio' => 'Radio Button',
        'checkbox' => 'Checkbox',
        'file' => 'File Upload',
    ];

    public const OPTION_FIELD_TYPES = ['select', 'radio', 'checkbox'];

    protected $fillable = [
        'courier_form_id',
        'label',
        'field_key',
        'field_type',
        'placeholder',
        'help_text',
        'options',
        'is_required',
        'show_in_list',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'show_in_list' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(CourierForm::class, 'courier_form_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CourierValue::class, 'courier_form_field_id');
    }

    public static function makeFieldKey(string $label, int $index = 0): string
    {
        $base = Str::of($label)->snake()->replaceMatches('/[^a-z0-9_]+/', '')->trim('_')->value();

        if ($base === '') {
            $base = 'field';
        }

        return $base . ($index > 0 ? '_' . $index : '');
    }

    public static function fieldTypeKeys(): array
    {
        return array_keys(self::FIELD_TYPES);
    }
}
