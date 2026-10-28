<?php

namespace App\Policies;

use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

/**
 * Leads (lot 1 : consultation seule) — admin et commercial ; jamais l'éditeur.
 * Les droits d'écriture et le Kanban arrivent au lot 3.
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Sales);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Lead $lead): bool
    {
        return false;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
