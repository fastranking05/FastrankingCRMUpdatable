<?php

namespace App\Services\AI\Security;

class ChatOutboundDataSanitizer
{
    /**
     * Strip internal/sensitive fields before data is sent to the LLM.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        return $this->walk($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function walk(array $data): array
    {
        $redactKeys = array_map('strtolower', config('ai.security.redact_field_names', []));
        $result = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, $redactKeys, true)) {
                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->walk($value);

                continue;
            }

            if (is_string($value)) {
                $result[$key] = $this->sanitizeStringValue($normalizedKey, $value);

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function sanitizeStringValue(string $key, string $value): string
    {
        if ($this->isSensitiveKey($key)) {
            return $this->maskSensitiveValue($key, $value);
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->maskEmail($value);
        }

        if ($this->looksLikePhone($value)) {
            return $this->maskPhone($value);
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (config('ai.security.mask_field_patterns', []) as $pattern) {
            if (preg_match($pattern, $key) === 1) {
                return true;
            }
        }

        return false;
    }

    private function maskSensitiveValue(string $key, string $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->maskEmail($value);
        }

        if ($this->looksLikePhone($value)) {
            return $this->maskPhone($value);
        }

        if (mb_strlen($value) <= 4) {
            return '****';
        }

        return mb_substr($value, 0, 2) . str_repeat('*', max(1, mb_strlen($value) - 4)) . mb_substr($value, -2);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2) {
            return '****';
        }

        [$local, $domain] = $parts;
        $visible = mb_substr($local, 0, 1);

        return $visible . '***@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 4) {
            return '****';
        }

        return '***' . substr($digits, -4);
    }

    private function looksLikePhone(string $value): bool
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return strlen($digits) >= 8 && strlen($digits) <= 15
            && preg_match('/^[\d\s+\-().]+$/', $value) === 1;
    }
}
