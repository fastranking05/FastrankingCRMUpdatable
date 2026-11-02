<?php

use App\Http\Controllers\Api\Contracts\ContractsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Contract Routes
|--------------------------------------------------------------------------
|
| CRUD over the contracts table (JSON documents linked to proposals and
| businesses) plus a send endpoint that emails a frontend-generated PDF.
|
*/

// Public e-sign endpoints (no auth — opened from the email signing link)
Route::prefix('contracts/public')->name('contracts.public.')->group(function () {
    Route::get('/{documentId}', [ContractsController::class, 'publicShow'])->name('show');
    Route::post('/{documentId}/sign', [ContractsController::class, 'publicSign'])->name('sign');
});

Route::middleware(['jwt.auth'])->prefix('contracts')->name('contracts.')->group(function () {
    Route::middleware('permission:Proposals,create')->group(function () {
        Route::post('/send', [ContractsController::class, 'send'])->name('send');
        Route::post('/', [ContractsController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Proposals,read')->group(function () {
        Route::get('/', [ContractsController::class, 'index'])->name('index');
        Route::get('/{id}', [ContractsController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Proposals,update')->group(function () {
        Route::put('/{id}', [ContractsController::class, 'update'])->name('update');
        Route::post('/{id}', [ContractsController::class, 'update'])->name('update.post');
    });

    Route::middleware('permission:Proposals,delete')->group(function () {
        Route::delete('/{id}', [ContractsController::class, 'destroy'])->name('destroy');
    });
});
