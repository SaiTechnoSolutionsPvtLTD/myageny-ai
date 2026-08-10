<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBug extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'production_initiation_id',
        'lead_id',
        'lead_product_id',
        'description',
        'priority',
        'attachment_path',
        'attachment_original_name',
        'status',
        'created_by_user_id',
    ];

    public function productionInitiation(): BelongsTo
    {
        return $this->belongsTo(ProductionInitiation::class, 'production_initiation_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function leadProduct(): BelongsTo
    {
        return $this->belongsTo(LeadProduct::class, 'lead_product_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return asset($this->attachment_path);
    }
}
