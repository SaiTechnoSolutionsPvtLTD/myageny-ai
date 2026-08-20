<?php

namespace App\Console\Commands;

use App\Mail\HrmsCalendarTaskNotificationMail;
use App\Models\HrmsTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendHrmsCalendarNotifications extends Command
{
    protected $signature = 'hrms:send-calendar-notifications';

    protected $description = 'Send HRMS calendar task notification emails for tasks scheduled on the current date';

    public function handle(): int
    {
        $tasks = HrmsTask::with('user')
            ->whereDate('task_date', today())
            ->where('status', 'pending')
            ->where('mail_sent', false)
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No pending HRMS calendar tasks found for notification today.');
            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($tasks as $task) {
            if (!$task->user || !$task->user->email) {
                continue;
            }

            try {
                Mail::to($task->user->email)->send(new HrmsCalendarTaskNotificationMail($task));
                $task->update(['mail_sent' => true]);
                $sentCount++;

                $this->info("Sent HRMS calendar notification to {$task->user->name} ({$task->user->email}) for task #{$task->id}");
                Log::info("HrmsCalendarTaskNotificationMail sent for task #{$task->id} to user ID {$task->user_id}");
            } catch (\Throwable $e) {
                $this->error("Failed sending HRMS task notification for task #{$task->id}: " . $e->getMessage());
                Log::error("HrmsCalendarTaskNotificationMail failed for task #{$task->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed HRMS calendar notifications. Sent: {$sentCount}.");

        return Command::SUCCESS;
    }
}
