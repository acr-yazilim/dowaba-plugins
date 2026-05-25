<?php

use App\Console\Commands\SendAppointmentReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Her gün saat 09:00'da yarınki randevulara WhatsApp hatırlatması
Schedule::command(SendAppointmentReminders::class)
    ->dailyAt('09:00')
    ->timezone('Europe/Istanbul')
    ->onOneServer()
    ->withoutOverlapping();
