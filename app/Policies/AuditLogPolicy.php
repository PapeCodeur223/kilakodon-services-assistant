<?php

namespace App\Policies;

use App\Domain\Platform\Models\AuditLog;
use App\Domain\Platform\Models\User;

/**
 * Journal d'audit : consultation par les administrateurs, aucune écriture depuis l'admin.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
