<?php

namespace App\Domain\Assistant\Enums;

enum FeedbackRating: string
{
    case Up = 'up';
    case Down = 'down';

    public function label(): string
    {
        return match ($this) {
            self::Up => 'Utile',
            self::Down => 'Pas utile',
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
