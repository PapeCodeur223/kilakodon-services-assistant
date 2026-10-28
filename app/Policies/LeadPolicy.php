<?php

namespace App\Policies;

use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

/**
 * Leads (CRM) — admin et commercial ; jamais l'éditeur. L'étape ne change que par l'action dédiée ;
 * aucune suppression depuis l'interface (les données personnelles se traitent via PRV, lot 6).
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
        return $this->viewAny($user);
    }

    public function changeStage(User $user, Lead $lead): bool
    {
        return $this->viewAny($user) && $lead->stage->allowedTransitions() !== [];
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $this->viewAny($user);
    }

    public function logActivity(User $user, Lead $lead): bool
    {
        return $this->viewAny($user);
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
