<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RaniPettyCash extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'rani_petty_cashes';

    protected $fillable = [
        'company_id',
        'user_id',
        'entry_date',
        'amount',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount'     => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
