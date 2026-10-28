<?php

namespace App\Domain\Diagnostic\Enums;

enum SessionStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Saved = 'saved';
    case Converted = 'converted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'En cours',
            self::Completed => 'Terminé',
            self::Saved => 'Enregistré',
            self::Converted => 'Converti en brief',
            self::Expired => 'Expiré',
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
