<?php

namespace App\Domain\Content\Support;

/**
 * Repère les informations non confirmées par Kilakodon, marquées « [À VALIDER] ».
 */
final class PendingValidation
{
    public const MARKER = '[À VALIDER';

    /**
     * Chemins des valeurs contenant le marqueur (ex. « features.2.title »).
     *
     * @return list<string>
     */
    public static function find(mixed $value, string $path = ''): array
    {
        if (is_string($value)) {
            return mb_stripos($value, self::MARKER) !== false ? [$path === '' ? '(valeur)' : $path] : [];
        }

        if (! is_array($value)) {
            return [];
        }

        $found = [];
        foreach ($value as $key => $item) {
            $found = [...$found, ...self::find($item, $path === '' ? (string) $key : $path.'.'.$key)];
        }

        return $found;
    }

    public static function has(mixed $value): bool
    {
        return self::find($value) !== [];
    }
}
