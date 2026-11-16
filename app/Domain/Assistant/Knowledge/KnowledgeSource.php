<?php

namespace App\Domain\Assistant\Knowledge;

use App\Domain\Assistant\Enums\KnowledgeSourceType;
use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Models\ContentModel;
use App\Domain\Content\Models\Faq;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Models\Sector;
use App\Domain\Content\Models\Service;

/**
 * Transforme un contenu publié du site en document Markdown pour la base de connaissances.
 * Seuls les champs publics sont repris (jamais le nom d'un client non autorisé, ni de champ interne).
 */
final class KnowledgeSource
{
    /** Pages éditoriales interrogeables par l'assistant (TRD §7.1) : pas les pages légales. */
    public const PAGES = ['methode', 'a-propos'];

    /** @var array<class-string<ContentModel>, KnowledgeSourceType> */
    public const TYPES = [
        Service::class => KnowledgeSourceType::Service,
        Sector::class => KnowledgeSourceType::Sector,
        Faq::class => KnowledgeSourceType::Faq,
        Page::class => KnowledgeSourceType::Page,
        Article::class => KnowledgeSourceType::Article,
        CaseStudy::class => KnowledgeSourceType::CaseStudy,
    ];

    public static function typeOf(ContentModel $content): ?KnowledgeSourceType
    {
        return self::TYPES[$content::class] ?? null;
    }

    /**
     * Le contenu fait-il partie de la base (publié et d'un type ou d'une page retenus) ?
     */
    public static function eligible(ContentModel $content): bool
    {
        if (! $content->isPublished() || self::typeOf($content) === null) {
            return false;
        }

        return ! $content instanceof Page || in_array($content->slug, self::PAGES, true);
    }

    /**
     * @return array{title: string, url: string, markdown: string}
     */
    public static function document(ContentModel $content): array
    {
        $url = $content->publicUrl();

        return match (true) {
            $content instanceof Service => ['title' => $content->title, 'url' => $url, 'markdown' => self::service($content)],
            $content instanceof Sector => ['title' => $content->name, 'url' => $url, 'markdown' => self::sector($content)],
            $content instanceof Faq => ['title' => $content->question, 'url' => route('home').'#faq', 'markdown' => $content->answer],
            $content instanceof Page => ['title' => self::plain($content->title), 'url' => $url, 'markdown' => $content->body],
            $content instanceof Article => ['title' => $content->title, 'url' => $url, 'markdown' => trim(($content->excerpt ?? '')."\n\n".$content->body)],
            $content instanceof CaseStudy => ['title' => $content->title, 'url' => $url, 'markdown' => self::caseStudy($content)],
            default => ['title' => '', 'url' => $url, 'markdown' => ''],
        };
    }

    private static function service(Service $s): string
    {
        $lines = ['# '.$s->title, (string) $s->tagline, '', '## Ce qui vous freine', (string) $s->problem, '', '## Pour qui ?', (string) $s->audience];
        $lines = [...$lines, '', '## Ce que vous obtenez', ...self::items($s->benefits)];
        $lines = [...$lines, '', '## Fonctionnalités', ...self::items($s->features)];
        if (filled($s->limits)) {
            $lines = [...$lines, '', '## Limites', (string) $s->limits];
        }
        if (filled($s->duration_hint)) {
            $lines = [...$lines, '', '## Délai indicatif', (string) $s->duration_hint];
        }
        foreach ((array) $s->faq as $item) {
            $lines = [...$lines, '', '## '.$item['q'], $item['a']];
        }

        return implode("\n", $lines);
    }

    private static function sector(Sector $s): string
    {
        return implode("\n", [
            '# '.$s->name, (string) $s->headline, '', (string) $s->intro,
            '', '## Vos défis', ...self::items($s->pain_points),
            '', '## Ce que ça change', ...self::items($s->outcomes),
        ]);
    }

    private static function caseStudy(CaseStudy $c): string
    {
        $parts = ['# '.$c->title, '## Contexte', (string) $c->context, '## Défi', (string) $c->challenge, '## Intervention', (string) $c->intervention];
        if ($c->results_verified && filled($c->results)) {
            $parts = [...$parts, '## Résultats', (string) $c->results];
        }

        return implode("\n\n", $parts);
    }

    /**
     * @param  mixed  $items  liste de chaînes ou de {title, text}
     * @return list<string>
     */
    private static function items(mixed $items): array
    {
        return array_values(array_map(function (mixed $item): string {
            if (is_array($item)) {
                return '- '.trim(($item['title'] ?? '').(isset($item['text']) && $item['text'] !== '' ? ' : '.$item['text'] : ''));
            }

            return '- '.(string) $item;
        }, (array) $items));
    }

    private static function plain(string $title): string
    {
        return trim(str_replace('*', '', $title));
    }
}
