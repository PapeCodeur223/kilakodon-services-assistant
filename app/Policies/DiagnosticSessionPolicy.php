<?php

namespace App\Policies;

use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

/**
 * Sessions de diagnostic : consultation par l'administrateur et le commercial, jamais d'écriture.
 */
class DiagnosticSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Sales);
    }

    public function view(User $user, DiagnosticSession $session): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DiagnosticSession $session): bool
    {
        return false;
    }

    public function delete(User $user, DiagnosticSession $session): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
