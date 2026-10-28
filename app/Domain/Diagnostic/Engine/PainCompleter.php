<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Moins de 3 priorités déclenchées : on complète par l'objectif principal (DIAGNOSTIC_SPEC §5).
 *
 * @phpstan-import-type Pain from RuleEngine
 */
final class PainCompleter
{
    /**
     * @param  list<Pain>  $pains
     * @param  array<string, mixed>  $answers
     * @return list<Pain>
     */
    public function complete(array $pains, array $answers): array
    {
        if (count($pains) >= Catalog::MAX_PAINS) {
            return $pains;
        }

        $goal = $answers['main_goal'] ?? null;
        $present = array_column($pains, 'key');

        foreach (is_string($goal) ? (Catalog::GOAL_PAINS[$goal] ?? []) : [] as $candidate) {
            if (! in_array($candidate, $present, true)) {
                $pains[] = self::pain($candidate, [], 'goal');
                break;
            }
        }

        return $pains;
    }

    /**
     * @param  list<string>  $ruleKeys
     * @return Pain
     */
    public static function pain(string $key, array $ruleKeys, string $source): array
    {
        return [
            'key' => $key,
            'label' => Catalog::PAINS[$key]['title'] ?? $key,
            'description' => Catalog::PAINS[$key]['description'] ?? '',
            'rule_keys' => $ruleKeys,
            'source' => $source,
        ];
    }
}
