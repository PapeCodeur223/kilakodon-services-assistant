<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Enums\AuthorizationStatus;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Exceptions\ContentTransitionException;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Un admin atteste l'autorisation du client (preuve : date, canal, interlocuteur).
 */
class AttestCaseStudyAuthorization
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /**
     * @throws AuthorizationException
     * @throws ContentTransitionException
     */
    public function handle(CaseStudy $caseStudy, AuthorizationStatus $status, ?string $note, User $by): CaseStudy
    {
        if ($by->cannot('attestAuthorization', $caseStudy)) {
            throw new AuthorizationException('Seul un administrateur peut attester une autorisation client.');
        }

        if ($status === AuthorizationStatus::Granted && blank($note)) {
            throw new ContentTransitionException('Indiquez la preuve de l’autorisation (date, canal, interlocuteur).');
        }

        if ($caseStudy->status === ContentStatus::Published && $status !== AuthorizationStatus::Granted) {
            throw new ContentTransitionException('Dépubliez d’abord la réalisation avant de retirer son autorisation.');
        }

        return DB::transaction(function () use ($caseStudy, $status, $note, $by): CaseStudy {
            $before = $caseStudy->authorization_status;

            $caseStudy->forceFill([
                'authorization_status' => $status,
                'authorization_note' => $note,
                'authorized_by' => $status === AuthorizationStatus::Granted ? $by->id : null,
                'authorized_at' => $status === AuthorizationStatus::Granted ? now() : null,
            ])->save();

            $this->recordAudit->handle(AuditAction::AuthorizationAttested, $caseStudy, [
                'before' => ['authorization_status' => $before->value],
                'after' => ['authorization_status' => $status->value],
            ], $by);

            return $caseStudy;
        });
    }
}
