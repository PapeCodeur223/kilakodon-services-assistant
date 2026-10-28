<?php

namespace App\Domain\Diagnostic\Engine;

use InvalidArgumentException;

/**
 * Un questionnaire versionné et ses règles, tel que le moteur le consomme.
 */
final readonly class TemplateDefinition
{
    /** Clés et valeurs d'options : elles servent d'identifiants dans le HTML et les expressions Livewire. */
    public const SAFE_KEY = '/^[a-z0-9_]{1,48}$/';

    /** @var array<string, QuestionDefinition> */
    public array $questions;

    /** @var list<RuleDefinition> */
    public array $rules;

    /**
     * @param  list<QuestionDefinition>  $questions
     * @param  list<RuleDefinition>  $rules
     */
    public function __construct(public int $version, array $questions, array $rules)
    {
        usort($questions, fn (QuestionDefinition $a, QuestionDefinition $b): int => [$a->step, $a->sort] <=> [$b->step, $b->sort]);

        $indexed = [];
        foreach ($questions as $question) {
            $indexed[$question->key] = $question;
        }

        $this->questions = $indexed;
        $this->rules = $rules;
    }

    /**
     * @param  array{version: int, questions: list<array<string, mixed>>, rules: list<array<string, mixed>>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['version'],
            array_map(fn (array $row, int $i): QuestionDefinition => QuestionDefinition::fromArray($row, $i), $data['questions'], array_keys($data['questions'])),
            array_map(fn (array $row): RuleDefinition => RuleDefinition::fromArray($row), $data['rules']),
        );
    }

    public function question(string $key): ?QuestionDefinition
    {
        return $this->questions[$key] ?? null;
    }

    /**
     * @return list<int>
     */
    public function steps(): array
    {
        $steps = array_values(array_unique(array_map(fn (QuestionDefinition $q): int => $q->step, $this->questions)));
        sort($steps);

        return $steps;
    }

    /**
     * Cohérence d'un template avant activation : opérateurs connus, questions citées existantes,
     * modules, priorités et clarifications présents au catalogue.
     *
     * @return list<string> erreurs lisibles (vide si le template est valide)
     */
    public function validate(): array
    {
        $errors = [];
        $evaluator = new ConditionEvaluator;

        $checkCondition = function (?array $condition, string $where) use (&$errors, $evaluator): void {
            foreach (ConditionEvaluator::referencedQuestions($condition) as $key) {
                if (! isset($this->questions[$key])) {
                    $errors[] = "{$where} : question « {$key} » inconnue.";
                }
            }
            try {
                $evaluator->trace($condition, []);
            } catch (InvalidArgumentException $e) {
                $errors[] = "{$where} : {$e->getMessage()}";
            }
        };

        if ($this->questions === []) {
            $errors[] = 'Le template ne contient aucune question.';
        }

        foreach ($this->questions as $question) {
            if (preg_match(self::SAFE_KEY, $question->key) !== 1) {
                $errors[] = "Question « {$question->key} » : clé invalide (minuscules, chiffres et _ uniquement).";
            }
            foreach ($question->options as $option) {
                if (preg_match(self::SAFE_KEY, $option['value']) !== 1) {
                    $errors[] = "Option « {$question->key}.{$option['value']} » : valeur invalide (minuscules, chiffres et _ uniquement).";
                }
            }
            if (! in_array($question->type, [QuestionDefinition::SINGLE, QuestionDefinition::MULTI, QuestionDefinition::TEXT, QuestionDefinition::NUMBER], true)) {
                $errors[] = "Question « {$question->key} » : type « {$question->type} » inconnu.";
            }
            if ($question->hasOptions() && $question->options === []) {
                $errors[] = "Question « {$question->key} » : aucune option.";
            }
            $checkCondition($question->showIf, "Question « {$question->key} »");
            foreach ($question->options as $option) {
                $checkCondition($option['show_if'] ?? null, "Option « {$question->key}.{$option['value']} »");
            }
        }

        foreach ($this->rules as $rule) {
            $checkCondition($rule->conditions, "Règle « {$rule->key} »");
            foreach ($rule->effects as $effect) {
                $valid = match ($effect['type']) {
                    RuleDefinition::RECOMMEND => isset(Catalog::MODULES[$effect['module'] ?? '']) && ($effect['weight'] ?? 0) > 0,
                    RuleDefinition::PAIN => isset(Catalog::PAINS[$effect['key'] ?? '']),
                    RuleDefinition::CLARIFY => isset(Catalog::CLARIFICATIONS[$effect['key'] ?? '']),
                    default => false,
                };
                if (! $valid) {
                    $errors[] = "Règle « {$rule->key} » : effet invalide (".json_encode($effect).').';
                }
            }
        }

        return $errors;
    }
}
