<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mémorise en session les paramètres utm_* de la page d'arrivée, pour la source des leads.
 * Première source conservée (pas d'écrasement en cours de visite).
 */
class CaptureUtm
{
    private const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession() && ! $request->session()->has('kh.utm')) {
            $utm = array_filter(
                array_map(fn ($value): string => mb_substr(is_string($value) ? $value : '', 0, 100), $request->only(self::KEYS)),
                fn (string $value): bool => $value !== '',
            );

            if ($utm !== []) {
                $request->session()->put('kh.utm', $utm);
            }
        }

        return $next($request);
    }
}
