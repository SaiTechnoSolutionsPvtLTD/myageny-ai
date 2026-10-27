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

    /**
     * Virtual attributes dynamically assigned for user-scoped views.
     * Declaring these explicitly prevents Eloquent from treating them as dirty database columns on save.
     */
    public $current_team_status = null;
    public $current_team_allocated_at = null;
    public $current_team_allocated_by = null;
    public $current_team_allocated_by_name = null;
    public $current_team_employee_user_ids = null;

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
        'lead_budget_amount',
        'budget_amount_type',
        'expected_date',
        'expected_value',
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
        'lead_budget_amount' => 'float',
        'expected_date' => 'date',
        'expected_value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            try {
                \App\Models\SmmSheet::syncFromInitiation($model);
            } catch (\Throwable $e) {
                \Log::warning('SmmSheet syncFromInitiation error: ' . $e->getMessage());
            }
        });
    }

    protected $appends = [
        'attachment_url',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        if (str_starts_with($this->attachment_path, 'uploads/') || str_starts_with($this->attachment_path, 'http://') || str_starts_with($this->attachment_path, 'https://')) {
            return asset($this->attachment_path);
        }

        return asset('storage/' . $this->attachment_path);
    }

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

    public function smmSheet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SmmSheet::class);
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
        return $this->belongsTo(User::class, 'ovp_allocated_to')->withTrashed();
    }

    public function ovpAllocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ovp_allocated_by')->withTrashed();
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

    public function testingDetails(): HasMany
    {
        return $this->hasMany(ProjectTestingDetail::class, 'production_initiation_id')->latest();
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(ProjectBug::class, 'production_initiation_id')->latest();
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(ProjectTimesheet::class);
    }

    public function productionTasks(): HasMany
    {
        return $this->hasMany(ProductionTask::class, 'production_initiation_id');
    }

    public function countReport()
    {
        return $this->hasOne(ProductionCountReport::class);
    }

    /**
     * Extract expiry / end date from custom_form_data (Priority 2 for Renewal expected date).
     */
    public function getFormExpiryDate(): ?\Carbon\Carbon
    {
        $formData = is_array($this->custom_form_data)
            ? $this->custom_form_data
            : (json_decode($this->custom_form_data ?? '[]', true) ?? []);

        if (!is_array($formData)) {
            return null;
        }

        $keywords = [
            'ovp_end_date', 'ovp end date', 'end_date', 'enddate', 'end date',
            'ovp_renewal_date', 'renewal_date', 'renewal date',
            'smm_end_date', 'smm end date', 'campaign_expiry', 'campaign expiry',
            'campaign_end', 'campaign end', 'expiry_date', 'expiry date', 'expiry', 'end'
        ];

        foreach ($formData as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fieldKey   = $field['field_name'] ?? '';
            $fieldLabel = $field['label'] ?? ($field['key'] ?? '');
            $fieldVal   = $field['value'] ?? '';

            $key   = strtolower(is_array($fieldKey) ? implode(' ', array_filter(array_map('strval', $fieldKey))) : trim((string) $fieldKey));
            $label = strtolower(is_array($fieldLabel) ? implode(' ', array_filter(array_map('strval', $fieldLabel))) : trim((string) $fieldLabel));

            if (is_array($fieldVal)) {
                $value = implode(', ', array_filter(array_map(fn($v) => is_array($v) ? json_encode($v) : (string) $v, $fieldVal)));
            } else {
                $value = trim((string) $fieldVal);
            }

            if (empty($value)) {
                continue;
            }

            foreach ($keywords as $kw) {
                if ($key === $kw || str_contains($key, $kw) || $label === $kw || str_contains($label, $kw)) {
                    try {
                        return \Carbon\Carbon::parse($value);
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolve the target expected date following priority:
     * 1. Explicit expected_date or leadProduct closure_date
     * 2. Expiry / End date from custom_form_data
     * 3. Fallback project_delivery_date
     */
    public function getResolvedExpectedDate(): ?\Carbon\Carbon
    {
        if (!empty($this->expected_date)) {
            try {
                return \Carbon\Carbon::parse($this->expected_date);
            } catch (\Throwable $e) {}
        }

        if (!empty($this->leadProduct?->closure_date)) {
            try {
                return \Carbon\Carbon::parse($this->leadProduct->closure_date);
            } catch (\Throwable $e) {}
        }

        $formExpiry = $this->getFormExpiryDate();
        if ($formExpiry) {
            return $formExpiry;
        }

        if (!empty($this->project_delivery_date)) {
            try {
                return \Carbon\Carbon::parse($this->project_delivery_date);
            } catch (\Throwable $e) {}
        }

        return null;
    }
}
