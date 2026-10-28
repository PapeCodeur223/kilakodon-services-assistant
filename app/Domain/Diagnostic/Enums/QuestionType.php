<?php

namespace App\Domain\Diagnostic\Enums;

enum QuestionType: string
{
    case Single = 'single';
    case Multi = 'multi';
    case Text = 'text';
    case Number = 'number';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Choix unique',
            self::Multi => 'Choix multiple',
            self::Text => 'Texte libre',
            self::Number => 'Nombre',
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
