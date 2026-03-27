<?php

use App\Http\Controllers\Api\Quality\QualityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quality Control Filter Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('quality')->name('quality.')->group(function () {
    Route::middleware('permission:Quality Control,read')->group(function () {
        // Filter endpoints
        Route::post('/', [QualityController::class, 'index'])->name('index');
        Route::post('/filter-options', [QualityController::class, 'getFilterOptions'])->name('filter-options');
    });
});
