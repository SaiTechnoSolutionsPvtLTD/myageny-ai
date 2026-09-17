<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBug extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'production_initiation_id',
        'lead_id',
        'lead_product_id',
        'description',
        'priority',
        'attachment_path',
        'attachment_original_name',
        'attachments',
        'status',
        'developer_status',
        'tester_status',
        'developer_remarks',
        'tester_remarks',
        'latest_remarks',
        'status_history',
        'reopen_count',
        'created_by_user_id',
    ];

    protected $casts = [
        'attachments' => 'array',
        'status_history' => 'array',
        'reopen_count' => 'integer',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return asset($this->attachment_path);
    }

    /**
     * Get all attachments as normalized array of ['path' => ..., 'name' => ...]
     */
    public function getAttachmentListAttribute(): array
    {
        $list = [];

        if (! empty($this->attachments) && is_array($this->attachments)) {
            foreach ($this->attachments as $item) {
                if (is_array($item) && ! empty($item['path'])) {
                    $list[] = [
                        'path' => $item['path'],
                        'name' => $item['name'] ?? basename($item['path']),
                        'url'  => asset($item['path']),
                    ];
                } elseif (is_string($item) && ! empty($item)) {
                    $list[] = [
                        'path' => $item,
                        'name' => basename($item),
                        'url'  => asset($item),
                    ];
                }
            }
        }

        // Fallback to legacy single attachment if attachments array was empty
        if (empty($list) && ! empty($this->attachment_path)) {
            $list[] = [
                'path' => $this->attachment_path,
                'name' => $this->attachment_original_name ?: basename($this->attachment_path),
                'url'  => asset($this->attachment_path),
            ];
        }

        return $list;
    }

    /**
     * Record a new status transition with mandatory remarks into history.
     */
    public function addStatusHistory($user, string $roleType, string $fromStatus, string $toStatus, string $remarks): void
    {
        $history = is_array($this->status_history) ? $this->status_history : [];
        $entry = [
            'id' => count($history) + 1,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'User',
            'role_type' => $roleType, // 'developer' or 'tester'
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'remarks' => trim($remarks),
            'created_at' => now()->format('Y-m-d H:i:s'),
            'formatted_date' => now()->format('d M Y, h:i A'),
        ];
        $history[] = $entry;

        $this->status_history = $history;
        $this->latest_remarks = trim($remarks);
        if ($roleType === 'developer') {
            $this->developer_remarks = trim($remarks);
        } else {
            $this->tester_remarks = trim($remarks);
        }
    }
}
