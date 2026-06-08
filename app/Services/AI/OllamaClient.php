<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, bool $stream = false): string
    {
        $host = rtrim((string) config('ai.ollama.host'), '/');
        $model = (string) config('ai.ollama.model');
        $timeout = (int) config('ai.ollama.timeout');

        $response = Http::timeout($timeout)
            ->post("{$host}/api/chat", [
                'model' => $model,
                'messages' => $messages,
                'stream' => $stream,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Ollama request failed: ' . $response->body());
        }

        $content = $response->json('message.content');

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        return $content;
    }

    public function isReachable(): bool
    {
        try {
            $host = rtrim((string) config('ai.ollama.host'), '/');

            return Http::timeout(5)->get("{$host}/api/tags")->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
