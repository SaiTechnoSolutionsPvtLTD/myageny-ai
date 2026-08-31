<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $interviews,
        public Carbon $targetDate
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->interviews->count();
        $dateStr = $this->targetDate->format('d M Y');

        if ($count === 1) {
            $first = $this->interviews->first();
            $candidateName = $first->candidate?->name ?: 'Candidate';
            $jobTitle = $first->candidate?->job_title ?: 'Position';
            $timeStr = $first->scheduled_at ? $first->scheduled_at->format('h:i A') : '';
            $subject = "🔔 Interview Reminder - Date: {$dateStr} | {$candidateName} ({$jobTitle})" . ($timeStr ? " at {$timeStr}" : "");
        } else {
            $subject = "🔔 Interview Reminder - Date: {$dateStr} | {$count} Candidates Scheduled";
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview_reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
