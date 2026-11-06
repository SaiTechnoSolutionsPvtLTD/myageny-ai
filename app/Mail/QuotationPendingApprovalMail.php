<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public ?string $recipientName = null
    ) {}

    public function envelope(): Envelope
    {
        $quotationNo = $this->quotation->quotation_no ?? $this->quotation->id;
        $customer = $this->quotation->lead?->contact_name ?: ($this->quotation->lead?->company_name ?: 'Customer');

        return new Envelope(
            subject: "📩 Quotation #{$quotationNo} Pending Approval - {$customer}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation_pending_approval',
            with: [
                'quotation'     => $this->quotation->loadMissing(['lead', 'createdBy', 'items.product']),
                'recipientName' => $this->recipientName ?? 'HR Team',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
