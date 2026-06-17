<?php

use App\Http\Controllers\Api\Seo\SeoQuestionCategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Question Categories Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('seo-question-categories')->name('seo-question-categories.')->group(function () {
    Route::middleware('permission:SEO,read')->group(function () {
        Route::get('/', [SeoQuestionCategoryController::class, 'index'])->name('index');
        Route::get('/active', [SeoQuestionCategoryController::class, 'getActive'])->name('active');
        Route::get('/{id}', [SeoQuestionCategoryController::class, 'show'])->name('show');
    });

    Route::middleware('permission:SEO,create')->group(function () {
        Route::post('/', [SeoQuestionCategoryController::class, 'store'])->name('store');
    });

    Route::middleware('permission:SEO,update')->group(function () {
        Route::put('/{id}', [SeoQuestionCategoryController::class, 'update'])->name('update');
    });

    Route::middleware('permission:SEO,delete')->group(function () {
        Route::delete('/{id}', [SeoQuestionCategoryController::class, 'destroy'])->name('destroy');
    });
});
