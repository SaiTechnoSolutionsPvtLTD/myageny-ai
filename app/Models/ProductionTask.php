<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionTask extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'created_by',
        'assigned_to',
        'task_date',
        'lead_id',
        'production_initiation_id',
        'product_name',
        'task_description',
        'status',
    ];

    protected $casts = [
        'task_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProductionInitiation::class, 'production_initiation_id');
    }
}
