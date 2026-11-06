<?php

namespace App\Services;

use App\Mail\QuotationApprovedMail;
use App\Mail\QuotationPendingApprovalMail;
use App\Mail\QuotationRejectedMail;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuotationNotificationService
{
    /**
     * Send email to HR role users when a quotation is created and pending approval.
     */
    public static function sendPendingApprovalNotification(Quotation $quotation): void
    {
        try {
            if ($quotation->is_approved) {
                return;
            }

            $quotation->loadMissing(['lead', 'createdBy', 'items.product']);

            // Find all active HR users for the quotation's company (or all active HR if company_id is null)
            // Exclude HR Admin: notification goes ONLY to pure HR (e.g. hr@saitechnosolutions.net)
            $hrUsers = User::withoutGlobalScopes()
                ->with(['roles' => fn($rq) => $rq->withoutGlobalScopes()->with('department')])
                ->where('is_active', true)
                ->when($quotation->company_id, fn ($query) => $query->where('company_id', $quotation->company_id))
                ->get()
                ->filter(fn (User $candidate) => ($candidate->belongsToHrDepartment() || $candidate->hasHrLikeRole()) && ! $candidate->isHrAdmin())
                ->filter(fn (User $candidate) => ! empty($candidate->email) && filter_var($candidate->email, FILTER_VALIDATE_EMAIL));

            // If no company-specific HR found, fallback to any active pure HR user in the system
            if ($hrUsers->isEmpty()) {
                $hrUsers = User::withoutGlobalScopes()
                    ->with(['roles' => fn($rq) => $rq->withoutGlobalScopes()->with('department')])
                    ->where('is_active', true)
                    ->get()
                    ->filter(fn (User $candidate) => ($candidate->belongsToHrDepartment() || $candidate->hasHrLikeRole()) && ! $candidate->isHrAdmin())
                    ->filter(fn (User $candidate) => ! empty($candidate->email) && filter_var($candidate->email, FILTER_VALIDATE_EMAIL));
            }

            foreach ($hrUsers as $hrUser) {
                try {
                    Mail::to($hrUser->email)->send(new QuotationPendingApprovalMail($quotation, $hrUser->name));
                } catch (\Throwable $mailEx) {
                    Log::error("Failed to send Quotation Pending Approval email to HR {$hrUser->email}: " . $mailEx->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::error("Error in QuotationNotificationService::sendPendingApprovalNotification: " . $e->getMessage());
        }
    }

    /**
     * Send email to the creator (sales person) when quotation is approved.
     */
    public static function sendApprovedNotification(Quotation $quotation): void
    {
        try {
            $quotation->loadMissing(['lead', 'approver', 'createdBy', 'items.product']);

            $creator = $quotation->createdBy;
            if (! $creator || empty($creator->email) || ! filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            Mail::to($creator->email)->send(new QuotationApprovedMail($quotation, $creator->name));
        } catch (\Throwable $e) {
            Log::error("Error in QuotationNotificationService::sendApprovedNotification: " . $e->getMessage());
        }
    }

    /**
     * Send email to the creator (sales person) when quotation is rejected.
     */
    public static function sendRejectedNotification(Quotation $quotation): void
    {
        try {
            $quotation->loadMissing(['lead', 'rejecter', 'createdBy', 'items.product']);

            $creator = $quotation->createdBy;
            if (! $creator || empty($creator->email) || ! filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            Mail::to($creator->email)->send(new QuotationRejectedMail($quotation, $creator->name));
        } catch (\Throwable $e) {
            Log::error("Error in QuotationNotificationService::sendRejectedNotification: " . $e->getMessage());
        }
    }
}
