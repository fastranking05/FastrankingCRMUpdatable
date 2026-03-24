<?php

use App\Http\Controllers\Api\Quality\QualityController;
use App\Http\Controllers\Api\Quality\QualityQuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quality Control Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('quality')->name('quality.')->group(function () {
    // Quality Records Routes
    Route::middleware('permission:Quality Control,read')->group(function () {
        Route::get('/', [QualityController::class, 'index'])->name('index');
        Route::get('/{id}', [QualityController::class, 'show'])->name('show');
        Route::get('/my-assignments', [QualityController::class, 'myAssignments'])->name('my-assignments');
        Route::get('/workload-stats', [QualityController::class, 'workloadStats'])->name('workload-stats');
    });

    Route::middleware('permission:Quality Control,update')->group(function () {
        Route::put('/{id}', [QualityController::class, 'update'])->name('update');
        Route::post('/{id}/reassign', [QualityController::class, 'reassign'])->name('reassign');
        Route::post('/{qualityId}/submit-answers', [QualityController::class, 'submitAnswers'])->name('submit-answers');
    });
});

// Quality Questions Routes
Route::middleware(['jwt.auth'])->prefix('quality-questions')->name('quality-questions.')->group(function () {
    Route::middleware('permission:Quality Control,read')->group(function () {
        Route::get('/', [QualityQuestionController::class, 'index'])->name('index');
    });

    Route::middleware('permission:Quality Control,create')->group(function () {
        Route::post('/', [QualityQuestionController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Quality Control,update')->group(function () {
        Route::put('/{id}', [QualityQuestionController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Quality Control,delete')->group(function () {
        Route::delete('/{id}', [QualityQuestionController::class, 'destroy'])->name('destroy');
    });
});
