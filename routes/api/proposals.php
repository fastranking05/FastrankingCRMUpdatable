<?php

use App\Http\Controllers\Api\Proposals\ProposalsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Proposals Module Routes
|--------------------------------------------------------------------------
|
| CRUD over the proposals table linked to deals, businesses, and services.
|
*/

Route::middleware(['jwt.auth'])->prefix('proposals')->name('proposals.')->group(function () {
    Route::middleware('permission:Deals,create')->group(function () {
        Route::get('/form/deals', [ProposalsController::class, 'getFormDeals'])->name('form.deals');
        Route::get('/form/deals/{dealId}', [ProposalsController::class, 'getFormDealContext'])->name('form.deal-context');
        Route::get('/form/services', [ProposalsController::class, 'getFormServices'])->name('form.services');
        Route::post('/', [ProposalsController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Deals,read')->group(function () {
        Route::get('/', [ProposalsController::class, 'index'])->name('index');
        Route::get('/filter-options', [ProposalsController::class, 'getFilterOptions'])->name('filter-options');
        Route::get('/{id}', [ProposalsController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Deals,update')->group(function () {
        Route::put('/{id}', [ProposalsController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Deals,delete')->group(function () {
        Route::delete('/{id}', [ProposalsController::class, 'destroy'])->name('destroy');
    });
});
