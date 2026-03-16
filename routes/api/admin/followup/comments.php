<?php

use App\Http\Controllers\Api\Followup\FollowupCommentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Follow-Up Comments Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('followup-comments')->name('followup-comments.')->group(function () {
    Route::middleware('permission:Follow-Up,read')->group(function () {
        Route::get('/', [FollowupCommentController::class, 'index'])->name('index');
        Route::get('/{id}', [FollowupCommentController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Follow-Up,create')->group(function () {
        Route::post('/', [FollowupCommentController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Follow-Up,update')->group(function () {
        Route::put('/{id}', [FollowupCommentController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Follow-Up,delete')->group(function () {
        Route::delete('/{id}', [FollowupCommentController::class, 'destroy'])->name('destroy');
    });
});
