<?php

namespace App\Domain\Content\Concerns;

use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Support\PendingValidation;
use App\Domain\Content\Support\PublishGuard;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * Contenu publiable (BACKEND_SCHEMA §3) : statut, date de publication, SEO, auteurs.
 * Le passage à « publié » n'est possible que via PublishContent (PublishGuard).
 *
 * @mixin Model
 */
trait Publishable
{
    use SoftDeletes;

    public static function bootPublishable(): void
    {
        static::saving(function (self $model): void {
            if ($model->isDirty('status') && $model->status === ContentStatus::Published && ! PublishGuard::isAllowed()) {
                throw new LogicException('La publication passe obligatoirement par l\'action PublishContent.');
            }

            $user = auth()->user();
            if ($user instanceof User) {
                if (! $model->exists) {
                    $model->setAttribute('created_by', $user->id);
                }
                $model->setAttribute('updated_by', $user->id);
            }
        });
    }

    public function initializePublishable(): void
    {
        $this->mergeCasts([
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'sort' => 'integer',
        ]);

        if (! array_key_exists('status', $this->attributes)) {
            $this->attributes['status'] = ContentStatus::Draft->value;
        }
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published->value)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * Contenus visibles : publiés pour le public, tous (sauf archivés) en prévisualisation équipe.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisible(Builder $query, bool $preview = false): void
    {
        if ($preview) {
            $query->where('status', '!=', ContentStatus::Archived->value);

            return;
        }

        $query->published();
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * Champs contenant encore « [À VALIDER] ».
     *
     * @return list<string>
     */
    public function pendingValidationFields(): array
    {
        return PendingValidation::find($this->only($this->contentFields()));
    }

    /**
     * Champs éditoriaux (révisions, audit des [À VALIDER]).
     *
     * @return list<string>
     */
    abstract public function contentFields(): array;

    abstract public function publicUrl(): string;
}
