<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTestingDetail extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'production_initiation_id',
        'lead_id',
        'lead_product_id',
        'testing_link',
        'credentials',
        'notes',
        'status',
        'moved_by_user_id',
        'testing_tl_id',
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

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by_user_id');
    }

    public function testingTl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'testing_tl_id');
    }
}
