<?php

namespace App\Domain\Diagnostic\Engine;

use InvalidArgumentException;

/**
 * Évalue une condition JSON (TRD §6) sur des réponses.
 * Blocs `all` / `any` / `none` imbriquables ; une condition vide vaut vrai.
 * `trace()` renvoie en plus les questions qui ont rendu la condition vraie (« Pourquoi ? »).
 */
final class ConditionEvaluator
{
    public const OPERATORS = [
        'eq', 'neq', 'in', 'not_in', 'contains', 'contains_any', 'contains_none',
        'answered', 'not_answered', 'is_unknown',
    ];

    /**
     * @param  array<string, mixed>|null  $condition
     * @param  array<string, mixed>  $answers
     */
    public function passes(?array $condition, array $answers): bool
    {
        return $this->trace($condition, $answers) !== null;
    }

    /**
     * @param  array<string, mixed>|null  $condition
     * @param  array<string, mixed>  $answers
     * @return list<string>|null clés des questions déterminantes, ou null si la condition est fausse
     */
    public function trace(?array $condition, array $answers): ?array
    {
        if ($condition === null || $condition === []) {
            return [];
        }

        if (isset($condition['q'])) {
            return $this->leaf($condition, $answers) ? [(string) $condition['q']] : null;
        }

        $unknownBlocks = array_diff(array_keys($condition), ['all', 'any', 'none']);
        if ($unknownBlocks !== []) {
            throw new InvalidArgumentException('Bloc de condition inconnu : '.implode(', ', $unknownBlocks));
        }

        $keys = [];

        foreach ($condition['all'] ?? [] as $item) {
            $trace = $this->trace($item, $answers);
            if ($trace === null) {
                return null;
            }
            $keys = [...$keys, ...$trace];
        }

        if (isset($condition['any']) && $condition['any'] !== []) {
            $matched = false;
            foreach ($condition['any'] as $item) {
                $trace = $this->trace($item, $answers);
                if ($trace !== null) {
                    $matched = true;
                    $keys = [...$keys, ...$trace];
                }
            }
            if (! $matched) {
                return null;
            }
        }

        foreach ($condition['none'] ?? [] as $item) {
            if ($this->trace($item, $answers) !== null) {
                return null;
            }
            $keys = [...$keys, ...self::referencedQuestions($item)];
        }

        return array_values(array_unique($keys));
    }

    /**
     * Questions citées par une condition (validation des templates, « none »).
     *
     * @param  array<string, mixed>|null  $condition
     * @return list<string>
     */
    public static function referencedQuestions(?array $condition): array
    {
        if ($condition === null || $condition === []) {
            return [];
        }

        if (isset($condition['q'])) {
            return [(string) $condition['q']];
        }

        $keys = [];
        foreach (['all', 'any', 'none'] as $block) {
            foreach ($condition[$block] ?? [] as $item) {
                $keys = [...$keys, ...self::referencedQuestions($item)];
            }
        }

        return array_values(array_unique($keys));
    }

    public static function isAnswered(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }

    public static function isUnknown(mixed $value): bool
    {
        return $value === Catalog::UNKNOWN || $value === [Catalog::UNKNOWN];
    }

    /**
     * @param  array<string, mixed>  $leaf
     * @param  array<string, mixed>  $answers
     */
    private function leaf(array $leaf, array $answers): bool
    {
        $op = (string) ($leaf['op'] ?? '');
        $answer = $answers[(string) $leaf['q']] ?? null;
        $expected = $leaf['v'] ?? null;

        return match ($op) {
            'eq' => $this->equals($answer, $expected),
            'neq' => ! $this->equals($answer, $expected),
            'in' => $this->in($answer, $expected),
            'not_in' => ! $this->in($answer, $expected),
            'contains' => in_array((string) $expected, $this->values($answer), true),
            'contains_any' => array_intersect($this->strings($expected), $this->values($answer)) !== [],
            'contains_none' => array_intersect($this->strings($expected), $this->values($answer)) === [],
            'answered' => self::isAnswered($answer),
            'not_answered' => ! self::isAnswered($answer),
            'is_unknown' => self::isUnknown($answer),
            default => throw new InvalidArgumentException("Opérateur de condition inconnu : {$op}"),
        };
    }

    private function equals(mixed $answer, mixed $expected): bool
    {
        return is_scalar($answer) && is_scalar($expected) && (string) $answer === (string) $expected;
    }

    private function in(mixed $answer, mixed $expected): bool
    {
        return is_scalar($answer) && in_array((string) $answer, $this->strings($expected), true);
    }

    /**
     * Valeurs choisies (une réponse simple compte comme une liste d'un élément ; « Je ne sais pas » ne contient rien).
     *
     * @return list<string>
     */
    private function values(mixed $answer): array
    {
        if (self::isUnknown($answer) || ! self::isAnswered($answer)) {
            return [];
        }

        return $this->strings($answer);
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $value): array
    {
        $list = is_array($value) ? $value : [$value];

        return array_values(array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $list));
    }
}
