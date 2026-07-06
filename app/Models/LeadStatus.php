<?php
// app/Models/LeadStatus.php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadStatus extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['name', 'company_id'];
}
