<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_code',
        'asset_name',
        'asset_category',
        'brand',
        'model_name',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'vendor_name',
        'invoice_number',
        'warranty_expiry_date',
        'asset_status',
        'assigned_employee_id',
        'assigned_date',
        'location',
        'condition_notes',
        'description',
        'custom_field_values',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_expiry_date' => 'date',
            'assigned_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'custom_field_values' => 'array',
        ];
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(EmployeeOnboarding::class, 'assigned_employee_id');
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
