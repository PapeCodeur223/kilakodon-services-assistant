<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Conversation\AssistantGuard;
use App\Domain\Assistant\Conversation\GuardFailure;
use App\Domain\Assistant\Models\AiUsageDaily;
use App\Domain\Assistant\Models\Conversation;
use App\Domain\Platform\Support\IpHasher;
use Illuminate\Support\Str;

/**
 * Ouvre une conversation anonyme (ULID) après les gardes : interrupteurs, budget, 5 conversations
 * par visiteur et par jour. Aucune donnée personnelle : clé visiteur aléatoire et IP hachée.
 */
class StartConversation
{
    public function __construct(
        private readonly AssistantGuard $guard,
        private readonly IpHasher $ipHasher,
    ) {}

    public function handle(?string $visitorKey, string $ip, ?string $entryPath): Conversation|GuardFailure
    {
        $visitorKey = $visitorKey !== null && Str::isUuid($visitorKey) ? $visitorKey : null;
        $ipHash = $this->ipHasher->hash($ip);

        if (($failure = $this->guard->checkStart($visitorKey, $ipHash)) !== null) {
            AiUsageDaily::today()->increment('blocked_requests');

            return $failure;
        }

        $path = $entryPath !== null && str_starts_with($entryPath, '/') ? mb_substr($entryPath, 0, 255) : null;

        return Conversation::query()->create([
            'visitor_key' => $visitorKey,
            'ip_hash' => $ipHash,
            'entry_path' => $path,
            'last_message_at' => now(),
        ]);
    }
}
