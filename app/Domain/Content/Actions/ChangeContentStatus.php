<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Concerns\Publishable;
use App\Domain\Content\Enums\ContentStatus;
use App\Domain\Content\Exceptions\ContentTransitionException;
use App\Domain\Content\Services\SitemapBuilder;
use App\Domain\Platform\Actions\RecordAudit;
use App\Domain\Platform\Enums\AuditAction;
use App\Domain\Platform\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Transitions hors publication. La publication passe par PublishContent.
 */
class ChangeContentStatus
{
    /**
     * Transition => réservée aux admins ?
     */
    private const TRANSITIONS = [
        'draft>review' => false,
        'review>draft' => false,
        'draft>archived' => true,
        'review>archived' => true,
        'published>draft' => true,
        'published>archived' => true,
        'archived>draft' => true,
    ];

    public function __construct(private readonly RecordAudit $recordAudit) {}

    /**
     * @param  Model&Publishable  $content
     *
     * @throws AuthorizationException
     * @throws ContentTransitionException
     */
    public function handle(Model $content, ContentStatus $to, User $by): Model
    {
        $from = $content->status;
        $key = $from->value.'>'.$to->value;

        if ($to === ContentStatus::Published || ! array_key_exists($key, self::TRANSITIONS)) {
            throw new ContentTransitionException("Transition impossible : {$from->label()} → {$to->label()}.");
        }

        $adminOnly = self::TRANSITIONS[$key];
        if (($adminOnly && ! $by->isAdmin()) || $by->cannot('changeStatus', $content)) {
            throw new AuthorizationException('Action réservée à un administrateur.');
        }

        return DB::transaction(function () use ($content, $from, $to, $by): Model {
            $content->status = $to;
            $content->save();

            $this->recordAudit->handle(AuditAction::StatusChanged, $content, [
                'before' => ['status' => $from->value],
                'after' => ['status' => $to->value],
            ], $by);

            Cache::forget(SitemapBuilder::CACHE_KEY);

            return $content;
        });
    }
}
