<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdBudgetRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ad_budget_requests';

    protected $fillable = [
        'company_id',
        'branch_id',
        'type',
        'ad_account_id',
        'requested_by',
        'amount',
        'selected_dates',
        'client_name',
        'project_id',
        'remarks',
        'status',
        'tl_approved_by',
        'tl_approved_at',
        'tl_remarks',
        'approved_by',
        'approved_at',
        'payment_date',
        'approved_amount',
        'accounts_remarks',
        'attachments',
    ];

    protected $casts = [
        'selected_dates'  => 'array',
        'attachments'     => 'array',
        'amount'          => 'float',
        'approved_amount' => 'float',
        'payment_date'    => 'date',
        'tl_approved_at'  => 'datetime',
        'approved_at'     => 'datetime',
    ];

    public function adAccount()
    {
        return $this->belongsTo(AdAccountMaster::class, 'ad_account_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function tlApprover()
    {
        return $this->belongsTo(User::class, 'tl_approved_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function project()
    {
        return $this->belongsTo(LeadProduct::class, 'project_id');
    }
}
