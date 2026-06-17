<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionWorkflowMapping extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'department_id',
        'workflow_data',
    ];

    protected $casts = [
        'workflow_data' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
