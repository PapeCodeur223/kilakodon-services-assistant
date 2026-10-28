<?php

namespace App\Domain\Brief\Actions;

use App\Domain\Brief\Enums\BriefStatus;
use App\Domain\Brief\Enums\SummarySource;
use App\Domain\Brief\Enums\VersionAuthor;
use App\Domain\Brief\Exceptions\BriefRequestException;
use App\Domain\Brief\Models\Brief;
use App\Domain\Brief\Services\BriefContent;
use App\Domain\Brief\Support\ReferenceGenerator;
use App\Domain\Crm\Models\Contact;
use App\Domain\Diagnostic\Engine\VisibilityResolver;
use App\Domain\Diagnostic\Enums\SessionStatus;
use App\Domain\Diagnostic\Models\DiagnosticSession;
use App\Domain\Diagnostic\Services\DiagnosticSummaryWriter;
use App\Domain\Platform\Support\PublicToken;
use InvalidArgumentException;

/**
 * Brief issu d'un diagnostic terminé : référence KLK, version 1 (brouillon à relire) et lien privé
 * de 30 jours. Un second brief pour le même diagnostic est refusé.
 */
class CreateBriefFromDiagnostic
{
    public function __construct(
        private readonly ReferenceGenerator $references,
        private readonly BriefContent $content,
        private readonly DiagnosticSummaryWriter $summaries,
        private readonly VisibilityResolver $visibility,
    ) {}

    /**
     * @return array{brief: Brief, token: string}
     */
    public function handle(DiagnosticSession $session, Contact $contact, ?string $company): array
    {
        $session->loadMissing(['template', 'result', 'brief']);
        $result = $session->result ?? throw new InvalidArgumentException('Le diagnostic n\'est pas terminé.');
        $token = PublicToken::generate();

        // Un diagnostic = un brief : jamais de nouveau lien (cela annulerait une révocation).
        if ($session->brief !== null) {
            throw BriefRequestException::alreadyRequested($session->brief->reference);
        }

        $template = $session->template->toDefinition();
        $outcome = $result->toOutcome();
        $answers = $this->visibility->prune($template, $session->answerMap());

        $brief = Brief::query()->create([
            'reference' => $this->references->next(),
            'diagnostic_session_id' => $session->id,
            'contact_id' => $contact->id,
            'status' => BriefStatus::Draft,
            'current_version' => 1,
            'access_token_hash' => $token['hash'],
            'access_expires_at' => now()->addDays(Brief::ACCESS_DAYS),
        ]);

        $brief->versions()->create([
            'version' => 1,
            'declared' => $this->content->declared($template, $answers, $contact, $company),
            'recommended' => $this->content->recommended($outcome),
            'summary' => $result->ai_summary ?? $this->summaries->template($outcome),
            'summary_source' => $result->ai_summary !== null ? SummarySource::Ai : SummarySource::Template,
            'created_by' => VersionAuthor::Prospect,
        ]);

        $session->forceFill(['status' => SessionStatus::Converted])->save();

        return ['brief' => $brief, 'token' => $token['token']];
    }
}
