<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'subject',
        'message',
        'attachment_path',
        'created_by',
        'to_user_id',
        'status',
        'remark',
    ];

    protected $appends = [
        'attachment_url',
    ];

    /**
     * Get the user who created the support ticket.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user to whom the ticket is assigned.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Get the full public URL for the ticket attachment.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }

        if (str_starts_with($this->attachment_path, 'http://') || str_starts_with($this->attachment_path, 'https://')) {
            return $this->attachment_path;
        }

        if (str_starts_with($this->attachment_path, 'uploads/')) {
            return asset($this->attachment_path);
        }

        return asset('storage/' . $this->attachment_path);
    }
}
