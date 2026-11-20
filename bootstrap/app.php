<?php

use App\Http\Middleware\CaptureUtm;
use App\Http\Middleware\EnsureEnvironment;
use App\Http\Middleware\LegacyRedirects;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Avant le routage : les anciennes URL n'ont pas de route.
        $middleware->prepend(LegacyRedirects::class);
        // Global : couvre aussi /admin (Filament) et les pages d'erreur.
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [CaptureUtm::class]);
        $middleware->alias([
            'env' => EnsureEnvironment::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    });

$app = $app->create();

// Production LWS (lot 7) : le code est dans ~/home/kilakodon-hub/current ; le .env et storage/ sont
// partagés entre les versions dans ~/home/kilakodon-hub/shared (repère : fichier .kh-shared).
// Sans liens symboliques, souvent désactivés en hébergement mutualisé. Sans effet en local.
$shared = dirname($app->basePath()).'/shared';
if (is_file($shared.'/.kh-shared')) {
    $app->useEnvironmentPath($shared);
    $app->useStoragePath($shared.'/storage');
}

return $app;
