<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected static ?array $sourceOptionsCache = null;
    protected static ?array $statusOptionsCache = null;

    protected $fillable = [
        'company_name',
        'contact_name',
        'lead_date',
        'mobile_number',
        'email',
        'lead_source',
        'lead_status',
        'lead_source_id',
        'lead_status_id',
        'product_name',
        'product_id',
        'priority',
        'deal_value',
        'remarks',
        'company_id',
        'branch_id',
        'assigned_to',
        'created_by',
        'facebook_lead_id',
        'facebook_campaign_id',
        'facebook_payload',
        'customer_support_tl_id',
        'customer_support_executive_id',
    ];

    protected $casts = [
        'lead_date'      => 'date',
        'deal_value'     => 'decimal:2',
        'facebook_payload' => 'array',
        'lead_source_id' => 'integer',
        'lead_status_id' => 'integer',
    ];

    // ── Constants ──────────────────────────────────────────────────

    const PRIORITIES = [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
    ];

    const STATUS_COLORS = [
        'new'         => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'],
        'qualified'   => ['bg' => '#f0fdfa', 'text' => '#0f766e', 'border' => '#99f6e4'],
        'proposal'    => ['bg' => '#faf5ff', 'text' => '#7c3aed', 'border' => '#e9d5ff'],
        'negotiation' => ['bg' => '#fffbeb', 'text' => '#b45309', 'border' => '#fde68a'],
        'won'         => ['bg' => '#f0fdf4', 'text' => '#16a34a', 'border' => '#bbf7d0'],
        'lost'        => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
    ];

    const PRIORITY_COLORS = [
        'low'    => ['bg' => '#f0fdf4', 'text' => '#16a34a', 'border' => '#bbf7d0'],
        'medium' => ['bg' => '#fffbeb', 'text' => '#b45309', 'border' => '#fde68a'],
        'high'   => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class, 'lead_status_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customerSupportTl()
    {
        return $this->belongsTo(User::class, 'customer_support_tl_id');
    }



    public function customerSupportExecutive()
    {
        return $this->belongsTo(User::class, 'customer_support_executive_id');
    }

    /**
     * Returns [id => name] map for all lead sources.
     */
    public static function sourceOptions(): array
    {
        if (static::$sourceOptionsCache !== null) {
            return static::$sourceOptionsCache;
        }

        return static::$sourceOptionsCache = LeadSource::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Returns array of valid lead source IDs.
     */
    public static function sourceKeys(): array
    {
        return array_keys(static::sourceOptions());
    }

    /**
     * Returns [id => name] map for all lead statuses.
     */
    public static function statusOptions(): array
    {
        if (static::$statusOptionsCache !== null) {
            return static::$statusOptionsCache;
        }

        return static::$statusOptionsCache = LeadStatus::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Returns array of valid lead status IDs.
     */
    public static function statusKeys(): array
    {
        return array_keys(static::statusOptions());
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeForUser($query, $userId)
    {
        return $query->where('assigned_to', $userId)
            ->orWhere('created_by', $userId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getStatusColorAttribute(): array
    {
        // Prefer relationship name, fall back to legacy string column
        $statusName = $this->relationLoaded('leadStatus')
            ? ($this->leadStatus?->name ?? $this->lead_status)
            : ($this->lead_status);

        $statusKey = str((string) $statusName)->lower()->replace(' ', '_')->value();

        return self::STATUS_COLORS[$statusKey] ?? ['bg' => '#f5f4f6', 'text' => '#7c7c7c', 'border' => '#e1dee3'];
    }

    public function getPriorityColorAttribute(): array
    {
        return self::PRIORITY_COLORS[$this->priority] ?? ['bg' => '#f5f4f6', 'text' => '#7c7c7c', 'border' => '#e1dee3'];
    }



    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst($this->priority);
    }

    public function getSourceLabelAttribute(): string
    {
        // Use relation if loaded, otherwise fall back to sourceOptions map or raw string
        if ($this->relationLoaded('leadSource') && $this->leadSource) {
            return $this->leadSource->name;
        }

        if ($this->lead_source_id) {
            return static::sourceOptions()[$this->lead_source_id]
                ?? $this->lead_source
                ?? (string) $this->lead_source_id;
        }

        return $this->lead_source ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->relationLoaded('leadStatus') && $this->leadStatus) {
            return $this->leadStatus->name;
        }

        if ($this->lead_status_id) {
            return static::statusOptions()[$this->lead_status_id]
                ?? $this->lead_status
                ?? (string) $this->lead_status_id;
        }

        return $this->lead_status ?? '';
    }

    public function getFormattedDealValueAttribute(): string
    {
        if (!$this->deal_value) return '—';
        return '₹' . number_format($this->deal_value, 2);
    }

    // ── Auto-set created_by ───────────────────────────────────────

    protected static function booted(): void
    {
        static::addGlobalScope('branch', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (! auth()->hasUser()) {
                return;
            }

            $user = auth()->user();
            if ($user && $user->isBranchAdmin()) {
                $branchIds = $user->getMyBranchIds();
                if (!empty($branchIds)) {
                    $builder->whereIn($builder->getModel()->getTable() . '.branch_id', $branchIds);
                }
            }
        });

        static::creating(function (Lead $lead) {
            if (!$lead->created_by && auth()->check()) {
                $lead->created_by = auth()->id();
            }

            if (!$lead->company_id && auth()->check()) {
                $lead->company_id = auth()->user()?->company_id;
            }

            if (!$lead->lead_date) {
                $lead->lead_date = now()->toDateString();
            }
        });
    }

    public function callUpdates()
    {
        return $this->hasMany(\App\Models\LeadCallUpdate::class)->latest('called_at');
    }

    public function reminders()
    {
        return $this->hasMany(\App\Models\LeadReminder::class)->orderBy('remind_at');
    }

    public function products()
    {
        return $this->hasMany(\App\Models\LeadProduct::class);
    }

    public function quotations()
    {
        return $this->hasMany(\App\Models\Quotation::class)->latest();
    }

    public function customFieldValues()
    {
        return $this->hasMany(LeadFieldValue::class);
    }

    public function show(Lead $lead)
    {
        $lead->load([
            'branch',
            'assignedTo',
            'createdBy',
            'callUpdates.user',
            'reminders.user',
            'products',
            'quotations.items',
            'quotations.createdBy',
        ]);

        return view('leads.show', compact('lead'));
    }
}
