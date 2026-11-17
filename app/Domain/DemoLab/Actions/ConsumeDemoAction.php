<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Exceptions\DemoLimitReached;
use App\Domain\DemoLab\Models\DemoWorkspace;

/**
 * Compte une action du visiteur et bloque au-delà de 500 par espace (TRD §8).
 * Incrément conditionnel en une requête : pas de dépassement en cas de requêtes simultanées.
 */
class ConsumeDemoAction
{
    public function handle(DemoWorkspace $workspace): void
    {
        $updated = DemoWorkspace::query()
            ->whereKey($workspace->id)
            ->where('expires_at', '>', now())
            ->where('actions_count', '<', DemoWorkspace::MAX_ACTIONS)
            ->increment('actions_count');

        if ($updated === 0) {
            throw new DemoLimitReached('Cet espace de démonstration a atteint sa limite ou a expiré.');
        }

        $workspace->actions_count++;
    }
}
