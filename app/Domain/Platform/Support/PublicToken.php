<?php

namespace App\Domain\Platform\Support;

/**
 * Tokens publics (brief, reprise de diagnostic, Démo Lab) — TRD §11 :
 * 32 octets aléatoires en base64url dans l'URL, seul le SHA-256 est stocké.
 */
final class PublicToken
{
    public const BYTES = 32;

    /**
     * @return array{token: string, hash: string}
     */
    public static function generate(): array
    {
        $token = rtrim(strtr(base64_encode(random_bytes(self::BYTES)), '+/', '-_'), '=');

        return ['token' => $token, 'hash' => self::hash($token)];
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Rejette d'emblée ce qui ne peut pas être un token (évite une requête inutile).
     */
    public static function looksValid(string $token): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{43}$/', $token) === 1;
    }

    public static function matches(string $token, string $hash): bool
    {
        return hash_equals($hash, self::hash($token));
    }
}
