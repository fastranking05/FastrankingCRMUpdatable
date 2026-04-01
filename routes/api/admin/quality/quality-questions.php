<?php

use App\Http\Controllers\Api\Quality\QualityQuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quality Questions Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('quality-questions')->name('quality-questions.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [QualityQuestionController::class, 'index'])->name('index');
        Route::get('/{id}', [QualityQuestionController::class, 'show'])->name('show');
        Route::get('/active', [QualityQuestionController::class, 'getActive'])->name('active');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [QualityQuestionController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [QualityQuestionController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-status', [QualityQuestionController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [QualityQuestionController::class, 'destroy'])->name('destroy');
    });
});
