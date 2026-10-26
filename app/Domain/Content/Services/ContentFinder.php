<?php

namespace App\Domain\Content\Services;

use App\Domain\Content\Concerns\Publishable;
use App\Domain\Content\Support\ContentPreview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Point d'entrée unique des pages publiques vers les contenus :
 * le public ne voit que les contenus publiés, l'équipe connectée voit aussi les brouillons.
 */
final class ContentFinder
{
    /**
     * @template T of Model
     *
     * @param  class-string<T>  $model
     * @return Builder<T>
     */
    public function query(string $model): Builder
    {
        /** @var Builder<T> $query */
        $query = $model::query();

        return $query->visible(ContentPreview::enabled())->orderBy('sort');
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $model
     * @return T&Publishable
     */
    public function bySlug(string $model, string $slug): Model
    {
        return $this->query($model)->where('slug', $slug)->firstOrFail();
    }
}
