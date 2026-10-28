<?php

namespace App\Domain\Content\Enums;

enum PageTemplate: string
{
    case Default = 'default';
    case Legal = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Standard',
            self::Legal => 'Légal',
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
