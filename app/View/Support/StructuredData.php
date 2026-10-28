<?php

namespace App\View\Support;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Service;
use App\Domain\Platform\Services\Settings;

/**
 * Données structurées schema.org (WEB-05). Uniquement des informations confirmées :
 * pas d'adresse précise, de note ni de prix tant qu'ils ne sont pas validés.
 */
final class StructuredData
{
    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $settings = app(Settings::class);

        return [
            '@context' => 'https://schema.org',
            '@type' => ['Organization', 'LocalBusiness'],
            '@id' => url('/').'#organization',
            'name' => 'Kilakodon Services',
            'url' => url('/'),
            'logo' => asset('images/brand/icon-512.png'),
            'image' => asset('images/brand/og-default.png'),
            'description' => 'Logiciels de gestion et solutions numériques sur mesure — Bamako, Mali',
            'email' => $settings->get('contact.email'),
            'telephone' => $settings->get('contact.whatsapp_primary'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Bamako',
                'addressCountry' => 'ML',
            ],
            'areaServed' => ['@type' => 'Country', 'name' => 'Mali'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function service(Service $service): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service->title,
            'description' => $service->tagline,
            'url' => $service->publicUrl(),
            'serviceType' => $service->title,
            'provider' => ['@id' => url('/').'#organization'],
            'areaServed' => ['@type' => 'Country', 'name' => 'Mali'],
        ];
    }

    /**
     * @param  iterable<array{q: string, a: string}>  $questions
     * @return array<string, mixed>|null
     */
    public static function faqPage(iterable $questions): ?array
    {
        $entities = [];
        foreach ($questions as $item) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ];
        }

        return $entities === [] ? null : [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function article(Article $article): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->excerpt,
            'url' => $article->publicUrl(),
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => $article->authorName()],
            'publisher' => ['@id' => url('/').'#organization'],
            'articleSection' => $article->category->label(),
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $i): array => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['label'],
                'item' => $item['url'],
            ], $items, array_keys($items)),
        ];
    }

    /**
     * JSON sûr dans un <script> : « < », « > » et « & » sont échappés.
     *
     * @param  array<string, mixed>  $data
     */
    public static function encode(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
