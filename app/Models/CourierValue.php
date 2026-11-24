<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CourierValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'courier_id',
        'courier_form_field_id',
        'field_label',
        'field_key',
        'field_type',
        'value',
        'file_path',
        'file_name',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CourierFormField::class, 'courier_form_field_id');
    }

    public function isImage(): bool
    {
        if (! $this->file_path) {
            return false;
        }

        $extension = Str::lower(pathinfo($this->file_path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
