<?php

namespace Database\Seeders;

use App\Domain\Platform\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Valeurs initiales des paramètres (config/kh.php). Ne remplace jamais une valeur existante.
 * En local uniquement (BACKEND_SCHEMA §9) : assistant activé avec un budget de 1 USD,
 * le pilote `fake` n'appelant aucune API. Hors local : IA coupée tant que Q-009 n'est pas tranchée.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, mixed> $values */
        $values = config('kh.settings', []);

        if (app()->isLocal()) {
            $values['ai.enabled'] = true;
            $values['ai.daily_budget_usd'] = 1;
        }

        foreach ($values as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
