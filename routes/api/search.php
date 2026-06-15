<?php

use App\Http\Controllers\Api\Search\GlobalSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Global Search Routes (Typesense + Laravel Scout)
|--------------------------------------------------------------------------
|
| Unified search across businesses, contacts, deals, appointments, users,
| emails, consultations, SEO audits, and comments.
|
*/

Route::middleware(['jwt.auth'])->prefix('search')->name('search.')->group(function () {
    Route::get('/', [GlobalSearchController::class, 'search'])->name('index');
    Route::get('/status', [GlobalSearchController::class, 'status'])->name('status');

    Route::post('/reindex', [GlobalSearchController::class, 'reindex'])->name('reindex');
});
