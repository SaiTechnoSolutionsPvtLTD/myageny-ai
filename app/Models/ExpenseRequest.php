<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseRequest extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'expense_category_id',
        'amount',
        'description',
        'attachment',
        'current_step',
        'current_approver_role_id',
        'status',
        'approver_id',
        'rejection_reason',
        'actioned_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'current_step' => 'integer',
        'actioned_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function currentApproverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'current_approver_role_id');
    }
}
