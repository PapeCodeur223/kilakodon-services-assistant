<?php

namespace App\Domain\DemoLab\Models\Concerns;

use App\Domain\DemoLab\Models\DemoWorkspace;
use App\Domain\DemoLab\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Global scope obligatoire des modèles `Demo*` (BACKEND_SCHEMA §7) : chaque requête est filtrée
 * sur l'espace courant ; sans espace courant, elle ne renvoie rien. La création renseigne
 * `workspace_id` d'office et refuse un autre espace que l'espace courant.
 *
 * @property string $workspace_id
 *
 * @mixin Model
 */
trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $query): void {
            $id = app(WorkspaceContext::class)->id();
            $column = $query->getModel()->qualifyColumn('workspace_id');

            $id === null ? $query->whereRaw('1 = 0') : $query->where($column, $id);
        });

        static::creating(function (Model $model): void {
            $id = app(WorkspaceContext::class)->id()
                ?? throw new LogicException('Aucun espace Démo Lab courant.');

            if (($model->getAttribute('workspace_id') ?? $id) !== $id) {
                throw new LogicException('Écriture refusée hors de l’espace Démo Lab courant.');
            }

            $model->setAttribute('workspace_id', $id);
        });
    }

    /**
     * @return BelongsTo<DemoWorkspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(DemoWorkspace::class, 'workspace_id');
    }
}
