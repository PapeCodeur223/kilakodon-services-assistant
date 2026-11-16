<?php

namespace Database\Seeders;

use App\Domain\Platform\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Valeurs initiales des paramètres (config/kh.php). Ne remplace jamais une valeur existante.
 * En local uniquement (BACKEND_SCHEMA §9) : assistant activé. Budget quotidien : 0,50 USD partout (D-023).
 * Hors local, l'IA reste coupée tant qu'un administrateur ne l'active pas dans les paramètres.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, mixed> $values */
        $values = config('kh.settings', []);

        if (app()->isLocal()) {
            $values['ai.enabled'] = true;
        }

        foreach ($values as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
