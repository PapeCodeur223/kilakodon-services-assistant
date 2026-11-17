<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\Platform\Support\PublicToken;

/**
 * Retrouve l'espace actif d'un visiteur à partir du token de son cookie `kh_lab`.
 */
class FindDemoWorkspace
{
    public function handle(?string $token): ?DemoWorkspace
    {
        if ($token === null || ! PublicToken::looksValid($token)) {
            return null;
        }

        return DemoWorkspace::query()->active()->where('token_hash', PublicToken::hash($token))->first();
    }
}
