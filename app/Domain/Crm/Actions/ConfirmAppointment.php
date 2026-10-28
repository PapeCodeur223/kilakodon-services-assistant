<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Enums\AppointmentStatus;
use App\Domain\Crm\Enums\LeadStage;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Platform\Actions\QueueOutboundMessage;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Confirmation humaine d'un rendez-vous (RDV-01) : date et heure fixées, e-mail « RDV confirmé »
 * au prospect s'il a donné une adresse, lead passé à l'étape « Rendez-vous » s'il était plus en amont.
 * Sert aussi à reprogrammer un rendez-vous déjà confirmé.
 */
class ConfirmAppointment
{
    public function __construct(
        private readonly ChangeLeadStage $changeStage,
        private readonly QueueOutboundMessage $queue,
        private readonly RecordAudit $audit,
    ) {}

    public function handle(Appointment $appointment, Carbon $scheduledAt, User $by, ?string $notes = null): Appointment
    {
        if (! in_array($appointment->status, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true)) {
            throw new CrmException('Seul un rendez-vous demandé ou confirmé peut être (re)programmé.');
        }
        if ($scheduledAt->isPast()) {
            throw new CrmException('La date du rendez-vous doit être dans le futur.');
        }

        return DB::transaction(function () use ($appointment, $scheduledAt, $by, $notes): Appointment {
            $appointment->forceFill([
                'status' => AppointmentStatus::Confirmed,
                'scheduled_at' => $scheduledAt->copy()->utc(),
                'confirmed_by' => $by->id,
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : $appointment->notes,
            ])->save();

            $lead = $appointment->lead()->with('contact')->firstOrFail();
            $local = $scheduledAt->copy()->timezone($appointment->timezone);

            $lead->activities()->create([
                'user_id' => $by->id,
                'type' => ActivityType::Meeting,
                'body' => 'Rendez-vous confirmé le '.$local->translatedFormat('l j F Y à H:i').' ('.$appointment->channel->label().')',
            ]);

            if (in_array($lead->stage, [LeadStage::New, LeadStage::Qualified], true)) {
                $this->changeStage->handle($lead, LeadStage::Meeting, $by, 'Rendez-vous confirmé');
            } elseif ($lead->first_response_at === null) {
                $lead->forceFill(['first_response_at' => now()])->save();
            }

            if ($lead->contact->email !== null) {
                $this->queue->handle($lead->contact->email, 'appointment_confirmed', [
                    'first_name' => strtok($lead->contact->full_name, ' ') ?: $lead->contact->full_name,
                    'date' => $local->translatedFormat('l j F Y'),
                    'time' => $local->format('H:i'),
                    'channel' => $appointment->channel->label(),
                    'topic' => $appointment->topic,
                ], $lead);
            }

            $this->audit->handle(AuditAction::StatusChanged, $lead, ['after' => ['appointment_id' => $appointment->id, 'status' => 'confirmed']], $by);

            return $appointment;
        });
    }

    /**
     * Annulation, réalisation ou absence : transitions depuis un rendez-vous demandé ou confirmé.
     */
    public function close(Appointment $appointment, AppointmentStatus $to, User $by, ?string $notes = null): Appointment
    {
        $allowed = match ($to) {
            AppointmentStatus::Cancelled => [AppointmentStatus::Requested, AppointmentStatus::Confirmed],
            AppointmentStatus::Done, AppointmentStatus::NoShow => [AppointmentStatus::Confirmed],
            default => [],
        };

        if (! in_array($appointment->status, $allowed, true)) {
            throw new CrmException("Impossible de passer ce rendez-vous à « {$to->label()} ».");
        }

        return DB::transaction(function () use ($appointment, $to, $by, $notes): Appointment {
            $appointment->forceFill([
                'status' => $to,
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : $appointment->notes,
            ])->save();

            $appointment->loadMissing('lead')->lead->activities()->create([
                'user_id' => $by->id,
                'type' => ActivityType::Meeting,
                'body' => "Rendez-vous : {$to->label()}".($notes !== null && trim($notes) !== '' ? ' — '.trim($notes) : ''),
            ]);

            return $appointment;
        });
    }
}
