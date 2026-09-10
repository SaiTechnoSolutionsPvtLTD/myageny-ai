<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class CustomerCampaign extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'customer_campaigns';

    protected $fillable = [
        'company_id',
        'lead_id',
        'lead_product_id',
        'production_initiation_id',
        'extended_from_id',
        'campaign_name',
        'ad_account_name',
        'platform',
        'status',
        'budget_amount',
        'budget_type',
        'refund_amount',
        'start_date',
        'end_date',
        'paused_at',
        'last_resumed_at',
        'stopped_at',
        'stop_date',
        'total_paused_days',
        'pause_history',
        'remarks',
        'stop_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'stop_date' => 'date',
        'paused_at' => 'datetime',
        'last_resumed_at' => 'datetime',
        'stopped_at' => 'datetime',
        'total_paused_days' => 'integer',
        'pause_history' => 'array',
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

    public function extendedFrom(): BelongsTo
    {
        return $this->belongsTo(CustomerCampaign::class, 'extended_from_id');
    }

    public function extensions()
    {
        return $this->hasMany(CustomerCampaign::class, 'extended_from_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isExpired(): bool
    {
        if ($this->status === 'completed' || $this->status === 'expired') {
            return true;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return true;
        }

        return false;
    }

    public function isStopped(): bool
    {
        return $this->status === 'stopped' || $this->stopped_at !== null;
    }

    public function currentPausedDays(): int
    {
        if ($this->status === 'paused' && $this->paused_at) {
            return max(1, (int) $this->paused_at->diffInDays(now()));
        }

        return 0;
    }

    public function calculateRunDays(?string $asOfDate = null): int
    {
        if (!$this->start_date) {
            return 0;
        }

        $startDate = Carbon::parse($this->start_date)->startOfDay();
        
        if ($this->status === 'stopped' && $this->stop_date) {
            $endDate = Carbon::parse($this->stop_date)->startOfDay();
        } elseif ($asOfDate) {
            $endDate = Carbon::parse($asOfDate)->startOfDay();
        } elseif ($this->end_date && $this->end_date->isPast()) {
            $endDate = Carbon::parse($this->end_date)->startOfDay();
        } else {
            $endDate = Carbon::now()->startOfDay();
        }

        if ($endDate->lt($startDate)) {
            return 0;
        }

        $totalDays = $startDate->diffInDays($endDate) + 1; // inclusive of start day
        $pausedDays = (int) ($this->total_paused_days ?? 0);

        if ($this->status === 'paused' && $this->paused_at) {
            $currentPause = (int) $this->paused_at->diffInDays(now());
            $pausedDays += max(1, $currentPause);
        }

        return max(0, (int) ($totalDays - $pausedDays));
    }

    public function calculateDailyBudget(): float
    {
        $amount = (float) ($this->budget_amount ?? 0);
        if ($amount <= 0) {
            return 0.0;
        }

        $type = strtolower($this->budget_type ?? 'monthly');

        if ($type === 'daily') {
            return $amount;
        }

        if ($type === 'monthly') {
            return round($amount / 30, 2);
        }

        // Total or Custom: divide by duration if start and end date exist
        if ($this->start_date && $this->end_date) {
            $days = Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1;
            if ($days > 0) {
                return round($amount / $days, 2);
            }
        }

        return round($amount / 30, 2);
    }
}
