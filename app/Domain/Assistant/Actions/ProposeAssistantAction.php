<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Conversation\AssistantAnswer;
use App\Domain\Assistant\Conversation\RespondTool;
use App\Domain\Assistant\Enums\MessageRole;
use App\Domain\Assistant\Enums\ProposalStatus;
use App\Domain\Assistant\Enums\ProposalType;
use App\Domain\Assistant\Enums\SuggestedAction;
use App\Domain\Assistant\Models\AiActionProposal;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Assistant\Models\Message;
use App\Domain\Crm\Enums\AppointmentChannel;
use App\Domain\Crm\Enums\SlotPeriod;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Support\PhoneNormalizer;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Propositions d'action de l'assistant (TRD §7.5) : un payload figé, son hash, une clé d'idempotence
 * et une expiration de 30 min. Le visiteur complète et corrige la carte ; toute modification crée une
 * NOUVELLE proposition (l'ancienne est annulée) : ce qui est confirmé est exactement ce qui a été montré.
 */
class ProposeAssistantAction
{
    /** Libellés des champs du brief, affichés sur la carte et dans le brief. */
    public const BRIEF_LABELS = [
        'sector' => 'Secteur d\'activité',
        'company_size' => 'Taille de l\'équipe',
        'current_tools' => 'Outils actuels',
        'main_problem' => 'Problème principal',
        'desired_outcome' => 'Résultat attendu',
        'timeline' => 'Échéance souhaitée',
    ];

    /**
     * Brouillon initial issu de la réponse du modèle : les coordonnées restent vides (le modèle ne les
     * reçoit jamais) et le consentement n'est jamais pré-coché.
     */
    public function fromAnswer(Conversation $conversation, Message $message, AssistantAnswer $answer): AiActionProposal
    {
        $type = $answer->suggestedAction === SuggestedAction::ProposeBrief ? ProposalType::ProposeBrief : ProposalType::RequestAppointment;
        $contact = ['full_name' => '', 'phone' => '', 'email' => null, 'company' => null];

        $payload = $type === ProposalType::ProposeBrief
            ? [
                'brief' => $answer->briefDraft ?? [...array_fill_keys(RespondTool::BRIEF_FIELDS, null), 'summary' => ''],
                'contact' => $contact,
                'consent_processing' => false,
                'marketing_opt_in' => false,
            ]
            : [
                'topic' => $this->lastVisitorMessage($conversation),
                'channel' => AppointmentChannel::Whatsapp->value,
                'slots' => [],
                'contact' => $contact,
                'consent_processing' => false,
                'marketing_opt_in' => false,
            ];

        return $this->create($conversation, $message->id, $type, $payload);
    }

    /**
     * Le visiteur a modifié ou complété la carte : on valide, on annule l'ancienne proposition et on en
     * fige une nouvelle, à confirmer telle quelle.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function revise(AiActionProposal $proposal, array $input): AiActionProposal
    {
        $payload = $this->validated($proposal->type, $input);

        return DB::transaction(function () use ($proposal, $payload): AiActionProposal {
            // Verrou : deux modifications simultanées ne peuvent pas produire deux propositions confirmables.
            /** @var AiActionProposal $locked */
            $locked = AiActionProposal::query()->whereKey($proposal->getKey())->lockForUpdate()->firstOrFail();

            $retry = in_array($locked->status, [ProposalStatus::Failed, ProposalStatus::Expired], true)
                || ($locked->status === ProposalStatus::Proposed && $locked->isExpired());

            if ($locked->status !== ProposalStatus::Proposed && ! $retry) {
                throw ValidationException::withMessages(['proposal' => 'Cette proposition a déjà été traitée ou remplacée. Demandez-en une nouvelle à l\'assistant.']);
            }

            // Après un échec ou une expiration, la saisie du visiteur repart dans une nouvelle proposition.
            $locked->forceFill(['status' => match (true) {
                $locked->status === ProposalStatus::Proposed && $locked->isExpired() => ProposalStatus::Expired,
                $locked->status === ProposalStatus::Proposed => ProposalStatus::Cancelled,
                default => $locked->status,
            }])->save();

            return $this->create($locked->conversation()->firstOrFail(), $locked->message_id, $locked->type, $payload);
        });
    }

    /**
     * Le payload d'une proposition est-il complet et valide (prêt à être confirmé) ?
     *
     * @param  array<string, mixed>  $payload
     */
    public function isComplete(ProposalType $type, array $payload): bool
    {
        try {
            $this->validated($type, $payload);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    /**
     * Mêmes règles que les formulaires publics (rendez-vous, brief) ; renvoie le payload normalisé.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validated(ProposalType $type, array $input): array
    {
        $phone = ['required', 'string', 'max:30', function (string $attribute, mixed $value, Closure $fail): void {
            if (PhoneNormalizer::toE164(is_string($value) ? $value : null) === null) {
                $fail('Ce numéro ne semble pas valide. Exemple : 74 66 64 89');
            }
        }];
        $common = [
            'contact' => ['required', 'array'],
            'contact.full_name' => ['required', 'string', 'min:2', 'max:160'],
            'contact.phone' => $phone,
            'contact.email' => ['nullable', 'email:rfc', 'max:190'],
            'contact.company' => ['nullable', 'string', 'max:160'],
            'consent_processing' => ['accepted'],
            'marketing_opt_in' => ['boolean'],
        ];

        if ($type === ProposalType::ProposeBrief) {
            $rules = [...$common, 'brief' => ['required', 'array'], 'brief.summary' => ['required', 'string', 'min:10', 'max:600']];
            foreach (RespondTool::BRIEF_FIELDS as $field) {
                $rules['brief.'.$field] = ['nullable', 'string', 'max:300'];
            }
        } else {
            $tomorrow = now(config('kh.display_timezone'))->addDay()->toDateString();
            $limit = now(config('kh.display_timezone'))->addMonths(3)->toDateString();
            $rules = [...$common,
                'topic' => ['required', 'string', 'min:3', 'max:255'],
                'channel' => ['required', Rule::enum(AppointmentChannel::class)],
                'slots' => ['required', 'array', 'min:1', 'max:'.Appointment::MAX_SLOTS],
                'slots.*.date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$tomorrow, 'before_or_equal:'.$limit],
                'slots.*.period' => ['required', Rule::enum(SlotPeriod::class)],
            ];
        }

        $data = Validator::make($input, $rules, [], [
            'contact.full_name' => 'nom', 'contact.phone' => 'téléphone', 'contact.email' => 'e-mail',
            'brief.summary' => 'résumé', 'topic' => 'motif', 'slots' => 'créneaux',
        ])->validate();

        $text = fn (mixed $v): ?string => is_string($v) && trim($v) !== '' ? trim($v) : null;
        $payload = [
            'contact' => [
                'full_name' => trim((string) $data['contact']['full_name']),
                'phone' => trim((string) $data['contact']['phone']),
                'email' => $text($data['contact']['email'] ?? null),
                'company' => $text($data['contact']['company'] ?? null),
            ],
            'consent_processing' => true,
            'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
        ];

        if ($type === ProposalType::ProposeBrief) {
            $brief = [];
            foreach (RespondTool::BRIEF_FIELDS as $field) {
                $brief[$field] = $text($data['brief'][$field] ?? null);
            }

            return ['brief' => [...$brief, 'summary' => trim((string) $data['brief']['summary'])], ...$payload];
        }

        return [
            'topic' => trim((string) $data['topic']),
            'channel' => (string) $data['channel'],
            'slots' => array_values(array_map(fn (array $s): array => ['date' => (string) $s['date'], 'period' => (string) $s['period']], (array) $data['slots'])),
            ...$payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function create(Conversation $conversation, ?int $messageId, ProposalType $type, array $payload): AiActionProposal
    {
        $hash = AiActionProposal::hashPayload($payload);

        return AiActionProposal::query()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $messageId,
            'type' => $type,
            'payload' => $payload,
            'payload_hash' => $hash,
            // Aléatoire : deux propositions identiques restent deux propositions distinctes.
            'idempotency_key' => hash('sha256', $hash.'|'.bin2hex(random_bytes(16))),
            'status' => ProposalStatus::Proposed,
            'expires_at' => now()->addMinutes(AiActionProposal::LIFETIME_MINUTES),
        ]);
    }

    private function lastVisitorMessage(Conversation $conversation): string
    {
        $last = Message::query()->where('conversation_id', $conversation->id)->where('role', MessageRole::User)->orderByDesc('id')->value('content');

        return mb_substr(trim((string) $last), 0, 255);
    }
}
