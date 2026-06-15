<?php

namespace App\Services\AI\Security;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Enforces read-only database access during chat data fetching.
 * Any non-SELECT query throws immediately.
 */
class ChatReadOnlyDatabaseGuard
{
    private static bool $listenerRegistered = false;

    private static int $guardDepth = 0;

    public function run(callable $callback): mixed
    {
        $this->registerListener();

        self::$guardDepth++;

        try {
            return $callback();
        } finally {
            self::$guardDepth--;
        }
    }

    private function registerListener(): void
    {
        if (self::$listenerRegistered) {
            return;
        }

        DB::listen(function ($query): void {
            if (self::$guardDepth === 0) {
                return;
            }

            $sql = strtolower(ltrim($query->sql));

            if (!preg_match('/^(select|with|show|explain)\b/', $sql)) {
                throw new RuntimeException(
                    'Chat is read-only: database write operations are not permitted.'
                );
            }
        });

        self::$listenerRegistered = true;
    }
}
