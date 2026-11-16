<?php

namespace App\Domain\Assistant\Enums;

enum ProposalStatus: string
{
    case Proposed = 'proposed';
    case Confirmed = 'confirmed';
    case Executed = 'executed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposée',
            self::Confirmed => 'Confirmée',
            self::Executed => 'Exécutée',
            self::Failed => 'Échec',
            self::Cancelled => 'Annulée',
            self::Expired => 'Expirée',
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
