<?php

namespace App\Mail;

use App\Models\ProductionInitiation;
use App\Models\ProjectTestingDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectTestingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProductionInitiation $project,
        public ProjectTestingDetail $testingDetail
    ) {}

    public function envelope(): Envelope
    {
        $projectName = $this->project->leadProduct?->name 
            ?: ($this->project->product?->name ?? 'Project #' . $this->project->id);

        return new Envelope(
            subject: '🧪 Project Moved to Testing: ' . $projectName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project_testing_details',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
