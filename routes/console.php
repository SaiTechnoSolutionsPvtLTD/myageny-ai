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