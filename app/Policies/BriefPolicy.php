<?php

namespace App\Policies;

use App\Domain\Brief\Models\Brief;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

/**
 * Briefs : administrateur et commercial (PRD §4.2). Les versions sont figées : aucune modification ;
 * seule la révocation du lien privé est possible.
 */
class BriefPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Sales);
    }

    public function view(User $user, Brief $brief): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Brief $brief): bool
    {
        return false;
    }

    public function delete(User $user, Brief $brief): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function revoke(User $user, Brief $brief): bool
    {
        return $this->viewAny($user) && ! $brief->isRevoked();
    }
}
