<?php

namespace App\View\Support;

use Illuminate\Support\Str;

/**
 * Lit le texte Markdown de la page « À propos » (modifiable dans l'admin) pour la mise en page riche :
 * introduction, sections « ## Titre » avec leurs paragraphes, leurs éléments « **Titre** : texte »
 * et une éventuelle phrase finale entièrement en gras (engagement).
 * Rien n'est inventé : la vue n'affiche que ce que contient le texte. Si la forme change trop,
 * la page revient au gabarit simple.
 *
 * @phpstan-type Item array{title: string, text: string}
 * @phpstan-type Section array{title: string, paragraphs: list<string>, items: list<Item>, statement: string|null}
 */
final class AboutContent
{
    /**
     * @return array{intro: list<string>, sections: list<Section>}
     */
    public static function parse(string $body): array
    {
        $intro = [];
        $sections = [];

        foreach (preg_split('/\R/u', $body) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^##\s+(.+)$/u', $line, $m) === 1) {
                $sections[] = ['title' => trim($m[1]), 'paragraphs' => [], 'items' => [], 'statement' => null];

                continue;
            }

            if ($sections === []) {
                $intro[] = $line;

                continue;
            }

            $last = array_key_last($sections);

            if (preg_match('/^\*\*([^*]+)\*\*\s*:\s*(.+)$/u', $line, $m) === 1) {
                $sections[$last]['items'][] = ['title' => trim($m[1]), 'text' => trim($m[2])];
            } elseif (preg_match('/^\*\*([^*]+)\*\*$/u', $line, $m) === 1) {
                $sections[$last]['statement'] = trim($m[1]);
            } else {
                $sections[$last]['paragraphs'][] = $line;
            }
        }

        return ['intro' => $intro, 'sections' => $sections];
    }

    /**
     * Section dont le titre contient l'un des mots (insensible à la casse et aux accents).
     *
     * @param  list<Section>  $sections
     * @return Section|null
     */
    public static function find(array $sections, string ...$words): ?array
    {
        foreach ($sections as $section) {
            $title = self::normalize($section['title']);
            foreach ($words as $word) {
                if (str_contains($title, self::normalize($word))) {
                    return $section;
                }
            }
        }

        return null;
    }

    /**
     * Icône Lucide selon le domaine d'expertise (décor uniquement).
     */
    public static function iconFor(string $title): string
    {
        $t = self::normalize($title);

        return match (true) {
            str_contains($t, 'logiciel') => 'layout-dashboard',
            str_contains($t, 'marketing'), str_contains($t, 'communication') => 'megaphone',
            str_contains($t, 'saas'), str_contains($t, 'digital') => 'cloud',
            str_contains($t, 'web') => 'globe',
            str_contains($t, 'design'), str_contains($t, 'graphi') => 'pen-tool',
            str_contains($t, 'audiovisuel'), str_contains($t, 'video') => 'clapperboard',
            str_contains($t, 'mobile') => 'smartphone',
            default => 'sparkles',
        };
    }

    /**
     * Initiales d'un nom (« Pape Soumaila Traoré » → « PT »).
     */
    public static function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [];
        $first = $words[0] ?? '';
        $lastWord = count($words) > 1 ? (string) end($words) : '';

        return mb_strtoupper(mb_substr($first, 0, 1).mb_substr($lastWord, 0, 1));
    }

    private static function normalize(string $text): string
    {
        return mb_strtolower(Str::ascii($text));
    }
}
