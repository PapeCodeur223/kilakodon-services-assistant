<?php

use Illuminate\Support\Facades\Schedule;

// Hébergement mutualisé : pas de worker permanent. La file `database` est vidée
// chaque minute par le scheduler (TRD §10). En local : `php artisan schedule:work`.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
