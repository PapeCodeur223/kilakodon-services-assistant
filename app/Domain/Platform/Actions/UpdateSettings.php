<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Models\User;
use App\Domain\Platform\Services\Settings;
use Illuminate\Support\Facades\DB;

class UpdateSettings
{
    public function __construct(private readonly Settings $settings) {}

    /**
     * Enregistre uniquement les clés modifiées. Toute modification des textes
     * de consentement incrémente leur version (les consentements passés gardent la leur).
     *
     * @param  array<string, mixed>  $values
     * @return list<string> clés modifiées
     */
    public function handle(array $values, User $by): array
    {
        $current = $this->settings->all();
        $changed = [];

        if (isset($values['consent.texts']) && is_array($values['consent.texts'])) {
            $values['consent.texts'] = $this->versionConsentTexts($values['consent.texts'], (array) ($current['consent.texts'] ?? []));
        }

        DB::transaction(function () use ($values, $current, $by, &$changed): void {
            foreach ($values as $key => $value) {
                if (array_key_exists($key, $current) && $current[$key] === $value) {
                    continue;
                }

                $this->settings->set($key, $value, $by);
                $changed[] = $key;
            }
        });

        return $changed;
    }

    /**
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $old
     * @return array<string, mixed>
     */
    private function versionConsentTexts(array $new, array $old): array
    {
        $version = (int) ($old['version'] ?? 1);
        $textChanged = ($new['processing'] ?? null) !== ($old['processing'] ?? null)
            || ($new['marketing'] ?? null) !== ($old['marketing'] ?? null);

        return [
            'version' => $textChanged ? $version + 1 : $version,
            'processing' => $new['processing'] ?? null,
            'marketing' => $new['marketing'] ?? null,
        ];
    }
}
