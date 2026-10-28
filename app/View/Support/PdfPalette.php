<?php

namespace App\View\Support;

/**
 * Couleurs des documents PDF. dompdf ne lit ni Tailwind ni les variables CSS : ces valeurs
 * recopient les tokens de resources/css/app.css (UX §3.1) et doivent rester alignées sur eux.
 */
final class PdfPalette
{
    public const TOKENS = [
        'ink' => '#0F1117',
        'slate' => '#5B5F72',
        'line' => '#E6E7EE',
        'fog' => '#F7F8FC',
        'blue' => '#282A89',
        'blue-50' => '#F1F2FB',
        'blue-100' => '#E3E4F6',
        'red' => '#D4170A',
        'success' => '#147A4B',
        'success-50' => '#E8F5EE',
    ];

    public static function get(string $token): string
    {
        return self::TOKENS[$token];
    }
}
