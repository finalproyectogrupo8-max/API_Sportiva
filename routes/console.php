<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired', ['--hours=24'])->daily();

// TIZ-32: requisitos de habilitación vencidos, en todos los clubes.
Schedule::command('club:expire-requirements')->dailyAt('03:00');

// TIZ-27: alertas automáticas del recorrido del partido.
Schedule::command('alerts:run')->dailyAt('06:00');
