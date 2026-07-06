<?php
// app/Models/OutcomeSubCategory.php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OutcomeSubCategory extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['category_id', 'name', 'company_id'];

    /**
     * Sub Category belongs to one Category.
     */
    public function category()
    {
        return $this->belongsTo(OutcomeCategory::class, 'category_id');
    }
}
