<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Filter System Routes
|--------------------------------------------------------------------------
| This file contains all the filter endpoints for different modules
*/

// Appointment Filter Routes
Route::group([], function () {
    require __DIR__ . '/admin/appointment/filters.php';
});

// Quality Control Filter Routes
Route::group([], function () {
    require __DIR__ . '/admin/quality/filters.php';
});

// Followup Filter Routes
Route::group([], function () {
    require __DIR__ . '/admin/followup/filters.php';
});
