<?php

namespace App\Services\AI\Security;

use InvalidArgumentException;

class ChatInputSanitizer
{
    /**
     * @throws InvalidArgumentException
     */
    public function sanitize(string $message): string
    {
        $message = str_replace("\0", '', $message);
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message) ?? '';
        $message = preg_replace('/\s+/u', ' ', trim($message)) ?? '';

        $maxLength = (int) config('ai.chat.max_message_length', 2000);

        if (mb_strlen($message) < 2) {
            throw new InvalidArgumentException('Message is too short.');
        }

        if (mb_strlen($message) > $maxLength) {
            throw new InvalidArgumentException("Message must not exceed {$maxLength} characters.");
        }

        if ($this->containsDisallowedPatterns($message)) {
            throw new InvalidArgumentException('Message contains disallowed content.');
        }

        return $message;
    }

    /**
     * @return string|null Sanitized search term or null if empty after sanitization
     */
    public function sanitizeSearchTerm(?string $term): ?string
    {
        if ($term === null || trim($term) === '') {
            return null;
        }

        $term = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($term)) ?? '';
        $maxLength = (int) config('ai.security.max_search_term_length', 100);

        if (mb_strlen($term) > $maxLength) {
            $term = mb_substr($term, 0, $maxLength);
        }

        if ($this->containsDisallowedPatterns($term)) {
            return null;
        }

        return mb_strlen($term) >= 3 ? $term : null;
    }

    private function containsDisallowedPatterns(string $value): bool
    {
        $patterns = config('ai.security.blocked_input_patterns', []);

        foreach ($patterns as $pattern) {
            if (@preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }
}
