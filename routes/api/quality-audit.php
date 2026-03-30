<?php

use App\Http\Controllers\Api\Quality\QualityAuditController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Quality Audit Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('quality-audit')->name('quality-audit.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/audit-pending', [QualityAuditController::class, 'auditPending'])->name('audit-pending');
        Route::get('/audit-completed', [QualityAuditController::class, 'auditCompleted'])->name('audit-completed');
        Route::get('/all', [QualityAuditController::class, 'allAudits'])->name('all');
    });
});
