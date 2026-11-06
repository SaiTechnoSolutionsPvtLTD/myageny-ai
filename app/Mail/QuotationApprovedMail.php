<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationApprovedMail extends Mailable
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
            subject: "✅ Quotation #{$quotationNo} Approved - {$customer}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation_approved',
            with: [
                'quotation'     => $this->quotation->loadMissing(['lead', 'approver', 'createdBy', 'items.product']),
                'recipientName' => $this->recipientName ?? 'Sales Team',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
