<?php

namespace App\Mail;

use App\Models\CustomerCampaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignStoppedRefundNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerCampaign $campaign,
        public ?User $stoppedByUser = null,
        public array $extraData = []
    ) {}

    public function envelope(): Envelope
    {
        $clientName = $this->campaign->lead?->company_name ?: ($this->campaign->lead?->contact_name ?? 'Client');

        return new Envelope(
            subject: '⚠️ Campaign Stopped & Refund Notice: ' . $this->campaign->campaign_name . ' (' . $clientName . ')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign_stopped_refund',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}