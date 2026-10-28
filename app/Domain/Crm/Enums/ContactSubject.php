<?php

namespace App\Domain\Crm\Enums;

enum ContactSubject: string
{
    case Project = 'project';
    case Demo = 'demo';
    case Support = 'support';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Project => 'Un projet de logiciel',
            self::Demo => 'Une démonstration',
            self::Support => 'Le support d’un logiciel existant',
            self::Other => 'Autre demande',
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
