<?php
// app/Models/OutcomeCategory.php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OutcomeCategory extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['name', 'company_id'];

    /**
     * One Category has many Sub Categories.
     */
    public function subCategories()
    {
        return $this->hasMany(OutcomeSubCategory::class, 'category_id');
    }
}
