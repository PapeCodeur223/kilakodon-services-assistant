<?php

namespace App\Domain\Content\Support;

use Closure;

/**
 * Seules les opérations explicitement autorisées (PublishContent, seeders) peuvent
 * faire passer un contenu au statut « publié » (BACKEND_SCHEMA §8.1).
 */
final class PublishGuard
{
    private static int $depth = 0;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function allow(Closure $callback): mixed
    {
        self::$depth++;

        try {
            return $callback();
        } finally {
            self::$depth--;
        }
    }

    public static function isAllowed(): bool
    {
        return self::$depth > 0;
    }
}
