<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;



Schedule::command('app:facebook-lead-integration')
    ->everyMinute();

Schedule::command('leads:sync-branch')
    ->everyTenMinutes();


Schedule::command('app:expire-companies')
    ->hourly();

Schedule::command('app:send-daily-reminder-emails')
    ->dailyAt('09:40');

Schedule::command('recruitment:send-interview-reminders')
    ->dailyAt('09:00');

Schedule::command('hrms:send-calendar-notifications')
    ->dailyAt('09:30');

Schedule::command('expenses:generate-recurring')
    ->dailyAt('00:05');

// Auto-sync Zoho Books (Quotations, Invoices, Expenses) every 20 minutes (GET sync only, strictly no auto-posting)
Schedule::command('zoho:auto-sync')
    ->cron('*/20 * * * *')
    ->withoutOverlapping()
    ->runInBackground();
