<?php

namespace App\Console\Commands;

use App\Mail\InterviewReminderMail;
use App\Models\RecruitmentInterview;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInterviewReminderEmails extends Command
{
    protected $signature = 'recruitment:send-interview-reminders
                            {--date= : Custom target interview date (default: tomorrow, format: YYYY-MM-DD)}
                            {--email=* : Custom recipient email address(es)}';

    protected $description = 'Send reminder email on the previous day for upcoming scheduled recruitment interviews to admin and HR';

    public function handle(): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::tomorrow()->startOfDay();

        $this->info("Checking scheduled interviews for date: {$targetDate->toDateString()}");

        $interviews = RecruitmentInterview::with(['candidate', 'interviewer', 'scheduler'])
            ->whereDate('scheduled_at', $targetDate->toDateString())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at', 'asc')
            ->get();

        if ($interviews->isEmpty()) {
            $this->info("No scheduled interviews found for {$targetDate->toDateString()}. No reminder email sent.");
            return Command::SUCCESS;
        }

        $recipients = $this->option('email');
        if (empty($recipients)) {
            $recipients = [
                // 'admin@saitechnosolutions.net',
                // 'hr@saitechnosolutions.net',
                'kesavaraj@saitechnosolutions.net',
            ];
        }

        $recipients = array_values(array_filter($recipients, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));

        if (empty($recipients)) {
            $this->error('No valid recipient emails configured.');
            return Command::FAILURE;
        }

        try {
            Mail::to($recipients)->send(new InterviewReminderMail($interviews, $targetDate));

            $recipientList = implode(', ', $recipients);
            $this->info("Successfully sent interview reminder for {$interviews->count()} candidate(s) to {$recipientList}");
            Log::info("Recruitment interview reminder sent for {$targetDate->toDateString()}", [
                'interviews_count' => $interviews->count(),
                'interview_ids' => $interviews->pluck('id')->all(),
                'recipients' => $recipients,
            ]);
        } catch (\Throwable $e) {
            $this->error("Failed sending interview reminder email: " . $e->getMessage());
            Log::error("Failed sending recruitment interview reminder: " . $e->getMessage(), [
                'target_date' => $targetDate->toDateString(),
                'interviews_count' => $interviews->count(),
                'recipients' => $recipients,
                'exception' => $e,
            ]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}