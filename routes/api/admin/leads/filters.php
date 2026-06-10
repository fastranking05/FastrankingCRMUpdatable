<?php

use App\Http\Controllers\Api\Leads\LeadsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Leads Filter Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('leads')->name('leads.')->group(function () {
    Route::middleware('permission:Leads,read')->group(function () {
        Route::get('/filter-options', [LeadsController::class, 'getFilterOptions'])->name('filter-options');
        Route::post('/leads-filter', [LeadsController::class, 'filterLeads'])->name('leads-filter');
    });
});
