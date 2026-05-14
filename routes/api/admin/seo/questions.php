<?php

use App\Http\Controllers\Api\Seo\SeoQuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Questions Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('seo-questions')->name('seo-questions.')->group(function () {
    Route::middleware('permission:SEO,read')->group(function () {
        Route::get('/', [SeoQuestionController::class, 'index'])->name('index');
        Route::get('/active', [SeoQuestionController::class, 'getActive'])->name('active');
        Route::get('/{id}', [SeoQuestionController::class, 'show'])->name('show');
    });

    Route::middleware('permission:SEO,create')->group(function () {
        Route::post('/', [SeoQuestionController::class, 'store'])->name('store');
    });

    Route::middleware('permission:SEO,update')->group(function () {
        Route::put('/{id}', [SeoQuestionController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-status', [SeoQuestionController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::middleware('permission:SEO,delete')->group(function () {
        Route::delete('/{id}', [SeoQuestionController::class, 'destroy'])->name('destroy');
    });
});
