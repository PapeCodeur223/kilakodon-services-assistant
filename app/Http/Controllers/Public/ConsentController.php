<?php

namespace App\Http\Controllers\Public;

use App\Domain\Crm\Actions\RecordConsent;
use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Platform\Analytics\AudienceConsent;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Choix de la bannière cookies (PRV-01) : simple formulaire, fonctionne sans JavaScript.
 * Accord ou refus écrits dans `consents` (écriture seule) ; le retrait est donc tracé.
 */
class ConsentController extends Controller
{
    public function update(Request $request, AudienceConsent $consent, RecordConsent $recordConsent): RedirectResponse
    {
        $data = $request->validate([
            'analytics' => ['required', 'in:accept,refuse'],
            'return' => ['nullable', 'string', 'max:255'],
        ]);

        $granted = $data['analytics'] === 'accept';
        $visitorKey = $granted ? ($consent->storedVisitorKey() ?? (string) Str::uuid()) : $consent->storedVisitorKey();

        // Une ligne à chaque changement de choix (pas de doublon si le visiteur renvoie le même choix).
        if (! $consent->decided() || $consent->granted() !== $granted) {
            $recordConsent->handle(null, ConsentPurpose::Analytics, $granted, ConsentSource::CookieBanner, $request->ip(), $visitorKey);
        }

        $response = redirect()->to($this->safeReturn($data['return'] ?? null))
            ->with('kh.consent_saved', $granted
                ? 'Merci, la mesure d’audience est activée.'
                : 'C’est noté : aucune mesure d’audience liée à votre navigateur.')
            ->withCookie(Cookie::make(AudienceConsent::CHOICE_COOKIE, $granted ? '1' : '0', AudienceConsent::LIFETIME_MINUTES));

        return $granted
            ? $response->withCookie(Cookie::make(AudienceConsent::VISITOR_COOKIE, (string) $visitorKey, AudienceConsent::LIFETIME_MINUTES))
            : $response->withCookie(Cookie::forget(AudienceConsent::VISITOR_COOKIE));
    }

    /**
     * Retour sur la page d'origine, chemin interne uniquement (pas de redirection ouverte).
     */
    private function safeReturn(?string $return): string
    {
        // Caractères de contrôle et espaces refusés : un navigateur supprime tabulations et retours à
        // la ligne, ce qui transformerait « /<tab>/site.test » en « //site.test ».
        if ($return === null || ! str_starts_with($return, '/') || str_starts_with($return, '//') || str_contains($return, '\\')
            || preg_match('/[\x00-\x20\x7F]/', $return) === 1) {
            return route('home');
        }

        return $return;
    }
}
