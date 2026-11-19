<?php

use App\Http\Controllers\Api\Billing\BillingSetupsController;
use App\Http\Controllers\Api\Billing\InvoicesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Billing Routes
|--------------------------------------------------------------------------
|
| Billing setups (per signed contract) and the invoices auto-generated from
| them. Uses the Invoice module permissions. Daily `invoices:generate`
| command creates due invoices and marks overdue ones.
|
*/

Route::middleware(['jwt.auth'])->prefix('billing')->name('billing.')->group(function () {

    // Read — setups, form helpers, invoices
    Route::middleware('permission:Invoice,read')->group(function () {
        Route::get('/setups/form/contracts', [BillingSetupsController::class, 'getFormContracts'])->name('setups.form.contracts');
        Route::get('/setups/form/contracts/{contractId}', [BillingSetupsController::class, 'getFormContractContext'])->name('setups.form.contract');
        Route::get('/setups', [BillingSetupsController::class, 'index'])->name('setups.index');
        Route::get('/setups/{id}', [BillingSetupsController::class, 'show'])->name('setups.show');

        Route::get('/invoices/filter-options', [InvoicesController::class, 'filterOptions'])->name('invoices.filter-options');
        Route::get('/invoices', [InvoicesController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{id}', [InvoicesController::class, 'show'])->name('invoices.show');
    });

    // Create — setups and invoice email
    Route::middleware('permission:Invoice,create')->group(function () {
        Route::post('/setups', [BillingSetupsController::class, 'store'])->name('setups.store');
        Route::post('/invoices/{id}/send', [InvoicesController::class, 'send'])->name('invoices.send');
    });

    // Update — setup config, payments, cancel
    Route::middleware('permission:Invoice,update')->group(function () {
        Route::put('/setups/{id}', [BillingSetupsController::class, 'update'])->name('setups.update');
        Route::post('/setups/{id}', [BillingSetupsController::class, 'update'])->name('setups.update.post');
        Route::post('/invoices/{id}/payments', [InvoicesController::class, 'recordPayment'])->name('invoices.payments.store');
        Route::post('/invoices/{id}/cancel', [InvoicesController::class, 'cancel'])->name('invoices.cancel');
    });

    // Delete — setups without invoices
    Route::middleware('permission:Invoice,delete')->group(function () {
        Route::delete('/setups/{id}', [BillingSetupsController::class, 'destroy'])->name('setups.destroy');
    });
});
