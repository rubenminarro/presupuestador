<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios a clientes y cuentas por pagar (requiere schedule:run cada minuto).
Schedule::command('notifications:send-reminders')
    ->dailyAt(config('notifications.reminders_at', '08:00'))
    ->withoutOverlapping();
