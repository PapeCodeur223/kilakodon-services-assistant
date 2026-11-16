<?php

namespace App\Domain\Assistant\Knowledge;

/**
 * Découpe un document Markdown en fragments de 120 à 400 mots, en coupant sur les titres (TRD §7.1).
 * Une section trop courte est fusionnée avec la suivante ; une section trop longue est coupée
 * entre deux paragraphes. PHP pur.
 *
 * @phpstan-type Chunk array{heading: string, content: string, words: int}
 */
final class KnowledgeChunker
{
    public const MIN_WORDS = 120;

    public const MAX_WORDS = 400;

    /**
     * @return list<Chunk>
     */
    public function chunk(string $title, string $markdown): array
    {
        $sections = $this->sections($markdown);
        $chunks = [];
        $buffer = null;

        foreach ($sections as $section) {
            foreach ($this->splitLong($section) as $part) {
                if ($buffer === null) {
                    $buffer = $part;
                } elseif (self::words($buffer['content']) < self::MIN_WORDS && self::words($buffer['content'].' '.$part['content']) <= self::MAX_WORDS) {
                    // Section trop courte : on la regroupe avec la suivante (titres conservés dans le texte).
                    $buffer['content'] = trim($buffer['content']."\n\n".($part['heading'] !== '' ? $part['heading'].' : ' : '').$part['content']);
                } else {
                    $chunks[] = $buffer;
                    $buffer = $part;
                }
            }
        }

        if ($buffer !== null) {
            // Dernier fragment trop court : rattaché au précédent si la taille le permet.
            $last = array_key_last($chunks);
            if ($last !== null && self::words($buffer['content']) < self::MIN_WORDS
                && self::words($chunks[$last]['content'].' '.$buffer['content']) <= self::MAX_WORDS) {
                $chunks[$last]['content'] .= "\n\n".($buffer['heading'] !== '' ? $buffer['heading'].' : ' : '').$buffer['content'];
            } else {
                $chunks[] = $buffer;
            }
        }

        return array_values(array_map(fn (array $c): array => [
            'heading' => mb_substr($c['heading'] !== '' ? $title.' — '.$c['heading'] : $title, 0, 255),
            'content' => trim($c['content']),
            'words' => self::words($c['content']),
        ], array_filter($chunks, fn (array $c): bool => trim($c['content']) !== '')));
    }

    public static function words(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * @return list<array{heading: string, content: string}>
     */
    private function sections(string $markdown): array
    {
        $sections = [];
        $current = ['heading' => '', 'content' => ''];

        foreach (preg_split('/\R/u', $markdown) ?: [] as $line) {
            if (preg_match('/^#{1,4}\s+(.+)$/u', trim($line), $m) === 1) {
                if (trim($current['content']) !== '') {
                    $sections[] = $current;
                }
                $current = ['heading' => trim($m[1], " *#\t"), 'content' => ''];

                continue;
            }
            $current['content'] .= $line."\n";
        }

        if (trim($current['content']) !== '') {
            $sections[] = $current;
        }

        return array_map(fn (array $s): array => ['heading' => $s['heading'], 'content' => trim($s['content'])], $sections);
    }

    /**
     * @param  array{heading: string, content: string}  $section
     * @return list<array{heading: string, content: string}>
     */
    private function splitLong(array $section): array
    {
        if (self::words($section['content']) <= self::MAX_WORDS) {
            return [$section];
        }

        $parts = [];
        $current = '';
        foreach (preg_split('/\n\s*\n/u', $section['content']) ?: [] as $paragraph) {
            if ($current !== '' && self::words($current.' '.$paragraph) > self::MAX_WORDS) {
                $parts[] = ['heading' => $section['heading'], 'content' => trim($current)];
                $current = '';
            }
            $current .= "\n\n".$paragraph;
        }
        if (trim($current) !== '') {
            $parts[] = ['heading' => $section['heading'], 'content' => trim($current)];
        }

        return $parts;
    }
}
