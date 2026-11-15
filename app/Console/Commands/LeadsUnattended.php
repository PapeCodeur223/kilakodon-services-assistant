<?php

namespace App\Console\Commands;

use App\Domain\Crm\Actions\AlertUnattendedLeads;
use Illuminate\Console\Command;

/**
 * Alerte horaire des leads sans réponse depuis plus de 24 h ouvrées (TRD §10).
 */
class LeadsUnattended extends Command
{
    protected $signature = 'kh:leads-unattended';

    protected $description = 'Alerte les administrateurs des leads sans première réponse depuis plus de 24 h ouvrées';

    public function handle(AlertUnattendedLeads $alert): int
    {
        $ids = $alert->handle();

        $this->info($ids === [] ? 'Aucun lead en attente.' : count($ids).' lead(s) signalé(s) : #'.implode(', #', $ids));

        return self::SUCCESS;
    }
}
