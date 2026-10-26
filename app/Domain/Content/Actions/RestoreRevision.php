<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Concerns\HasRevisions;
use App\Domain\Content\Concerns\Publishable;
use App\Domain\Content\Models\ContentRevision;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Restaure les champs éditoriaux d'une révision ; la restauration devient une nouvelle version.
 * Le statut n'est jamais modifié.
 */
class RestoreRevision
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /**
     * @param  Model&Publishable&HasRevisions  $content
     *
     * @throws AuthorizationException
     */
    public function handle(Model $content, ContentRevision $revision, User $by): Model
    {
        if ($revision->revisionable_type !== $content->getMorphClass() || (int) $revision->revisionable_id !== (int) $content->getKey()) {
            throw new InvalidArgumentException('Cette révision appartient à un autre contenu.');
        }

        if ($by->cannot('update', $content)) {
            throw new AuthorizationException('Vous ne pouvez pas modifier ce contenu.');
        }

        return DB::transaction(function () use ($content, $revision, $by): Model {
            $data = array_intersect_key($revision->data, array_flip($content->revisionFields()));
            $content->forceFill($data)->save();

            $this->recordAudit->handle(AuditAction::RevisionRestored, $content, ['after' => ['version' => $revision->version]], $by);

            return $content;
        });
    }
}
