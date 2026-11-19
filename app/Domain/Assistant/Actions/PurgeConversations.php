<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Models\Conversation;
use App\Domain\Brief\Models\Brief;

/**
 * Supprime les conversations de l'assistant non liées à un lead ni à un brief, sans activité depuis
 * plus de 90 jours (PRV-03, TRD §10), avec leurs messages, retours et propositions (cascade).
 */
class PurgeConversations
{
    public const RETENTION_DAYS = 90;

    public function handle(): int
    {
        $limit = now()->subDays(self::RETENTION_DAYS);
        $deleted = 0;

        Conversation::query()
            ->whereNull('lead_id')
            ->whereNotIn('id', Brief::query()->whereNotNull('conversation_id')->select('conversation_id'))
            ->where(fn ($query) => $query->where('last_message_at', '<', $limit)
                ->orWhere(fn ($q) => $q->whereNull('last_message_at')->where('created_at', '<', $limit)))
            ->select('id')
            ->chunkById(200, function ($conversations) use (&$deleted): void {
                $deleted += Conversation::query()->whereKey($conversations->modelKeys())->delete();
            });

        return $deleted;
    }
}
