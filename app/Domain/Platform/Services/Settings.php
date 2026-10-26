<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\Models\Setting;
use App\Domain\Platform\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\QueryException;

/**
 * Paramètres clé/valeur, mis en cache 10 min et invalidés à chaque sauvegarde.
 * Une clé absente de la base retombe sur config('kh.settings').
 */
class Settings
{
    public const CACHE_KEY = 'kh.settings';

    public const TTL_SECONDS = 600;

    public function __construct(private readonly Cache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = config('kh.settings', []);

        return array_replace($defaults, $this->stored());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value, ?User $by = null): Setting
    {
        $setting = Setting::query()->firstOrNew(['key' => $key]);
        $setting->value = $value;
        $setting->updated_by = $by?->id;
        $setting->save();

        return $setting;
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        try {
            return $this->cache->remember(
                self::CACHE_KEY,
                self::TTL_SECONDS,
                fn (): array => Setting::query()->pluck('value', 'key')->all(),
            );
        } catch (QueryException) {
            // Base indisponible (page d'erreur, installation) : valeurs par défaut.
            return [];
        }
    }
}
