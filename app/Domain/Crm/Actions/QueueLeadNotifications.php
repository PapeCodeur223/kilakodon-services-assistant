<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Enums\OutboundChannel;
use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Services\Settings;

/**
 * Met en file (`outbound_messages`) la notification interne et l'accusé de réception.
 * L'envoi est découplé : un échec d'e-mail ne peut pas faire perdre la demande (WEB-06, TRD §12).
 * Aucune donnée personnelle dans l'e-mail interne : il renvoie vers l'admin (COM-01).
 */
class QueueLeadNotifications
{
    public function __construct(private readonly Settings $settings) {}

    /**
     * @param  array<string, mixed>  $ackPayload  données propres à l'accusé (ex. référence du brief)
     */
    public function handle(Lead $lead, Contact $contact, string $ackTemplate = 'lead_ack', array $ackPayload = []): void
    {
        foreach ((array) $this->settings->get('notify.lead_recipients', []) as $recipient) {
            $this->queue((string) $recipient, 'lead_new_internal', ['lead_id' => $lead->id, 'type' => $lead->type->value], $lead);
        }

        if ($contact->email !== null) {
            $this->queue($contact->email, $ackTemplate, [
                'lead_id' => $lead->id,
                'first_name' => strtok($contact->full_name, ' ') ?: $contact->full_name,
                'hours' => (string) $this->settings->get('contact.hours'),
                ...$ackPayload,
            ], $lead);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function queue(string $to, string $template, array $payload, Lead $lead): void
    {
        $message = new OutboundMessage([
            'channel' => OutboundChannel::Email,
            'to' => $to,
            'template' => $template,
            'payload' => $payload,
            'status' => OutboundStatus::Queued,
        ]);
        $message->related()->associate($lead);
        $message->save();
    }
}
