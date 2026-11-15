<?php

namespace App\Domain\Platform\Support;

use App\Domain\Content\Support\PendingValidation;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Services\Settings;
use InvalidArgumentException;

/**
 * Modèles d'e-mail de la file (COM-01, CONTENT §11). Objets sans donnée personnelle ;
 * l'e-mail interne renvoie vers l'admin au lieu de recopier les coordonnées.
 *
 * @phpstan-type MailContent array{subject: string, view: string, data: array<string, mixed>}
 */
final class MailTemplates
{
    public const TEMPLATES = [
        'lead_new_internal', 'lead_ack', 'brief_ack', 'appointment_ack', 'appointment_requested_internal',
        'appointment_confirmed', 'lead_unattended',
    ];

    /**
     * @return MailContent
     */
    public static function for(OutboundMessage $message): array
    {
        $p = $message->payload;
        $data = [...$p, 'hours' => self::hours(), 'whatsapp' => WhatsAppLink::for('email')];

        return match ($message->template) {
            'lead_new_internal' => [
                'subject' => 'Nouveau lead '.mb_strtolower(LeadType::tryFrom((string) ($p['type'] ?? ''))?->label() ?? 'reçu').' — '.($p['reference'] ?? '#'.($p['lead_id'] ?? '?')),
                'view' => 'emails.lead-new-internal',
                'data' => [...$data, 'adminUrl' => self::leadUrl($p)],
            ],
            'lead_ack' => [
                'subject' => 'Votre demande a bien été reçue — Kilakodon Services',
                'view' => 'emails.lead-ack',
                'data' => $data,
            ],
            'brief_ack' => [
                'subject' => 'Votre demande a bien été reçue — Kilakodon Services',
                'view' => 'emails.brief-ack',
                'data' => [...$data, 'whatsapp' => WhatsAppLink::forBrief((string) ($p['reference'] ?? ''))],
            ],
            'appointment_ack' => [
                'subject' => 'Votre demande de rendez-vous a bien été reçue — Kilakodon Services',
                'view' => 'emails.appointment-ack',
                'data' => $data,
            ],
            'appointment_requested_internal' => [
                'subject' => 'Demande de rendez-vous — lead #'.($p['lead_id'] ?? '?'),
                'view' => 'emails.appointment-requested-internal',
                'data' => [...$data, 'adminUrl' => self::leadUrl($p)],
            ],
            'appointment_confirmed' => [
                'subject' => 'Votre rendez-vous avec Kilakodon Services',
                'view' => 'emails.appointment-confirmed',
                'data' => $data,
            ],
            'lead_unattended' => [
                'subject' => count((array) ($p['lead_ids'] ?? [])).' lead(s) en attente depuis plus de 24 h',
                'view' => 'emails.lead-unattended',
                'data' => [...$data, 'adminUrl' => url('/admin/leads')],
            ],
            default => throw new InvalidArgumentException("Modèle d'e-mail inconnu : {$message->template}"),
        };
    }

    /**
     * Horaires cités seulement une fois confirmés (Q-002).
     */
    private static function hours(): ?string
    {
        $hours = (string) app(Settings::class)->get('contact.hours');

        return $hours === '' || PendingValidation::has($hours) ? null : $hours;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function leadUrl(array $payload): string
    {
        return url('/admin/leads/'.($payload['lead_id'] ?? ''));
    }
}
