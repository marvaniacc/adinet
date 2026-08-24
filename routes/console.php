<?php

use Illuminate\Support\Facades\Schedule;

// Unanswered consultation requests expire after 7 days.
Schedule::command('consultation:expire --days=7')->dailyAt('03:00');
Schedule::command('adinet:send-reminders')->hourly();
Schedule::command('adinet:backup --keep=7')->dailyAt('02:00');
