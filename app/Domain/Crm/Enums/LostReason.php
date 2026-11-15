<?php

namespace App\Domain\Crm\Enums;

enum LostReason: string
{
    case Price = 'price';
    case Timing = 'timing';
    case Competitor = 'competitor';
    case NoResponse = 'no_response';
    case NotAFit = 'not_a_fit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Price => 'Prix',
            self::Timing => 'Calendrier',
            self::Competitor => 'Concurrent retenu',
            self::NoResponse => 'Sans réponse',
            self::NotAFit => 'Hors de notre périmètre',
            self::Other => 'Autre',
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
