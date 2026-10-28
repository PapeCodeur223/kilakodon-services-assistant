<?php

namespace App\Domain\Content\Services;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/**
 * sitemap.xml : uniquement les pages publiques et les contenus publiés (jamais de brouillon).
 * Mis en cache ; régénéré par `php artisan sitemap:generate` (quotidien) et à chaque publication.
 */
final class SitemapBuilder
{
    public const CACHE_KEY = 'kh.sitemap';

    public function __construct(private readonly Cache $cache) {}

    public function xml(): string
    {
        return $this->cache->remember(self::CACHE_KEY, now()->addDay(), fn (): string => $this->build()->render());
    }

    public function refresh(): string
    {
        $this->cache->forget(self::CACHE_KEY);

        return $this->xml();
    }

    public function build(): Sitemap
    {
        $sitemap = Sitemap::create()
            ->add(Url::create(route('home'))->setPriority(1.0))
            ->add(Url::create(route('solutions.index'))->setPriority(0.9))
            ->add(Url::create(route('articles.index'))->setPriority(0.6))
            ->add(Url::create(route('contact'))->setPriority(0.6))
            ->add(Url::create(route('diagnostic.start'))->setPriority(0.8))
            ->add(Url::create(route('appointment'))->setPriority(0.5));

        if (CaseStudy::query()->published()->exists()) {
            $sitemap->add(Url::create(route('cases.index'))->setPriority(0.6));
        }

        foreach ([Service::class => 0.9, Sector::class => 0.8, CaseStudy::class => 0.6, Article::class => 0.6, Page::class => 0.4] as $model => $priority) {
            /** @var iterable<Service|Sector|CaseStudy|Article|Page> $items */
            $items = $model::query()->published()->orderBy('sort')->get();

            foreach ($items as $item) {
                $sitemap->add($this->url($item, $priority));
            }
        }

        return $sitemap;
    }

    private function url(Model $content, float $priority): Url
    {
        /** @var Service|Sector|CaseStudy|Article|Page $content */
        $url = Url::create($content->publicUrl())->setPriority($priority);

        if ($content->updated_at !== null) {
            $url->setLastModificationDate($content->updated_at);
        }

        return $url;
    }
}
