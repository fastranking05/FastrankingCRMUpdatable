<?php

use App\Http\Controllers\Api\Seo\SeoFilterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Filter Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('seo')->name('seo.')->group(function () {
    Route::middleware('permission:SEO,read')->group(function () {
        // Filter endpoints
        Route::get('/filter-options', [SeoFilterController::class, 'getFilterOptions'])->name('filter-options');
        Route::post('/seo-filter', [SeoFilterController::class, 'index'])->name('seo-filter');
    });
});
