<?php

namespace App\Domain\Content\Enums;

enum FaqCategory: string
{
    case General = 'general';
    case Pricing = 'pricing';
    case Process = 'process';
    case Support = 'support';
    case Diagnostic = 'diagnostic';
    case Ai = 'ai';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Général',
            self::Pricing => 'Tarifs',
            self::Process => 'Méthode',
            self::Support => 'Support',
            self::Diagnostic => 'Diagnostic',
            self::Ai => 'Assistant IA',
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
