<?php

use App\Http\Controllers\Api\Seo\SeoDataSubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Data Submission Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('seo-data-submission')->name('seo-data-submission.')->group(function () {
    Route::middleware('permission:SEO,create')->group(function () {
        Route::post('/', [SeoDataSubmissionController::class, 'submitSeoAudit'])->name('submit');
        Route::get('/questions', [SeoDataSubmissionController::class, 'getActiveQuestions'])->name('questions');
    });
});
