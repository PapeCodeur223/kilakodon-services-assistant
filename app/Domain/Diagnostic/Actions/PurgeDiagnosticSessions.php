<?php

namespace App\Domain\Diagnostic\Actions;

use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Models\DiagnosticSession;

/**
 * Supprime les sessions anonymes expirées (> 30 jours, TRD §10) avec leurs réponses et résultats.
 * Les sessions converties en brief sont conservées : elles relèvent du CRM.
 */
class PurgeDiagnosticSessions
{
    public function handle(): int
    {
        $deleted = 0;

        DiagnosticSession::query()
            ->where('expires_at', '<', now())
            ->where('status', '!=', SessionStatus::Converted)
            ->whereDoesntHave('brief')
            ->select('id')
            ->chunkById(200, function ($sessions) use (&$deleted): void {
                $deleted += DiagnosticSession::query()->whereKey($sessions->modelKeys())->delete();
            });

        return $deleted;
    }
}
