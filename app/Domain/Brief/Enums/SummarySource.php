<?php

namespace App\Domain\Brief\Enums;

enum SummarySource: string
{
    case Ai = 'ai';
    case Template = 'template';
    case ProspectEdited = 'prospect_edited';

    public function label(): string
    {
        return match ($this) {
            self::Ai => 'Rédigé automatiquement',
            self::Template => 'Résumé type',
            self::ProspectEdited => 'Corrigé par le prospect',
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
