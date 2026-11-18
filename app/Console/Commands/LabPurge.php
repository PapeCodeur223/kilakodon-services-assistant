<?php

namespace App\Console\Commands;

use App\Domain\DemoLab\Actions\PurgeDemoWorkspaces;
use Illuminate\Console\Command;

/**
 * Purge horaire des espaces Démo Lab expirés (TRD §8).
 */
class LabPurge extends Command
{
    protected $signature = 'kh:lab-purge';

    protected $description = 'Supprime les espaces Démo Lab expirés et toutes leurs données fictives';

    public function handle(PurgeDemoWorkspaces $purge): int
    {
        $this->info($purge->handle().' espace(s) Démo Lab supprimé(s).');

        return self::SUCCESS;
    }
}
