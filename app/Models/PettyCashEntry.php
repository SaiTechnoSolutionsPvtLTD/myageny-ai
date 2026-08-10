<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashEntry extends Model
{
    use BelongsToCompany;

    protected $table = 'petty_cash_entries';

    protected $fillable = [
        'company_id',
        'branch_id',
        'entry_date',
        'voucher_no',
        'name',
        'particulars',
        'type',
        'amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $tableRelation = $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForBranch(Builder $query, int|string $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }
}
