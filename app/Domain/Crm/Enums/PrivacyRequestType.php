<?php

namespace App\Domain\Crm\Enums;

enum PrivacyRequestType: string
{
    case Access = 'access';
    case Rectification = 'rectification';
    case Erasure = 'erasure';
    case Objection = 'objection';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Accès',
            self::Rectification => 'Rectification',
            self::Erasure => 'Suppression',
            self::Objection => 'Opposition',
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
