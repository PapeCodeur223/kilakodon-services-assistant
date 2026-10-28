<?php

namespace App\Console\Commands;

use App\Domain\Diagnostic\Actions\PurgeDiagnosticSessions;
use Illuminate\Console\Command;

/**
 * Purge quotidienne des sessions de diagnostic anonymes expirées (TRD §10).
 */
class DiagnosticPurge extends Command
{
    protected $signature = 'kh:diagnostic-purge';

    protected $description = 'Supprime les sessions de diagnostic anonymes expirées (plus de 30 jours)';

    public function handle(PurgeDiagnosticSessions $purge): int
    {
        $count = $purge->handle();

        $this->info("{$count} session(s) de diagnostic supprimée(s).");

        return self::SUCCESS;
    }
}
