<?php

use App\Console\Commands\SendCheckInReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Her gün saat 11:00'da yarın check-in yapacak misafirlere WhatsApp hatırlatması
Schedule::command(SendCheckInReminders::class)
    ->dailyAt('11:00')
    ->timezone('Europe/Istanbul')
    ->onOneServer()
    ->withoutOverlapping();
