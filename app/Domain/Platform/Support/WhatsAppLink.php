<?php

namespace App\Domain\Platform\Support;

use App\Domain\Platform\Services\Settings;
use Illuminate\Support\Str;

/**
 * Lien wa.me vers le numéro principal, avec message pré-rempli selon le contexte (CONTENT §11, WEB-09).
 * Le contexte servira aussi à la mesure (`whatsapp_click`, TRD §9).
 */
class WhatsAppLink
{
    public const GENERAL_MESSAGE = 'Bonjour Kilakodon, je souhaite échanger sur un projet.';

    public const ASSISTANT_SUMMARY_MAX = 300;

    public static function for(string $context = 'general', ?string $text = null): string
    {
        $number = preg_replace('/\D+/', '', (string) app(Settings::class)->get('contact.whatsapp_primary'));

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text ?? self::GENERAL_MESSAGE);
    }

    public static function forSolution(string $solutionTitle): string
    {
        return self::for('solution', "Bonjour Kilakodon, je m'intéresse à « {$solutionTitle} ».");
    }

    public static function forBrief(string $reference): string
    {
        return self::for('brief', "Bonjour Kilakodon, je viens de valider mon brief {$reference}. Pouvons-nous en discuter ?");
    }

    public static function forDemo(): string
    {
        return self::for('lab', "Bonjour Kilakodon, j'ai testé la démo Boutique et j'aimerais un outil similaire.");
    }

    public static function forAssistant(string $summary): string
    {
        $summary = Str::limit(trim(preg_replace('/\s+/u', ' ', $summary) ?? ''), self::ASSISTANT_SUMMARY_MAX - 1, '…');

        return self::for('assistant', "Bonjour Kilakodon, je viens de l'assistant du site. Mon besoin : {$summary}");
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
