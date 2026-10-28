<?php

namespace App\Domain\Brief\Actions;

use App\Domain\Brief\Models\Brief;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;

/**
 * Révoque le lien privé d'un brief (BRF-04). Le brief reste consultable dans l'admin.
 */
class RevokeBriefAccess
{
    public function __construct(private readonly RecordAudit $audit) {}

    public function handle(Brief $brief): Brief
    {
        if ($brief->isRevoked()) {
            return $brief;
        }

        $brief->forceFill(['access_revoked_at' => now()])->save();
        $this->audit->handle(AuditAction::BriefAccessRevoked, $brief);

        return $brief;
    }
}
