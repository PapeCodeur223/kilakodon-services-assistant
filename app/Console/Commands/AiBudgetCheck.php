<?php

namespace App\Console\Commands;

use App\Domain\Assistant\Actions\CheckAssistantBudget;
use Illuminate\Console\Command;

/**
 * Budget quotidien de l'assistant : alerte à 80 % (TRD §10, toutes les 15 minutes).
 */
class AiBudgetCheck extends Command
{
    protected $signature = 'kh:ai-budget-check';

    protected $description = 'Vérifie la dépense IA du jour et alerte les administrateurs à 80 % du budget';

    public function handle(CheckAssistantBudget $check): int
    {
        $result = $check->handle();

        if ($result['ratio'] === null) {
            $this->warn('Budget quotidien non fixé (paramètre ai.daily_budget_usd) : seul le pilote factice peut fonctionner.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Dépense du jour : %.4f $ sur %.2f $ (%d %%)%s',
            $result['spent_micros'] / 1_000_000,
            $result['budget_micros'] / 1_000_000,
            (int) floor($result['ratio'] * 100),
            $result['alerted'] ? ' — alerte envoyée.' : '.',
        ));

        return self::SUCCESS;
    }
}
