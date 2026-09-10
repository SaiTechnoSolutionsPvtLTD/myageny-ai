<?php

namespace App\Mail;

use App\Models\HrmsAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HrmsAnnouncementNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public HrmsAnnouncement $announcement,
        public ?string $recipientName = null
    ) {}

    public function envelope(): Envelope
    {
        $priorityPrefix = match (strtolower((string) $this->announcement->priority)) {
            'high' => '🔴 [HIGH PRIORITY] ',
            'medium' => '🟠 ',
            'low' => '🔵 ',
            default => '📢 ',
        };

        return new Envelope(
            subject: $priorityPrefix . 'New Announcement: ' . $this->announcement->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hrms_announcement_notification',
            with: [
                'announcement' => $this->announcement,
                'recipientName' => $this->recipientName ?: 'Team Member',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
