<?php

namespace App\Domain\DemoLab\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case MobileMoney = 'mobile_money';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::MobileMoney => 'Mobile Money',
            self::Credit => 'Crédit',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'banknote',
            self::MobileMoney => 'smartphone',
            self::Credit => 'notebook-pen',
        };
    }
}
