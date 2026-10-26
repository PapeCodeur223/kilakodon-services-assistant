<?php

namespace App\Domain\Platform\Support;

use App\Domain\Platform\Services\Settings;

/**
 * Lien wa.me vers le numéro principal, avec message pré-rempli (CONTENT §11).
 * Le contexte servira à la mesure (TRD §9) et aux messages contextuels (lot 1).
 */
class WhatsAppLink
{
    public const GENERAL_MESSAGE = 'Bonjour Kilakodon, je souhaite échanger sur un projet.';

    public static function for(string $context = 'general', ?string $text = null): string
    {
        $number = preg_replace('/\D+/', '', (string) app(Settings::class)->get('contact.whatsapp_primary'));

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text ?? self::GENERAL_MESSAGE);
    }

    /**
     * Numéro affichable : « +223 74 66 64 89 ».
     */
    public static function display(string $e164): string
    {
        $digits = preg_replace('/\D+/', '', $e164) ?? '';

        if (str_starts_with($digits, '223') && strlen($digits) === 11) {
            return '+223 '.implode(' ', str_split(substr($digits, 3), 2));
        }

        return $e164;
    }
}
