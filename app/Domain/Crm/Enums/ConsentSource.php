<?php

namespace App\Domain\Crm\Enums;

enum ConsentSource: string
{
    case BriefForm = 'brief_form';
    case ContactForm = 'contact_form';
    case AppointmentForm = 'appointment_form';
    case Assistant = 'assistant';
    case CookieBanner = 'cookie_banner';

    public function label(): string
    {
        return match ($this) {
            self::BriefForm => 'Formulaire brief',
            self::ContactForm => 'Formulaire contact',
            self::AppointmentForm => 'Formulaire rendez-vous',
            self::Assistant => 'Assistant',
            self::CookieBanner => 'Bannière cookies',
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
