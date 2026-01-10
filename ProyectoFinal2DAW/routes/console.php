<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar envío de recordatorios de citas diariamente a las 10:00 AM
Schedule::command('citas:enviar-recordatorios')
    ->dailyAt('10:00')
    ->timezone('Europe/Madrid')
    ->onSuccess(function () {
        Log::info('✅ Recordatorios de citas enviados correctamente', [
            'timestamp' => now()->toDateTimeString()
        ]);
    })
    ->onFailure(function () {
        Log::error('❌ Error al enviar recordatorios de citas', [
            'timestamp' => now()->toDateTimeString()
        ]);
    });

// Programar backups automáticos diariamente a las 2:00 AM
Schedule::command('backup:run')
    ->daily()
    ->at('02:00')
    ->when(fn() => env('BACKUP_ENABLED', true));

// Limpiar backups antiguos diariamente a las 3:00 AM
Schedule::command('backup:clean')
    ->daily()
    ->at('03:00')
    ->when(fn() => env('BACKUP_ENABLED', true));

// Monitorear salud de backups diariamente a las 4:00 AM
Schedule::command('backup:monitor')
    ->daily()
    ->at('04:00')
    ->when(fn() => env('BACKUP_ENABLED', true));
