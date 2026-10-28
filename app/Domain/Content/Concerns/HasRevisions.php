<?php

namespace App\Domain\Content\Concerns;

use App\Domain\Content\Models\ContentRevision;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Crée une révision (instantané des champs éditoriaux) à chaque sauvegarde qui les modifie.
 *
 * @mixin Model
 */
trait HasRevisions
{
    public static function bootHasRevisions(): void
    {
        // Relu en base pour inclure les valeurs par défaut des colonnes.
        static::created(fn (self $model) => $model->recordRevision($model->fresh() ?? $model));

        // `updated` ne se déclenche que si une colonne a réellement changé.
        static::updated(function (self $model): void {
            if ($model->wasChanged($model->revisionFields())) {
                $model->recordRevision($model);
            }
        });
    }

    protected function recordRevision(Model $snapshot): void
    {
        $user = auth()->user();

        $this->revisions()->create([
            'version' => ((int) $this->revisions()->max('version')) + 1,
            'data' => $snapshot->only($this->revisionFields()),
            'user_id' => $user instanceof User ? $user->id : null,
        ]);
    }

    /**
     * @return MorphMany<ContentRevision, $this>
     */
    public function revisions(): MorphMany
    {
        return $this->morphMany(ContentRevision::class, 'revisionable')->orderByDesc('version');
    }

    /**
     * @return list<string>
     */
    public function revisionFields(): array
    {
        return [...$this->contentFields(), 'slug', 'seo_title', 'seo_description', 'sort'];
    }
}
