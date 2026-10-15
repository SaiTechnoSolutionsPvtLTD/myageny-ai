<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'item_masters';

    protected $fillable = [
        'company_id',
        'item_name',
        'description',
        'hsn_sac_code',
        'rate',
        'gst',
        'remarks',
        'status',
        'created_by',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'gst'  => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
