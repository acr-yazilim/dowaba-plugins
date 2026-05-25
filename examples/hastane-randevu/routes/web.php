<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tüm demo route'ları `acr/` prefix'i altında
|--------------------------------------------------------------------------
| Yazılımcı bu demo'yu kendi Laravel projesine kopyaladığında, mevcut
| route'larıyla (örn. `/appointments`) çakışmaması için tüm URL'ler
| `acr/` ile başlar. Adapt ederken kendi prefix'iyle değiştirebilir
| (örn. `Route::prefix('hospital-ai')`).
|
| Root `/` → `acr/` redirect (kolaylık).
*/

Route::redirect('/', '/acr');

Route::prefix('acr')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::get('/create', [AppointmentController::class, 'create'])->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('cancel');
    });
});
