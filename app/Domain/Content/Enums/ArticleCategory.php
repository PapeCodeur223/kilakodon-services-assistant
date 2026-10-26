<?php

namespace App\Domain\Content\Enums;

enum ArticleCategory: string
{
    case Guides = 'guides';
    case Stock = 'stock';
    case Choosing = 'choosing';
    case Field = 'field';

    public function label(): string
    {
        return match ($this) {
            self::Guides => 'Guides',
            self::Stock => 'Stock & caisse',
            self::Choosing => 'Bien choisir',
            self::Field => 'Réalités terrain',
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
