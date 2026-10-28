<?php

namespace App\Domain\Brief\Enums;

enum VersionAuthor: string
{
    case Prospect = 'prospect';
    case Staff = 'staff';
    case Assistant = 'assistant';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Prospect',
            self::Staff => 'Équipe',
            self::Assistant => 'Assistant',
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
