<?php

namespace App\Domain\Platform\Enums;

enum OutboundStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'En attente',
            self::Sent => 'Envoyé',
            self::Failed => 'Échec',
            self::Abandoned => 'Abandonné',
        };
    }
}
