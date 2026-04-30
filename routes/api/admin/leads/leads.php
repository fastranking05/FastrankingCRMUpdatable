<?php

use App\Http\Controllers\Api\Leads\LeadsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Leads Routes
|--------------------------------------------------------------------------
*/

// Free access route for business names
Route::get('/leads/business-names', [LeadsController::class, 'getAllBusinessNames'])->name('leads.business-names');

Route::middleware(['jwt.auth'])->prefix('leads')->name('leads.')->group(function () {
    Route::middleware('permission:Leads,read')->group(function () {
        Route::get('/all-leads', [LeadsController::class, 'getAllLeads'])->name('all-leads');
        Route::get('/my-leads', [LeadsController::class, 'getMyLeads'])->name('my-leads');
        Route::get('/', [LeadsController::class, 'index'])->name('index');
        Route::get('/{id}', [LeadsController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Leads,create')->group(function () {
        Route::post('/', [LeadsController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Leads,update')->group(function () {
        Route::put('/{id}', [LeadsController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Leads,delete')->group(function () {
        Route::delete('/{id}', [LeadsController::class, 'destroy'])->name('destroy');
    });
});
