<?php

namespace App\Domain\DemoLab\Actions;

use App\Domain\DemoLab\Models\DemoWorkspace;

/**
 * Supprime les espaces expirés ; les clés étrangères en cascade effacent toutes leurs données (TRD §8).
 */
class PurgeDemoWorkspaces
{
    public function handle(): int
    {
        $deleted = 0;

        DemoWorkspace::query()
            ->where('expires_at', '<', now())
            ->select('id')
            ->chunkById(100, function ($workspaces) use (&$deleted): void {
                $deleted += DemoWorkspace::query()->whereKey($workspaces->modelKeys())->delete();
            });

        return $deleted;
    }
}
