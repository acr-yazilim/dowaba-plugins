<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tüm demo route'ları `acr/` prefix'i altında
|--------------------------------------------------------------------------
| Yazılımcı bu demo'yu kendi Laravel projesine kopyaladığında, mevcut
| route'larıyla (örn. `/bookings`) çakışmaması için tüm URL'ler `acr/`
| ile başlar. Adapt ederken kendi prefix'iyle değiştirebilir
| (örn. `Route::prefix('hotel-pms')`).
|
| Root `/` → `acr/` redirect (kolaylık).
*/

Route::redirect('/', '/acr');

Route::prefix('acr')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/create', [BookingController::class, 'create'])->name('create');
        Route::post('/', [BookingController::class, 'store'])->name('store');
        Route::post('/{booking}/checkin', [BookingController::class, 'checkin'])->name('checkin');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
    });
});
