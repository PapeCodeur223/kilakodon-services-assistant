<?php

namespace App\Domain\Crm\Enums;

enum LeadStage: string
{
    case New = 'new';
    case Qualified = 'qualified';
    case Meeting = 'meeting';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Qualified => 'Qualifié',
            self::Meeting => 'Rendez-vous',
            self::Proposal => 'Proposition',
            self::Won => 'Gagné',
            self::Lost => 'Perdu',
            self::OnHold => 'En pause',
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
