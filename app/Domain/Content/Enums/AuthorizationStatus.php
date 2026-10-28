<?php

namespace App\Domain\Content\Enums;

enum AuthorizationStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Granted = 'granted';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Aucune',
            self::Pending => 'En attente',
            self::Granted => 'Accordée',
            self::Refused => 'Refusée',
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
