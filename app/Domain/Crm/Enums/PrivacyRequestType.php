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
     * Libellés du formulaire public, formulés du point de vue du visiteur.
     *
     * @return array<string, string>
     */
    public static function publicOptions(): array
    {
        return [
            self::Access->value => 'Recevoir une copie de mes données',
            self::Rectification->value => 'Corriger mes données',
            self::Erasure->value => 'Supprimer mes données',
            self::Objection->value => 'Ne plus être contacté',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
