<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/create', [OrderController::class, 'create'])->name('create');
    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::post('/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('mark-paid');
    Route::post('/{order}/mark-shipped', [OrderController::class, 'markShipped'])->name('mark-shipped');
    Route::post('/{order}/mark-delivered', [OrderController::class, 'markDelivered'])->name('mark-delivered');
    Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
});
