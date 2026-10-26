<?php

namespace App\Domain\Platform\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Sales => 'Commercial',
            self::Editor => 'Éditeur',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}
