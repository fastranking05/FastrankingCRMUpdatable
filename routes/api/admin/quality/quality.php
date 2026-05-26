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
        Route::get('/filter-options', [QualityController::class, 'getFilterOptions'])->name('filter-options');
        // Static paths must come before /{id} or "my-assignments" is routed to show().
        Route::get('/my-assignments', [QualityController::class, 'myAssignments'])->name('my-assignments');
        Route::get('/workload-stats', [QualityController::class, 'workloadStats'])->name('workload-stats');
        Route::get('/{id}', [QualityController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Quality Control,update')->group(function () {
        Route::put('/{id}', [QualityController::class, 'update'])->name('update');
        Route::post('/{id}/reassign', [QualityController::class, 'reassign'])->name('reassign');
        Route::post('/{qualityId}/submit-answers', [QualityController::class, 'submitAnswers'])->name('submit-answers');
    });
});
