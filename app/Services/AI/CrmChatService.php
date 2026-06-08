<?php

namespace App\Services\AI;

use App\Models\User;
use RuntimeException;

class CrmChatService
{
    public function __construct(
        private readonly OllamaClient $ollama,
        private readonly GlobalChatDataService $globalData,
    ) {}

    /**
     * @return array{
     *     answer: string,
     *     access_level: string,
     *     readable_modules: array<int, string>,
     *     model: string
     * }
     */
    public function handle(User $user, string $message): array
    {
        if (!config('ai.enabled')) {
            throw new RuntimeException('AI chat is disabled. Set AI_CHAT_ENABLED=true in .env');
        }

        if (!$this->ollama->isReachable()) {
            throw new RuntimeException('Ollama is not running. Start the Ollama app and try again.');
        }

        $crmData = $this->globalData->fetch($user, $message);
        $scope = $crmData['access']['scope'] ?? [];

        $systemPrompt = $this->buildSystemPrompt($user, $crmData);

        $userPrompt = json_encode([
            'question' => $message,
            'crm_data' => $crmData,
        ], JSON_UNESCAPED_UNICODE);

        // Ollama can take 30–120s; PHP default max_execution_time is 30s and kills artisan serve.
        $ollamaTimeout = (int) config('ai.ollama.timeout', 120);
        set_time_limit($ollamaTimeout + 60);

        $answer = $this->ollama->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);

        return [
            'answer' => $answer,
            'access_level' => $scope['access_level'] ?? 'executive',
            'readable_modules' => $crmData['access']['readable_modules'] ?? [],
            'model' => (string) config('ai.ollama.model'),
        ];
    }

    /**
     * @param  array<string, mixed>  $crmData
     */
    private function buildSystemPrompt(User $user, array $crmData): string
    {
        $name = trim($user->first_name . ' ' . $user->last_name);
        $scope = $crmData['access']['scope'] ?? [];
        $modules = implode(', ', $crmData['access']['readable_modules'] ?? []) ?: 'none';

        return implode("\n", [
            "You are the global CRM assistant for {$name}.",
            'You have access to ALL modules this user can read — not just one module.',
            'Access level: ' . ($scope['access_level'] ?? 'executive') . '.',
            'Readable modules: ' . $modules . '.',
            'crm_data contains: summaries (counts), query_context (targeted facts for the question), recent_records, and optional search results.',
            'Prioritize query_context when present — it answers latest/today/pending/count questions directly.',
            'Use summaries for total counts. Use recent_records for latest items when query_context is empty.',
            'Only use data inside crm_data. Never invent records, counts, or names.',
            'If all relevant sections are empty or zero, say no matching data exists in this user scope.',
            'If the user asks about a module they cannot read, say they do not have permission.',
            'Answer about any CRM topic (leads, deals, appointments, emails, follow-ups, SEO, users, etc.) using the provided data.',
            $this->languageInstruction(),
            'Keep answers concise, accurate, and helpful.',
        ]);
    }

    private function languageInstruction(): string
    {
        $language = strtolower((string) config('ai.chat.reply_language', 'en'));

        return match ($language) {
            'hi', 'hindi' => 'Always reply in Hindi, even if the user writes in English or Hinglish.',
            'hinglish' => 'Always reply in Hinglish (mix of Hindi and English), even if the user writes in another language.',
            default => 'Always reply in English only, even if the user writes in Hindi or Hinglish.',
        };
    }
}
