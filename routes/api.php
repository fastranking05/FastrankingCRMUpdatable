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
