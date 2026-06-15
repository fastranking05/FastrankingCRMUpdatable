<?php

use App\Http\Controllers\Api\Chat\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AI Chat Routes (Ollama)
|--------------------------------------------------------------------------
|
| Global CRM chatbot — one endpoint for all modules. Answers use the
| logged-in user's role hierarchy and module read permissions.
|
*/

Route::middleware(['jwt.auth', 'chat.security'])->prefix('chat')->name('chat.')->group(function () {
    Route::post('/', [ChatController::class, 'chat'])
        ->middleware('throttle:ai-chat')
        ->name('message');
    Route::get('/status', [ChatController::class, 'status'])
        ->middleware('throttle:ai-chat-status')
        ->name('status');
});
