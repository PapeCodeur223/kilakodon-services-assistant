<?php

namespace App\Policies;

use App\Domain\Assistant\Models\AiFeedback;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

/**
 * Retours sur les réponses de l'assistant (IA-06) : revue hebdomadaire par l'éditeur ou l'admin.
 */
class AiFeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Editor);
    }

    public function view(User $user, AiFeedback $feedback): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AiFeedback $feedback): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, AiFeedback $feedback): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
