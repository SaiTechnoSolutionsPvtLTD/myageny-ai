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
}
