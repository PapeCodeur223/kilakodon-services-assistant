<?php

namespace App\Domain\Assistant\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case HandedOff = 'handed_off';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::HandedOff => 'Transférée',
            self::Closed => 'Fermée',
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
