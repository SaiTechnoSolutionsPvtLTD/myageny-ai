<?php

namespace App\Mail;

use App\Models\AdBudgetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdBudgetNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdBudgetRequest $adBudgetRequest,
        public string $event, // 'request_created', 'tl_approved', 'accounts_approved', 'rejected'
        public ?string $recipientName = null
    ) {}

    public function envelope(): Envelope
    {
        $typeLabel = ucfirst($this->adBudgetRequest->type);
        $subject = match ($this->event) {
            'request_created' => "📩 New {$typeLabel} Ad Budget Request (#{$this->adBudgetRequest->id}) - Pending DM TL",
            'tl_approved' => "✅ DM TL Approved {$typeLabel} Ad Budget (#{$this->adBudgetRequest->id}) - Pending HR Approval",
            'accounts_approved' => "🎉 Accounts Approved & Finalized {$typeLabel} Ad Budget (#{$this->adBudgetRequest->id})",
            'rejected' => "❌ {$typeLabel} Ad Budget Request (#{$this->adBudgetRequest->id}) Rejected",
            default => "{$typeLabel} Ad Budget Request Update (#{$this->adBudgetRequest->id})",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ad_budget_notification',
            with: [
                'adBudgetRequest' => $this->adBudgetRequest->load(['adAccount', 'requester', 'tlApprover', 'approver']),
                'event'           => $this->event,
                'recipientName'   => $this->recipientName ?? 'Team Member',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
