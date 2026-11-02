<?php

use App\Http\Controllers\Api\Proposals\ProposalsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Proposals Module Routes
|--------------------------------------------------------------------------
|
| CRUD over the proposals table linked to businesses and services.
|
*/

Route::middleware(['jwt.auth'])->prefix('proposals')->name('proposals.')->group(function () {
    Route::middleware('permission:Proposals,create')->group(function () {
        Route::get('/form/businesses', [ProposalsController::class, 'getFormBusinesses'])->name('form.businesses');
        Route::get('/form/businesses/{businessId}', [ProposalsController::class, 'getFormBusinessContext'])->name('form.business-context');
        Route::get('/form/services', [ProposalsController::class, 'getFormServices'])->name('form.services');
        Route::post('/', [ProposalsController::class, 'store'])->name('store');
        Route::post('/{id}/send', [ProposalsController::class, 'send'])->name('send');
    });

    Route::middleware('permission:Proposals,read')->group(function () {
        Route::get('/', [ProposalsController::class, 'index'])->name('index');
        Route::get('/filter-options', [ProposalsController::class, 'getFilterOptions'])->name('filter-options');
        Route::get('/{id}', [ProposalsController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Proposals,update')->group(function () {
        Route::put('/{id}', [ProposalsController::class, 'update'])->name('update');
        Route::post('/{id}', [ProposalsController::class, 'update'])->name('update.post');
    });

    Route::middleware('permission:Proposals,delete')->group(function () {
        Route::delete('/{id}', [ProposalsController::class, 'destroy'])->name('destroy');
    });
});
