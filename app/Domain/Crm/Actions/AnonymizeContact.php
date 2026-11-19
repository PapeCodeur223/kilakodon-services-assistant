<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Assistant\Models\AiActionProposal;
use App\Domain\Assistant\Models\Message;
use App\Domain\Crm\Enums\ActivityType;
use App\Domain\Crm\Exceptions\CrmException;
use App\Domain\Crm\Models\Activity;
use App\Domain\Crm\Models\Appointment;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Support\ContactDataScope;
use App\Domain\Diagnostic\Models\DiagnosticAnswer;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\OutboundMessage;
use App\Domain\Platform\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Droit à l'effacement (PRV-02, BACKEND_SCHEMA §8.3) : remplace les données identifiantes par des
 * valeurs neutres dans le contact, ses leads, briefs, diagnostics, conversations et e-mails ;
 * révoque les liens privés ; conserve les agrégats (étapes, dates, sources) ; journalise.
 * Irréversible : appelée seulement après vérification de l'identité, depuis l'admin.
 */
class AnonymizeContact
{
    public const REDACTED = '[Contenu anonymisé]';

    /** Valeur neutre pour les empreintes d'IP non nullables. */
    private const NEUTRAL_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public function __construct(private readonly RecordAudit $audit) {}

    public function handle(Contact $contact, User $by): Contact
    {
        if ($contact->anonymized_at !== null) {
            throw new CrmException('Ce contact est déjà anonymisé.');
        }

        $scope = ContactDataScope::for($contact);
        $pdfs = [];

        DB::transaction(function () use ($contact, $by, $scope, &$pdfs): void {
            $leadIds = $scope->leads->pluck('id')->all();

            $contact->forceFill([
                'full_name' => "Contact anonymisé #{$contact->id}",
                'phone_e164' => null,
                'whatsapp_e164' => null,
                'email' => null,
                'job_title' => null,
                'city' => null,
                'marketing_opt_in' => false,
                'anonymized_at' => now(),
            ])->save();

            // Entreprises (du contact et de ses leads) : souvent le nom du commerçant lui-même (« Chez Awa ») ;
            // anonymisées si aucun autre contact actif n'y est rattaché. Secteur et taille restent (agrégats).
            $companyIds = array_unique(array_filter([$contact->company_id, ...$scope->leads->pluck('company_id')->all()]));
            foreach ($companyIds as $companyId) {
                $shared = Contact::query()->where('company_id', $companyId)->whereKeyNot($contact->id)->whereNull('anonymized_at')->exists();
                if (! $shared) {
                    DB::table('companies')->where('id', $companyId)->update(['name' => "Entreprise anonymisée #{$companyId}", 'city' => null, 'updated_at' => now()]);
                }
            }

            // Leads : on garde type, source, étape, motif codé de perte et dates (agrégats) ; le texte libre disparaît.
            DB::table('leads')->whereIn('id', $leadIds ?: [0])->update(['message' => null, 'next_action' => null, 'updated_at' => now()]);
            DB::table('lead_stage_history')->whereIn('lead_id', $leadIds ?: [0])->update(['reason' => null]);
            Activity::query()->whereIn('lead_id', $leadIds ?: [0])->where('type', '!=', ActivityType::System)->update(['body' => self::REDACTED]);
            // Activités système : on garde « Étape : A → B », jamais le motif libre recopié entre parenthèses.
            Activity::query()->whereIn('lead_id', $leadIds ?: [0])->where('type', ActivityType::System)->get(['id', 'body'])
                ->each(function (Activity $activity): void {
                    $body = preg_match('/^(Étape : [^(]+?)(?: \(.*\))?$/su', $activity->body, $match) === 1 ? trim($match[1]) : self::REDACTED;
                    DB::table('activities')->where('id', $activity->id)->update(['body' => $body]);
                });
            Appointment::query()->whereIn('lead_id', $leadIds ?: [0])->update(['topic' => self::REDACTED, 'notes' => null]);

            // Journal d'audit : l'action et sa date restent ; le détail (avant / après, motifs) peut contenir des
            // coordonnées ou des motifs libres, il est effacé pour toutes les fiches de ce contact.
            $this->redactAudit($contact, $leadIds, $scope->briefs->pluck('id')->all());

            foreach ($scope->briefs as $brief) {
                $pdfs[] = $brief->pdf_path;
                $brief->forceFill(['access_revoked_at' => $brief->access_revoked_at ?? now(), 'pdf_path' => null])->save();

                // Exception documentée à l'immuabilité des versions : l'effacement prime (requête directe).
                foreach ($brief->versions as $version) {
                    DB::table('brief_versions')->where('id', $version->id)->update([
                        'declared' => json_encode($this->redactDeclared($version->declared), JSON_UNESCAPED_UNICODE),
                        'summary' => self::REDACTED,
                        'prospect_comment' => null,
                    ]);
                }

                if ($brief->diagnostic_session_id !== null) {
                    $this->redactSession($brief->diagnostic_session_id);
                }
            }

            // Conversations : messages supprimés (avec les retours et propositions qui recopient les coordonnées).
            $conversationIds = $scope->conversations->pluck('id')->all();
            AiActionProposal::query()->whereIn('conversation_id', $conversationIds ?: [''])->delete();
            Message::query()->whereIn('conversation_id', $conversationIds ?: [''])->delete();
            DB::table('conversations')->whereIn('id', $conversationIds ?: [''])->update([
                'visitor_key' => null,
                'ip_hash' => self::NEUTRAL_HASH,
                'entry_path' => null,
                'message_count' => 0,
                'updated_at' => now(),
            ]);

            // E-mails : destinataire et contenu effacés, l'historique d'envoi reste.
            OutboundMessage::query()->whereIn('id', $scope->outbound->pluck('id')->all() ?: [0])
                ->update(['to' => '[anonymisé]', 'payload' => '[]', 'last_error' => null]);

            $this->audit->handle(AuditAction::Anonymized, $contact, ['after' => [
                'leads' => count($leadIds),
                'briefs' => $scope->briefs->count(),
                'conversations' => count($conversationIds),
                'messages_sent' => $scope->outbound->count(),
            ]], $by);
        });

        foreach (array_filter($pdfs) as $path) {
            Storage::disk('local')->delete((string) $path);
        }

        return $contact->refresh();
    }

