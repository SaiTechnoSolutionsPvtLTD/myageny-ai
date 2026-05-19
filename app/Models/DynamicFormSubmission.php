<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicFormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'dynamic_form_id',
        'submitted_by_ip',
        'submitted_by_user_agent',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'dynamic_form_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(DynamicFormSubmissionValue::class)->with('field');
    }
}
