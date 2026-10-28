<?php

namespace App\Domain\Diagnostic\Engine;

/**
 * Questions et options visibles selon les réponses (DIA-01).
 * `prune()` supprime les réponses devenues non pertinentes et nettoie les valeurs
 * (option masquée, choix exclusif, « Je ne sais pas » non autorisé, texte trop long).
 *
 * @phpstan-import-type Option from QuestionDefinition
 */
final class VisibilityResolver
{
    public function __construct(private readonly ConditionEvaluator $evaluator = new ConditionEvaluator) {}

    /**
     * @param  array<string, mixed>  $answers
     * @return list<QuestionDefinition>
     */
    public function visibleQuestions(TemplateDefinition $template, array $answers, ?int $step = null): array
    {
        $visible = [];

        foreach ($template->questions as $question) {
            if ($step !== null && $question->step !== $step) {
                continue;
            }
            if ($this->evaluator->passes($question->showIf, $answers)) {
                $visible[] = $question;
            }
        }

        return $visible;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return list<Option>
     */
    public function visibleOptions(QuestionDefinition $question, array $answers): array
    {
        return array_values(array_filter(
            $question->options,
            fn (array $option): bool => $this->evaluator->passes($option['show_if'] ?? null, $answers),
        ));
    }

    /**
     * Répète le nettoyage jusqu'à stabilité : retirer une réponse peut masquer d'autres questions.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function prune(TemplateDefinition $template, array $answers): array
    {
        for ($pass = 0; $pass < 10; $pass++) {
            $cleaned = [];

            foreach ($template->questions as $key => $question) {
                if (! array_key_exists($key, $answers) || ! $this->evaluator->passes($question->showIf, $answers)) {
                    continue;
                }

                $value = $this->clean($question, $answers[$key], $answers);
                if (ConditionEvaluator::isAnswered($value)) {
                    $cleaned[$key] = $value;
                }
            }

            if ($cleaned === $answers) {
                return $cleaned;
            }
            $answers = $cleaned;
        }

        return $answers;
    }

    /**
     * Questions requises et visibles d'une étape restées sans réponse.
     *
     * @param  array<string, mixed>  $answers
     * @return list<string>
     */
    public function missingRequired(TemplateDefinition $template, array $answers, int $step): array
    {
        $missing = [];

        foreach ($this->visibleQuestions($template, $answers, $step) as $question) {
            if ($question->required && ! ConditionEvaluator::isAnswered($answers[$question->key] ?? null)) {
                $missing[] = $question->key;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private function clean(QuestionDefinition $question, mixed $value, array $answers): mixed
    {
        if (ConditionEvaluator::isUnknown($value)) {
            return $question->allowUnknown ? Catalog::UNKNOWN : null;
        }

        $allowed = array_map(fn (array $option): string => $option['value'], $this->visibleOptions($question, $answers));

        return match ($question->type) {
            QuestionDefinition::SINGLE => is_scalar($value) && in_array((string) $value, $allowed, true) ? (string) $value : null,
            QuestionDefinition::MULTI => $this->cleanMulti($question, $value, $allowed),
            QuestionDefinition::TEXT => is_scalar($value) ? mb_substr(trim((string) $value), 0, Catalog::TEXT_MAX_LENGTH) : null,
            QuestionDefinition::NUMBER => is_numeric($value) ? $value + 0 : null,
            default => null,
        };
    }

    /**
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function cleanMulti(QuestionDefinition $question, mixed $value, array $allowed): array
    {
        $values = is_array($value) ? $value : [$value];
        // Ordre du questionnaire, sans doublon ni valeur inconnue : le résultat ne dépend pas de l'ordre des clics.
        $values = array_values(array_filter($allowed, fn (string $option): bool => in_array($option, array_map('strval', array_filter($values, 'is_scalar')), true)));

        foreach ($values as $selected) {
            if ($question->option($selected)['exclusive'] ?? false) {
                return [$selected];
            }
        }

        if ($question->maxSelections !== null) {
            $values = array_slice($values, 0, $question->maxSelections);
        }

        return $values;
    }
}
