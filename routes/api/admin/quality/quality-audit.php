<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quality Audit Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('quality')->name('quality.')->group(function () {
    // Quality Audit Routes
    Route::middleware('permission:Quality,read')->group(function () {
        Route::get('/audit', [QualityAuditController::class, 'index'])->name('audit.index');
        Route::get('/audit/{id}', [QualityAuditController::class, 'show'])->name('audit.show');
    });
});
