<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth Routes (Public + Protected)
require __DIR__ . '/api/auth.php';

// Admin Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/departments.php';
    require __DIR__ . '/api/admin/modules.php';
    require __DIR__ . '/api/admin/roles.php';
    require __DIR__ . '/api/admin/teams.php';
    require __DIR__ . '/api/admin/users.php';
});

// Follow-Up Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/followup/followup.php';
    // Individual routes (optional - can be removed if not needed)
    require __DIR__ . '/api/admin/followup/businesses.php';
    require __DIR__ . '/api/admin/followup/auth-persons.php';
    require __DIR__ . '/api/admin/followup/details.php';
    require __DIR__ . '/api/admin/followup/comments.php';
});
