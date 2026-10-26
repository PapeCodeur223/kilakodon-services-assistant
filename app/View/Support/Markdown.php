<?php

namespace App\View\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Markdown des contenus (TRD §2) : HTML saisi échappé, liens dangereux refusés.
 */
final class Markdown
{
    public static function html(?string $markdown): HtmlString
    {
        return new HtmlString(Str::markdown((string) $markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]));
    }

    /**
     * Titres de niveau 2 (pour le sommaire ancré des pages légales).
     *
     * @return list<array{id: string, title: string}>
     */
    public static function headings(?string $markdown): array
    {
        preg_match_all('/^##\s+(.+)$/m', (string) $markdown, $matches);

        return array_map(fn (string $title): array => ['id' => Str::slug($title), 'title' => trim($title)], $matches[1]);
    }

    /**
     * Ajoute un id aux <h2> pour les ancres du sommaire.
     */
    public static function withAnchors(?string $markdown): HtmlString
    {
        $html = (string) self::html($markdown);

        $html = (string) preg_replace_callback('/<h2>(.*?)<\/h2>/s', fn (array $m): string => '<h2 id="'.e(Str::slug(html_entity_decode(strip_tags($m[1])))).'">'.$m[1].'</h2>', $html);

        return new HtmlString($html);
    }
}
