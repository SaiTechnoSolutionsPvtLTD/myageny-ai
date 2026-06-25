<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionCountReport extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'lead_id',
        'lead_product_id',
        'production_initiation_id',
        'product_id',
        'department_id',
        'poster_count',
        'video_count',
        'allocated_team_user_ids',
        'allocated_user_ids',
        'allocated_by',
        'allocated_at',
        'status',
    ];

    protected $casts = [
        'poster_count' => 'integer',
        'video_count' => 'integer',
        'allocated_team_user_ids' => 'array',
        'allocated_user_ids' => 'array',
        'allocated_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function leadProduct(): BelongsTo
    {
        return $this->belongsTo(LeadProduct::class);
    }

    public function productionInitiation(): BelongsTo
    {
        return $this->belongsTo(ProductionInitiation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