    /**
     * @param  array<string, mixed>  $declared
     * @return array<string, mixed>
     */
    private function redactDeclared(array $declared): array
    {
        $declared['contact'] = ['full_name' => null, 'phone' => null, 'email' => null, 'company' => null];
        $declared['context_note'] = null;

        if (isset($declared['answers']) && is_array($declared['answers'])) {
            $declared['answers'] = array_values(array_map(
                // Réponses libres (brief de l'assistant : problème, objectif, outils) : effacées.
                fn ($answer) => is_array($answer) && ($answer['step'] ?? null) === 'assistant' ? [...$answer, 'answer' => self::REDACTED] : $answer,
                array_filter(
                    $declared['answers'],
                    fn ($answer): bool => ! (is_array($answer) && ($answer['key'] ?? null) === 'context_note'),
                ),
            ));
        }

        return $declared;
    }

    /**
     * @param  list<int>  $leadIds
     * @param  list<int>  $briefIds
     */
    private function redactAudit(Contact $contact, array $leadIds, array $briefIds): void
    {
        $appointmentIds = Appointment::query()->whereIn('lead_id', $leadIds ?: [0])->pluck('id')->all();

        DB::table('audit_logs')
            ->where(function ($query) use ($contact, $leadIds, $briefIds, $appointmentIds): void {
                $query->where(fn ($q) => $q->where('auditable_type', $contact->getMorphClass())->where('auditable_id', $contact->id))
                    ->orWhere(fn ($q) => $q->where('auditable_type', 'lead')->whereIn('auditable_id', $leadIds ?: [0]))
                    ->orWhere(fn ($q) => $q->where('auditable_type', 'brief')->whereIn('auditable_id', $briefIds ?: [0]))
                    ->orWhere(fn ($q) => $q->where('auditable_type', 'appointment')->whereIn('auditable_id', $appointmentIds ?: [0]));
            })
            ->update(['changes' => null]);
    }

    private function redactSession(string $sessionId): void
    {
        // Réponse libre du diagnostic : seule question où le prospect écrit ce qu'il veut.
        DiagnosticAnswer::query()->where('session_id', $sessionId)->where('question_key', 'context_note')->delete();

        DiagnosticSession::query()->whereKey($sessionId)->update([
            'visitor_key' => null,
            'ip_hash' => self::NEUTRAL_HASH,
            // Nouvelle empreinte aléatoire : l'ancien lien de reprise ne fonctionne plus.
            'resume_token_hash' => hash('sha256', random_bytes(32)),
        ]);
    }
}
