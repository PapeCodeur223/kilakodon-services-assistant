<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;

/**
 * Attribue un lead à un responsable actif (commercial ou administrateur), ou le désattribue.
 */
class AssignLead
{
    public function __construct(private readonly RecordAudit $audit) {}

    public function handle(Lead $lead, ?User $owner, ?User $by): Lead
    {
        if ($owner !== null && (! $owner->is_active || ! $owner->hasRole(UserRole::Admin, UserRole::Sales))) {
            throw new CrmException('Seul un commercial ou un administrateur actif peut être responsable d\'un lead.');
        }

        $previous = $lead->owner_id;
        if ($previous === $owner?->id) {
            return $lead;
        }

        $lead->forceFill(['owner_id' => $owner?->id])->save();

        $lead->activities()->create([
            'user_id' => $by?->id,
            'type' => ActivityType::System,
            'body' => $owner !== null ? "Attribué à {$owner->name}" : 'Responsable retiré',
        ]);

        $this->audit->handle(AuditAction::Updated, $lead, ['before' => ['owner_id' => $previous], 'after' => ['owner_id' => $owner?->id]], $by);

        return $lead;
    }
}
