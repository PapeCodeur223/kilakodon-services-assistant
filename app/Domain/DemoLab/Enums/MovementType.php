<?php

namespace App\Domain\DemoLab\Enums;

enum MovementType: string
{
    case Sale = 'sale';
    case Restock = 'restock';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Vente',
            self::Restock => 'Réassort',
            self::Adjustment => 'Ajustement',
        };
    }
}
