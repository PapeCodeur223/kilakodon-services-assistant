<?php

namespace App\Domain\Assistant\Enums;

enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case Notice = 'notice';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Visiteur',
            self::Assistant => 'Assistant',
            self::Notice => 'Information',
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
