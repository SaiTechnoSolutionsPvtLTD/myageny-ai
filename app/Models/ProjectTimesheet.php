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
}
