<?php

namespace App\Policies;

use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Contacts, entreprises et rendez-vous : consultation et mise à jour par l'admin et le commercial,
 * jamais par l'éditeur. Pas de suppression depuis l'interface ; la fusion de contacts est réservée
 * à ces mêmes rôles et journalisée.
 */
class CrmRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Sales);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return $this->viewAny($user);
    }

    public function merge(User $user, Model $record): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
