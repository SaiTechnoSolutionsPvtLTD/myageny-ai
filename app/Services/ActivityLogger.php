<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Record a generic activity log entry.
     */
    public static function log(
        string $module,
        string $action,
        string $description,
        ?int $leadId = null,
        array $properties = [],
        ?User $user = null,
        ?Request $request = null
    ): ?ActivityLog {
        try {
            $req = $request ?: request();
            $actor = $user ?: Auth::user();

            $leadTitle = null;
            if ($leadId) {
                try {
                    $lead = Lead::find($leadId);
                    $leadTitle = $lead ? ($lead->company_name ?: $lead->contact_name ?: ('Lead #' . $lead->id)) : ('Lead #' . $leadId);
                } catch (\Throwable $te) {
                    $leadTitle = 'Lead #' . $leadId;
                }
            }

            return ActivityLog::create([
                'company_id' => $actor?->company_id,
                'user_id'    => $actor?->id,
                'user_name'  => $actor?->name ?: 'System',
                'user_email' => $actor?->email,
                'lead_id'    => $leadId,
                'lead_title' => $leadTitle,
                'module'     => $module,
                'action'     => $action,
                'description'=> $description,
                'url'        => $req ? $req->fullUrl() : null,
                'method'     => $req ? $req->method() : 'CLI',
                'ip_address' => $req ? $req->ip() : null,
                'user_agent' => $req ? substr((string) $req->userAgent(), 0, 500) : null,
                'properties' => ! empty($properties) ? $properties : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write activity log: ' . $e->getMessage(), [
                'module' => $module,
                'action' => $action,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Log user login.
     */
    public static function logLogin(User $user, ?Request $request = null): ?ActivityLog
    {
        $req = $request ?: request();
        $ip = $req ? $req->ip() : 'Unknown IP';
        return self::log(
            module: 'Auth',
            action: 'login',
            description: "User {$user->name} logged into the system from {$ip}",
            properties: [
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'login_time' => now()->toDateTimeString(),
            ],
            user: $user,
            request: $req
        );
    }

    /**
     * Log user logout.
     */
    public static function logLogout(User $user, ?Request $request = null): ?ActivityLog
    {
        $req = $request ?: request();
        return self::log(
            module: 'Auth',
            action: 'logout',
            description: "User {$user->name} signed out of the system",
            properties: [
                'email' => $user->email,
                'logout_time' => now()->toDateTimeString(),
            ],
            user: $user,
            request: $req
        );
    }

    /**
     * Log a lead-related activity.
     */
    public static function logLeadAction(
        Lead|int $lead,
        string $action,
        string $description,
        array $properties = [],
        ?User $user = null
    ): ?ActivityLog {
        $leadModel = $lead instanceof Lead ? $lead : Lead::find($lead);
        $leadId = $leadModel?->id ?: (is_numeric($lead) ? (int)$lead : null);
        $leadName = $leadModel ? ($leadModel->company_name ?: $leadModel->contact_name ?: ('LD-' . $leadModel->id)) : ('Lead #' . $leadId);

        return self::log(
            module: 'Leads',
            action: $action,
            description: $description,
            leadId: $leadId,
            properties: array_merge(['lead_name' => $leadName], $properties),
            user: $user
        );
    }
}
