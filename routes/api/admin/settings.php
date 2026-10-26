<?php

use App\Http\Controllers\Api\Admin\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('admin/settings')->name('settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::get('/{id}', [SettingController::class, 'show'])->name('show');
    Route::post('/', [SettingController::class, 'store'])->name('store');
    Route::put('/{id}', [SettingController::class, 'update'])->name('update');
    Route::post('/{id}', [SettingController::class, 'update'])->name('update.post');
    Route::delete('/{id}', [SettingController::class, 'destroy'])->name('destroy');
});
