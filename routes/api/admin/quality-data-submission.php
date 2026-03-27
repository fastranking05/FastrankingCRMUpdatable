<?php

use App\Http\Controllers\Api\Quality\QualityDataSubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quality Data Submission Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('quality-data-submission')->name('quality-data-submission.')->group(function () {
    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [QualityDataSubmissionController::class, 'submitQualityData'])->name('submit');
        Route::get('/questions', [QualityDataSubmissionController::class, 'getActiveQuestions'])->name('questions');
    });
});
