<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule reminder sending
Schedule::command('reminders:send')->dailyAt('09:00');

// Schedule announcement publishing
Schedule::command('announcements:publish-scheduled')->everyMinute();
