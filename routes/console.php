<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup automático diario a las 02:00 AM (CU-11 automatizado)
// Los archivos generados se nombran auto_backup_YYYY-MM-DD_HH-ii-ss.sql
// y se mantienen los últimos 30 respaldos automáticos en disco.
Schedule::command('backup:automatico')
    ->dailyAt('02:00')
    ->withoutOverlapping()         // Evita que se ejecute si ya hay una copia en progreso
    ->runInBackground()            // No bloquea otros procesos del scheduler
    ->appendOutputTo(storage_path('logs/backup_automatico.log')); // Log dedicado
