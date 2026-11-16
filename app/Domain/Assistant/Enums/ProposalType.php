<?php

namespace App\Domain\Assistant\Enums;

enum ProposalType: string
{
    case ProposeBrief = 'propose_brief';
    case RequestAppointment = 'request_appointment';

    public function label(): string
    {
        return match ($this) {
            self::ProposeBrief => 'Brief',
            self::RequestAppointment => 'Rendez-vous',
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
