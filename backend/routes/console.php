<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Telescope\Telescope;

Schedule::command('horizon:snapshot')->everyFiveMinutes();

if (class_exists(Telescope::class)) {
    Schedule::command('telescope:prune --hours=24')->daily();
}
