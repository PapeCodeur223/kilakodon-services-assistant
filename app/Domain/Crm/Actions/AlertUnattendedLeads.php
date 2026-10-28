<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Support\BusinessHours;
use App\Domain\Platform\Actions\QueueOutboundMessage;
use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;

/**
 * Alerte les administrateurs des leads sans première réponse depuis plus de 24 h ouvrées (COM-01).
 * Un lead n'est signalé qu'une fois ; un seul e-mail par administrateur et par passage.
 */
class AlertUnattendedLeads
{
    public const THRESHOLD_HOURS = 24;

    public function __construct(private readonly QueueOutboundMessage $queue) {}

    /**
     * @return list<int> leads signalés
     */
    public function handle(): array
    {
        $alreadyAlerted = OutboundMessage::query()
            ->where('template', 'lead_unattended')
            ->pluck('payload')
            ->flatMap(fn (mixed $payload): array => (array) (is_array($payload) ? ($payload['lead_ids'] ?? []) : []))
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $leadIds = Lead::query()
            ->whereIn('stage', LeadStage::active())
            ->whereNull('first_response_at')
            ->where('created_at', '<=', now()->subHours(self::THRESHOLD_HOURS))
            ->whereNotIn('id', $alreadyAlerted ?: [0])
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->filter(fn (Lead $lead): bool => BusinessHours::between($lead->created_at ?? now(), now()) >= self::THRESHOLD_HOURS)
            ->pluck('id')
            ->values()
            ->all();

        if ($leadIds === []) {
            return [];
        }

        $admins = User::query()->where('role', UserRole::Admin)->where('is_active', true)->pluck('email');
        foreach ($admins as $email) {
            $this->queue->handle((string) $email, 'lead_unattended', ['lead_ids' => $leadIds]);
        }

        return $leadIds;
    }
}
