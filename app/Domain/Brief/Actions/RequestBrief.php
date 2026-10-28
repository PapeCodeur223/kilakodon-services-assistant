<?php

namespace App\Domain\Brief\Actions;

use App\Domain\Brief\Exceptions\BriefRequestException;
use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Actions\CreateLeadFromBrief;
use App\Domain\Crm\Actions\RecordConsents;
use App\Domain\Crm\Actions\UpsertContact;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * « Recevoir mon brief » (P1, étape 5) : contact, consentements, brief et lead, en une transaction.
 *
 * Un diagnostic ne produit qu'un brief : une nouvelle demande est refusée. Sinon, quiconque détient
 * le lien de reprise pourrait obtenir un nouveau lien privé (et annuler une révocation) ou écraser
 * le contact. La session est verrouillée et doit être terminée : le brief reprend exactement
 * la restitution calculée sur les réponses enregistrées.
 */
class RequestBrief
{
    public function __construct(
        private readonly UpsertContact $upsertContact,
        private readonly RecordConsents $consents,
        private readonly CreateBriefFromDiagnostic $createBrief,
        private readonly CreateLeadFromBrief $createLead,
    ) {}

    /**
     * @return array{brief: Brief, token: string, lead: Lead, duplicates: Collection<int, Contact>}
     *
     * @throws BriefRequestException
     */
    public function handle(DiagnosticSession $session, ProspectDetails $details): array
    {
        return DB::transaction(function () use ($session, $details): array {
            // Verrou : deux soumissions simultanées (deux onglets) ne créent pas deux briefs.
            $session = DiagnosticSession::query()->lockForUpdate()->findOrFail($session->id);
            $session->load(['brief', 'answers', 'template', 'result']);

            if ($session->brief !== null) {
                throw BriefRequestException::alreadyRequested($session->brief->reference);
            }

            if ($session->status !== SessionStatus::Completed || $session->result === null) {
                throw BriefRequestException::notCompleted();
            }

            $answers = $session->answerMap();

            $upserted = $this->upsertContact->handle($details, null, [
                'sector_code' => is_string($answers['sector'] ?? null) ? $answers['sector'] : null,
                'size_band' => is_string($answers['team_size'] ?? null) ? $answers['team_size'] : null,
                'sites_band' => is_string($answers['sites'] ?? null) ? $answers['sites'] : null,
            ]);

            $this->consents->handle($upserted->contact, $details->marketingOptIn, ConsentSource::BriefForm, $details->ip);

            ['brief' => $brief, 'token' => $token] = $this->createBrief->handle($session, $upserted->contact, $details->company);
            $lead = $this->createLead->handle($brief);

            return ['brief' => $brief, 'token' => $token, 'lead' => $lead, 'duplicates' => $upserted->duplicates];
        });
    }
}
