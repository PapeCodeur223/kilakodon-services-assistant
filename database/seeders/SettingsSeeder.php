<?php

namespace Database\Seeders;

use App\Domain\Platform\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Valeurs initiales des paramètres (config/kh.php). Ne remplace jamais une valeur existante.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, mixed> $defaults */
        $defaults = config('kh.settings', []);

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
