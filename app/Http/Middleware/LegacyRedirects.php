<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301 depuis les anciennes URL de kilakodon-services.com (TRD §5.3, config/kh_redirects.php).
 */
class LegacyRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $target = $this->target($request);

        // URL absolue construite depuis l'hôte : sur « /index.php », url() garderait le script dans la base.
        return $target !== null ? redirect()->away(rtrim($request->getSchemeAndHttpHost().$target, '/'), 301) : $next($request);
    }

    private function target(Request $request): ?string
    {
        // L'URI brute est nécessaire : « /index.php » est le contrôleur frontal et disparaît de path().
        $path = '/'.ltrim((string) parse_url($request->getRequestUri(), PHP_URL_PATH), '/');
        $path = rtrim($path, '/') ?: '/';

        /** @var array<string, string> $paths */
        $paths = config('kh_redirects.paths', []);
        if (isset($paths[$path])) {
            return $paths[$path];
        }

        /** @var array<string, array{param: string, map: array<string, string>, fallback: string}> $queries */
        $queries = config('kh_redirects.query', []);
        if (isset($queries[$path])) {
            $rule = $queries[$path];

            return $rule['map'][(string) $request->query($rule['param'])] ?? $rule['fallback'];
        }

        return null;
    }
}
