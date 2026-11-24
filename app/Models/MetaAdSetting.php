<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAdSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'ad_account_id',
        'account_label',
        'access_token',
        'graph_version',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
