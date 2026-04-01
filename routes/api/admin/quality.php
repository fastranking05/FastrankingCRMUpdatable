<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quality Control Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('quality')->name('quality.')->group(function () {
    require __DIR__ . '/quality/quality.php';
    require __DIR__ . '/quality/quality-questions.php';
    require __DIR__ . '/quality/quality-data-submission.php';
    require __DIR__ . '/quality/quality-audit.php';
});
