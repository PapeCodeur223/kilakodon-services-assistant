<?php

namespace Database\Seeders;

use App\Domain\Platform\Enums\UserRole;
use App\Domain\Platform\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de développement — exécuté uniquement en environnement local.
 * Comptes fictifs sur le domaine réservé .test ; mot de passe commun de développement.
 */
class DevSeeder extends Seeder
{
    public const PASSWORD = 'kilakodon-dev-2026';

    public function run(): void
    {
        $users = [
            ['name' => 'Admin Démo', 'email' => 'admin@kilakodon.test', 'role' => UserRole::Admin],
            ['name' => 'Commercial Démo', 'email' => 'sales@kilakodon.test', 'role' => UserRole::Sales],
            ['name' => 'Éditeur Démo', 'email' => 'editor@kilakodon.test', 'role' => UserRole::Editor],
        ];

        foreach ($users as $user) {
            User::query()->firstOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'role' => $user['role'], 'password' => self::PASSWORD, 'is_active' => true],
            );
        }
    }
}
