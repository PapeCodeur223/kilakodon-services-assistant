<?php

namespace App\Policies;

use App\Domain\Content\Concerns\Publishable;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Contenus (solutions, secteurs, réalisations, articles, FAQ, pages) — PRD §4.2 :
 * admin et éditeur rédigent ; seul l'admin publie, dépublie, archive et supprime.
 * Un contenu publié ou archivé n'est plus modifiable par un éditeur.
 */
class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Editor);
    }

    public function view(User $user, Model $content): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * @param  Model&Publishable  $content
     */
    public function update(User $user, Model $content): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasRole(UserRole::Editor)
            && in_array($content->status, [ContentStatus::Draft, ContentStatus::Review], true);
    }

    public function changeStatus(User $user, Model $content): bool
    {
        return $this->viewAny($user);
    }

    public function publish(User $user, Model $content): bool
    {
        return $user->isAdmin();
    }

    public function attestAuthorization(User $user, Model $content): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Model $content): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Model $content): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Model $content): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
