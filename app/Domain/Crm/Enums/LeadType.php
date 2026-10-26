<?php

namespace App\Domain\Crm\Enums;

enum LeadType: string
{
    case Diagnostic = 'diagnostic';
    case Contact = 'contact';
    case Appointment = 'appointment';
    case Assistant = 'assistant';
    case Lab = 'lab';

    public function label(): string
    {
        return match ($this) {
            self::Diagnostic => 'Diagnostic',
            self::Contact => 'Contact',
            self::Appointment => 'Rendez-vous',
            self::Assistant => 'Assistant',
            self::Lab => 'Démo Lab',
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
