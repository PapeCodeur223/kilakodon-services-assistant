<?php

namespace App\Domain\Assistant\Enums;

enum ReviewStatus: string
{
    case ToReview = 'to_review';
    case Reviewed = 'reviewed';
    case Actioned = 'actioned';

    public function label(): string
    {
        return match ($this) {
            self::ToReview => 'À revoir',
            self::Reviewed => 'Revu',
            self::Actioned => 'Corrigé',
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
