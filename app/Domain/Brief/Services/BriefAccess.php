<?php

namespace App\Domain\Brief\Services;

use App\Domain\Brief\Models\Brief;
use App\Domain\Platform\Support\PublicToken;

/**
 * Retrouve un brief depuis le token de son lien privé. Un token inconnu ne révèle rien
 * (404) ; un lien expiré ou révoqué est distingué pour afficher la page « lien expiré ».
 */
class BriefAccess
{
    public function find(string $token): ?Brief
    {
        if (! PublicToken::looksValid($token)) {
            return null;
        }

        return Brief::query()->where('access_token_hash', PublicToken::hash($token))->first();
    }
}
