<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;

/**
 * Réinitialise la double authentification d'un utilisateur qui a perdu son application et ses codes
 * de secours : secret et codes effacés, nouvelle configuration demandée à la prochaine connexion
 * (obligatoire pour un admin). Toujours journalisé (TRD §11).
 */
class ResetMultiFactor
{
    public function __construct(private readonly RecordAudit $audit) {}

    public function handle(User $user, ?User $by = null): User
    {
        $user->saveAppAuthenticationSecret(null);
        $user->saveAppAuthenticationRecoveryCodes(null);

        $this->audit->handle(AuditAction::MultiFactorReset, $user, ['after' => ['email' => $user->email]], $by);

        return $user;
    }
}
