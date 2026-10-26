<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Data\ContactRequest;
use App\Domain\Crm\Enums\ConsentPurpose;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Enums\ContactSubject;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Models\Consent;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Support\PhoneNormalizer;
use App\Domain\Platform\Enums\OutboundChannel;
use App\Domain\Platform\Enums\OutboundStatus;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Services\Settings;
use App\Domain\Platform\Support\IpHasher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Formulaire de contact → contact, consentements, lead et e-mails en file (WEB-06).
 * Idempotent : une double soumission identique le même jour renvoie le lead existant.
 * Les e-mails sont seulement mis en file (`outbound_messages`) : leur envoi ne peut pas faire perdre la demande.
 */
class CreateLeadFromContact
{
    public function __construct(
        private readonly Settings $settings,
        private readonly IpHasher $ipHasher,
    ) {}

    public function handle(ContactRequest $request): Lead
    {
        $phone = PhoneNormalizer::toE164($request->phone);

        if ($phone === null) {
            throw new InvalidArgumentException('Numéro de téléphone invalide.');
        }

        $key = $this->idempotencyKey($phone, $request->message);

        $existing = Lead::query()->where('idempotency_key', $key)->first();
        if ($existing !== null) {
            return $existing;
        }

        try {
            return DB::transaction(fn (): Lead => $this->create($request, $phone, $key));
        } catch (UniqueConstraintViolationException) {
            // Deux soumissions simultanées : la première a gagné.
            return Lead::query()->where('idempotency_key', $key)->firstOrFail();
        }
    }

    /**
     * BACKEND_SCHEMA §8.2 : SHA-256 de (type, téléphone normalisé, message tronqué, date du jour).
     */
    public function idempotencyKey(string $phoneE164, string $message): string
    {
        return hash('sha256', implode('|', [
            LeadType::Contact->value,
            $phoneE164,
            mb_substr(trim($message), 0, 200),
            now(config('kh.display_timezone'))->toDateString(),
        ]));
    }

    private function create(ContactRequest $request, string $phone, string $key): Lead
    {
        $contact = Contact::query()->create([
            'full_name' => trim($request->fullName),
            'phone_e164' => $phone,
            'whatsapp_e164' => $phone,
            'email' => $request->email !== null ? mb_strtolower(trim($request->email)) : null,
            'marketing_opt_in' => $request->marketingOptIn,
        ]);

        $this->recordConsents($contact, $request);

        $subject = ContactSubject::tryFrom($request->subject) ?? ContactSubject::Other;

        $lead = Lead::query()->create([
            'contact_id' => $contact->id,
            'type' => LeadType::Contact,
            'source' => mb_substr($request->utm['utm_source'] ?? 'organic', 0, 64),
            'message' => 'Sujet : '.$subject->label()."\n\n".trim($request->message),
            'idempotency_key' => $key,
            'utm' => $request->utm !== [] ? $request->utm : null,
        ]);

        $this->queueEmails($lead, $contact);

        return $lead;
    }

    private function recordConsents(Contact $contact, ContactRequest $request): void
    {
        $version = (string) (((array) $this->settings->get('consent.texts', []))['version'] ?? 1);
        $ipHash = $request->ip !== null ? $this->ipHasher->hash($request->ip) : null;

        foreach ([ConsentPurpose::RequestProcessing->value => true, ConsentPurpose::Marketing->value => $request->marketingOptIn] as $purpose => $granted) {
            Consent::query()->create([
                'contact_id' => $contact->id,
                'purpose' => $purpose,
                'granted' => $granted,
                'text_version' => $version,
                'source' => ConsentSource::ContactForm,
                'ip_hash' => $ipHash,
            ]);
        }
    }

    /**
     * Aucune donnée personnelle dans l'e-mail interne : il renvoie vers l'admin (COM-01).
     */
    private function queueEmails(Lead $lead, Contact $contact): void
    {
        foreach ((array) $this->settings->get('notify.lead_recipients', []) as $recipient) {
            $this->queue((string) $recipient, 'lead_new_internal', ['lead_id' => $lead->id, 'type' => $lead->type->value], $lead);
        }

        if ($contact->email !== null) {
            $this->queue($contact->email, 'lead_ack', [
                'lead_id' => $lead->id,
                'first_name' => strtok($contact->full_name, ' ') ?: $contact->full_name,
                'hours' => (string) $this->settings->get('contact.hours'),
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
