<?php

namespace App\Http\Middleware;

use App\Domain\Platform\Models\User;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * MFA par application obligatoire pour le rôle admin uniquement (TRD §11).
 * Les autres rôles peuvent l'activer depuis leur profil.
 */
class RequireAdminMultiFactor extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
