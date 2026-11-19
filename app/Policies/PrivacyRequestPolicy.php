<?php

namespace App\Policies;

use App\Domain\Crm\Models\PrivacyRequest;
use App\Domain\Platform\Models\User;

/**
 * Demandes de droits (PRV-02) : réservées aux administrateurs. Une demande reçue par téléphone
 * peut être saisie à la main ; aucune suppression (la trace du traitement est conservée).
 */
class PrivacyRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PrivacyRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PrivacyRequest $request): bool
    {
        return $user->isAdmin();
    }

    /** Export JSON et anonymisation du contact lié. */
    public function process(User $user, PrivacyRequest $request): bool
    {
        return $user->isAdmin() && $user->is_active;
    }

    public function delete(User $user, PrivacyRequest $request): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
