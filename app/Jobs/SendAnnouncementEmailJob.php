<?php

namespace App\Jobs;

use App\Mail\HrmsAnnouncementNotificationMail;
use App\Models\HrmsAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAnnouncementEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public HrmsAnnouncement $announcement,
        public string $recipientEmail,
        public ?string $recipientName = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->recipientEmail) || ! filter_var($this->recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::warning("[SendAnnouncementEmailJob] Skipped invalid email address: '{$this->recipientEmail}' for announcement #{$this->announcement->id}");
            return;
        }

        try {
            Mail::to($this->recipientEmail)->send(
                new HrmsAnnouncementNotificationMail($this->announcement, $this->recipientName)
            );

            Log::info("[SendAnnouncementEmailJob] Successfully sent announcement #{$this->announcement->id} email to {$this->recipientEmail}");
        } catch (Throwable $e) {
            Log::error("[SendAnnouncementEmailJob] Failed sending announcement #{$this->announcement->id} email to {$this->recipientEmail}: " . $e->getMessage());
            throw $e;
        }
    }
}
