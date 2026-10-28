<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Enums\LostReason;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Seule porte de changement d'étape d'un lead (BACKEND_SCHEMA §8.1, CRM-02) : transition autorisée,
 * motif obligatoire pour « perdu » et « en pause », historique (auteur, date, motif), activité et audit.
 */
class ChangeLeadStage
{
    public function __construct(private readonly RecordAudit $audit) {}

    public function handle(Lead $lead, LeadStage $to, ?User $by, ?string $reason = null, ?LostReason $lostReason = null): Lead
    {
        $reason = $reason !== null && trim($reason) !== '' ? mb_substr(trim($reason), 0, 255) : null;

        if ($to === LeadStage::Lost && $lostReason === null) {
            throw new CrmException('Indiquez pourquoi ce lead est perdu.');
        }
        if ($to === LeadStage::Lost && $lostReason === LostReason::Other && $reason === null) {
            throw new CrmException('Précisez le motif « Autre ».');
        }
        if ($to === LeadStage::OnHold && $reason === null) {
            throw new CrmException('Indiquez pourquoi ce lead est mis en pause.');
        }

        return DB::transaction(function () use ($lead, $to, $by, $reason, $lostReason): Lead {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $from = $lead->stage;

            if ($from === $to) {
                return $lead;
            }
            if (! $from->canTransitionTo($to)) {
                throw new CrmException("Passage impossible de « {$from->label()} » à « {$to->label()} ».");
            }

            // Pour « perdu », le motif de perte (vérifié plus haut) précède la précision libre.
            $historyReason = $to === LeadStage::Lost
                ? $lostReason->label().($reason !== null ? ' — '.$reason : '')
                : $reason;

            $lead->forceFill([
                'stage' => $to,
                'lost_reason' => $to === LeadStage::Lost ? $lostReason : null,
                'first_response_at' => $lead->first_response_at ?? ($by !== null ? now() : null),
            ])->save();

            $lead->stageHistory()->create([
                'from_stage' => $from,
                'to_stage' => $to,
                'user_id' => $by?->id,
                'reason' => $historyReason !== null ? mb_substr($historyReason, 0, 255) : null,
            ]);

            $lead->activities()->create([
                'user_id' => $by?->id,
                'type' => ActivityType::System,
                'body' => "Étape : {$from->label()} → {$to->label()}".($historyReason !== null ? " ({$historyReason})" : ''),
            ]);

            $this->audit->handle(AuditAction::StatusChanged, $lead, [
                'before' => ['stage' => $from->value],
                'after' => ['stage' => $to->value, 'reason' => $historyReason],
            ], $by);

            return $lead;
        });
    }
}
