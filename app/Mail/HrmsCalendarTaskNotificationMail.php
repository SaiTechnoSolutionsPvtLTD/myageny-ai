<?php

namespace App\Mail;

use App\Models\HrmsTask;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HrmsCalendarTaskNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public HrmsTask $hrmsTask
    ) {}

    public function envelope(): Envelope
    {
        $dateStr = $this->hrmsTask->task_date ? $this->hrmsTask->task_date->format('d M Y') : 'Today';
        $timeStr = $this->hrmsTask->task_time ? date('h:i A', strtotime($this->hrmsTask->task_time)) : 'All Day';

        return new Envelope(
            subject: "📅 HRMS Calendar Task Reminder: {$dateStr} at {$timeStr}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hrms_calendar_task',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
