<?php

namespace App\View\Support;

/**
 * Lit le texte Markdown de la page « Méthode » (modifiable dans l'admin) pour la présenter en frise :
 * introduction, étapes « ## N. Titre » avec leur « **Livrable :** », et la liste « ## Nos engagements ».
 * Si le texte ne suit plus cette forme, la page revient au gabarit simple.
 *
 * @phpstan-type Step array{number: int, title: string, text: string, deliverable: string|null}
 */
final class MethodContent
{
    /**
     * @return array{intro: string, steps: list<Step>, engagements: list<string>}
     */
    public static function parse(string $body): array
    {
        $intro = [];
        $steps = [];
        $engagements = [];
        $section = 'intro';

        foreach (preg_split('/\R/u', $body) ?: [] as $line) {
            $line = trim($line);

            if (preg_match('/^##\s+(\d+)\s*[.)]\s*(.+)$/u', $line, $m) === 1) {
                $steps[] = ['number' => (int) $m[1], 'title' => trim($m[2]), 'text' => '', 'deliverable' => null];
                $section = 'step';

                continue;
            }

            if (preg_match('/^##\s+(.+)$/u', $line, $m) === 1) {
                $section = mb_stripos($m[1], 'engagement') !== false ? 'engagements' : 'other';

                continue;
            }

            if ($line === '') {
                continue;
            }

            if ($section === 'intro') {
                $intro[] = $line;
            } elseif ($section === 'step' && $steps !== []) {
                $last = array_key_last($steps);
                if (preg_match('/^\*\*Livrable\s*:\*\*\s*(.+)$/iu', $line, $m) === 1) {
                    $steps[$last]['deliverable'] = trim($m[1]);
                } else {
                    $steps[$last]['text'] = trim($steps[$last]['text'].' '.$line);
                }
            } elseif ($section === 'engagements' && preg_match('/^[-*]\s+(.+)$/u', $line, $m) === 1) {
                $engagements[] = trim($m[1]);
            }
        }

        return ['intro' => implode(' ', $intro), 'steps' => $steps, 'engagements' => $engagements];
    }
}
