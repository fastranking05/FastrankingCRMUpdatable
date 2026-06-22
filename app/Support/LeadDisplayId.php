<?php

namespace App\Support;

class LeadDisplayId
{
    public const PREFIX = 'FRLID';

    public const PAD_LENGTH = 7;

    public static function format(int|string|null $id): string
    {
        if ($id === null || $id === '') {
            return '';
        }

        if (!is_numeric($id)) {
            return (string) $id;
        }

        $numericId = (int) $id;

        if ($numericId < 0) {
            return (string) $id;
        }

        return self::PREFIX . str_pad((string) $numericId, self::PAD_LENGTH, '0', STR_PAD_LEFT);
    }

    public static function parse(string $query): ?int
    {
        $trimmed = trim($query);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^' . preg_quote(self::PREFIX, '/') . '0*(\d+)$/i', $trimmed, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    public static function resolveNumericId(string $query): ?int
    {
        $parsed = self::parse($query);

        if ($parsed !== null) {
            return $parsed;
        }

        $trimmed = trim($query);

        if ($trimmed !== '' && ctype_digit($trimmed)) {
            return (int) $trimmed;
        }

        return null;
    }
}
