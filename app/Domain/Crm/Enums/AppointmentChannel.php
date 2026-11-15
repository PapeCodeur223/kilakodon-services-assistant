<?php

namespace App\Domain\Crm\Enums;

enum AppointmentChannel: string
{
    case Whatsapp = 'whatsapp';
    case Phone = 'phone';
    case Video = 'video';
    case Onsite = 'onsite';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'Appel WhatsApp',
            self::Phone => 'Appel téléphonique',
            self::Video => 'Visioconférence',
            self::Onsite => 'Rencontre sur place',
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
