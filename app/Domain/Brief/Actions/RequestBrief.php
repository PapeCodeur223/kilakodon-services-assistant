<?php

namespace App\Domain\Brief\Actions;

use App\Domain\Brief\Models\Brief;
use App\Domain\Crm\Actions\CreateLeadFromBrief;
use App\Domain\Crm\Actions\RecordConsents;
use App\Domain\Crm\Actions\UpsertContact;
use App\Domain\Crm\Data\ProspectDetails;
use App\Domain\Crm\Enums\ConsentSource;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Lead;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * « Recevoir mon brief » (P1, étape 5) : contact, consentements, brief et lead, en une transaction.
 * Une nouvelle soumission pour le même diagnostic met à jour le contact et renvoie le même brief et le même lead.
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
     */
    public function handle(DiagnosticSession $session, ProspectDetails $details): array
    {
        return DB::transaction(function () use ($session, $details): array {
            $session->loadMissing(['brief.contact', 'answers']);
            $answers = $session->answerMap();

            $upserted = $this->upsertContact->handle($details, $session->brief?->contact, [
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
