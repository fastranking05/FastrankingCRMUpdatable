<?php

use App\Http\Controllers\Api\Seo\SeoAuditController;
use App\Http\Controllers\Api\Seo\SeoViewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Audit Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('seo-audit')->name('seo-audit.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/audit-pending', [SeoAuditController::class, 'auditPending'])->name('audit-pending');
        Route::get('/audit-completed', [SeoAuditController::class, 'auditCompleted'])->name('audit-completed');
        Route::get('/not-applicable', [SeoAuditController::class, 'notApplicable'])->name('not-applicable');
        Route::get('/all', [SeoAuditController::class, 'allAudits'])->name('all');
    });
});

/*
|--------------------------------------------------------------------------
| SEO View Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('seo-view')->name('seo-view.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/comprehensive', [SeoViewController::class, 'comprehensiveView'])->name('comprehensive');
        Route::get('/business/{businessId}', [SeoViewController::class, 'comprehensiveViewByBusiness'])->name('business');
    });
});
