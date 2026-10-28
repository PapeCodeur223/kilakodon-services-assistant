<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Score de maturité depuis les outils actuels (DIAGNOSTIC_SPEC §3) : un indicateur simple,
 * jamais présenté comme scientifique.
 */
final class MaturityScorer
{
    /**
     * @param  array<string, mixed>  $answers
     */
    public function score(array $answers): float
    {
        $tools = $answers['current_tools'] ?? [];
        $tools = is_array($tools) ? $tools : [$tools];

        $sum = 0.0;
        foreach (array_unique(array_map('strval', array_filter($tools, 'is_scalar'))) as $tool) {
            $sum += Catalog::TOOL_POINTS[$tool] ?? 0.0;
        }

        $score = min($sum, Catalog::MATURITY_CAP);

        if (($answers['existing_satisfaction'] ?? null) === 'ok') {
            $score += 1.0;
        }

        return round($score, 1);
    }

    public function level(float $score): int
    {
        return match (true) {
            $score <= 1.0 => 1,
            $score <= 3.0 => 2,
            $score <= 5.0 => 3,
            default => 4,
        };
    }
}
