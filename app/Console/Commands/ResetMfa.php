<?php

namespace App\Console\Commands;

use App\Domain\Platform\Actions\ResetMultiFactor;
use App\Domain\Platform\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

/**
 * Procédure de secours : un utilisateur a perdu son application d'authentification et ses codes de secours.
 * À lancer uniquement après avoir vérifié l'identité de la personne (RUNBOOK).
 */
class ResetMfa extends Command
{
    protected $signature = 'kh:mfa-reset
        {email : Adresse e-mail du compte}
        {--force : Ne pas demander de confirmation}';

    protected $description = 'Réinitialise la double authentification d\'un utilisateur (nouvelle configuration à la prochaine connexion)';

    public function handle(ResetMultiFactor $reset): int
    {
        $user = User::query()->where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();

        if ($user === null) {
            $this->components->error('Aucun utilisateur avec cette adresse.');

            return self::FAILURE;
        }

        if ($user->getAppAuthenticationSecret() === null) {
            $this->components->info('La double authentification de ce compte n\'est pas configurée : rien à réinitialiser.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! confirm("Réinitialiser la double authentification de {$user->email} ?", default: false)) {
            $this->components->warn('Opération annulée.');

            return self::FAILURE;
        }

        $reset->handle($user);
        $this->components->info("Double authentification réinitialisée pour {$user->email}. Un nouveau QR code sera demandé à la prochaine connexion.");

        return self::SUCCESS;
    }
}
