<?php

namespace App\Domain\Crm\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Done = 'done';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandé',
            self::Confirmed => 'Confirmé',
            self::Cancelled => 'Annulé',
            self::Done => 'Réalisé',
            self::NoShow => 'Absent',
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
