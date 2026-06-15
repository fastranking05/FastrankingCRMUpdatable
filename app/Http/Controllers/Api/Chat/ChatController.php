<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\AI\CrmChatService;
use App\Services\AI\OllamaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends BaseApiController
{
    public function __construct(
        private readonly CrmChatService $chatService,
        private readonly OllamaClient $ollamaClient,
    ) {}

    /**
     * Global CRM chat — searches all modules the user can access.
     *
     * POST /api/chat
     */
    public function chat(Request $request): JsonResponse
    {
        $maxLength = (int) config('ai.chat.max_message_length', 2000);

        $validator = Validator::make($request->all(), [
            'message' => "required|string|min:2|max:{$maxLength}",
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            set_time_limit((int) config('ai.ollama.timeout', 120) + 60);

            $result = $this->chatService->handle(
                auth()->user(),
                (string) $request->input('message')
            );

            return $this->successResponse($result, 'Chat response generated successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 503);
        } catch (\Throwable $e) {
            return $this->errorResponse(
                'Chat failed',
                500,
                config('app.debug') ? $e->getMessage() : null
            );
        }
    }

    /**
     * Check AI chat and Ollama connection status.
     *
     * GET /api/chat/status
     */
    public function status(): JsonResponse
    {
        return $this->successResponse([
            'enabled' => (bool) config('ai.enabled'),
            'ollama_reachable' => $this->ollamaClient->isReachable(),
            'model' => config('ai.ollama.model'),
            'host' => config('ai.ollama.host'),
        ], 'Chat status retrieved successfully');
    }
}
