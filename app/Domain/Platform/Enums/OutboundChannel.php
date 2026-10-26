<?php

namespace App\Domain\Platform\Enums;

enum OutboundChannel: string
{
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'E-mail',
        };
    }
}
