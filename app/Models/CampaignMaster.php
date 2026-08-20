<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignMaster extends Model
{
    protected $fillable = [ 'ad_id', 'camp_id', 'access_token', 'campaign_name','is_integrated','new_fields', 'product_id' ];

    public function assignedUsers(): HasMany
    {
        return $this->hasMany(AssignedUser::class, 'campaign_id');
    }

    public function fieldMigrations(): HasMany
    {
        return $this->hasMany(CampaignFieldMigration::class, 'campaign_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
