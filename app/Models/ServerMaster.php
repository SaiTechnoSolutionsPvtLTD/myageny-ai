<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServerMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'server_masters';

    protected $fillable = [
        'company_id',
        'server_name',
        'disk_space',
        'cpu',
        'ip_address',
        'provider_name',
        'plan_type',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    /**
     * Scope for active servers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for company filtering
     */
    public function scopeForCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;
        return $query->when($companyId, function ($q) use ($companyId) {
            $q->where(function ($sub) use ($companyId) {
                $sub->where('company_id', $companyId)->orWhereNull('company_id');
            });
        });
    }

    /**
     * User who created the record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the record
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Hosting accounts hosted on this server
     */
    public function hostings()
    {
        return $this->hasMany(HostingRecord::class, 'server_id')->orderBy('hosting_name');
    }

    /**
     * Domains hosted on this server through mapped hostings
     */
    public function domains()
    {
        return $this->hasManyThrough(
            DomainRecord::class,
            HostingRecord::class,
            'server_id',          // Foreign key on hosting_records table
            'hosting_record_id',  // Foreign key on domain_records table
            'id',                 // Local key on server_masters table
            'id'                  // Local key on hosting_records table
        )->orderBy('domain_name');
    }
}
