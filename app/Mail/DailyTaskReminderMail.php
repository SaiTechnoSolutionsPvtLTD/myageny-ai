<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyTaskReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Collection $todayReminders,
        public Collection $overdueReminders
    ) {}

    public function envelope(): Envelope
    {
        $todayCount = $this->todayReminders->count();
        $overdueCount = $this->overdueReminders->count();

        $parts = [];
        if ($todayCount > 0) {
            $parts[] = "{$todayCount} Today";
        }
        if ($overdueCount > 0) {
            $parts[] = "{$overdueCount} Overdue";
        }
        $summaryStr = implode(', ', $parts);

        return new Envelope(
            subject: "🔔 Daily Tasks & Reminders Digest ({$summaryStr}) - " . $this->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily_task_reminders',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
