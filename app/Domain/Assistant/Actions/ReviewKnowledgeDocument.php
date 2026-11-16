<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Enums\KnowledgeStatus;
use App\Domain\Assistant\Models\KnowledgeDocument;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Approbation (ou retrait) d'un document de la base de connaissances : seul un admin rend un document
 * interrogeable par l'assistant (PRD §4.2). Journalisé.
 */
class ReviewKnowledgeDocument
{
    public function __construct(
        private readonly RebuildKnowledge $rebuild,
        private readonly RecordAudit $audit,
    ) {}

    public function approve(KnowledgeDocument $document, User $by): KnowledgeDocument
    {
        if (! $by->isAdmin()) {
            throw new AuthorizationException('Seul un administrateur peut approuver un document.');
        }

        if ($document->isManual()) {
            $this->rebuild->forManual($document);
        }

        $document->forceFill([
            'status' => KnowledgeStatus::Approved,
            'approved_by' => $by->id,
            'approved_at' => now(),
        ])->save();

        $this->audit->handle(AuditAction::Published, $document, ['after' => ['status' => 'approved', 'version' => $document->version]], $by);

        return $document;
    }

    public function retire(KnowledgeDocument $document, User $by): KnowledgeDocument
    {
        if (! $by->isAdmin()) {
            throw new AuthorizationException('Seul un administrateur peut retirer un document.');
        }

        $document->forceFill(['status' => KnowledgeStatus::Retired])->save();
        $this->audit->handle(AuditAction::StatusChanged, $document, ['after' => ['status' => 'retired']], $by);

        return $document;
    }
}
