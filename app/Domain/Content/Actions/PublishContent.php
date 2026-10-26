<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Concerns\Publishable;
use App\Domain\Content\Enums\AuthorizationStatus;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Exceptions\ContentTransitionException;
use App\Domain\Content\Models\CaseStudy;
use App\Domain\Content\Services\SitemapBuilder;
use App\Domain\Content\Support\PublishGuard;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Seul chemin vers le statut « publié » (WEB-01, BACKEND_SCHEMA §8.1).
 * Refuse : un non-admin, un contenu qui contient « [À VALIDER] », une réalisation non autorisée.
 */
class PublishContent
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /**
     * @param  Model&Publishable  $content
     *
     * @throws AuthorizationException
     * @throws ContentTransitionException
     */
    public function handle(Model $content, User $by): Model
    {
        if ($by->cannot('publish', $content)) {
            throw new AuthorizationException('Seul un administrateur peut publier.');
        }

        if ($content->status === ContentStatus::Published) {
            return $content;
        }

        $pending = $content->pendingValidationFields();
        if ($pending !== []) {
            throw new ContentTransitionException('Contenu à compléter avant publication ([À VALIDER]) : '.implode(', ', $pending).'.');
        }

        if ($content instanceof CaseStudy && $content->authorization_status !== AuthorizationStatus::Granted) {
            throw new ContentTransitionException('Publication impossible : l’autorisation écrite du client n’est pas accordée.');
        }

        return DB::transaction(function () use ($content, $by): Model {
            $from = $content->status;

            PublishGuard::allow(function () use ($content): void {
                $content->status = ContentStatus::Published;
                $content->published_at ??= now();
                $content->save();
            });

            $this->recordAudit->handle(AuditAction::Published, $content, [
                'before' => ['status' => $from->value],
                'after' => ['status' => ContentStatus::Published->value],
            ], $by);

            Cache::forget(SitemapBuilder::CACHE_KEY);

            return $content;
        });
    }
}
