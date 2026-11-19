<?php

namespace App\Console\Commands;

use App\Domain\Assistant\Actions\PurgeConversations;
use Illuminate\Console\Command;

/**
 * Purge quotidienne des conversations non liées à un lead (PRV-03, TRD §10).
 */
class ConversationsPurge extends Command
{
    protected $signature = 'kh:conversations-purge';

    protected $description = 'Supprime les conversations de l\'assistant non liées à un lead, inactives depuis plus de 90 jours';

    public function handle(PurgeConversations $purge): int
    {
        $count = $purge->handle();

        $this->info("{$count} conversation(s) supprimée(s).");

        return self::SUCCESS;
    }
}
