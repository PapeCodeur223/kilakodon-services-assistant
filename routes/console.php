<?php

use App\Domain\Brief\Support\ReferenceGenerator;
use Illuminate\Support\Facades\Schedule;

// Hébergement mutualisé : pas de worker permanent. La file `database` est vidée
// chaque minute par le scheduler (TRD §10). En local : `php artisan schedule:work`.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('sitemap:generate')->dailyAt('03:00');

// Sessions de diagnostic anonymes de plus de 30 jours.
Schedule::command('kh:diagnostic-purge')->dailyAt('02:00');

// Compteurs de références KLK de l'année en cours et de la suivante, créés à l'avance.
Schedule::call(fn () => app(ReferenceGenerator::class)->prepare())
    ->name('kh:sequences-prepare')
    ->dailyAt('01:00');
