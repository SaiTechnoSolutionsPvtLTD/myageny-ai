<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DynamicFormSubmissionValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'dynamic_form_submission_id',
        'dynamic_form_field_id',
        'value',
        'file_path',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DynamicFormSubmission::class, 'dynamic_form_submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(DynamicFormField::class, 'dynamic_form_field_id');
    }
}
