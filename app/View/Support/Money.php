<?php

namespace App\View\Support;

/**
 * Montants en FCFA (entiers) : « 17 500 FCFA », espace insécable fine comme séparateur de milliers.
 */
final class Money
{
    public static function xof(int $amount, bool $unit = true): string
    {
        $formatted = number_format($amount, 0, ',', "\u{202F}");

        return $unit ? $formatted."\u{00A0}FCFA" : $formatted;
    }
}
