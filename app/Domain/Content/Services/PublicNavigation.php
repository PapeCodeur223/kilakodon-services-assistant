<?php

namespace App\Domain\Content\Services;

use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;
use Illuminate\Database\QueryException;

/**
 * Navigation publique construite depuis les contenus visibles : aucun lien vers un brouillon
 * (donc vers une 404) pour le public. Calculée une fois par requête.
 */
final class PublicNavigation
{
    /** @var array{solutions: list<array{title: string, url: string, icon: string, tagline: string}>, sectors: list<array{title: string, url: string, icon: string}>, main: list<array{title: string, path: string}>, company: list<array{title: string, path: string}>, legal: list<array{title: string, path: string}>}|null */
    private ?array $memo = null;

    public function __construct(private readonly ContentFinder $content) {}

    /**
     * @return array{solutions: list<array{title: string, url: string, icon: string, tagline: string}>, sectors: list<array{title: string, url: string, icon: string}>, main: list<array{title: string, path: string}>, company: list<array{title: string, path: string}>, legal: list<array{title: string, path: string}>}
     */
    public function all(): array
    {
        return $this->memo ??= $this->build();
    }

    /**
     * @return array{solutions: list<array{title: string, url: string, icon: string, tagline: string}>, sectors: list<array{title: string, url: string, icon: string}>, main: list<array{title: string, path: string}>, company: list<array{title: string, path: string}>, legal: list<array{title: string, path: string}>}
     */
    private function build(): array
    {
        try {
            $pages = $this->content->query(Page::class)->pluck('slug')->all();
            $hasCases = $this->content->query(CaseStudy::class)->exists();

            $solutions = $this->content->query(Service::class)->get(['slug', 'title', 'icon', 'tagline'])
                ->map(fn (Service $s): array => ['title' => $s->title, 'url' => $s->publicUrl(), 'icon' => $s->icon, 'tagline' => $s->tagline])
                ->values()->all();

            $sectors = $this->content->query(Sector::class)->get(['slug', 'name', 'icon'])
                ->map(fn (Sector $s): array => ['title' => $s->name, 'url' => $s->publicUrl(), 'icon' => $s->icon ?? 'store'])
                ->values()->all();
        } catch (QueryException) {
            $pages = [];
            $hasCases = false;
            $solutions = [];
            $sectors = [];
        }

        $keep = fn (array $links): array => array_values(array_filter($links, fn (array $link): bool => match (true) {
            isset($link['page']) => in_array($link['page'], $pages, true),
            ($link['cases'] ?? false) === true => $hasCases,
            default => true,
        }));

        /** @var array<string, list<array<string, mixed>>> $config */
        $config = config('kh_navigation');

        return [
            'solutions' => $solutions,
            'sectors' => $sectors,
            'main' => $keep($config['main']),
            'company' => $keep($config['company']),
            'legal' => $keep($config['legal']),
        ];
    }
}
