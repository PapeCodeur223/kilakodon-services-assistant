<?php

namespace App\Domain\Assistant\Enums;

enum KnowledgeStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Approved => 'Approuvé',
            self::Retired => 'Retiré',
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
