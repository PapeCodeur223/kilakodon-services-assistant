<?php

namespace App\Domain\Platform\Support;

/**
 * Les IP ne sont jamais stockées en clair (TRD §11).
 */
class IpHasher
{
    public function hash(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
