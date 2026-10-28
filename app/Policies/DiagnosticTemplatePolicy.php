<?php

namespace App\Policies;

use App\Domain\Diagnostic\Models\DiagnosticTemplate;
use App\Domain\Platform\Models\User;

/**
 * Questionnaires de diagnostic : réservés à l'administrateur. Un template actif ou retiré
 * n'est plus modifiable ni supprimable (BACKEND_SCHEMA §4) ; on le duplique.
 */
class DiagnosticTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, DiagnosticTemplate $template): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, DiagnosticTemplate $template): bool
    {
        return $user->isAdmin() && ! $template->isLocked();
    }

    public function delete(User $user, DiagnosticTemplate $template): bool
    {
        return $user->isAdmin() && ! $template->isLocked();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function activate(User $user, DiagnosticTemplate $template): bool
    {
        return $user->isAdmin() && ! $template->isLocked();
    }

    public function duplicate(User $user, DiagnosticTemplate $template): bool
    {
        return $user->isAdmin();
    }
}
