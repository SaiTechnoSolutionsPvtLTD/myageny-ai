<?php

namespace App\Console\Commands;

use App\Mail\DailyTaskReminderMail;
use App\Models\LeadReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyReminderEmails extends Command
{
    protected $signature = 'app:send-daily-reminder-emails {--user= : Optional target user ID}';

    protected $description = 'Send daily task & reminder email digest to active Sales department users with today or overdue reminders';

    public function handle(): int
    {
        $targetUserId = $this->option('user');

        $query = User::where('is_active', true);

        if ($targetUserId) {
            $query->where('id', (int) $targetUserId);
        }

        $users = $query->get()->filter(function (User $user) {
            return $user->belongsToSalesDepartment()
                || $user->hasSalesLikeRole()
                || $user->can('leads.view');
        });

        if ($users->isEmpty()) {
            $this->info('No active Sales users found for reminder email processing.');
            return Command::SUCCESS;
        }

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            $userConstraint = function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('lead', fn ($lq) => $lq->where('assigned_to', $user->id));
            };

            $baseReminders = LeadReminder::with(['lead:id,company_name,contact_name,mobile_number'])
                ->where('is_completed', false)
                ->where($userConstraint);

            $todayReminders = (clone $baseReminders)
                ->whereDate('remind_at', today())
                ->orderBy('remind_at', 'asc')
                ->get();

            $overdueReminders = (clone $baseReminders)
                ->whereDate('remind_at', '<', today())
                ->orderBy('remind_at', 'asc')
                ->get();

            // Send mail ONLY if user has at least 1 today reminder or 1 overdue reminder
            if ($todayReminders->isNotEmpty() || $overdueReminders->isNotEmpty()) {
                try {
                    Mail::to($user->email)->send(new DailyTaskReminderMail($user, $todayReminders, $overdueReminders));
                    $sentCount++;

                    $this->info("Sent reminder digest to {$user->name} ({$user->email}) - Today: {$todayReminders->count()}, Overdue: {$overdueReminders->count()}");
                    Log::info("DailyTaskReminderMail sent to user ID {$user->id} ({$user->email})", [
                        'today_count' => $todayReminders->count(),
                        'overdue_count' => $overdueReminders->count(),
                    ]);
                } catch (\Throwable $e) {
                    $this->error("Failed sending email to {$user->email}: " . $e->getMessage());
                    Log::error("DailyTaskReminderMail failed for user ID {$user->id}: " . $e->getMessage());
                }
            } else {
                $skippedCount++;
                $this->line("Skipped {$user->name} ({$user->email}) - 0 reminders today/overdue.");
            }
        }

        $this->info("Completed daily reminder email dispatch. Sent: {$sentCount}, Skipped: {$skippedCount}.");

        return Command::SUCCESS;
    }
}
