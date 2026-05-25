<?php

use App\Console\Commands\SendDeliveryConfirmation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Her gün saat 14:00'da 3 gün önce kargoya verilmiş ama hala teslim olmamış
// siparişlere "ürün ulaştı mı?" mesajı (memnuniyet + lojistik takibi)
Schedule::command(SendDeliveryConfirmation::class)
    ->dailyAt('14:00')
    ->timezone('Europe/Istanbul')
    ->onOneServer()
    ->withoutOverlapping();
