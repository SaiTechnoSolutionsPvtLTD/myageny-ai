<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaySalesTrackerCategory extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'day_sales_tracker_categories';

    protected $fillable = [
        'company_id',
        'name',
    ];
}
