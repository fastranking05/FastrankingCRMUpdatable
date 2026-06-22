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
| Results are limited to entity types the user's department(s) can read.
|
*/

Route::middleware(['jwt.auth'])->prefix('search')->name('search.')->group(function () {
    Route::middleware('any.module.read')->group(function () {
        Route::get('/', [GlobalSearchController::class, 'search'])->name('index');
        Route::get('/status', [GlobalSearchController::class, 'status'])->name('status');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::post('/reindex', [GlobalSearchController::class, 'reindex'])->name('reindex');
    });
});
