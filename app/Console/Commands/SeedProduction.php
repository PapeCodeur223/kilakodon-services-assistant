<?php

namespace App\Console\Commands;

use App\Domain\Platform\Models\Setting;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Console\Command;

/**
 * Données de production (lot 7), appelée par deploy/kh-deploy.sh à chaque mise en ligne :
 * - base vide (première installation) : réglages, contenus validés, questionnaire, base de connaissances
 *   (DatabaseSeeder ; le DevSeeder et ses comptes de test ne tournent jamais hors local) ;
 * - base déjà installée : seulement les nouveaux réglages (firstOrCreate), sans jamais écraser
 *   les contenus ni les paramètres modifiés par l'équipe dans l'admin.
 */
class SeedProduction extends Command
{
    protected $signature = 'kh:seed-production';

    protected $description = 'Initialise la base de production à la première installation, sinon ajoute seulement les nouveaux réglages';

    public function handle(): int
    {
        if ($this->laravel->isLocal()) {
            $this->warn('Environnement local : utilisez php artisan db:seed (données de développement).');

            return self::FAILURE;
        }

        if (! Setting::query()->exists()) {
            $this->info('Base vide : installation des réglages, contenus validés, questionnaire et base de connaissances…');
            $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
            $this->info('Installation terminée. Créez maintenant un administrateur : php artisan kh:make-admin');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => SettingsSeeder::class, '--force' => true]);
        $this->info('Base déjà installée : seuls les nouveaux réglages ont été ajoutés (rien n’a été écrasé).');

        return self::SUCCESS;
    }
}
