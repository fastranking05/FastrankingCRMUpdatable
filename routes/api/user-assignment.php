<?php

use App\Http\Controllers\Api\UserAssignmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Assignment Routes
|--------------------------------------------------------------------------
|
| Routes for managing user assignment system with Round Robin and load balancing
|
*/

Route::middleware(['jwt.auth'])->prefix('user-assignment')->name('user-assignment.')->group(function () {
    
    // Get Sales department assignment statistics
    Route::middleware('permission:UserAssignment,read')->group(function () {
        Route::get('/sales-stats', [UserAssignmentController::class, 'getSalesAssignmentStats'])->name('sales-stats');
        Route::get('/next-user', [UserAssignmentController::class, 'getNextAssignedUser'])->name('next-user');
    });

    // Reset round robin index (admin only)
    Route::middleware('permission:UserAssignment,manage')->group(function () {
        Route::post('/reset-round-robin', [UserAssignmentController::class, 'resetRoundRobinIndex'])->name('reset-round-robin');
        Route::post('/reassign-consultations', [UserAssignmentController::class, 'reassignConsultationsForInactiveUser'])->name('reassign-consultations');
    });
});
