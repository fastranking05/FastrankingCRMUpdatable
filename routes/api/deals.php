<?php

use App\Http\Controllers\Api\Deals\DealsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Deals Module Routes
|--------------------------------------------------------------------------
|
| CRUD over the deals table with deal_stage filtering and business comments.
|
*/

Route::middleware(['jwt.auth'])->prefix('deals')->name('deals.')->group(function () {
    Route::middleware('permission:Deals,create')->group(function () {
        Route::get('/form/businesses', [DealsController::class, 'getFormBusinesses'])->name('form.businesses');
        Route::get('/form/businesses/{followupBusinessId}/auth-persons', [DealsController::class, 'getFormBusinessAuthPersons'])->name('form.business-auth-persons');
        Route::post('/', [DealsController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Deals,read')->group(function () {
        Route::get('/', [DealsController::class, 'index'])->name('index');
        Route::get('/filter-options', [DealsController::class, 'getFilterOptions'])->name('filter-options');
        Route::get('/{id}', [DealsController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Deals,update')->group(function () {
        Route::put('/{id}', [DealsController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Deals,delete')->group(function () {
        Route::delete('/{id}', [DealsController::class, 'destroy'])->name('destroy');
    });
});
