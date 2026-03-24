<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quality Control Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('quality')->name('quality.')->group(function () {
    require __DIR__ . '/quality/quality.php';
});

Route::middleware(['jwt.auth'])->prefix('quality-questions')->name('quality-questions.')->group(function () {
    require __DIR__ . '/quality/quality.php';
});
