<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CstDailyClosing extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'cst_daily_closings';

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'closing_date',
        'monthly_target',
        'today_revenue',
        'till_now_achieved',
        'completed_percentage',
        'current_week_meetings',
        'total_allocated_accounts',
        'today_added_accounts',
        'welcome_call_pending_count',
        'remarks',
        'is_on_leave_tomorrow',
        'tomorrow_plans',
        'plan_for_tomorrow',
        'attachments',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'closing_date'               => 'date',
        'monthly_target'             => 'float',
        'today_revenue'              => 'float',
        'till_now_achieved'          => 'float',
        'completed_percentage'       => 'float',
        'current_week_meetings'      => 'integer',
        'total_allocated_accounts'   => 'integer',
        'today_added_accounts'       => 'integer',
        'welcome_call_pending_count' => 'integer',
        'is_on_leave_tomorrow'       => 'boolean',
        'tomorrow_plans'             => 'array',
        'attachments'                => 'array',
        'reviewed_at'                => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withoutGlobalScopes();
    }

    /**
     * Get total expected value for tomorrow's plans.
     */
    public function getTotalExpectedValueAttribute(): float
    {
        if (empty($this->tomorrow_plans) || !is_array($this->tomorrow_plans)) {
            return 0.0;
        }
        return (float) collect($this->tomorrow_plans)->sum(function ($item) {
            return floatval($item['expected_value'] ?? 0);
        });
    }

    /**
     * Get all attachments formatted with url, name, size, formatted_size.
     */
    public function getAttachmentListAttribute(): array
    {
        $list = [];

        if (! empty($this->attachments) && is_array($this->attachments)) {
            foreach ($this->attachments as $item) {
                if (is_array($item) && ! empty($item['path'])) {
                    $path = ltrim((string) $item['path'], '/');
                    $fullPath = public_path($path);
                    $exists = file_exists($fullPath);
                    $size = $item['size'] ?? ($exists ? @filesize($fullPath) : null);
                    $list[] = [
                        'path' => $path,
                        'name' => $item['name'] ?? basename($path),
                        'size' => $size,
                        'formatted_size' => $size ? $this->formatFileSize($size) : null,
                        'mime_type' => $item['mime_type'] ?? null,
                        'url'  => asset($path),
                    ];
                } elseif (is_string($item) && ! empty($item)) {
                    $path = ltrim($item, '/');
                    $fullPath = public_path($path);
                    $exists = file_exists($fullPath);
                    $size = $exists ? @filesize($fullPath) : null;
                    $list[] = [
                        'path' => $path,
                        'name' => basename($path),
                        'size' => $size,
                        'formatted_size' => $size ? $this->formatFileSize($size) : null,
                        'mime_type' => null,
                        'url'  => asset($path),
                    ];
                }
            }
        }

        return $list;
    }

    protected function formatFileSize($bytes): string
    {
        $bytes = (float) $bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
