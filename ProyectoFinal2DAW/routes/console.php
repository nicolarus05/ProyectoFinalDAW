<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar envío de recordatorios de citas diariamente a las 10:00 AM
Schedule::command('citas:enviar-recordatorios')
    ->dailyAt('10:00')
    ->timezone('Europe/Madrid');
