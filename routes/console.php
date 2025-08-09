<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily session management
Schedule::command('sessions:manage-daily')
    ->dailyAt('00:01')
    ->description('Expire old attendance sessions daily at midnight');

// Schedule old session cleanup weekly
Schedule::command('sessions:manage-daily --clean-old')
    ->weeklyOn(1, '02:00')
    ->description('Clean up old expired sessions weekly');
