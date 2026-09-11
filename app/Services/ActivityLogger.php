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

    /**
     * Log a call update action (create, update, delete).
     */
    public static function logCallUpdate(
        string $action,
        \App\Models\LeadCallUpdate|array $callData,
        Lead|int|null $lead = null,
        ?User $user = null,
        array $extraProperties = []
    ): ?ActivityLog {
        $actor = $user ?: Auth::user();
        $userName = $actor?->name ?: 'System';
        $userId = $actor?->id;

        $leadId = $lead instanceof Lead ? $lead->id : ($lead ?: ($callData instanceof \App\Models\LeadCallUpdate ? $callData->lead_id : ($callData['lead_id'] ?? null)));
        $leadModel = null;
        if ($lead instanceof Lead) {
            $leadModel = $lead;
        } elseif ($leadId) {
            try {
                $leadModel = Lead::find($leadId);
            } catch (\Throwable) {
                $leadModel = null;
            }
        }
        $leadName = $leadModel ? ($leadModel->company_name ?: $leadModel->contact_name ?: ('LD-' . $leadModel->id)) : ('Lead #' . $leadId);

        $outcome = $callData instanceof \App\Models\LeadCallUpdate ? ($callData->outcome_label ?: $callData->outcome) : ($callData['outcome_label'] ?? $callData['outcome'] ?? 'N/A');
        $callType = $callData instanceof \App\Models\LeadCallUpdate ? ($callData->call_type_label ?: $callData->call_type) : ($callData['call_type'] ?? 'Outgoing');
        $nextFollowUp = $callData instanceof \App\Models\LeadCallUpdate ? $callData->next_follow_up?->format('Y-m-d') : ($callData['next_follow_up'] ?? null);

        $actionVerb = match (strtolower($action)) {
            'create', 'created', 'store', 'add' => 'created',
            'update', 'updated', 'edit' => 'updated',
            'delete', 'deleted', 'destroy', 'remove' => 'deleted',
            default => $action,
        };

        $description = match ($actionVerb) {
            'created' => "{$userName} added a call update ({$outcome}) for {$leadName}" . ($nextFollowUp ? " (Next Follow-up: {$nextFollowUp})" : ""),
            'updated' => "{$userName} updated call update ({$outcome}) for {$leadName}" . ($nextFollowUp ? " (Next Follow-up: {$nextFollowUp})" : ""),
            'deleted' => "{$userName} deleted call update ({$outcome}) for {$leadName}",
            default => "{$userName} performed {$action} on call update for {$leadName}",
        };

        $properties = array_merge([
            'call_update_id'   => $callData instanceof \App\Models\LeadCallUpdate ? $callData->id : ($callData['id'] ?? null),
            'user_id'          => $userId,
            'user_name'        => $userName,
            'lead_id'          => $leadId,
            'lead_name'        => $leadName,
            'outcome'          => $outcome,
            'call_type'        => $callType,
            'next_follow_up'   => $nextFollowUp,
            'followup_time'    => $callData instanceof \App\Models\LeadCallUpdate ? $callData->followup_time : ($callData['followup_time'] ?? null),
            'notes'            => $callData instanceof \App\Models\LeadCallUpdate ? $callData->notes : ($callData['notes'] ?? null),
            'called_at'        => $callData instanceof \App\Models\LeadCallUpdate ? $callData->called_at?->toDateTimeString() : ($callData['called_at'] ?? null),
        ], $extraProperties);

        return self::log(
            module: 'Call Updates',
            action: $actionVerb,
            description: $description,
            leadId: $leadId,
            properties: $properties,
            user: $actor
        );
    }

    /**
     * Log a payment action (create, update, delete).
     */
    public static function logPayment(
        string $action,
        \Illuminate\Database\Eloquent\Model|array $paymentData,
        Lead|int|null $lead = null,
        ?User $user = null,
        array $extraProperties = []
    ): ?ActivityLog {
        $actor = $user ?: Auth::user();
        $userName = $actor?->name ?: 'System';
        $userId = $actor?->id;

        $leadId = $lead instanceof Lead ? $lead->id : ($lead ?: ($paymentData instanceof \Illuminate\Database\Eloquent\Model ? $paymentData->lead_id : ($paymentData['lead_id'] ?? null)));
        $leadModel = null;
        if ($lead instanceof Lead) {
            $leadModel = $lead;
        } elseif ($leadId) {
            try {
                $leadModel = Lead::find($leadId);
            } catch (\Throwable) {
                $leadModel = null;
            }
        }
        $leadName = $leadModel ? ($leadModel->company_name ?: $leadModel->contact_name ?: ('LD-' . $leadModel->id)) : ('Lead #' . $leadId);

        $amount = $paymentData instanceof \Illuminate\Database\Eloquent\Model ? (float)$paymentData->amount : (float)($paymentData['amount'] ?? 0);
        $formattedAmount = '₹' . number_format($amount, 2);
        $paymentMode = $paymentData instanceof \Illuminate\Database\Eloquent\Model ? ($paymentData->mode_label ?: $paymentData->payment_mode) : ($paymentData['payment_mode'] ?? 'N/A');
        $paymentDate = $paymentData instanceof \Illuminate\Database\Eloquent\Model ? ($paymentData->payment_date?->format('Y-m-d') ?: $paymentData->payment_date) : ($paymentData['payment_date'] ?? null);
        $refNumber = $paymentData instanceof \Illuminate\Database\Eloquent\Model ? $paymentData->reference_number : ($paymentData['reference_number'] ?? null);
        $productName = $paymentData instanceof \Illuminate\Database\Eloquent\Model && isset($paymentData->leadProduct) && $paymentData->leadProduct ? $paymentData->leadProduct->product_name : ($extraProperties['product_name'] ?? null);

        $actionVerb = match (strtolower($action)) {
            'create', 'created', 'store', 'add' => 'created',
            'update', 'updated', 'edit' => 'updated',
            'delete', 'deleted', 'destroy', 'remove' => 'deleted',
            default => $action,
        };

        $productSuffix = $productName ? " for product '{$productName}'" : "";

        $description = match ($actionVerb) {
            'created' => "{$userName} recorded payment of {$formattedAmount} via {$paymentMode}{$productSuffix} for {$leadName}",
            'updated' => "{$userName} updated payment of {$formattedAmount}{$productSuffix} for {$leadName}",
            'deleted' => "{$userName} deleted payment of {$formattedAmount} via {$paymentMode}{$productSuffix} for {$leadName}",
            default => "{$userName} performed {$action} on payment of {$formattedAmount} for {$leadName}",
        };

        $properties = array_merge([
            'payment_id'       => $paymentData instanceof \Illuminate\Database\Eloquent\Model ? $paymentData->id : ($paymentData['id'] ?? null),
            'lead_product_id'  => $paymentData instanceof \Illuminate\Database\Eloquent\Model ? $paymentData->lead_product_id : ($paymentData['lead_product_id'] ?? null),
            'product_name'     => $productName,
            'user_id'          => $userId,
            'user_name'        => $userName,
            'lead_id'          => $leadId,
            'lead_name'        => $leadName,
            'amount'           => $amount,
            'formatted_amount' => $formattedAmount,
            'payment_mode'     => $paymentMode,
            'payment_date'     => $paymentDate,
            'reference_number' => $refNumber,
            'notes'            => $paymentData instanceof \Illuminate\Database\Eloquent\Model ? $paymentData->notes : ($paymentData['notes'] ?? null),
        ], $extraProperties);

        return self::log(
            module: 'Payments',
            action: $actionVerb,
            description: $description,
            leadId: $leadId,
            properties: $properties,
            user: $actor
        );
    }
}
