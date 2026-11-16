<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Enums\ProposalStatus;
use App\Domain\Assistant\Enums\ProposalType;
use App\Domain\Assistant\Models\AiActionProposal;
use App\Domain\Brief\Actions\CreateBriefFromAssistant;
use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Actions\RequestAppointment;
use App\Domain\Crm\Data\AppointmentRequest;
use App\Domain\Crm\Enums\AppointmentChannel;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Confirmation explicite d'une proposition (TRD §7.5) : statut, expiration et hash vérifiés, puis la
 * MÊME Action métier que le formulaire classique, dans une transaction. Un double clic renvoie le
 * résultat existant ; un échec est enregistré et renvoyé comme tel, jamais affiché comme un succès.
 */
class ConfirmAssistantAction
{
    /** Lien privé du brief, connu seulement juste après sa création (le token n'est stocké que haché). */
    private ?string $lastToken = null;

    public function __construct(
        private readonly ProposeAssistantAction $proposals,
        private readonly CreateBriefFromAssistant $briefs,
        private readonly RequestAppointment $appointments,
    ) {}

    /**
     * @return array{status: string, message: string, result?: array<string, mixed>}
     */
    public function handle(AiActionProposal $proposal, string $hash, ?string $ip = null): array
    {
        return DB::transaction(function () use ($proposal, $hash, $ip): array {
            /** @var AiActionProposal $locked */
            $locked = AiActionProposal::query()->whereKey($proposal->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === ProposalStatus::Executed) {
                return $this->executed($locked);
            }

            if ($locked->status !== ProposalStatus::Proposed) {
                return $this->refused('Cette proposition n\'est plus valable. Demandez-en une nouvelle à l\'assistant.');
            }

            if ($locked->isExpired()) {
                $locked->forceFill(['status' => ProposalStatus::Expired])->save();

                return $this->refused('Cette proposition a expiré (30 minutes). Demandez-en une nouvelle à l\'assistant.');
            }

            // Payload figé : le hash envoyé doit être celui de la proposition, et le payload stocké intact.
            if (! hash_equals($locked->payload_hash, $hash) || ! hash_equals($locked->payload_hash, AiActionProposal::hashPayload($locked->payload))) {
                return $this->refused('Les informations ont changé depuis leur affichage : vérifiez la carte puis confirmez à nouveau.');
            }

            if (! $this->proposals->isComplete($locked->type, $locked->payload)) {
                return $this->refused('Complétez la carte (nom, téléphone, consentement) avant de confirmer.');
            }

            $locked->forceFill(['status' => ProposalStatus::Confirmed, 'confirmed_at' => now()])->save();

            try {
                $result = DB::transaction(fn (): Brief|Appointment => $this->execute($locked, $ip));
            } catch (Throwable $e) {
                // Message métier conservé ; pour toute autre exception (SQL…), seule sa classe : pas de coordonnées dans les journaux.
                $error = $e instanceof CrmException || $e instanceof InvalidArgumentException ? $e->getMessage() : $e::class;
                Log::warning('Assistant : échec de l\'action confirmée.', ['proposal' => $locked->id, 'error' => $error]);
                report($e);
                $locked->forceFill(['status' => ProposalStatus::Failed, 'error' => mb_substr($error, 0, 255)])->save();

                return ['status' => 'failed', 'message' => 'L\'envoi n\'a pas abouti. Réessayez ou écrivez-nous sur WhatsApp : votre demande n\'a pas été enregistrée.'];
            }

            $locked->result()->associate($result);
            $locked->forceFill(['status' => ProposalStatus::Executed, 'executed_at' => now()])->save();

            return $this->executed($locked, $result instanceof Brief ? $this->lastToken : null);
        });
    }

    private function execute(AiActionProposal $proposal, ?string $ip): Brief|Appointment
    {
        $payload = $proposal->payload;
        $conversation = $proposal->conversation()->firstOrFail();

        if ($proposal->type === ProposalType::ProposeBrief) {
            /** @var array{brief: array<string, string|null>, contact: array{full_name: string, phone: string, email: string|null, company: string|null}, marketing_opt_in: bool} $payload */
            $created = $this->briefs->handle($conversation, $payload, $ip);
            $this->lastToken = $created['token'];

            return $created['brief'];
        }

        $appointment = $this->appointments->handle(new AppointmentRequest(
            fullName: (string) $payload['contact']['full_name'],
            phone: (string) $payload['contact']['phone'],
            email: $payload['contact']['email'] ?? null,
            company: $payload['contact']['company'] ?? null,
            topic: (string) $payload['topic'],
            channel: AppointmentChannel::from((string) $payload['channel']),
            slots: array_values((array) $payload['slots']),
            marketingOptIn: (bool) $payload['marketing_opt_in'],
            ip: $ip,
            consentSource: ConsentSource::Assistant,
            source: 'assistant',
        ));

        $conversation->forceFill(['lead_id' => $appointment->lead_id])->save();

        return $appointment;
    }

    /**
     * @return array{status: string, message: string, result: array<string, mixed>}
     */
    private function executed(AiActionProposal $proposal, ?string $briefToken = null): array
    {
        if ($proposal->type === ProposalType::ProposeBrief) {
            $brief = $proposal->result;
            $reference = $brief instanceof Brief ? $brief->reference : null;

            return [
                'status' => 'executed',
                'message' => 'Votre brief '.$reference.' a bien été envoyé. Un membre de l\'équipe vous recontacte.',
                'result' => array_filter([
                    'type' => 'brief',
                    'reference' => $reference,
                    // Le lien privé n'est renvoyé qu'à la création : un second clic ne le régénère pas.
                    'url' => $briefToken !== null ? route('brief.show', $briefToken) : null,
                ]),
            ];
        }

        return [
            'status' => 'executed',
            'message' => 'Votre demande de rendez-vous est transmise. Elle n\'est pas encore confirmée : un membre de l\'équipe vous recontacte pour fixer le créneau.',
            'result' => ['type' => 'appointment'],
        ];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function refused(string $message): array
    {
        return ['status' => 'refused', 'message' => $message];
    }
}
