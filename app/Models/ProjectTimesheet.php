<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimesheet extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'production_initiation_id',
        'user_id',
        'timesheet_date',
        'project_delivery_date',
        'status',
        'project_type',
        'poster_count',
        'video_count',
        'committed_posters',
        'committed_videos',
        'waiting_posters',
        'waiting_videos',
        'day_closing_update',
        'attachments',
        'poster_approval_status',
        'poster_approved_by',
        'poster_approved_at',
        'poster_approval_remarks',
        'smm_synced',
        'smm_synced_count',
        'dm_post_proofs',
        'dm_published_count',
        'dm_published_at',
        'dm_published_by',
    ];

    protected $casts = [
        'timesheet_date' => 'date',
        'project_delivery_date' => 'date',
        'poster_count' => 'integer',
        'video_count' => 'integer',
        'committed_posters' => 'integer',
        'committed_videos' => 'integer',
        'waiting_posters' => 'integer',
        'waiting_videos' => 'integer',
        'attachments' => 'array',
        'poster_approved_at' => 'datetime',
        'smm_synced' => 'boolean',
        'smm_synced_count' => 'integer',
        'dm_post_proofs' => 'array',
        'dm_published_count' => 'integer',
        'dm_published_at' => 'datetime',
        'dm_published_by' => 'integer',
    ];

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

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProductionInitiation::class, 'production_initiation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posterApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_approved_by');
    }

    public function dmPublishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dm_published_by');
    }

    public function isPosterApproved(): bool
    {
        return $this->poster_approval_status === 'approved';
    }

    public function isPosterPending(): bool
    {
        return $this->poster_approval_status === 'pending';
    }

    public function isPosterRejected(): bool
    {
        return $this->poster_approval_status === 'rejected';
    }

    /**
     * Get image attachments (posters) formatted.
     */
    public function getPosterAttachmentsAttribute(): array
    {
        return array_values(array_filter($this->attachment_list, function ($item) {
            $mime = strtolower((string) ($item['mime_type'] ?? ''));
            $name = strtolower((string) ($item['name'] ?? ''));
            return str_starts_with($mime, 'image/') || preg_match('/\.(png|jpe?g|webp|gif|svg)$/i', $name);
        }));
    }

    /**
     * Get DM post proofs list formatted.
     */
    public function getDmPostProofListAttribute(): array
    {
        $list = [];
        if (! empty($this->dm_post_proofs) && is_array($this->dm_post_proofs)) {
            foreach ($this->dm_post_proofs as $proof) {
                if (is_array($proof) && ! empty($proof['path'])) {
                    $path = ltrim((string) $proof['path'], '/');
                    $fullPath = public_path($path);
                    $exists = file_exists($fullPath);
                    $size = $proof['size'] ?? ($exists ? @filesize($fullPath) : null);
                    $list[] = [
                        'path'           => $path,
                        'name'           => $proof['name'] ?? basename($path),
                        'size'           => $size,
                        'formatted_size' => $size ? $this->formatFileSize($size) : null,
                        'mime_type'      => $proof['mime_type'] ?? null,
                        'url'            => asset($path),
                        'post_url'       => $proof['post_url'] ?? null,
                        'posted_date'    => $proof['posted_date'] ?? null,
                        'uploaded_by'    => $proof['uploaded_by'] ?? null,
                        'uploaded_by_name' => $proof['uploaded_by_name'] ?? null,
                        'remarks'        => $proof['remarks'] ?? null,
                        'created_at'     => $proof['created_at'] ?? null,
                    ];
                }
            }
        }
        return $list;
    }
}

