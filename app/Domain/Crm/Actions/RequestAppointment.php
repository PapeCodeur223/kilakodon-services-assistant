<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Data\AppointmentRequest;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Enums\LeadType;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Support\PhoneNormalizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Demande de rendez-vous (RDV-01) : toujours au statut `requested` ; seul un humain confirme.
 * Depuis un brief, la demande rejoint le lead existant ; sinon un lead « Rendez-vous » est créé
 * (idempotent : même téléphone, même motif, même jour).
 */
class RequestAppointment
{
    public function __construct(
        private readonly UpsertContact $upsertContact,
        private readonly RecordConsents $consents,
        private readonly QueueLeadNotifications $notifications,
    ) {}

    public function handle(AppointmentRequest $request): Appointment
    {
        $slots = array_slice($request->slots, 0, Appointment::MAX_SLOTS);
        if ($slots === []) {
            throw new CrmException('Proposez au moins un créneau.');
        }

        $phone = PhoneNormalizer::toE164($request->phone) ?? throw new CrmException('Numéro de téléphone invalide.');
        $lead = $request->brief !== null ? $request->brief->loadMissing('lead')->lead : null;

        if ($lead !== null) {
            return DB::transaction(fn (): Appointment => $this->forExistingLead($lead, $request, $slots));
        }

        $key = $this->idempotencyKey($phone, $request->topic);
        $existing = Lead::query()->where('idempotency_key', $key)->first();
        if ($existing !== null) {
            return $existing->appointments()->firstOrFail();
        }

        try {
            return DB::transaction(fn (): Appointment => $this->forNewLead($request, $slots, $key));
        } catch (UniqueConstraintViolationException) {
            return Lead::query()->where('idempotency_key', $key)->firstOrFail()->appointments()->firstOrFail();
        }
    }

    /**
     * BACKEND_SCHEMA §8.2 : SHA-256 de (type, téléphone normalisé, motif tronqué, date du jour).
     */
    public function idempotencyKey(string $phoneE164, string $topic): string
    {
        return hash('sha256', implode('|', [
            LeadType::Appointment->value,
            $phoneE164,
            mb_substr(trim($topic), 0, 200),
            now(config('kh.display_timezone'))->toDateString(),
        ]));
    }

    /**
     * @param  list<array{date: string, period: string}>  $slots
     */
    private function forNewLead(AppointmentRequest $request, array $slots, string $key): Appointment
    {
        $contact = $this->upsertContact->handle(new ProspectDetails(
            fullName: $request->fullName,
            phone: $request->phone,
            email: $request->email,
            company: $request->company,
            marketingOptIn: $request->marketingOptIn,
            ip: $request->ip,
        ))->contact;

        $this->consents->handle($contact, $request->marketingOptIn, ConsentSource::AppointmentForm, $request->ip);

        $lead = Lead::query()->create([
            'contact_id' => $contact->id,
            'company_id' => $contact->company_id,
            'type' => LeadType::Appointment,
            'source' => mb_substr($request->utm['utm_source'] ?? 'organic', 0, 64),
            'message' => 'Demande de rendez-vous : '.trim($request->topic),
            'idempotency_key' => $key,
            'utm' => $request->utm !== [] ? $request->utm : null,
        ]);

        $appointment = $this->createAppointment($lead, $request, $slots);
        $this->notifications->handle($lead, $contact, 'appointment_ack', ['topic' => trim($request->topic)]);

        return $appointment;
    }

    /**
     * @param  list<array{date: string, period: string}>  $slots
     */
    private function forExistingLead(Lead $lead, AppointmentRequest $request, array $slots): Appointment
    {
        $lead->loadMissing('contact');
        $this->consents->handle($lead->contact, $request->marketingOptIn, ConsentSource::AppointmentForm, $request->ip);

        $appointment = $this->createAppointment($lead, $request, $slots);
        $this->notifications->handle($lead, $lead->contact, 'appointment_ack', ['topic' => trim($request->topic)], 'appointment_requested_internal');

        return $appointment;
    }

    /**
     * @param  list<array{date: string, period: string}>  $slots
     */
    private function createAppointment(Lead $lead, AppointmentRequest $request, array $slots): Appointment
    {
        $appointment = $lead->appointments()->create([
            'status' => AppointmentStatus::Requested,
            'channel' => $request->channel,
            'topic' => mb_substr(trim($request->topic), 0, 255),
            'preferred_slots' => $slots,
            'timezone' => config('kh.display_timezone'),
        ]);

        $lead->activities()->create([
            'type' => ActivityType::System,
            'body' => 'Demande de rendez-vous ('.$request->channel->label().') : '.implode(' ; ', $appointment->slotLabels()),
        ]);

        return $appointment;
    }
}
