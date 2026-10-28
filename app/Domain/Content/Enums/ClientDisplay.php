<?php

namespace App\Domain\Content\Enums;

enum ClientDisplay: string
{
    case Named = 'named';
    case Anonymized = 'anonymized';

    public function label(): string
    {
        return match ($this) {
            self::Named => 'Client nommé',
            self::Anonymized => 'Client anonymisé',
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
