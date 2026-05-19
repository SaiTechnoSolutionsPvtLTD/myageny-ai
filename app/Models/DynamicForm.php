<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'public_token',
        'description',
        'success_message',
        'is_active',
        'allow_multiple_submissions',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allow_multiple_submissions' => 'boolean',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DynamicFormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(DynamicFormSubmission::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
