<?php

namespace App\Domain\Brief\Actions;

use App\Domain\Assistant\Actions\ProposeAssistantAction;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Brief\Enums\BriefStatus;
use App\Domain\Brief\Enums\SummarySource;
use App\Domain\Brief\Enums\VersionAuthor;
use App\Domain\Brief\Models\Brief;
use App\Domain\Brief\Support\ReferenceGenerator;
use App\Domain\Crm\Actions\CreateLeadFromBrief;
use App\Domain\Crm\Actions\RecordConsents;
use App\Domain\Crm\Actions\UpsertContact;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Models\Lead;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Support\PublicToken;
use Illuminate\Support\Facades\DB;

/**
 * Brief confirmé depuis l'assistant (IA-02, IA-03) : le visiteur a relu et validé chaque champ sur la
 * carte de confirmation. Contact, consentements, brief validé (sans recommandation : seules les règles
 * du diagnostic en produisent), puis le même lead que pour un brief de diagnostic.
 */
class CreateBriefFromAssistant
{
    public function __construct(
        private readonly ReferenceGenerator $references,
        private readonly UpsertContact $upsertContact,
        private readonly RecordConsents $consents,
        private readonly CreateLeadFromBrief $leads,
        private readonly RecordAudit $audit,
    ) {}

    /**
     * @param  array{brief: array<string, string|null>, contact: array{full_name: string, phone: string, email: string|null, company: string|null}, marketing_opt_in: bool}  $payload
     * @return array{brief: Brief, lead: Lead, token: string}
     */
    public function handle(Conversation $conversation, array $payload, ?string $ip = null): array
    {
        return DB::transaction(function () use ($conversation, $payload, $ip): array {
            $details = $payload['contact'];
            $contact = $this->upsertContact->handle(new ProspectDetails(
                fullName: $details['full_name'],
                phone: $details['phone'],
                email: $details['email'],
                company: $details['company'],
                marketingOptIn: $payload['marketing_opt_in'],
                ip: $ip,
            ))->contact;
            $this->consents->handle($contact, $payload['marketing_opt_in'], ConsentSource::Assistant, $ip);

            $token = PublicToken::generate();
            $brief = Brief::query()->create([
                'reference' => $this->references->next(),
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'status' => BriefStatus::Validated,
                'current_version' => 1,
                'access_token_hash' => $token['hash'],
                'access_expires_at' => now()->addDays(Brief::ACCESS_DAYS),
                'validated_at' => now(),
            ]);

            $answers = [];
            foreach (ProposeAssistantAction::BRIEF_LABELS as $key => $label) {
                if (filled($payload['brief'][$key] ?? null)) {
                    $answers[] = ['key' => $key, 'question' => $label, 'answer' => (string) $payload['brief'][$key], 'step' => 'assistant'];
                }
            }

            $brief->versions()->create([
                'version' => 1,
                'declared' => [
                    'contact' => [
                        'full_name' => $contact->full_name,
                        'phone' => $contact->phone_e164,
                        'email' => $contact->email,
                        'company' => $details['company'],
                    ],
                    'answers' => $answers,
                    'context_note' => null,
                ],
                'recommended' => [],
                'summary' => (string) $payload['brief']['summary'],
                'summary_source' => SummarySource::ProspectEdited,
                'created_by' => VersionAuthor::Assistant,
            ]);

            // Même trace que ValidateBrief : le visiteur a validé ce contenu sur la carte de confirmation.
            $this->audit->handle(AuditAction::BriefValidated, $brief, ['after' => ['version' => 1, 'source' => 'assistant']]);

            $lead = $this->leads->handle($brief);
            $conversation->forceFill(['lead_id' => $lead->id])->save();

            return ['brief' => $brief, 'lead' => $lead, 'token' => $token['token']];
        });
    }
}
