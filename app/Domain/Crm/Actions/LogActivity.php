<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Activity;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Models\User;
use Illuminate\Support\Carbon;

/**
 * Note, appel, WhatsApp, e-mail, rendez-vous ou tâche sur un lead (CRM-04). Toujours interne.
 * La première activité humaine fixe `first_response_at` (délai de prise en charge).
 */
class LogActivity
{
    public const BODY_MAX = 5000;

    public function handle(Lead $lead, ActivityType $type, string $body, ?User $by, ?Carbon $dueAt = null): Activity
    {
        $body = trim($body);

        if ($body === '') {
            throw new CrmException('Le contenu de l\'activité est vide.');
        }
        if ($type === ActivityType::System) {
            throw new CrmException('Les activités « Système » sont réservées à la plateforme.');
        }
        if ($type === ActivityType::Task && $dueAt === null) {
            throw new CrmException('Une tâche doit avoir une échéance.');
        }

        $activity = $lead->activities()->create([
            'user_id' => $by?->id,
            'type' => $type,
            'body' => mb_substr($body, 0, self::BODY_MAX),
            'due_at' => $type === ActivityType::Task ? $dueAt : null,
        ]);

        if ($by !== null && $lead->first_response_at === null) {
            $lead->forceFill(['first_response_at' => now()])->save();
        }

        return $activity;
    }

    public function complete(Activity $activity): Activity
    {
        if (! $activity->isOpenTask()) {
            throw new CrmException('Seule une tâche ouverte peut être marquée comme faite.');
        }

        $activity->forceFill(['done_at' => now()])->save();

        return $activity;
    }
}
