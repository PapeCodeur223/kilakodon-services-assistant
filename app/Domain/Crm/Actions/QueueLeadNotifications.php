<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Actions\QueueOutboundMessage;
use App\Domain\Platform\Services\Settings;

/**
 * Met en file la notification interne et l'accusé de réception d'un lead (COM-01).
 * L'envoi est découplé : un échec d'e-mail ne peut pas faire perdre la demande (WEB-06, TRD §12).
 * Aucune donnée personnelle dans l'e-mail interne : il renvoie vers l'admin.
 */
class QueueLeadNotifications
{
    public function __construct(
        private readonly Settings $settings,
        private readonly QueueOutboundMessage $queue,
    ) {}

    /**
     * @param  array<string, mixed>  $ackPayload  données propres à l'accusé (ex. référence du brief)
     */
    public function handle(Lead $lead, Contact $contact, string $ackTemplate = 'lead_ack', array $ackPayload = [], string $internalTemplate = 'lead_new_internal'): void
    {
        $internal = ['lead_id' => $lead->id, 'type' => $lead->type->value];
        if (isset($ackPayload['reference'])) {
            $internal['reference'] = $ackPayload['reference'];
        }

        foreach ((array) $this->settings->get('notify.lead_recipients', []) as $recipient) {
            $this->queue->handle((string) $recipient, $internalTemplate, $internal, $lead);
        }

        if ($contact->email !== null) {
            $this->queue->handle($contact->email, $ackTemplate, [
                'lead_id' => $lead->id,
                'first_name' => strtok($contact->full_name, ' ') ?: $contact->full_name,
                ...$ackPayload,
            ], $lead);
        }
    }
}
