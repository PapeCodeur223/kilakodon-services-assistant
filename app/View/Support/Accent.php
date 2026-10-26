<?php

namespace App\View\Support;

use Illuminate\Support\HtmlString;

/**
 * Titre avec fragment d'accent : « Pilotez … *faits pour elle*. »
 * Le texte est échappé AVANT la conversion de *…* en <em>, seule balise produite.
 */
class Accent
{
    public static function html(?string $text): HtmlString
    {
        $escaped = e((string) $text);

        return new HtmlString((string) preg_replace('/\*([^*]+)\*/u', '<em>$1</em>', $escaped, 1));
    }

    public static function plain(?string $text): string
    {
        return str_replace('*', '', (string) $text);
    }
}
