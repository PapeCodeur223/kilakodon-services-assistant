<?php

namespace App\Console\Commands;

use App\Domain\Content\Services\PendingValidationReport;
use Illuminate\Console\Command;

/**
 * Liste les « [À VALIDER] » restants (critère Go/No-Go, PRD §8).
 * Code de sortie 1 tant qu'il en reste dans les contenus publiés, les paramètres ou l'accueil.
 * La même liste est visible dans l'administration (page « À valider »).
 */
class ContentAudit extends Command
{
    protected $signature = 'kh:content-audit {--all : Inclure aussi les brouillons}';

    protected $description = 'Liste les informations « [À VALIDER] » restantes (contenus publiés, paramètres, accueil)';

    public function handle(PendingValidationReport $report): int
    {
        $items = $report->items(includeDrafts: (bool) $this->option('all'));

        if ($items === []) {
            $this->components->info('Aucun « [À VALIDER] » restant.');

            return self::SUCCESS;
        }

        $this->table(
            ['Type', 'Élément', 'Statut', 'Champ'],
            array_map(fn (array $item): array => [
                $item['type'],
                $item['source'] === 'home' ? 'resources/content/home.php' : (string) ($item['record']?->getAttributes()['slug'] ?? $item['element']),
                $item['status'],
                $item['field'],
            ], $items),
        );
        $this->components->warn(count($items).' information(s) à valider par Kilakodon avant la mise en production.');

        return self::FAILURE;
    }
}
