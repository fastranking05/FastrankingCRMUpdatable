<?php

use App\Http\Controllers\Api\Email\EmailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt.auth'])->prefix('emails')->name('emails.')->group(function () {
    Route::middleware('permission:Email,read')->group(function () {
        Route::get('/all-emails', [EmailController::class, 'getAllEmails'])->name('all-emails');
        Route::get('/my-emails', [EmailController::class, 'getMyEmails'])->name('my-emails');
        Route::get('/', [EmailController::class, 'index'])->name('index');
        Route::get('/{id}', [EmailController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Email,create')->group(function () {
        Route::post('/', [EmailController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Email,update')->group(function () {
        Route::put('/{id}', [EmailController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Email,delete')->group(function () {
        Route::delete('/{id}', [EmailController::class, 'destroy'])->name('destroy');
    });
});
