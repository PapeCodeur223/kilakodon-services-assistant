<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité — TRD §11.
 * Pages publiques : scripts `self` + nonce. Back-office Filament : Alpine/Livewire exigent
 * 'unsafe-eval' et des scripts en ligne (voir DECISIONS.md).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request));
        }

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        $nonce = Vite::cspNonce();
        $isAdmin = $request->is('admin', 'admin/*', 'livewire/*', 'filament/*');
        $devServer = $this->viteDevServer();

        $script = $isAdmin
            ? ["'self'", "'unsafe-inline'", "'unsafe-eval'"]
            : ["'self'", "'nonce-{$nonce}'"];
        $style = ["'self'", "'unsafe-inline'"];
        $connect = ["'self'"];
        $img = ["'self'", 'data:', 'blob:'];

        if ($devServer !== null) {
            $script[] = $devServer;
            $style[] = $devServer;
            $connect[] = $devServer;
            $connect[] = str_replace('http', 'ws', $devServer);
            $img[] = $devServer;
        }

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => $script,
            'style-src' => $style,
            'img-src' => $img,
            'font-src' => $devServer !== null ? ["'self'", 'data:', $devServer] : ["'self'", 'data:'],
            'connect-src' => $connect,
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'object-src' => ["'none'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $name): string => $name.' '.implode(' ', array_unique($sources)))
            ->implode('; ');
    }

    /**
     * URL du serveur Vite en développement (fichier public/hot), sinon null.
     */
    private function viteDevServer(): ?string
    {
        $hot = public_path('hot');

        if (! app()->isLocal() || ! is_file($hot)) {
            return null;
        }

        $url = rtrim(trim((string) file_get_contents($hot)), '/');

        return preg_match('#^https?://[^/\s]+$#', $url) ? $url : null;
    }
}
