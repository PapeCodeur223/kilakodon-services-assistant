<?php

namespace App\Domain\DemoLab\Support;

use App\Domain\DemoLab\Models\DemoWorkspace;
use Closure;

/**
 * Espace Démo Lab courant (TRD §8). Sans espace courant, toute requête sur un modèle `Demo*`
 * ne renvoie rien et toute création échoue : l'isolation ne dépend pas de la vigilance du code appelant.
 */
final class WorkspaceContext
{
    private ?string $workspaceId = null;

    public function set(?DemoWorkspace $workspace): void
    {
        $this->workspaceId = $workspace?->id;
    }

    public function id(): ?string
    {
        return $this->workspaceId;
    }

    /**
     * Exécute un traitement dans un espace, puis restaure l'espace précédent.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(DemoWorkspace $workspace, Closure $callback): mixed
    {
        $previous = $this->workspaceId;
        $this->workspaceId = $workspace->id;

        try {
            return $callback();
        } finally {
            $this->workspaceId = $previous;
        }
    }
}
