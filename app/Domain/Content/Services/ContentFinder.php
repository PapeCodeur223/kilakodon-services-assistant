<?php

namespace App\Domain\Content\Services;

use App\Domain\Content\Models\ContentModel;
use App\Domain\Content\Support\ContentPreview;
use Illuminate\Database\Eloquent\Builder;

/**
 * Point d'entrée unique des pages publiques vers les contenus :
 * le public ne voit que les contenus publiés, l'équipe connectée voit aussi les brouillons.
 */
final class ContentFinder
{
    /**
     * @template T of ContentModel
     *
     * @param  class-string<T>  $model
     * @return Builder<T>
     */
    public function query(string $model): Builder
    {
        /** @var Builder<T> $query */
        $query = $model::query();

        return $query->scopes(['visible' => [ContentPreview::enabled()]])->orderBy('sort');
    }

    /**
     * @template T of ContentModel
     *
     * @param  class-string<T>  $model
     * @return T
     */
    public function bySlug(string $model, string $slug): ContentModel
    {
        return $this->query($model)->where('slug', $slug)->firstOrFail();
    }
}
