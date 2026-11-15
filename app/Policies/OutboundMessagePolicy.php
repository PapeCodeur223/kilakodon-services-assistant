<?php

namespace App\Policies;

use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;

/**
 * Écran « Envois » (Réglages) : administrateur uniquement ; seul le renvoi est possible.
 */
class OutboundMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, OutboundMessage $message): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, OutboundMessage $message): bool
    {
        return false;
    }

    public function resend(User $user, OutboundMessage $message): bool
    {
        return $user->isAdmin() && in_array($message->status, [OutboundStatus::Failed, OutboundStatus::Abandoned], true);
    }

    public function delete(User $user, OutboundMessage $message): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
