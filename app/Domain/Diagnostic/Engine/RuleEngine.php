<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Évalue les règles actives par priorité et sélectionne recommandations, priorités et clarifications
 * (TRD §6, DIAGNOSTIC_SPEC §1). Chaque effet garde la règle et les questions qui l'ont déclenché.
 *
 * @phpstan-type Reason array{rule_key: string, explanation: string, question_keys: list<string>}
 * @phpstan-type Recommendation array{module: string, weight: int, label: string, solution: string, reasons: list<Reason>}
 * @phpstan-type Pain array{key: string, label: string, description: string, rule_keys: list<string>, source: string}
 * @phpstan-type Clarification array{key: string, label: string, rule_keys: list<string>}
 */
final class RuleEngine
{
    public function __construct(private readonly ConditionEvaluator $evaluator = new ConditionEvaluator) {}

    /**
     * @param  list<RuleDefinition>  $rules
     * @param  array<string, mixed>  $answers
     * @return array{recommendations: list<Recommendation>, pains: list<Pain>, clarifications: list<Clarification>}
     */
    public function evaluate(array $rules, array $answers): array
    {
        $rules = array_values(array_filter($rules, fn (RuleDefinition $rule): bool => $rule->isActive));
        // Tri stable : à priorité égale, l'ordre de déclaration du template (celui de la spec) est conservé.
        usort($rules, fn (RuleDefinition $a, RuleDefinition $b): int => $a->priority <=> $b->priority);

        /** @var array<string, array{weight: int, priority: int, reasons: list<Reason>}> $modules */
        $modules = [];
        /** @var array<string, array{count: int, priority: int, order: int, rule_keys: list<string>}> $pains */
        $pains = [];
        /** @var array<string, list<string>> $clarifications */
        $clarifications = [];

        foreach ($rules as $rule) {
            $questionKeys = $this->evaluator->trace($rule->conditions, $answers);
            if ($questionKeys === null) {
                continue;
            }

            foreach ($rule->effects as $effect) {
                $key = (string) ($effect['key'] ?? '');

                switch ($effect['type']) {
                    case RuleDefinition::RECOMMEND:
                        $module = (string) ($effect['module'] ?? '');
                        $modules[$module] ??= ['weight' => 0, 'priority' => $rule->priority, 'reasons' => []];
                        $modules[$module]['weight'] += (int) ($effect['weight'] ?? 0);
                        $modules[$module]['reasons'][] = ['rule_key' => $rule->key, 'explanation' => $rule->explanation, 'question_keys' => $questionKeys];
                        break;
                    case RuleDefinition::PAIN:
                        $pains[$key] ??= ['count' => 0, 'priority' => $rule->priority, 'order' => count($pains), 'rule_keys' => []];
                        $pains[$key]['count']++;
                        $pains[$key]['rule_keys'][] = $rule->key;
                        break;
                    case RuleDefinition::CLARIFY:
                        $clarifications[$key][] = $rule->key;
                        break;
                }
            }
        }

        [$recommendations, $scopeUnclear] = $this->selectModules($modules);

        if ($scopeUnclear) {
            $clarifications['scope_unclear'] ??= [];
        }

        return [
            'recommendations' => $recommendations,
            'pains' => $this->selectPains($pains),
            'clarifications' => array_values(array_map(
                fn (string $key, array $ruleKeys): array => ['key' => $key, 'label' => Catalog::CLARIFICATIONS[$key] ?? $key, 'rule_keys' => $ruleKeys],
                array_keys($clarifications),
                $clarifications,
            )),
        ];
    }

    /**
     * Modules ≥ 3 (5 au maximum) ; sinon le meilleur s'il atteint 2, avec `scope_unclear` ; sinon rien.
     * Égalité : priorité de règle la plus faible, puis ordre du catalogue.
     *
     * @param  array<string, array{weight: int, priority: int, reasons: list<Reason>}>  $modules
     * @return array{0: list<Recommendation>, 1: bool}
     */
    private function selectModules(array $modules): array
    {
        uksort($modules, fn (string $a, string $b): int => [-$modules[$a]['weight'], $modules[$a]['priority'], Catalog::moduleOrder($a)]
            <=> [-$modules[$b]['weight'], $modules[$b]['priority'], Catalog::moduleOrder($b)]);

        $selected = array_filter($modules, fn (array $m): bool => $m['weight'] >= Catalog::RECOMMEND_THRESHOLD);
        $scopeUnclear = false;

        if ($selected === []) {
            $scopeUnclear = true;
            $best = array_slice($modules, 0, 1, true);
            $selected = array_filter($best, fn (array $m): bool => $m['weight'] >= Catalog::FALLBACK_THRESHOLD);
        }

        $selected = array_slice($selected, 0, Catalog::MAX_RECOMMENDATIONS, true);

        return [array_values(array_map(fn (string $module, array $m): array => [
            'module' => $module,
            'weight' => $m['weight'],
            'label' => Catalog::moduleLabel($module),
            'solution' => Catalog::MODULES[$module]['solution'] ?? '',
            'reasons' => $m['reasons'],
        ], array_keys($selected), $selected)), $scopeUnclear];
    }

    /**
     * Au plus 3 priorités, par fréquence puis priorité de règle (TRD §6).
     *
     * @param  array<string, array{count: int, priority: int, order: int, rule_keys: list<string>}>  $pains
     * @return list<Pain>
     */
    private function selectPains(array $pains): array
    {
        uksort($pains, fn (string $a, string $b): int => [-$pains[$a]['count'], $pains[$a]['priority'], $pains[$a]['order']]
            <=> [-$pains[$b]['count'], $pains[$b]['priority'], $pains[$b]['order']]);

        $pains = array_slice($pains, 0, Catalog::MAX_PAINS, true);

        return array_values(array_map(fn (string $key, array $p): array => PainCompleter::pain($key, $p['rule_keys'], 'rule'), array_keys($pains), $pains));
    }
}
