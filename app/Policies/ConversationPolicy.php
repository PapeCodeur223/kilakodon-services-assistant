<?php

namespace App\Policies;

use App\Domain\Assistant\Models\Conversation;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

/**
 * Conversations de l'assistant (PRD §3) : lecture seule. L'admin voit tout ; le commercial seulement
 * les conversations liées à ses leads ; l'éditeur n'y a pas accès (il voit les retours à revoir).
 */
class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Sales);
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole(UserRole::Sales)
            && $conversation->lead_id !== null
            && $conversation->lead()->where('owner_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return false;
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
