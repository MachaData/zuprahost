<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatizaciones diarias: vencimientos y recordatorios.
// Hora configurable con REMINDERS_TIME (formato HH:MM, por defecto 08:00).
Schedule::command('app:process-billing-reminders')->dailyAt(env('REMINDERS_TIME', '08:00'));

// Facturación por periodos: emite las renovaciones que vencen pronto. Corre
// antes de los recordatorios para que el aviso salga con la factura ya emitida.
Schedule::command('app:generate-period-invoices')
    ->dailyAt(env('BILLING_TIME', '07:30'))
    ->withoutOverlapping();
