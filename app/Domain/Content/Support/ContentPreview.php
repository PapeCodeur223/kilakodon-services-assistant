<?php

namespace App\Domain\Content\Support;

use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use Throwable;

/**
 * Prévisualisation des brouillons par l'équipe (WEB-01) : un admin ou un éditeur connecté
 * voit aussi les contenus non publiés sur le site, avec un bandeau et en `noindex`.
 */
final class ContentPreview
{
    public static function enabled(): bool
    {
        try {
            $user = auth()->user();
        } catch (Throwable) {
            // Base indisponible (page 500) : jamais de prévisualisation.
            return false;
        }

        return $user instanceof User && $user->is_active && $user->hasRole(UserRole::Admin, UserRole::Editor);
    }

    /**
     * Masque au public une valeur qui contient encore « [À VALIDER] ».
     */
    public static function shows(mixed $value): bool
    {
        return ! PendingValidation::has($value) || self::enabled();
    }
}
