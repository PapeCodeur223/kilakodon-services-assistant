<?php

namespace App\Domain\Platform\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Choix du visiteur pour la mesure d'audience (PRV-01, TRD §9). Le cookie `kh_consent` mémorise le
 * choix (cookie nécessaire) ; le cookie `kh_vk` (visitor_key, 13 mois) n'existe que si la mesure est
 * acceptée. Sans accord, les événements restent agrégés, sans visitor_key. Q-011 : mesure désactivée
 * par défaut jusqu'à acceptation.
 */
final class AudienceConsent
{
    public const CHOICE_COOKIE = 'kh_consent';

    public const VISITOR_COOKIE = 'kh_vk';

    /** 13 mois, en minutes. */
    public const LIFETIME_MINUTES = 60 * 24 * 395;

    public function __construct(private readonly Request $request) {}

    public function decided(): bool
    {
        return in_array($this->request->cookie(self::CHOICE_COOKIE), ['1', '0'], true);
    }

    public function granted(): bool
    {
        return $this->request->cookie(self::CHOICE_COOKIE) === '1';
    }

    /**
     * La clé visiteur, seulement si la mesure d'audience est acceptée.
     */
    public function visitorKey(): ?string
    {
        if (! $this->granted()) {
            return null;
        }

        $key = $this->request->cookie(self::VISITOR_COOKIE);

        return is_string($key) && Str::isUuid($key) ? strtolower($key) : null;
    }

    /**
     * La clé actuelle même après un retrait, pour tracer le retrait sur le même visiteur.
     */
    public function storedVisitorKey(): ?string
    {
        $key = $this->request->cookie(self::VISITOR_COOKIE);

        return is_string($key) && Str::isUuid($key) ? strtolower($key) : null;
    }
}
