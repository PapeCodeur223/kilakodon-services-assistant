<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint une route à un environnement (ex. `env:local` pour /_styleguide) ; 404 sinon.
 */
class EnsureEnvironment
{
    public function handle(Request $request, Closure $next, string ...$environments): Response
    {
        abort_unless(app()->environment($environments), 404);

        return $next($request);
    }
}
