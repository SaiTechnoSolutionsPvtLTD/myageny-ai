<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionInitiation extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'lead_id',
        'lead_product_id',
        'product_id',
        'department_id',
        'product_name',
        'total_working_days',
        'ui_available',
        'requirements',
        'attachment_path',
        'attachment_name',
        'workflow_snapshot',
        'custom_form_data',
        'status',
        'ovp_allocation_status',
        'ovp_allocated_to',
        'ovp_allocated_by',
        'ovp_allocated_at',
        'welcome_call_date',
        'welcome_call_time',
        'client_name',
        'company_name',
        'reviewed_at',
        'reviewed_by',
        'production_approval_status',
        'production_approval_remarks',
        'production_approval_reviewed_at',
        'production_approval_reviewed_by',
        'project_allocation_status',
        'project_allocated_at',
        'project_allocated_by',
        'project_allocated_tl_user_ids',
        'tl_employee_allocations',
        'employee_allocation_status',
        'employee_allocated_at',
        'employee_allocated_by',
        'project_delivery_date',
        'project_execution_status',
        'project_allocated_employee_user_ids',
        'initiated_by',
        'content_calendar_sheet_url',
        'content_calendar_approved',
        'content_calendar_remarks',
    ];

    protected $casts = [
        'ui_available' => 'boolean',
        'workflow_snapshot' => 'array',
        'custom_form_data' => 'array',
        'ovp_allocated_at' => 'datetime',
        'welcome_call_date' => 'date',
        'reviewed_at' => 'datetime',
        'production_approval_reviewed_at' => 'datetime',
        'project_allocated_at' => 'datetime',
        'project_delivery_date' => 'date',
        'project_allocated_tl_user_ids' => 'array',
        'tl_employee_allocations' => 'array',
        'employee_allocated_at' => 'datetime',
        'project_allocated_employee_user_ids' => 'array',
        'content_calendar_approved' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function leadProduct(): BelongsTo
    {
        return $this->belongsTo(LeadProduct::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function ovpAllocatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ovp_allocated_to');
    }

    public function ovpAllocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ovp_allocated_by');
    }

    public function productionApprovalReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'production_approval_reviewed_by');
    }

    public function projectAllocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_allocated_by');
    }

    public function employeeAllocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_allocated_by');
    }

    public function projectUpdates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->latest();
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(ProjectTimesheet::class);
    }

    public function countReport()
    {
        return $this->hasOne(ProductionCountReport::class);
    }
}
