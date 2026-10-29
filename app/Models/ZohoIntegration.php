<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoIntegration extends Model
{
    use HasFactory;

    protected $table = 'zoho_integrations';

    protected $fillable = [
        'company_id',
        'client_id',
        'client_secret',
        'redirect_uri',
        'region',
        'accounts_url',
        'api_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'organization_id',
        'organization_name',
        'organizations_data',
        'is_connected',
        'last_synced_at',
        'last_error',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'organizations_data' => 'array',
        'token_expires_at'   => 'datetime',
        'last_synced_at'     => 'datetime',
        'is_connected'       => 'boolean',
    ];

    /**
     * Check if token is expired or expires in next 2 minutes.
     */
    public function isTokenExpired(): bool
    {
        if (empty($this->access_token) || empty($this->token_expires_at)) {
            return true;
        }

        return Carbon::now()->addMinutes(2)->isAfter($this->token_expires_at);
    }

    public function quotations()
    {
        return $this->hasMany(ZohoQuotation::class, 'company_id', 'company_id');
    }

    /**
     * Get or create singleton integration record for current company/app.
     */
    public static function current(?int $companyId = null): self
    {
        $companyId = $companyId ?: (auth()->user()?->company_id ?? 1);

        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'region'       => 'in',
                'accounts_url' => 'https://accounts.zoho.in',
                'api_url'      => 'https://www.zohoapis.in',
                'redirect_uri' => url('/accounts/zoho/callback'),
            ]
        );
    }
}
