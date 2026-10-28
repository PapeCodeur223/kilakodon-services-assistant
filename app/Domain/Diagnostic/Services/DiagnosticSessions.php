<?php

namespace App\Domain\Diagnostic\Services;

use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Platform\Support\PublicToken;

/**
 * Reprise d'une session par son lien (DIA-03) : token inconnu ou session expirée → null (404).
 */
class DiagnosticSessions
{
    public function findByToken(string $token): ?DiagnosticSession
    {
        if (! PublicToken::looksValid($token)) {
            return null;
        }

        $session = DiagnosticSession::query()->where('resume_token_hash', PublicToken::hash($token))->first();

        return $session !== null && ! $session->isExpired() ? $session : null;
    }
}
