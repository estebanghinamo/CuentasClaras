<?php

use App\Jobs\SendInstallmentRemindersJob;
use App\Jobs\SendOverdueDigestJob;
use App\Jobs\SendServiceRemindersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// M-13: cierre mensual del período anterior, día 1 a las 00:30 (APP_TIMEZONE).
Schedule::command('closings:run')->monthlyOn(1, '00:30');

// M-18: escaneo semanal de gastos recurrentes (además del disparo por cierre).
Schedule::command('suggestions:scan')->weeklyOn(1, '03:00');

// M-17: recordatorios diarios (§4). SendOverdueDigestJob va 5 min después de
// los otros dos a propósito, sin depender de orden estricto entre jobs.
Schedule::job(new SendServiceRemindersJob())->dailyAt('09:00');
Schedule::job(new SendInstallmentRemindersJob())->monthlyOn(1, '09:00');
Schedule::job(new SendOverdueDigestJob())->dailyAt('09:05');
