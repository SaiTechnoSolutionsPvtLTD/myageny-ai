<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DynamicFormField extends Model
{
    use HasFactory;

    public const FIELD_TYPES = [
        'text',
        'number',
        'textarea',
        'select',
        'radio',
        'checkbox',
        'file',
    ];

    protected $fillable = [
        'dynamic_form_id',
        'label',
        'field_key',
        'field_type',
        'placeholder',
        'help_text',
        'options',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function submissionValues(): HasMany
    {
        return $this->hasMany(DynamicFormSubmissionValue::class);
    }

    public static function makeFieldKey(string $label, int $index = 0): string
    {
        $base = Str::of($label)->snake()->replaceMatches('/[^a-z0-9_]+/', '')->trim('_')->value();

        if ($base === '') {
            $base = 'field';
        }

        return $base . ($index > 0 ? '_' . $index : '');
    }
}
