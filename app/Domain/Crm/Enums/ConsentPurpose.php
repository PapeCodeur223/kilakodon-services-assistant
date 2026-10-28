<?php

namespace App\Domain\Crm\Enums;

enum ConsentPurpose: string
{
    case RequestProcessing = 'request_processing';
    case Marketing = 'marketing';
    case Analytics = 'analytics';

    public function label(): string
    {
        return match ($this) {
            self::RequestProcessing => 'Traitement de la demande',
            self::Marketing => 'Actualités',
            self::Analytics => 'Mesure d’audience',
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
