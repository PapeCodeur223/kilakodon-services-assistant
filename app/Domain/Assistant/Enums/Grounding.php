<?php

namespace App\Domain\Assistant\Enums;

enum Grounding: string
{
    case Sourced = 'sourced';
    case Partial = 'partial';
    case NoSource = 'no_source';

    public function label(): string
    {
        return match ($this) {
            self::Sourced => 'Fondée sur les sources',
            self::Partial => 'En partie sourcée',
            self::NoSource => 'Sans source',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
