<?php

namespace App\Console\Commands;

use App\Domain\Platform\Actions\CreateUser;
use App\Domain\Platform\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeAdmin extends Command
{
    protected $signature = 'kh:make-admin
        {--name= : Nom complet}
        {--email= : Adresse e-mail}';

    protected $description = 'Crée un administrateur (mot de passe saisi de façon masquée ; MFA demandée à la première connexion)';

    public function handle(CreateUser $createUser): int
    {
        $name = $this->option('name') ?: text('Nom complet', required: true);
        $email = $this->option('email') ?: text('Adresse e-mail', required: true);
        $password = password('Mot de passe (12 caractères minimum, lettres et chiffres)', required: true);
        $confirmation = password('Confirmez le mot de passe', required: true);

        if ($password !== $confirmation) {
            $this->components->error('Les mots de passe ne correspondent pas.');

            return self::FAILURE;
        }

        try {
            $user = $createUser->handle((string) $name, (string) $email, $password, UserRole::Admin);
        } catch (ValidationException $e) {
            foreach ($e->validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $this->components->info("Administrateur créé : {$user->email}. La MFA sera demandée à la première connexion sur /admin.");

        return self::SUCCESS;
    }
}
